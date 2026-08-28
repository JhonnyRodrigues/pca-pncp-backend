<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint510;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Contract\Sprint510\Support\BorrowedAdodbReadOnlyGuard;
use Tests\Contract\Sprint510\Support\OracleCancellationEvidenceInvestigationRunner;
use Tests\Contract\Sprint510\Support\SafeInvestigationReport;
use Tests\Contract\Sprint510\Support\Sprint510Config;

final class Sprint510InvestigationTest extends TestCase
{
    public function testCarregamentoDeveFalharFechadoSemArquivoExterno(): void
    {
        $previous = getenv('PCA_SPRINT510_CONFIG_FILE');
        putenv('PCA_SPRINT510_CONFIG_FILE');

        try {
            Sprint510Config::loadFromEnvironment();
            $this->fail('Era esperada recusa sem arquivo externo.');
        } catch (RuntimeException $failure) {
            $this->assertStringContainsString('nao foi informado', $failure->getMessage());
        } finally {
            if (is_string($previous)) {
                putenv('PCA_SPRINT510_CONFIG_FILE=' . $previous);
            } else {
                putenv('PCA_SPRINT510_CONFIG_FILE');
            }
        }
    }

    public function testPreflightDeveExigirConsentimentoExplicitoSomenteParaLeitura(): void
    {
        $config = $this->validOracleConfig();
        $config['allow_reads'] = false;

        $this->expectException(InvalidArgumentException::class);
        Sprint510Config::oracle(['oracle' => $config]);
    }

    public function testPreflightDeveRecusarQualquerAutorizacaoDeEscrita(): void
    {
        $config = $this->validOracleConfig();
        $config['allow_writes'] = true;

        $this->expectException(InvalidArgumentException::class);
        Sprint510Config::oracle(['oracle' => $config]);
    }

