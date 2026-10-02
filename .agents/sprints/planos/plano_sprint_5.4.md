# Sprint 5.4 — Estado operacional, contexto, auditoria e eventos técnicos

## Resumo

Implementar com TDD os adapters das três Ports existentes e uma capacidade independente de produzir, acumular e preparar eventos técnicos para exportação futura.

Preservar Domain, `PublicarPcaResponse`, fachada e assinaturas das Ports. Cada incremento terá validação e pausa obrigatória. Nenhuma integração com Scriptcase ou `SEGURANCA_LOG` será executada nesta sprint.

## Interfaces e decisões

- Manter inalteradas `EstadoOperacionalPublicacaoPort`, `ContextoUsuarioPort`, `RegistroAuditoriaPort`, `PublicarPcaResponse` e `CorrelacaoExecucao`.
- Reutilizar a mesma `ConexaoAdodbEmprestada` nos adapters Oracle, com mapeamentos allowlisted, binds e nenhum controle transacional autônomo.
- Criar `EventoTecnico` imutável e um acumulador que implemente PSR-3, sem I/O externo.
- `DESCRIPTION.action` identificará a operação técnica; códigos de `FalhaSegura` ficarão em `error.code` ou `metadata`.
- Não modelar ainda o valor físico de `SEGURANCA_LOG.ACTION`.
- Não adicionar eventos à Response nem implementar sua exposição pela fachada.

## Incrementos revisados

1. **Estado operacional em `PAC_PLANOS`**
   - Mapear tabela, UO, exercício, modo, UASG, estado, sequencial e literais sem defaults.
   - Exigir transação ativa para consulta autoritativa e registro.
   - No individual, ler e propagar todas as versões da UO/exercício; no consolidado, todas as linhas do exercício.
   - Compor a projeção usando valores não nulos coerentes e admitir metadados nulos em versões novas.
   - Interpretar ausência integral de estado/sequencial como `NAO_PUBLICADO`, desde que modo e UASG estejam coerentes com a unidade.
   - Rejeitar pares parciais, literais desconhecidos, sequenciais inválidos e divergências como `CONFLITO`.
   - Atualizar modo, UASG, estado e sequencial em um único statement e reler antes de retornar sucesso.
   - Validar, apresentar resultados e pausar.

2. **Contexto do usuário**
   - Injetar no adapter o login confiável por execução, preservando `resolver()` sem parâmetros.
   - Consultar exatamente um Representante ativo em `SGI.RH_FUNCIONARIOS`, comparando o login com `UPPER`.
   - Consultar exatamente uma Lotação vigente e ativa por `RH_FUNCIONARIOS_LOTACOES` e `RH_LOTACOES`.
   - Usar binds para login, código do funcionário e literal ativo `1`.
   - Validar IDs positivos e textos obrigatórios antes de construir `Representante`, `Lotacao` e `ContextoUsuario`.
   - Retornar `NAO_RESOLVIDO` para login vazio, cardinalidade ou dados inválidos; falhas Oracle retornam `FALHA_SISTEMICA`.
   - Nunca inferir Lotação por UO ou UASG.
   - Validar, apresentar resultados e pausar.

3. **Auditoria em `PAC_HISTORICOS`**
   - Mapear tabela e colunas de instante, procedimento, mensagem, Representante e Lotação.
   - Exigir transação ativa e inserir append-only usando `SYSDATE`.
   - Persistir códigos tipados 5, 8 ou 9, mensagem pronta e identidades do Representante e da Lotação por binds.
   - Não consultar o catálogo de procedimentos, registrar tentativas/falhas ou controlar commit/rollback.
   - Traduzir falhas para `ResultadoPersistenciaLocal::falhaSistemica`.
   - Validar compartilhamento da sessão e atomicidade com confirmação e estado; apresentar resultados e pausar.

