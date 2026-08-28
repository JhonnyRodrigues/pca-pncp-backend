<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint59\Support;

use DateTimeImmutable;
use RuntimeException;
use Semae\Pca\Application\Commands\ComandoExcluirItensPca;
use Semae\Pca\Application\Commands\ComandoExcluirPca;
use Semae\Pca\Application\Commands\ComandoInserirItensPca;
use Semae\Pca\Application\Commands\ComandoPublicarPca;
use Semae\Pca\Application\Enums\SituacaoAutenticacaoPncp;
use Semae\Pca\Application\Enums\SituacaoBuscaCandidatosPca;
use Semae\Pca\Application\Enums\SituacaoEscritaPncp;
use Semae\Pca\Application\Enums\SituacaoObservacaoPca;
use Semae\Pca\Application\Results\ResultadoBuscaCandidatosPca;
use Semae\Pca\Application\Results\ResultadoPublicacaoPncp;
use Semae\Pca\Application\Results\ResultadoObservacaoPca;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\Demanda;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\SituacaoDemanda;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\DecimalExato;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Semae\Pca\Infrastructure\Observabilidade\AcumuladorEventosTecnicos;
use Semae\Pca\Infrastructure\Observabilidade\RelogioSistemaEventosTecnicos;
use Semae\Pca\Infrastructure\Pncp\Autenticacao\AutenticadorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Autenticacao\SessaoPncpAutenticada;
use Semae\Pca\Infrastructure\Pncp\Escrita\EscritorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Semae\Pca\Infrastructure\Pncp\Http\ConfiguracaoTransportePncp;
use Semae\Pca\Infrastructure\Pncp\Http\ExecutorCurlNativoPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteCurlPncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\ConfiguracaoLeituraPncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\LeitorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Leitura\TemporizadorSistemaPncp;
use Semae\Pca\Infrastructure\Pncp\Observabilidade\AcaoTecnicaPncp;
use Semae\Pca\Infrastructure\Pncp\Serializacao\SerializadorPayloadPncp;
use Throwable;

final class PncpAdaptersContractRunner
{
    private readonly Cnpj $cnpj;
    private readonly UnidadeExternaPublicacao $unidade;
    private readonly AcumuladorEventosTecnicos $logger;
    private readonly PncpDiagnosticTransport $diagnosticTransport;
    private readonly AutenticadorPncpAdapter $autenticador;
    private readonly LeitorPncpAdapter $leitor;
    private readonly EscritorPncpAdapter $escritor;

    /** @var array<string, bool|string|null> */
    private array $cleanupDiagnostic = [
        'pca_detectado_apos_publicacao' => null,
        'tentativa' => 'NAO_INICIADA',
        'ausencia_definitiva_confirmada' => false,
    ];

