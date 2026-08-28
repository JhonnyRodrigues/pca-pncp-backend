<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint59\Support;

use RuntimeException;

final class SafeContractReport
{
    /** @var string[] */
    private const FORBIDDEN_KEYS = [
        'authorization',
        'body',
        'cnpj',
        'corpo',
        'credential',
        'credencial',
        'header',
        'headers',
        'iddemanda',
        'ids_demandas',
        'login',
        'numeroitem',
        'password',
        'payload',
        'senha',
        'sequencial',
        'sequencialpca',
        'token',
        'uasg',
        'codigouasg',
        'codigounidade',
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

        foreach ($sensitiveValues as $value) {
            if ($value !== '' && str_contains($json, $value)) {
                throw new RuntimeException('O relatorio contratual continha valor sensivel.');
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
                throw new RuntimeException('O relatorio contratual continha campo sensivel.');
            }
            if (is_array($value)) {
                self::assertSafeKeys($value);
            }
        }
    }
}
