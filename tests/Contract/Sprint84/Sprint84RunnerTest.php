<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;
use Semae\Pca\Presentation\ViewModels\ResultadoUnidadeViewModel;
use Tests\Contract\Sprint84\Support\OracleCheckpointExecutor;
use Tests\Contract\Sprint84\Support\Sprint84AdodbGuard;
use Tests\Contract\Sprint84\Support\Sprint84GateException;
use Tests\Contract\Sprint84\Support\Sprint84Runner;
use Tests\Support\PerfilPublicacaoPcaFixture;

final class Sprint84RunnerTest extends TestCase
{
    public function testPrecheckDeveExecutarSomenteOracleEExibirDiagnosticoCompleto(): void
    {
        $chamadasPublicador = 0;
        $chamadasPncp = 0;
        $runner = $this->runner(
            'PRECHECK',
            null,
            static function () use (&$chamadasPublicador): never {
                $chamadasPublicador++;
                throw new \LogicException('Publicador não deveria ser chamado.');
            },
            null,
            [],
            null,
            static function () use (&$chamadasPncp): array {
                $chamadasPncp++;

                return [
                    ['situacao' => 'AUSENTE'],
                    ['situacao' => 'AUSENTE'],
                ];
            }
        );

        $report = $runner->run();

        $this->assertSame('PRECHECK_CONFIRMADO', $report['resultado']);
        $this->assertSame(0, $chamadasPublicador);
        $this->assertSame(1, $chamadasPncp);
        $this->assertCount(2, $report['precheck_pncp']);
        $this->assertSame('SELECT ID FROM CHECKPOINT ORDER BY ID', $report['precheck_oracle']['consultas']['estado']['sql']);
        $this->assertSame([['ID' => 7]], $report['precheck_oracle']['consultas']['estado']['linhas']);
        $this->assertArrayHasKey('sha256_relatorio', $report);
        $this->assertSame('[PROTEGIDO]', $report['perfil']['pncp']['senha']);
    }

    public function testEtapaRealDeveRepetirPrecheckAntesDoFluxoEExecutarPosVerificacoes(): void
    {
        $base = $this->runner('PRECHECK')->run();
        $hash = $base['precheck_oracle']['sha256'];
        $ordem = [];
        $runner = $this->runner(
            'PUBLICAR',
            $hash,
            static function () use (&$ordem): PublicarPcaViewModel {
                $ordem[] = 'publicador';

                return new PublicarPcaViewModel(
                    'CONCLUIDA',
                    [
                        new ResultadoUnidadeViewModel(
                            'UO-101',
                            'INDIVIDUAL',
                            2027,
                            '928341',
                            'UASG 101',
                            'CONCLUIDO',
                            'PUBLICAR',
                            71,
                            1,
                            0,
                            null,
                            null
                        ),
                        new ResultadoUnidadeViewModel(
                            'UO-202',
                            'INDIVIDUAL',
                            2027,
                            '928342',
                            'UASG 202',
                            'CONCLUIDO',
                            'PUBLICAR',
                            72,
                            1,
                            0,
                            null,
                            null
                        ),
                    ],
                    null,
                    null,
                    2,
                    2,
                    0,
                    '00000000-0000-4000-8000-000000000084'
                );
            },
            static function (PublicarPcaViewModel $viewModel) use (&$ordem): array {
                $ordem[] = 'pncp_pos';

                return [
                    [
                        'situacao' => 'ENCONTRADO',
                        'sequencial_solicitado' => 71,
                        'sequencial_observado' => 71,
                        'codigo_uasg' => '928341',
                        'ids_demandas' => [1011],
                    ],
                    [
                        'situacao' => 'ENCONTRADO',
                        'sequencial_solicitado' => 72,
                        'sequencial_observado' => 72,
                        'codigo_uasg' => '928342',
                        'ids_demandas' => [2021],
                    ],
                ];
            },
            [],
            null,
            static function () use (&$ordem): array {
                $ordem[] = 'pncp_pre';

                return [
                    ['situacao' => 'AUSENTE'],
                    ['situacao' => 'AUSENTE'],
                ];
            }
        );

        $report = $runner->run();

        $this->assertSame(['pncp_pre', 'publicador', 'pncp_pos'], $ordem);
        $this->assertSame('FLUXO_CONFIRMADO', $report['resultado']);
        $this->assertSame('PUBLICAR', $report['view_model']['resultados_unitarios'][0]['decisao']);
        $this->assertSame(71, $report['observacoes_pncp_pos_fluxo'][0]['sequencial_observado']);
        $this->assertSame(4, count($report['diagnostico_adodb']));
        $this->assertArrayHasKey('poscheck_oracle', $report);
    }

