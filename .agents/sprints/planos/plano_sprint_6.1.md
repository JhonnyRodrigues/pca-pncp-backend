# Sprint 6.1 — Grafo manual sem Presentation

**Status:** Concluída em 31/08/2026 após os três microciclos autorizados.

## Resumo

Baseline confirmada no commit `3e07d08b64a93574823eb4b4437e8886fb10ce93`, com árvore de trabalho limpa e branch dois commits à frente de `origin/main`.

Criar, por TDD, um montador interno que concentre a topologia da Application e devolva `PublicarPcaUseCase` pronto para uso. O montador receberá implementações já construídas das nove Ports vigentes e o logger PSR-3.

A aprovação do plano autoriza somente o Microciclo 1. Cada microciclo terminará com diff, testes e pendências, exigindo nova autorização explícita para o seguinte.

## Interface e limites

- Criar `Semae\Pca\Composition\MontadorNucleoPublicacaoPca`.
- Injetar pelo construtor:
  `ContextoUsuarioPort`, `PlanoRepository`, `EstrategiaPublicacaoPort`, `UnidadeDeTrabalhoPort`, `AutenticadorPncpPort`, `LeitorPncpPort`, `EscritorPncpPort`, `EstadoOperacionalPublicacaoPort`, `RegistroAuditoriaPort` e `LoggerInterface`.
- Expor `montar(): PublicarPcaUseCase`, construindo explicitamente a política e os colaboradores internos existentes.
- Não restaurar `AutenticadorPncp`, `LeitorPncp` ou `EscritorPncp` nem criar aliases.
- Preservar os adapters `AutenticadorPncpAdapter`, `LeitorPncpAdapter` e `EscritorPncpAdapter`.
- Não alterar as demais Ports, `PlanoRepository`, `OraclePlanoRepository`, `MapeamentoPlanosOracle`, `MapeadorDadosPlanoOracle`, UseCase ou Domain.
- Não criar container, Service Locator, fachada, Controller, Presenter, perfil de implantação ou configuração global.
- Não instanciar recursos ou adapters concretos; configuração e ciclo controlado permanecem para as Sprints 6.2 e 8.1.

## Microciclos

1. **Contrato e montagem manual**
   - Registrar o plano aprovado da Sprint 6.1 já com os nomes `*PncpPort`.
   - Escrever primeiro o teste do montador.
   - Implementar o menor grafo explícito capaz de devolver o UseCase com todas as dependências por construtor.
   - Executar o teste específico e a suíte diretamente relacionada.
   - Pausar e solicitar autorização para o Microciclo 2.

2. **Integração interna do grafo**
   - Trabalhar sobre a versão atual de `FluxoConcretoApplicationTest`, que já utiliza as novas Ports.
   - Substituir somente sua montagem manual duplicada pelo novo montador.
   - Preservar integralmente cenários, doubles e resultados já demonstrados.
   - Confirmar que unidade de trabalho, política, Ports e logger compartilhados chegam aos colaboradores corretos.
   - Executar testes de Composition/Application e pausar para autorização do Microciclo 3.

3. **Reconciliação documental e gate**
   - Atualizar roadmap, estado operacional e documentação navegacional para registrar a Sprint 6.1 concluída e a Fase 6 ainda em andamento.
   - Preservar e incorporar as alterações já feitas em `catalogo_de_arquivos.md`, `glossario.md`, `guia_de_leitura_do_codigo.md` e `estado_atual.md`.
   - Manter `regras_de_negocio.md` completamente intocado.
   - Registrar a Sprint 6.2 como próxima etapa, dependente de planejamento e autorização próprios.
   - Executar suíte padrão completa, lint dos PHP alterados, PSR-4 estrito e `git diff --check`.
   - Inspecionar a direção das dependências e confirmar ausência de Presentation, container e Service Locator.

## Testes e aceitação

- O montador aceita exatamente as nove Ports vigentes e o logger.
- `montar()` devolve um `PublicarPcaUseCase` executável.
- O fluxo concreto continua cobrindo publicação, retificação, exclusão e modos individual/consolidado pelo novo ponto de montagem.
- Nenhuma referência às três interfaces PNCP antigas é reintroduzida.
- Domain permanece sem dependência de Composition ou Infrastructure.
- Nenhum teste chama Oracle, PNCP ou Scriptcase reais.
- Nenhuma dependência externa é adicionada.
- A baseline atual e as alterações do mantenedor serão preservadas; nenhum commit será criado.
