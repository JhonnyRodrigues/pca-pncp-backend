<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84\Support;

use Psr\Log\NullLogger;
use Semae\Pca\Application\Commands\ComandoExcluirPca;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Enums\SituacaoAutenticacaoPncp;
use Semae\Pca\Application\Enums\SituacaoBuscaCandidatosPca;
use Semae\Pca\Application\Enums\SituacaoEscritaPncp;
use Semae\Pca\Application\Enums\SituacaoObservacaoPca;
use Semae\Pca\Application\Ports\AutenticadorPncpPort;
use Semae\Pca\Application\Ports\EscritorPncpPort;
use Semae\Pca\Application\Ports\LeitorPncpPort;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Composition\ComposicaoPncpPublicacaoPca;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Semae\Pca\Infrastructure\Pncp\Http\ExecutorCurlNativoPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteCurlPncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\TemporizadorSistemaPncp;
use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;
use Semae\Pca\Presentation\ViewModels\ResultadoUnidadeViewModel;

final class Sprint84PncpVerifier
{
    /** @param array<string, mixed> $configuration */
    private function __construct(
        private readonly AutenticadorPncpPort $authenticator,
        private readonly LeitorPncpPort $reader,
        private readonly EscritorPncpPort $writer,
        private readonly Cnpj $cnpj,
        private readonly array $configuration,
        private readonly ?Sprint84PncpTransportGuard $transportGuard = null
    ) {}

    /** @param array<string, mixed> $profile @param array<string, mixed> $configuration */
    public static function native(
        PerfilPublicacaoPca $typedProfile,
        array $profile,
        array $configuration,
        ?Sprint84PncpTransportGuard $transportGuard = null
    ): self {
        $transport = $transportGuard ?? new Sprint84PncpTransportGuard(
            new TransporteCurlPncp(
                $typedProfile->getConfiguracaoTransporte(),
                new ExecutorCurlNativoPncp()
            )
        );
        $composition = new ComposicaoPncpPublicacaoPca(
            $typedProfile,
            $transport,
            $typedProfile->getConfiguracaoLeitura(),
            new NullLogger(),
            new TemporizadorSistemaPncp()
        );

        return new self(
            $composition->getAutenticador(),
            $composition->getLeitor(),
            $composition->getEscritor(),
            new Cnpj((string) $profile['cnpj']),
            $configuration,
            $transport
        );
    }

    /** @param array<string, mixed> $configuration */
    public static function fromPorts(
        AutenticadorPncpPort $authenticator,
        LeitorPncpPort $reader,
        EscritorPncpPort $writer,
        Cnpj $cnpj,
        array $configuration
    ): self {
        return new self($authenticator, $reader, $writer, $cnpj, $configuration);
    }

    /** @return array<int, array<string, mixed>> */
    public function diagnosticoHttp(): array
    {
        return $this->transportGuard?->diagnostico() ?? [];
    }

