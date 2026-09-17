<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84\Support;

use Closure;
use Semae\Pca\Composition\CompositionRootPublicacaoPca;
use Semae\Pca\Composition\ExecutorPublicacaoPcaScriptcase;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Semae\Pca\Infrastructure\Pncp\Http\ExecutorCurlNativoPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteCurlPncp;
use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;
use Semae\Pca\Presentation\ViewModels\ResultadoUnidadeViewModel;

final class Sprint84Runner
{
    private const UUID_V4 = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/Di';
    private const STAGES = ['PRECHECK', 'PUBLICAR', 'NENHUMA_ACAO', 'RETIFICAR', 'EXCLUIR_PCA'];

    /** @var array<string, mixed>|null */
    private ?array $lastOracleCheckpoint = null;

    /**
     * @param array<string, mixed> $configuration
     * @param array<string, mixed> $profile
     * @param array<string, array<string, mixed>> $queries
     */
    private function __construct(
        private readonly array $configuration,
        private readonly array $profile,
        private readonly string $authenticatedLogin,
        private readonly string $serverName,
        private readonly string $requestId,
        private readonly string $stage,
        private readonly Sprint84AdodbGuard $adodb,
        private readonly OracleCheckpointExecutor $checkpointExecutor,
        private readonly array $queries,
        private readonly Closure $publisher,
        private readonly Closure $pncpPostVerifier,
        private readonly ?Closure $emergencyCleanup,
        private readonly Closure $pncpPreVerifier,
        private readonly Closure $pncpDiagnostics
    ) {}

    public static function fromEnvironment(
        object $db,
        string $login,
        string $serverName,
        string $requestId,
        string $stage
    ): self {
        $configuration = Sprint84Config::loadFromEnvironment();
        $profile = Sprint84Config::loadHomologationProfileArray($configuration, $serverName);
        $typedProfile = new PerfilPublicacaoPca($profile);
        $guard = new Sprint84AdodbGuard($db, $typedProfile->getCharsetAdodb());
        $pncpTransportGuard = new Sprint84PncpTransportGuard(
            new TransporteCurlPncp(
                $typedProfile->getConfiguracaoTransporte(),
                new ExecutorCurlNativoPncp()
            ),
            $configuration['allow_pncp_writes'] === true
                && !in_array($stage, ['PRECHECK', 'NENHUMA_ACAO'], true)
        );
        $pncpVerifier = Sprint84PncpVerifier::native(
            $typedProfile,
            $profile,
            $configuration,
            $pncpTransportGuard
        );

        return self::fromComponents(
            $configuration,
            $profile,
            $login,
            $serverName,
            $requestId,
            $stage,
            $guard,
            new OracleCheckpointExecutor($guard, $typedProfile->getCharsetAdodb()),
            (new OracleCheckpointQueries($typedProfile, $configuration, $login))->all(),
            static fn (
                object $connection,
                string $authenticatedLogin,
                string $host,
                int $year,
                string $correlation
            ): PublicarPcaViewModel => (new ExecutorPublicacaoPcaScriptcase(
                new CompositionRootPublicacaoPca($pncpTransportGuard)
            ))->executar(
                $connection,
                $authenticatedLogin,
                $host,
                $year,
                $correlation
            ),
            $pncpVerifier->verify(...),
            $pncpVerifier->cleanup(...),
            $pncpVerifier->precheck(...),
            $pncpVerifier->diagnosticoHttp(...)
        );
    }

    /**
     * @param array<string, mixed> $configuration
     * @param array<string, mixed> $profile
     * @param array<string, array<string, mixed>> $queries
     */
    public static function fromComponents(
        array $configuration,
        array $profile,
        string $login,
        string $serverName,
        string $requestId,
        string $stage,
        Sprint84AdodbGuard $adodb,
        OracleCheckpointExecutor $checkpointExecutor,
        array $queries,
        callable $publisher,
        callable $pncpPostVerifier,
        ?callable $emergencyCleanup = null,
        ?callable $pncpPreVerifier = null,
        ?callable $pncpDiagnostics = null
    ): self {
        return new self(
            $configuration,
            $profile,
            $login,
            $serverName,
            $requestId,
            $stage,
            $adodb,
            $checkpointExecutor,
            $queries,
            Closure::fromCallable($publisher),
            Closure::fromCallable($pncpPostVerifier),
            $emergencyCleanup === null ? null : Closure::fromCallable($emergencyCleanup),
            Closure::fromCallable($pncpPreVerifier ?? static fn (): array => []),
            Closure::fromCallable($pncpDiagnostics ?? static fn (): array => [])
        );
    }

