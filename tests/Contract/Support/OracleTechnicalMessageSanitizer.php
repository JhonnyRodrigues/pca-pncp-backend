<?php

declare(strict_types=1);

namespace Tests\Contract\Support;

use Semae\Pca\Infrastructure\Oracle\Adodb\FalhaOracleException;
use Throwable;

final class OracleTechnicalMessageSanitizer
{
    /** @param string[] $sensitiveValues */
    public static function sanitize(Throwable $cause, array $sensitiveValues): string
    {
        $message = $cause->getMessage();

        if ($cause instanceof FalhaOracleException && $cause->getCodigoOracle() !== null) {
            $message .= ' (' . $cause->getCodigoOracle() . ')';
        }

        usort(
            $sensitiveValues,
            static fn (string $first, string $second): int => strlen($second) <=> strlen($first)
        );
        foreach ($sensitiveValues as $value) {
            if ($value !== '') {
                $message = str_ireplace($value, '[DADO REDIGIDO]', $message);
            }
        }

        $patterns = [
            '/\bAuthorization\s*:\s*(?:Bearer\s+)?[^\s,;]+/iu' => '[DADO REDIGIDO]',
            '/\b(?:senha|password|token|credencial|credential)\b\s*[:=]\s*'
                . '(?:"[^"]*"|\'[^\']*\'|[^\s,;]+)/iu' => '[DADO REDIGIDO]',
            '/\b(?:body|corpo|payload|headers?)\b\s*[:=]\s*[^\r\n;]+/iu'
                => '[DADO REDIGIDO]',
            '/(?<![0-9])[0-9]{14}(?![0-9])/' => '[CNPJ REDIGIDO]',
            '~https?://[^\s"\'<>]+~iu' => '[URL REDIGIDA]',
            '/\b(?:SELECT|INSERT\s+INTO|UPDATE|DELETE\s+FROM|MERGE\s+INTO|'
                . 'ALTER\s+TABLE|CREATE\s+TABLE|DROP\s+TABLE)\b.*$/isu'
                => '[SQL REDIGIDO]',
        ];
        $sanitized = preg_replace(
            array_keys($patterns),
            array_values($patterns),
            $message
        );
        $sanitized = is_string($sanitized) ? trim($sanitized) : '';
        $sanitized = preg_replace('/\s+/u', ' ', $sanitized);

        if (!is_string($sanitized) || $sanitized === '') {
            return 'Falha técnica sem mensagem segura disponível.';
        }

        if (strlen($sanitized) <= 1000) {
            return $sanitized;
        }

        if (preg_match('/\A.{0,997}/us', $sanitized, $match) === 1) {
            return $match[0] . '...';
        }

        return 'Falha técnica sem mensagem segura disponível.';
    }
}
