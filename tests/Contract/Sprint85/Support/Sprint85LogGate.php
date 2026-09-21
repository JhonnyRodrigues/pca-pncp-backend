<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint85\Support;

use JsonException;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Oracle\Observabilidade\OracleRegistroEventoTecnicoAdapter;
use Tests\Contract\Sprint84\Support\OracleCheckpointExecutor;
use Tests\Contract\Sprint84\Support\Sprint84AdodbGuard;
use Tests\Contract\Sprint84\Support\Sprint84GateException;

final class Sprint85LogGate
{
    /** @param array<string, mixed> $configuration */
    public function __construct(
        private readonly object $db,
        private readonly string $serverName,
        private readonly array $configuration
    ) {}

    public static function fromEnvironment(object $db, string $serverName): self
    {
        $configuration = Sprint85LogGateConfig::loadFromEnvironment();
        Sprint85LogGateConfig::assertServer($configuration, $serverName);

        return new self($db, $serverName, $configuration);
    }

    /** @return array<string, mixed> */
    public function precheck(): array
    {
        $queries = (new Sprint85LogGateQueries($this->configuration))->precheck();
        self::assertReadOnlyQueries($queries);
        [$checkpoint, $guard] = $this->execute($queries);
        $this->assertLogStructure($checkpoint);

        $expectedHash = $this->configuration['oracle_checkpoint_sha256'];
        if (
            is_string($expectedHash)
            && !hash_equals($expectedHash, (string) $checkpoint['sha256'])
        ) {
            throw new Sprint84GateException(
                'PRECHECK_HASH_DIVERGENTE',
                'PRECHECK_ORACLE',
                'O estado Oracle divergiu do checkpoint fixado.',
                [
                    'sha256_esperado' => $expectedHash,
                    'sha256_encontrado' => $checkpoint['sha256'],
                ]
            );
        }

        return [
            'sprint' => '8.5',
            'resultado' => 'PRECHECK_LOGS_CONFIRMADO',
            'etapa' => $this->configuration['etapa_autorizada'],
            'server_name' => $this->serverName,
            'identificador_log' => $this->configuration['identificador_log'],
            'oracle_checkpoint' => $checkpoint,
            'diagnostico_adodb' => $guard->diagnostico(),
            'oracle_writes' => false,
            'pncp_access' => false,
            'pncp_writes' => false,
            'log_writes' => false,
        ];
    }

    /** @return array{json: string, bytes: int, sha256: string, identificador_log: string} */
    public function prepare(string $correlationId): array
    {
        if ($this->configuration['etapa_autorizada'] !== 'LOGS') {
            throw new Sprint84GateException(
                'ETAPA_LOGS_NAO_AUTORIZADA',
                'LOG_SINTETICO',
                'A configuração externa não autorizou a etapa LOGS.'
            );
        }

        $this->precheck();
        $payload = Sprint85SyntheticLog::create(
            (string) $this->configuration['identificador_log'],
            $correlationId
        );

        return $payload + [
            'identificador_log' => (string) $this->configuration['identificador_log'],
        ];
    }

    /** @return array<string, mixed> */
    public function verify(string $expectedJson): array
    {
        $queries = (new Sprint85LogGateQueries($this->configuration))->verification();
        self::assertReadOnlyQueries($queries);
        [$checkpoint, $guard] = $this->execute($queries);
        $description = $checkpoint['snapshot']['resultados']['log_tecnico_persistido'][0]['DESCRIPTION']
            ?? null;

        if (!is_string($description) || !hash_equals($expectedJson, $description)) {
            throw new Sprint84GateException(
                'LOG_PERSISTIDO_DIVERGENTE',
                'LOG_SINTETICO',
                'O CLOB persistido não corresponde integralmente ao JSON enviado.'
            );
        }

        $decoded = json_decode($description, true);
        if (
            !is_array($decoded)
            || ($decoded['metadata']['identificadorGate'] ?? null)
                !== $this->configuration['identificador_log']
            || ($decoded['message'] ?? null)
                !== 'Evento sintético para validar a persistência integral pelo Scriptcase.'
            || ($decoded['error'] ?? null) !== []
            || ($decoded['context'] ?? null) !== []
        ) {
            throw new Sprint84GateException(
                'LOG_PERSISTIDO_CONTEUDO_INVALIDO',
                'LOG_SINTETICO',
                'O JSON relido do CLOB não preservou o contrato esperado.'
            );
        }

        return [
            'sprint' => '8.5',
            'resultado' => 'LOGS_CONFIRMADOS',
            'identificador_log' => $this->configuration['identificador_log'],
            'action' => 'execute',
            'bytes_persistidos' => strlen($description),
            'sha256_persistido' => hash('sha256', $description),
            'acentos_preservados' => true,
            'mapas_vazios_preservados' => true,
            'quantidade_logs' => 1,
            'diagnostico_adodb' => $guard->diagnostico(),
            'oracle_writes' => false,
            'pncp_access' => false,
            'pncp_writes' => false,
            'log_writes' => true,
        ];
    }