    /** @return array<string, mixed> */
    public function run(): array
    {
        $this->validateInvocation();
        $precheck = $this->checkpointExecutor->execute($this->queries);
        $this->lastOracleCheckpoint = $precheck;
        $this->validateCheckpointHash($precheck['sha256']);
        $pncpPrecheck = ($this->pncpPreVerifier)($precheck);
        if (!is_array($pncpPrecheck)) {
            throw new Sprint84GateException(
                'PRECHECK_PNCP_RETORNO_INVALIDO',
                'PRECHECK_PNCP',
                'A verificação PNCP inicial não retornou uma lista.',
                ['tipo_recebido' => get_debug_type($pncpPrecheck)]
            );
        }
        $this->validatePncpPreState($pncpPrecheck);

        $base = $this->baseReport($precheck);
        $base['precheck_pncp'] = $pncpPrecheck;
        if ($this->stage === 'PRECHECK') {
            $base['resultado'] = 'PRECHECK_CONFIRMADO';
            $base['diagnostico_pncp_http'] = ($this->pncpDiagnostics)();
            $base['diagnostico_adodb'] = $this->adodb->diagnostico();

            return Sprint84DiagnosticReport::finish($base);
        }

        $viewModel = ($this->publisher)(
            $this->adodb,
            $this->authenticatedLogin,
            $this->serverName,
            (int) $this->configuration['ano'],
            $this->requestId
        );
        if (!$viewModel instanceof PublicarPcaViewModel) {
            throw new Sprint84GateException(
                'FLUXO_RETORNO_INVALIDO',
                'FLUXO_PUBLICO',
                'A fachada pública não retornou PublicarPcaViewModel.',
                ['tipo_recebido' => get_debug_type($viewModel)]
            );
        }

        $this->validateViewModel($viewModel, $precheck);
        $pncp = ($this->pncpPostVerifier)($viewModel);
        if (!is_array($pncp)) {
            throw new Sprint84GateException(
                'POSCHECK_PNCP_RETORNO_INVALIDO',
                'POSCHECK_PNCP',
                'A verificação PNCP posterior não retornou uma lista.',
                ['tipo_recebido' => get_debug_type($pncp)]
            );
        }
        $this->validatePncpPostState($pncp, $viewModel);
        $postcheck = $this->checkpointExecutor->execute($this->queries, false);
        $targets = $this->projectionTargets($viewModel, $precheck);
        $this->validateOperationalProjection($postcheck, $targets);
        $pncpDiagnostics = ($this->pncpDiagnostics)();
        $this->validateNoActionInvariants(
            $precheck,
            $postcheck,
            $pncpPrecheck,
            $pncp,
            $pncpDiagnostics,
            $targets
        );

        $base['resultado'] = 'FLUXO_CONFIRMADO';
        $base['view_model'] = $this->viewModelToArray($viewModel);
        $base['observacoes_pncp_pos_fluxo'] = $pncp;
        $base['poscheck_oracle'] = $postcheck;
        $base['projecao_operacional_completada'] = $this->completedProjectionPairs($precheck, $postcheck);
        $base['diagnostico_pncp_http'] = $pncpDiagnostics;
        $base['diagnostico_adodb'] = $this->adodb->diagnostico();

        return Sprint84DiagnosticReport::finish($base);
    }

    /** @return array<string, mixed> */
    public function cleanupEmergency(): array
    {
        $this->validateInvocation(false);
        $sequences = $this->configuration['sequenciais_limpeza_autorizados'] ?? [];
        if (!is_array($sequences) || $sequences === []) {
            throw new Sprint84GateException(
                'LIMPEZA_SEQUENCIAL_NAO_AUTORIZADO',
                'LIMPEZA_EMERGENCIAL',
                'A limpeza emergencial exige ao menos um sequencial explicitamente autorizado.'
            );
        }
        if ($this->emergencyCleanup === null) {
            throw new Sprint84GateException(
                'LIMPEZA_EXECUTOR_AUSENTE',
                'LIMPEZA_EMERGENCIAL',
                'O executor PNCP da limpeza emergencial não está disponível.'
            );
        }

        $checkpoint = $this->checkpointExecutor->execute($this->queries, false);
        $result = ($this->emergencyCleanup)($sequences, $checkpoint);
        if (!is_array($result)) {
            throw new Sprint84GateException(
                'LIMPEZA_RETORNO_INVALIDO',
                'LIMPEZA_EMERGENCIAL',
                'A limpeza emergencial não retornou diagnóstico válido.'
            );
        }

        return Sprint84DiagnosticReport::finish([
            'sprint' => '8.4',
            'resultado' => 'LIMPEZA_CONFIRMADA',
            'sequenciais_autorizados' => $sequences,
            'detalhes' => $result,
            'checkpoint_oracle' => $checkpoint,
            'diagnostico_pncp_http' => ($this->pncpDiagnostics)(),
            'diagnostico_adodb' => $this->adodb->diagnostico(),
        ]);
    }

