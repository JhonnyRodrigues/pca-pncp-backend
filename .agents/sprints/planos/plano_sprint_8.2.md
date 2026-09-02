# Sprint 8.2 — Integração Oracle real

**Status:** em execução; Microciclos 1 a 5 concluídos em 02/09/2026. O
Microciclo 6 aguarda autorização explícita para regressão e encerramento.

## Resumo e alcance da evidência

Criar um gate opt-in próprio da Sprint 8.2, executado no Scriptcase de homologação, reutilizando a massa dedicada da Sprint 5.9 e o registro/perfil final da Sprint 8.1.

A Sprint 8.2 comprovará:

- a subcomposição Oracle real usada pelo `CompositionRootPublicacaoPca`;
- os seis adapters Oracle criados por uma única receita de produção;
- bind, início, commit e rollback reais;
- locks `FOR UPDATE NOWAIT` e contenção;
- mesma sessão entre todos os adapters;
- reconstrução, confirmação temporária, estado operacional e auditoria;
- restauração integral da massa após rollback;
- preservação da conexão ADOdb emprestada.

O `CompositionRootPublicacaoPca` pode ser montado com a conexão e o perfil reais sem executar PNCP, pois a construção dos adapters HTTP não dispara autenticação nem rede. O gate poderá comprovar essa montagem sem I/O.

Entretanto, não é possível exercitar operações Oracle através da fachada retornada pelo Root até o UseCase sem alcançar posteriormente a autenticação PNCP. Como não será criado seam artificial, a evidência ficará precisamente limitada a:

1. montagem real do Root sem I/O;
2. uso obrigatório da mesma subcomposição Oracle pelo Root;
3. exercício Oracle real dessa subcomposição pelo gate.

A Sprint 8.2 não comprovará o fluxo público integral `PublicadorPcaScriptcase → Fachada → UseCase`. Os fluxos completos permanecem nas sprints posteriores.

## Composição e interfaces

### Receita Oracle única

- Extrair a criação dos seis adapters Oracle para uma subcomposição interna de produção.
- O `CompositionRootPublicacaoPca` será obrigatoriamente refatorado para usar essa subcomposição.
- Ela será a única receita vigente para criar:
  - `OracleContextoUsuarioAdapter`;
  - `OraclePlanoRepository`;
  - `OracleEstrategiaPublicacaoAdapter`;
  - `OracleUnidadeDeTrabalhoAdapter`;
  - `OracleEstadoOperacionalPublicacaoAdapter`;
  - `OracleRegistroAuditoriaAdapter`.
- O gate da Sprint 8.2 usará exatamente a mesma subcomposição.
- Não haverá branch, parâmetro, implementação alternativa ou comportamento especial para testes.
- A subcomposição permanecerá interna ao backend e não será exposta à Blank, à fachada ou à Presentation.
- Todos os adapters receberão a mesma instância de `ConexaoAdodbEmprestada`; isso será comprovado por testes estruturais e pelo fingerprint da sessão durante o exercício real.
- PNCP, logger e demais participantes continuarão sendo montados exclusivamente pelo Root final.

### Carregamento do perfil

- Manter inalterada a API pública `PublicadorPcaScriptcase::executar(...)`.
- Extrair o carregamento protegido do registro/perfil para um componente interno compartilhado pelo entrypoint e pelo gate.
- Preservar literalmente as regras da Sprint 8.1: allowlist exata, caminhos absolutos externos à release, ausência de defaults e falha fechada.

### Gate contratual

Adicionar o entrypoint exclusivo da Blank protegida:

```php
executar_integracao_oracle_sprint_82(
    object $db,
    string $login,
    string $serverName,
    string $mode = 'adapters'
): array
```

Modos permitidos:

- `commit`;
- `adapters`;
- `lock_holder`;
- `lock_expect_blocked`;
- `lock_expect_available`.

Adicionar `PCA_SPRINT82_CONFIG_FILE`, apontando para configuração externa com estrutura exata:

- `allow_writes`;
- `server_name` esperado;
- ano e UO da massa;
- sequencial temporário;
- marcador exclusivo de auditoria;
- duração controlada do lock.

Mapeamentos, CNPJ e credenciais não serão duplicados: virão do perfil final selecionado por `PCA_PNCP_PROFILE_REGISTRY_FILE`.

## Microciclos e pausas obrigatórias

