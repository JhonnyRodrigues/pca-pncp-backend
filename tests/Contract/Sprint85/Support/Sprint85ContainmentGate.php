<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint85\Support;

use Closure;
use Semae\Pca\Composition\CompositionRootPublicacaoPca;
use Semae\Pca\Composition\ExecutorPublicacaoPcaScriptcase;
use Semae\Pca\Infrastructure\Pncp\Http\ExecutorCurlNativoPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteCurlPncp;
use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;
use Semae\Pca\Presentation\ViewModels\ResultadoUnidadeViewModel;
use Semae\Pca\RegistradorEventosTecnicosScriptcase;
use Semae\Pca\ResultadoPublicacaoPcaScriptcase;
use Tests\Contract\Sprint84\Support\OracleCheckpointExecutor;
use Tests\Contract\Sprint84\Support\Sprint84AdodbGuard;
use Tests\Contract\Sprint84\Support\Sprint84GateException;
use Tests\Contract\Sprint84\Support\Sprint84PncpTransportGuard;

final class Sprint85ContainmentGate
{
    private const UUID_V4 = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/Di';
    private const UASG_CONSOLIDADORA = '930281';

    private readonly Closure $checkpoint;
    private readonly Closure $lockProbe;
    private readonly Closure $publisher;
    private readonly Closure $registrar;
    private readonly Closure $pncpDiagnostics;

    /** @param array<string, mixed> $configuration */
    private function __construct(
        private readonly array $configuration,
        callable $checkpoint,
        callable $lockProbe,
        callable $publisher,
        callable $registrar,
        callable $pncpDiagnostics
    ) {
        $this->checkpoint = Closure::fromCallable($checkpoint);
        $this->lockProbe = Closure::fromCallable($lockProbe);
        $this->publisher = Closure::fromCallable($publisher);
        $this->registrar = Closure::fromCallable($registrar);
        $this->pncpDiagnostics = Closure::fromCallable($pncpDiagnostics);
    }

    public static function fromEnvironment(object $db, string $login, string $serverName): self
    {
        $configuration = Sprint85Config::loadFromEnvironment();
        $profile = Sprint85Config::loadHomologationProfile($configuration, $serverName);
        $adodb = new Sprint84AdodbGuard($db, $profile->getCharsetAdodb());
        $queries = (new Sprint85OracleQueries($profile, $configuration, $login))->all();
        Sprint85Precheck::assertReadOnlyQueries($queries);
        $checkpointExecutor = new OracleCheckpointExecutor($adodb, $profile->getCharsetAdodb());
        $transport = new Sprint84PncpTransportGuard(
            new TransporteCurlPncp(
                $profile->getConfiguracaoTransporte(),
                new ExecutorCurlNativoPncp()
            ),
            false
        );
        $lock = new Sprint85OracleLockProbe($adodb, $profile);
        $executor = new ExecutorPublicacaoPcaScriptcase(
            new CompositionRootPublicacaoPca($transport)
        );

        return new self(
            $configuration,
            static fn (): array => $checkpointExecutor->execute($queries),
            static function (string $expectation) use ($lock, $configuration): void {
                if ($expectation === 'BLOQUEADA') {
                    $lock->exigirBloqueio((int) $configuration['ano'], self::UASG_CONSOLIDADORA);
                    return;
                }

                $lock->exigirDisponibilidade(
                    (int) $configuration['ano'],
                    self::UASG_CONSOLIDADORA
                );
            },
            static fn (string $requestId): ResultadoPublicacaoPcaScriptcase =>
                $executor->executarComEventos(
                    $adodb,
                    $login,
                    $serverName,
                    (int) $configuration['ano'],
                    $requestId
                ),
            static fn (array $events): ?string =>
                (new RegistradorEventosTecnicosScriptcase())->registrar(
                    $db,
                    $login,
                    $serverName,
                    'teste_contencao_sprint85',
                    $events
                ),
            $transport->diagnostico(...)
        );
    }

    /** @param array<string, mixed> $configuration */
    public static function fromComponents(
        array $configuration,
        callable $checkpoint,
        callable $lockProbe,
        callable $publisher,
        callable $registrar,
        callable $pncpDiagnostics
    ): self {
        return new self(
            $configuration,
            $checkpoint,
            $lockProbe,
            $publisher,
            $registrar,
            $pncpDiagnostics
        );
    }

