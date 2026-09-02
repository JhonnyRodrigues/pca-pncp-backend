<?php

declare(strict_types=1);

/**
 * Modelo sem segredos para a integração Oracle real da Sprint 8.2.
 *
 * Copie para um arquivo fora do repositório e do webroot, restrinja sua
 * leitura e informe o caminho absoluto em PCA_SPRINT82_CONFIG_FILE.
 * Registro, perfil, mapeamentos e credenciais continuam externos e são
 * localizados exclusivamente por PCA_PNCP_PROFILE_REGISTRY_FILE.
 */
return [
    'allow_writes' => false,
    'server_name' => '',
    'massa' => [
        'ano' => 0,
        'id_unidade_orcamentaria' => 0,
        'sequencial_teste' => 0,
        'mensagem_auditoria' => 'PCA-S82-INTEGRACAO-ORACLE',
        'lock_hold_seconds' => 30,
    ],
];
