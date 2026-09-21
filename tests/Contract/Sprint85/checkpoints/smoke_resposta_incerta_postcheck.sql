-- Sprint 8.5 / Microciclo 8 / POSTCHECK de resposta HTTP incerta.
-- Execute uma consulta por vez e nao repita a publicacao.
-- Todas as consultas sao somente leitura.

-- 01 projecao: exatamente 1 linha. Esperado antes e depois do smoke:
-- TOTAL_PLANOS e CONFORMES 112; DIVERGENTES 0.
SELECT COUNT(*) AS TOTAL_PLANOS,
       SUM(CASE
           WHEN p.ESTRATEGIA_ADOTADA_PUBLICACAO = 'CONSOLIDADO'
            AND p.UASG_ADOTADA = '930281'
            AND p.ESTADO_PUBLICACAO = 'EXCLUIDO'
            AND p.SEQUENCIAL_PUBLICACAO_PNCP = 36 THEN 1 ELSE 0
       END) AS CONFORMES,
       SUM(CASE
           WHEN p.ESTRATEGIA_ADOTADA_PUBLICACAO = 'CONSOLIDADO'
            AND p.UASG_ADOTADA = '930281'
            AND p.ESTADO_PUBLICACAO = 'EXCLUIDO'
            AND p.SEQUENCIAL_PUBLICACAO_PNCP = 36 THEN 0 ELSE 1
       END) AS DIVERGENTES
  FROM SGI.PAC_PLANOS p
 WHERE p.ANO_EXERCICIO = 2026;

-- 02 confirmacoes: zero linhas indica que o fluxo nao confirmou os planos;
-- tres linhas devem ser exatamente os planos vigentes 143, 124 e 224.
SELECT p.ID_PLANO,
       p.FK_UNIDADE_ORCAMENTARIA,
       p.VERSAO,
       TO_CHAR(p.DATA_PUBLICACAO, 'YYYY-MM-DD HH24:MI:SS') AS DATA_PUBLICACAO
  FROM SGI.PAC_PLANOS p
 WHERE p.ANO_EXERCICIO = 2026
   AND p.DATA_PUBLICACAO IS NOT NULL
 ORDER BY p.FK_UNIDADE_ORCAMENTARIA, p.ID_PLANO;

-- 03 plano intermediario protegido: exatamente uma linha, DATA_PUBLICACAO nula.
SELECT p.ID_PLANO,
       p.FK_UNIDADE_ORCAMENTARIA,
       p.VERSAO,
       TO_CHAR(p.DATA_PUBLICACAO, 'YYYY-MM-DD HH24:MI:SS') AS DATA_PUBLICACAO
  FROM SGI.PAC_PLANOS p
 WHERE p.ID_PLANO = 103
   AND p.ANO_EXERCICIO = 2026;

-- 04 ciclo: exatamente 1 linha; TOTAL_DEMANDAS e CANCELADAS 112;
-- PARTICIPANTES 0.
SELECT COUNT(*) AS TOTAL_DEMANDAS,
       SUM(CASE WHEN d.SITUACAO = 4 THEN 1 ELSE 0 END) AS CANCELADAS,
       SUM(CASE WHEN d.SITUACAO IN (2, 3, 5) THEN 1 ELSE 0 END) AS PARTICIPANTES
  FROM SGI.PAC_DEMANDA d
  JOIN SGI.PAC_PLANOS p ON p.ID_PLANO = d.FK_PLANO
 WHERE p.ANO_EXERCICIO = 2026;

-- 05 auditoria de negocio: zero linhas.
SELECT TO_CHAR(h.DATA_INSERCAO, 'YYYY-MM-DD HH24:MI:SS') AS DATA_REGISTRO,
       h.FK_PROCEDIMENTO AS PROCEDIMENTO,
       h.MENSAGEM,
       h.FK_REPRESENTANTE,
       h.FK_LOTACAO
  FROM SGI.PAC_HISTORICOS h
  JOIN SGI.RH_FUNCIONARIOS f
    ON f.COD_FUNCIONARIO = h.FK_REPRESENTANTE
 WHERE UPPER(f.NOME_USUARIO) = UPPER('jsilva')
   AND UPPER(h.MENSAGEM) LIKE UPPER('Exercício: 2026;%')
 ORDER BY h.DATA_INSERCAO;

-- 06 controles: exatamente 2 linhas, sem alteracao.
SELECT c.ESCOPO,
       c.ANO_EXECUCAO,
       c.IDENTIFICADOR_ALVO
  FROM SGI.PAC_CONTROLES_CONCORRENCIA c
 WHERE c.ANO_EXECUCAO = 2026
 ORDER BY c.ESCOPO, c.IDENTIFICADOR_ALVO;

-- 07 eventos tecnicos de hoje para a aplicacao real e o operador.
-- Cardinalidade: zero ou mais. Se houver linhas, CORRELACAO permite recuperar
-- a execucao que nao chegou ao navegador. ACTION deve ser execute e JSON_VALIDO
-- deve ser SIM. A consulta nao devolve o CLOB integral.
SELECT l.ID,
       TO_CHAR(l.INSERTED_DATE, 'YYYY-MM-DD HH24:MI:SS') AS INSERTED_DATE,
       l.USERNAME,
       l.APPLICATION,
       l.ACTION,
       DBMS_LOB.GETLENGTH(l.DESCRIPTION) AS TAMANHO_DESCRIPTION,
       CASE WHEN l.DESCRIPTION IS JSON THEN 'SIM' ELSE 'NAO' END AS JSON_VALIDO,
       JSON_VALUE(
           l.DESCRIPTION,
           '$.correlationId' RETURNING VARCHAR2(64) NULL ON ERROR
       ) AS CORRELACAO,
       JSON_VALUE(
           l.DESCRIPTION,
           '$.action' RETURNING VARCHAR2(100) NULL ON ERROR
       ) AS ACAO_EVENTO,
       JSON_VALUE(
           l.DESCRIPTION,
           '$.level' RETURNING VARCHAR2(20) NULL ON ERROR
       ) AS NIVEL_EVENTO
  FROM SGI.SEGURANCA_LOG l
 WHERE l.INSERTED_DATE >= TRUNC(SYSDATE)
   AND UPPER(l.USERNAME) = UPPER('jsilva')
   AND l.APPLICATION = 'PCA_PUBLICAR_B'
   AND l.ACTION = 'execute'
 ORDER BY l.ID;
