<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint85;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Tests\Contract\Sprint84\Support\Sprint84GateException;
use Tests\Contract\Sprint85\Support\Sprint85OracleQueries;
use Tests\Contract\Sprint85\Support\Sprint85Precheck;
use Tests\Contract\Sprint85\Support\Sprint85Config;
use Tests\Support\PerfilPublicacaoPcaFixture;

final class Sprint85PreparationTest extends TestCase
{
    public function testConsultasDevemSerCompletasSomenteLeituraEDeclararContrato(): void
    {
        $queries = (new Sprint85OracleQueries(
            new PerfilPublicacaoPca(PerfilPublicacaoPcaFixture::valido()),
            $this->configuration(),
            'usuario.teste'
        ))->all();

        $this->assertCount(12, $queries);
        $this->assertSame([
            'estrategia_corrente',
            'representante',
            'lotacao_usuario',
            'unidades_orcamentarias',
            'todas_versoes',
            'versoes_vigentes',
            'fotografia_vigente',
            'ciclo_demandas',
            'projecao_operacional',
            'auditoria',
            'controles_concorrencia',
            'logs_tecnicos_gate',
        ], array_keys($queries));

        Sprint85Precheck::assertReadOnlyQueries($queries);

        foreach ($queries as $query) {
            $this->assertIsArray($query['binds']);
            $this->assertNotSame([], $query['columns']);
            $this->assertArrayHasKey('expected_cardinality', $query);
        }

        $logs = $queries['logs_tecnicos_gate'];
        $this->assertStringContainsString('SGI.SEGURANCA_LOG', $logs['sql']);
        $this->assertStringContainsString('l.DESCRIPTION AS DESCRIPTION', $logs['sql']);
        $this->assertSame(['execute', 'SPRINT85-TESTE-LOCAL'], $logs['binds']);
        $this->assertSame(['exact' => 0], $logs['expected_cardinality']);
        $this->assertSame(
            ['exact' => 2],
            $queries['controles_concorrencia']['expected_cardinality']
        );
    }

    public function testPrecheckDeveRecusarSqlMutanteAntesDeIo(): void
    {
        $this->expectException(Sprint84GateException::class);
        $this->expectExceptionMessage('não aceita SQL mutante');

        Sprint85Precheck::assertReadOnlyQueries([
            'indevida' => [
                'sql' => 'SELECT ID FROM TABELA FOR UPDATE',
                'binds' => [],
                'columns' => ['ID'],
                'expected_cardinality' => ['exact' => 1],
            ],
        ]);
    }

    public function testConsultasDevemCobrirTresUosSemOcultarParticipanteDoConsolidado(): void
    {
        $configuration = $this->configuration();
        $configuration['unidades'][] = [
            'id_unidade_orcamentaria' => 149,
            'quantidade_versoes' => 71,
            'quantidade_fotografia' => 71,
            'quantidade_demandas' => 71,
        ];
        $queries = (new Sprint85OracleQueries(
            new PerfilPublicacaoPca(PerfilPublicacaoPcaFixture::valido()),
            $configuration,
            'usuario.teste'
        ))->all();

        $this->assertSame(['exact' => 3], $queries['unidades_orcamentarias']['expected_cardinality']);
        $this->assertSame(['exact' => 3], $queries['versoes_vigentes']['expected_cardinality']);
        $this->assertStringContainsString('IN (?, ?, ?)', $queries['todas_versoes']['sql']);
        $this->assertSame([2026, 1, 3, 149], $queries['todas_versoes']['binds']);
    }

