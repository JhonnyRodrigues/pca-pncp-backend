<?php

declare(strict_types=1);

use Tests\Contract\Sprint83\Support\PncpTrainingContractRunner;
use Tests\Contract\Sprint83\Support\Sprint83Config;
use Tests\Contract\Support\SafeContractReport;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

try {
    $config = Sprint83Config::loadFromEnvironment();
    $profile = Sprint83Config::loadHomologationProfileArray(
        $config,
        $config['server_name']
    );
    $report = PncpTrainingContractRunner::fromEnvironment()->cleanupOwnedResidue();
    $json = SafeContractReport::encode($report, [
        $profile['cnpj'],
        $profile['pncp']['login'],
        $profile['pncp']['senha'],
        $config['server_name'],
        $config['massa']['codigo_uasg'],
        $config['massa']['descricao_uasg'],
    ]);

    echo $json, PHP_EOL, 'SHA256: ', hash('sha256', $json), PHP_EOL;
} catch (Throwable) {
    fwrite(STDERR, 'Limpeza Sprint 8.3 recusada sem exposição de detalhes.' . PHP_EOL);
    exit(1);
}