    /** @return array<string, mixed> */
    public function failureContext(): array
    {
        return [
            'etapa' => $this->stage,
            'modo' => $this->configuration['modo'] ?? null,
            'request_id' => $this->requestId,
            'server_name' => $this->serverName,
            'login_sgi' => $this->authenticatedLogin,
            'configuracao' => $this->configuration,
            'perfil' => $this->profile,
            'caminhos' => $this->paths(),
            'precheck_oracle' => $this->lastOracleCheckpoint,
            'diagnostico_pncp_http' => ($this->pncpDiagnostics)(),
            'diagnostico_adodb' => $this->adodb->diagnostico(),
        ];
    }

    private function validateInvocation(bool $requireStageMatch = true): void
    {
        if (!in_array($this->stage, self::STAGES, true)) {
            throw new Sprint84GateException(
                'ETAPA_INVALIDA',
                'CONFIGURACAO',
                'A etapa solicitada não pertence à Sprint 8.4.',
                ['etapa_recebida' => $this->stage, 'etapas_aceitas' => self::STAGES]
            );
        }
        if ($requireStageMatch && ($this->configuration['etapa_autorizada'] ?? null) !== $this->stage) {
            throw new Sprint84GateException(
                'ETAPA_NAO_AUTORIZADA',
                'CONFIGURACAO',
                'A etapa solicitada diverge da etapa autorizada na configuração.',
                [
                    'etapa_solicitada' => $this->stage,
                    'etapa_autorizada' => $this->configuration['etapa_autorizada'] ?? null,
                ]
            );
        }
        if ($this->serverName !== ($this->configuration['server_name'] ?? null)) {
            throw new Sprint84GateException(
                'SERVER_NAME_DIVERGENTE',
                'CONFIGURACAO',
                'O servidor recebido diverge do servidor autorizado.',
                ['recebido' => $this->serverName, 'autorizado' => $this->configuration['server_name'] ?? null]
            );
        }
        if (preg_match(self::UUID_V4, $this->requestId) !== 1) {
            throw new Sprint84GateException(
                'REQUEST_ID_INVALIDO',
                'CONFIGURACAO',
                'O request ID deve ser UUID v4.',
                ['request_id' => $this->requestId]
            );
        }
    }

    private function validateCheckpointHash(string $found): void
    {
        $expected = $this->configuration['oracle_checkpoint_sha256'] ?? null;
        if ($this->stage === 'PRECHECK' && $expected === null) {
            return;
        }
        if (!is_string($expected) || !hash_equals($expected, $found)) {
            throw new Sprint84GateException(
                'PRECHECK_HASH_DIVERGENTE',
                'PRECHECK_ORACLE',
                'O estado Oracle mudou ou não corresponde ao checkpoint aprovado.',
                ['sha256_esperado' => $expected, 'sha256_encontrado' => $found]
            );
        }
    }

    /** @param array<int, mixed> $observations */
    private function validatePncpPreState(array $observations): void
    {
        $expectedCount = ($this->configuration['modo'] ?? null) === 'INDIVIDUAL' ? 2 : 1;
        $expectedState = $this->configuration['estado_pncp_esperado'] ?? null;
        if (count($observations) !== $expectedCount) {
            throw new Sprint84GateException(
                'PRECHECK_PNCP_CARDINALIDADE_DIVERGENTE',
                'PRECHECK_PNCP',
                'A quantidade de unidades observadas no PNCP divergiu do modo autorizado.',
                ['esperado' => $expectedCount, 'encontrado' => count($observations)]
            );
        }

        foreach ($observations as $index => $observation) {
            if (!is_array($observation) || ($observation['situacao'] ?? null) !== $expectedState) {
                throw new Sprint84GateException(
                    'PRECHECK_PNCP_ESTADO_DIVERGENTE',
                    'PRECHECK_PNCP',
                    'O estado PNCP inicial divergiu do checkpoint autorizado.',
                    ['indice' => $index, 'esperado' => $expectedState, 'encontrado' => $observation]
                );
            }
        }
    }