    public function testCheckpointManualDosLogsDeveEstarMaterializadoESomenteLeitura(): void
    {
        $path = __DIR__ . '/checkpoints/logs_precheck.sql';

        $this->assertFileExists($path);
        $contents = file_get_contents($path);
        $this->assertIsString($contents);
        $this->assertStringNotContainsString('?', $contents);
        $this->assertStringContainsString('SPRINT85-LOGS-HML-20260921-JSILVA-02', $contents);
        $this->assertStringContainsString("l.ACTION = 'execute'", $contents);
        $this->assertStringContainsString('DBMS_LOB.INSTR', $contents);
        $this->assertStringContainsString(
            'Esperado: ACTION = VARCHAR2(30 BYTE); DESCRIPTION = CLOB.',
            $contents
        );

        $withoutComments = preg_replace('/^--.*$/m', '', $contents);
        $this->assertIsString($withoutComments);
        $statements = array_values(array_filter(
            array_map('trim', preg_split('/;\s*(?:\R|$)/', $withoutComments) ?: []),
            static fn (string $statement): bool => $statement !== ''
        ));

        $this->assertCount(2, $statements);
        foreach ($statements as $index => $statement) {
            $this->assertMatchesRegularExpression('/^SELECT\b/i', $statement, (string) $index);
            $this->assertDoesNotMatchRegularExpression(
                '/\b(?:INSERT|UPDATE|DELETE|MERGE|ALTER|DROP|CREATE|TRUNCATE|BEGIN|DECLARE)\b|FOR\s+UPDATE/i',
                $statement,
                (string) $index
            );
        }
    }

    public function testConfiguracaoDoSmokeDeveComecarDesarmadaEComFotografiaVazia(): void
    {
        $configuration = Sprint85Config::validate(
            require __DIR__ . '/smoke-config.example.php'
        );

        $this->assertSame('PRECHECK', $configuration['etapa_autorizada']);
        $this->assertFalse($configuration['allow_oracle_writes']);
        $this->assertFalse($configuration['allow_pncp_writes']);
        $this->assertFalse($configuration['allow_log_writes']);
        $this->assertNull($configuration['oracle_checkpoint_sha256']);
        $this->assertSame([3, 94, 149], array_column(
            $configuration['unidades'],
            'id_unidade_orcamentaria'
        ));
        $this->assertSame([0, 0, 0], array_column(
            $configuration['unidades'],
            'quantidade_fotografia'
        ));
    }

    public function testCheckpointManualDoSmokeDeveConterDozeSelectsSomenteLeitura(): void
    {
        $contents = file_get_contents(__DIR__ . '/checkpoints/smoke_precheck.sql');
        $this->assertIsString($contents);
        $this->assertStringContainsString(
            'SPRINT85-SMOKE-HML-20260921-JSILVA-01',
            $contents
        );

        $withoutComments = preg_replace('/^--.*$/m', '', $contents);
        $this->assertIsString($withoutComments);
        $statements = array_values(array_filter(
            array_map('trim', preg_split('/;\s*(?:\R|$)/', $withoutComments) ?: []),
            static fn (string $statement): bool => $statement !== ''
        ));

        $this->assertCount(12, $statements);
        foreach ($statements as $statement) {
            $this->assertMatchesRegularExpression('/^SELECT\b/i', $statement);
            $this->assertDoesNotMatchRegularExpression(
                '/\b(?:INSERT|UPDATE|DELETE|MERGE|ALTER|DROP|CREATE|TRUNCATE|BEGIN|DECLARE)\b|FOR\s+UPDATE/i',
                $statement
            );
        }
    }

    public function testPostcheckDoSmokeDeveConterSeteSelectsSomenteLeitura(): void
    {
        $contents = file_get_contents(__DIR__ . '/checkpoints/smoke_postcheck.sql');
        $this->assertIsString($contents);
        $this->assertStringContainsString('PREENCHER_UUID_DA_RESPOSTA', $contents);
        $this->assertStringContainsString('l.DESCRIPTION IS JSON', $contents);

        $withoutComments = preg_replace('/^--.*$/m', '', $contents);
        $this->assertIsString($withoutComments);
        $statements = array_values(array_filter(
            array_map('trim', preg_split('/;\s*(?:\R|$)/', $withoutComments) ?: []),
            static fn (string $statement): bool => $statement !== ''
        ));

        $this->assertCount(7, $statements);
        foreach ($statements as $statement) {
            $this->assertMatchesRegularExpression('/^SELECT\b/i', $statement);
            $this->assertDoesNotMatchRegularExpression(
                '/\b(?:INSERT|UPDATE|DELETE|MERGE|ALTER|DROP|CREATE|TRUNCATE|BEGIN|DECLARE)\b|FOR\s+UPDATE/i',
                $statement
            );
        }
    }