    public function testNenhumaAcaoDeveConfirmarImutabilidadeOraclePncpESemEscritaExterna(): void
    {
        $base = $this->runner('PRECHECK')->run();
        $hash = $base['precheck_oracle']['sha256'];
        $runner = $this->runner(
            'NENHUMA_ACAO',
            $hash,
            fn (): PublicarPcaViewModel => $this->viewModelNenhumaAcao(),
            static fn (): array => [
                [
                    'situacao' => 'ENCONTRADO',
                    'sequencial_solicitado' => 71,
                    'sequencial_observado' => 71,
                    'codigo_uasg' => '928341',
                    'ids_demandas' => [1011],
                ],
                [
                    'situacao' => 'ENCONTRADO',
                    'sequencial_solicitado' => 72,
                    'sequencial_observado' => 72,
                    'codigo_uasg' => '928342',
                    'ids_demandas' => [2021],
                ],
            ],
            [],
            null,
            static fn (): array => [
                [
                    'situacao' => 'ENCONTRADO',
                    'codigo_uasg' => '928341',
                    'sequencial_pncp' => 71,
                    'ids_demandas' => [1011],
                ],
                [
                    'situacao' => 'ENCONTRADO',
                    'codigo_uasg' => '928342',
                    'sequencial_pncp' => 72,
                    'ids_demandas' => [2021],
                ],
            ],
            static fn (): array => [
                ['requisicao' => ['metodo' => 'POST', 'caminho' => '/v1/usuarios/login']],
                ['requisicao' => ['metodo' => 'GET', 'caminho' => '/v1/orgaos/123/pca/2027']],
            ]
        );

        $report = $runner->run();

        $this->assertSame('FLUXO_CONFIRMADO', $report['resultado']);
        $this->assertSame(
            $report['precheck_oracle']['sha256'],
            $report['poscheck_oracle']['sha256']
        );
        $this->assertSame(
            ['SEM_ALTERACAO', 'SEM_ALTERACAO'],
            array_column($report['view_model']['resultados_unitarios'], 'situacao')
        );
    }

    public function testNenhumaAcaoDeveRecusarEscritaPncpMesmoComResultadoConvergente(): void
    {
        $base = $this->runner('PRECHECK')->run();
        $hash = $base['precheck_oracle']['sha256'];
        $observacoes = [
            [
                'situacao' => 'ENCONTRADO',
                'codigo_uasg' => '928341',
                'sequencial_pncp' => 71,
                'ids_demandas' => [1011],
            ],
            [
                'situacao' => 'ENCONTRADO',
                'codigo_uasg' => '928342',
                'sequencial_pncp' => 72,
                'ids_demandas' => [2021],
            ],
        ];
        $runner = $this->runner(
            'NENHUMA_ACAO',
            $hash,
            fn (): PublicarPcaViewModel => $this->viewModelNenhumaAcao(),
            static fn (): array => [
                [
                    'situacao' => 'ENCONTRADO', 'codigo_uasg' => '928341',
                    'sequencial_solicitado' => 71, 'sequencial_observado' => 71,
                    'ids_demandas' => [1011],
                ],
                [
                    'situacao' => 'ENCONTRADO', 'codigo_uasg' => '928342',
                    'sequencial_solicitado' => 72, 'sequencial_observado' => 72,
                    'ids_demandas' => [2021],
                ],
            ],
            [],
            null,
            static fn (): array => $observacoes,
            static fn (): array => [[
                'requisicao' => [
                    'metodo' => 'POST',
                    'caminho' => '/v1/orgaos/12345678000195/pca',
                ],
            ]]
        );

        $this->expectException(Sprint84GateException::class);
        $this->expectExceptionMessage('escrita PNCP');
        $runner->run();
    }

