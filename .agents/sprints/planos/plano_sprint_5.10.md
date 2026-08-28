# Sprint 5.10 — Investigação da evidência de cancelamento e encerramento da Fase 5

**Classificação:** plano de sprint aprovado  
**Status:** Em execução — incremento investigativo  
**Aprovado em:** 28/08/2026

## Objetivo

Investigar a fonte Oracle necessária para implementar
`EvidenciaCancelamentoHomologadoPort`, auditar as condições restantes da
Infrastructure e produzir o plano definitivo do adapter.

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
