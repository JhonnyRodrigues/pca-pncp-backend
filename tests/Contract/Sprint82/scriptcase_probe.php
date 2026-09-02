<?php

declare(strict_types=1);

use Tests\Contract\Sprint82\Support\OracleContractRunner;
use Tests\Contract\Sprint82\Support\OracleFailureReport;
use Tests\Contract\Sprint82\Support\Sprint82Config;
use Tests\Contract\Support\SafeContractReport;

/**
 * Entrypoint exclusivo de uma aplicacao Blank protegida do Scriptcase.
 * O host deve carregar vendor/autoload.php e fornecer a conexao ADOdb emprestada.
 *
 * @return array<string, mixed>
 */
function executar_integracao_oracle_sprint_82(
    object $db,
    string $login,
    string $serverName,
    string $mode = 'adapters'
): array {
    $sensitiveValues = [$login, $serverName];

    try {
        $config = Sprint82Config::loadFromEnvironment();
        $sensitiveValues[] = $config['massa']['mensagem_auditoria'];
        $report = OracleContractRunner::fromConfig(
            $db,
            $login,
            $serverName,
            $config
        )->run($mode);
        SafeContractReport::encode($report, $sensitiveValues);
        $report['sha256_conteudo_sanitizado'] = SafeContractReport::hash($report);

        return $report;
    } catch (Throwable $failure) {
        $report = OracleFailureReport::create($mode, $failure, $sensitiveValues);
        SafeContractReport::encode($report, $sensitiveValues);

        return $report;
    }
}
