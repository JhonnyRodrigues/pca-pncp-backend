-- Sprint 8.5 / Microciclo 6 / PRECHECK do gate de logs no validaSGI
-- IDENTIFICADOR_LOG = SPRINT85-LOGS-HML-20260921-JSILVA-02
-- Execute cada SELECT separadamente no banco de homologação.
-- Estas consultas não dependem da massa de PCA e são somente leitura.

-- 01 estrutura_log_tecnico
-- Colunas exatas: COLUMN_NAME, DATA_TYPE, DATA_LENGTH, CHAR_LENGTH
-- Cardinalidade: exatamente 2.
-- Esperado: ACTION = VARCHAR2(30 BYTE); DESCRIPTION = CLOB.
SELECT c.COLUMN_NAME AS COLUMN_NAME,
       c.DATA_TYPE AS DATA_TYPE,
       c.DATA_LENGTH AS DATA_LENGTH,
       c.CHAR_LENGTH AS CHAR_LENGTH
  FROM ALL_TAB_COLUMNS c
 WHERE c.OWNER = 'SGI'
   AND c.TABLE_NAME = 'SEGURANCA_LOG'
   AND c.COLUMN_NAME IN ('ACTION', 'DESCRIPTION')
 ORDER BY c.COLUMN_NAME;

-- 02 logs_tecnicos_antes_gate
-- Colunas exatas: ACTION, DESCRIPTION
-- Cardinalidade: exatamente 0. Se houver uma linha, não repita o gate com o
-- mesmo identificador: prepare outro identificador e um novo checkpoint.
SELECT l.ACTION AS ACTION,
       l.DESCRIPTION AS DESCRIPTION
  FROM SGI.SEGURANCA_LOG l
 WHERE l.ACTION = 'execute'
   AND DBMS_LOB.INSTR(
       l.DESCRIPTION,
       'SPRINT85-LOGS-HML-20260921-JSILVA-02'
   ) > 0;
