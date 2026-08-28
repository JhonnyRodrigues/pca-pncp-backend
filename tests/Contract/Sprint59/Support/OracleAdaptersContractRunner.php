<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint59\Support;

use DateTimeZone;
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
use Semae\Pca\Infrastructure\Oracle\Auditoria\OracleRegistroAuditoriaAdapter;
use Semae\Pca\Infrastructure\Oracle\Contexto\MapeamentoLotacoesUsuarioOracle;
use Semae\Pca\Infrastructure\Oracle\Contexto\MapeamentoRepresentantesOracle;
use Semae\Pca\Infrastructure\Oracle\Contexto\OracleContextoUsuarioAdapter;
use Semae\Pca\Infrastructure\Oracle\EstadoOperacional\MapeamentoEstadoOperacionalOracle;
use Semae\Pca\Infrastructure\Oracle\EstadoOperacional\OracleEstadoOperacionalPublicacaoAdapter;
use Semae\Pca\Infrastructure\Oracle\Estrategia\MapeamentoConfiguracaoEstrategiaOracle;
use Semae\Pca\Infrastructure\Oracle\Estrategia\MapeamentoPlanosEstrategiaOracle;
use Semae\Pca\Infrastructure\Oracle\Estrategia\OracleEstrategiaPublicacaoAdapter;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoDemandasOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoLotacoesOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoPlanosOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoUnidadesOrcamentariasOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\OraclePlanoRepository;
use Semae\Pca\Infrastructure\Oracle\Transacao\MapeamentoControlesConcorrenciaOracle;
use Semae\Pca\Infrastructure\Oracle\Transacao\OracleUnidadeDeTrabalhoAdapter;
use Throwable;

final class OracleAdaptersContractRunner
{
    private readonly BorrowedAdodbGuard $guard;
    private readonly ConexaoAdodbEmprestada $connection;
    private readonly MapeamentoControlesConcorrenciaOracle $concurrencyControlMapping;
    private readonly OracleUnidadeDeTrabalhoAdapter $unitOfWork;
    private readonly OracleEstrategiaPublicacaoAdapter $strategy;
    private readonly OraclePlanoRepository $plans;
    private readonly OracleEstadoOperacionalPublicacaoAdapter $state;
    private readonly OracleContextoUsuarioAdapter $context;
    private readonly OracleRegistroAuditoriaAdapter $audit;
    private readonly MapeamentoHistoricosOracle $historyMapping;
    private string $currentStage = 'bind';

    /** @param array<string, mixed> $config */
    public function __construct(object $db, private readonly string $login, private readonly array $config)
    {
        $mapping = $config['mappings'];
        $this->guard = new BorrowedAdodbGuard($db);
        $this->connection = new ConexaoAdodbEmprestada($this->guard);
        $this->concurrencyControlMapping = new MapeamentoControlesConcorrenciaOracle(
            ...$mapping['controles_concorrencia']
        );
        $this->unitOfWork = new OracleUnidadeDeTrabalhoAdapter(
            $this->connection,
            $this->concurrencyControlMapping
        );
        $unitsMapping = new MapeamentoUnidadesOrcamentariasOracle(
            ...$mapping['unidades_orcamentarias']
        );
        $this->strategy = new OracleEstrategiaPublicacaoAdapter(
            $this->connection,
            new MapeamentoConfiguracaoEstrategiaOracle(...$mapping['configuracao_estrategia']),
            new MapeamentoPlanosEstrategiaOracle(...$mapping['planos_estrategia']),
            $unitsMapping
        );
        $this->plans = new OraclePlanoRepository(
            $this->connection,
            new MapeamentoPlanosOracle(...$mapping['planos']),
            new MapeamentoDemandasOracle(...$mapping['demandas']),
            $unitsMapping,
            new MapeamentoLotacoesOracle(...$mapping['lotacoes']),
            new DateTimeZone($config['timezone'])
        );
        $this->state = new OracleEstadoOperacionalPublicacaoAdapter(
            $this->connection,
            new MapeamentoEstadoOperacionalOracle(...$mapping['estado_operacional'])
        );
        $this->context = new OracleContextoUsuarioAdapter(
            $this->connection,
            new MapeamentoRepresentantesOracle(...$mapping['representantes']),
            new MapeamentoLotacoesUsuarioOracle(...$mapping['lotacoes_usuario']),
            $login
        );
        $this->historyMapping = new MapeamentoHistoricosOracle(...$mapping['historicos']);
        $this->audit = new OracleRegistroAuditoriaAdapter($this->connection, $this->historyMapping);
    }