    /** @param array<string, mixed> $checkpoint @return array<int, array<string, mixed>> */
    public function precheck(array $checkpoint): array
    {
        $session = $this->authenticate();
        $units = $this->unitsFromCheckpoint($checkpoint);
        $reports = [];

        foreach ($units as $unit) {
            $search = $this->reader->localizarCandidatos($session, $this->cnpj, $unit);
            if ($search->getSituacao() !== SituacaoBuscaCandidatosPca::CONCLUIDA) {
                $failure = $search->getFalha();
                throw new Sprint84GateException(
                    'PRECHECK_PNCP_BUSCA_INCONCLUSIVA',
                    'PRECHECK_PNCP',
                    'A busca PNCP inicial não foi conclusiva.',
                    [
                        'codigo_uasg' => $unit->getUasgExterna()->getCodigo(),
                        'situacao' => $search->getSituacao()->value,
                        'falha' => $failure === null ? null : [
                            'codigo' => $failure->getCodigo(),
                            'mensagem' => $failure->getMensagem(),
                        ],
                        'diagnostico_http' => $this->diagnosticoHttp(),
                    ]
                );
            }

            $candidates = $search->getCandidatos();
            if (count($candidates) > 1) {
                throw new Sprint84GateException(
                    'PRECHECK_PNCP_CANDIDATOS_AMBIGUOS',
                    'PRECHECK_PNCP',
                    'Mais de um PCA candidato foi encontrado para a unidade externa.',
                    [
                        'codigo_uasg' => $unit->getUasgExterna()->getCodigo(),
                        'candidatos' => array_map(
                            static fn ($candidate): array => [
                                'sequencial' => $candidate->getSequencialPca()?->getValor(),
                                'ids_demandas' => $candidate->getIdsDemandas(),
                            ],
                            $candidates
                        ),
                    ]
                );
            }

            $candidate = $candidates[0] ?? null;
            $expectedSequence = $this->expectedSequence($checkpoint, $unit);
            if (
                $candidate !== null
                && $expectedSequence !== null
                && $candidate->getSequencialPca()?->getValor() !== $expectedSequence
            ) {
                throw new Sprint84GateException(
                    'PRECHECK_PNCP_SEQUENCIAL_DIVERGENTE',
                    'PRECHECK_PNCP',
                    'O sequencial PNCP encontrado divergiu da projeção Oracle.',
                    [
                        'codigo_uasg' => $unit->getUasgExterna()->getCodigo(),
                        'sequencial_oracle' => $expectedSequence,
                        'sequencial_pncp' => $candidate->getSequencialPca()?->getValor(),
                    ]
                );
            }

            $reports[] = [
                'modo' => $unit->getModo()->value,
                'codigo_uasg' => $unit->getUasgExterna()->getCodigo(),
                'descricao_uasg' => $unit->getUasgExterna()->getDescricao(),
                'situacao' => $candidate === null ? 'AUSENTE' : 'ENCONTRADO',
                'sequencial_oracle' => $expectedSequence,
                'sequencial_pncp' => $candidate?->getSequencialPca()?->getValor(),
                'ids_demandas' => $candidate?->getIdsDemandas() ?? [],
            ];
        }

        return $reports;
    }

    /** @return array<int, array<string, mixed>> */
    public function verify(PublicarPcaViewModel $viewModel): array
    {
        $session = $this->authenticate();
        $observations = [];

        foreach ($viewModel->getResultadosUnitarios() as $result) {
            $unit = $this->unitFromViewModel($result);
            $sequenceValue = $result->getSequencialPca();
            if ($sequenceValue === null) {
                throw $this->failure(
                    'POSCHECK_PNCP_SEQUENCIAL_AUSENTE',
                    'O resultado unitário não informou o sequencial a observar.',
                    ['id_unidade_orcamentaria' => $result->getIdUnidadeOrcamentaria()]
                );
            }

            $sequence = new SequencialPca($sequenceValue);
            $observation = $this->reader->observarPcaConhecido(
                $session,
                $this->cnpj,
                $unit,
                $sequence
            );
            $observed = $observation->getPcaObservado();
            $failure = $observation->getFalha();
            $observations[] = [
                'id_unidade_orcamentaria' => $result->getIdUnidadeOrcamentaria(),
                'modo' => $result->getModo(),
                'codigo_uasg' => $result->getCodigoUasg(),
                'descricao_uasg' => $result->getDescricaoUasg(),
                'ano_execucao' => $result->getAnoExecucao(),
                'sequencial_solicitado' => $sequenceValue,
                'situacao' => $observation->getSituacao()->value,
                'pca_ativo' => $observed?->estaAtivo(),
                'sequencial_observado' => $observed?->getSequencialPca()?->getValor(),
                'ids_demandas' => $observed?->getIdsDemandas(),
                'falha' => $failure === null ? null : [
                    'codigo' => $failure->getCodigo(),
                    'mensagem' => $failure->getMensagem(),
                ],
            ];
        }

        return $observations;
    }