    public function testPreflightDeveExigirMassaDedicadaCriadaPeloFluxoOficial(): void
    {
        foreach (['dedicated_mass', 'official_sgi_workflow', 'physical_source_confirmed'] as $field) {
            $config = $this->validOracleConfig();
            $config[$field] = false;

            try {
                Sprint510Config::oracle(['oracle' => $config]);
                $this->fail("Era esperada recusa do campo {$field}.");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testPreflightDeveExigirTodosOsModosAprovados(): void
    {
        $config = $this->validOracleConfig();
        unset($config['modes']['consolidado']);

        $this->expectException(InvalidArgumentException::class);
        Sprint510Config::oracle(['oracle' => $config]);
    }

    public function testPreflightDeveRecusarSqlQueNaoSejaSelectUnicoEReadOnly(): void
    {
        foreach (['UPDATE T SET X = 1', 'SELECT 1 FROM DUAL; DELETE FROM T', 'SELECT 1 FROM T FOR UPDATE'] as $sql) {
            $config = $this->validOracleConfig();
            $config['modes']['individual'][0]['sql'] = $sql;

            try {
                Sprint510Config::oracle(['oracle' => $config]);
                $this->fail('Era esperada recusa do SQL investigativo inseguro.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testPreflightDeveRecusarAliasInvalidoOuInesperadoNaConfiguracao(): void
    {
        $config = $this->validOracleConfig();
        $config['modes']['individual'][0]['aliases'] = ['JUSTIFICATIVA', 'nome fisico'];

        $this->expectException(InvalidArgumentException::class);
        Sprint510Config::oracle(['oracle' => $config]);
    }

    public function testRunnerDeveConfirmarCasoIndividualComBindsEMesmaSessao(): void
    {
        $db = new Sprint510AdodbFake([
            [[0 => '10:20', 'SESSAO' => '10:20']],
            [[
                0 => 'Motivo integral',
                'JUSTIFICATIVA' => 'Motivo integral',
                'VINCULO' => 'CORRETO',
                'ORDEM_EVENTO' => 71,
            ]],
            [['SESSAO' => '10:20']],
        ]);

        $report = OracleCancellationEvidenceInvestigationRunner::fromConfig(
            $db,
            ['oracle' => $this->validOracleConfig()]
        )->run('individual');

        $this->assertTrue($report['confirmado']);
        $this->assertTrue($report['somente_leitura']);
        $this->assertTrue($report['mesma_sessao']);
        $this->assertFalse($report['close_invocado']);
        $this->assertSame(3, count($db->consultas));
        $this->assertSame(['param_0' => 2027], $db->consultas[1]['parametros']);
        $this->assertSame(0, $db->execucoes);
        $this->assertSame(0, $db->transacoes);
    }

    public function testRunnerDeveAprovarAusenciaQuandoNenhumaLinhaForEsperada(): void
    {
        $db = new Sprint510AdodbFake([
            [['SESSAO' => '10:20']],
            [],
            [['SESSAO' => '10:20']],
        ]);

        $report = OracleCancellationEvidenceInvestigationRunner::fromConfig(
            $db,
            ['oracle' => $this->validOracleConfig()]
        )->run('ausente');

        $this->assertTrue($report['confirmado']);
        $this->assertSame(0, $report['resultado']['checks'][0]['quantidade_linhas']);
    }

    public function testRunnerDeveRecusarCardinalidadeDivergente(): void
    {
        $db = new Sprint510AdodbFake([
            [['SESSAO' => '10:20']],
            [
                ['JUSTIFICATIVA' => 'Motivo integral', 'VINCULO' => 'CORRETO', 'ORDEM_EVENTO' => 1],
                ['JUSTIFICATIVA' => 'Outro motivo', 'VINCULO' => 'CORRETO', 'ORDEM_EVENTO' => 2],
            ],
            [['SESSAO' => '10:20']],
        ]);

        $report = OracleCancellationEvidenceInvestigationRunner::fromConfig(
            $db,
            ['oracle' => $this->validOracleConfig()]
        )->run('individual');

        $this->assertFalse($report['confirmado']);
        $this->assertFalse($report['resultado']['checks'][0]['cardinalidade_confirmada']);
    }

    public function testRunnerDeveRecusarJustificativaAusenteEVinculoDivergente(): void
    {
        $db = new Sprint510AdodbFake([
            [['SESSAO' => '10:20']],
            [['JUSTIFICATIVA' => ' ', 'VINCULO' => 'OUTRO', 'ORDEM_EVENTO' => 71]],
            [['SESSAO' => '10:20']],
        ]);

        $report = OracleCancellationEvidenceInvestigationRunner::fromConfig(
            $db,
            ['oracle' => $this->validOracleConfig()]
        )->run('individual');

        $this->assertFalse($report['confirmado']);
        $this->assertSame(
            [false, false, true],
            array_column($report['resultado']['checks'][0]['verificacoes'], 'confirmada')
        );
    }

    public function testRunnerDeveRecusarOrdenacaoAmbigua(): void
    {
        $config = $this->validOracleConfig();
        $config['modes']['consolidado'][0]['expected_rows'] = 2;
        $db = new Sprint510AdodbFake([
            [['SESSAO' => '10:20']],
            [
                ['JUSTIFICATIVA' => 'A', 'VINCULO' => 'CORRETO', 'ORDEM_EVENTO' => 71],
                ['JUSTIFICATIVA' => 'B', 'VINCULO' => 'CORRETO', 'ORDEM_EVENTO' => 71],
            ],
            [['SESSAO' => '10:20']],
        ]);

        $report = OracleCancellationEvidenceInvestigationRunner::fromConfig(
            $db,
            ['oracle' => $config]
        )->run('consolidado');

        $this->assertFalse($report['confirmado']);
        $this->assertFalse($report['resultado']['checks'][0]['verificacoes'][2]['confirmada']);
    }

    public function testRunnerDeveFalharComEtapaSeguraQuandoAliasNaoForRetornado(): void
    {
        $db = new Sprint510AdodbFake([
            [['SESSAO' => '10:20']],
            [['JUSTIFICATIVA' => 'Motivo integral', 'VINCULO' => 'CORRETO']],
        ]);

        $report = OracleCancellationEvidenceInvestigationRunner::fromConfig(
            $db,
            ['oracle' => $this->validOracleConfig()]
        )->run('individual');

        $this->assertFalse($report['confirmado']);
        $this->assertSame('check_1', $report['falha']['etapa']);
        $this->assertSame('INVESTIGACAO_ORACLE_SPRINT_510_FALHOU', $report['falha']['codigo']);
        $this->assertArrayNotHasKey('sql', $report['falha']);
    }

    public function testGuardaDeveBloquearExecuteTransacaoECloseAntesDoAdodb(): void
    {
        $db = new Sprint510AdodbFake([]);
        $guard = new BorrowedAdodbReadOnlyGuard($db);

        foreach (['BeginTrans', 'CommitTrans', 'RollbackTrans', 'Close'] as $method) {
            try {
                $guard->{$method}();
                $this->fail("{$method} deveria ser recusado.");
            } catch (LogicException) {
                $this->addToAssertionCount(1);
            }
        }

        try {
            $guard->Execute('DELETE FROM T');
            $this->fail('Execute deveria ser recusado.');
        } catch (LogicException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(0, $db->execucoes);
        $this->assertSame(0, $db->transacoes);
        $this->assertSame(0, $db->fechamentos);
        $this->assertTrue($guard->wasWriteAttempted());
        $this->assertTrue($guard->wasCloseCalled());
    }

    public function testRelatorioDeveRecusarCamposEValoresSensiveis(): void
    {
        foreach (
            [
                [['sql' => 'SELECT SEGREDO FROM T'], []],
                [['confirmado' => false, 'detalhe' => 'Motivo integral'], ['Motivo integral']],
            ] as [$report, $sensitive]
        ) {
            try {
                SafeInvestigationReport::encode($report, $sensitive);
                $this->fail('Era esperada recusa de relatório sensível.');
            } catch (RuntimeException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testRelatorioSanitizadoDeveSerEstavelParaHash(): void
    {
        $report = ['sprint' => '5.10', 'confirmado' => true, 'somente_leitura' => true];

        $this->assertSame(
            SafeInvestigationReport::hash($report),
            SafeInvestigationReport::hash($report)
        );
    }

    public function testEntrypointDevePreservarAssinaturaAprovada(): void
    {
        require_once __DIR__ . '/scriptcase_probe.php';
        $function = new \ReflectionFunction(
            'executar_investigacao_evidencia_cancelamento_oracle_sprint_510'
        );

        $this->assertSame(
            ['db', 'login', 'mode'],
            array_map(
                static fn (\ReflectionParameter $parameter): string => $parameter->getName(),
                $function->getParameters()
            )
        );
        $this->assertSame('metadata', $function->getParameters()[2]->getDefaultValue());
    }

    /** @return array<string, mixed> */
    private function validOracleConfig(): array
    {
        $caseCheck = [
            'label' => 'caso_individual',
            'sql' => 'SELECT JUSTIFICATIVA, VINCULO, ORDEM_EVENTO FROM FONTE WHERE ANO = ?',
            'params' => [2027],
            'expected_rows' => 1,
            'aliases' => ['JUSTIFICATIVA', 'VINCULO', 'ORDEM_EVENTO'],
            'assertions' => [
                ['alias' => 'JUSTIFICATIVA', 'operator' => 'non_empty'],
                ['alias' => 'VINCULO', 'operator' => 'eq', 'value' => 'CORRETO'],
                ['alias' => 'ORDEM_EVENTO', 'operator' => 'unique'],
            ],
        ];

        return [
            'allow_reads' => true,
            'dedicated_mass' => true,
            'official_sgi_workflow' => true,
            'physical_source_confirmed' => true,
            'modes' => [
                'metadata' => [[
                    'label' => 'metadados_fonte',
                    'sql' => 'SELECT TIPO, NULAVEL FROM DICIONARIO WHERE OBJETO = ?',
                    'params' => ['FONTE'],
                    'expected_rows' => 1,
                    'aliases' => ['TIPO', 'NULAVEL'],
                    'assertions' => [
                        ['alias' => 'TIPO', 'operator' => 'not_null'],
                        ['alias' => 'NULAVEL', 'operator' => 'not_null'],
                    ],
                ]],
                'individual' => [$caseCheck],
                'consolidado' => [[...$caseCheck, 'label' => 'caso_consolidado']],
                'ausente' => [[
                    ...$caseCheck,
                    'label' => 'caso_ausente',
                    'expected_rows' => 0,
                    'assertions' => [],
                ]],
            ],
        ];
    }
}

final class Sprint510AdodbFake
{
    /** @var list<array<int, array<int|string, mixed>>|false> */
    private array $responses;

    /** @var list<array{sql: string, parametros: array<int|string, mixed>}> */
    public array $consultas = [];

    public int $execucoes = 0;
    public int $transacoes = 0;
    public int $fechamentos = 0;

    /** @param list<array<int, array<int|string, mixed>>|false> $responses */
    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    public function BeginTrans(): bool
    {
        $this->transacoes++;
        return true;
    }

    public function CommitTrans(): bool
    {
        $this->transacoes++;
        return true;
    }

    public function RollbackTrans(): bool
    {
        $this->transacoes++;
        return true;
    }

    /** @param array<int|string, mixed> $parameters */
    public function Execute(string $sql, array $parameters = []): bool
    {
        $this->execucoes++;
        return true;
    }

    /** @param array<int|string, mixed> $parameters */
    public function GetAll(string $sql, array $parameters = []): array|false
    {
        $this->consultas[] = ['sql' => $sql, 'parametros' => $parameters];
        return array_shift($this->responses) ?? false;
    }

    public function Param(string|false $name): string
    {
        return $name === false ? '' : ':' . $name;
    }

    public function ErrorNo(): int
    {
        return 0;
    }

    public function ErrorMsg(): string
    {
        return '';
    }

    public function Close(): void
    {
        $this->fechamentos++;
    }
}
