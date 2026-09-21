-- Sprint 8.5 / Microciclo 7 / sessao Oracle A
-- Execute somente depois de aprovar o PRECHECK manual e o PRECHECK da Blank.
-- A consulta deve retornar exatamente uma linha e manter a transacao aberta.
-- Nao feche esta aba e nao execute COMMIT enquanto a etapa CONTENCAO roda na
-- sessao B do validaSGI.

SELECT c.ESCOPO AS ESCOPO,
       c.ANO_EXECUCAO AS ANO_EXECUCAO,
       c.IDENTIFICADOR_ALVO AS IDENTIFICADOR_ALVO
  FROM SGI.PAC_CONTROLES_CONCORRENCIA c
 WHERE c.ESCOPO = 'UNIDADE_EXTERNA'
   AND c.ANO_EXECUCAO = 2026
   AND c.IDENTIFICADOR_ALVO = '930281'
 FOR UPDATE;

-- Depois de receber o relatorio da etapa CONTENCAO, libere a ancora na mesma
-- sessao A. ROLLBACK libera o lock sem alterar dados.
ROLLBACK;