    public function testNenhumaAcaoDeveRecusarSituacaoUnitariaConcluido(): void
    {
        $runner = $this->runnerNenhumaAcao(
            fn (): PublicarPcaViewModel => $this->viewModelNenhumaAcao('CONCLUIDO')
        );

        $this->expectException(Sprint84GateException::class);
        $this->expectExceptionMessage('situação unitária');
        $runner->run();
    }

    public function testNenhumaAcaoDeveRecusarMudancaNosItensPncp(): void
    {
        $runner = $this->runnerNenhumaAcao(null, [[1011], [9999]]);

        $this->expectException(Sprint84GateException::class);
        $this->expectExceptionMessage('observações PNCP');
        $runner->run();
    }

    public function testNenhumaAcaoDeveRecusarMudancaNoSnapshotOracle(): void
    {
        $runner = $this->runnerNenhumaAcao(null, null, [], new AdodbMutavelRunnerSprint84());

        $this->expectException(Sprint84GateException::class);
        $this->expectExceptionMessage('snapshot Oracle');
        $runner->run();
    }

    public function testNenhumaAcaoDeveAceitarSomentePropagacaoDosParesNulosNasTresConsultas(): void
    {
        $report = $this->runnerNenhumaAcao(null, null, [], new AdodbProjecaoRunnerSprint84())->run();

        $this->assertSame('FLUXO_CONFIRMADO', $report['resultado']);
        $this->assertNotSame($report['precheck_oracle']['sha256'], $report['poscheck_oracle']['sha256']);
        $this->assertCount(1, $report['projecao_operacional_completada']['projecao_operacional']);
        $this->assertCount(1, $report['projecao_operacional_completada']['todas_versoes']);
        $this->assertCount(1, $report['projecao_operacional_completada']['versoes_vigentes']);
    }

    public function testGateDeveRecusarProjecaoAindaPendente(): void
    {
        $this->expectException(Sprint84GateException::class);
        $this->expectExceptionMessage('projeção operacional');
        $this->runnerNenhumaAcao(null, null, [], new AdodbProjecaoRunnerSprint84(false))->run();
    }

    public function testPropagacaoNaoDevePermitirAlteracaoDaConfirmacao(): void
    {
        $this->expectException(Sprint84GateException::class);
        $this->expectExceptionMessage('snapshot Oracle');
        $this->runnerNenhumaAcao(null, null, [], new AdodbProjecaoRunnerSprint84(true, true))->run();
    }

    public function testHashDivergenteDeveBloquearAntesDePublicadorEPncp(): void
    {
        $chamadas = 0;
        $runner = $this->runner(
            'RETIFICAR',
            str_repeat('a', 64),
            static function () use (&$chamadas): never {
                $chamadas++;
                throw new \LogicException('I/O externo proibido.');
            }
        );

        try {
            $runner->run();
            $this->fail('Hash divergente deveria bloquear o fluxo.');
        } catch (Sprint84GateException $failure) {
            $this->assertSame('PRECHECK_HASH_DIVERGENTE', $failure->getErrorCode());
            $this->assertSame('PRECHECK_ORACLE', $failure->getPhase());
            $this->assertSame(0, $chamadas);
        }
    }

