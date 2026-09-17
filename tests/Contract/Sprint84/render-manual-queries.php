<?php

declare(strict_types=1);

use Semae\Pca\Composition\CarregadorPerfilPublicacaoPca;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Tests\Contract\Sprint84\Support\OracleCheckpointQueries;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

if ($argc !== 8) {
    fwrite(
        STDERR,
        'Uso: php render-manual-queries.php SERVER LOGIN ANO UO1 FOTO1 UO2 FOTO2' . PHP_EOL
    );
    exit(1);
}

[, $server, $login, $year, $unit1, $photo1, $unit2, $photo2] = $argv;
$configuration = [
    'modo' => 'INDIVIDUAL',
    'ano' => (int) $year,
    'unidades' => [
        [
            'id_unidade_orcamentaria' => (int) $unit1,
            'quantidade_versoes' => 1,
            'quantidade_fotografia' => (int) $photo1,
            'quantidade_demandas' => (int) $photo1,
        ],
        [
            'id_unidade_orcamentaria' => (int) $unit2,
            'quantidade_versoes' => 1,
            'quantidade_fotografia' => (int) $photo2,
            'quantidade_demandas' => (int) $photo2,
        ],
    ],
    'baseline_auditoria' => 0,
];
$rawProfile = (new CarregadorPerfilPublicacaoPca())->carregar($server);
$queries = (new OracleCheckpointQueries(
    new PerfilPublicacaoPca($rawProfile),
    $configuration,
    $login
))->all();

echo json_encode(
    $queries,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
), PHP_EOL;
