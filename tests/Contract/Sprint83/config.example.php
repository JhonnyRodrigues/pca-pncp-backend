<?php

declare(strict_types=1);

/**
 * Modelo sem segredos para a suíte PNCP da Sprint 8.3.
 *
 * Copie para um arquivo fora do repositório e do webroot, restrinja sua
 * leitura e informe o caminho absoluto em PCA_SPRINT83_CONFIG_FILE.
 * Credenciais, CNPJ, ambiente e limites permanecem no perfil final.
 */
return [
    'allow_writes' => false,
    'server_name' => '', // exemplo: 'dev.com.br'
    'massa' => [
        'ano' => 0,
        'data_desejada' => 'AAAA-MM-DD',

        /*O gate exige que não exista candidato para essa combinação antes de publicar. Ele não tenta misturar a massa de teste em um PCA preexistente.*/
        'codigo_uasg' => '',
        'descricao_uasg' => 'Massa dedicada Sprint 8.3',

        // Os três IDs abaixo representam três demandas artificiais diferentes usadas para testar o ciclo de vida do PCA.
        'id_demanda_publicacao_1' => 0, // primeiro item enviado na publicação (criação do PCA).
        'id_demanda_publicacao_2' => 0, // segundo item enviado junto na criação.
        'id_demanda_inclusao' => 0, //terceiro item, enviado depois por uma chamada separada de inclusão (retificação).

        /* O ciclo nominal pretendido é:
        Criar PCA com itens 1 e 2
                ↓
        Consultar e testar paginação
                ↓
        Adicionar o item 3
                ↓
        Excluir o item 2
                ↓
        Excluir todo o PCA
                ↓
        Confirmar ausência
        */

        /** quando preenchido, autoriza a limpeza isolada de resíduo conhecido, ou seja, exclui o PCA inteiro com o referido "sequencialPca" */
        'residuo_sequencial_autorizado' => null,

        /*Esse bloco é um modelo comum para construir as três demandas artificiais:*/
        'item' => [
            'categoria' => 0, // 1 para material ou 2 para serviço
            'catalogo' => 0, // 1 para catálogo do Compras.gov.br ou 2 para outros catálogos
            'unidade_medida' => '',
            'codigo_item' => '',
            'grupo' => '',
            'descricao_grupo' => '',
            'origem' => 'N', // N para demanda normal ou E para extraordinária
        ],
    ],
];