    public function testFalhaPncpDevePreservarCheckpointOracleIntegralNoContexto(): void
    {
        $runner = $this->runner(
            'PRECHECK',
            null,
            null,
            null,
            [],
            null,
            static fn (): never => throw new Sprint84GateException(
                'PRECHECK_PNCP_BUSCA_INCONCLUSIVA',
                'PRECHECK_PNCP',
                'Falha PNCP de teste.'
            ),
            static fn (): array => [[
                'requisicao' => ['caminho' => '/v1/orgaos/123/pca/2027'],
                'resposta' => ['status_http' => 404, 'corpo' => '{"mensagem":"ausente"}'],
            ]]
        );

        try {
            $runner->run();
            $this->fail('Falha PNCP deveria interromper o gate.');
        } catch (Sprint84GateException) {
            $context = $runner->failureContext();
            $this->assertSame(
                [['ID' => 7]],
                $context['precheck_oracle']['consultas']['estado']['linhas']
            );
            $this->assertSame(64, strlen($context['precheck_oracle']['sha256']));
            $this->assertSame(
                404,
                $context['diagnostico_pncp_http'][0]['resposta']['status_http']
            );
        }
    }

    public function testLimpezaDeveSerRecusadaSemSequencialAutorizado(): void
    {
        $runner = $this->runner('PRECHECK');

        $this->expectException(Sprint84GateException::class);
        $this->expectExceptionMessage('sequencial');
        $runner->cleanupEmergency();
    }

    public function testLimpezaAutorizadaDeveUsarSequencialExatoECheckpointAtual(): void
    {
        $recebido = [];
        $runner = $this->runner(
            'PRECHECK',
            null,
            null,
            null,
            [71],
            static function (array $sequences, array $checkpoint) use (&$recebido): array {
                $recebido = [$sequences, $checkpoint['sha256']];

                return [[
                    'sequencial' => 71,
                    'exclusao_executada' => true,
                    'ausencia_confirmada' => true,
                ]];
            }
        );

        $report = $runner->cleanupEmergency();

        $this->assertSame('LIMPEZA_CONFIRMADA', $report['resultado']);
        $this->assertSame([71], $report['sequenciais_autorizados']);
        $this->assertSame([71], $recebido[0]);
        $this->assertSame($report['checkpoint_oracle']['sha256'], $recebido[1]);
        $this->assertTrue($report['detalhes'][0]['ausencia_confirmada']);
        $this->assertArrayHasKey('sha256_relatorio', $report);
    }

