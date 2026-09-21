-- Sprint 8.5 / Microciclo 6 / diagnóstico somente leitura do registro técnico
-- Execute cada SELECT separadamente no banco de homologação.
-- Não execute novamente a etapa LOGS.

-- 01 identidade_conexao_consulta
-- Colunas exatas: DATABASE_NAME, SERVICE_NAME, CURRENT_SCHEMA, SESSION_USER.
-- Cardinalidade: exatamente 1.
-- Objetivo: identificar o banco e o schema vistos pela conexão usada no
-- pós-check, sem revelar credenciais.
SELECT SYS_CONTEXT('USERENV', 'DB_NAME') AS DATABASE_NAME,
       SYS_CONTEXT('USERENV', 'SERVICE_NAME') AS SERVICE_NAME,
       SYS_CONTEXT('USERENV', 'CURRENT_SCHEMA') AS CURRENT_SCHEMA,
       SYS_CONTEXT('USERENV', 'SESSION_USER') AS SESSION_USER
  FROM DUAL;

-- 02 estrutura_completa_log
-- Colunas exatas: COLUMN_ID, COLUMN_NAME, DATA_TYPE, DATA_LENGTH,
--                CHAR_LENGTH, NULLABLE.
-- Cardinalidade: uma linha por coluna de SGI.SEGURANCA_LOG.
-- Objetivo: descobrir com segurança os nomes das colunas de data, aplicação,
-- usuário e chave antes de montar uma consulta por horário.
SELECT c.COLUMN_ID AS COLUMN_ID,
       c.COLUMN_NAME AS COLUMN_NAME,
       c.DATA_TYPE AS DATA_TYPE,
       c.DATA_LENGTH AS DATA_LENGTH,
       c.CHAR_LENGTH AS CHAR_LENGTH,
       c.NULLABLE AS NULLABLE
  FROM ALL_TAB_COLUMNS c
 WHERE c.OWNER = 'SGI'
   AND c.TABLE_NAME = 'SEGURANCA_LOG'
 ORDER BY c.COLUMN_ID;

-- 03 objetos_visiveis_com_mesmo_nome
-- Colunas exatas: OWNER, OBJECT_NAME, OBJECT_TYPE, STATUS.
-- Cardinalidade esperada: ao menos 1; deve incluir SGI / SEGURANCA_LOG / TABLE.
-- Objetivo: detectar outra tabela ou view homônima visível pela sessão.
SELECT o.OWNER AS OWNER,
       o.OBJECT_NAME AS OBJECT_NAME,
       o.OBJECT_TYPE AS OBJECT_TYPE,
       o.STATUS AS STATUS
  FROM ALL_OBJECTS o
 WHERE o.OBJECT_NAME = 'SEGURANCA_LOG'
 ORDER BY o.OWNER,
          o.OBJECT_TYPE;

-- 04 sinonimos_visiveis
-- Colunas exatas: OWNER, SYNONYM_NAME, TABLE_OWNER, TABLE_NAME, DB_LINK.
-- Cardinalidade: zero ou mais.
-- Objetivo: detectar redirecionamento de SEGURANCA_LOG para outro objeto ou
-- banco. DB_LINK vazio significa que não há redirecionamento remoto.
SELECT s.OWNER AS OWNER,
       s.SYNONYM_NAME AS SYNONYM_NAME,
       s.TABLE_OWNER AS TABLE_OWNER,
       s.TABLE_NAME AS TABLE_NAME,
       s.DB_LINK AS DB_LINK
  FROM ALL_SYNONYMS s
 WHERE s.SYNONYM_NAME = 'SEGURANCA_LOG'
 ORDER BY s.OWNER;

-- 05 identificador_em_qualquer_acao
-- Colunas exatas: ACTION, TAMANHO_DESCRIPTION, INICIO_DESCRIPTION.
-- Cardinalidade esperada neste diagnóstico: exatamente 0, conforme o
-- pós-check já relatado. Se aparecer linha, não repita o gate.
-- Objetivo: eliminar o filtro ACTION = 'execute' como causa do resultado vazio.
SELECT l.ACTION AS ACTION,
       DBMS_LOB.GETLENGTH(l.DESCRIPTION) AS TAMANHO_DESCRIPTION,
       DBMS_LOB.SUBSTR(l.DESCRIPTION, 500, 1) AS INICIO_DESCRIPTION
  FROM SGI.SEGURANCA_LOG l
 WHERE DBMS_LOB.INSTR(
           l.DESCRIPTION,
           'SPRINT85-LOGS-HML-20260921-JSILVA-02'
       ) > 0;