    public function testPostcheckDaRespostaIncertaDeveConterSeteSelectsSomenteLeitura(): void
    {
        $contents = file_get_contents(
            __DIR__ . '/checkpoints/smoke_resposta_incerta_postcheck.sql'
        );
        $this->assertIsString($contents);
        $this->assertStringContainsString("l.APPLICATION = 'PCA_PUBLICAR_B'", $contents);
        $this->assertStringContainsString("UPPER(l.USERNAME) = UPPER('jsilva')", $contents);
        $this->assertStringContainsString('JSON_VALUE(', $contents);
        $this->assertStringNotContainsString('PREENCHER_UUID', $contents);

        $withoutComments = preg_replace('/^--.*$/m', '', $contents);
        $this->assertIsString($withoutComments);
        $statements = array_values(array_filter(
            array_map('trim', preg_split('/;\s*(?:\R|$)/', $withoutComments) ?: []),
            static fn (string $statement): bool => $statement !== ''
        ));

        $this->assertCount(7, $statements);
        foreach ($statements as $statement) {
            $this->assertMatchesRegularExpression('/^SELECT\b/i', $statement);
            $this->assertDoesNotMatchRegularExpression(
                '/\b(?:INSERT|UPDATE|DELETE|MERGE|ALTER|DROP|CREATE|TRUNCATE|BEGIN|DECLARE)\b|FOR\s+UPDATE/i',
                $statement
            );
        }
    }

    public function testPrecheckDeveExecutarAsDozeConsultasSemEscritaOuFechamento(): void
    {
        $profile = new PerfilPublicacaoPca(PerfilPublicacaoPcaFixture::valido());
        $configuration = $this->configuration();
        $queries = (new Sprint85OracleQueries(
            $profile,
            $configuration,
            'usuario.teste'
        ))->all();
        $db = new AdodbSomenteLeituraSprint85($this->responsesFor($queries));

        $report = (new Sprint85Precheck(
            $db,
            'usuario.teste',
            'pca.homologacao.test',
            $configuration + [
                'etapa_autorizada' => 'PRECHECK',
                'oracle_checkpoint_sha256' => null,
            ],
            $profile
        ))->run();

        $this->assertSame('PRECHECK_CONFIRMADO', $report['resultado']);
        $this->assertSame('PRECHECK', $report['etapa']);
        $this->assertCount(12, $db->queries);
        $this->assertFalse($report['oracle_writes']);
        $this->assertFalse($report['pncp_access']);
        $this->assertFalse($report['log_writes']);
        $this->assertSame(64, strlen($report['oracle_checkpoint']['sha256']));
    }

    public function testEntrypointDeveRecusarConfiguracaoAusenteAntesDeIo(): void
    {
        $previous = getenv('PCA_SPRINT85_CONFIG_FILE');
        putenv('PCA_SPRINT85_CONFIG_FILE');
        require_once __DIR__ . '/scriptcase_probe.php';

        try {
            $result = \executar_precheck_sprint_85(
                new \stdClass(),
                'usuario.teste',
                'pca.homologacao.test'
            );
        } finally {
            $previous === false
                ? putenv('PCA_SPRINT85_CONFIG_FILE')
                : putenv('PCA_SPRINT85_CONFIG_FILE=' . $previous);
        }

        $this->assertSame('PRECHECK_RECUSADO', $result['resultado']);
        $this->assertSame('CONFIG_LOCALIZADOR_AUSENTE', $result['codigo']);
        $this->assertFalse($result['oracle_writes']);
        $this->assertFalse($result['pncp_access']);
        $this->assertFalse($result['log_writes']);
    }

