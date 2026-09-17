<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84\Support;

final class Sprint84DiagnosticReport
{
    /** @param array<string, mixed> $report @return array<string, mixed> */
    public static function finish(array $report): array
    {
        $secrets = self::collectSecrets($report);
        $report = self::protectSecrets($report);
        $report = self::redactSecretFragments($report, $secrets);
        $json = json_encode(
            $report,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE
                | JSON_THROW_ON_ERROR
        );
        $report = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $report['sha256_relatorio'] = hash('sha256', $json);

        return $report;
    }

    /** @return string[] */
    private static function collectSecrets(mixed $value, ?string $key = null): array
    {
        if ($key !== null && self::isSecretKey($key)) {
            if (is_string($value) && $value !== '') {
                return [$value];
            }
            if (is_array($value)) {
                $found = [];
                array_walk_recursive($value, static function (mixed $item) use (&$found): void {
                    if (is_string($item) && $item !== '') {
                        $found[] = $item;
                    }
                });

                return array_values(array_unique($found));
            }
        }

        if (!is_array($value)) {
            return [];
        }

        $secrets = [];
        foreach ($value as $childKey => $childValue) {
            $secrets = array_merge(
                $secrets,
                self::collectSecrets($childValue, is_string($childKey) ? $childKey : null)
            );
        }

        return array_values(array_unique($secrets));
    }

    private static function protectSecrets(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null && self::isSecretKey($key)) {
            return '[PROTEGIDO]';
        }

        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $childKey => $childValue) {
            $value[$childKey] = self::protectSecrets(
                $childValue,
                is_string($childKey) ? $childKey : null
            );
        }

        return $value;
    }

    /** @param string[] $secrets */
    private static function redactSecretFragments(mixed $value, array $secrets): mixed
    {
        if (is_string($value)) {
            return str_replace($secrets, '[PROTEGIDO]', $value);
        }
        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $childValue) {
            $value[$key] = self::redactSecretFragments($childValue, $secrets);
        }

        return $value;
    }

    private static function isSecretKey(string $key): bool
    {
        return preg_match(
            '/(?:senha|password|token|jwt|authorization|credencial)/i',
            $key
        ) === 1;
    }
}