-- 06 quantidade_por_variacao_da_acao
-- Colunas exatas: ACTION, QUANTIDADE.
-- Cardinalidade: zero ou mais.
-- Objetivo: verificar se a tabela possui registros históricos cuja ação,
-- desconsiderando maiúsculas e espaços, seja execute.
SELECT l.ACTION AS ACTION,
       COUNT(*) AS QUANTIDADE
  FROM SGI.SEGURANCA_LOG l
 WHERE LOWER(TRIM(l.ACTION)) = 'execute'
 GROUP BY l.ACTION
 ORDER BY l.ACTION;

-- 07 registros_recentes_da_blank_ou_acao
-- Colunas exatas: ID, INSERTED_DATE, USERNAME, APPLICATION, CREATOR, ACTION,
--                TAMANHO_DESCRIPTION, TAMANHO_DESCRIPTION2,
--                INICIO_DESCRIPTION.
-- Cardinalidade: zero ou mais, limitada aos 50 registros mais recentes.
-- Objetivo: localizar uma eventual linha criada pela tentativa mesmo que a
-- descrição tenha sido alterada, cortada ou desviada para DESCRIPTION2.
-- A janela cobre 19 a 21/09/2026 no relógio do Oracle de homologação.
SELECT l.ID AS ID,
       TO_CHAR(l.INSERTED_DATE, 'YYYY-MM-DD HH24:MI:SS') AS INSERTED_DATE,
       l.USERNAME AS USERNAME,
       l.APPLICATION AS APPLICATION,
       l.CREATOR AS CREATOR,
       l.ACTION AS ACTION,
       CASE
           WHEN l.DESCRIPTION IS NULL THEN NULL
           ELSE DBMS_LOB.GETLENGTH(l.DESCRIPTION)
       END AS TAMANHO_DESCRIPTION,
       CASE
           WHEN l.DESCRIPTION2 IS NULL THEN NULL
           ELSE DBMS_LOB.GETLENGTH(l.DESCRIPTION2)
       END AS TAMANHO_DESCRIPTION2,
       DBMS_LOB.SUBSTR(l.DESCRIPTION, 500, 1) AS INICIO_DESCRIPTION
  FROM SGI.SEGURANCA_LOG l
 WHERE l.INSERTED_DATE >= DATE '2026-09-19'
   AND l.INSERTED_DATE < DATE '2026-09-22'
   AND (
       LOWER(l.APPLICATION) LIKE '%teste_contracts_sprint85%'
       OR LOWER(TRIM(l.ACTION)) = 'execute'
   )
 ORDER BY l.INSERTED_DATE DESC,
          l.ID DESC
 FETCH FIRST 50 ROWS ONLY;

-- 08 atividade_geral_na_janela
-- Colunas exatas: QUANTIDADE, PRIMEIRO_REGISTRO, ULTIMO_REGISTRO.
-- Cardinalidade: exatamente 1.
-- Objetivo: distinguir ausência específica da Blank de uma tabela que não
-- recebeu nenhum log no período.
SELECT COUNT(*) AS QUANTIDADE,
       TO_CHAR(MIN(l.INSERTED_DATE), 'YYYY-MM-DD HH24:MI:SS')
           AS PRIMEIRO_REGISTRO,
       TO_CHAR(MAX(l.INSERTED_DATE), 'YYYY-MM-DD HH24:MI:SS')
           AS ULTIMO_REGISTRO
  FROM SGI.SEGURANCA_LOG l
 WHERE l.INSERTED_DATE >= DATE '2026-09-19'
   AND l.INSERTED_DATE < DATE '2026-09-22';

-- 09 geracao_da_chave_id
-- Colunas exatas: COLUMN_NAME, DATA_DEFAULT, IDENTITY_COLUMN.
-- Cardinalidade: exatamente 1.
-- Objetivo: descobrir como o ID obrigatório é gerado sem inferir uma
-- sequência pelo nome.
SELECT c.COLUMN_NAME AS COLUMN_NAME,
       c.DATA_DEFAULT AS DATA_DEFAULT,
       c.IDENTITY_COLUMN AS IDENTITY_COLUMN
  FROM ALL_TAB_COLUMNS c
 WHERE c.OWNER = 'SGI'
   AND c.TABLE_NAME = 'SEGURANCA_LOG'
   AND c.COLUMN_NAME = 'ID';