    public function persistir(string $json, string $login, string $aplicacao): void
    {
        if ($this->configuration['etapa_autorizada'] !== 'LOGS') {
            throw new Sprint84GateException(
                'ETAPA_LOGS_NAO_AUTORIZADA',
                'LOG_SINTETICO',
                'A configuração externa não autorizou a etapa LOGS.'
            );
        }

        try {
            $evento = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $failure) {
            throw new Sprint84GateException(
                'LOG_SINTETICO_INVALIDO',
                'LOG_SINTETICO',
                'O evento sintético não pôde ser materializado.',
                [],
                $failure
            );
        }

        if (!is_array($evento)) {
            throw new Sprint84GateException(
                'LOG_SINTETICO_INVALIDO',
                'LOG_SINTETICO',
                'O evento sintético não pôde ser materializado.'
            );
        }

        (new OracleRegistroEventoTecnicoAdapter(new ConexaoAdodbEmprestada(
            $this->db,
            (string) $this->configuration['charset_adodb']
        )))->registrar($evento, $login, $aplicacao);
    }

    /**
     * @param array<string, array<string, mixed>> $queries
     * @return array{array<string, mixed>, Sprint84AdodbGuard}
     */
    private function execute(array $queries): array
    {
        $charset = (string) $this->configuration['charset_adodb'];
        $guard = new Sprint84AdodbGuard($this->db, $charset);
        $checkpoint = (new OracleCheckpointExecutor(
            $guard,
            $charset
        ))->execute($queries);

        return [$checkpoint, $guard];
    }

    /** @param array<string, mixed> $checkpoint */
    private function assertLogStructure(array $checkpoint): void
    {
        $rows = $checkpoint['snapshot']['resultados']['estrutura_log_tecnico'] ?? null;
        if (!is_array($rows)) {
            throw new Sprint84GateException(
                'LOG_ESTRUTURA_INVALIDA',
                'PRECHECK_ORACLE',
                'A estrutura física do log não pôde ser confirmada.'
            );
        }

        $byName = [];
        foreach ($rows as $row) {
            if (is_array($row) && is_string($row['COLUMN_NAME'] ?? null)) {
                $byName[$row['COLUMN_NAME']] = $row;
            }
        }

        $action = $byName['ACTION'] ?? [];
        $description = $byName['DESCRIPTION'] ?? [];
        if (
            ($action['DATA_TYPE'] ?? null) !== 'VARCHAR2'
            || (int) ($action['DATA_LENGTH'] ?? 0) !== 30
            || ($description['DATA_TYPE'] ?? null) !== 'CLOB'
        ) {
            throw new Sprint84GateException(
                'LOG_ESTRUTURA_DIVERGENTE',
                'PRECHECK_ORACLE',
                'ACTION ou DESCRIPTION divergiu do contrato físico esperado.'
            );
        }
    }

    /** @param array<string, array<string, mixed>> $queries */
    private static function assertReadOnlyQueries(array $queries): void
    {
        foreach ($queries as $name => $query) {
            $sql = $query['sql'] ?? null;
            if (!is_string($sql) || preg_match('/^\s*SELECT\b/i', $sql) !== 1) {
                throw new Sprint84GateException(
                    'PRECHECK_SQL_NAO_SOMENTE_LEITURA',
                    'PRECHECK_CONFIGURACAO',
                    'Todas as consultas do PRECHECK devem iniciar com SELECT.',
                    ['consulta' => $name]
                );
            }
            if (
                preg_match(
                    '/\b(?:INSERT|UPDATE|DELETE|MERGE|ALTER|DROP|TRUNCATE|FOR\s+UPDATE)\b/i',
                    $sql
                ) === 1
            ) {
                throw new Sprint84GateException(
                    'PRECHECK_SQL_MUTANTE',
                    'PRECHECK_CONFIGURACAO',
                    'O PRECHECK não aceita SQL mutante ou lock.',
                    ['consulta' => $name]
                );
            }
        }
    }
}
