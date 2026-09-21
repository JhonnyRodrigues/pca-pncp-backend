<?php

declare(strict_types=1);

use Tests\Contract\Sprint84\Support\Sprint84ConfigurationException;
use Tests\Contract\Sprint84\Support\Sprint84GateException;
use Tests\Contract\Sprint85\Support\Sprint85ContainmentGate;

/** @return array<string, mixed> */
function executar_precheck_contencao_sprint_85(
    object $db,
    string $login,
    string $serverName
): array {
    try {
        return Sprint85ContainmentGate::fromEnvironment($db, $login, $serverName)->precheck();
    } catch (Throwable $failure) {
        return sprint85_relatorio_falha_contencao($failure, 'PRECHECK_CONTENCAO_RECUSADO');
    }
}

/** @return array<string, mixed> */
function executar_contencao_sprint_85(
    object $db,
    string $login,
    string $serverName,
    string $requestId
): array {
    try {
        return Sprint85ContainmentGate::fromEnvironment($db, $login, $serverName)
            ->executar($requestId);
    } catch (Throwable $failure) {
        return sprint85_relatorio_falha_contencao($failure, 'CONTENCAO_NAO_CONFIRMADA');
    }
}

/** @return array<string, mixed> */
function verificar_disponibilidade_contencao_sprint_85(
    object $db,
    string $login,
    string $serverName
): array {
    try {
        return Sprint85ContainmentGate::fromEnvironment($db, $login, $serverName)
            ->verificarDisponibilidade();
    } catch (Throwable $failure) {
        return sprint85_relatorio_falha_contencao($failure, 'DISPONIBILIDADE_NAO_CONFIRMADA');
    }
}

/** @return array<string, mixed> */
function sprint85_relatorio_falha_contencao(Throwable $failure, string $result): array
{
    $code = match (true) {
        $failure instanceof Sprint84ConfigurationException => $failure->getErrorCode(),
        $failure instanceof Sprint84GateException => $failure->getErrorCode(),
        default => 'GATE_CONTENCAO_FALHA_INESPERADA',
    };

    return [
        'sprint' => '8.5',
        'resultado' => $result,
        'codigo' => $code,
        'mensagem' => 'O gate de contencao foi interrompido com seguranca.',
        'oracle_writes' => false,
        'pncp_writes' => false,
        'log_writes' => $code === 'REGISTRO_EVENTOS_FALHOU',
    ];
}
