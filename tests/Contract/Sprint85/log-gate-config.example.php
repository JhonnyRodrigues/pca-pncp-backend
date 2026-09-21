<?php

declare(strict_types=1);

/**
 * Configuração sem segredos e exclusiva do Microciclo 6.
 * Copie para fora do repositório e do webroot.
 */
return [
    'allow_oracle_writes' => false,
    'allow_pncp_writes' => false,
    'allow_log_writes' => false,
    'server_name' => 'validasgi.semaepiracicaba.sp.gov.br',
    'charset_adodb' => 'Windows-1252',
    'etapa_autorizada' => 'PRECHECK',
    'oracle_checkpoint_sha256' => null,
    'identificador_log' => 'SPRINT85-LOGS-HML-20260921-JSILVA-02',
];
