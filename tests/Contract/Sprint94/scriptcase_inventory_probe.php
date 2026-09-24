<?php

declare(strict_types=1);

use Tests\Contract\Sprint94\Support\Sprint94ConfigurationException;
use Tests\Contract\Sprint94\Support\Sprint94InventoryGate;
use Tests\Contract\Sprint94\Support\Sprint94InventoryException;
use Tests\Contract\Sprint94\Support\Sprint94Report;

/** @return array<string, mixed> */
function executar_precheck_sprint_94(object $db, string $login, string $serverName): array
{
    try {
        return Sprint94InventoryGate::fromEnvironment($db, $login, $serverName)->precheck();
    } catch (Throwable $failure) {
        return sprint94_inventory_failure($failure, 'PRECHECK_RECUSADO');
    }
}

/** @return array<string, mixed> */
function executar_inventario_sprint_94(object $db, string $login, string $serverName): array
{
    try {
        return Sprint94InventoryGate::fromEnvironment($db, $login, $serverName)->inventory();
    } catch (Throwable $failure) {
        return sprint94_inventory_failure($failure, 'INVENTARIO_INCONCLUSIVO');
    }
}

/** @return array<string, mixed> */
function sprint94_inventory_failure(Throwable $failure, string $result): array
{
    $code = $failure instanceof Sprint94ConfigurationException || $failure instanceof Sprint94InventoryException
        ? $failure->getErrorCode()
        : 'GATE_SPRINT94_FALHA_SEGURA';

    return Sprint94Report::create([
        'codigo' => $code,
        'escritas' => ['logs' => false, 'oracle' => false, 'pncp' => false],
        'resultado' => $result,
        'sprint' => '9.4',
    ]);
}
