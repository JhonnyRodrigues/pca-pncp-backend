<?php

declare(strict_types=1);

use Tests\Contract\Sprint84\Support\Sprint84ConfigurationException;
use Tests\Contract\Sprint84\Support\Sprint84GateException;
use Tests\Contract\Sprint85\Support\Sprint85LogGate;

/** @return array<string, mixed> */
function executar_precheck_logs_sprint_85(object $db, string $serverName): array
{
    try {
        return Sprint85LogGate::fromEnvironment($db, $serverName)->precheck();
    } catch (Throwable $failure) {
        return sprint85_relatorio_falha_logs($failure, 'PRECHECK_LOGS_RECUSADO', false);
    }
}

/** @return array{json: string, bytes: int, sha256: string, identificador_log: string} */
function preparar_log_sintetico_sprint_85(
    object $db,
    string $serverName,
    string $correlationId
): array {
    return Sprint85LogGate::fromEnvironment($db, $serverName)->prepare($correlationId);
}

/** @return array<string, mixed> */
function verificar_log_sintetico_sprint_85(
    object $db,
    string $serverName,
    string $expectedJson
): array {
    return Sprint85LogGate::fromEnvironment($db, $serverName)->verify($expectedJson);
}

function persistir_log_sintetico_sprint_85(
    object $db,
    string $serverName,
    string $json,
    string $login,
    string $aplicacao
): void {
    Sprint85LogGate::fromEnvironment($db, $serverName)->persistir(
        $json,
        $login,
        $aplicacao
    );
}

/** @return array<string, mixed> */
function sprint85_relatorio_falha_logs(
    Throwable $failure,
    string $result,
    bool $logWriteAttempted
): array {
    $code = match (true) {
        $failure instanceof Sprint84ConfigurationException => $failure->getErrorCode(),
        $failure instanceof Sprint84GateException => $failure->getErrorCode(),
        default => 'GATE_LOGS_FALHA_INESPERADA',
    };

    return [
        'sprint' => '8.5',
        'resultado' => $result,
        'codigo' => $code,
        'mensagem' => 'O gate de logs foi interrompido com segurança.',
        'oracle_writes' => false,
        'pncp_access' => false,
        'pncp_writes' => false,
        'log_writes' => $logWriteAttempted,
    ];
}