1. **Contrato externo e carregamento compartilhado**
   - Registrar o plano aprovado.
   - Implementar por TDD a configuração externa, o exemplo sem dados reais, o carregador compartilhado do perfil e o preflight sem Oracle.
   - Recusar produção, escrita não autorizada, caminhos internos, estrutura desconhecida, servidor divergente e perfil inválido.
   - Pausar após testes focados e regressão completa do entrypoint da Sprint 8.1.

2. **Subcomposição Oracle única**
   - Extrair a receita interna dos seis adapters.
   - Alterar obrigatoriamente o `CompositionRootPublicacaoPca` para consumir essa receita.
   - Fazer o gate consumir a mesma receita, sem variações para teste.
   - Testar que os seis adapters recebem a mesma instância de `ConexaoAdodbEmprestada`.
   - Confirmar que montar o Root real não chama ADOdb nem executa rede PNCP.
   - Pausar após regressões de Composition, Presentation e integração.

3. **Runner transacional e relatórios seguros**
   - Implementar os modos `commit` e `adapters`.
   - Reutilizar o exercício Oracle comprovado na Sprint 5.9 por suporte contratual compartilhado, preservando integralmente suas APIs e relatórios antigos.
   - Implementar relatório por estágios, rollback best-effort, guarda contra `Close()` e sanitização.
   - Adicionar configuração PHPUnit e comandos Composer específicos da 8.2.
   - Pausar após Red → Green → Refactor, suítes padrão e contratuais verdes.

4. **Prova transacional em homologação**
   - O mantenedor provisionará os arquivos externos e executará o preflight na Blank protegida.
   - Executará os modos `commit` e `adapters` e enviará somente os JSONs sanitizados.
   - Qualquer falha interromperá o avanço e manterá a sprint aberta.
   - Pausar para conferir a evidência e a restauração da massa.

5. **Prova real de contenção**
   - Em duas sessões Scriptcase, executar `lock_holder` e, durante sua retenção, `lock_expect_blocked`.
   - Após a liberação, executar `lock_expect_available`.
   - Exigir contenção imediata traduzida de `ORA-00054`, disponibilidade posterior, sessão estável e ausência de `Close()`.
   - Pausar após conferência dos três relatórios.

6. **Encerramento**
   - Executar regressões, lint, PSR-4, inspeções de dependência e `git diff --check`.
   - Atualizar plano, README operacional, catálogo e estado atual somente com evidências efetivamente obtidas.
   - Marcar a Sprint 8.2 concluída e a 8.3 como próxima somente se todos os modos reais forem confirmados.
   - Não alterar regras, DI ou Roadmap autoritativo, salvo incompatibilidade concreta que reabra DI-008 ou DI-010.

Cada microciclo exige autorização explícita própria e termina obrigatoriamente com uma pausa.

## Evidência específica do modo `commit`

O modo `commit` terá duas transações claramente separadas no relatório.

### Provisionamento técnico

O adapter poderá executar a transação técnica idempotente já existente para garantir a linha permanente de concorrência.

O relatório identificará separadamente:

- início da transação técnica;
- execução do provisionamento idempotente;
- commit técnico;
- linha já existente ou disponível após o provisionamento, quando observável.

Esse commit não contará como evidência principal do modo `commit`.

### Transação controlada principal

Depois do provisionamento, o gate deverá:

1. iniciar explicitamente uma nova transação Oracle;
2. adquirir o lock real `FOR UPDATE NOWAIT`;
3. comprovar que o lock pertence ao mesmo fingerprint de sessão;
4. não chamar Repository, estado operacional ou auditoria para escrita;
5. confirmar a unidade de trabalho por `CommitTrans`;
6. comprovar que a transação foi encerrada;
7. readquirir controladamente o mesmo lock em nova transação, demonstrando sua liberação pelo commit anterior;
8. desfazer essa transação de verificação;
9. executar nova consulta na mesma conexão, comprovando sua reutilização;
10. confirmar que `Close()` não foi chamado.

O relatório distinguirá explicitamente:

- `commit_tecnico_provisionamento`;
- `transacao_controlada_iniciada`;
- `lock_controlado_adquirido`;
- `mesma_sessao`;
- `commit_controlado_concluido`;
- `lock_liberado_apos_commit`;
- `conexao_reutilizavel`;
- `alteracao_negocio_executada: false`;
- `close_invocado: false`.