    /** @param array<string, mixed> $config */
    public static function fromConfig(object $db, string $login, array $config): self
    {
        return new self($db, $login, Sprint59Config::oracle($config));
    }

    /** @return array<string, mixed> */
    public function run(string $mode): array
    {
        if (!in_array($mode, ['adapters', 'lock_holder', 'lock_expect_blocked', 'lock_expect_available'], true)) {
            throw new RuntimeException('Modo Oracle invalido.');
        }

        return $mode === 'adapters' ? $this->runAdapters() : $this->runLock($mode);
    }

    /** @return array<string, mixed> */
    private function runAdapters(): array
    {
        $this->currentStage = 'bind';

        try {
            return $this->executeAdapters();
        } catch (Throwable $exception) {
            throw new OracleAdaptersContractFailure($this->currentStage, $exception);
        }
    }

    /** @return array<string, mixed> */
    private function executeAdapters(): array
    {
        $year = new AnoExecucao($this->config['ano']);
        $sessionBefore = $this->sessionFingerprint();
        $bindRows = $this->connection->buscarTodos('SELECT ? AS VALOR_BIND FROM DUAL', ['SPRINT_59_BIND']);
        if ($this->firstScalar($bindRows, 'VALOR_BIND') !== 'SPRINT_59_BIND') {
            throw new RuntimeException('O bind Oracle nao preservou o valor esperado.');
        }

        $this->currentStage = 'estrategia_corrente';
        $currentStrategy = $this->strategy->consultarCorrente($year);
        if ($currentStrategy->getSituacao() !== SituacaoConsultaEstrategia::DISPONIVEL) {
            throw new RuntimeException('A estrategia corrente Oracle nao ficou disponivel.');
        }
        $this->currentStage = 'estrategia_adotada';
        $adoptedStrategy = $this->strategy->consultarAdotada($year);
        if ($adoptedStrategy->getSituacao() !== SituacaoConsultaEstrategia::DISPONIVEL) {
            throw new RuntimeException('A estrategia adotada Oracle nao ficou disponivel.');
        }
        $this->currentStage = 'contexto_usuario';
        $resolvedContext = $this->context->resolver();
        if ($resolvedContext->getSituacao() !== SituacaoResolucaoContextoUsuario::RESOLVIDO) {
            throw new RuntimeException('O contexto Oracle do usuario nao foi resolvido.');
        }
        $context = $resolvedContext->getContexto();
        if ($context === null) {
            throw new RuntimeException('O resultado tipado de contexto veio sem dados.');
        }

        $this->currentStage = 'identidade_plano';
        $identity = $this->findIdentity($year);
        $unit = UnidadeExternaPublicacao::individual($identity);
        $this->currentStage = 'inicio_transacao';
        $started = $this->unitOfWork->iniciar(ChaveUnidadeTrabalho::unidadeExterna($unit));
        if ($started->getSituacao() !== SituacaoInicioUnidadeTrabalho::INICIADA) {
            throw new RuntimeException('A unidade de trabalho Oracle nao foi iniciada.');
        }

        $rollbackDone = false;
        try {
            $sessionInside = $this->sessionFingerprint();
            if ($sessionInside !== $sessionBefore) {
                throw new RuntimeException('Os adapters Oracle nao permaneceram na mesma sessao.');
            }

            $this->currentStage = 'reconstrucao_plano';
            $plan = $this->plans->reconstruirFotografiaVigente($identity);
            if ($plan->possuiSincronizacaoConfirmada()) {
                throw new RuntimeException('A massa Oracle deve possuir confirmacao pendente.');
            }
            $this->currentStage = 'consulta_estado';
            $stateBeforeResult = $this->state->consultar($unit);
            if ($stateBeforeResult->getSituacao() !== SituacaoConsultaEstadoOperacional::DISPONIVEL) {
                throw new RuntimeException('O estado operacional Oracle nao ficou disponivel.');
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
                throw new RuntimeException('A massa Oracle ja coincide com o estado temporario.');
            }

            $this->currentStage = 'confirmacao_plano';
            $this->plans->confirmarSincronizacao($plan);
            if (!$this->plans->reconstruirFotografiaVigente($identity)->possuiSincronizacaoConfirmada()) {
                throw new RuntimeException('A confirmacao do Plano nao ficou visivel na transacao.');
            }
            $this->currentStage = 'registro_estado';
            $stateWrite = $this->state->registrar($unit, $temporaryState);
            if ($stateWrite->getSituacao() !== SituacaoPersistenciaLocal::CONCLUIDA) {
                throw new RuntimeException('A escrita do estado operacional Oracle falhou.');
            }
            $this->currentStage = 'registro_auditoria';
            $auditWrite = $this->audit->registrar(new IntencaoRegistroHistorico(
                ProcedimentoPublicacaoPca::PUBLICACAO_INICIAL,
                $this->config['mensagem_auditoria'],
                $context->getRepresentante(),
                $context->getLotacao()
            ));
            if ($auditWrite->getSituacao() !== SituacaoPersistenciaLocal::CONCLUIDA) {
                throw new RuntimeException('A escrita de auditoria Oracle falhou.');
            }
            if ($this->historyCount() !== $historyBefore + 1) {
                throw new RuntimeException('A auditoria nao ficou visivel dentro da transacao.');
            }

            $this->currentStage = 'rollback';
            $rollback = $this->unitOfWork->desfazer();
            if ($rollback->getSituacao() !== SituacaoFinalizacaoUnidadeTrabalho::CONCLUIDA) {
                throw new RuntimeException('O rollback Oracle nao foi concluido.');
            }
            $rollbackDone = true;

            $this->currentStage = 'verificacao_rollback';
            $verifyStarted = $this->unitOfWork->iniciar(ChaveUnidadeTrabalho::unidadeExterna($unit));
            if ($verifyStarted->getSituacao() !== SituacaoInicioUnidadeTrabalho::INICIADA) {
                throw new RuntimeException('A verificacao posterior ao rollback nao iniciou.');
            }
            try {
                $restoredPlan = $this->plans->reconstruirFotografiaVigente($identity);
                $restoredState = $this->state->consultar($unit)->getEstadoOperacional();
                if ($restoredPlan->possuiSincronizacaoConfirmada()
                    || $restoredState === null
                    || !$this->sameState($stateBefore, $restoredState)
                    || $this->historyCount() !== $historyBefore
                ) {
                    throw new RuntimeException('O rollback nao restaurou atomicamente a massa Oracle.');
                }
            } finally {
                $this->unitOfWork->desfazer();
            }
        } finally {
            if (!$rollbackDone && $this->connection->estaEmTransacao()) {
                $this->unitOfWork->desfazer();
            }
        }

        $sessionAfter = $this->sessionFingerprint();
        if ($sessionAfter !== $sessionBefore || $this->guard->wasCloseCalled()) {
            throw new RuntimeException('A conexao emprestada nao permaneceu utilizavel.');
        }

        return $this->report('adapters', [
            'binds' => true,
            'mesma_sessao' => true,
            'leituras_tipadas' => true,
            'escrita_visivel_na_transacao' => true,
            'confirmacao_estado_auditoria_atomicos' => true,
            'rollback_restaurou_massa' => true,
            'conexao_emprestada_utilizavel' => true,
            'close_invocado' => false,
        ]);
    }

