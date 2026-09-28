-- Sprint 9.5: precheck somente leitura apos a migration V202609251823.
-- Executar no DBeaver conectado a producao. Exportar todos os resultados.
-- Nao acionar a Blank enquanto qualquer resultado divergir do esperado.

-- 01 Confirmar o ambiente.
SELECT SYS_CONTEXT('USERENV', 'DB_NAME') AS BANCO,
       SYS_CONTEXT('USERENV', 'SERVICE_NAME') AS SERVICO,
       SYS_CONTEXT('USERENV', 'CURRENT_SCHEMA') AS ESQUEMA
  FROM DUAL;

-- Esperado: BANCO=sgi, SERVICO=sgi e ESQUEMA=SGI.

-- 02 Confirmar os 366 vinculos normais nos planos de versao 1.
WITH ALVOS AS (
    SELECT 149 AS ID_UO, 61 AS ID_PLANO, '205' AS UASG, 208 AS ESPERADO
      FROM DUAL
    UNION ALL
    SELECT 3, 141, '207', 5 FROM DUAL
    UNION ALL
    SELECT 94, 1, '210', 153 FROM DUAL
)
SELECT A.ID_UO,
       A.UASG,
       A.ID_PLANO,
       A.ESPERADO,
       COUNT(D.ID_DEMANDA) AS TOTAL_VINCULADO,
       COUNT(DISTINCT D.ID_DEMANDA) AS TOTAL_DISTINTO,
       CASE
           WHEN COUNT(D.ID_DEMANDA) = A.ESPERADO
            AND COUNT(DISTINCT D.ID_DEMANDA) = A.ESPERADO
           THEN 'CONFORME'
           ELSE 'DIVERGENTE'
       END AS RESULTADO
  FROM ALVOS A
  LEFT JOIN SGI.PAC_DEMANDA D
    ON D.FK_PLANO = A.ID_PLANO
   AND D.ORIGEM = 'N'
   AND D.SITUACAO IN (2, 3, 5)
 GROUP BY A.ID_UO, A.UASG, A.ID_PLANO, A.ESPERADO
 ORDER BY A.UASG;

-- Esperado: 205=208, 207=5 e 210=153; todas as linhas CONFORME.

-- 03 Reconstruir as quantidades que o backend considera desejadas em 2026.
-- A consulta reproduz o pertencimento por FK_PLANO usado pelo repository.
WITH UNIDADES AS (
    SELECT 149 AS ID_UO, '205' AS UASG, 279 AS TOTAL_ESPERADO,
           208 AS NORMAIS_ESPERADAS, 71 AS EXTRAORDINARIAS_ESPERADAS
      FROM DUAL
    UNION ALL
    SELECT 3, '207', 8, 5, 3 FROM DUAL
    UNION ALL
    SELECT 94, '210', 191, 153, 38 FROM DUAL
), FOTOGRAFIA AS (
    SELECT P.FK_UNIDADE_ORCAMENTARIA AS ID_UO,
           COUNT(DISTINCT D.ID_DEMANDA) AS TOTAL_DESEJADO,
           COUNT(DISTINCT CASE WHEN D.ORIGEM = 'N' THEN D.ID_DEMANDA END)
               AS TOTAL_NORMAL,
           COUNT(DISTINCT CASE WHEN D.ORIGEM = 'E' THEN D.ID_DEMANDA END)
               AS TOTAL_EXTRAORDINARIO
      FROM SGI.PAC_PLANOS P
      JOIN SGI.PAC_DEMANDA D
        ON D.FK_PLANO = P.ID_PLANO
       AND D.SITUACAO IN (2, 3, 5)
     WHERE P.ANO_EXERCICIO = 2026
       AND P.FK_UNIDADE_ORCAMENTARIA IN (149, 3, 94)
     GROUP BY P.FK_UNIDADE_ORCAMENTARIA
)
SELECT U.ID_UO,
       U.UASG,
       U.TOTAL_ESPERADO,
       NVL(F.TOTAL_DESEJADO, 0) AS TOTAL_DESEJADO,
       U.NORMAIS_ESPERADAS,
       NVL(F.TOTAL_NORMAL, 0) AS TOTAL_NORMAL,
       U.EXTRAORDINARIAS_ESPERADAS,
       NVL(F.TOTAL_EXTRAORDINARIO, 0) AS TOTAL_EXTRAORDINARIO,
       CASE
           WHEN NVL(F.TOTAL_DESEJADO, 0) = U.TOTAL_ESPERADO
            AND NVL(F.TOTAL_NORMAL, 0) = U.NORMAIS_ESPERADAS
            AND NVL(F.TOTAL_EXTRAORDINARIO, 0) = U.EXTRAORDINARIAS_ESPERADAS
           THEN 'CONFORME'
           ELSE 'DIVERGENTE'
       END AS RESULTADO
  FROM UNIDADES U
  LEFT JOIN FOTOGRAFIA F
    ON F.ID_UO = U.ID_UO
 ORDER BY U.UASG;

-- Esperado:
-- UASG 205: 279 desejados = 208 normais + 71 extraordinarios.
-- UASG 207:   8 desejados =   5 normais +  3 extraordinarios.
-- UASG 210: 191 desejados = 153 normais + 38 extraordinarios.

-- 04 Exportar os IDs desejados para comparacao exata com o inventario PNCP.
SELECT CASE P.FK_UNIDADE_ORCAMENTARIA
           WHEN 149 THEN '205'
           WHEN 3 THEN '207'
           WHEN 94 THEN '210'
       END AS UASG,
       D.ID_DEMANDA AS NUMERO_ITEM,
       D.ORIGEM,
       D.SITUACAO,
       D.FK_PLANO,
       P.VERSAO
  FROM SGI.PAC_PLANOS P
  JOIN SGI.PAC_DEMANDA D
    ON D.FK_PLANO = P.ID_PLANO
 WHERE P.ANO_EXERCICIO = 2026
   AND P.FK_UNIDADE_ORCAMENTARIA IN (149, 3, 94)
   AND D.SITUACAO IN (2, 3, 5)
 ORDER BY UASG, D.ID_DEMANDA;

-- Esperado: 478 linhas distintas. Comparadas com build/itens_2026.json,
-- devem resultar somente em inclusoes: 205=71, 207=3 e 210=38; exclusoes=0.

-- 05 Garantir que a aplicacao da migration nao gerou auditoria de publicacao.
SELECT H.FK_PROCEDIMENTO,
       COUNT(*) AS TOTAL
  FROM SGI.PAC_HISTORICOS H
 WHERE H.FK_PROCEDIMENTO IN (5, 8, 9)
   AND H.MENSAGEM LIKE 'Exercício: 2026;%'
 GROUP BY H.FK_PROCEDIMENTO
 ORDER BY H.FK_PROCEDIMENTO;

-- Esperado nesta etapa: nenhuma linha.
