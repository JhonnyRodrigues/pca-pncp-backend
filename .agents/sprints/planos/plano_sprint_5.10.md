# Sprint 5.10 — Simplificação da justificativa e encerramento da Fase 5

**Status:** Concluída em 31/08/2026 após os quatro microciclos autorizados.

## Resumo

Substituir a exigência de localizar o último cancelamento homologado por uma justificativa institucional fixa:

> Exclusão integral do PCA por ausência de demandas vigentes no SGI.

O gatilho permanece: fotografia vigente do SGI vazia e PCA externo ativo. O PNCP exige justificativa textual, mas não sua vinculação à última demanda cancelada. [Manual oficial do PNCP](https://pncp.gov.br/manual/pt-br/latest/plano_de_contratacao/excluir_plano_de_contratacoes.html)

A implementação será dividida em microciclos. Cada microciclo exigirá autorização explícita antes de começar e terminará com diff, testes executados e pendências. Nenhum ciclo seguinte será iniciado automaticamente.

## Microciclos autorizáveis

### Microciclo 1 — Revisão normativa

- Revisar `RN-PUB-003`, DI-007, DI-008, DI-009, modelo de domínio e fluxo de implementação.
- Fixar o texto institucional e retirar a exigência da evidência histórica.
- Preservar o gatilho de exclusão, o procedimento de auditoria 9 e todos os critérios de confirmação externa.
- Registrar a investigação anterior como superada pela revisão normativa.
- Validar referências documentais e `git diff --check`.
- Não alterar código nesse ciclo.

### Microciclo 2 — TDD do fluxo simplificado

- Ajustar primeiro os testes do executor para exigir a justificativa fixa.
- Demonstrar os modos individual e consolidado.
- Remover do construtor interno de `ExecutorComandosUnidade` a dependência da Port de evidência.
- Construir `ComandoExcluirPca` com uma constante privada contendo o texto aprovado.
- Preservar sequencial obrigatório, rollback e tratamento de rejeição, incerteza e falha sistêmica.
- Executar os testes diretamente afetados e a suíte Application.
- Manter temporariamente os tipos antigos sem uso para limitar o ciclo.

### Microciclo 3 — Remoção da capacidade obsoleta

- Remover `EvidenciaCancelamentoHomologadoPort`, resultado, enum e Value Object correspondentes.
- Remover testes, fakes e imports exclusivos dessa capacidade.
- Retirar a suíte investigativa `tests/Contract/Sprint510`, sua configuração PHPUnit e seus comandos Composer.
- Preservar os documentos da investigação como registro histórico, sem conservar código executável sem consumidor.
- Confirmar ausência de referências órfãs, PSR-4 estrito, lint e `git diff --check`.

### Microciclo 4 — Reconciliação documental e gate final

- Atualizar roadmap, README, índice documental, catálogo, guia de leitura e estado operacional.
- Registrar a Sprint 5.10 como revisão normativa e simplificação concluída, sem apresentar a investigação Oracle como executada.
- Executar suíte Application, adapters, contratos controlados da Sprint 5.9, contratos legados, suíte padrão, lint, PSR-4 estrito e `git diff --check`.
- Auditar se todas as Ports vigentes possuem implementação concreta e se resta algum bloqueio da Infrastructure.
- Somente com todos os gates verdes, aceitar a DI-008 no escopo comprovado e marcar Sprint 5.10 e Fase 5 como concluídas.
- A Fase 6 ficará apenas apta a planejamento próprio; não será iniciada nem autorizada implicitamente.

## Interfaces e comportamento preservado

- Nenhuma fachada, contrato PNCP, serializer, adapter de produção ou API pública será alterada.
- A mudança de assinatura ficará restrita ao construtor interno de `ExecutorComandosUnidade`.
- O procedimento de auditoria 9 continuará registrando a exclusão integral como atualmente.
- Não será repetido o gate PNCP real, pois o formato externo permanece igual e uma exclusão real já foi comprovada.
- Alterações locais alheias à tarefa serão preservadas e nenhum commit será criado.

## Critérios de aceitação

- Exclusões individual e consolidada enviam exatamente o texto fixo aprovado.
- SGI vazio com PCA ativo continua produzindo `EXCLUIR_PCA`, ainda que o PNCP possua itens.
- PCA já ausente continua sem nova exclusão.
- Sequencial ausente, rollback e resultados PNCP mantêm o comportamento vigente.
- Não resta código ou documentação vigente afirmando que a última justificativa de cancelamento precisa ser recuperada.
- Cada microciclo permanece isoladamente consistente e só avança mediante nova autorização explícita.
