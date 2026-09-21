<?php

declare(strict_types=1);

use Tests\Contract\Sprint85\Support\Sprint85Config;
use Tests\Contract\Sprint85\Support\Sprint85OracleQueries;
use Tests\Contract\Sprint85\Support\Sprint85Precheck;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

if ($argc !== 2) {
    fwrite(STDERR, 'Uso: php render-manual-queries.php LOGIN_SGI' . PHP_EOL);
    exit(1);
}

$configuration = Sprint85Config::loadFromEnvironment();
$profile = Sprint85Config::loadHomologationProfile(
    $configuration,
    (string) $configuration['server_name']
);
$queries = (new Sprint85OracleQueries($profile, $configuration, $argv[1]))->all();
Sprint85Precheck::assertReadOnlyQueries($queries);

echo json_encode(
    $queries,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
), PHP_EOL;