    /** @return array<string, mixed> */
    private function runLock(string $mode): array
    {
        $key = ChaveUnidadeTrabalho::estrategiaAnual(new AnoExecucao($this->config['ano']));
        $started = $this->unitOfWork->iniciar($key);
        $expected = $mode === 'lock_expect_blocked'
            ? SituacaoInicioUnidadeTrabalho::BLOQUEADA
            : SituacaoInicioUnidadeTrabalho::INICIADA;

        if ($started->getSituacao() !== $expected) {
            if ($this->connection->estaEmTransacao()) {
                $this->unitOfWork->desfazer();
            }
            throw new RuntimeException('O resultado de contencao Oracle divergiu do modo solicitado.');
        }

        if ($mode === 'lock_holder') {
            sleep($this->config['lock_hold_seconds']);
        }
        if ($this->connection->estaEmTransacao()) {
            $this->unitOfWork->desfazer();
        }

        return $this->report($mode, [
            'situacao_inicio' => $started->getSituacao()->value,
            'ora_00054_traduzida' => $mode === 'lock_expect_blocked',
            'lock_liberado_ao_final' => true,
            'close_invocado' => $this->guard->wasCloseCalled(),
        ]);
    }

    private function findIdentity(AnoExecucao $year): IdentidadePlano
    {
        $found = array_values(array_filter(
            $this->plans->listarIdentidades($year),
            fn (IdentidadePlano $identity): bool =>
                $identity->getUnidadeOrcamentaria()->getLotacao()->getId()
                    === $this->config['id_unidade_orcamentaria']
        ));
        if (count($found) !== 1) {
            throw new RuntimeException('A massa Oracle nao resolveu uma identidade unica.');
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
        $value = $this->firstScalar($rows, 'TOTAL');
        if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
            throw new RuntimeException('A contagem de auditoria Oracle foi invalida.');
        }
        return (int) $value;
    }

