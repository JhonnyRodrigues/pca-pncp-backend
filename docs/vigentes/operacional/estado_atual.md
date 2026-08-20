# Estado operacional do projeto PCA/PNCP

**Classificação documental:** Operacional

**Status:** Vigente

**Autoridade/Escopo:** progresso do projeto, última sprint concluída, próxima
sprint na ordem, gates, pendências e restrições operacionais

**Atualizado em:** 20/08/2026

## Limites de autoridade

Este documento centraliza somente o estado corrente do trabalho. Ele não cria
nem altera regras de negócio, modelo de domínio, decisões de integração,
arquitetura, fluxo de implementação, ordem das sprints ou critérios de saída.

Para esses assuntos, prevalecem as fontes autoritativas registradas no
[índice documental](../../README.md). A ordem de construção permanece definida
pelo [Roadmap](../autoritativos/roadmap.md).

## Progresso

- A Fase 4 — Application está concluída.
- A Sprint 5.1 — Gates contratuais Oracle e PNCP está concluída.
- A Sprint 5.2 — Sessão Oracle, unidade de trabalho, estratégia e locks está
  concluída.
- A última sprint concluída é a Sprint 5.2.
- A próxima sprint na ordem é a Sprint 5.3 — Mapeamento e PlanoRepository.
- A Sprint 5.3 não foi iniciada e este registro não constitui autorização
  implícita para iniciá-la.

## Evidências da Sprint 5.2

- A conexão ADOdb emprestada está encapsulada na Infrastructure Oracle, usa
  binds produzidos por `Param()` e nunca é fechada pelo backend.
- A unidade de trabalho e o adapter de estratégia compartilham a mesma sessão.
- A transação local cobre início, confirmação e desfazimento, sem início
  aninhado.
- As âncoras permanentes são provisionadas somente por DML idempotente e o
  lock usa `SELECT ... FOR UPDATE NOWAIT`.
- `ORA-00001`, `ORA-00054` e `ORA-00942` recebem tratamento seguro e tipado no
  contexto aplicável.
- A estratégia anual corrente e adotada é lida sem default; a adoção exige
  transação ativa, grava apenas campos ainda nulos e é confirmada por
  releitura.
- Tabela, colunas, relações, filtros e literais Oracle são obrigatoriamente
  injetados por mapeamentos físicos validados.
- A suíte padrão aprovou 388 testes e 1.330 asserções.
- A suíte contratual não real aprovou 24 testes e 51 asserções.
- A suíte focada da Infrastructure Oracle aprovou 49 testes e 191 asserções.

Nenhum probe real Oracle ou PNCP e nenhum DDL foram executados durante a
Sprint 5.2.

## Gates confirmados

- O gate Oracle/Scriptcase confirmou ADOdb emprestado, `Param()`, mesma sessão,
  `BeginTrans`, `CommitTrans`, `RollbackTrans`, ausência de `Close()`,
  `FOR UPDATE NOWAIT` e contenção traduzível a partir de `ORA-00054`.
- O gate PNCP confirmou os contratos autorizados de runtime, TLS, autenticação,
  leitura e escrita no ambiente de treinamento.

As evidências não autoritativas da Sprint 5.1 permanecem em
[Evidências dos gates Oracle e PNCP](../../investigacoes/evidencias_sprint_5_1_gates_oracle_pncp_2026-08-17.md).

## Pendências não bloqueantes

- A compatibilidade externa do PNCP com CNPJ alfanumérico permanece **AINDA
  NÃO COMPROVADA**.
- Uma chamada única com 1.001 itens permanece **AINDA NÃO COMPROVADA**.
- O limite operacional continua em 1.000 itens por chamada.

## Restrições para o próximo avanço

- O provisionamento de âncoras depende de tabela e colunas preexistentes,
  fornecidas pelo mapeamento físico; o backend não cria DDL nem aplica
  fallback se a tabela estiver ausente.
- Nomes físicos, credenciais, tokens e strings de conexão não possuem default
  no código e não devem ser registrados neste documento.
- A conexão ADOdb fornecida pelo Scriptcase permanece emprestada e nunca pode
  ser fechada pelo backend.
- PlanoRepository, reconstrução de Planos e qualquer outro item da Sprint 5.3
  permanecem fora do escopo concluído.
