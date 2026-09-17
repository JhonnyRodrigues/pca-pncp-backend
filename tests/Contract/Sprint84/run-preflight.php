<?php

declare(strict_types=1);

use Semae\Pca\Composition\PerfilPublicacaoPca;
use Tests\Contract\Sprint84\Support\Sprint84Config;
use Tests\Contract\Sprint84\Support\Sprint84ErrorReport;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

try {
    $configuration = Sprint84Config::loadFromEnvironment();
    $rawProfile = Sprint84Config::loadHomologationProfileArray(
        $configuration,
        $configuration['server_name']
    );
    $profile = new PerfilPublicacaoPca($rawProfile);

    echo json_encode([
        'sprint' => '8.4',
        'preflight' => 'CONFIRMADO',
        'configuracao' => $configuration,
        'perfil' => [
            'ambiente' => $profile->getAmbiente()->value,
            'cnpj' => $rawProfile['cnpj'],
            'login_pncp' => $profile->getLoginPncp(),
        ],
        'consultas_oracle_executadas' => false,
        'oracle_acessado' => false,
        'pncp_acessado' => false,
        'escritas_oracle_autorizadas' => true,
        'escritas_pncp_autorizadas' => true,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
} catch (Throwable $failure) {
    fwrite(
        STDERR,
        json_encode(
            Sprint84ErrorReport::fromThrowable('CONFIGURACAO', $failure),
            JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_INVALID_UTF8_SUBSTITUTE
        ) . PHP_EOL
    );
    exit(1);
}
