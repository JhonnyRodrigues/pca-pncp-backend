<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint510\Support;

use RuntimeException;

final class SafeInvestigationReport
{
    /** @var string[] */
    private const FORBIDDEN_KEYS = [
        'authorization', 'body', 'cnpj', 'column', 'coluna', 'corpo',
        'credential', 'credencial', 'ddl', 'header', 'headers', 'id',
        'justificativa', 'login', 'nome_fisico', 'object', 'objeto',
        'password', 'payload', 'query', 'schema', 'senha', 'sql', 'table',
        'tabela', 'token', 'url',
    ];

    /**
     * @param array<string, mixed> $report
     * @param string[] $sensitiveValues
     */
    public static function encode(array $report, array $sensitiveValues): string
    {
        self::assertSafeKeys($report);
        $json = json_encode(
            $report,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        usort(
            $sensitiveValues,
            static fn (string $first, string $second): int => strlen($second) <=> strlen($first)
        );
        foreach ($sensitiveValues as $value) {
            if ($value !== '' && stripos($json, $value) !== false) {
                throw new RuntimeException('O relatorio investigativo continha valor sensivel.');
            }
        }

        return $json;
    }

    /** @param array<string, mixed> $report */
    public static function hash(array $report): string
    {
        return hash('sha256', self::encode($report, []));
    }

    /** @param array<int|string, mixed> $values */
    private static function assertSafeKeys(array $values): void
    {
        foreach ($values as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::FORBIDDEN_KEYS, true)) {
                throw new RuntimeException('O relatorio investigativo continha campo sensivel.');
            }
            if (is_array($value)) {
                self::assertSafeKeys($value);
            }
        }
    }
}