    public function testBlankDaSprint85DeveLimitarOGateAPrecheckELogs(): void
    {
        $path = __DIR__ . '/blank_scriptcase/teste_contracts_sprint85.php';

        $this->assertFileExists($path);
        $contents = file_get_contents($path);
        $this->assertIsString($contents);
        $this->assertStringContainsString('PCA_SPRINT85_LOG_GATE_CONFIG_FILE', $contents);
        $this->assertStringContainsString('/pca-pncp-log-gate', $contents);
        $this->assertStringContainsString('/backend/vendor/autoload.php', $contents);
        $this->assertStringContainsString('/gate/bootstrap.php', $contents);
        $this->assertStringContainsString("\$etapasDisponiveis = ['PRECHECK', 'LOGS'];", $contents);
        $this->assertStringContainsString('executar_precheck_logs_sprint_85(', $contents);
        $this->assertStringContainsString('preparar_log_sintetico_sprint_85(', $contents);
        $this->assertStringContainsString('persistir_log_sintetico_sprint_85(', $contents);
        $this->assertStringContainsString('verificar_log_sintetico_sprint_85(', $contents);
        $this->assertStringContainsString('sprint85_log_gate_diagnosticar_correlacao()', $contents);
        $this->assertStringContainsString('sprint85_log_gate_diagnosticar_clob_adodb(', $contents);
        $this->assertStringContainsString("method_exists(\$db, 'UpdateClob')", $contents);
        $this->assertStringContainsString("'HTTP_X_CORRELATION_ID'", $contents);
        $this->assertStringNotContainsString('random_bytes(', $contents);
        $this->assertStringNotContainsString('sc_log_add(', $contents);
        $this->assertStringNotContainsString('PCA_SPRINT84_CONFIG_FILE', $contents);
        $this->assertStringNotContainsString('executar_fluxos_completos_sprint_84', $contents);
        $this->assertStringNotContainsString('PublicadorPcaScriptcase', $contents);

        foreach (['PUBLICAR', 'NENHUMA_ACAO', 'RETIFICAR', 'EXCLUIR_PCA'] as $etapaEncerrada) {
            $this->assertStringNotContainsString("'{$etapaEncerrada}',", $contents);
        }
    }

    /** @return array<string, mixed> */
    private function configuration(): array
    {
        return [
            'modo' => 'CONSOLIDADO',
            'ano' => 2026,
            'unidades' => [
                [
                    'id_unidade_orcamentaria' => 1,
                    'quantidade_versoes' => 3,
                    'quantidade_fotografia' => 0,
                    'quantidade_demandas' => 2,
                ],
                [
                    'id_unidade_orcamentaria' => 3,
                    'quantidade_versoes' => 3,
                    'quantidade_fotografia' => 0,
                    'quantidade_demandas' => 1,
                ],
            ],
            'baseline_auditoria' => 3,
            'identificador_log' => 'SPRINT85-TESTE-LOCAL',
            'quantidade_logs_esperada' => 0,
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $queries
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function responsesFor(array $queries): array
    {
        $responses = [];
        foreach ($queries as $query) {
            $cardinality = $query['expected_cardinality']['exact']
                ?? $query['expected_cardinality']['min']
                ?? 0;
            $row = array_fill_keys($query['columns'], null);
            $responses[] = array_fill(0, $cardinality, $row);
        }

        return $responses;
    }
}

final class AdodbSomenteLeituraSprint85
{
    /** @var array<int, array{sql: string, binds: array<int|string, mixed>}> */
    public array $queries = [];

    /** @param array<int, array<int, array<string, mixed>>> $responses */
    public function __construct(private array $responses) {}

    /** @param array<int|string, mixed> $binds @return array<int, array<string, mixed>> */
    public function GetAll(string $sql, array $binds = []): array
    {
        $this->queries[] = ['sql' => $sql, 'binds' => $binds];

        return array_shift($this->responses) ?? [];
    }

    public function Param(string|false $name): string
    {
        return ':' . ($name === false ? 'param' : $name);
    }

    public function ErrorNo(): int
    {
        return 0;
    }

    public function ErrorMsg(): string
    {
        return '';
    }
}
