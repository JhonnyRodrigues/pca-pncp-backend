<?php

declare(strict_types=1);

/**
 * Modelo sem segredos para os fluxos completos da Sprint 8.4.
 *
 * Copie para um arquivo fora do repositório e do webroot, restrinja sua
 * leitura e informe o caminho absoluto em PCA_SPRINT84_CONFIG_FILE.
 * Perfil, CNPJ, credenciais e mapeamentos permanecem no perfil final.
 */
return [
    // PRECHECK: ambas false. NENHUMA_ACAO Oracle-only: true/false.
    // PUBLICAR, RETIFICAR e EXCLUIR_PCA: ambas true, após autorização.
    'allow_oracle_writes' => false,
    'allow_pncp_writes' => false,
    'server_name' => '',
    'modo' => 'INDIVIDUAL',
    'ano' => 0,
    'etapa_autorizada' => 'PRECHECK',
    'unidades' => [
        [
            'id_unidade_orcamentaria' => 0,
            'quantidade_versoes' => 0,
            'quantidade_fotografia' => 0,
            'quantidade_demandas' => 0,
            'inclusoes_retificacao' => 0,
            'exclusoes_retificacao' => 0,
        ],
        [
            'id_unidade_orcamentaria' => 0,
            'quantidade_versoes' => 0,
            'quantidade_fotografia' => 0,
            'quantidade_demandas' => 0,
            'inclusoes_retificacao' => 0,
            'exclusoes_retificacao' => 0,
        ],
    ],
    // null é aceito somente no PRECHECK inicial; etapas reais exigem SHA-256.
    'oracle_checkpoint_sha256' => null,
    'estado_pncp_esperado' => 'AUSENTE',
    // Preencha somente depois de identificar e autorizar um resíduo específico.
    'sequenciais_limpeza_autorizados' => [],
    'baseline_auditoria' => 0,
];