A sequência de chamadas ADOdb será registrada internamente pela guarda contratual para impedir que o commit técnico seja confundido com o commit controlado principal.

## Testes e critérios de aceitação

- Configuração: caminho ausente, relativo ou interno; estrutura adicional; tipos inválidos; `allow_writes=false`; servidor divergente; perfil `PRODUCAO`.
- Composição: única receita usada pelo Root e pelo gate; seis adapters com a mesma conexão; nenhum branch de teste; montagem sem I/O.
- Commit: transação principal distinta do provisionamento, lock adquirido, commit concluído, lock liberado e readquirível, conexão reutilizável, nenhuma alteração de negócio.
- Adapters: bind preservado, estratégia e contexto resolvidos, identidade única e mesma sessão.
- Massa: confirmação inicialmente pendente, estado temporário distinto e contagem de auditoria conhecida.
- Rollback: confirmação volta a pendente, estado original é restaurado e auditoria retorna à contagem anterior.
- Locks: detentor, bloqueio imediato e disponibilidade posterior.
- Segurança: nenhum segredo, caminho, SQL, hostname, CNPJ ou dado da massa aparece no relatório; `Close()` nunca é chamado.
- Regressão: suítes padrão, Infrastructure Oracle, contratos 5.9 e contratos 8.2 verdes; lint, PSR-4 e direção das dependências aprovados.

## Premissas e limites

- Ambiente exclusivo: homologação Scriptcase; produção será recusada.
- A massa da Sprint 5.9 permanece dedicada e restaurável.
- O marcador de auditoria e o sequencial temporário da 8.2 serão novos.
- A linha técnica permanente de concorrência poderá ser provisionada idempotentemente; não haverá DDL.
- Todas as alterações de negócio do modo `adapters` serão temporárias e protegidas por rollback.
- O mantenedor executará a Blank e enviará somente relatórios sanitizados.
- PNCP, fluxo público integral, renderização da Blank e `sc_log_add` permanecem fora da Sprint 8.2.
- Nenhum commit Git será criado.

## Evidências do Microciclo 1

- O Red focado executou sete testes, com cinco erros e duas falhas causados
  exclusivamente pela ausência do carregador compartilhado e da configuração
  da Sprint 8.2.
- O Green transversal aprovou 24 testes e 332 asserções, cobrindo carregador,
  configuração, entrypoint público e integração anterior ao I/O externo.
- A suíte padrão aprovou 867 testes e 3.791 asserções.
- A suíte contratual da Sprint 5.9 permaneceu verde com 54 testes e 149
  asserções; o contrato novo da Sprint 8.2 aprovou 5 testes e 14 asserções.
- O autoload PSR-4 estrito gerou 1.766 classes, e o lint aprovou os oito
  arquivos PHP afetados diretamente.
- O entrypoint preservou sua assinatura e agora delega a única leitura do
  registro/perfil a `CarregadorPerfilPublicacaoPca`, mantendo seleção literal,
  validação integral do registro e caminhos externos à release.
- `PCA_SPRINT82_CONFIG_FILE` exige consentimento explícito, estrutura exata,
  massa tipada e servidor idêntico ao recebido; o preflight materializa o
  perfil final, aceita somente homologação e não acessa Oracle ou PNCP.
- Nenhum Composition Root, adapter Oracle, runner transacional ou relatório
  real foi alterado ou antecipado. Nenhum acesso externo ou commit foi feito.

## Evidências do Microciclo 2

- O Red focado executou seis testes e 152 asserções; o único erro foi causado
  pela ausência de `ComposicaoOraclePublicacaoPca`.
- O Green focado aprovou nove testes e 205 asserções. A regressão conjunta de
  Composition, Presentation e Integration aprovou 46 testes e 593 asserções.
- A suíte padrão aprovou 868 testes e 3.804 asserções.
- A suíte contratual da Sprint 5.9 permaneceu verde com 54 testes e 149
  asserções; os contratos da Sprint 8.2 aprovaram 6 testes e 21 asserções.
- O autoload PSR-4 estrito gerou 1.770 classes, e o lint aprovou os treze
  arquivos PHP afetados diretamente até este microciclo.
- `CompositionRootPublicacaoPca` passou a consumir obrigatoriamente
  `ComposicaoOraclePublicacaoPca`. A auditoria encontrou as seis construções
  concretas somente nessa subcomposição, sem receita paralela no código de
  produção.
