-- Sprint 8.5 / Microciclo 7 / diagnostico anterior ao novo seed de HML.
-- Execute cada SELECT separadamente. Todas as consultas sao somente leitura.
-- Nenhuma consulta adquire lock ou altera dados.

-- 01 identidade_ambiente
-- Cardinalidade: exatamente 1.
-- Esperado: DATABASE_NAME=SGIHML, SERVICE_NAME=sgihml e CURRENT_SCHEMA=SGI.
SELECT SYS_CONTEXT('USERENV', 'DB_NAME') AS DATABASE_NAME,
       SYS_CONTEXT('USERENV', 'SERVICE_NAME') AS SERVICE_NAME,
       SYS_CONTEXT('USERENV', 'CURRENT_SCHEMA') AS CURRENT_SCHEMA,
       SYS_CONTEXT('USERENV', 'SESSION_USER') AS SESSION_USER
  FROM DUAL;

-- 02 validade_exercicio
-- Cardinalidade: exatamente 1; SITUACAO deve ser CONFORME.
SELECT EXTRACT(YEAR FROM SYSDATE) AS ANO_CORRENTE,
       2026 AS ANO_DA_MASSA,
       CASE
           WHEN 2026 IN (
               EXTRACT(YEAR FROM SYSDATE),
               EXTRACT(YEAR FROM SYSDATE) + 1
           ) THEN 'CONFORME'
           ELSE 'FORA_DA_RN_CAD_002'
       END AS SITUACAO
  FROM DUAL;

-- 03 configuracao_consolidada
-- Cardinalidade: exatamente 2; valores CONSOLIDADO e 930281.
SELECT c.CHAVE,
       c.VALOR,
       c.DESCRICAO
  FROM SGI.PAC_CONFIGURACOES c
 WHERE c.CHAVE IN (
       'ESTRATEGIA_PUBLICACAO_PLANOS',
       'UASG_CONSOLIDADORA'
 )
 ORDER BY c.CHAVE;

-- 04 candidatas_sem_plano_2026
-- Cardinalidade: zero ou mais.
-- Candidata somente quando PLANOS_2026=0 e DEMANDAS_2026=0.
SELECT u.ID_UNIDADE_ORCAMENTARIA,
       l.NOME_LOTACAO,
       u.CODIGO_UASG,
       u.DESCRICAO_UASG,
       COUNT(DISTINCT p.ID_PLANO) AS PLANOS_2026,
       COUNT(DISTINCT d.ID_DEMANDA) AS DEMANDAS_2026
  FROM SGI.PAC_UNIDADES_ORCAMENTARIAS u
  JOIN SGI.RH_LOTACOES l
    ON l.COD_LOTACAO = u.ID_UNIDADE_ORCAMENTARIA
   AND l.ATIVO = 1
  LEFT JOIN SGI.PAC_PLANOS p
    ON p.FK_UNIDADE_ORCAMENTARIA = u.ID_UNIDADE_ORCAMENTARIA
   AND p.ANO_EXERCICIO = 2026
  LEFT JOIN SGI.PAC_DEMANDA d
    ON d.FK_PLANO = p.ID_PLANO
 WHERE u.ATIVA = 'S'
 GROUP BY u.ID_UNIDADE_ORCAMENTARIA,
          l.NOME_LOTACAO,
          u.CODIGO_UASG,
          u.DESCRICAO_UASG
HAVING COUNT(DISTINCT p.ID_PLANO) = 0
   AND COUNT(DISTINCT d.ID_DEMANDA) = 0
 ORDER BY u.ID_UNIDADE_ORCAMENTARIA;

