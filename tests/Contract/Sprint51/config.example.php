<?php

declare(strict_types=1);

/**
 * Modelo sem segredos para o gate contratual da Sprint 5.1.
 *
 * Copie este conteúdo para um arquivo fora do repositório e do webroot,
 * restrinja sua leitura ao usuário do processo e informe o caminho em
 * PCA_SPRINT51_CONFIG_FILE. Nunca versione o arquivo preenchido.
 */
return [
    'pncp' => [
        'base_url' => 'https://treina.pncp.gov.br/api/pncp',
        // Opcional: caminho absoluto para um CA bundle legível pelo PHP/cURL.
        'ca_bundle' => null,
        'login' => '',
        'senha' => '',
        'cnpj' => '',
        'codigo_unidade' => '',
        'ano' => 0,
        'data_desejada' => 'AAAA-MM-DD',
        'numero_item_base' => 0,
        'marcador_massa' => 'PCA-S51-',
        // Preencher somente para limpar resíduo conhecido de execução anterior.
        'owned_residue_sequence' => null,
        'allow_writes' => false,
        'allow_large_batch' => false,
        'connect_timeout_seconds' => 5,
        'read_timeout_seconds' => 30,
        'write_timeout_seconds' => 60,
        // Limites exclusivos do probe; não são defaults da futura Infrastructure.
        'max_response_bytes' => 8388608,
        'page_size' => 100,
        'item_template' => [
            'categoriaItemPca' => 0,
            'unidadeFornecimento' => '',
            'unidadeRequisitante' => '',
            'grupoContratacaoCodigo' => '',
            'grupoContratacaoNome' => '',
            'catalogo' => 0,
            'classificacaoCatalogo' => '1',
            'codigoItem' => '',
            'classificacaoSuperiorCodigo' => '',
            'classificacaoSuperiorNome' => '',
            'pdmCodigo' => null,
            'pdmDescricao' => null,
        ],
    ],
    'oracle' => [
        'schema_owner' => '',
        'tables' => [],
        'fixture' => [
            'table' => '',
            'key_column' => '',
            'key_value' => '',
            'value_column' => '',
            'probe_value' => 'SPRINT51_PROBE',
        ],
        'lock' => [
            'table' => '',
            'key_column' => '',
            'key_value' => '',
            'hold_seconds' => 30,
        ],
        // Cada consulta deve ser SELECT somente e retornar um único escalar.
        'aggregate_checks' => [
            [
                'label' => 'SUBSTITUIR_POR_VALIDACAO_REAL',
                'sql' => 'SELECT 1 FROM DUAL',
                'params' => [],
                'expect' => ['operator' => 'eq', 'value' => 1],
            ],
        ],
    ],
];
