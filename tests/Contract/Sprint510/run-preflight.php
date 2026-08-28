<?php

declare(strict_types=1);

use Tests\Contract\Sprint510\Support\Sprint510Config;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

try {
    $config = Sprint510Config::loadFromEnvironment();
    $oracle = Sprint510Config::oracle($config);

    echo json_encode([
        'sprint' => '5.10',
        'preflight' => 'CONFIRMADA',
        'gate' => 'ORACLE_INVESTIGACAO_EVIDENCIA_CANCELAMENTO',
        'modos_validados' => array_keys($oracle['modes']),
        'somente_leitura' => true,
        'segredos_exibidos' => false,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
} catch (Throwable $failure) {
    fwrite(STDERR, 'Preflight Sprint 5.10 recusado: ' . $failure->getMessage() . PHP_EOL);
    exit(1);
}
