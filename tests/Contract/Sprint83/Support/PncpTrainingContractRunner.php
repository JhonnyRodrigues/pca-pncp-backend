<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint83\Support;

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
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Composition\ComposicaoPncpPublicacaoPca;
use Semae\Pca\Composition\PerfilPublicacaoPca;
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
use Semae\Pca\Infrastructure\Pncp\Autenticacao\SessaoPncpAutenticada;
use Semae\Pca\Infrastructure\Pncp\Http\ExecutorCurlNativoPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteCurlPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\ConfiguracaoLeituraPncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\TemporizadorSistemaPncp;
use Throwable;

final class PncpTrainingContractRunner
{
    private readonly Cnpj $cnpj;
    private readonly UnidadeExternaPublicacao $unidade;
    private readonly AcumuladorEventosTecnicos $logger;
    private readonly PncpTrainingTransport $transport;
    private readonly ComposicaoPncpPublicacaoPca $composition;

    /** @var string[] */
    private readonly array $sensitiveFragments;

    /** @var string[] */
    private readonly array $sensitiveExactStrings;

    /** @param array<string, mixed> $config */
    private function __construct(
        private readonly array $config,
        array $profile,
        TransporteHttpPncp $transport
    ) {
        $typedProfile = new PerfilPublicacaoPca($profile);
        $this->cnpj = new Cnpj($profile['cnpj']);
        $this->sensitiveFragments = array_values(array_filter([
            $profile['cnpj'],
            $profile['pncp']['login'],
            $profile['pncp']['senha'],
            $config['server_name'],
            $config['massa']['descricao_uasg'],
        ], static fn (mixed $value): bool => is_string($value) && $value !== ''));
        $this->sensitiveExactStrings = [$config['massa']['codigo_uasg']];
        $this->transport = new PncpTrainingTransport($transport);
        $this->logger = new AcumuladorEventosTecnicos(
            self::uuidV4(),
            'PNCP_TREINAMENTO',
            'CONTRATOS_SPRINT_83',
            null,
            new RelogioSistemaEventosTecnicos()
        );
        $readConfig = $typedProfile->getConfiguracaoLeitura();
        $this->composition = new ComposicaoPncpPublicacaoPca(
            $typedProfile,
            $this->transport,
            new ConfiguracaoLeituraPncp(
                1,
                $readConfig->getProfundidadeMaximaJson(),
                $readConfig->getLimiteMaximoPaginas(),
                $readConfig->getEsperaRetryPadraoMs(),
                $readConfig->getTetoRetryAfterMs()
            ),
            $this->logger,
            new TemporizadorSistemaPncp()
        );
        $year = new AnoExecucao($config['massa']['ano']);
        $uasg = new Uasg(
            $config['massa']['codigo_uasg'],
            $config['massa']['descricao_uasg']
        );
        $unit = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria('SPRINT-83'),
            new Lotacao(1, 'Massa dedicada Sprint 8.3'),
            $uasg,
            'Massa dedicada Sprint 8.3'
        );
        $this->unidade = UnidadeExternaPublicacao::individual(
            new IdentidadePlano($unit, $year)
        );
    }

    public static function fromEnvironment(): self
    {
        $config = Sprint83Config::loadFromEnvironment();
        $profile = Sprint83Config::loadHomologationProfileArray(
            $config,
            $config['server_name']
        );
        $typedProfile = new PerfilPublicacaoPca($profile);

        return new self(
            $config,
            $profile,
            new TransporteCurlPncp(
                $typedProfile->getConfiguracaoTransporte(),
                new ExecutorCurlNativoPncp()
            )
        );
    }

    /** @param array<string, mixed> $config @param array<string, mixed> $profile */
    public static function fromComponents(
        array $config,
        array $profile,
        TransporteHttpPncp $transport
    ): self {
        return new self($config, $profile, $transport);
    }

    /** @return array<string, mixed> */
    public function runNominal(): array
    {
        $this->assertNormalExecutionMayStart();
        $session = $this->authenticate();
        $this->requireNoCandidate($session);
        $demands = $this->demands();
        $tracked = null;
        $receivedAndDeletedSequence = null;
        $writesStarted = false;

        try {
            $writesStarted = true;
            $publication = $this->composition->getEscritor()->publicar(
                $session,
                new ComandoPublicarPca($this->cnpj, $this->unidade, [$demands[0], $demands[1]])
            );
            if (
                $publication->getSituacao() !== SituacaoEscritaPncp::ACEITA
                || $publication->getSequencialPca() === null
            ) {
                throw new RuntimeException('O gate nominal não confirmou a publicação.');
            }
            $tracked = $publication->getSequencialPca();
            $receivedAndDeletedSequence = $tracked->getValor();
            $this->requireObservedIds($session, $tracked, [$demands[0], $demands[1]]);

            $insertion = $this->composition->getEscritor()->inserirItens(
                $session,
                new ComandoInserirItensPca($this->cnpj, $this->unidade, $tracked, [$demands[2]])
            );
            if ($insertion->getSituacao() !== SituacaoEscritaPncp::ACEITA) {
                throw new RuntimeException('O gate nominal não confirmou a inclusão.');
            }
            $this->requireObservedIds($session, $tracked, $demands);

            $exclusion = $this->composition->getEscritor()->excluirItens(
                $session,
                new ComandoExcluirItensPca(
                    $this->cnpj,
                    $this->unidade,
                    $tracked,
                    [$demands[1]->getIdDemanda()],
                    'Validação contratual Sprint 8.3'
                )
            );
            if ($exclusion->getSituacao() !== SituacaoEscritaPncp::ACEITA) {
                throw new RuntimeException('O gate nominal não confirmou a exclusão de item.');
            }
            $this->requireObservedIds($session, $tracked, [$demands[0], $demands[2]]);
            $this->deleteAndRequireAbsence($session, $tracked);
            $tracked = null;
        } catch (Throwable $failure) {
            $cleanup = $writesStarted && $this->bestEffortCleanup($session, $tracked);
            throw new RuntimeException(
                $cleanup
                    ? 'O gate nominal falhou depois de confirmar a limpeza.'
                    : 'O gate nominal falhou e pode ter deixado resíduo.',
                0,
                $failure
            );
        }

        $paginated = $this->transport->percorreuPaginaPosteriorAUm();
        $exactDecimals = $this->transport->publicacaoContemLexemas([
            '"quantidade":3.0000',
            '"valorTotal":10.0000',
            '"valorTotal":0.5000',
        ]);
        if (!$paginated || !$exactDecimals) {
            throw new RuntimeException('O gate nominal não comprovou paginação e decimais exatos.');
        }

        return $this->report('NOMINAL', [
            'paginacao_multipla' => true,
            'decimais_exatos' => true,
            'referencia_externa_positiva' => true,
            'sequencial_pca_recebido_excluido' => $receivedAndDeletedSequence,
            'identificadores_preservados' => true,
            'limpeza_confirmada' => true,
        ]);
    }

    /** @return array<string, mixed> */
    public function runUncertainWrite(): array
    {
        $this->assertNormalExecutionMayStart();
        $session = $this->authenticate();
        $this->requireNoCandidate($session);
        $demands = $this->demands();
        $tracked = null;
        $this->transport->perderProximaRespostaDePublicacao();

        try {
            $publication = $this->composition->getEscritor()->publicar(
                $session,
                new ComandoPublicarPca($this->cnpj, $this->unidade, [$demands[0], $demands[1]])
            );
            if ($publication->getSituacao() !== SituacaoEscritaPncp::RESULTADO_INCERTO) {
                throw new RuntimeException('O gate não classificou a resposta descartada como incerta.');
            }
            if ($this->transport->contar('POST', '/pca') !== 1) {
                throw new RuntimeException('O gate repetiu uma publicação de resultado incerto.');
            }

            $search = $this->composition->getLeitor()->localizarCandidatos(
                $session,
                $this->cnpj,
                $this->unidade
            );
            if (
                $search->getSituacao() !== SituacaoBuscaCandidatosPca::CONCLUIDA
                || count($search->getCandidatos()) !== 1
                || $search->getCandidatos()[0]->getSequencialPca() === null
            ) {
                throw new RuntimeException('O gate não localizou unicamente o efeito da escrita incerta.');
            }
            $candidate = $search->getCandidatos()[0];
            $tracked = $candidate->getSequencialPca();
            $this->requireIds($candidate->getIdsDemandas(), [$demands[0], $demands[1]]);
            $this->deleteAndRequireAbsence($session, $tracked);
            $tracked = null;
        } catch (Throwable $failure) {
            $cleanup = $this->bestEffortCleanup($session, $tracked);
            throw new RuntimeException(
                $cleanup
                    ? 'O gate incerto falhou depois de confirmar a limpeza.'
                    : 'O gate incerto falhou e pode ter deixado resíduo.',
                0,
                $failure
            );
        }

        return $this->report('ESCRITA_INCERTA', [
            'resposta_descartada' => true,
            'sem_repeticao' => $this->transport->contar('POST', '/pca') === 1,
            'efeito_localizado_por_leitura' => true,
            'identificadores_preservados' => true,
            'limpeza_confirmada' => true,
        ]);
    }

    /** @return array<string, mixed> */
    public function cleanupOwnedResidue(): array
    {
        $authorized = $this->config['massa']['residuo_sequencial_autorizado'];
        if (!is_int($authorized) || $authorized < 1) {
            throw new RuntimeException('A limpeza exige resíduo explicitamente autorizado.');
        }

        $session = $this->authenticate();
        $sequence = new SequencialPca($authorized);
        $observation = $this->composition->getLeitor()->observarPcaConhecido(
            $session,
            $this->cnpj,
            $this->unidade,
            $sequence
        );
        if ($observation->getSituacao() === SituacaoObservacaoPca::AUSENCIA_DEFINITIVA) {
            return $this->report('LIMPEZA_ISOLADA', [
                'publicacao_executada' => false,
                'exclusao_executada' => false,
                'limpeza_confirmada' => true,
            ]);
        }
        if ($observation->getSituacao() !== SituacaoObservacaoPca::ENCONTRADO) {
            throw new RuntimeException('A limpeza recusou alvo não confirmado da massa dedicada.');
        }

        $this->deleteAndRequireAbsence($session, $sequence);

        return $this->report('LIMPEZA_ISOLADA', [
            'publicacao_executada' => false,
            'exclusao_executada' => true,
            'limpeza_confirmada' => true,
        ]);
    }

    private function assertNormalExecutionMayStart(): void
    {
        if ($this->config['massa']['residuo_sequencial_autorizado'] !== null) {
            throw new RuntimeException(
                'O gate recusou publicar enquanto houver resíduo explicitamente pendente.'
            );
        }
    }

    private function authenticate(): SessaoPncpAutenticada
    {
        $result = $this->composition->getAutenticador()->autenticar();
        if (
            $result->getSituacao() !== SituacaoAutenticacaoPncp::AUTENTICADA
            || !$result->getSessao() instanceof SessaoPncpAutenticada
        ) {
            throw new RuntimeException('O gate PNCP falhou na autenticação segura.');
        }

        return $result->getSessao();
    }

    private function requireNoCandidate(SessaoPncpAutenticada $session): void
    {
        $search = $this->composition->getLeitor()->localizarCandidatos(
            $session,
            $this->cnpj,
            $this->unidade
        );
        if (
            $search->getSituacao() !== SituacaoBuscaCandidatosPca::CONCLUIDA
            || $search->getCandidatos() !== []
        ) {
            throw new RuntimeException('O gate recusou candidato preexistente não autorizado.');
        }
    }

    /** @param Demanda[] $expected */
    private function requireObservedIds(
        SessaoPncpAutenticada $session,
        SequencialPca $sequence,
        array $expected
    ): void {
        $observation = $this->composition->getLeitor()->observarPcaConhecido(
            $session,
            $this->cnpj,
            $this->unidade,
            $sequence
        );
        if ($observation->getSituacao() !== SituacaoObservacaoPca::ENCONTRADO) {
            throw new RuntimeException('O gate não encontrou o PCA esperado.');
        }

        $this->requireIds($observation->getPcaObservado()?->getIdsDemandas() ?? [], $expected);
    }

    /** @param int[] $actual @param Demanda[] $expected */
    private function requireIds(array $actual, array $expected): void
    {
        $expectedIds = array_map(
            static fn (Demanda $demand): int => $demand->getIdDemanda(),
            $expected
        );
        sort($actual, SORT_NUMERIC);
        sort($expectedIds, SORT_NUMERIC);
        if ($actual !== $expectedIds) {
            throw new RuntimeException('O gate observou identificadores divergentes.');
        }
    }

    private function deleteAndRequireAbsence(
        SessaoPncpAutenticada $session,
        SequencialPca $sequence
    ): void {
        $result = $this->composition->getEscritor()->excluirPca(
            $session,
            new ComandoExcluirPca(
                $this->cnpj,
                $this->unidade,
                $sequence,
                'Limpeza contratual Sprint 8.3'
            )
        );
        if ($result->getSituacao() !== SituacaoEscritaPncp::ACEITA) {
            throw new RuntimeException('O gate não confirmou a exclusão integral.');
        }

        $observation = $this->composition->getLeitor()->observarPcaConhecido(
            $session,
            $this->cnpj,
            $this->unidade,
            $sequence
        );
        if ($observation->getSituacao() !== SituacaoObservacaoPca::AUSENCIA_DEFINITIVA) {
            throw new RuntimeException('O gate não comprovou ausência definitiva.');
        }
    }

    private function bestEffortCleanup(
        SessaoPncpAutenticada $session,
        ?SequencialPca $sequence
    ): bool {
        try {
            if ($sequence !== null) {
                $this->deleteAndRequireAbsence($session, $sequence);

                return true;
            }

            $search = $this->composition->getLeitor()->localizarCandidatos(
                $session,
                $this->cnpj,
                $this->unidade
            );
            if ($search->getSituacao() !== SituacaoBuscaCandidatosPca::CONCLUIDA) {
                return false;
            }
            if ($search->getCandidatos() === []) {
                return true;
            }
            if (
                count($search->getCandidatos()) !== 1
                || $search->getCandidatos()[0]->getSequencialPca() === null
            ) {
                return false;
            }
            $this->deleteAndRequireAbsence(
                $session,
                $search->getCandidatos()[0]->getSequencialPca()
            );

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /** @return Demanda[] */
    private function demands(): array
    {
        $identity = $this->unidade->getIdentidadePlano();
        if ($identity === null) {
            throw new RuntimeException('A massa PNCP não possui identidade individual.');
        }
        $mass = $this->config['massa'];
        $ids = [
            $mass['id_demanda_publicacao_1'],
            $mass['id_demanda_publicacao_2'],
            $mass['id_demanda_inclusao'],
        ];
        $totals = ['10.0000', '0.5000', '7.2500'];
        $item = $mass['item'];

        return array_map(
            static fn (int $id, int $index): Demanda => new Demanda(
                $id,
                SituacaoDemanda::HOMOLOGADA,
                1,
                $identity->getUnidadeOrcamentaria(),
                $identity->getAnoExecucao(),
                new DateTimeImmutable($mass['data_desejada']),
                $item['categoria'],
                'PCA-S83 contrato de treinamento ' . ($index + 1),
                $item['unidade_medida'],
                3,
                new DecimalExato($totals[$index]),
                new DecimalExato($totals[$index]),
                $item['catalogo'],
                $item['categoria'],
                $item['codigo_item'],
                $item['grupo'],
                $item['descricao_grupo'],
                $item['origem']
            ),
            $ids,
            array_keys($ids)
        );
    }

    /** @param array<string, mixed> $checks @return array<string, mixed> */
    private function report(string $mode, array $checks): array
    {
        $this->assertTechnicalEventsAreSafe();
        if ($this->transport->contar('POST', '/usuarios/login') !== 1) {
            throw new RuntimeException('O gate não comprovou autenticação única.');
        }

        return array_merge([
            'sprint' => '8.3',
            'gate' => 'PNCP_TREINAMENTO_COMPOSICAO',
            'modo' => $mode,
            'confirmado' => true,
            'manual_pncp_versao' => Sprint83Config::MANUAL_PNCP_VERSAO,
            'manual_pncp_data' => Sprint83Config::MANUAL_PNCP_DATA,
            'openapi_versao' => Sprint83Config::OPENAPI_VERSAO,
            'contrato_revalidado_em' => Sprint83Config::CONTRATO_REVALIDADO_EM,
            'executado_em_utc' => gmdate('c'),
            'perfil_homologacao' => true,
            'autenticacao_unica' => true,
            'eventos_sanitizados' => true,
            'quantidade_eventos' => count($this->logger->getEventos()),
        ], $checks);
    }

    private function assertTechnicalEventsAreSafe(): void
    {
        $envelopes = array_map(
            static fn ($event): array => $event->paraEnvelope(),
            $this->logger->getEventos()
        );
        $json = json_encode($envelopes, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        foreach ($this->sensitiveFragments as $sensitive) {
            if (str_contains($json, $sensitive)) {
                throw new RuntimeException('Os eventos técnicos continham valor sensível.');
            }
        }

        foreach ($this->sensitiveExactStrings as $sensitive) {
            $encoded = json_encode($sensitive, JSON_THROW_ON_ERROR);

            if (str_contains($json, $encoded)) {
                throw new RuntimeException('Os eventos técnicos continham valor sensível.');
            }
        }
    }

    private static function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-'
            . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-'
            . substr($hex, 20, 12);
    }
}