    /** @param array<string, mixed> $precheck */
    private function validateViewModel(PublicarPcaViewModel $viewModel, array $precheck): void
    {
        if ($viewModel->getSituacao() !== 'CONCLUIDA') {
            throw new Sprint84GateException(
                'FLUXO_NAO_CONCLUIDO',
                'FLUXO_PUBLICO',
                'A fachada pública não concluiu a etapa autorizada.',
                ['view_model' => $this->viewModelToArray($viewModel)]
            );
        }

        $expectedUnits = ($this->configuration['modo'] ?? null) === 'INDIVIDUAL' ? 2 : 1;
        $results = $viewModel->getResultadosUnitarios();
        if (count($results) !== $expectedUnits) {
            throw new Sprint84GateException(
                'FLUXO_CARDINALIDADE_DIVERGENTE',
                'FLUXO_PUBLICO',
                'A quantidade de unidades processadas divergiu do modo autorizado.',
                ['esperado' => $expectedUnits, 'encontrado' => count($results)]
            );
        }

        foreach ($results as $index => $result) {
            if (!$result instanceof ResultadoUnidadeViewModel) {
                throw new Sprint84GateException(
                    'FLUXO_RESULTADO_INVALIDO',
                    'FLUXO_PUBLICO',
                    'A fachada retornou resultado unitário inválido.',
                    ['indice' => $index, 'tipo' => get_debug_type($result)]
                );
            }
            if ($result->getDecisao() !== $this->stage) {
                throw new Sprint84GateException(
                    'FLUXO_DECISAO_DIVERGENTE',
                    'FLUXO_PUBLICO',
                    'A decisão calculada divergiu da etapa autorizada.',
                    [
                        'indice' => $index,
                        'esperado' => $this->stage,
                        'encontrado' => $result->getDecisao(),
                    ]
                );
            }
            $expectedSituation = $this->stage === 'NENHUMA_ACAO'
                ? 'SEM_ALTERACAO'
                : 'CONCLUIDO';
            if ($result->getSituacao() !== $expectedSituation) {
                throw new Sprint84GateException(
                    'FLUXO_SITUACAO_DIVERGENTE',
                    'FLUXO_PUBLICO',
                    'A situação unitária divergiu da etapa autorizada.',
                    [
                        'indice' => $index,
                        'esperado' => $expectedSituation,
                        'encontrado' => $result->getSituacao(),
                    ]
                );
            }
            if (
                $result->getModo() !== ($this->configuration['modo'] ?? null)
                || $result->getAnoExecucao() !== ($this->configuration['ano'] ?? null)
            ) {
                throw new Sprint84GateException(
                    'FLUXO_ESCOPO_DIVERGENTE',
                    'FLUXO_PUBLICO',
                    'Modo ou ano do resultado divergiu do escopo autorizado.',
                    [
                        'indice' => $index,
                        'modo_esperado' => $this->configuration['modo'] ?? null,
                        'modo_encontrado' => $result->getModo(),
                        'ano_esperado' => $this->configuration['ano'] ?? null,
                        'ano_encontrado' => $result->getAnoExecucao(),
                    ]
                );
            }
        }

        $this->validateResultUnits($results, $precheck);
        $this->validateResultQuantities($results);
    }

