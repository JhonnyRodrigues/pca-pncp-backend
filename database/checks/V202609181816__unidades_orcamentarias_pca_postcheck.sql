-- Execute depois da migração V202609181816.
-- Cardinalidade: exatamente 4 linhas, todas com SITUACAO = CONFORME.

WITH UNIDADE_ESPERADA AS (
    SELECT 1 AS ID_UNIDADE_ORCAMENTARIA,
           'Gabinete da Presidência' AS DESCRICAO_UASG,
           '204' AS CODIGO_UASG,
           'S' AS ATIVA
      FROM DUAL
    UNION ALL
    SELECT 3,
           'Procuradoria Jurídica',
           '207',
           'S'
      FROM DUAL
    UNION ALL
    SELECT 94,
           'Diretoria Geral de Saneamento',
           '210',
           'S'
      FROM DUAL
    UNION ALL
    SELECT 149,
           'Diretoria Geral Administrativa e Financeira',
           '205',
           'S'
      FROM DUAL
)
SELECT E.ID_UNIDADE_ORCAMENTARIA,
       U.DESCRICAO_UASG AS DESCRICAO_ENCONTRADA,
       U.CODIGO_UASG AS CODIGO_ENCONTRADO,
       U.ATIVA AS ATIVA_ENCONTRADA,
       CASE
           WHEN L.COD_LOTACAO IS NOT NULL
            AND U.ID_UNIDADE_ORCAMENTARIA = E.ID_UNIDADE_ORCAMENTARIA
            AND NVL(U.DESCRICAO_UASG, CHR(0)) = NVL(E.DESCRICAO_UASG, CHR(0))
            AND NVL(U.CODIGO_UASG, CHR(0)) = NVL(E.CODIGO_UASG, CHR(0))
            AND NVL(U.ATIVA, CHR(0)) = NVL(E.ATIVA, CHR(0))
               THEN 'CONFORME'
           ELSE 'DIVERGENTE'
       END AS SITUACAO
  FROM UNIDADE_ESPERADA E
  LEFT JOIN SGI.RH_LOTACOES L
    ON L.COD_LOTACAO = E.ID_UNIDADE_ORCAMENTARIA
  LEFT JOIN SGI.PAC_UNIDADES_ORCAMENTARIAS U
    ON U.ID_UNIDADE_ORCAMENTARIA = E.ID_UNIDADE_ORCAMENTARIA
 ORDER BY E.ID_UNIDADE_ORCAMENTARIA;