    /** @return array<string, mixed> */
    public function precheck(): array
    {
        $this->exigirEtapa('PRECHECK');
        $checkpoint = ($this->checkpoint)();

        return [
            'sprint' => '8.5',
            'resultado' => 'PRECHECK_CONTENCAO_CONFIRMADO',
            'etapa' => 'PRECHECK',
            'oracle_checkpoint' => $checkpoint,
            'oracle_writes' => false,
            'pncp_access' => false,
            'pncp_writes' => false,
            'log_writes' => false,
        ];
    }

    /** @return array<string, mixed> */
    public function executar(string $requestId): array
    {
        $this->exigirEtapa('CONTENCAO');
        $this->exigirRequestId($requestId);
        $antes = ($this->checkpoint)();
        $this->exigirHash($antes);
        ($this->lockProbe)('BLOQUEADA');

        $execucao = ($this->publisher)($requestId);
        if (!$execucao instanceof ResultadoPublicacaoPcaScriptcase) {
            throw $this->erro('RETORNO_PUBLICACAO_INVALIDO', 'A publicacao nao retornou o contrato esperado.');
        }
        if ($execucao->getAvisoRegistroTecnico() !== null) {
            throw $this->erro('EXPORTACAO_EVENTOS_FALHOU', 'Os eventos tecnicos nao puderam ser exportados.');
        }
        if ($execucao->getEventos() === []) {
            throw $this->erro('EVENTOS_AUSENTES', 'A execucao nao exportou eventos tecnicos.');
        }

        $viewModel = $execucao->getResultado();
        $this->validarResultadoBloqueado($viewModel, $requestId);
        $diagnosticoPncp = $this->validarPncp();
        $depois = ($this->checkpoint)();
        $this->validarSnapshotPreservado($antes, $depois);

        $aviso = ($this->registrar)($execucao->getEventos());
        if ($aviso !== null) {
            throw $this->erro('REGISTRO_EVENTOS_FALHOU', 'Os eventos tecnicos nao foram registrados integralmente.');
        }

        return [
            'sprint' => '8.5',
            'resultado' => 'CONTENCAO_CONFIRMADA',
            'situacao_global' => $viewModel->getSituacao(),
            'resultado_unitario' => $this->resultadoParaArray($viewModel->getResultadosUnitarios()[0]),
            'quantidade_planejadas' => $viewModel->getQuantidadePlanejadas(),
            'quantidade_visitadas' => $viewModel->getQuantidadeVisitadas(),
            'quantidade_nao_processadas' => $viewModel->getQuantidadeNaoProcessadas(),
            'correlacao' => $viewModel->getCorrelacao(),
            'eventos_registrados' => count($execucao->getEventos()),
            'oracle_checkpoint_sha256_antes' => $antes['sha256'] ?? null,
            'oracle_checkpoint_sha256_depois' => $depois['sha256'] ?? null,
            'oracle_snapshot_preservado' => true,
            'auditoria_negocio_preservada' => true,
            'pncp_diagnostico' => $diagnosticoPncp,
            'pncp_writes' => false,
            'log_writes' => true,
        ];
    }

    /** @return array<string, mixed> */
    public function verificarDisponibilidade(): array
    {
        $this->exigirEtapa('CONTENCAO');
        ($this->lockProbe)('DISPONIVEL');

        return [
            'sprint' => '8.5',
            'resultado' => 'ANCORA_DISPONIVEL',
            'oracle_writes' => false,
            'pncp_access' => false,
            'pncp_writes' => false,
            'log_writes' => false,
        ];
    }

    private function exigirEtapa(string $esperada): void
    {
        if (($this->configuration['etapa_autorizada'] ?? null) !== $esperada) {
            throw $this->erro('ETAPA_NAO_AUTORIZADA', 'A configuracao nao autorizou esta etapa.');
        }
    }

    private function exigirRequestId(string $requestId): void
    {
        if (preg_match(self::UUID_V4, $requestId) !== 1) {
            throw $this->erro('CORRELACAO_INVALIDA', 'O gate exige um X-Request-ID UUID v4 valido.');
        }
    }