4. **Eventos técnicos imutáveis e acumulador PSR-3**
   - Produzir o envelope camelCase com `timestamp`, `level`, `correlationId`, `environment`, `system`, `module`, `action`, `message`, `error`, `context`, `metadata` e `ipAddress`.
   - Exigir UUID v4 no acumulador, sem endurecer `CorrelacaoExecucao`.
   - Usar ambiente explícito, sistema fixo `pca-pncp-backend`, módulo explícito e IP confiável opcional.
   - Normalizar níveis para `DEBUG`, `INFO`, `WARNING` e `ERROR`.
   - Adotar ações operacionais mínimas:
     - `PROCESSAR_RESULTADO_ESCRITA`;
     - `VALIDAR_CONVERGENCIA`;
     - `EXECUTAR_RECONCILIACAO`;
     - `PERSISTIR_CONVERGENCIA`;
     - `FINALIZAR_UNIDADE`;
     - `DESFAZER_UNIDADE`.
   - Passar a ação explicitamente em cada chamada de log. Em erro, manter a mesma ação da operação que falhou e registrar `FalhaSegura` como `error.code` e `error.message`.
   - Manter `message` técnico e sanitizado; nunca usar o código da falha como `action`.
   - Tratar `error`, `context` e `metadata` como mapas associativos sanitizados, removendo recursivamente credenciais, JWT, autorização, SQL, conexão, payload/resposta integrais, stack traces e valores não serializáveis.
   - O acumulador armazenará apenas objetos `EventoTecnico` imutáveis e ordenados.
   - Criar uma capacidade independente de produzir um lote para exportação futura. O lote calculará `executionTimeMs` no momento da criação e acrescentará esse valor somente à cópia do `metadata` do último envelope exportado.
   - Não modificar o último `EventoTecnico`, não congelar ou alterar eventos retroativamente e não integrar o lote à Response.
   - Validar, apresentar resultados e pausar.

5. **Demonstração final e documentação**
   - Executar primeiro todos os testes focados, suíte padrão, suíte contratual não real, lint, autoload PSR-4 e inspeção arquitetural.
   - Demonstrar que os eventos permanecem apenas em memória, não integram `PublicarPcaResponse` e não produzem escrita Oracle ou Scriptcase.
   - Somente após todos os incrementos verdes, atualizar DI-006, DI-010 e o Fluxo para registrar:
     - acumulação em memória pelo backend;
     - ausência dos eventos na Response nesta sprint;
     - exposição futura pela fachada;
     - persistência futura pela Blank com `sc_log_add`;
     - separação entre `SEGURANCA_LOG` e `PAC_HISTORICOS`;
     - ausência de segunda conexão Oracle para logging;
     - possibilidade aceita de perda dos eventos em falha fatal anterior ao retorno.
   - Atualizar depois o estado operacional e o catálogo de arquivos, sem alterar o roadmap prescritivo.
   - Executar validação documental, `git diff --check` e pausa final.

## Testes e critérios de aceitação

- Estado: individual/consolidado, versões múltiplas, linhas novas nulas, três estados, divergências, propagação, releitura e falhas Oracle.
- Contexto: login vazio ou com variação de caixa, zero/um/múltiplos Representantes e Lotações, vínculos encerrados, inatividade, dados inválidos e falha sistêmica.
- Auditoria: procedimentos 5/8/9, `SYSDATE`, autoria, binds, append-only, transação obrigatória e ausência de catálogo dinâmico.
- Eventos: envelope exato, UUID v4, ISO 8601, níveis, ações operacionais, falha separada da ação, ordem, sanitização e IP opcional.
- Imutabilidade: criar eventos, exportar o lote com duração e comprovar que o último objeto e seu `metadata` original permanecem inalterados.
- Fronteiras: nenhuma alteração em `PublicarPcaResponse`, fachada, Presenter, Composition Root ou Blank; nenhuma chamada a `sc_log_add` e nenhuma escrita em `SEGURANCA_LOG`.

## Premissas e adiamentos

- Linha de base: 428 testes e 1.511 asserções; suíte contratual com 24 testes e 51 asserções.
- A futura sprint Scriptcase decidirá o valor genérico de `SEGURANCA_LOG.ACTION` e mapeará o evento detalhado para `DESCRIPTION`.
- A futura fachada obterá o lote acumulado do grafo de execução e o devolverá ao host.
- A perda de eventos por falha fatal antes do retorno é uma limitação aceita.
- Não haverá segunda conexão, DDL, probe real ou commit sem solicitação explícita.