    /** @param array<int, mixed> $observations */
    private function validatePncpPostState(
        array $observations,
        PublicarPcaViewModel $viewModel
    ): void
    {
        $expected = $this->stage === 'EXCLUIR_PCA' ? 'AUSENCIA_DEFINITIVA' : 'ENCONTRADO';
        $expectedCount = ($this->configuration['modo'] ?? null) === 'INDIVIDUAL' ? 2 : 1;
        if (count($observations) !== $expectedCount) {
            throw new Sprint84GateException(
                'POSCHECK_PNCP_CARDINALIDADE_DIVERGENTE',
                'POSCHECK_PNCP',
                'A quantidade de observações PNCP divergiu do modo autorizado.',
                ['esperado' => $expectedCount, 'encontrado' => count($observations)]
            );
        }
        $observedItemCount = 0;
        foreach ($observations as $index => $observation) {
            if (!is_array($observation) || ($observation['situacao'] ?? null) !== $expected) {
                throw new Sprint84GateException(
                    'POSCHECK_PNCP_DIVERGENTE',
                    'POSCHECK_PNCP',
                    'A observação PNCP posterior divergiu do estado esperado.',
                    ['indice' => $index, 'esperado' => $expected, 'encontrado' => $observation]
                );
            }
            $result = $viewModel->getResultadosUnitarios()[$index];
            if (
                ($observation['sequencial_solicitado'] ?? null) !== $result->getSequencialPca()
                || (
                    $expected === 'ENCONTRADO'
                    && ($observation['sequencial_observado'] ?? null) !== $result->getSequencialPca()
                )
                || (
                    isset($observation['codigo_uasg'])
                    && $observation['codigo_uasg'] !== $result->getCodigoUasg()
                )
            ) {
                throw new Sprint84GateException(
                    'POSCHECK_PNCP_IDENTIDADE_DIVERGENTE',
                    'POSCHECK_PNCP',
                    'Sequencial ou UASG da observação PNCP divergiu do resultado público.',
                    ['indice' => $index, 'resultado' => $this->viewModelToArray($viewModel), 'observacao' => $observation]
                );
            }
            if ($expected === 'ENCONTRADO') {
                $ids = $observation['ids_demandas'] ?? null;
                if (!is_array($ids)) {
                    throw new Sprint84GateException(
                        'POSCHECK_PNCP_ITENS_AUSENTES',
                        'POSCHECK_PNCP',
                        'A observação PNCP encontrada não informou os itens integrais.',
                        ['indice' => $index, 'observacao' => $observation]
                    );
                }
                $observedItemCount += count($ids);
            }
        }

        $expectedItemCount = $this->stage === 'EXCLUIR_PCA'
            ? 0
            : array_sum(array_column(
                $this->configuration['unidades'] ?? [],
                'quantidade_fotografia'
            ));
        if ($observedItemCount !== $expectedItemCount) {
            throw new Sprint84GateException(
                'POSCHECK_PNCP_ITENS_DIVERGENTES',
                'POSCHECK_PNCP',
                'A quantidade de itens PNCP observada divergiu da fotografia autorizada.',
                ['esperado' => $expectedItemCount, 'encontrado' => $observedItemCount]
            );
        }
    }

    /**
     * @param ResultadoUnidadeViewModel[] $results
     * @param array<string, mixed> $precheck
     */
    private function validateResultUnits(array $results, array $precheck): void
    {
        if (($this->configuration['modo'] ?? null) === 'CONSOLIDADO') {
            if ($results[0]->getIdUnidadeOrcamentaria() !== null) {
                throw new Sprint84GateException(
                    'FLUXO_UNIDADE_CONSOLIDADA_INVALIDA',
                    'FLUXO_PUBLICO',
                    'O resultado consolidado não pode possuir identidade individual.',
                    ['identidade' => $results[0]->getIdUnidadeOrcamentaria()]
                );
            }

            return;
        }

        $rows = $precheck['consultas']['unidades_orcamentarias']['linhas'] ?? null;
        if (!is_array($rows)) {
            return;
        }
        $expected = array_map(
            static fn (array $row): array => [
                (string) $row['IDENTIDADE_UO'],
                (string) $row['CODIGO_UASG'],
            ],
            $rows
        );
        $found = array_map(
            static fn (ResultadoUnidadeViewModel $result): array => [
                $result->getIdUnidadeOrcamentaria(),
                $result->getCodigoUasg(),
            ],
            $results
        );
        sort($expected);
        sort($found);
        if ($found !== $expected) {
            throw new Sprint84GateException(
                'FLUXO_UNIDADES_DIVERGENTES',
                'FLUXO_PUBLICO',
                'As identidades/UASGs processadas divergiram das duas UOs do checkpoint.',
                ['esperado' => $expected, 'encontrado' => $found]
            );
        }
    }

