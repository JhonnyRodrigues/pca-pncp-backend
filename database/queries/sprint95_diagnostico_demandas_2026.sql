-- Sprint 9.5: diagnostico somente leitura de demandas de 2026 que o SGI
-- apresenta como homologadas, mas que nao constam na fotografia exportada.
-- Executar no DBeaver, na mesma conexao de producao usada no precheck.
-- Nao executar DML nem acionar a Blank para investigar esta divergencia.

-- 01 Identificar a conexao antes de comparar resultados do SGI e do backend.
SELECT SYS_CONTEXT('USERENV', 'DB_NAME') AS BANCO,
       SYS_CONTEXT('USERENV', 'SERVICE_NAME') AS SERVICO,
       SYS_CONTEXT('USERENV', 'CURRENT_SCHEMA') AS ESQUEMA
  FROM DUAL;

-- 02 Amostras dos itens normais e extraordinarios das UASGs 205 e 207.
-- Procurar os IDs diretamente em PAC_DEMANDA. LEFT JOIN preserva a linha
-- mesmo quando o Plano de origem nao aparece em PAC_PLANOS.
WITH IDS AS (
    SELECT 411 AS ID_DEMANDA FROM DUAL UNION ALL
    SELECT 412 FROM DUAL UNION ALL
    SELECT 413 FROM DUAL UNION ALL
    SELECT 414 FROM DUAL UNION ALL
    SELECT 415 FROM DUAL UNION ALL
    SELECT 418 FROM DUAL UNION ALL
    SELECT 419 FROM DUAL UNION ALL
    SELECT 434 FROM DUAL UNION ALL
    SELECT 795 FROM DUAL UNION ALL
    SELECT 796 FROM DUAL UNION ALL
    SELECT 797 FROM DUAL UNION ALL
    SELECT 814 FROM DUAL UNION ALL
    SELECT 879 FROM DUAL UNION ALL
    SELECT 912 FROM DUAL
)
SELECT IDS.ID_DEMANDA AS ID_PROCURADO,
       D.ID_DEMANDA,
       D.SITUACAO,
       D.FK_PLANO,
       P.ID_PLANO,
       P.ANO_EXERCICIO,
       P.FK_UNIDADE_ORCAMENTARIA,
       P.VERSAO,
       P.DATA_HOMOLOGACAO
  FROM IDS
  LEFT JOIN SGI.PAC_DEMANDA D
    ON D.ID_DEMANDA = IDS.ID_DEMANDA
  LEFT JOIN SGI.PAC_PLANOS P
    ON P.ID_PLANO = D.FK_PLANO
 ORDER BY IDS.ID_DEMANDA;

-- 03 Comparar pistas fisicas de origem e lotacao. Estes campos nao provam,
-- isoladamente, que uma Demanda pertence a uma UO e a um exercicio.
SELECT D.ID_DEMANDA,
       D.SITUACAO,
       D.ORIGEM,
       D.VINCULACAO_DEPENDENCIA,
       D.FK_PLANO,
       D.FK_LOTACAO_SOLICITANTE,
       D.FK_LOTACAO_CONSOLIDADORA,
       D.FK_LOTACAO_AVALIADORA
  FROM SGI.PAC_DEMANDA D
 WHERE D.ID_DEMANDA IN (411, 414, 795, 814)
 ORDER BY D.ID_DEMANDA;

-- 04 Localizar possiveis tabelas/colunas de relacionamento no dicionario.
-- Nenhuma delas sera adotada como regra de pertencimento sem verificar o SGI.
SELECT C.TABLE_NAME,
       C.COLUMN_NAME,
       C.DATA_TYPE
  FROM ALL_TAB_COLUMNS C
 WHERE C.OWNER = 'SGI'
   AND C.TABLE_NAME LIKE 'PAC_%'
   AND (
       C.COLUMN_NAME LIKE '%DEMANDA%'
       OR C.COLUMN_NAME LIKE '%EXERCICIO%'
       OR C.COLUMN_NAME IN ('FK_PLANO', 'FK_UNIDADE_ORCAMENTARIA')
   )
 ORDER BY C.TABLE_NAME, C.COLUMN_ID;