    private function runner(
        string $stage,
        ?string $hash = null,
        ?callable $publisher = null,
        ?callable $pncpVerifier = null,
        array $cleanupSequences = [],
        ?callable $cleanup = null,
        ?callable $pncpPreVerifier = null,
        ?callable $pncpDiagnostics = null,
        ?object $adodbInterno = null
    ): Sprint84Runner {
        $guard = new Sprint84AdodbGuard($adodbInterno ?? new AdodbRunnerSprint84());
        $queries = [
            'estado' => [
                'sql' => 'SELECT ID FROM CHECKPOINT ORDER BY ID',
                'binds' => [],
                'columns' => ['ID'],
                'expected_cardinality' => ['exact' => 1],
            ],
            'projecao_operacional' => [
                'sql' => 'SELECT PROJECAO FROM CHECKPOINT', 'binds' => [],
                'columns' => ['FK_UNIDADE_ORCAMENTARIA', 'VERSAO', 'ESTRATEGIA_ADOTADA_PUBLICACAO',
                    'UASG_ADOTADA', 'ESTADO_PUBLICACAO', 'SEQUENCIAL_PUBLICACAO_PNCP'],
                'expected_cardinality' => ['exact' => 2],
            ],
        ];
        if ($adodbInterno instanceof AdodbProjecaoRunnerSprint84) {
            $queries['projecao_operacional']['expected_cardinality'] = ['exact' => 3];
            foreach (['todas_versoes', 'versoes_vigentes'] as $name) {
                $queries[$name] = $queries['projecao_operacional'];
                $queries[$name]['sql'] = 'SELECT ' . strtoupper($name) . ' FROM CHECKPOINT';
                $queries[$name]['columns'][] = 'DATA_PUBLICACAO';
            }
            $queries['versoes_vigentes']['expected_cardinality'] = ['exact' => 2];
        }
        $config = [
            'allow_oracle_writes' => true,
            'allow_pncp_writes' => true,
            'server_name' => 'pca.homologacao.test',
            'modo' => 'INDIVIDUAL',
            'ano' => 2027,
            'etapa_autorizada' => $stage,
            'unidades' => [
                [
                    'id_unidade_orcamentaria' => 101,
                    'quantidade_fotografia' => 1,
                    'inclusoes_retificacao' => $stage === 'RETIFICAR' ? 1 : 0,
                    'exclusoes_retificacao' => $stage === 'RETIFICAR' ? 1 : 0,
                ],
                [
                    'id_unidade_orcamentaria' => 202,
                    'quantidade_fotografia' => 1,
                    'inclusoes_retificacao' => $stage === 'RETIFICAR' ? 1 : 0,
                    'exclusoes_retificacao' => $stage === 'RETIFICAR' ? 1 : 0,
                ],
            ],
            'oracle_checkpoint_sha256' => $hash,
            'estado_pncp_esperado' => $stage === 'PUBLICAR' || $stage === 'PRECHECK' ? 'AUSENTE' : 'ENCONTRADO',
            'sequenciais_limpeza_autorizados' => $cleanupSequences,
            'baseline_auditoria' => 0,
        ];
        $profile = [
            'ambiente' => 'HOMOLOGACAO',
            'cnpj' => '12345678000195',
            'pncp' => ['login' => 'login-visivel', 'senha' => 'segredo'],
            'oracle' => PerfilPublicacaoPcaFixture::valido()['oracle'],
        ];

        return Sprint84Runner::fromComponents(
            $config,
            $profile,
            'usuario.teste',
            'pca.homologacao.test',
            '00000000-0000-4000-8000-000000000084',
            $stage,
            $guard,
            new OracleCheckpointExecutor($guard),
            $queries,
            $publisher ?? static fn (): PublicarPcaViewModel => throw new \LogicException('não usado'),
            $pncpVerifier ?? static fn (): array => [],
            $cleanup,
            $pncpPreVerifier ?? static fn (): array => [
                ['situacao' => 'AUSENTE'],
                ['situacao' => 'AUSENTE'],
            ],
            $pncpDiagnostics
        );
    }

    /** @param array<int, int[]>|null $afterIds */
    private function runnerNenhumaAcao(
        ?callable $publisher = null,
        ?array $afterIds = null,
        array $diagnostics = [],
        ?object $adodb = null
    ): Sprint84Runner {
        $hash = $this->runner('PRECHECK', adodbInterno: $adodb === null ? null : clone $adodb)
            ->run()['precheck_oracle']['sha256'];
        $afterIds ??= [[1011], [2021]];

        return $this->runner(
            'NENHUMA_ACAO',
            $hash,
            $publisher ?? fn (): PublicarPcaViewModel => $this->viewModelNenhumaAcao(),
            static fn (): array => [
                [
                    'situacao' => 'ENCONTRADO', 'codigo_uasg' => '928341',
                    'sequencial_solicitado' => 71, 'sequencial_observado' => 71,
                    'ids_demandas' => $afterIds[0],
                ],
                [
                    'situacao' => 'ENCONTRADO', 'codigo_uasg' => '928342',
                    'sequencial_solicitado' => 72, 'sequencial_observado' => 72,
                    'ids_demandas' => $afterIds[1],
                ],
            ],
            [],
            null,
            static fn (): array => [
                [
                    'situacao' => 'ENCONTRADO', 'codigo_uasg' => '928341',
                    'sequencial_pncp' => 71, 'ids_demandas' => [1011],
                ],
                [
                    'situacao' => 'ENCONTRADO', 'codigo_uasg' => '928342',
                    'sequencial_pncp' => 72, 'ids_demandas' => [2021],
                ],
            ],
            static fn (): array => $diagnostics,
            $adodb
        );
    }

