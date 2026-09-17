<?php

declare(strict_types=1);

use Tests\Contract\Sprint84\Support\Sprint84DiagnosticReport;
use Tests\Contract\Sprint84\Support\Sprint84ErrorReport;
use Tests\Contract\Sprint84\Support\Sprint84GateException;
use Tests\Contract\Sprint84\Support\Sprint84Runner;

/**
 * Entrypoint exclusivo da Blank protegida da Sprint 8.4.
 * O host fornece a conexão ADOdb emprestada e já carregou vendor/autoload.php.
 *
 * @return array<string, mixed>
 */
function executar_fluxos_completos_sprint_84(
    object $db,
    string $login,
    string $serverName,
    string $requestId,
    string $etapa
): array {
    $runner = null;

    try {
        $runner = Sprint84Runner::fromEnvironment(
            $db,
            $login,
            $serverName,
            $requestId,
            $etapa
        );

        return $runner->run();
    } catch (Throwable $failure) {
        $fase = $failure instanceof Sprint84GateException
            ? $failure->getPhase()
            : 'CONFIGURACAO';

        $report = Sprint84ErrorReport::fromThrowable($fase, $failure);
        if ($runner instanceof Sprint84Runner) {
            $report['contexto_gate'] = $runner->failureContext();
        }

        return Sprint84DiagnosticReport::finish($report);
    }
}

/**
 * Recuperação separada. Com a lista padrão vazia, recusa antes de autenticar.
 *
 * @return array<string, mixed>
 */
function executar_limpeza_emergencial_sprint_84(
    object $db,
    string $login,
    string $serverName,
    string $requestId
): array {
    $runner = null;

    try {
        $runner = Sprint84Runner::fromEnvironment(
            $db,
            $login,
            $serverName,
            $requestId,
            'PRECHECK'
        );

        return $runner->cleanupEmergency();
    } catch (Throwable $failure) {
        $fase = $failure instanceof Sprint84GateException
            ? $failure->getPhase()
            : 'LIMPEZA_EMERGENCIAL';

        $report = Sprint84ErrorReport::fromThrowable($fase, $failure);
        if ($runner instanceof Sprint84Runner) {
            $report['contexto_gate'] = $runner->failureContext();
        }

        return Sprint84DiagnosticReport::finish($report);
    }
}
