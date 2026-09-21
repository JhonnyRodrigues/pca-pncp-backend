<?php

declare(strict_types=1);

/**
 * Modelo sem segredos para os gates da Sprint 8.5.
 *
 * Copie para fora do repositório e do webroot. Informe seu caminho absoluto
 * em PCA_SPRINT85_CONFIG_FILE. Perfil, CNPJ e credenciais permanecem no perfil
 * externo selecionado por PCA_PNCP_PROFILE_REGISTRY_FILE.
 */
return [
    
    'allow_oracle_writes' => false, // Somente SMOKE usa true.
    'allow_pncp_writes' => false, // Permanece false em todos os gates da Sprint 8.5.
    'allow_log_writes' => false,
    'server_name' => '',
    'modo' => 'CONSOLIDADO',
    'ano' => 2026,
    'etapa_autorizada' => 'PRECHECK', // PRECHECK, LOGS, CONTENCAO ou SMOKE.
    'unidades' => [
        [
            'id_unidade_orcamentaria' => 0,
            'quantidade_versoes' => 0,
            'quantidade_fotografia' => 0,
            'quantidade_demandas' => 0,
        ],
        [
            'id_unidade_orcamentaria' => 0,
            'quantidade_versoes' => 0,
            'quantidade_fotografia' => 0,
            'quantidade_demandas' => 0,
        ],
    ],
    
    'oracle_checkpoint_sha256' => null, // null é aceito somente no PRECHECK inicial.
    
    'identificador_log' => 'SPRINT85-PREENCHER', // Identificador único preparado para localizar somente os logs deste gate.
    'quantidade_logs_esperada' => 0,
    'sequenciais_legados' => [16, 17, 18],
    'sequencial_pca_excluido' => 36,
    'id_plano_sem_confirmacao' => 103,
    'baseline_auditoria' => 0,
];