    /** @param array<string, mixed> $config */
    private function __construct(private readonly array $config)
    {
        $transporteConfig = new ConfiguracaoTransportePncp(
            AmbientePncp::HOMOLOGACAO,
            $config['limite_payload_bytes'],
            $config['limite_resposta_bytes'],
            $config['timeout_conexao'],
            $config['timeout_login'],
            $config['timeout_leitura'],
            $config['timeout_escrita'],
            $config['ca_bundle']
        );
        $transporte = new TransporteCurlPncp($transporteConfig, new ExecutorCurlNativoPncp());
        $this->diagnosticTransport = new PncpDiagnosticTransport($transporte);
        $this->logger = new AcumuladorEventosTecnicos(
            self::uuidV4(),
            'PNCP_TREINAMENTO',
            'CONTRATOS_ADAPTERS_SPRINT_59',
            null,
            new RelogioSistemaEventosTecnicos()
        );
        $this->autenticador = new AutenticadorPncpAdapter(
            $this->diagnosticTransport,
            $transporteConfig,
            $this->logger,
            $config['login'],
            $config['senha']
        );
        $this->leitor = new LeitorPncpAdapter(
            $this->diagnosticTransport,
            $transporteConfig,
            new ConfiguracaoLeituraPncp($config['page_size'], 64, $config['max_pages'], 250, 5000),
            $this->logger,
            new TemporizadorSistemaPncp()
        );
        $this->escritor = new EscritorPncpAdapter(
            $this->diagnosticTransport,
            $transporteConfig,
            new SerializadorPayloadPncp(),
            $this->logger
        );

        $this->cnpj = new Cnpj($config['cnpj']);
        $year = new AnoExecucao($config['ano']);
        $uasg = new Uasg($config['codigo_uasg'], $config['descricao_uasg']);
        $unit = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria('SPRINT-59'),
            new Lotacao(1, 'Massa dedicada Sprint 5.9'),
            $uasg,
            'Massa dedicada Sprint 5.9'
        );
        $this->unidade = UnidadeExternaPublicacao::individual(new IdentidadePlano($unit, $year));
    }

    /** @param array<string, mixed> $config */
    public static function fromConfig(array $config): self
    {
        return new self(Sprint59Config::pncp($config));
    }

    /** @return array<string, mixed> */
    public function run(): array
    {
        $this->assertPublicationMayStart();
        $session = $this->authenticate();

        $tracked = null;
        $cleanupConfirmed = true;
        $writesStarted = false;
        $steps = [];

        try {
            $this->cleanExplicitResidueOrRefuseUnknown($session, $steps);
            $demands = $this->demands();
            $writesStarted = true;
            $publication = $this->escritor->publicar(
                $session,
                new ComandoPublicarPca($this->cnpj, $this->unidade, [$demands[0], $demands[1]])
            );
            $this->validatePublication(
                $publication,
                $this->diagnosticTransport->getUltimaResposta(),
                $this->diagnosticTransport->getUltimaRequisicao(),
                $this->diagnosticTransport->getUltimaFalha()
            );
            $tracked = $publication->getSequencialPca();
            $steps['publicacao'] = 'ACEITA';

            $this->requireObservedIds($session, $tracked, [$demands[0]->getIdDemanda(), $demands[1]->getIdDemanda()]);
            $steps['observacao_inicial'] = 'ENCONTRADO';

            $insertion = $this->escritor->inserirItens(
                $session,
                new ComandoInserirItensPca($this->cnpj, $this->unidade, $tracked, [$demands[2]])
            );
            if ($insertion->getSituacao() !== SituacaoEscritaPncp::ACEITA) {
                throw new RuntimeException('Gate PNCP nao confirmou a inclusao de item.');
            }
            $this->requireObservedIds($session, $tracked, array_map(
                static fn (Demanda $demand): int => $demand->getIdDemanda(),
                $demands
            ));
            $steps['inclusao'] = 'ACEITA';

            $exclusion = $this->escritor->excluirItens(
                $session,
                new ComandoExcluirItensPca(
                    $this->cnpj,
                    $this->unidade,
                    $tracked,
                    [$demands[1]->getIdDemanda()],
                    'Validacao contratual Sprint 5.9'
                )
            );
            if ($exclusion->getSituacao() !== SituacaoEscritaPncp::ACEITA) {
                throw new RuntimeException('Gate PNCP nao confirmou a exclusao de item.');
            }
            $this->requireObservedIds(
                $session,
                $tracked,
                [$demands[0]->getIdDemanda(), $demands[2]->getIdDemanda()]
            );
            $steps['exclusao_item'] = 'ACEITA';

            $this->deleteAndRequireAbsence($session, $tracked);
            $tracked = null;
            $steps['exclusao_pca'] = 'AUSENCIA_DEFINITIVA';
        } catch (Throwable $failure) {
            $cleanupConfirmed = $writesStarted
                ? $this->bestEffortCleanupAfterWrite($session, $tracked)
                : false;
            throw $this->gateFailureAfterCleanup($failure, $cleanupConfirmed);
        }

        $this->assertTechnicalEventsAreSafe($session);

        return [
            'sprint' => '5.9',
            'gate' => 'PNCP_TREINAMENTO',
            'confirmado' => true,
            'ambiente_canonico' => true,
            'tls_confirmado' => true,
            'sessao_opaca' => true,
            'paginacao_exercitada_pelo_adapter' => true,
            'resultados_tipados' => true,
            'decimais_exatos' => ['10.0000', '0.1667', '7.2500'],
            'ids_lacunados' => true,
            'etapas' => $steps,
            'limpeza_confirmada' => $cleanupConfirmed,
            'eventos_sanitizados' => true,
            'quantidade_eventos' => count($this->logger->getEventos()),
            'adapters' => [
                AutenticadorPncpAdapter::class,
                LeitorPncpAdapter::class,
                EscritorPncpAdapter::class,
                TransporteCurlPncp::class,
                SerializadorPayloadPncp::class,
            ],
            'cnpj_alfanumerico' => 'NAO_COMPROVADO_NAO_BLOQUEANTE',
            'lote_1001' => 'NAO_COMPROVADO_NAO_BLOQUEANTE',
        ];
    }

    /** @return array<string, mixed> */
    public function cleanupOwnedResidue(): array
    {
        $authorized = $this->config['owned_residue_sequence'];
        if (!is_int($authorized) || $authorized < 1) {
            throw new RuntimeException(
                'A limpeza exige owned_residue_sequence explicitamente autorizado.'
            );
        }

        $session = $this->authenticate();
        $this->diagnosticTransport->limparHistorico();
        $sequence = new SequencialPca($authorized);
        $observation = null;
        $deleted = false;

        try {
            $observation = $this->leitor->observarPcaConhecido(
                $session,
                $this->cnpj,
                $this->unidade,
                $sequence
            );

            if (
                $observation->getSituacao() !== SituacaoObservacaoPca::ENCONTRADO
                && $observation->getSituacao() !== SituacaoObservacaoPca::AUSENCIA_DEFINITIVA
            ) {
                $observation = $this->cleanupObservationFromSearch(
                    $this->leitor->localizarCandidatos($session, $this->cnpj, $this->unidade),
                    $authorized
                );
            }

            if ($observation->getSituacao() === SituacaoObservacaoPca::ENCONTRADO) {
                $this->deleteAndRequireAbsence($session, $sequence);
                $deleted = true;
            } elseif ($observation->getSituacao() !== SituacaoObservacaoPca::AUSENCIA_DEFINITIVA) {
                throw new RuntimeException(
                    'A limpeza recusou excluir sem confirmar o PCA conhecido da massa dedicada.'
                );
            }

            $this->assertTechnicalEventsAreSafe($session);
        } catch (Throwable $failure) {
            throw $this->cleanupFailure($failure, $observation);
        }

        return array_merge([
            'sprint' => '5.9',
            'operacao' => 'LIMPEZA_PNCP_TREINAMENTO',
            'confirmado' => true,
            'alvo_explicitamente_autorizado' => true,
            'pca_encontrado' => $deleted,
            'exclusao_executada' => $deleted,
            'exclusao_aceita' => $deleted,
            'ausencia_definitiva_confirmada' => true,
            'publicacao_executada' => false,
            'eventos_sanitizados' => true,
            'quantidade_eventos' => count($this->logger->getEventos()),
        ], $this->cleanupDiagnostic($observation));
    }

    private function cleanupFailure(
        Throwable $failure,
        ?ResultadoObservacaoPca $observation
    ): RuntimeException {
        $report = array_merge([
            'sprint' => '5.9',
            'operacao' => 'LIMPEZA_PNCP_TREINAMENTO',
            'confirmado' => false,
            'exclusao_aceita' => $this->cleanupDeleteStatus() === 200,
            'ausencia_definitiva_confirmada' => false,
            'publicacao_executada' => false,
        ], $this->cleanupDiagnostic($observation));

        return new RuntimeException(
            SafeContractReport::encode($report, [
                $this->config['login'],
                $this->config['senha'],
                $this->config['cnpj'],
                $this->config['codigo_uasg'],
                $this->config['descricao_uasg'],
            ]),
            0,
            $failure
        );
    }

    /** @return array<string, mixed> */
    private function cleanupDiagnostic(?ResultadoObservacaoPca $observation): array
    {
        $safeFailure = $observation?->getFalha();
        $items = $observation?->getPcaObservado()?->getIdsDemandas() ?? [];
        $reads = [];

        foreach ($this->diagnosticTransport->getHistorico() as $exchange) {
            $request = $exchange['requisicao'];
            if ($request->getMetodo() !== 'GET') {
                continue;
            }

            $reads[] = array_merge([
                'status_http' => $exchange['resposta']?->getStatus(),
                'caminho_logico' => $this->safeLogicalPath($request),
            ], $this->safeResponseError($exchange['resposta']));
        }

        return [
            'situacao_observacao' => $observation?->getSituacao()->value,
            'falha_codigo' => $safeFailure?->getCodigo(),
            'falha_mensagem' => $safeFailure?->getMensagem(),
            'quantidade_itens_observados' => count($items),
            'requisicoes_leitura' => $reads,
            'status_http_exclusao' => $this->cleanupDeleteStatus(),
        ];
    }

    private function cleanupDeleteStatus(): ?int
    {
        foreach ($this->diagnosticTransport->getHistorico() as $exchange) {
            if ($exchange['requisicao']->getMetodo() === 'DELETE') {
                return $exchange['resposta']?->getStatus();
            }
        }

        return null;
    }

    private function cleanupObservationFromSearch(
        ResultadoBuscaCandidatosPca $search,
        int $authorizedSequence
    ): ResultadoObservacaoPca {
        if ($search->getSituacao() !== SituacaoBuscaCandidatosPca::CONCLUIDA) {
            throw new RuntimeException(
                'A limpeza nao concluiu a busca independente do residuo autorizado.'
            );
        }

        $candidates = $search->getCandidatos();
        if ($candidates === []) {
            return ResultadoObservacaoPca::ausenciaDefinitiva();
        }

        if (count($candidates) !== 1) {
            throw new RuntimeException('A limpeza encontrou candidatos ambiguos para a massa dedicada.');
        }

        $candidate = $candidates[0];
        if ($candidate->getSequencialPca()?->getValor() !== $authorizedSequence) {
            throw new RuntimeException('A limpeza encontrou residuo diferente do autorizado.');
        }

        return ResultadoObservacaoPca::encontrado($candidate);
    }

    private function safeLogicalPath(RequisicaoHttpPncp $request): string
    {
        return str_replace(
            rawurlencode((string) $this->config['cnpj']),
            '{cnpj}',
            $request->getCaminho()
        );
    }

    /** @return array{codigo_resposta_seguro: string|null, mensagem_resposta_segura: string|null} */
    private function safeResponseError(?RespostaHttpPncp $response): array
    {
        $result = [
            'codigo_resposta_seguro' => null,
            'mensagem_resposta_segura' => null,
        ];

        if ($response === null || $response->getStatus() < 400) {
            return $result;
        }

        $decoded = json_decode($response->getCorpo(), true);
        if (!is_array($decoded) || array_is_list($decoded)) {
            return $result;
        }

        foreach (['code', 'codigo', 'error', 'erro', 'title', 'titulo'] as $key) {
            if (isset($decoded[$key]) && is_scalar($decoded[$key])) {
                $result['codigo_resposta_seguro'] = $this->sanitizeDiagnosticText(
                    (string) $decoded[$key]
                );
                break;
            }
        }

        foreach (['message', 'mensagem', 'detail', 'detalhe'] as $key) {
            if (isset($decoded[$key]) && is_scalar($decoded[$key])) {
                $result['mensagem_resposta_segura'] = $this->sanitizeDiagnosticText(
                    (string) $decoded[$key]
                );
                break;
            }
        }

        return $result;
    }

    private function sanitizeDiagnosticText(string $text): string
    {
        $text = preg_replace('~https?://[^\s"<>]+~i', '{url}', $text) ?? '';
        $text = preg_replace('~Authorization\s*[:=]\s*\S+~i', 'Authorization {redacted}', $text)
            ?? '';
        $text = preg_replace('~Bearer\s+\S+~i', 'Bearer {redacted}', $text) ?? '';
        $text = preg_replace('~\b\d{14}\b~', '{cnpj}', $text) ?? '';

        foreach ([
            $this->config['login'] ?? '',
            $this->config['senha'] ?? '',
            $this->config['cnpj'] ?? '',
            $this->config['codigo_uasg'] ?? '',
            $this->config['descricao_uasg'] ?? '',
        ] as $sensitive) {
            if (is_string($sensitive) && $sensitive !== '') {
                $replacement = $sensitive === ($this->config['cnpj'] ?? null)
                    ? '{cnpj}'
                    : '{dado_sensivel}';
                $text = str_replace($sensitive, $replacement, $text);
            }
        }

        return substr(trim($text), 0, 500);
    }

    private function assertPublicationMayStart(): void
    {
        if ($this->config['owned_residue_sequence'] !== null) {
            throw new RuntimeException(
                'Gate PNCP recusado: execute a limpeza isolada e remova '
                    . 'owned_residue_sequence antes de nova publicacao.'
            );
        }
    }

    private function authenticate(): SessaoPncpAutenticada
    {
        $auth = $this->autenticador->autenticar();
        if ($auth->getSituacao() !== SituacaoAutenticacaoPncp::AUTENTICADA) {
            throw new RuntimeException('Gate PNCP falhou na autenticacao segura.');
        }
        $session = $auth->getSessao();
        if (!$session instanceof SessaoPncpAutenticada) {
            throw new RuntimeException('Gate PNCP nao recebeu sessao opaca compativel.');
        }

        return $session;
    }

    /** @return Demanda[] */
    private function demands(): array
    {
        $identity = $this->unidade->getIdentidadePlano();
        if ($identity === null) {
            throw new RuntimeException('Massa PNCP individual invalida.');
        }
        $unit = $identity->getUnidadeOrcamentaria();
        $year = $identity->getAnoExecucao();
        $date = new DateTimeImmutable($this->config['data_desejada']);
        $ids = [
            $this->config['id_demanda_publicacao_1'],
            $this->config['id_demanda_publicacao_2'],
            $this->config['id_demanda_inclusao'],
        ];
        $totals = ['10.0000', '0.1667', '7.2500'];
        $template = $this->config['item_template'];

        return array_map(
            static fn (int $id, int $index): Demanda => new Demanda(
                $id,
                SituacaoDemanda::HOMOLOGADA,
                1,
                $unit,
                $year,
                $date,
                $template['categoria'],
                'PCA-S59 contrato adapter ' . ($index + 1),
                $template['unidade_medida'],
                3,
                new DecimalExato($totals[$index]),
                new DecimalExato($totals[$index]),
                $template['catalogo'],
                $template['codigo_item'],
                $template['grupo'],
                $template['descricao_grupo'],
                $template['origem']
            ),
            $ids,
            array_keys($ids)
        );
    }

    private function cleanExplicitResidueOrRefuseUnknown(
        SessaoPncpAutenticada $session,
        array &$steps
    ): void {
        $search = $this->leitor->localizarCandidatos($session, $this->cnpj, $this->unidade);
        if ($search->getSituacao() !== SituacaoBuscaCandidatosPca::CONCLUIDA) {
            throw new RuntimeException('Gate PNCP nao concluiu a busca preventiva.');
        }
        $candidates = $search->getCandidatos();
        if ($candidates === []) {
            $steps['busca_preventiva'] = 'SEM_CANDIDATOS';
            return;
        }

        $authorized = $this->config['owned_residue_sequence'];
        if (count($candidates) !== 1 || $authorized === null) {
            throw new RuntimeException('Gate PNCP recusou alvo preexistente nao autorizado.');
        }
        $sequence = $candidates[0]->getSequencialPca();
        if ($sequence === null || $sequence->getValor() !== $authorized) {
            throw new RuntimeException('Gate PNCP recusou residuo diferente do autorizado.');
        }
        $this->deleteAndRequireAbsence($session, $sequence);
        $steps['residuo_autorizado'] = 'REMOVIDO';
    }

    /** @param int[] $expectedIds */
    private function requireObservedIds(
        SessaoPncpAutenticada $session,
        SequencialPca $sequence,
        array $expectedIds
    ): void {
        $observation = $this->leitor->observarPcaConhecido(
            $session,
            $this->cnpj,
            $this->unidade,
            $sequence
        );
        if ($observation->getSituacao() !== SituacaoObservacaoPca::ENCONTRADO) {
            throw new RuntimeException('Gate PNCP nao encontrou o PCA esperado.');
        }
        $observed = $observation->getPcaObservado();
        $actualIds = $observed?->getIdsDemandas() ?? [];
        sort($actualIds);
        sort($expectedIds);
        if ($actualIds !== $expectedIds) {
            throw new RuntimeException('Gate PNCP observou conjunto de itens divergente.');
        }
    }

    private function deleteAndRequireAbsence(
        SessaoPncpAutenticada $session,
        SequencialPca $sequence
    ): void {
        $result = $this->escritor->excluirPca(
            $session,
            new ComandoExcluirPca(
                $this->cnpj,
                $this->unidade,
                $sequence,
                'Limpeza contratual Sprint 5.9'
            )
        );
        if ($result->getSituacao() !== SituacaoEscritaPncp::ACEITA) {
            throw new RuntimeException('Gate PNCP nao confirmou a exclusao integral.');
        }
        $observation = $this->leitor->observarPcaConhecido(
            $session,
            $this->cnpj,
            $this->unidade,
            $sequence
        );
        if ($observation->getSituacao() !== SituacaoObservacaoPca::AUSENCIA_DEFINITIVA) {
            $observation = $this->cleanupObservationFromSearch(
                $this->leitor->localizarCandidatos($session, $this->cnpj, $this->unidade),
                $sequence->getValor()
            );
        }
        if ($observation->getSituacao() !== SituacaoObservacaoPca::AUSENCIA_DEFINITIVA) {
            throw new RuntimeException('Gate PNCP nao comprovou ausencia definitiva.');
        }
    }

    private function bestEffortCleanupAfterWrite(
        SessaoPncpAutenticada $session,
        ?SequencialPca $sequence
    ): bool {
        $this->cleanupDiagnostic = [
            'pca_detectado_apos_publicacao' => null,
            'tentativa' => 'INICIADA',
            'ausencia_definitiva_confirmada' => false,
        ];

        try {
            if ($sequence !== null) {
                $this->cleanupDiagnostic['pca_detectado_apos_publicacao'] = true;
                $this->cleanupDiagnostic['tentativa'] = 'EXCLUSAO_POR_SEQUENCIAL';
                $this->deleteAndRequireAbsence($session, $sequence);
                $this->cleanupDiagnostic['tentativa'] = 'PCA_REMOVIDO';
                $this->cleanupDiagnostic['ausencia_definitiva_confirmada'] = true;

                return true;
            }

            $search = $this->leitor->localizarCandidatos($session, $this->cnpj, $this->unidade);
            if ($search->getSituacao() !== SituacaoBuscaCandidatosPca::CONCLUIDA) {
                $this->cleanupDiagnostic['tentativa'] = 'BUSCA_INCONCLUSIVA';

                return false;
            }
            $candidates = $search->getCandidatos();
            if ($candidates === []) {
                $this->cleanupDiagnostic['pca_detectado_apos_publicacao'] = false;
                $this->cleanupDiagnostic['tentativa'] = 'NENHUM_PCA_ENCONTRADO';
                $this->cleanupDiagnostic['ausencia_definitiva_confirmada'] = true;

                return true;
            }
            $this->cleanupDiagnostic['pca_detectado_apos_publicacao'] = true;
            if (count($candidates) !== 1 || $candidates[0]->getSequencialPca() === null) {
                $this->cleanupDiagnostic['tentativa'] = 'CANDIDATO_NAO_DETERMINISTICO';

                return false;
            }
            $this->cleanupDiagnostic['tentativa'] = 'EXCLUSAO_POR_BUSCA';
            $this->deleteAndRequireAbsence($session, $candidates[0]->getSequencialPca());
            $this->cleanupDiagnostic['tentativa'] = 'PCA_REMOVIDO';
            $this->cleanupDiagnostic['ausencia_definitiva_confirmada'] = true;

            return true;
        } catch (Throwable $failure) {
            $this->cleanupDiagnostic['tentativa'] = 'FALHOU';
            $this->cleanupDiagnostic['falha_tipo'] = $failure::class;
            $this->cleanupDiagnostic['falha_mensagem'] = $failure->getMessage();

            return false;
        }
    }

    private function validatePublication(
        ResultadoPublicacaoPncp $publication,
        ?RespostaHttpPncp $response,
        ?RequisicaoHttpPncp $request,
        ?Throwable $transportFailure
    ): void {
        if (
            $publication->getSituacao() === SituacaoEscritaPncp::ACEITA
            && $publication->getSequencialPca() !== null
        ) {
            return;
        }

        $failure = $publication->getFalha();
        $statusHttp = $response?->getStatus() ?? $this->publicationHttpStatus();
        $headers = $response?->getHeaders() ?? [];
        $locations = $headers['location'] ?? [];
        $cause = $this->publicationFailureCause(
            $publication->getSituacao(),
            $failure?->getCodigo(),
            $statusHttp,
            $publication->getSequencialPca() !== null,
            $response
        );
        $diagnostic = [
            'situacao' => $publication->getSituacao()->value,
            'falha_codigo' => $failure?->getCodigo(),
            'falha_mensagem' => $failure?->getMensagem(),
            'sequencial_retornado' => $publication->getSequencialPca()?->getValor(),
            'status_http' => $statusHttp,
            'location_valores' => $locations,
            'quantidade_location' => count($locations),
            'headers_resposta' => $headers,
            'corpo_resposta' => $response?->getCorpo(),
            'payload_enviado' => $request?->getCorpo(),
            'classificacao_causa' => $cause,
            'excecao_original' => $transportFailure === null ? null : [
                'tipo' => $transportFailure::class,
                'mensagem' => $transportFailure->getMessage(),
            ],
        ];

        throw new RuntimeException(
            $this->encodeDiagnostic($diagnostic),
            0,
            $transportFailure
        );
    }

    private function publicationFailureCause(
        SituacaoEscritaPncp $situation,
        ?string $failureCode,
        ?int $statusHttp,
        bool $hasSequence,
        ?RespostaHttpPncp $response
    ): string {
        if ($situation === SituacaoEscritaPncp::ACEITA && !$hasSequence) {
            return 'SEQUENCIAL_AUSENTE';
        }

        if ($situation === SituacaoEscritaPncp::REJEITADA) {
            return 'PUBLICACAO_REJEITADA';
        }

        if ($situation === SituacaoEscritaPncp::FALHA_SISTEMICA) {
            return $failureCode === 'PNCP_PUBLICACAO_TRANSPORTE_FALHOU'
                ? 'FALHA_TRANSPORTE_OU_TLS'
                : 'FALHA_SISTEMICA';
        }

        if (
            $situation === SituacaoEscritaPncp::RESULTADO_INCERTO
            && in_array($statusHttp, [200, 201], true)
            && !$hasSequence
        ) {
            return $response === null
                ? 'LOCATION_AUSENTE'
                : $this->classifyLocation($response);
        }

        if ($situation === SituacaoEscritaPncp::RESULTADO_INCERTO && $statusHttp === null) {
            return 'FALHA_TRANSPORTE_OU_RESULTADO_SEM_RESPOSTA';
        }

        return 'STATUS_HTTP_INESPERADO';
    }

    private function classifyLocation(RespostaHttpPncp $response): string
    {
        $locations = $response->getHeaders()['location'] ?? [];

        if ($locations === []) {
            return 'LOCATION_AUSENTE';
        }
        if (count($locations) !== 1) {
            return 'LOCATION_DUPLICADO';
        }

        $location = trim($locations[0]);
        if (str_contains($location, '?')) {
            return 'LOCATION_COM_QUERY_STRING';
        }
        if (str_contains($location, '#')) {
            return 'LOCATION_COM_FRAGMENTO';
        }

        $absolute = preg_match('#^https?://#i', $location) === 1;
        $path = $location;
        $alternateHomologationPrefix = false;

        if ($absolute) {
            $actual = parse_url($location);
            $expected = parse_url(AmbientePncp::HOMOLOGACAO->getBaseUrl());
            if (!is_array($actual) || !is_array($expected)) {
                return 'LOCATION_CAMINHO_DIVERGENTE';
            }
            if (($actual['host'] ?? null) !== ($expected['host'] ?? null)) {
                return 'LOCATION_HOST_DIVERGENTE';
            }
            if (
                ($actual['scheme'] ?? null) !== ($expected['scheme'] ?? null)
                || ($actual['port'] ?? null) !== ($expected['port'] ?? null)
            ) {
                return 'LOCATION_ORIGEM_DIVERGENTE';
            }

            $basePath = rtrim((string) ($expected['path'] ?? ''), '/');
            $actualPath = (string) ($actual['path'] ?? '');
            if (str_starts_with($actualPath, $basePath . '/')) {
                $path = substr($actualPath, strlen($basePath));
            } elseif (str_starts_with($actualPath, '/pncp-api/')) {
                $path = substr($actualPath, strlen('/pncp-api'));
                $alternateHomologationPrefix = true;
            } else {
                return 'LOCATION_CAMINHO_DIVERGENTE';
            }
        }

        $matches = [];
        if (preg_match('#^/v1/orgaos/([^/]+)/pca/([^/]+)/([^/]+)$#D', $path, $matches) !== 1) {
            return 'LOCATION_CAMINHO_DIVERGENTE';
        }
        if ($matches[1] !== rawurlencode((string) $this->config['cnpj'])) {
            return 'LOCATION_CNPJ_DIVERGENTE';
        }
        if ($matches[2] !== (string) $this->config['ano']) {
            return 'LOCATION_ANO_DIVERGENTE';
        }
        if (
            preg_match('/^[1-9][0-9]*$/D', $matches[3]) !== 1
            || !is_int(filter_var(
                $matches[3],
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            ))
        ) {
            return 'LOCATION_SEQUENCIAL_INVALIDO';
        }

        if (!$absolute) {
            return 'LOCATION_RELATIVO_CANONICO';
        }

        return $alternateHomologationPrefix
            ? 'LOCATION_ABSOLUTO_CANONICO_ALTERNATIVO'
            : 'LOCATION_ABSOLUTO_CANONICO';
    }

    private function publicationHttpStatus(): ?int
    {
        foreach (array_reverse($this->logger->getEventos()) as $event) {
            $envelope = $event->paraEnvelope();

            if (($envelope['action'] ?? null) !== AcaoTecnicaPncp::PUBLICAR_PCA_PNCP->value) {
                continue;
            }

            $status = $envelope['metadata']['statusHttp'] ?? null;

            return is_int($status) ? $status : null;
        }

        return null;
    }

    private function gateFailureAfterCleanup(
        Throwable $failure,
        bool $cleanupConfirmed
    ): RuntimeException {
        $failureDiagnostic = json_decode($failure->getMessage(), true);

        return new RuntimeException(
            $this->encodeDiagnostic([
                'confirmado' => false,
                'limpeza_confirmada' => $cleanupConfirmed,
                'residuo_possivel' => !$cleanupConfirmed,
                'resultado_limpeza' => $this->cleanupDiagnostic,
                'falha_publicacao' => is_array($failureDiagnostic)
                    ? $failureDiagnostic
                    : ['mensagem' => $failure->getMessage()],
            ]),
            0,
            $failure
        );
    }

    /** @param array<string, mixed> $diagnostic */
    private function encodeDiagnostic(array $diagnostic): string
    {
        return json_encode(
            $this->sanitizeDiagnosticValue($diagnostic),
            JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_INVALID_UTF8_SUBSTITUTE
                | JSON_THROW_ON_ERROR
        );
    }

    private function sanitizeDiagnosticValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->sanitizeDiagnosticValue($item), $value);
        }
        if (!is_string($value)) {
            return $value;
        }

        $value = str_ireplace(
            $this->diagnosticSensitiveValues(),
            '[DADO REDIGIDO]',
            $value
        );
        $value = (string) preg_replace(
            '/\bAuthorization\s*:\s*(?:Bearer\s+)?[^\s,;]+/iu',
            '[DADO REDIGIDO]',
            $value
        );
        $value = (string) preg_replace(
            '/\b(?:senha|password|token|credencial|credential)\b\s*[:=]\s*[^\s,;]+/iu',
            '[DADO REDIGIDO]',
            $value
        );

        return $value;
    }

    /** @return string[] */
    private function diagnosticSensitiveValues(): array
    {
        return array_values(array_filter([
            $this->config['login'] ?? null,
            $this->config['senha'] ?? null,
        ], static fn (mixed $value): bool => is_string($value) && $value !== ''));
    }

    private function assertTechnicalEventsAreSafe(SessaoPncpAutenticada $session): void
    {
        $events = array_map(
            static fn ($event): array => $event->paraEnvelope(),
            $this->logger->getEventos()
        );
        SafeContractReport::encode(['eventos' => $events], [
            $this->config['login'],
            $this->config['senha'],
            $this->config['cnpj'],
            $this->config['codigo_uasg'],
            $this->config['descricao_uasg'],
            $session->getTokenOpaco(),
        ]);
    }

    private static function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4)
            . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }
}
