# Sprint 5.2 — Plano revisado com microciclos obrigatórios

## Resumo

Implementar somente a Infrastructure Oracle necessária à Sprint 5.2, sem alterar Ports da Application, executar DDL/probes reais ou antecipar a Sprint 5.3.

Cada microciclo terá TDD, validações e pausa obrigatória. O microciclo seguinte somente começará após validação explícita do anterior.

## Namespaces e interfaces

Novos componentes ficarão sob `Semae\Pca\Infrastructure\Oracle`:

- `Adodb\ConexaoAdodbEmprestada`;
- `Transacao\OracleUnidadeDeTrabalhoAdapter`;
- `Estrategia\OracleEstrategiaPublicacaoAdapter`;
- mapeamentos físicos específicos nos respectivos namespaces;
- exceção Oracle interna e sanitizada, se necessária.

Os nomes `Adapter` distinguirão implementações concretas das Ports `UnidadeDeTrabalhoPort` e `EstrategiaPublicacaoPort`.

As Ports, Results e Value Objects existentes não serão modificados.

## Microciclos

### 1. `ConexaoAdodbEmprestada`

- Encapsular um único objeto ADOdb emprestado.
- Validar a presença de `BeginTrans`, `CommitTrans`, `RollbackTrans`, `Execute`, `GetAll`, `Param`, `ErrorNo` e `ErrorMsg`.
- Converter placeholders `?` em binds nomeados produzidos por `Param()`.
- Controlar o estado transacional e impedir início aninhado.
- Sanitizar falhas, expondo no máximo o código `ORA-*`.
- Não expor `_connectionID`, `Close()`, destructor com efeitos ou o objeto ADOdb interno.
- Testar binds, leituras, comandos, begin/commit/rollback, falhas e ausência de fechamento.

**Validação e pausa:** executar testes focados, suíte padrão, lint dos novos arquivos e inspeção do diff; apresentar os resultados e aguardar validação antes do Microciclo 2.

### 2. Unidade de trabalho e âncoras

- Criar mapeamento físico obrigatório e validado para:
  - schema;
  - tabela de âncoras existente;
  - coluna de escopo;
  - coluna do exercício;
  - coluna do identificador-alvo;
  - identificador fixo, não vazio, do alvo anual.
- Implementar `OracleUnidadeDeTrabalhoAdapter`.
- Traduzir a chave para `ESCOPO + ANO_EXERCICIO + IDENTIFICADOR_ALVO`:
  - escopo anual com alvo explicitamente injetado;
  - unidade externa com código da UASG como alvo.
- Provisionar a linha por DML idempotente em transação técnica curta.
- Tratar corrida `ORA-00001` como âncora provisionada por outra sessão.
- Adquirir o lock de negócio com `SELECT ... FOR UPDATE NOWAIT`.
- Traduzir `ORA-00054` para `BLOQUEADA`, com rollback imediato.
- Manter a transação aberta somente após aquisição bem-sucedida.
- Implementar confirmação, desfazimento e cleanup idempotente após bloqueio.
- Nunca criar ou executar DDL.
- Se o mapeamento estiver incompleto ou surgir `ORA-00942`/evidência de tabela inexistente, interromper o avanço e reportar a lacuna; não criar tabela nem aplicar fallback.

**Validação e pausa:** testar provisionamento, concorrência simulada, chaves, contenção, rollback, commit, falhas e sessão compartilhada; executar regressão e aguardar validação antes do Microciclo 3.

### 3. Estratégia anual

- Criar mapeamento físico obrigatório e allowlisted para:
  - tabela e colunas chave/valor da configuração;
  - literais físicos dos modos, sem defaults;
  - tabela e colunas de exercício, UO, modo adotado e UASG adotada em `PAC_PLANOS`;
  - relação física entre Plano e cadastro de UASG.
- O plano físico do lookup da UASG consolidadora declarará obrigatoriamente:
  - tabela;
  - coluna do código;
  - coluna da descrição;
  - coluna e valor do filtro de ativo;
  - critério de unicidade: código mais filtro ativo deve retornar exatamente uma linha.
- Ausência, duplicidade, inatividade ou descrição vazia produzirão estratégia inválida ou falha tipada, sem descrição sintética ou fallback.
- Implementar `OracleEstrategiaPublicacaoAdapter`:
  - consultar estratégia corrente sem default;
  - distinguir ausência/incoerência de falha Oracle;
  - consultar a estratégia adotada e rejeitar estados parciais ou conflitantes;
  - exigir transação ativa para adoção;
  - atualizar somente linhas ainda sem estratégia;
  - propagar UASG por linha no modo individual e UASG única no consolidado;
  - reler e validar o resultado antes de informar adoção concluída;
  - nunca abrir ou confirmar autonomamente a transação da Application.
- Demonstrar que este adapter e o de unidade de trabalho utilizam a mesma `ConexaoAdodbEmprestada`.

**Validação e pausa:** executar testes focados, suíte padrão, suíte contratual não real, lint, autoload PSR-4 e inspeção do diff. Somente após código integralmente verde e validação explícita avançar à documentação.

### 4. Documentação final

- Criar `docs/vigentes/operacional/estado_atual.md` somente neste microciclo.
- Limitar sua autoridade a progresso, última sprint concluída, próxima sprint na ordem, gates, pendências e restrições.
- Registrar os resultados reais das suítes, Sprint 5.2 concluída e Sprint 5.3 não iniciada nem autorizada implicitamente.
- Registrar gates Oracle/Scriptcase e PNCP confirmados, CNPJ alfanumérico e chamada acima de 1.000 itens não comprovados e não bloqueantes, além do limite de 1.000 itens.
- No README raiz, substituir o estado detalhado por link ao documento operacional.
- No Roadmap, remover apenas progresso corrente duplicado e preservar ordem, escopo, critérios e gates.
- Em `docs/README.md`, registrar a categoria operacional e sua autoridade limitada.
- No catálogo, registrar o novo documento e os componentes/testes de Infrastructure.
- Não alterar RN, DI, Modelo, Fluxo ou Arquitetura.

**Validação e pausa final:** executar suítes completa e contratual não real, lint, autoload PSR-4, `git diff --check` e conferir que todas as alterações preexistentes continuam preservadas.

## Critérios gerais de aceitação

- TDD em cada microciclo.
- Nenhuma chamada real ao Oracle ou PNCP.
- Nenhum DDL criado ou executado.
- Nenhuma credencial, conexão ou nome físico real solicitado.
- Nenhum `Close()`, `_connectionID`, OCI8 próprio ou PDO_OCI.
- Nenhum `PlanoRepository`, estado operacional completo, auditoria, observabilidade, PNCP, Presentation ou Composition Root.
- Nenhum trabalho da Sprint 5.3.
- Nenhum commit sem solicitação explícita.