    private function viewModelNenhumaAcao(string $situacao = 'SEM_ALTERACAO'): PublicarPcaViewModel
    {
        return new PublicarPcaViewModel(
            'CONCLUIDA',
            [
                new ResultadoUnidadeViewModel(
                    'UO-101', 'INDIVIDUAL', 2027, '928341', 'UASG 101',
                    $situacao, 'NENHUMA_ACAO', 71, 0, 0, null, null
                ),
                new ResultadoUnidadeViewModel(
                    'UO-202', 'INDIVIDUAL', 2027, '928342', 'UASG 202',
                    $situacao, 'NENHUMA_ACAO', 72, 0, 0, null, null
                ),
            ],
            null,
            null,
            2,
            2,
            0,
            '00000000-0000-4000-8000-000000000084'
        );
    }
}

final class AdodbRunnerSprint84
{
    /** @param array<int|string, mixed> $binds */
    public function GetAll(string $sql, array $binds = []): array
    {
        if (str_contains($sql, 'PROJECAO')) {
            return AdodbProjecaoRunnerSprint84::linhas(false);
        }
        return [['ID' => 7]];
    }
}

final class AdodbMutavelRunnerSprint84
{
    private int $consultas = 0;

    /** @param array<int|string, mixed> $binds */
    public function GetAll(string $sql, array $binds = []): array
    {
        if (str_contains($sql, 'PROJECAO')) {
            return AdodbProjecaoRunnerSprint84::linhas(false);
        }
        $this->consultas++;

        return [['ID' => $this->consultas === 1 ? 7 : 8]];
    }
}

final class AdodbProjecaoRunnerSprint84
{
    /** @var array<string, int> */
    private array $consultas = [];

    public function __construct(private readonly bool $completar = true, private readonly bool $alterarConfirmacao = false) {}

    public function GetAll(string $sql, array $binds = []): array
    {
        if (str_contains($sql, 'SELECT ID')) {
            return [['ID' => 7]];
        }
        $this->consultas[$sql] = ($this->consultas[$sql] ?? 0) + 1;
        $depois = $this->consultas[$sql] > 1;
        $linhas = self::linhas(!$depois || !$this->completar, true);
        if (str_contains($sql, 'VERSOES_VIGENTES')) {
            array_shift($linhas);
        }
        if (!str_contains($sql, 'PROJECAO')) {
            foreach ($linhas as &$linha) {
                $linha['DATA_PUBLICACAO'] = $depois && $this->alterarConfirmacao ? 'alterada' : '2026-09-15 18:03:52';
            }
            unset($linha);
        }
        return $linhas;
    }

    public static function linhas(bool $pendente, bool $historico = false): array
    {
        $linhas = [
            ['FK_UNIDADE_ORCAMENTARIA' => 'UO-101', 'VERSAO' => 1,
                'ESTRATEGIA_ADOTADA_PUBLICACAO' => 'INDIVIDUAL', 'UASG_ADOTADA' => '928341',
                'ESTADO_PUBLICACAO' => $pendente ? null : 'PUBLICADO',
                'SEQUENCIAL_PUBLICACAO_PNCP' => $pendente ? null : 71],
            ['FK_UNIDADE_ORCAMENTARIA' => 'UO-202', 'VERSAO' => 1,
                'ESTRATEGIA_ADOTADA_PUBLICACAO' => 'INDIVIDUAL', 'UASG_ADOTADA' => '928342',
                'ESTADO_PUBLICACAO' => 'PUBLICADO', 'SEQUENCIAL_PUBLICACAO_PNCP' => 72],
        ];
        if ($historico) {
            $anterior = $linhas[0];
            $anterior['ESTADO_PUBLICACAO'] = 'PUBLICADO';
            $anterior['SEQUENCIAL_PUBLICACAO_PNCP'] = 71;
            $linhas[0]['VERSAO'] = 3;
            array_unshift($linhas, $anterior);
        }
        return $linhas;
    }
}
