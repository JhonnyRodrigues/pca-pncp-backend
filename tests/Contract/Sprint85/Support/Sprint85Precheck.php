<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint85\Support;

use Semae\Pca\Composition\PerfilPublicacaoPca;
use Tests\Contract\Sprint84\Support\OracleCheckpointExecutor;
use Tests\Contract\Sprint84\Support\Sprint84AdodbGuard;
use Tests\Contract\Sprint84\Support\Sprint84GateException;

final class Sprint85Precheck
{
    /** @param array<string, mixed> $configuration */
    public function __construct(
        private readonly object $db,
        private readonly string $authenticatedLogin,
        private readonly string $serverName,
        private readonly array $configuration,
        private readonly PerfilPublicacaoPca $profile
    ) {}

    public static function fromEnvironment(
        object $db,
        string $authenticatedLogin,
        string $serverName
    ): self {
        $configuration = Sprint85Config::loadFromEnvironment();
        $profile = Sprint85Config::loadHomologationProfile($configuration, $serverName);

        return new self($db, $authenticatedLogin, $serverName, $configuration, $profile);
    }

    /** @return array<string, mixed> */
    public function run(): array
    {
        $queries = (new Sprint85OracleQueries(
            $this->profile,
            $this->configuration,
            $this->authenticatedLogin
        ))->all();
        self::assertReadOnlyQueries($queries);

        $guard = new Sprint84AdodbGuard($this->db, $this->profile->getCharsetAdodb());
        $checkpoint = (new OracleCheckpointExecutor(
            $guard,
            $this->profile->getCharsetAdodb()
        ))->execute($queries);

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
            'resultado' => 'PRECHECK_CONFIRMADO',
            'etapa' => $this->configuration['etapa_autorizada'],
            'server_name' => $this->serverName,
            'modo' => $this->configuration['modo'],
            'ano' => $this->configuration['ano'],
            'oracle_checkpoint' => $checkpoint,
            'diagnostico_adodb' => $guard->diagnostico(),
            'oracle_writes' => false,
            'pncp_access' => false,
            'pncp_writes' => false,
            'log_writes' => false,
        ];
    }

    /** @param array<string, array<string, mixed>> $queries */
    public static function assertReadOnlyQueries(array $queries): void
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
            if (preg_match('/\b(?:INSERT|UPDATE|DELETE|MERGE|ALTER|DROP|TRUNCATE|FOR\s+UPDATE)\b/i', $sql) === 1) {
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
