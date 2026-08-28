<?php

declare(strict_types=1);

use Tests\Contract\Sprint59\Support\OracleAdaptersContractRunner;
use Tests\Contract\Sprint59\Support\OracleAdaptersFailureReport;
use Tests\Contract\Sprint59\Support\SafeContractReport;
use Tests\Contract\Sprint59\Support\Sprint59Config;

/**
 * Entrypoint exclusivo de uma aplicacao Blank protegida do Scriptcase.
 * O host deve carregar vendor/autoload.php e passar a conexao ADOdb emprestada.
 * PCA_SPRINT59_DEBUG_ORACLE=true habilita causa tecnica sanitizada em falhas
 * do modo adapters; sem o opt-in, a mensagem permanece generica.
 *
 * @return array<string, mixed>
 */
function executar_contratos_adapters_oracle_sprint_59(
    object $db,
    string $login,
    string $mode = 'adapters'
): array {
    $sensitiveValues = [$login];

    try {
        $config = Sprint59Config::loadFromEnvironment();
        $oracle = Sprint59Config::oracle($config);
        $sensitiveValues[] = $oracle['mensagem_auditoria'];
        $report = OracleAdaptersContractRunner::fromConfig($db, $login, $config)->run($mode);
        SafeContractReport::encode($report, $sensitiveValues);
        $report['sha256_conteudo_sanitizado'] = SafeContractReport::hash($report);

        return $report;
    } catch (Throwable $exception) {
        $report = OracleAdaptersFailureReport::create(
            $mode,
            $exception,
            $sensitiveValues
        );
        SafeContractReport::encode($report, $sensitiveValues);

        return $report;
    }
}
