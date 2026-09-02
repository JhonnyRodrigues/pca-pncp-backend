<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint82\Support;

use Tests\Contract\Support\OracleContractFailure;
use Tests\Contract\Support\OracleTechnicalMessageSanitizer;
use Throwable;

final class OracleFailureReport
{
    private const MODES = [
        'commit',
        'adapters',
        'lock_holder',
        'lock_expect_blocked',
        'lock_expect_available',
    ];

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
            'sprint' => '8.2',
            'gate' => 'ORACLE_COMPOSITION_SCRIPTCASE',
            'modo' => $safeMode,
            'confirmado' => false,
            'falha' => [
                'codigo' => 'CONTRATO_ORACLE_SPRINT_82_FALHOU',
                'mensagem' => 'O contrato Oracle foi recusado sem exposicao de detalhes internos.',
                'etapa' => $failure instanceof OracleContractFailure
                    ? $failure->getStage()
                    : 'preparacao',
            ],
            'close_invocado' => false,
        ];

        if (!self::debugEnabled() || !in_array($safeMode, ['commit', 'adapters'], true)) {
            return $report;
        }

        $cause = $failure instanceof OracleContractFailure
            ? $failure->getCause()
            : $failure;
        $report['falha']['tipo'] = $cause::class;
        $report['falha']['mensagem'] = OracleTechnicalMessageSanitizer::sanitize(
            $cause,
            $sensitiveValues
        );

        return $report;
    }

    private static function debugEnabled(): bool
    {
        $value = getenv('PCA_SPRINT82_DEBUG_ORACLE');

        return is_string($value) && strtolower(trim($value)) === 'true';
    }
}
