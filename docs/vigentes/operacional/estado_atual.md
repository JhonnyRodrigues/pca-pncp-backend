# Estado operacional do projeto PCA/PNCP

**Classificação documental:** Operacional

**Status:** Vigente

**Autoridade/Escopo:** progresso do projeto, última sprint concluída, próxima
sprint na ordem, gates, pendências e restrições operacionais

**Atualizado em:** 24/08/2026

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
- A Sprint 5.3 — Mapeamento e PlanoRepository está concluída.
- A última sprint concluída é a Sprint 5.3.
- A próxima sprint na ordem é a Sprint 5.4 — Estado operacional, contexto,
  auditoria e observabilidade.
- A Sprint 5.4 não foi iniciada e este registro não constitui autorização
  implícita para iniciá-la.

## Evidências da Sprint 5.3

- Os mapeamentos de Planos, Demandas e Unidades Orçamentárias validam os
  identificadores Oracle e não possuem nomes físicos implícitos.
- A tradução física usa aliases canônicos, datas com máscaras explícitas,
  fuso operacional injetado e decimais exatos sem `float`.
- A descoberta retorna identidades distintas e ordenadas, exige cadastro ativo
  único de UO e rejeita ausência ou incoerência de Lotação e UASG.
- A reconstrução exige transação ativa, seleciona a maior versão sem usar a
  confirmação e reúne Demandas Homologadas, Compradas e Publicadas de todas as
  versões de origem.
- Plano vazio permanece reconstruível; cardinalidade, duplicidade e conteúdo
  físico inválido são rejeitados antes de atravessar a Infrastructure.
- A confirmação confere a identidade e versão exatas, atualiza somente a linha
  pendente com `SYSDATE` e relê a intenção, sem mutar o Plano carregado.
- O Repository reutiliza a conexão e a transação emprestadas, sem `Close()`,
  commit ou rollback autônomo.
- A suíte padrão aprovou 428 testes e 1.511 asserções.
- A suíte contratual não real aprovou 24 testes e 51 asserções.
- A suíte focada da Infrastructure Oracle aprovou 89 testes e 372 asserções.

Nenhum probe real Oracle ou PNCP e nenhum DDL foram executados durante a
Sprint 5.3.

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
- Estado operacional da publicação, contexto do usuário, auditoria e adaptação
  de observabilidade permanecem no escopo ainda não iniciado da Sprint 5.4.
