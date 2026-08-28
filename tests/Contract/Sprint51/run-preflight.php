<?php

declare(strict_types=1);

use Tests\Contract\Sprint51\Support\Sprint51Config;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$target = $argv[1] ?? 'all';

try {
    $config = Sprint51Config::loadFromEnvironment();
    $validated = [];

    if ($target === 'all' || $target === 'pncp') {
        Sprint51Config::pncp($config);
        $validated[] = 'PNCP_TREINAMENTO';
    }
    if ($target === 'all' || $target === 'oracle') {
        Sprint51Config::oracle($config);
        $validated[] = 'ORACLE_SCRIPTCASE';
    }
    if ($validated === []) {
        throw new RuntimeException('Alvo deve ser all, pncp ou oracle.');
    }

    echo json_encode([
        'sprint' => '5.1',
        'preflight' => 'CONFIRMADA',
        'gates_validados' => $validated,
        'segredos_exibidos' => false,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'Preflight Sprint 5.1 recusado: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