    /**
     * @param ResultadoUnidadeViewModel[] $results
     */
    private function validateResultQuantities(array $results): void
    {
        $expectedInclusions = 0;
        $expectedExclusions = 0;
        if ($this->stage === 'PUBLICAR') {
            $expectedInclusions = array_sum(array_column(
                $this->configuration['unidades'] ?? [],
                'quantidade_fotografia'
            ));
        } elseif ($this->stage === 'RETIFICAR') {
            $expectedInclusions = array_sum(array_column(
                $this->configuration['unidades'] ?? [],
                'inclusoes_retificacao'
            ));
            $expectedExclusions = array_sum(array_column(
                $this->configuration['unidades'] ?? [],
                'exclusoes_retificacao'
            ));
        }

        $foundInclusions = array_sum(array_map(
            static fn (ResultadoUnidadeViewModel $result): int => $result->getQuantidadeInclusoes(),
            $results
        ));
        $foundExclusions = array_sum(array_map(
            static fn (ResultadoUnidadeViewModel $result): int => $result->getQuantidadeExclusoes(),
            $results
        ));
        if ($foundInclusions !== $expectedInclusions || $foundExclusions !== $expectedExclusions) {
            throw new Sprint84GateException(
                'FLUXO_QUANTIDADES_DIVERGENTES',
                'FLUXO_PUBLICO',
                'As inclusões/exclusões divergiram das quantidades autorizadas.',
                [
                    'inclusoes_esperadas' => $expectedInclusions,
                    'inclusoes_encontradas' => $foundInclusions,
                    'exclusoes_esperadas' => $expectedExclusions,
                    'exclusoes_encontradas' => $foundExclusions,
                ]
            );
        }
    }

    /**
     * @param array<string, mixed> $precheck
     * @param array<string, mixed> $postcheck
     * @param array<int, mixed> $pncpBefore
     * @param array<int, mixed> $pncpAfter
     * @param array<int, mixed> $diagnostics
     * @param array<string, array<string, mixed>> $targets
     */
    private function validateNoActionInvariants(
        array $precheck,
        array $postcheck,
        array $pncpBefore,
        array $pncpAfter,
        array $diagnostics,
        array $targets
    ): void {
        if ($this->stage !== 'NENHUMA_ACAO') {
            return;
        }

        $expected = $precheck['snapshot'];
        foreach (['todas_versoes', 'versoes_vigentes', 'projecao_operacional'] as $name) {
            if (!isset($expected['resultados'][$name])) {
                continue;
            }
            foreach ($expected['resultados'][$name] as &$row) {
                $target = $targets[(string) ($row['FK_UNIDADE_ORCAMENTARIA'] ?? '')] ?? null;
                if (
                    $target !== null
                    && array_key_exists('ESTADO_PUBLICACAO', $row)
                    && array_key_exists('SEQUENCIAL_PUBLICACAO_PNCP', $row)
                    && $row['ESTADO_PUBLICACAO'] === null
                    && $row['SEQUENCIAL_PUBLICACAO_PNCP'] === null
                ) {
                    $row['ESTADO_PUBLICACAO'] = $target['estado'];
                    $row['SEQUENCIAL_PUBLICACAO_PNCP'] = $target['sequencial'];
                }
            }
            unset($row);
        }

        if ($expected !== $postcheck['snapshot']) {
            throw new Sprint84GateException(
                'NENHUMA_ACAO_ORACLE_ALTERADO',
                'POSCHECK_ORACLE',
                'NENHUMA_ACAO alterou o snapshot Oracle protegido.',
                [
                    'sha256_antes' => $precheck['sha256'] ?? null,
                    'sha256_depois' => $postcheck['sha256'] ?? null,
                    'snapshot_esperado' => $expected,
                    'snapshot_encontrado' => $postcheck['snapshot'],
                ]
            );
        }

        $before = $this->normalizePncpObservations($pncpBefore, 'sequencial_pncp');
        $after = $this->normalizePncpObservations($pncpAfter, 'sequencial_observado');
        if ($before !== $after) {
            throw new Sprint84GateException(
                'NENHUMA_ACAO_PNCP_ALTERADO',
                'POSCHECK_PNCP',
                'NENHUMA_ACAO encontrou alteração entre as observações PNCP.',
                ['antes' => $before, 'depois' => $after]
            );
        }

        foreach ($diagnostics as $index => $call) {
            $request = is_array($call) ? ($call['requisicao'] ?? null) : null;
            if (!is_array($request)) {
                continue;
            }
            $method = strtoupper((string) ($request['metodo'] ?? ''));
            $path = (string) ($request['caminho'] ?? '');
            $allowedLogin = $method === 'POST' && $path === '/v1/usuarios/login';
            if (($method === 'POST' && !$allowedLogin) || $method === 'DELETE') {
                throw new Sprint84GateException(
                    'NENHUMA_ACAO_ESCRITA_PNCP_DETECTADA',
                    'POSCHECK_PNCP',
                    'NENHUMA_ACAO executou uma escrita PNCP proibida.',
                    ['indice' => $index, 'metodo' => $method, 'caminho' => $path]
                );
            }
        }
    }

