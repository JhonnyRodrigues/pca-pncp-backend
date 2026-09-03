<?php

declare(strict_types=1);

use Tests\Contract\Sprint83\Support\Sprint83Config;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

try {
    $configuracao = Sprint83Config::loadFromEnvironment();
    Sprint83Config::loadHomologationProfile(
        $configuracao,
        $configuracao['server_name']
    );

    echo json_encode([
        'sprint' => '8.3',
        'preflight' => 'CONFIRMADA',
        'manual_pncp_versao' => Sprint83Config::MANUAL_PNCP_VERSAO,
        'manual_pncp_data' => Sprint83Config::MANUAL_PNCP_DATA,
        'openapi_versao' => Sprint83Config::OPENAPI_VERSAO,
        'contrato_revalidado_em' => Sprint83Config::CONTRATO_REVALIDADO_EM,
        'perfil_homologacao' => true,
        'escritas_pncp_autorizadas' => true,
        'oracle_acessado' => false,
        'pncp_acessado' => false,
        'segredos_exibidos' => false,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
} catch (Throwable) {
    fwrite(STDERR, 'Preflight Sprint 8.3 recusado sem exposição de detalhes.' . PHP_EOL);
    exit(1);
}
