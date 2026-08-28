<?php

declare(strict_types=1);

use Tests\Contract\Sprint510\Support\OracleCancellationEvidenceInvestigationRunner;
use Tests\Contract\Sprint510\Support\SafeInvestigationReport;
use Tests\Contract\Sprint510\Support\Sprint510Config;

/**
 * Entrypoint exclusivo da investigacao read-only em uma Blank protegida.
 * O host deve carregar o autoloader e passar sua conexao ADOdb emprestada.
 *
 * @return array<string, mixed>
 */
function executar_investigacao_evidencia_cancelamento_oracle_sprint_510(
    object $db,
    string $login,
    string $mode = 'metadata'
): array {
    $sensitiveValues = [$login];

    try {
        $config = Sprint510Config::loadFromEnvironment();
        $oracle = Sprint510Config::oracle($config);
        $sensitiveValues = [...$sensitiveValues, ...Sprint510Config::sensitiveValues($oracle)];
        $report = OracleCancellationEvidenceInvestigationRunner::fromConfig($db, $config)->run($mode);
        SafeInvestigationReport::encode($report, $sensitiveValues);
        $report['sha256_conteudo_sanitizado'] = SafeInvestigationReport::hash($report);

        return $report;
    } catch (Throwable) {
        return [
            'sprint' => '5.10',
            'gate' => 'ORACLE_INVESTIGACAO_EVIDENCIA_CANCELAMENTO',
            'modo' => in_array($mode, Sprint510Config::modes(), true) ? $mode : 'INVALIDO',
            'confirmado' => false,
            'somente_leitura' => true,
            'close_invocado' => false,
            'falha' => [
                'codigo' => 'INVESTIGACAO_ORACLE_SPRINT_510_FALHOU',
                'etapa' => 'preflight',
                'mensagem' => 'A investigacao Oracle foi recusada sem exposicao de detalhes internos.',
            ],
        ];
    }
}
