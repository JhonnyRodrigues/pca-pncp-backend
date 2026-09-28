-- Sprint 9.5 / Microciclo 1: fotografia Oracle somente leitura.
-- Execute cada SELECT no DBeaver conectado ao ambiente de producao correto.
-- Nao execute migrations ou o script de vinculo da Sprint 9.4 nesta etapa.

-- 01 Estrategia corrente: exatamente uma linha INDIVIDUAL.
SELECT c.CHAVE,
       c.VALOR,
       c.DESCRICAO
  FROM SGI.PAC_CONFIGURACOES c
 WHERE c.CHAVE = 'ESTRATEGIA_PUBLICACAO_PLANOS';

-- 02 Procedimentos de auditoria: exatamente os IDs 5, 8 e 9.
SELECT p.ID_PROCEDIMENTO,
       p.PROCEDIMENTO
  FROM SGI.PAC_PROCEDIMENTOS p
 WHERE p.ID_PROCEDIMENTO IN (5, 8, 9)
 ORDER BY p.ID_PROCEDIMENTO;

-- 03 Todas as versoes e suas projecoes nos dois exercicios.
SELECT p.ANO_EXERCICIO,
       p.FK_UNIDADE_ORCAMENTARIA,
       p.ID_PLANO,
       p.VERSAO,
       TO_CHAR(p.DATA_PUBLICACAO, 'YYYY-MM-DD HH24:MI:SS') AS DATA_CONFIRMACAO,
       p.ESTRATEGIA_ADOTADA_PUBLICACAO,
       p.UASG_ADOTADA,
       p.ESTADO_PUBLICACAO,
       p.SEQUENCIAL_PUBLICACAO_PNCP
  FROM SGI.PAC_PLANOS p
 WHERE p.ANO_EXERCICIO IN (2026, 2027)
 ORDER BY p.ANO_EXERCICIO,
          p.FK_UNIDADE_ORCAMENTARIA,
          p.VERSAO,
          p.ID_PLANO;

-- 04 Demandas ligadas a PAC_PLANOS de 2026/2027, inclusive situacoes nao
-- participantes. Demandas com FK_PLANO nulo ficam fora desta consulta.
-- Nao usar este resultado isolado como fotografia completa de 2026.
SELECT p.ANO_EXERCICIO,
       p.FK_UNIDADE_ORCAMENTARIA,
       d.ID_DEMANDA,
       d.SITUACAO,
       d.FK_PLANO
  FROM SGI.PAC_DEMANDA d
  JOIN SGI.PAC_PLANOS p
    ON p.ID_PLANO = d.FK_PLANO
 WHERE p.ANO_EXERCICIO IN (2026, 2027)
 ORDER BY p.ANO_EXERCICIO,
          p.FK_UNIDADE_ORCAMENTARIA,
          d.ID_DEMANDA;