- A prova estrutural confirmou que os seis adapters recebem a mesma instância
  de `ConexaoAdodbEmprestada`; o contrato 8.2 instancia diretamente essa
  subcomposição de produção e nenhuma montagem executou I/O.
- A subcomposição não contém branches, parâmetros ou implementações de teste,
  não lê ambiente e não foi exposta à Blank ou à Presentation.
- Nenhum runner transacional, relatório real, acesso Oracle/PNCP ou commit foi
  criado neste microciclo.

## Evidências do Microciclo 3

- O Red contratual executou onze testes e 21 asserções; os cinco erros foram
  causados exclusivamente pela ausência do suporte compartilhado, do relatório
  de falha e do entrypoint 8.2.
- O Green contratual da Sprint 8.2 aprovou 12 testes e 43 asserções. O contrato
  5.9 permaneceu verde com 54 testes e 149 asserções, preservando assinatura,
  modos e formato dos relatórios existentes.
- A suíte padrão aprovou 868 testes e 3.804 asserções. A suíte focada de
  Infrastructure Oracle aprovou 130 testes e 682 asserções.
- O autoload PSR-4 estrito gerou 1.782 classes; o lint aprovou os 23 arquivos
  PHP diretamente afetados até este microciclo. O `composer.json` permaneceu
  válido, com apenas o aviso preexistente de licença ausente.
- O gate 8.2 monta o `CompositionRootPublicacaoPca` real sem executar ADOdb ou
  PNCP e exercita `ComposicaoOraclePublicacaoPca` diretamente, sem atravessar a
  fachada ou criar seam de teste.
- O exercício Oracle compartilhado mantém o fluxo `adapters` e os locks da
  Sprint 5.9, adicionando à 8.2 o modo `commit` com registro ordenado das
  chamadas ADOdb, rollback best-effort e guarda que proíbe `Close()`.
- O relatório do modo `commit` separa início, execução e commit do
  provisionamento técnico da nova transação controlada, do lock real, do
  `CommitTrans` principal, da readquisição posterior e da reutilização da
  mesma conexão. Nenhuma escrita de negócio participa desse modo.
- Relatórios de falha permanecem genéricos por padrão; o diagnóstico opt-in é
  restrito aos modos `commit` e `adapters` e sanitiza credenciais, CNPJ, URL,
  SQL e valores sensíveis conhecidos.
- Foram adicionados `contract:oracle82:tests` e
  `contract:oracle82:preflight`. Nenhum Oracle, PNCP, DDL, Blank real ou commit
  Git foi executado neste microciclo.

## Evidências do Microciclo 4

- O preflight foi confirmado no PHP 8.1 `cgi-fcgi` da Blank protegida de
  desenvolvimento, com perfil de homologação, escrita Oracle autorizada e
  nenhuma chamada Oracle ou PNCP durante sua execução. O mesmo preflight foi
  repetido internamente antes de cada modo real aprovado.
- O modo `commit` foi aprovado com o hash sanitizado
  `4d0111f16fa205ecca028a3dc8f1cf2c0ea5b567432f379e453e03a19d3bd6d7`.
  Ele distinguiu a transação e o commit técnico de provisionamento da nova
  transação controlada, do lock real, do `CommitTrans` principal e da
  readquisição posterior do lock.
- O modo `commit` também confirmou fingerprint estável da sessão, encerramento
  da transação principal, conexão reutilizável, ausência de alteração de
  negócio, ausência de `Close()` e montagem do Composition Root final sem I/O.
- O modo `adapters` foi aprovado com o hash sanitizado
  `c21808c0d15d79095dcc79b78f82d1a3e1ac7e2ce612839170d3f2018d36c707`.
  Foram confirmados binds, mesma sessão, contexto e leituras tipadas, escrita
  visível na transação e atomicidade entre confirmação, estado e auditoria.
- A verificação posterior confirmou que o rollback restaurou integralmente a
  confirmação pendente, o estado original e a contagem anterior de auditoria;
  a conexão emprestada permaneceu utilizável e não recebeu `Close()`.
- Duas recusas operacionais precederam a aprovação: login vazio na Blank e UO
  da 8.2 divergente da massa dedicada da 5.9. Ambas foram corrigidas somente na
  configuração hospedeira, sem alteração do backend ou da regra de negócio.