    /** @return array<string, array{estado: string, sequencial: int|string|null, uasg: string, estrategia: string}> */
    private function projectionTargets(PublicarPcaViewModel $viewModel, array $precheck): array
    {
        $mapping = $this->profile['oracle']['mapeamentos']['estado_operacional'];
        $targets = [];
        foreach ($viewModel->getResultadosUnitarios() as $result) {
            $unitIds = $result->getModo() === 'CONSOLIDADO'
                ? array_column($this->configuration['unidades'], 'id_unidade_orcamentaria')
                : [$result->getIdUnidadeOrcamentaria()];
            foreach ($precheck['consultas']['unidades_orcamentarias']['linhas'] ?? [] as $row) {
                if ((string) $row['IDENTIDADE_UO'] === $result->getIdUnidadeOrcamentaria()) {
                    $unitIds = [$row['CHAVE_UO_PLANO']];
                }
            }
            foreach ($unitIds as $id) {
                $sequence = $result->getSequencialPca();
                if ($sequence === null || $sequence <= 0) {
                    throw new Sprint84GateException(
                        'POSCHECK_SEQUENCIAL_INVALIDO',
                        'POSCHECK_ORACLE',
                        'A projeção operacional exige o sequencial positivo comprovado pelo fluxo.',
                        ['unidade' => $id, 'sequencial' => $sequence]
                    );
                }
                foreach ($precheck['consultas']['projecao_operacional']['linhas'] ?? [] as $row) {
                    if (
                        (string) $row['FK_UNIDADE_ORCAMENTARIA'] === (string) $id
                        && is_string($row['SEQUENCIAL_PUBLICACAO_PNCP'])
                        && $row['SEQUENCIAL_PUBLICACAO_PNCP'] === (string) $sequence
                    ) {
                        $sequence = $row['SEQUENCIAL_PUBLICACAO_PNCP'];
                    }
                }
                $targets[(string) $id] = [
                    'estado' => $mapping[$this->stage === 'EXCLUIR_PCA' ? 'literalExcluido' : 'literalPublicado'],
                    'sequencial' => $sequence,
                    'uasg' => $result->getCodigoUasg(),
                    'estrategia' => $mapping[$result->getModo() === 'CONSOLIDADO'
                        ? 'literalEstrategiaConsolidada' : 'literalEstrategiaIndividual'],
                ];
            }
        }
        return $targets;
    }

