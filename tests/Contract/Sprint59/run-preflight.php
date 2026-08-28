<?php

declare(strict_types=1);

use Tests\Contract\Sprint59\Support\Sprint59Config;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$target = $argv[1] ?? 'all';

try {
    $config = Sprint59Config::loadFromEnvironment();
    $validated = [];

    if ($target === 'all' || $target === 'pncp') {
        Sprint59Config::pncp($config);
        $validated[] = 'PNCP_TREINAMENTO';
    }
    if ($target === 'all' || $target === 'oracle') {
        Sprint59Config::oracle($config);
        $validated[] = 'ORACLE_SCRIPTCASE';
    }
    if ($validated === []) {
        throw new RuntimeException('Alvo deve ser all, pncp ou oracle.');
    }

    echo json_encode([
        'sprint' => '5.9',
        'preflight' => 'CONFIRMADA',
        'gates_validados' => $validated,
        'segredos_exibidos' => false,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'Preflight Sprint 5.9 recusado: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