- Por decisão explícita do mantenedor, registro, perfis e configuração estão
  sob o webroot somente neste ambiente controlado de desenvolvimento. Essa é
  uma exceção operacional local e não autoriza a mesma disposição em produção,
  onde os arquivos continuarão obrigatoriamente externos ao webroot e à
  release.
- Os relatórios aprovados não exibiram caminho, hostname, login, CNPJ, SQL,
  credenciais ou dados da massa. Nenhum PNCP, DDL ou commit Git foi executado.

## Evidências do Microciclo 5

- Duas sessões Scriptcase independentes executaram a prova real de contenção.
  A Sessão A manteve `lock_holder` aguardando enquanto a Sessão B executou
  `lock_expect_blocked`.
- `lock_expect_blocked` retornou `BLOQUEADA` antes do término da Sessão A,
  confirmou tradução de `ORA-00054` e produziu o hash sanitizado
  `6037ce8054aedf777f41425b0f422a854137eaedcc07ec8d58767e32d4d26c84`.
- `lock_holder` retornou `INICIADA`, liberou sua transação ao final e produziu
  o hash sanitizado
  `a0b5a315104cdf2af366c3ced1cc89d6d456ec7271f66d01ba8b61f1d72345bd`.
- Depois da liberação, `lock_expect_available` readquiriu a mesma chave com
  situação `INICIADA` e produziu o hash sanitizado
  `cea92f6a4a93ab0588f0405aad4e76dccd3d7d93ef55041629cce551ce20e028`.
- Os três modos confirmaram liberação do lock ao final, ausência de `Close()`
  e montagem do Composition Root final sem I/O. Nenhum deles executou escrita
  de negócio, PNCP ou DDL.
- Os relatórios aprovados permaneceram sanitizados e não exibiram caminho,
  hostname, login, CNPJ, SQL, credenciais ou dados da massa.

## Validações e limites

- TDD focado, regressões relacionadas e suíte padrão;
- contratos das Sprints 5.9 e 8.2, lint, PSR-4 e direção das dependências;
- relatórios sanitizados e rollback best-effort;
- somente homologação, com produção recusada;
- nenhuma chamada PNCP, DDL, seam artificial ou commit Git;
- conclusão somente após todos os relatórios reais serem confirmados.

## Evidências do Microciclo 6

- A suíte padrão aprovou 868 testes e 3.804 asserções; a suíte focada de
  Infrastructure aprovou 477 testes e 1.807 asserções.
- Os contratos opt-in das Sprints 5.1, 5.9 e 8.2 aprovaram, respectivamente,
  24/51, 54/149 e 12/43 testes/asserções. Nenhum grupo real, preflight externo
  ou acesso Oracle/PNCP foi repetido durante o encerramento.
- O autoload PSR-4 estrito gerou 1.782 classes. O lint aprovou 317 arquivos PHP,
  e `composer.json` foi validado com apenas o aviso preexistente de licença
  ausente.
- A inspeção de dependências não encontrou referência proibida no Domain ou na
  Application. As seis construções concretas dos adapters Oracle existem no
  código de produção somente em `ComposicaoOraclePublicacaoPca`, consumida pelo
  `CompositionRootPublicacaoPca`.
- `git diff --check` aprovou todo o escopo da Sprint 8.2. A alteração local
  preexistente de `AGENTS.md`, pertencente ao mantenedor, foi preservada e
  excluída dessa inspeção por conter whitespace fora deste escopo.
- Foi criado o guia vigente de execução das probes 5.1, 5.9 e 8.2, com roteiro
  para desenvolvedores iniciantes, configuração externa, Blanks, modos,
  contenção, limpeza PNCP, leitura dos relatórios e diagnóstico. O README foi
  reduzido a comandos e referência para esse guia.
- Catálogo, índice documental, guia de leitura e estado operacional foram
  atualizados somente com as evidências efetivamente obtidas. Nenhuma RN, DI ou
  etapa do Roadmap autoritativo foi alterada.
- Todos os modos reais exigidos foram confirmados. A Sprint 8.2 está concluída,
  e a Sprint 8.3 — Suíte PNCP de treinamento é a próxima etapa na ordem,
  dependente de planejamento e autorização próprios.
- Nenhum commit Git foi criado.
