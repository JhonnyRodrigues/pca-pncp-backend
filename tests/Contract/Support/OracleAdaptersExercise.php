<?php

declare(strict_types=1);

namespace Tests\Contract\Support;

use RuntimeException;
use Semae\Pca\Application\Enums\SituacaoConsultaEstadoOperacional;
use Semae\Pca\Application\Enums\SituacaoConsultaEstrategia;
use Semae\Pca\Application\Enums\SituacaoFinalizacaoUnidadeTrabalho;
use Semae\Pca\Application\Enums\SituacaoInicioUnidadeTrabalho;
use Semae\Pca\Application\Enums\SituacaoPersistenciaLocal;
use Semae\Pca\Application\Enums\SituacaoResolucaoContextoUsuario;
use Semae\Pca\Application\ValueObjects\ChaveUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\IntencaoRegistroHistorico;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Enums\ProcedimentoPublicacaoPca;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Oracle\Auditoria\MapeamentoHistoricosOracle;
use Throwable;

final class OracleAdaptersExercise
{
    private string $currentStage = 'bind';

    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly BorrowedAdodbGuard $guard,
        private readonly ConexaoAdodbEmprestada $connection,
        private readonly OracleAdaptersSet $adapters,
        private readonly MapeamentoHistoricosOracle $historyMapping,
        private readonly array $config,
        private readonly string $bindMarker
    ) {
    }

    /** @return array<string, mixed> */
    public function run(string $mode): array
    {
        if (!in_array($mode, [
            'commit',
            'adapters',
            'lock_holder',
            'lock_expect_blocked',
            'lock_expect_available',
        ], true)) {
            throw new RuntimeException('Modo Oracle inválido.');
        }

        return match ($mode) {
            'commit' => $this->runCommit(),
            'adapters' => $this->runAdapters(),
            default => $this->runLock($mode),
        };
    }

    /** @return array<string, mixed> */
    private function runCommit(): array
    {
        $this->currentStage = 'provisionamento_tecnico';

        try {
            return $this->executeCommit();
        } catch (Throwable $exception) {
            $this->rollbackBestEffort();

            throw new OracleContractFailure($this->currentStage, $exception);
        }
    }

    /** @return array<string, mixed> */
    private function executeCommit(): array
    {
        $sessionBefore = $this->sessionFingerprint();
        $sequenceStart = $this->guard->countRecordedCalls();
        $transactionsBefore = $this->guard->countCalls('BeginTrans');
        $executionsBefore = $this->guard->countCalls('Execute');
        $commitsBefore = $this->guard->countCalls('CommitTrans');
        $key = ChaveUnidadeTrabalho::estrategiaAnual(
            new AnoExecucao($this->config['ano'])
        );
        $started = $this->adapters->getUnitOfWork()->iniciar($key);
        $technicalTransaction = $this->guard->countCalls('BeginTrans')
            >= $transactionsBefore + 2;
        $technicalProvisioning = $this->guard->countCalls('Execute')
            > $executionsBefore;
        $technicalCommit = $this->guard->countCalls('CommitTrans') > $commitsBefore;
        $orderedStart = $this->guard->hasCallsInOrderAfter([
            'BeginTrans',
            'Execute',
            'CommitTrans',
            'BeginTrans',
            'GetAll',
        ], $sequenceStart);

        if (
            !$technicalTransaction
            || !$technicalProvisioning
            || !$technicalCommit
            || !$orderedStart
        ) {
            throw new RuntimeException(
                'O provisionamento técnico não precedeu a transação controlada.'
            );
        }

        $this->currentStage = 'lock_controlado';
        if (
            $started->getSituacao() !== SituacaoInicioUnidadeTrabalho::INICIADA
            || !$this->connection->estaEmTransacao()
            || $this->sessionFingerprint() !== $sessionBefore
        ) {
            throw new RuntimeException('A transação controlada não adquiriu o lock na mesma sessão.');
        }

        $commitsAfterProvisioning = $this->guard->countCalls('CommitTrans');
        $this->currentStage = 'commit_controlado';
        $confirmed = $this->adapters->getUnitOfWork()->confirmar();

        if (
            $confirmed->getSituacao() !== SituacaoFinalizacaoUnidadeTrabalho::CONCLUIDA
            || $this->connection->estaEmTransacao()
            || $this->guard->countCalls('CommitTrans') <= $commitsAfterProvisioning
        ) {
            throw new RuntimeException('O commit controlado não foi efetivamente concluído.');
        }

        $this->currentStage = 'verificacao_liberacao';
        $reacquired = $this->adapters->getUnitOfWork()->iniciar($key);

        if ($reacquired->getSituacao() !== SituacaoInicioUnidadeTrabalho::INICIADA) {
            throw new RuntimeException('O lock não pôde ser readquirido após o commit controlado.');
        }

        $released = $this->adapters->getUnitOfWork()->desfazer();
        if ($released->getSituacao() !== SituacaoFinalizacaoUnidadeTrabalho::CONCLUIDA) {
            throw new RuntimeException('A verificação do lock não foi desfeita.');
        }

        $this->currentStage = 'conexao_reutilizavel';
        if (
            $this->sessionFingerprint() !== $sessionBefore
            || $this->connection->estaEmTransacao()
            || $this->guard->wasCloseCalled()
        ) {
            throw new RuntimeException('A conexão não permaneceu reutilizável após o commit.');
        }

        return [
            'transacao_tecnica_iniciada' => true,
            'provisionamento_idempotente_executado' => true,
            'commit_tecnico_provisionamento' => true,
            'linha_controle_disponivel_apos_provisionamento' => true,
            'transacao_controlada_iniciada' => true,
            'lock_controlado_adquirido' => true,
            'mesma_sessao' => true,
            'commit_controlado_concluido' => true,
            'lock_liberado_apos_commit' => true,
            'conexao_reutilizavel' => true,
            'alteracao_negocio_executada' => false,
            'close_invocado' => false,
        ];
    }

    /** @return array<string, mixed> */
    private function runAdapters(): array
    {
        $this->currentStage = 'bind';

        try {
            return $this->executeAdapters();
        } catch (Throwable $exception) {
            $this->rollbackBestEffort();

            throw new OracleContractFailure($this->currentStage, $exception);
        }
    }

    /** @return array<string, mixed> */
    private function executeAdapters(): array
    {
        $year = new AnoExecucao($this->config['ano']);
        $sessionBefore = $this->sessionFingerprint();
        $bindRows = $this->connection->buscarTodos(
            'SELECT ? AS VALOR_BIND FROM DUAL',
            [$this->bindMarker]
        );
        if (self::firstScalar($bindRows, 'VALOR_BIND') !== $this->bindMarker) {
            throw new RuntimeException('O bind Oracle não preservou o valor esperado.');
        }

        $this->currentStage = 'estrategia_corrente';
        $currentStrategy = $this->adapters->getStrategy()->consultarCorrente($year);
        if ($currentStrategy->getSituacao() !== SituacaoConsultaEstrategia::DISPONIVEL) {
            throw new RuntimeException('A estratégia corrente Oracle não ficou disponível.');
        }

        $this->currentStage = 'estrategia_adotada';
        $adoptedStrategy = $this->adapters->getStrategy()->consultarAdotada($year);
        if ($adoptedStrategy->getSituacao() !== SituacaoConsultaEstrategia::DISPONIVEL) {
            throw new RuntimeException('A estratégia adotada Oracle não ficou disponível.');
        }

        $this->currentStage = 'contexto_usuario';
        $resolvedContext = $this->adapters->getContext()->resolver();
        if ($resolvedContext->getSituacao() !== SituacaoResolucaoContextoUsuario::RESOLVIDO) {
            throw new RuntimeException('O contexto Oracle do usuário não foi resolvido.');
        }
        $context = $resolvedContext->getContexto();
        if ($context === null) {
            throw new RuntimeException('O resultado tipado de contexto veio sem dados.');
        }

        $this->currentStage = 'identidade_plano';
        $identity = $this->findIdentity($year);
        $unit = UnidadeExternaPublicacao::individual($identity);
        $this->currentStage = 'inicio_transacao';
        $started = $this->adapters->getUnitOfWork()->iniciar(
            ChaveUnidadeTrabalho::unidadeExterna($unit)
        );
        if ($started->getSituacao() !== SituacaoInicioUnidadeTrabalho::INICIADA) {
            throw new RuntimeException('A unidade de trabalho Oracle não foi iniciada.');
        }

        $rollbackDone = false;
        try {
            if ($this->sessionFingerprint() !== $sessionBefore) {
                throw new RuntimeException('Os adapters Oracle não permaneceram na mesma sessão.');
            }

            $this->currentStage = 'reconstrucao_plano';
            $plan = $this->adapters->getPlans()->reconstruirFotografiaVigente($identity);
            if ($plan->possuiSincronizacaoConfirmada()) {
                throw new RuntimeException('A massa Oracle deve possuir confirmação pendente.');
            }

            $this->currentStage = 'consulta_estado';
            $stateBeforeResult = $this->adapters->getState()->consultar($unit);
            if ($stateBeforeResult->getSituacao() !== SituacaoConsultaEstadoOperacional::DISPONIVEL) {
                throw new RuntimeException('O estado operacional Oracle não ficou disponível.');
            }
            $stateBefore = $stateBeforeResult->getEstadoOperacional();
            if ($stateBefore === null) {
                throw new RuntimeException('O estado operacional tipado veio sem valor.');
            }

            $this->currentStage = 'registro_auditoria';
            $historyBefore = $this->historyCount();
            $this->currentStage = 'consulta_estado';
            $temporaryState = EstadoOperacionalPublicacao::publicado(
                new SequencialPca($this->config['sequencial_teste'])
            );
            if ($this->sameState($stateBefore, $temporaryState)) {
                throw new RuntimeException('A massa Oracle já coincide com o estado temporário.');
            }

            $this->currentStage = 'confirmacao_plano';
            $this->adapters->getPlans()->confirmarSincronizacao($plan);
            if (
                !$this->adapters->getPlans()
                    ->reconstruirFotografiaVigente($identity)
                    ->possuiSincronizacaoConfirmada()
            ) {
                throw new RuntimeException('A confirmação do Plano não ficou visível na transação.');
            }

            $this->currentStage = 'registro_estado';
            $stateWrite = $this->adapters->getState()->registrar($unit, $temporaryState);
            if ($stateWrite->getSituacao() !== SituacaoPersistenciaLocal::CONCLUIDA) {
                throw new RuntimeException('A escrita do estado operacional Oracle falhou.');
            }

            $this->currentStage = 'registro_auditoria';
            $auditWrite = $this->adapters->getAudit()->registrar(
                new IntencaoRegistroHistorico(
                    ProcedimentoPublicacaoPca::PUBLICACAO_INICIAL,
                    $this->config['mensagem_auditoria'],
                    $context->getRepresentante(),
                    $context->getLotacao()
                )
            );
            if ($auditWrite->getSituacao() !== SituacaoPersistenciaLocal::CONCLUIDA) {
                throw new RuntimeException('A escrita de auditoria Oracle falhou.');
            }
            if ($this->historyCount() !== $historyBefore + 1) {
                throw new RuntimeException('A auditoria não ficou visível dentro da transação.');
            }

            $this->currentStage = 'rollback';
            $rollback = $this->adapters->getUnitOfWork()->desfazer();
            if ($rollback->getSituacao() !== SituacaoFinalizacaoUnidadeTrabalho::CONCLUIDA) {
                throw new RuntimeException('O rollback Oracle não foi concluído.');
            }
            $rollbackDone = true;

            $this->currentStage = 'verificacao_rollback';
            $verifyStarted = $this->adapters->getUnitOfWork()->iniciar(
                ChaveUnidadeTrabalho::unidadeExterna($unit)
            );
            if ($verifyStarted->getSituacao() !== SituacaoInicioUnidadeTrabalho::INICIADA) {
                throw new RuntimeException('A verificação posterior ao rollback não iniciou.');
            }
            try {
                $restoredPlan = $this->adapters->getPlans()
                    ->reconstruirFotografiaVigente($identity);
                $restoredState = $this->adapters->getState()
                    ->consultar($unit)
                    ->getEstadoOperacional();
                if (
                    $restoredPlan->possuiSincronizacaoConfirmada()
                    || $restoredState === null
                    || !$this->sameState($stateBefore, $restoredState)
                    || $this->historyCount() !== $historyBefore
                ) {
                    throw new RuntimeException('O rollback não restaurou atomicamente a massa Oracle.');
                }
            } finally {
                $this->adapters->getUnitOfWork()->desfazer();
            }
        } finally {
            if (!$rollbackDone && $this->connection->estaEmTransacao()) {
                $this->adapters->getUnitOfWork()->desfazer();
            }
        }

        if (
            $this->sessionFingerprint() !== $sessionBefore
            || $this->guard->wasCloseCalled()
        ) {
            throw new RuntimeException('A conexão emprestada não permaneceu utilizável.');
        }

        return [
            'binds' => true,
            'mesma_sessao' => true,
            'leituras_tipadas' => true,
            'escrita_visivel_na_transacao' => true,
            'confirmacao_estado_auditoria_atomicos' => true,
            'rollback_restaurou_massa' => true,
            'conexao_emprestada_utilizavel' => true,
            'close_invocado' => false,
        ];
    }

    /** @return array<string, mixed> */
    private function runLock(string $mode): array
    {
        $this->currentStage = 'lock_aquisicao';

        try {
            $key = ChaveUnidadeTrabalho::estrategiaAnual(
                new AnoExecucao($this->config['ano'])
            );
            $started = $this->adapters->getUnitOfWork()->iniciar($key);
            $expected = $mode === 'lock_expect_blocked'
                ? SituacaoInicioUnidadeTrabalho::BLOQUEADA
                : SituacaoInicioUnidadeTrabalho::INICIADA;

            if ($started->getSituacao() !== $expected) {
                throw new RuntimeException(
                    'O resultado de contenção Oracle divergiu do modo solicitado.'
                );
            }

            if ($mode === 'lock_holder') {
                $this->currentStage = 'lock_retencao';
                sleep($this->config['lock_hold_seconds']);
            }

            $this->currentStage = 'lock_finalizacao';
            if ($this->connection->estaEmTransacao()) {
                $this->adapters->getUnitOfWork()->desfazer();
            }

            return [
                'situacao_inicio' => $started->getSituacao()->value,
                'ora_00054_traduzida' => $mode === 'lock_expect_blocked',
                'lock_liberado_ao_final' => true,
                'close_invocado' => $this->guard->wasCloseCalled(),
            ];
        } catch (Throwable $exception) {
            $this->rollbackBestEffort();

            throw new OracleContractFailure($this->currentStage, $exception);
        }
    }

    private function findIdentity(AnoExecucao $year): IdentidadePlano
    {
        $found = array_values(array_filter(
            $this->adapters->getPlans()->listarIdentidades($year),
            fn (IdentidadePlano $identity): bool =>
                $identity->getUnidadeOrcamentaria()->getLotacao()->getId()
                    === $this->config['id_unidade_orcamentaria']
        ));
        if (count($found) !== 1) {
            throw new RuntimeException('A massa Oracle não resolveu uma identidade única.');
        }

        return $found[0];
    }

    private function historyCount(): int
    {
        $table = $this->historyMapping->getTabelaQualificada();
        $message = $this->historyMapping->getColunaMensagem();
        $rows = $this->connection->buscarTodos(
            "SELECT COUNT(*) AS TOTAL FROM {$table} WHERE {$message} = ?",
            [$this->config['mensagem_auditoria']]
        );
        $value = self::firstScalar($rows, 'TOTAL');
        if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
            throw new RuntimeException('A contagem de auditoria Oracle foi inválida.');
        }

        return (int) $value;
    }

    private function sessionFingerprint(): string
    {
        $rows = $this->connection->buscarTodos(
            "SELECT SYS_CONTEXT('USERENV', 'SID') AS SESSAO FROM DUAL"
        );
        $value = self::firstScalar($rows, 'SESSAO');
        if (!is_string($value) && !is_int($value)) {
            throw new RuntimeException('A sessão Oracle não foi identificada.');
        }

        return (string) $value;
    }

    /** @param array<int, array<int|string, mixed>> $rows */
    public static function firstScalar(array $rows, string $expectedAlias): mixed
    {
        if (count($rows) !== 1) {
            throw new RuntimeException('Consulta contratual Oracle não retornou linha única.');
        }

        foreach ($rows[0] as $alias => $value) {
            if (is_string($alias) && strcasecmp($alias, $expectedAlias) === 0) {
                return $value;
            }
        }

        throw new RuntimeException('Consulta contratual Oracle não retornou o alias esperado.');
    }

    private function sameState(
        EstadoOperacionalPublicacao $first,
        EstadoOperacionalPublicacao $second
    ): bool {
        return $first->getEstado() === $second->getEstado()
            && $first->getSequencialPca()?->getValor()
                === $second->getSequencialPca()?->getValor();
    }

    private function rollbackBestEffort(): void
    {
        if (!$this->connection->estaEmTransacao()) {
            return;
        }

        try {
            $this->adapters->getUnitOfWork()->desfazer();
        } catch (Throwable) {
        }
    }
}