    /**
     * @param int[] $authorizedSequences
     * @param array<string, mixed> $checkpoint
     * @return array<int, array<string, mixed>>
     */
    public function cleanup(array $authorizedSequences, array $checkpoint): array
    {
        $session = $this->authenticate();
        $units = $this->unitsFromCheckpoint($checkpoint);
        $reports = [];

        foreach ($authorizedSequences as $authorized) {
            $sequence = new SequencialPca($authorized);
            $matches = [];

            foreach ($units as $unit) {
                $search = $this->reader->localizarCandidatos($session, $this->cnpj, $unit);
                if ($search->getSituacao() !== SituacaoBuscaCandidatosPca::CONCLUIDA) {
                    throw $this->failure(
                        'LIMPEZA_BUSCA_INCONCLUSIVA',
                        'A limpeza não conseguiu enumerar os candidatos PNCP.',
                        ['sequencial' => $authorized, 'situacao' => $search->getSituacao()->value]
                    );
                }

                foreach ($search->getCandidatos() as $candidate) {
                    if ($candidate->getSequencialPca()?->getValor() === $authorized) {
                        $matches[] = $unit;
                    }
                }
            }

            if (count($matches) > 1) {
                throw $this->failure(
                    'LIMPEZA_ALVO_AMBIGUO',
                    'O sequencial autorizado apareceu em mais de uma unidade externa.',
                    ['sequencial' => $authorized, 'quantidade_unidades' => count($matches)]
                );
            }
            if ($matches === []) {
                $confirmedAbsences = [];
                foreach ($units as $unit) {
                    $observation = $this->reader->observarPcaConhecido(
                        $session,
                        $this->cnpj,
                        $unit,
                        $sequence
                    );
                    $confirmedAbsences[] = [
                        'modo' => $unit->getModo()->value,
                        'codigo_uasg' => $unit->getUasgExterna()->getCodigo(),
                        'situacao' => $observation->getSituacao()->value,
                    ];
                    if ($observation->getSituacao() !== SituacaoObservacaoPca::AUSENCIA_DEFINITIVA) {
                        throw $this->failure(
                            'LIMPEZA_AUSENCIA_INCONCLUSIVA',
                            'O alvo não apareceu na busca, mas sua ausência definitiva não foi comprovada.',
                            [
                                'sequencial' => $authorized,
                                'codigo_uasg' => $unit->getUasgExterna()->getCodigo(),
                                'situacao' => $observation->getSituacao()->value,
                            ]
                        );
                    }
                }
                $reports[] = [
                    'sequencial' => $authorized,
                    'exclusao_executada' => false,
                    'ausencia_confirmada' => true,
                    'observacoes' => $confirmedAbsences,
                ];
                continue;
            }

            $unit = $matches[0];
            $exclusion = $this->writer->excluirPca(
                $session,
                new ComandoExcluirPca(
                    $this->cnpj,
                    $unit,
                    $sequence,
                    'Limpeza emergencial autorizada da Sprint 8.4'
                )
            );
            if ($exclusion->getSituacao() !== SituacaoEscritaPncp::ACEITA) {
                throw $this->failure(
                    'LIMPEZA_EXCLUSAO_NAO_CONFIRMADA',
                    'O PNCP não confirmou a exclusão do sequencial autorizado.',
                    ['sequencial' => $authorized, 'situacao' => $exclusion->getSituacao()->value]
                );
            }

            $observation = $this->reader->observarPcaConhecido(
                $session,
                $this->cnpj,
                $unit,
                $sequence
            );
            if ($observation->getSituacao() !== SituacaoObservacaoPca::AUSENCIA_DEFINITIVA) {
                throw $this->failure(
                    'LIMPEZA_AUSENCIA_NAO_CONFIRMADA',
                    'A leitura posterior não comprovou ausência definitiva.',
                    ['sequencial' => $authorized, 'situacao' => $observation->getSituacao()->value]
                );
            }

            $reports[] = [
                'sequencial' => $authorized,
                'modo' => $unit->getModo()->value,
                'codigo_uasg' => $unit->getUasgExterna()->getCodigo(),
                'exclusao_executada' => true,
                'ausencia_confirmada' => true,
            ];
        }

        return $reports;
    }

    private function authenticate(): SessaoPncp
    {
        $authentication = $this->authenticator->autenticar();
        $session = $authentication->getSessao();
        if (
            $authentication->getSituacao() !== SituacaoAutenticacaoPncp::AUTENTICADA
            || !$session instanceof SessaoPncp
        ) {
            throw $this->failure(
                'PNCP_AUTENTICACAO_FALHOU',
                'A autenticação PNCP do pós-check não foi confirmada.',
                ['situacao' => $authentication->getSituacao()->value]
            );
        }

        return $session;
    }

    /** @param array<string, mixed> $checkpoint */
    private function expectedSequence(
        array $checkpoint,
        UnidadeExternaPublicacao $unit
    ): ?int {
        $rows = $checkpoint['consultas']['projecao_operacional']['linhas'] ?? [];
        $sequences = [];
        foreach ($rows as $row) {
            if ((string) ($row['UASG_ADOTADA'] ?? '') !== $unit->getUasgExterna()->getCodigo()) {
                continue;
            }
            $value = $row['SEQUENCIAL_PUBLICACAO_PNCP'] ?? null;
            if ($value !== null) {
                $sequences[] = (int) $value;
            }
        }
        $sequences = array_values(array_unique($sequences, SORT_NUMERIC));
        if (count($sequences) > 1) {
            throw new Sprint84GateException(
                'PRECHECK_ORACLE_SEQUENCIAIS_DIVERGENTES',
                'PRECHECK_PNCP',
                'A projeção Oracle contém mais de um sequencial para a unidade externa.',
                [
                    'codigo_uasg' => $unit->getUasgExterna()->getCodigo(),
                    'sequenciais' => $sequences,
                ]
            );
        }

        return $sequences[0] ?? null;
    }