    /** @param array<string, mixed> $checkpoint */
    private function exigirHash(array $checkpoint): void
    {
        $esperado = $this->configuration['oracle_checkpoint_sha256'] ?? null;
        $encontrado = $checkpoint['sha256'] ?? null;
        if (!is_string($esperado) || !is_string($encontrado) || !hash_equals($esperado, $encontrado)) {
            throw $this->erro('PRECHECK_HASH_DIVERGENTE', 'O estado Oracle divergiu do checkpoint fixado.');
        }
    }

    private function validarResultadoBloqueado(PublicarPcaViewModel $resultado, string $requestId): void
    {
        $unitarios = $resultado->getResultadosUnitarios();
        $unitario = $unitarios[0] ?? null;
        if (
            $resultado->getSituacao() !== 'CONCLUIDA_COM_PENDENCIAS'
            || count($unitarios) !== 1
            || !$unitario instanceof ResultadoUnidadeViewModel
            || $unitario->getSituacao() !== 'BLOQUEADO'
            || $unitario->getModo() !== 'CONSOLIDADO'
            || $unitario->getAnoExecucao() !== (int) $this->configuration['ano']
            || $unitario->getCodigoUasg() !== self::UASG_CONSOLIDADORA
            || $unitario->getDecisao() !== null
            || $unitario->getSequencialPca() !== null
            || $unitario->getQuantidadeInclusoes() !== 0
            || $unitario->getQuantidadeExclusoes() !== 0
            || $resultado->getQuantidadePlanejadas() !== 1
            || $resultado->getQuantidadeVisitadas() !== 1
            || $resultado->getQuantidadeNaoProcessadas() !== 0
            || $resultado->getCorrelacao() !== $requestId
        ) {
            throw $this->erro('RESULTADO_CONTENCAO_DIVERGENTE', 'O fluxo nao produziu o resultado bloqueado esperado.');
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function validarPncp(): array
    {
        $calls = ($this->pncpDiagnostics)();
        if (!is_array($calls) || count($calls) !== 1) {
            throw $this->erro('PNCP_ACESSO_DIVERGENTE', 'A contencao deve acessar somente a autenticacao PNCP.');
        }

        $call = $calls[0] ?? null;
        $request = is_array($call) ? ($call['requisicao'] ?? null) : null;
        $method = is_array($request) ? strtoupper((string) ($request['metodo'] ?? '')) : '';
        $path = is_array($request) ? (string) ($request['caminho'] ?? '') : '';
        if ($method !== 'POST' || $path !== '/v1/usuarios/login') {
            throw $this->erro('PNCP_ESCRITA_DETECTADA', 'A contencao tentou uma operacao PNCP nao autorizada.');
        }

        $response = is_array($call) ? ($call['resposta'] ?? null) : null;

        return [[
            'metodo' => $method,
            'caminho' => $path,
            'status_http' => is_array($response) ? ($response['status_http'] ?? null) : null,
            'falhou' => is_array($call) && array_key_exists('erro', $call),
        ]];
    }

    /** @param array<string, mixed> $antes @param array<string, mixed> $depois */
    private function validarSnapshotPreservado(array $antes, array $depois): void
    {
        if (($antes['snapshot'] ?? null) !== ($depois['snapshot'] ?? null)) {
            throw $this->erro('ORACLE_ALTERADO_NA_CONTENCAO', 'O snapshot Oracle mudou durante a contencao.');
        }
    }

    /** @return array<string, mixed> */
    private function resultadoParaArray(ResultadoUnidadeViewModel $resultado): array
    {
        return [
            'modo' => $resultado->getModo(),
            'ano_execucao' => $resultado->getAnoExecucao(),
            'codigo_uasg' => $resultado->getCodigoUasg(),
            'situacao' => $resultado->getSituacao(),
            'decisao' => $resultado->getDecisao(),
            'sequencial_pca' => $resultado->getSequencialPca(),
            'quantidade_inclusoes' => $resultado->getQuantidadeInclusoes(),
            'quantidade_exclusoes' => $resultado->getQuantidadeExclusoes(),
            'codigo_falha' => $resultado->getCodigoFalha(),
            'mensagem_falha' => $resultado->getMensagemFalha(),
        ];
    }

    private function erro(string $codigo, string $mensagem): Sprint84GateException
    {
        return new Sprint84GateException($codigo, 'CONTENCAO', $mensagem);
    }
}
