# Sprint 6.3 — Gate do núcleo executável

**Status:** Concluída em 31/08/2026 após os quatro microciclos autorizados.

## Resumo

Encerrar a Fase 6 comprovando que o núcleo permanece executável, corretamente
composto e independente de Presentation.

A sprint será de validação e documentação: não são esperadas alterações em
`src/` ou `tests/`, nem a criação de um teste arquitetural adicional. A
aprovação inicial autoriza somente o Microciclo 1; cada ciclo terminará com
diff, evidências e pausa obrigatória.

## Microciclos

1. **Baseline e gate da integração controlada**
   - Confirmar novamente HEAD, árvore de trabalho e alterações acumuladas das
     Sprints 6.1 e 6.2.
   - Registrar o plano aprovado em
     `.agents/sprints/planos/plano_sprint_6.3.md`.
   - Executar separadamente as suítes de Composition e Integration.
   - Confirmar que os nove adapters concretos continuam montados sobre ADOdb e
     cURL controlados, sem Oracle, PNCP ou Scriptcase reais.
   - Validar os três cenários vigentes: convergência sem escrita, retificação e
     falha final reconciliável.
   - Pausar antes do Microciclo 2.

2. **Gate unitário e direção das dependências**
   - Executar separadamente as suítes de Domain e Application.
   - Auditar os namespaces e imports de produção:
     - Domain não depende de Application, Infrastructure, Composition ou
       Presentation;
     - Application depende apenas de Application, Domain e PSR-3;
     - o montador intermediário depende apenas de Application, Domain e PSR-3,
       sem selecionar adapters concretos;
     - Infrastructure continua implementando as nove Ports internas.
   - Confirmar ausência de container, Service Locator, configuração global e
     aliases das antigas Ports PNCP.
   - Demonstrar que nenhuma classe de Presentation é necessária para construir
     e executar `PublicarPcaUseCase`.
   - Pausar antes do Microciclo 3.

3. **Gate completo de regressão**
   - Executar:
     - suíte padrão;
     - suíte dos adapters;
     - contratos controlados legados;
     - contratos controlados dos adapters;
     - autoload PSR-4 estrito;
     - lint de todos os PHP de `src/` e `tests/`;
     - `git diff --check`.
   - Confirmar que os contratos controlados excluem `pncp-live`.
   - Não executar preflight, grupos live, probes ou qualquer integração externa.
   - Se surgir defeito de produção ou teste, interromper o ciclo e apresentar a
     evidência antes de autorizar qualquer correção.
   - Pausar antes do Microciclo 4.

4. **Reconciliação documental e encerramento da fase**
   - Somente com todos os gates verdes, marcar a Sprint 6.3 e a Fase 6 como
     concluídas.
   - Atualizar roadmap, estado operacional e guia de leitura com os resultados
     efetivamente obtidos.
   - Registrar a Sprint 7.1 — Controller e entrada tipada como próxima etapa,
     ainda não planejada nem autorizada.
   - Preservar as alterações vigentes do catálogo e glossário; alterá-los
     somente se a revisão identificar referência que tenha ficado objetivamente
     desatualizada.
   - Atualizar o status do plano da Sprint 6.3 e validar referências,
     `git diff --check`, estado do Git e diff final.
   - Apresentar arquivos alterados, contagens dos gates e pendências.

## Interfaces e limites

- Nenhuma API, classe de produção, teste ou dependência externa deverá ser
  criada ou modificada.
- Não antecipar Presentation, Controller, Presenter, ViewModels, fachada,
  Composition Root final, perfil de implantação ou integração Scriptcase.
- O montador da Sprint 6.1 permanece um wiring intermediário: não passa a
  selecionar adapters ou recursos concretos.
- `regras_de_negocio.md` permanecerá completamente intocado.
- Todas as alterações locais existentes serão preservadas e nenhum commit será
  criado.

## Critérios de aceitação

- Composition, Integration, Domain, Application, suíte padrão, adapters e
  contratos controlados aprovados.
- Os três cenários controlados da Sprint 6.2 continuam executáveis sem I/O real.
- Domain e Application mantêm a direção correta das dependências.
- O núcleo pode ser construído e executado sem qualquer classe de Presentation.
- Nenhuma referência às antigas interfaces `AutenticadorPncp`, `LeitorPncp` ou
  `EscritorPncp` reaparece.
- PSR-4 estrito, lint e `git diff --check` aprovados.
- A Fase 6 somente será encerrada após todas essas evidências; a Fase 7 não será
  iniciada implicitamente.