-- 10 constraints_da_tabela
-- Colunas exatas: CONSTRAINT_NAME, CONSTRAINT_TYPE, STATUS, COLUMN_NAME,
--                POSITION.
-- Cardinalidade: zero ou mais.
-- Objetivo: conhecer chave e restrições necessárias ao insert inicial.
SELECT co.CONSTRAINT_NAME AS CONSTRAINT_NAME,
       co.CONSTRAINT_TYPE AS CONSTRAINT_TYPE,
       co.STATUS AS STATUS,
       cc.COLUMN_NAME AS COLUMN_NAME,
       cc.POSITION AS POSITION
  FROM ALL_CONSTRAINTS co
  LEFT JOIN ALL_CONS_COLUMNS cc
    ON cc.OWNER = co.OWNER
   AND cc.CONSTRAINT_NAME = co.CONSTRAINT_NAME
   AND cc.TABLE_NAME = co.TABLE_NAME
 WHERE co.OWNER = 'SGI'
   AND co.TABLE_NAME = 'SEGURANCA_LOG'
 ORDER BY co.CONSTRAINT_NAME,
          cc.POSITION;

-- 11 triggers_da_tabela
-- Colunas exatas: TRIGGER_NAME, STATUS, TRIGGERING_EVENT, TRIGGER_TYPE.
-- Cardinalidade: zero ou mais.
-- Objetivo: descobrir se ID, data ou outros campos são preenchidos por trigger.
SELECT t.TRIGGER_NAME AS TRIGGER_NAME,
       t.STATUS AS STATUS,
       t.TRIGGERING_EVENT AS TRIGGERING_EVENT,
       t.TRIGGER_TYPE AS TRIGGER_TYPE
  FROM ALL_TRIGGERS t
 WHERE t.TABLE_OWNER = 'SGI'
   AND t.TABLE_NAME = 'SEGURANCA_LOG'
 ORDER BY t.TRIGGER_NAME;

-- 12 amostra_recente_do_modulo_scriptcase
-- Colunas exatas: ID, INSERTED_DATE, USERNAME, APPLICATION, CREATOR, IP_USER,
--                ACTION, TAMANHO_DESCRIPTION, TAMANHO_DESCRIPTION2,
--                INICIO_DESCRIPTION.
-- Cardinalidade: zero a 10.
-- Objetivo: observar o formato físico criado pelo módulo habilitado, sem
-- devolver o CLOB ou BLOB integral.
SELECT l.ID AS ID,
       TO_CHAR(l.INSERTED_DATE, 'YYYY-MM-DD HH24:MI:SS') AS INSERTED_DATE,
       l.USERNAME AS USERNAME,
       l.APPLICATION AS APPLICATION,
       l.CREATOR AS CREATOR,
       l.IP_USER AS IP_USER,
       l.ACTION AS ACTION,
       CASE
           WHEN l.DESCRIPTION IS NULL THEN NULL
           ELSE DBMS_LOB.GETLENGTH(l.DESCRIPTION)
       END AS TAMANHO_DESCRIPTION,
       CASE
           WHEN l.DESCRIPTION2 IS NULL THEN NULL
           ELSE DBMS_LOB.GETLENGTH(l.DESCRIPTION2)
       END AS TAMANHO_DESCRIPTION2,
       DBMS_LOB.SUBSTR(l.DESCRIPTION, 500, 1) AS INICIO_DESCRIPTION
  FROM SGI.SEGURANCA_LOG l
 WHERE l.INSERTED_DATE >= DATE '2026-09-20'
   AND LOWER(l.APPLICATION) LIKE '%teste_contracts_sprint85%'
 ORDER BY l.INSERTED_DATE DESC,
          l.ID DESC
 FETCH FIRST 10 ROWS ONLY;

-- 13 fonte_do_trigger_de_id
-- Colunas exatas: LINE, TEXT.
-- Cardinalidade: uma linha por linha-fonte do trigger.
-- Objetivo: confirmar como TR_SEGURANCA_LOG preenche o ID antes de omiti-lo no
-- insert do registrador próprio.
SELECT s.LINE AS LINE,
       s.TEXT AS TEXT
  FROM ALL_SOURCE s
 WHERE s.OWNER = 'SGI'
   AND s.NAME = 'TR_SEGURANCA_LOG'
   AND s.TYPE = 'TRIGGER'
 ORDER BY s.LINE;