-- 05 fotografia_replicada_2026_por_uo
-- Cardinalidade: uma linha por UO que possua plano de 2026.
-- Esta consulta identifica dados replicados que o seed nao podera alterar.
SELECT p.FK_UNIDADE_ORCAMENTARIA,
       COUNT(DISTINCT p.ID_PLANO) AS PLANOS,
       COUNT(DISTINCT d.ID_DEMANDA) AS DEMANDAS,
       COUNT(DISTINCT CASE
           WHEN d.SITUACAO IN (2, 3, 5) THEN d.ID_DEMANDA
       END) AS DEMANDAS_ATIVAS,
       MIN(p.ID_PLANO) AS MENOR_ID_PLANO,
       MAX(p.ID_PLANO) AS MAIOR_ID_PLANO
  FROM SGI.PAC_PLANOS p
  LEFT JOIN SGI.PAC_DEMANDA d
    ON d.FK_PLANO = p.ID_PLANO
 WHERE p.ANO_EXERCICIO = 2026
 GROUP BY p.FK_UNIDADE_ORCAMENTARIA
 ORDER BY p.FK_UNIDADE_ORCAMENTARIA;

-- 06 colisoes_da_fotografia_anterior
-- Cardinalidade: exatamente 1.
-- Todos os valores devem ser zero antes de considerar a reutilizacao dos IDs.
SELECT (SELECT COUNT(*)
          FROM SGI.PAC_PLANOS p
         WHERE p.ID_PLANO IN (99, 100, 101, 102, 103, 104)) AS PLANOS,
       (SELECT COUNT(*)
          FROM SGI.PAC_DEMANDA d
         WHERE d.ID_DEMANDA IN (810, 811, 812)) AS DEMANDAS,
       (SELECT COUNT(*)
          FROM SGI.PAC_CONTROLES_CONCORRENCIA c
         WHERE c.ANO_EXECUCAO = 2026
           AND c.ESCOPO IN ('ESTRATEGIA_ANUAL', 'UNIDADE_EXTERNA')) AS CONTROLES,
       (SELECT COUNT(*)
          FROM SGI.PAC_HISTORICOS h
         WHERE UPPER(h.MENSAGEM) LIKE UPPER('Exercício: 2026;%')) AS AUDITORIAS
  FROM DUAL;

-- 07 estrutura_das_tabelas_da_massa
-- Cardinalidade: uma linha por coluna das quatro tabelas.
SELECT c.TABLE_NAME,
       c.COLUMN_ID,
       c.COLUMN_NAME,
       c.DATA_TYPE,
       c.DATA_LENGTH,
       c.CHAR_LENGTH,
       c.NULLABLE,
       c.DATA_DEFAULT,
       c.IDENTITY_COLUMN
  FROM ALL_TAB_COLUMNS c
 WHERE c.OWNER = 'SGI'
   AND c.TABLE_NAME IN (
       'PAC_PLANOS',
       'PAC_DEMANDA',
       'PAC_HISTORICOS',
       'PAC_CONTROLES_CONCORRENCIA'
   )
 ORDER BY c.TABLE_NAME, c.COLUMN_ID;

-- 08 constraints_das_tabelas_da_massa
-- Cardinalidade: zero ou mais.
SELECT co.TABLE_NAME,
       co.CONSTRAINT_NAME,
       co.CONSTRAINT_TYPE,
       co.STATUS,
       cc.COLUMN_NAME,
       cc.POSITION,
       co.R_OWNER,
       co.R_CONSTRAINT_NAME
  FROM ALL_CONSTRAINTS co
  LEFT JOIN ALL_CONS_COLUMNS cc
    ON cc.OWNER = co.OWNER
   AND cc.CONSTRAINT_NAME = co.CONSTRAINT_NAME
   AND cc.TABLE_NAME = co.TABLE_NAME
 WHERE co.OWNER = 'SGI'
   AND co.TABLE_NAME IN (
       'PAC_PLANOS',
       'PAC_DEMANDA',
       'PAC_HISTORICOS',
       'PAC_CONTROLES_CONCORRENCIA'
   )
 ORDER BY co.TABLE_NAME, co.CONSTRAINT_NAME, cc.POSITION;

