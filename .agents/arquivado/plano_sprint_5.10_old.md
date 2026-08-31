# Sprint 5.10 — Investigação da evidência de cancelamento e encerramento da Fase 5

**Classificação:** plano de sprint aprovado  
**Status:** Em execução — microciclo 1 concluído; aguarda autorização do 2
**Aprovado em:** 28/08/2026

## Revisão superveniente

Em 31/08/2026, o mantenedor aprovou substituir a busca da última justificativa
de cancelamento pela mensagem institucional fixa `Exclusão integral do PCA por
ausência de demandas vigentes no SGI.`. O gatilho permanece sendo a fotografia
vigente do SGI vazia com PCA externo ativo.

A implementação substitutiva foi autorizada em quatro microciclos com pausa
obrigatória entre eles:

1. revisão normativa;
2. TDD do fluxo simplificado;
3. remoção da capacidade histórica sem consumidor;
4. reconciliação documental e gate final da Fase 5.

O plano investigativo abaixo permanece preservado como registro da decisão
anterior. A investigação Oracle real e o adapter nele previstos deixam de ser
necessários; isso não encerra a Fase 5 antes dos microciclos e gates restantes.

## Contexto

A Sprint 5.10 investiga, de forma exclusivamente read-only, a fonte Oracle da evidência do último cancelamento homologado que tornou vazia a unidade externa. Quando a fotografia vigente fica vazia e ainda existe um PCA externo ativo, o backend deve excluí-lo do PNCP usando integralmente a justificativa desse fato histórico comprovado, nunca uma justificativa inferida. No modo consolidado, a unidade somente fica vazia quando a união de todas as UOs participantes não possui Demandas vigentes.

## Objetivo

Investigar a fonte Oracle necessária para implementar `EvidenciaCancelamentoHomologadoPort`, auditar as condições restantes da Infrastructure e produzir o plano definitivo do adapter.

## Limites autorizados

- criar uma suíte opt-in, read-only e fail-closed para a investigação Oracle;
- obter DDL, semântica e relacionamentos com o DBA e usar massa dedicada criada
  pelo fluxo oficial do SGI;
- registrar a Sprint 5.10, a matriz de encerramento e o estado dos gates;
- considerar cumpridos os gates da DI-009, mantendo o limite vigente de 1.000
  itens e tratando eventual incompatibilidade futura de CNPJ alfanumérico como
  motivo para reabrir somente essa decisão;
- preservar a DI-008 como condicional e a DI-010 para fases posteriores.

Este incremento não implementa adapter, mapeamento de produção, Port, regra de
negócio, API pública ou Composition Root. A Fase 6 permanece bloqueada.

## Checkpoint obrigatório

Depois da investigação real:

1. fonte compatível: produzir novo plano TDD para o mapeamento, o adapter e seu
   gate Oracle real;
2. evidência incompatível: reabrir somente a fonte autoritativa afetada;
3. evidência insuficiente: manter Sprint 5.10 e Fase 5 bloqueadas.

Nenhuma implementação do adapter está autorizada antes de nova aprovação
explícita.
