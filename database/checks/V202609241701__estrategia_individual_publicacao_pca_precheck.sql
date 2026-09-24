-- Execute antes da migração V202609241701.
-- Cardinalidade: exatamente 1 linha.
-- Estados aceitos: CONFORME ou MIGRACAO_NECESSARIA.
-- AUSENTE ou DIVERGENTE bloqueiam a migração e exigem análise.

WITH DESCRICAO_ESPERADA AS (
    SELECT 'Aceita os valores: INDIVIDUAL ou CONSOLIDADO. Altera a decisão operacional da integração. No evento de publicação, consolida as demandas em uma única UASG antes de enviar os dados ao PNCP' AS ANTERIOR,
           'Aceita os valores: INDIVIDUAL ou CONSOLIDADO. Define a estratégia operacional usada para publicar os planos no PNCP.' AS ATUAL
      FROM DUAL
)
SELECT 'ESTRATEGIA_PUBLICACAO_PLANOS' AS CHAVE_CONFIGURACAO,
       C.VALOR AS VALOR_ENCONTRADO,
       C.DESCRICAO AS DESCRICAO_ENCONTRADA,
       CASE
           WHEN C.CHAVE IS NULL THEN 'AUSENTE'
           WHEN C.VALOR = 'INDIVIDUAL'
            AND C.DESCRICAO = E.ATUAL
               THEN 'CONFORME'
           WHEN C.VALOR IN ('CONSOLIDADO', 'INDIVIDUAL')
            AND C.DESCRICAO IN (E.ANTERIOR, E.ATUAL)
               THEN 'MIGRACAO_NECESSARIA'
           ELSE 'DIVERGENTE'
       END AS SITUACAO
  FROM DESCRICAO_ESPERADA E
  LEFT JOIN SGI.PAC_CONFIGURACOES C
    ON C.CHAVE = 'ESTRATEGIA_PUBLICACAO_PLANOS';
