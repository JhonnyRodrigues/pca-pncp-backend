<?php

declare(strict_types=1);

use Tests\Contract\Sprint84\Support\Sprint84ConfigurationException;
use Tests\Contract\Sprint84\Support\Sprint84GateException;
use Tests\Contract\Sprint85\Support\Sprint85Precheck;

/**
 * Executa somente o PRECHECK Oracle da Sprint 8.5. O host já carregou o
 * autoloader e fornece a conexão ADOdb emprestada.
 *
 * @return array<string, mixed>
 */
function executar_precheck_sprint_85(
    object $db,
    string $login,
    string $serverName
): array {
    try {
        return Sprint85Precheck::fromEnvironment($db, $login, $serverName)->run();
    } catch (Throwable $failure) {
        $code = match (true) {
            $failure instanceof Sprint84ConfigurationException => $failure->getErrorCode(),
            $failure instanceof Sprint84GateException => $failure->getErrorCode(),
            default => 'PRECHECK_FALHA_INESPERADA',
        };

        return [
            'sprint' => '8.5',
            'resultado' => 'PRECHECK_RECUSADO',
            'codigo' => $code,
            'mensagem' => 'O PRECHECK foi recusado sem executar o gate real.',
            'oracle_writes' => false,
            'pncp_access' => false,
            'pncp_writes' => false,
            'log_writes' => false,
        ];
    }
}
