<?php

declare(strict_types=1);

use Tests\Contract\Sprint85\Support\Sprint85Config;
use Tests\Contract\Sprint85\Support\Sprint85OracleQueries;
use Tests\Contract\Sprint85\Support\Sprint85Precheck;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

try {
    $configuration = Sprint85Config::loadFromEnvironment();
    $profile = Sprint85Config::loadHomologationProfile(
        $configuration,
        (string) $configuration['server_name']
    );
    $queries = (new Sprint85OracleQueries($profile, $configuration, 'LOGIN_PREENCHIDO_NO_GATE'))->all();
    Sprint85Precheck::assertReadOnlyQueries($queries);

    echo json_encode([
        'sprint' => '8.5',
        'preflight' => 'CONFIRMADO',
        'etapa_autorizada' => $configuration['etapa_autorizada'],
        'modo' => $configuration['modo'],
        'ano' => $configuration['ano'],
        'quantidade_consultas' => count($queries),
        'consultas_somente_leitura' => true,
        'ambiente_perfil' => $profile->getAmbiente()->value,
        'oracle_acessado' => false,
        'pncp_acessado' => false,
        'pacote_instalado' => false,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
} catch (Throwable) {
    fwrite(STDERR, json_encode([
        'sprint' => '8.5',
        'preflight' => 'RECUSADO',
        'mensagem' => 'A configuração externa ou o perfil de homologação é inválido.',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL);
    exit(1);
}
