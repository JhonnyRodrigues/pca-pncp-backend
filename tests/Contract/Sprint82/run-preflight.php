<?php

declare(strict_types=1);

use Tests\Contract\Sprint82\Support\Sprint82Config;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

try {
    $configuracao = Sprint82Config::loadFromEnvironment();
    Sprint82Config::loadHomologationProfile(
        $configuracao,
        $configuracao['server_name']
    );

    echo json_encode([
        'sprint' => '8.2',
        'preflight' => 'CONFIRMADA',
        'perfil_homologacao' => true,
        'escritas_oracle_autorizadas' => true,
        'oracle_acessado' => false,
        'pncp_acessado' => false,
        'segredos_exibidos' => false,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
} catch (Throwable) {
    fwrite(STDERR, 'Preflight Sprint 8.2 recusado sem exposição de detalhes.' . PHP_EOL);
    exit(1);
}
