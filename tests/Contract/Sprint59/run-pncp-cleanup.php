<?php

declare(strict_types=1);

use Tests\Contract\Sprint59\Support\PncpAdaptersContractRunner;
use Tests\Contract\Sprint59\Support\SafeContractReport;
use Tests\Contract\Sprint59\Support\Sprint59Config;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

try {
    $config = Sprint59Config::loadFromEnvironment();
    $pncp = Sprint59Config::pncp($config);
    $report = PncpAdaptersContractRunner::fromConfig($config)->cleanupOwnedResidue();
    $json = SafeContractReport::encode($report, [
        $pncp['login'],
        $pncp['senha'],
        $pncp['cnpj'],
        $pncp['codigo_uasg'],
        $pncp['descricao_uasg'],
    ]);

    fwrite(STDOUT, $json . PHP_EOL . 'SHA256: ' . hash('sha256', $json) . PHP_EOL);
} catch (Throwable $failure) {
    fwrite(STDERR, 'Limpeza PNCP Sprint 5.9 recusada: ' . $failure->getMessage() . PHP_EOL);
    exit(1);
}
