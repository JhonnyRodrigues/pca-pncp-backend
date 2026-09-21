-- Execute depois da migração V202609181815.
-- Cardinalidade: exatamente 2 linhas, ambas com SITUACAO = CONFORME.

WITH CONFIGURACAO_ESPERADA AS (
    SELECT 'ESTRATEGIA_PUBLICACAO_PLANOS' AS CHAVE,
           'CONSOLIDADO' AS VALOR,
           'Aceita os valores: INDIVIDUAL ou CONSOLIDADO. Altera a decisão operacional da integração. No evento de publicação, consolida as demandas em uma única UASG antes de enviar os dados ao PNCP' AS DESCRICAO
      FROM DUAL
    UNION ALL
    SELECT 'UASG_CONSOLIDADORA' AS CHAVE,
           '930281' AS VALOR,
           'MSP - SERVIÇO MUN. ÁGUA E ESGOTO DE PIRACICABA' AS DESCRICAO
      FROM DUAL
)
SELECT E.CHAVE AS CHAVE_CONFIGURACAO,
       C.VALOR AS VALOR_ENCONTRADO,
       C.DESCRICAO AS DESCRICAO_ENCONTRADA,
       CASE
           WHEN C.CHAVE IS NOT NULL
            AND NVL(C.VALOR, CHR(0)) = NVL(E.VALOR, CHR(0))
            AND NVL(C.DESCRICAO, CHR(0)) = NVL(E.DESCRICAO, CHR(0))
               THEN 'CONFORME'
           ELSE 'DIVERGENTE'
       END AS SITUACAO
  FROM CONFIGURACAO_ESPERADA E
  LEFT JOIN SGI.PAC_CONFIGURACOES C
    ON C.CHAVE = E.CHAVE
 ORDER BY E.CHAVE;

