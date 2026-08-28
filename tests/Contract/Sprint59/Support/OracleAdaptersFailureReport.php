<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint59\Support;

use Semae\Pca\Infrastructure\Oracle\Adodb\FalhaOracleException;
use Throwable;

final class OracleAdaptersFailureReport
{
    private const MODES = [
        'adapters',
        'lock_holder',
        'lock_expect_blocked',
        'lock_expect_available',
    ];

    private const GENERIC_MESSAGE =
        'O contrato Oracle foi recusado sem exposicao de detalhes internos.';

    /**
     * @param string[] $sensitiveValues
     * @return array<string, mixed>
     */
    public static function create(
        string $mode,
        Throwable $failure,
        array $sensitiveValues
    ): array {
        $safeMode = in_array($mode, self::MODES, true) ? $mode : 'INVALIDO';
        $report = [
            'sprint' => '5.9',
            'gate' => 'ORACLE_SCRIPTCASE',
            'modo' => $safeMode,
            'confirmado' => false,
            'falha' => [
                'codigo' => 'CONTRATO_ORACLE_SPRINT_59_FALHOU',
                'mensagem' => self::GENERIC_MESSAGE,
            ],
            'close_invocado' => false,
        ];

        if ($safeMode !== 'adapters') {
            return $report;
        }

        $report['falha']['etapa'] = $failure instanceof OracleAdaptersContractFailure
            ? $failure->getStage()
            : 'preparacao';

        if (!self::debugEnabled()) {
            return $report;
        }

        $cause = $failure instanceof OracleAdaptersContractFailure
            ? $failure->getCause()
            : $failure;
        $report['falha']['tipo'] = $cause::class;
        $report['falha']['mensagem'] = self::technicalMessage($cause, $sensitiveValues);

        return $report;
    }

    public static function debugEnabled(): bool
    {
        $value = getenv('PCA_SPRINT59_DEBUG_ORACLE');

        return is_string($value) && strtolower(trim($value)) === 'true';
    }

    /** @param string[] $sensitiveValues */
    private static function technicalMessage(Throwable $cause, array $sensitiveValues): string
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
            return 'Falha tecnica sem mensagem segura disponivel.';
        }

        if (strlen($sanitized) <= 1000) {
            return $sanitized;
        }

        if (preg_match('/\A.{0,997}/us', $sanitized, $match) === 1) {
            return $match[0] . '...';
        }

        return 'Falha tecnica sem mensagem segura disponivel.';
    }
}