    private function sessionFingerprint(): string
    {
        $rows = $this->connection->buscarTodos(
            "SELECT SYS_CONTEXT('USERENV', 'SID') AS SESSAO FROM DUAL"
        );
        $value = $this->firstScalar($rows, 'SESSAO');
        if (!is_string($value) && !is_int($value)) {
            throw new RuntimeException('A sessao Oracle nao foi identificada.');
        }
        return (string) $value;
    }

    /** @param array<int, array<int|string, mixed>> $rows */
    private function firstScalar(array $rows, string $expectedAlias): mixed
    {
        if (count($rows) !== 1) {
            throw new RuntimeException('Consulta contratual Oracle nao retornou linha unica.');
        }

        foreach ($rows[0] as $alias => $value) {
            if (is_string($alias) && strcasecmp($alias, $expectedAlias) === 0) {
                return $value;
            }
        }

        throw new RuntimeException('Consulta contratual Oracle nao retornou o alias esperado.');
    }

    private function sameState(
        EstadoOperacionalPublicacao $first,
        EstadoOperacionalPublicacao $second
    ): bool {
        return $first->getEstado() === $second->getEstado()
            && $first->getSequencialPca()?->getValor() === $second->getSequencialPca()?->getValor();
    }

    /** @param array<string, mixed> $checks @return array<string, mixed> */
    private function report(string $mode, array $checks): array
    {
        return [
            'sprint' => '5.9',
            'gate' => 'ORACLE_SCRIPTCASE',
            'modo' => $mode,
            'confirmado' => true,
            'verificacoes' => $checks,
            'adapters' => [
                OracleUnidadeDeTrabalhoAdapter::class,
                OracleEstrategiaPublicacaoAdapter::class,
                OraclePlanoRepository::class,
                OracleEstadoOperacionalPublicacaoAdapter::class,
                OracleContextoUsuarioAdapter::class,
                OracleRegistroAuditoriaAdapter::class,
            ],
            'detalhes_sensiveis_exibidos' => false,
        ];
    }
}
