<?php

declare(strict_types=1);

/**
 * Configuracao externa sem segredos para o gate de contencao no validaSGI.
 * Copie para sprint85.php fora do artefato e mantenha PRECHECK ate fixar o
 * SHA-256 retornado pela propria Blank.
 */
return [
    'allow_oracle_writes' => false,
    'allow_pncp_writes' => false,
    'allow_log_writes' => false,
    'server_name' => 'validasgi.semaepiracicaba.sp.gov.br',
    'modo' => 'CONSOLIDADO',
    'ano' => 2026,
    'etapa_autorizada' => 'PRECHECK',
    'unidades' => [
        [
            'id_unidade_orcamentaria' => 3,
            'quantidade_versoes' => 3,
            'quantidade_fotografia' => 3,
            'quantidade_demandas' => 3,
        ],
        [
            'id_unidade_orcamentaria' => 94,
            'quantidade_versoes' => 38,
            'quantidade_fotografia' => 38,
            'quantidade_demandas' => 38,
        ],
        [
            'id_unidade_orcamentaria' => 149,
            'quantidade_versoes' => 71,
            'quantidade_fotografia' => 71,
            'quantidade_demandas' => 71,
        ],
    ],
    'oracle_checkpoint_sha256' => null,
    'identificador_log' => 'SPRINT85-CONTENCAO-HML-20260921-JSILVA-01',
    'quantidade_logs_esperada' => 0,
    'sequenciais_legados' => [16, 17, 18],
    'sequencial_pca_excluido' => 36,
    'id_plano_sem_confirmacao' => 103,
    'baseline_auditoria' => 0,
];