    /** @param array<string, mixed> $postcheck @param array<string, array<string, mixed>> $targets */
    private function validateOperationalProjection(array $postcheck, array $targets): void
    {
        foreach (['projecao_operacional', 'todas_versoes', 'versoes_vigentes'] as $name) {
            if (!isset($this->queries[$name]) && $name !== 'projecao_operacional') {
                continue;
            }
            $rows = $postcheck['consultas'][$name]['linhas'] ?? [];
            $expectedCount = $this->queries[$name]['expected_cardinality']['exact'] ?? null;
            $valid = $rows !== [] && count($rows) === $expectedCount;
            foreach ($rows as $index => $row) {
                $target = $targets[(string) ($row['FK_UNIDADE_ORCAMENTARIA'] ?? '')] ?? null;
                if (
                    $target === null
                    || ($row['ESTADO_PUBLICACAO'] ?? null) !== $target['estado']
                    || !array_key_exists('SEQUENCIAL_PUBLICACAO_PNCP', $row)
                    || (!is_int($row['SEQUENCIAL_PUBLICACAO_PNCP']) && !is_string($row['SEQUENCIAL_PUBLICACAO_PNCP']))
                    || (string) $row['SEQUENCIAL_PUBLICACAO_PNCP'] !== (string) $target['sequencial']
                    || ($row['ESTRATEGIA_ADOTADA_PUBLICACAO'] ?? null) !== $target['estrategia']
                    || (string) ($row['UASG_ADOTADA'] ?? '') !== $target['uasg']
                ) {
                    throw new Sprint84GateException(
                        'POSCHECK_PROJECAO_OPERACIONAL_DIVERGENTE',
                        'POSCHECK_ORACLE',
                        'A projeção operacional posterior está pendente ou diverge do resultado comprovado.',
                        ['consulta' => $name, 'indice' => $index, 'esperado' => $target, 'encontrado' => $row]
                    );
                }
            }
            if (!$valid) {
                throw new Sprint84GateException(
                    'POSCHECK_PROJECAO_OPERACIONAL_INCOMPLETA',
                    'POSCHECK_ORACLE',
                    'A projeção operacional posterior não possui a cardinalidade esperada.',
                    ['consulta' => $name, 'esperado' => $expectedCount, 'linhas' => $rows]
                );
            }
        }
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function completedProjectionPairs(array $before, array $after): array
    {
        $completed = [];
        foreach (['projecao_operacional', 'todas_versoes', 'versoes_vigentes'] as $name) {
            foreach ($before['consultas'][$name]['linhas'] ?? [] as $index => $row) {
                if (
                    ($row['ESTADO_PUBLICACAO'] ?? null) === null
                    && ($row['SEQUENCIAL_PUBLICACAO_PNCP'] ?? null) === null
                ) {
                    $completed[$name][] = ['antes' => $row, 'depois' => $after['consultas'][$name]['linhas'][$index]];
                }
            }
        }
        return $completed;
    }

    /** @param array<int, mixed> $observations @return array<string, array<string, mixed>> */
    private function normalizePncpObservations(array $observations, string $sequenceKey): array
    {
        $normalized = [];
        foreach ($observations as $observation) {
            if (!is_array($observation)) {
                continue;
            }
            $uasg = (string) ($observation['codigo_uasg'] ?? '');
            $ids = $observation['ids_demandas'] ?? [];
            if (!is_array($ids)) {
                $ids = [];
            }
            $ids = array_map('intval', $ids);
            sort($ids, SORT_NUMERIC);
            $normalized[$uasg] = [
                'sequencial' => isset($observation[$sequenceKey])
                    ? (int) $observation[$sequenceKey]
                    : null,
                'ids_demandas' => $ids,
            ];
        }
        ksort($normalized, SORT_STRING);

        return $normalized;
    }

    /** @param array<string, mixed> $precheck @return array<string, mixed> */
    private function baseReport(array $precheck): array
    {
        return [
            'sprint' => '8.4',
            'etapa' => $this->stage,
            'modo' => $this->configuration['modo'] ?? null,
            'request_id' => $this->requestId,
            'server_name' => $this->serverName,
            'login_sgi' => $this->authenticatedLogin,
            'caminhos' => $this->paths(),
            'configuracao' => $this->configuration,
            'perfil' => $this->profile,
            'precheck_oracle' => $precheck,
        ];
    }

    /** @return array<string, array<string, string|null>> */
    private function paths(): array
    {
        $config = getenv(Sprint84Config::ENVIRONMENT_VARIABLE);
        $registry = getenv('PCA_PNCP_PROFILE_REGISTRY_FILE');

        return [
            'configuracao_sprint84' => $this->pathDetails($config),
            'registro_perfis' => $this->pathDetails($registry),
        ];
    }

    /** @return array<string, string|null> */
    private function pathDetails(string|false $path): array
    {
        $resolved = is_string($path) ? realpath($path) : false;

        return [
            'informado' => is_string($path) ? $path : null,
            'resolvido' => is_string($resolved) ? $resolved : null,
        ];
    }

    /** @return array<string, mixed> */
    private function viewModelToArray(PublicarPcaViewModel $viewModel): array
    {
        return [
            'situacao' => $viewModel->getSituacao(),
            'codigo_falha' => $viewModel->getCodigoFalha(),
            'mensagem_falha' => $viewModel->getMensagemFalha(),
            'quantidade_planejadas' => $viewModel->getQuantidadePlanejadas(),
            'quantidade_visitadas' => $viewModel->getQuantidadeVisitadas(),
            'quantidade_nao_processadas' => $viewModel->getQuantidadeNaoProcessadas(),
            'correlacao' => $viewModel->getCorrelacao(),
            'resultados_unitarios' => array_map(
                static fn (ResultadoUnidadeViewModel $result): array => [
                    'id_unidade_orcamentaria' => $result->getIdUnidadeOrcamentaria(),
                    'modo' => $result->getModo(),
                    'ano_execucao' => $result->getAnoExecucao(),
                    'codigo_uasg' => $result->getCodigoUasg(),
                    'descricao_uasg' => $result->getDescricaoUasg(),
                    'situacao' => $result->getSituacao(),
                    'decisao' => $result->getDecisao(),
                    'sequencial_pca' => $result->getSequencialPca(),
                    'quantidade_inclusoes' => $result->getQuantidadeInclusoes(),
                    'quantidade_exclusoes' => $result->getQuantidadeExclusoes(),
                    'codigo_falha' => $result->getCodigoFalha(),
                    'mensagem_falha' => $result->getMensagemFalha(),
                ],
                $viewModel->getResultadosUnitarios()
            ),
        ];
    }
}
