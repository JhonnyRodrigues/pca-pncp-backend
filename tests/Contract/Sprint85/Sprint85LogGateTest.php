<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint85;

use PHPUnit\Framework\TestCase;
use Tests\Contract\Sprint84\Support\Sprint84ConfigurationException;
use Tests\Contract\Sprint84\Support\Sprint84GateException;
use Tests\Contract\Sprint85\Support\Sprint85LogGate;
use Tests\Contract\Sprint85\Support\Sprint85LogGateConfig;
use Tests\Contract\Sprint85\Support\Sprint85LogGateQueries;
use Tests\Contract\Sprint85\Support\Sprint85Precheck;
use Tests\Contract\Sprint85\Support\Sprint85SyntheticLog;

final class Sprint85LogGateTest extends TestCase
{
    public function testConfiguracaoDoPrecheckDeveProibirTodasAsEscritas(): void
    {
        $configuration = Sprint85LogGateConfig::validate($this->configuration());

        $this->assertSame('PRECHECK', $configuration['etapa_autorizada']);
        $this->assertFalse($configuration['allow_oracle_writes']);
        $this->assertFalse($configuration['allow_pncp_writes']);
        $this->assertFalse($configuration['allow_log_writes']);
    }

    public function testEtapaLogsDeveExigirConsentimentoEHash(): void
    {
        $configuration = $this->configuration();
        $configuration['etapa_autorizada'] = 'LOGS';
        $configuration['allow_log_writes'] = true;

        $this->expectException(Sprint84ConfigurationException::class);
        $this->expectExceptionMessage('CONFIG_HASH_INVALIDO');

        Sprint85LogGateConfig::validate($configuration);
    }

    public function testPrecheckDeLogsDeveTerSomenteDuasConsultasIndependentesDoPca(): void
    {
        $queries = (new Sprint85LogGateQueries($this->configuration()))->precheck();

        $this->assertSame(
            ['estrutura_log_tecnico', 'logs_tecnicos_antes_gate'],
            array_keys($queries)
        );
        Sprint85Precheck::assertReadOnlyQueries($queries);

        $sql = implode(' ', array_column($queries, 'sql'));
        $this->assertStringContainsString('ALL_TAB_COLUMNS', $sql);
        $this->assertStringContainsString('SGI.SEGURANCA_LOG', $sql);
        $this->assertStringNotContainsString('PAC_PLANOS', $sql);
        $this->assertStringNotContainsString('PAC_DEMANDA', $sql);
    }

    public function testEventoSinteticoDeveExcederQuatroMilBytesEPreservarContrato(): void
    {
        $payload = Sprint85SyntheticLog::create(
            'SPRINT85-LOGS-HML-20260918-JSILVA-TESTE',
            '123e4567-e89b-42d3-a456-426614174000'
        );

        $this->assertGreaterThan(4000, $payload['bytes']);
        $this->assertSame(64, strlen($payload['sha256']));
        $this->assertStringContainsString('\\u00e7', $payload['json']);
        $this->assertStringContainsString('"error":{}', $payload['json']);
        $this->assertStringContainsString('"context":{}', $payload['json']);
        $this->assertStringNotContainsString('Validação', $payload['json']);
    }

    public function testPreparacaoDeveRepetirPrecheckComHashAntesDeLiberarJson(): void
    {
        $configuration = $this->configuration();
        $configuration['etapa_autorizada'] = 'LOGS';
        $configuration['allow_log_writes'] = true;
        $configuration['oracle_checkpoint_sha256'] = str_repeat('0', 64);
        $db = new AdodbSomenteLeituraLogGateSprint85([
            $this->schemaRows(),
            [],
        ]);
        $gate = new Sprint85LogGate(
            $db,
            'validasgi.semaepiracicaba.sp.gov.br',
            $configuration
        );

        $this->expectException(Sprint84GateException::class);
        $this->expectExceptionMessage('estado Oracle divergiu');

        $gate->prepare('123e4567-e89b-42d3-a456-426614174000');
    }

    public function testPersistenciaDeveUsarAdapterClobEPreservarJsonSintetico(): void
    {
        $configuration = $this->configuration();
        $configuration['etapa_autorizada'] = 'LOGS';
        $configuration['allow_log_writes'] = true;
        $configuration['oracle_checkpoint_sha256'] = str_repeat('0', 64);
        $payload = Sprint85SyntheticLog::create(
            'SPRINT85-LOGS-HML-20260920-JSILVA-02',
            '123e4567-e89b-42d3-a456-426614174000'
        );
        $db = new AdodbSomenteLeituraLogGateSprint85([[['ID' => 7758000]]]);
        $gate = new Sprint85LogGate(
            $db,
            'validasgi.semaepiracicaba.sp.gov.br',
            $configuration
        );

        $gate->persistir($payload['json'], 'jsilva', 'teste_contracts_sprint85');

        $this->assertSame(1, $db->inicios);
        $this->assertSame(1, $db->commits);
        $this->assertSame(0, $db->rollbacks);
        $this->assertSame([$payload['json']], $db->clobs);
    }

    /** @return array<string, mixed> */
    private function configuration(): array
    {
        return [
            'allow_oracle_writes' => false,
            'allow_pncp_writes' => false,
            'allow_log_writes' => false,
            'server_name' => 'validasgi.semaepiracicaba.sp.gov.br',
            'charset_adodb' => 'Windows-1252',
            'etapa_autorizada' => 'PRECHECK',
            'oracle_checkpoint_sha256' => null,
            'identificador_log' => 'SPRINT85-LOGS-HML-20260918-JSILVA-TESTE',
        ];
    }

    /** @return array<int, array<string, int|string>> */
    private function schemaRows(): array
    {
        return [
            [
                'COLUMN_NAME' => 'ACTION',
                'DATA_TYPE' => 'VARCHAR2',
                'DATA_LENGTH' => 30,
                'CHAR_LENGTH' => 30,
            ],
            [
                'COLUMN_NAME' => 'DESCRIPTION',
                'DATA_TYPE' => 'CLOB',
                'DATA_LENGTH' => 4000,
                'CHAR_LENGTH' => 4000,
            ],
        ];
    }
}

final class AdodbSomenteLeituraLogGateSprint85
{
    public int $inicios = 0;
    public int $commits = 0;
    public int $rollbacks = 0;

    /** @var string[] */
    public array $clobs = [];

    /** @param array<int, array<int, array<string, mixed>>> $responses */
    public function __construct(private array $responses) {}

    /** @param array<int|string, mixed> $binds @return array<int, array<string, mixed>> */
    public function GetAll(string $sql, array $binds = []): array
    {
        return array_shift($this->responses) ?? [];
    }

    public function BeginTrans(): bool
    {
        $this->inicios++;

        return true;
    }

    public function CommitTrans(): bool
    {
        $this->commits++;

        return true;
    }

    public function RollbackTrans(): bool
    {
        $this->rollbacks++;

        return true;
    }

    /** @param array<string, mixed> $binds */
    public function Execute(string $sql, array $binds = []): bool
    {
        return true;
    }

    public function UpdateClob(
        string $table,
        string $column,
        string $content,
        string $where
    ): bool {
        $this->clobs[] = $content;

        return true;
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