    private function unitFromViewModel(ResultadoUnidadeViewModel $result): UnidadeExternaPublicacao
    {
        $year = new AnoExecucao($result->getAnoExecucao());
        $uasg = new Uasg($result->getCodigoUasg(), $result->getDescricaoUasg());
        if ($result->getModo() === 'CONSOLIDADO') {
            return UnidadeExternaPublicacao::consolidada($year, $uasg);
        }

        $identity = $result->getIdUnidadeOrcamentaria();
        if ($identity === null || trim($identity) === '') {
            throw $this->failure(
                'POSCHECK_IDENTIDADE_UO_AUSENTE',
                'A unidade individual não informou sua identidade.',
                ['resultado' => $result->getSituacao()]
            );
        }
        $unit = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria($identity),
            new Lotacao(1, 'Lotação técnica do pós-check Sprint 8.4'),
            $uasg,
            'Unidade do pós-check Sprint 8.4'
        );

        return UnidadeExternaPublicacao::individual(new IdentidadePlano($unit, $year));
    }

    /** @param array<string, mixed> $checkpoint @return UnidadeExternaPublicacao[] */
    private function unitsFromCheckpoint(array $checkpoint): array
    {
        $queries = $checkpoint['consultas'] ?? null;
        if (!is_array($queries)) {
            throw $this->failure('LIMPEZA_CHECKPOINT_INVALIDO', 'O checkpoint Oracle não contém consultas.');
        }
        $year = new AnoExecucao((int) $this->configuration['ano']);

        if ($this->configuration['modo'] === 'CONSOLIDADO') {
            $rows = $queries['estrategia_corrente']['linhas'] ?? [];
            foreach ($rows as $row) {
                if (($row['VALOR_CONFIGURACAO'] ?? null) !== 'CONSOLIDADO') {
                    return [UnidadeExternaPublicacao::consolidada(
                        $year,
                        new Uasg(
                            (string) $row['VALOR_CONFIGURACAO'],
                            (string) ($row['DESCRICAO_CONFIGURACAO'] ?? 'UASG consolidadora Sprint 8.4')
                        )
                    )];
                }
            }
            throw $this->failure(
                'LIMPEZA_UASG_CONSOLIDADORA_AUSENTE',
                'O checkpoint não informou a UASG consolidadora.'
            );
        }

        $authorizedUnitIds = array_map(
            static fn (array $unit): int => (int) $unit['id_unidade_orcamentaria'],
            $this->configuration['unidades']
        );
        $rows = $queries['unidades_orcamentarias']['linhas'] ?? [];
        $units = [];
        foreach ($rows as $row) {
            $physicalId = (int) ($row['CHAVE_UO_PLANO'] ?? 0);
            if (!in_array($physicalId, $authorizedUnitIds, true)) {
                continue;
            }
            $domainUnit = new UnidadeOrcamentaria(
                new IdentidadeUnidadeOrcamentaria((string) $row['IDENTIDADE_UO']),
                new Lotacao((int) $row['ID_LOTACAO'], (string) $row['DESCRICAO_LOTACAO']),
                new Uasg((string) $row['CODIGO_UASG'], (string) $row['DESCRICAO_UASG']),
                (string) $row['DESCRICAO_UO']
            );
            $units[] = UnidadeExternaPublicacao::individual(new IdentidadePlano($domainUnit, $year));
        }

        if (count($units) !== 2) {
            throw $this->failure(
                'LIMPEZA_UNIDADES_DIVERGENTES',
                'O checkpoint não materializou exatamente as duas UOs autorizadas.',
                ['quantidade' => count($units), 'ids_autorizados' => $authorizedUnitIds]
            );
        }

        return $units;
    }

    /** @param array<string, mixed> $details */
    private function failure(string $code, string $message, array $details = []): Sprint84GateException
    {
        $phase = str_starts_with($code, 'LIMPEZA_')
            ? 'LIMPEZA_EMERGENCIAL'
            : 'POSCHECK_PNCP';

        return new Sprint84GateException($code, $phase, $message, $details);
    }
}