-- 09 filhos_de_planos_e_demandas
-- Cardinalidade: zero ou mais.
-- Revela tabelas que precisarao entrar no backup e na limpeza do seed.
SELECT filho.TABLE_NAME AS TABELA_FILHA,
       filho.CONSTRAINT_NAME AS FK_FILHA,
       filho_col.COLUMN_NAME AS COLUNA_FILHA,
       pai.TABLE_NAME AS TABELA_PAI,
       pai_col.COLUMN_NAME AS COLUNA_PAI,
       filho.STATUS
  FROM ALL_CONSTRAINTS filho
  JOIN ALL_CONSTRAINTS pai
    ON pai.OWNER = filho.R_OWNER
   AND pai.CONSTRAINT_NAME = filho.R_CONSTRAINT_NAME
  JOIN ALL_CONS_COLUMNS filho_col
    ON filho_col.OWNER = filho.OWNER
   AND filho_col.CONSTRAINT_NAME = filho.CONSTRAINT_NAME
  JOIN ALL_CONS_COLUMNS pai_col
    ON pai_col.OWNER = pai.OWNER
   AND pai_col.CONSTRAINT_NAME = pai.CONSTRAINT_NAME
   AND pai_col.POSITION = filho_col.POSITION
 WHERE filho.OWNER = 'SGI'
   AND filho.CONSTRAINT_TYPE = 'R'
   AND pai.TABLE_NAME IN ('PAC_PLANOS', 'PAC_DEMANDA')
 ORDER BY pai.TABLE_NAME,
          filho.TABLE_NAME,
          filho.CONSTRAINT_NAME,
          filho_col.POSITION;

-- 10 triggers_das_tabelas_da_massa
-- Cardinalidade: zero ou mais.
SELECT t.TABLE_NAME,
       t.TRIGGER_NAME,
       t.STATUS,
       t.TRIGGERING_EVENT,
       t.TRIGGER_TYPE
  FROM ALL_TRIGGERS t
 WHERE t.TABLE_OWNER = 'SGI'
   AND t.TABLE_NAME IN (
       'PAC_PLANOS',
       'PAC_DEMANDA',
       'PAC_HISTORICOS',
       'PAC_CONTROLES_CONCORRENCIA'
   )
 ORDER BY t.TABLE_NAME, t.TRIGGER_NAME;

-- 11 projecao_operacional_2026
-- Cardinalidade: exatamente 1, inclusive quando nao houver planos.
-- TOTAL_PLANOS deve repetir o total da consulta 05. A soma dos tres grupos
-- deve ser igual a TOTAL_PLANOS.
SELECT COUNT(*) AS TOTAL_PLANOS,
       SUM(CASE
           WHEN p.ESTRATEGIA_ADOTADA_PUBLICACAO = 'CONSOLIDADO'
            AND p.UASG_ADOTADA = '930281'
               THEN 1
           ELSE 0
       END) AS PLANOS_CONSOLIDADOS_930281,
       SUM(CASE
           WHEN p.ESTRATEGIA_ADOTADA_PUBLICACAO IS NULL
            AND p.UASG_ADOTADA IS NULL
               THEN 1
           ELSE 0
       END) AS PLANOS_SEM_ESTRATEGIA,
       SUM(CASE
           WHEN p.ESTRATEGIA_ADOTADA_PUBLICACAO = 'CONSOLIDADO'
            AND p.UASG_ADOTADA = '930281'
               THEN 0
           WHEN (
               p.ESTRATEGIA_ADOTADA_PUBLICACAO IS NULL
               AND p.UASG_ADOTADA IS NULL
           )
               THEN 0
           ELSE 1
       END) AS PLANOS_COM_OUTRA_ESTRATEGIA,
       COUNT(p.ESTADO_PUBLICACAO) AS PLANOS_COM_ESTADO,
       COUNT(p.SEQUENCIAL_PUBLICACAO_PNCP) AS PLANOS_COM_SEQUENCIAL
  FROM SGI.PAC_PLANOS p
 WHERE p.ANO_EXERCICIO = 2026;
