Você trabalhará no repositório deste backend PCA.

Use Goal Mode com o objetivo:

“Planejar e, somente após minha aprovação explícita, implementar a Sprint 5.2 — Sessão Oracle, unidade de trabalho, estratégia e locks.”

Trabalhe em português.

REGRAS DE CONDUÇÃO

- A Sprint 5.1 já foi concluída e commitada.
- Antes de qualquer trabalho, execute `git status --short` e inspecione o último commit.
- O worktree pode continuar contendo alterações locais preexistentes não relacionadas.
- Preserve rigorosamente todas essas alterações.
- Não reverta, normalize, reformate ou inclua arquivos alheios no escopo.
- Não use comandos destrutivos do Git.
- Não implemente nada antes de apresentar o plano e receber minha aprovação.
- Não inicie nem antecipe a Sprint 5.3.
- Não solicite credenciais, tokens, strings de conexão ou dados sensíveis.
- Não execute DDL ou probes reais sem autorização explícita.

ESTADO DE ENTRADA

- Fase 4 concluída.
- Sprint 5.1 concluída e versionada.
- Sprint 5.2 autorizável, mas ainda não iniciada.
- Linha de base: 339 testes e 1.139 asserções.
- Suíte contratual: 24 testes e 51 asserções.
- Gates obrigatórios Oracle/Scriptcase e PNCP confirmados.
- CNPJ alfanumérico: AINDA NÃO COMPROVADO e não bloqueante.
- Chamada única com 1.001 itens: AINDA NÃO COMPROVADA e não bloqueante.
- Limite operacional mantido em 1.000 itens por chamada.
- O gate Oracle comprovou ADOdb emprestado, `Param()`, mesma sessão,
  begin/commit/rollback, ausência de `Close()`, `FOR UPDATE NOWAIT` e
  contenção com `ORA-00054`.
- Caso o README raiz ainda indique que somente a Sprint 5.1 está autorizada,
  considere esse trecho desatualizado. Para a ordem atual, prevalece o roadmap.

ESCOPO DA SPRINT 5.2

- encapsular a conexão ADOdb emprestada;
- compartilhar a mesma sessão entre os adapters Oracle;
- implementar unidade de trabalho e transação local;
- implementar locks por âncoras permanentes;
- implementar leitura e adoção da estratégia anual;
- nunca fechar a conexão emprestada.

Referências principais: DI-003, DI-004, DI-007 e DI-008.

Não pertencem à Sprint 5.2:

- PlanoRepository e reconstrução de Planos;
- estado operacional completo;
- auditoria e observabilidade;
- transporte PNCP;
- Presentation;
- Composition Root;
- qualquer item das Sprints 5.3 em diante.

LEIA `.agents/README.md`

Se surgir incompatibilidade com uma Port ou fonte autoritativa, pare e
apresente a evidência antes de propor a alteração.