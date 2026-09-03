# Roadmap de Implementação do Backend PCA/PNCP

**Classificação documental:** Autoritativo

**Status:** Vigente

**Autoridade/Escopo:** ordem de construção do backend, dependências entre
fases, sprints e gates de avanço

**Versão documental:** V2

**Sucessor de:** [Roadmap V1 legado](../../historico/roadmap_v1_legado.md)

## 1. Finalidade, autoridade e limites

Este documento responde à pergunta:

> Em qual ordem o backend deve ser construído e quais evidências liberam cada
> avanço?

O Roadmap organiza a construção. Ele não cria regras de negócio, não redefine
o modelo de domínio, não escolhe mecanismos de integração e não substitui a
colaboração dinâmica já aprovada.

A autoridade permanece distribuída:

| Assunto | Fonte |
| --- | --- |
| Regras de negócio | [Regras de negócio](regras_de_negocio.md) |
| Conceitos, identidades, relações e invariantes | [Modelo de domínio](modelo_de_dominio.md) |
| Contratos externos, persistência técnica e operação | [Decisões de integração](decisoes_de_integracao.md) |
| Colaboração em tempo de execução | [Fluxo de implementação](fluxo_de_implementacao.md) |
| Limites estáticos e direção das dependências | [Arquitetura](../apoio/arquitetura.md) |
| Classificação e precedência documental | [Índice documental](../../README.md) |

Quando uma tarefa deste Roadmap depender de comportamento de negócio, sua
interpretação deve voltar às referências RN aplicáveis. Quando depender de
integração, deve voltar à DI correspondente e aos seus gates.

As fases representam sequência de trabalho, não camadas equivalentes. Uma fase
pode atravessar mais de uma camada para entregar um incremento coerente.

O código existente não é fonte normativa. Resultados anteriores devem convergir
com as fontes vigentes por meio das sprints de refatoração e de seus testes.

O conteúdo prescritivo deste documento compreende as Fases 0 a 9, seus gates,
critérios de saída e matrizes de rastreabilidade. O Apêndice A possui natureza
exclusivamente histórica e didática.

## 2. Acompanhamento do projeto PCA — estado real

O progresso corrente, a última sprint concluída, a próxima sprint na ordem,
os gates, as pendências e as restrições são mantidos no
[estado operacional do projeto](../operacional/estado_atual.md).

As fases e sprints abaixo permanecem a sequência prescritiva vigente. O estado
operacional não altera essa ordem nem seus critérios de avanço.

## 3. Como usar este Roadmap

Cada fase apresenta:

- objetivo e motivo de existir;
- entradas necessárias;
- entregas esperadas;
- critérios de saída;
- riscos;
- sprints ordenadas.

Uma sprint representa um incremento coerente e testável. Ela não precisa
corresponder a uma classe. Mudanças de comportamento em código seguem:

> Red → Green → Refactor

Testes que evidenciem regra de negócio devem usar a referência @regra RN-*.
Testes estritamente técnicos não precisam de associação artificial com RN.

Os marcadores didáticos têm os seguintes significados:

- **Princípio reutilizável:** orientação aplicável a outros projetos;
- **Decisão do PCA:** aplicação concreta das fontes vigentes neste backend;
- **Ponto em aberto:** condição documentada que depende de gate, sem default
  inventado.

Falha em gate bloqueia apenas o avanço dependente e reabre somente a decisão
afetada, conforme o registro das DI.

## 4. Visão geral da sequência

| Fase | Resultado principal |
| --- | --- |
| 0 — Preparação do projeto | Ambiente, governança, estrutura e testes básicos disponíveis |
| 1 — Descoberta e documentação do negócio | Vocabulário, RN, cenários e modelo conceitual coerentes |
| 2 — Modelagem e implementação do Domain | Conceitos e políticas do negócio demonstrados por TDD |
| 3 — Contratos requeridos pelo Domain | Persistência do Aggregate expressa por contrato interno mínimo |
| 4 — Application em estágios | Caso de uso completo demonstrado com mocks, sem adapters concretos |
| 5 — Infrastructure concreta | Adapters Oracle e PNCP implementando contratos já provados |
| 6 — Wiring controlado do núcleo | Núcleo executável composto sem depender da Presentation final |
| 7 — Presentation | Entrada, fachada, Controller, Presenter e ViewModels protegidos |
| 8 — Integração real e Composition Root final | Backend validado no Scriptcase, Oracle e PNCP de homologação |
| 9 — Entrega e operação | Release segura, observável, implantável e recuperável |

A sequência externa aprovada é:

> Application completa com mocks → Infrastructure concreta → wiring controlado
> → Presentation → integração real e Composition Root final → entrega e
> operação.

## 5. Fases e sprints

### Fase 0 — Preparação do projeto

**Objetivo:** deixar o projeto reproduzível antes de implementar comportamento.

**Por que existe:** ambiente instável, convenções concorrentes ou testes sem
bootstrap confiável tornam qualquer evolução posterior difícil de explicar e
repetir.

**Entradas:** objetivo inicial do produto e restrições do ambiente.

**Entregas:** repositório, runtime canônico, governança, estrutura PHP,
autoload e testes básicos.

**Critérios de saída:** comandos documentados funcionam no runtime canônico; a
estrutura separa src e tests; o bootstrap de testes é reproduzível.

**Riscos:** transformar preparação em desenho prematuro do domínio ou manter
mais de um runtime como referência.

#### Sprint 0.1 — Repositório e runtime canônico

- inicializar e proteger o histórico no Git;
- registrar o runtime PHP canônico e os comandos de diagnóstico;
- verificar versões mínimas das ferramentas.

**Princípio reutilizável:** uma única referência de execução reduz diferenças
entre desenvolvimento, testes e integração.

**Decisão do PCA:** o WSL é o runtime PHP canônico; outros executáveis servem
somente para diagnóstico.

#### Sprint 0.2 — Identidade e governança

- criar a entrada cognitiva do projeto;
- definir governança para agentes e documentação;
- registrar finalidade técnica e didática;
- estabelecer classificação e precedência das fontes.

**Princípio reutilizável:** governança deve existir antes que documentos
temporários passem a competir com especificações.

#### Sprint 0.3 — Estrutura PHP e padrões

- configurar Composer e autoload PSR-4;
- organizar src e tests;
- adotar PHP 8.1, PSR-12 e direção inicial das dependências;
- evitar dependências externas sem necessidade demonstrada.

#### Sprint 0.4 — Ambiente de testes

- configurar PHPUnit e seu bootstrap;
- comprovar o carregamento das classes;
- remover testes temporários quando o primeiro comportamento real estiver
  coberto.

**Critério didático:** o teste de bootstrap demonstra o ambiente; ele não é uma
regra do produto.

### Fase 1 — Descoberta e documentação do negócio

**Objetivo:** compreender o problema antes de transformar mecanismos em modelo.

**Por que existe:** regras reconstruídas a partir de código ou banco tendem a
misturar decisões acidentais com linguagem do negócio.

**Entradas:** autoridades de negócio, processo observado e contexto funcional.

**Entregas:** contexto, atores, linguagem ubíqua, catálogo RN, cenários,
modelo conceitual e fluxo de negócio coerentes.

**Critérios de saída:** regras possuem IDs estáveis; conflitos documentais
foram resolvidos; lacunas estão explícitas; nenhuma tecnologia externa define
o modelo.

**Riscos:** tratar apoio, diagramas, código ou investigação como norma.

#### Sprint 1.1 — Contexto, atores e fronteiras

- identificar objetivos, usuários, responsabilidades e eventos relevantes;
- separar planejamento, publicação e auditoria;
- registrar o alcance atual sem antecipar soluções técnicas.

Referências principais: RN-FLX-* e contexto funcional vigente.

#### Sprint 1.2 — Linguagem ubíqua e cenários

- consolidar termos usados pelas autoridades do negócio;
- escrever exemplos e contraexemplos;
- distinguir UO, Lotação, UASG, Plano, versão e publicação.

**Princípio reutilizável:** um termo só é útil quando preserva o mesmo
significado entre conversa, documento e teste.

#### Sprint 1.3 — Catálogo canônico de regras

- registrar regras com IDs RN-CONTEXTO-NNN;
- identificar origem, status, exemplos e evidências;
- preservar regras substituídas sem mantê-las na sequência vigente;
- definir como testes referenciam RN.

**Decisão do PCA:** o catálogo autoritativo precede modelo, fluxo, roadmap e
código afetados.

#### Sprint 1.4 — Modelo conceitual e coerência arquitetural

- representar identidades, relações e invariantes derivadas das RN;
- delimitar Aggregates e fatos externos;
- revisar os limites arquiteturais sem escolher adapters.

Referências principais: RN-CAD-*, RN-DEM-*, RN-PLA-* e *RN-AUD-*.

#### Sprint 1.5 — Fluxo do negócio e gate documental

- relacionar cenários, regras e modelo;
- revisar diagramas e explicações de apoio;
- resolver conflitos antes da implementação;
- registrar lacunas na fonte responsável.

Esta sprint descreve o fluxo do negócio. A colaboração técnica entre
componentes somente é consolidada depois das decisões de integração.

### Fase 2 — Modelagem e implementação do Domain

**Objetivo:** implementar o núcleo do negócio por incrementos conceituais e
ciclos TDD.

**Por que existe:** o Domain precisa permanecer compreensível e executável sem
Oracle, PNCP, Scriptcase, HTTP ou configuração.

**Entradas:** RN vigentes e modelo conceitual aprovado.

**Entregas:** Objetos de Valor, Entities, Aggregate, fatos imutáveis, exceções
e políticas puras.

**Critérios de saída:** invariantes relevantes possuem testes; dependências
externas não alcançam o Domain; testes de regra possuem @regra.

**Riscos:** uma classe por substantivo, entidades anêmicas, abstrações
prematuras ou identidade derivada de chaves físicas.

#### Sprint 2.1 — Valores fundamentais

- implementar CNPJ e Ano de Execução como valores imutáveis;
- demonstrar normalização, validade e igualdade;
- cobrir RN-CAD-001 e RN-CAD-002.

#### Sprint 2.2 — Conceitos organizacionais

- representar Lotação, Representante e UASG;
- modelar Unidade Orçamentária com identidade contínua;
- proteger RN-CAD-003 a RN-CAD-006 sem incorporar consultas ou cadastros
  físicos.

**Ponto em aberto:** o formato lexical completo da UASG permanece uma lacuna
do catálogo e não recebe validação inventada.

#### Sprint 2.3 — Ciclo da Demanda

- implementar identidade global, quantidade, categoria e origem;
- representar pertencimento a UO e exercício;
- demonstrar participação na fotografia e efeitos do ciclo homologado;
- cobrir RN-DEM-001 a RN-DEM-009 conforme o comportamento envolvido.

#### Sprint 2.4 — Aggregate Plano

- implementar identidade lógica por UO e Ano de Execução;
- distinguir versão vigente de identidade;
- representar fotografia completa e Plano com zero Demandas;
- proteger uniformidade de UO e exercício;
- cobrir especialmente RN-PLA-001, RN-PLA-004, RN-PLA-005 e RN-PLA-006.

**Decisão do PCA:** versão de origem da Demanda não limita seu pertencimento à
fotografia vigente.

#### Sprint 2.5 — Estados e política de publicação

- representar estados operacionais como fatos tipados externos ao Aggregate;
- implementar a política pura que produz PUBLICAR, RETIFICAR, EXCLUIR_PCA ou
  NENHUMA_ACAO;
- demonstrar que versão e sequencial isolados não determinam a ação;
- cobrir RN-PUB-001 a RN-PUB-008.

#### Sprint 2.6 — Fatos de auditoria

- representar Procedimento como classificação estável;
- representar Histórico de Procedimento como fato imutável;
- distinguir identidade de negócio de chave técnica;
- cobrir RN-AUD-001 a RN-AUD-005 sem criar barramento ou infraestrutura de
  eventos.

#### Sprint 2.7 — Coerência e regressão do Domain

- executar a suíte do Domain;
- revisar nomes, exceções, imutabilidade e acoplamento;
- eliminar testes que evidenciem premissas substituídas;
- confirmar cobertura das invariantes vigentes.

### Fase 3 — Contratos requeridos pelo Domain

**Objetivo:** definir somente contratos que expressam necessidades próprias do
Domain.

**Por que existe:** nem toda leitura externa é Repository de domínio. O
ownership do contrato acompanha a camada que necessita da capacidade.

**Entradas:** Aggregate e políticas demonstrados por testes.

**Entregas:** ownership explícito e contrato mínimo do PlanoRepository.

**Critérios de saída:** o contrato usa linguagem do Aggregate, não expõe
tecnologia e não inclui capacidades operacionais da Application.

**Riscos:** criar Repositories genéricos para toda tabela ou testar interfaces
apenas por reflection.

#### Sprint 3.1 — Necessidades e ownership de persistência

- identificar quais operações reconstroem ou confirmam o Aggregate;
- separar estado cross-Plan, contexto, auditoria e integração;
- manter as capacidades operacionais para a Fase 4.

Referência principal: DI-007.

#### Sprint 3.2 — Contrato mínimo do PlanoRepository

- definir descoberta de identidades lógicas;
- definir reconstrução da fotografia vigente, inclusive vazia;
- definir confirmação orientada à intenção;
- evitar tipos SQL, Oracle ou de transporte.

**Princípio reutilizável:** a evidência do contrato virá do consumidor na
Application e, depois, dos testes do adapter; a existência isolada da interface
não justifica um teste.

### Fase 4 — Application em estágios

**Objetivo:** construir o caso de uso completo com contratos internos e doubles
antes de implementar mecanismos externos.

**Por que existe:** a Application prova a coordenação e revela as Ports
necessárias sem misturar regras com Oracle, PNCP ou Scriptcase.

**Entradas:** Domain e PlanoRepository, RN vigentes e decisões de integração.

**Entregas:** Request, validação, Response, Ports, resultados tipados e UseCase
completo com mocks.

**Critérios de saída:** fluxo global e unitário demonstrado sem adapters
concretos; falhas e gates cobertos; contratos não expõem tecnologia.

**Riscos:** criar uma Port genérica, transferir decisão para o UseCase ou
iniciar Infrastructure antes do gate.

#### Sprint 4.1 — Request imutável

- definir entrada apenas com CNPJ e Ano de Execução;
- impedir ambiente, URL, credenciais, login e token no Request;
- demonstrar imutabilidade.

Referências: RN-CAD-001, RN-CAD-002 e DI-010.

#### Sprint 4.2 — Validação de fronteira

- validar a estrutura da solicitação sem duplicar invariantes do Domain;
- retornar erros de forma tipada;
- encerrar entrada inválida antes de qualquer acesso externo.

#### Sprint 4.3 — Decisão de processamento no Domain

- consumir a política pura do Domain;
- demonstrar as quatro decisões vigentes;
- remover decisão baseada apenas em versão ou data.

Referências: RN-PUB-001 a RN-PUB-008, DI-001, DI-002 e DI-005.

#### Sprint 4.4 — UseCase inicial

- validar a solicitação;
- consultar identidades e Planos pelo contrato;
- delegar decisões ao Domain;
- trabalhar com zero, uma ou várias identidades;
- produzir resultado provisório sem escrita externa.

#### Sprint 4.5 — DTOs de resposta

- substituir estruturas provisórias por Response e resultados tipados;
- manter DTOs sem regra de negócio;
- preservar a fronteira para Presentation futura.

#### Sprint 4.6 — Gate das decisões de integração

- consolidar DI-001 a DI-010 depois do modelo;
- definir Ports, fatos externos, falhas e gates;
- criar ou atualizar o fluxo de implementação;
- bloquear código afetado enquanto houver conflito documental.

**Decisão do PCA:** DI-001 a DI-007 e DI-009 estão aceitas; DI-008 e DI-010
estão aceitas condicionalmente e preservam seus gates.

#### Sprint 4.7 — Refatoração de convergência arquitetural com TDD

- reconciliar Domain e Application existentes com RN, modelo e DI vigentes;
- corrigir identidade, fotografia, estado e decisões do Plano;
- aceitar Plano vazio;
- corrigir ownership de contratos e auditoria;
- substituir testes baseados em premissas históricas;
- preservar Red → Green → Refactor em incrementos pequenos.

**Estado do PCA:** concluída em 04/08/2026 após reauditoria final aprovar as
correções das desconformidades identificadas na proteção da fotografia vigente,
nas pré-condições da política e nas invariantes de seus fatos e resultados. As
ressalvas não bloqueantes sobre licença permanecem transversais. A representação
decimal interna exata foi corrigida na Sprint 4.13; serialização, escala externa
e arredondamento continuam destinados à Sprint 5.6. O trabalho permaneceu
restrito ao passo 8 e à Sprint 4.7, sem antecipar a Sprint 4.8 naquele
encerramento.

#### Sprint 4.8 — Ports e contratos tipados da Application

- definir autenticação, leitura e escrita PNCP separadas;
- definir estratégia corrente e adotada;
- definir unidade de trabalho e exclusividade;
- definir contexto do usuário, auditoria e estado operacional;
- representar sessão, observações, comandos e falhas sem HTTP, JSON ou Oracle.

Referências: DI-006 e DI-007.

**Estado do PCA:** concluída e validada em 05/08/2026 após auditoria final dos
Ports e contratos tipados da Application. Foram demonstradas por testes
consumidores as capacidades de estratégia, autenticação, leitura e escrita
PNCP, unidade de trabalho exclusiva, contexto do usuário, auditoria e estado
operacional cross-Plan. A auditoria confirmou a permanência do
`PlanoRepository` no Domain, a ausência de detalhes de transporte e
persistência nos contratos internos e a não antecipação de adapters ou
orquestração. Os gates condicionantes das implementações concretas permanecem
vigentes. A ressalva transversal sobre licença continua não bloqueante. A
continuidade ocorreu na Sprint 4.9 após autorização explícita.

#### Sprint 4.9 — Preparação global e unidades externas

- validar contexto do usuário;
- descobrir identidades sem montar fotografia antecipada;
- validar UO e UASG;
- adotar ou conferir a estratégia anual;
- formar unidades individuais ou consolidada;
- autenticar uma vez por execução.

Referências: RN-CAD-003 a RN-CAD-006, RN-PUB-009 a RN-PUB-011, DI-003,
DI-007 e DI-010.

**Estado do PCA:** concluída e validada em 05/08/2026 após implementação em
cinco microciclos com TDD e pausas de validação. A preparação global passou a
resolver o contexto do usuário, descobrir somente identidades lógicas, validar
UO e UASG, adotar ou conferir a estratégia anual em unidade de trabalho curta,
formar unidades externas individuais ou consolidada e autenticar uma vez após
a confirmação local. Falhas conhecidas permanecem tipadas e seguras, sem
reconstrução antecipada de fotografias, adapters concretos ou processamento
unitário da Sprint 4.10. A regressão final aprovou 192 testes e 502 asserções.
A continuidade ocorreu nas Sprints 4.10 a 4.12, encerrando a Fase 4 após seus
respectivos gates e validações.

#### Sprint 4.10 — Processamento unitário com mocks

Esta sprint entrega um incremento interno testável e ainda não o conecta ao
caso de uso público. O despacho de escrita permanece síncrono e encaminha seus
fatos a um contrato interno de finalização na mesma execução; a implementação
desse contrato, com observação final e conclusão local, pertence à Sprint 4.11.
Assim, nenhum caminho público termina com transação aberta ou processamento
parcialmente conectado, preservando a transação única de `DI-004`.

- solicitar exclusividade;
- reler fotografias e estado operacional;
- observar o estado externo completo;
- fornecer fatos ao Domain;
- executar os comandos tipados necessários;
- construir a justificativa de `EXCLUIR_PCA` na Application, sem alterar a
  fotografia vigente ou o `PlanoRepository`;
- continuar unidades independentes somente nos casos aprovados.

Referências: RN-PUB-003, DI-002, DI-003, DI-004, DI-007 e fluxo vigente.

**Estado do PCA:** concluída e validada em 05/08/2026 após implementação em
seis microciclos com TDD e pausas de validação. O incremento interno passou a
proteger os desfechos e o contexto da finalização futura, reler a unidade sob
exclusividade, observar o
PNCP, delegar a decisão à política do Domain, despachar comandos tipados e
coordenar a continuidade entre unidades independentes. Falhas sistêmicas
interrompem a varredura sem fabricar resultados para unidades não visitadas;
bloqueios e falhas reconciliáveis preservam a continuidade individual. O modo
consolidado processa somente sua unidade conjunta. O finalizador permanece
apenas como contrato mockado, e nenhum adapter, observação final, confirmação,
auditoria ou conexão ao caso de uso público foi antecipado. A regressão final
aprovou 276 testes e 827 asserções. As Sprints 4.11 e 4.12 foram posteriormente
implementadas e submetidas ao checkpoint de auditoria encerrado abaixo.

A consulta histórica introduzida naquele incremento foi posteriormente
superada pela revisão de `RN-PUB-003` e removida na Sprint 5.10.

#### Sprint 4.11 — Reconciliação, confirmação e auditoria

- observar novamente depois das escritas;
- tratar resultado incerto sem retry cego;
- permitir no máximo uma reconciliação adicional;
- confirmar versões, estado operacional e auditoria na mesma unidade local;
- preservar RN-PLA-007 e RN-AUD-001 a RN-AUD-005.

**Estado do PCA:** concluída e validada em 10/08/2026 após a correção em TDD do
defeito crítico identificado pela auditoria. Em nova `PUBLICAR` depois de um
estado `EXCLUIDO`, uma escrita com `RESULTADO_INCERTO` sem novo sequencial passa
a ignorar o vínculo histórico, consultar candidatos sem adoção automática e
observar antes da única reconciliação adicional permitida. A validação também
comprovou `NENHUMA_ACAO` quando todos os Planos já estavam confirmados e o uso
de PSR-3 nos pontos relevantes de escrita, reconciliação e falha.

#### Sprint 4.12 — Resultados e gate da Application completa

- demonstrar todos os resultados unitários e globais;
- preservar resultados conhecidos após interrupção;
- cobrir os modos individual e consolidado;
- executar a suíte relacionada e a regressão;
- confirmar que nenhum adapter concreto foi necessário.

**Checkpoint intermediário de auditoria em 06/08/2026:** as Sprints 4.11 e 4.12
estavam implementadas, porém ainda não aprovadas. A auditoria conjunta foi
concluída e identificou como defeito crítico o uso do sequencial histórico de
um PCA `EXCLUIDO` como vínculo ativo depois de uma nova `PUBLICAR` com
`RESULTADO_INCERTO` sem novo sequencial. Ficaram então pendentes as evidências
mínimas de `NENHUMA_ACAO` com todos os Planos já confirmados, dos resultados
globais individual e múltiplo sem pendências, de `FALHA_RECONCILIAVEL` na
Response global e do consumo PSR-3. Naquele checkpoint, a Fase 5 permaneceu
bloqueada até a correção em TDD, a regressão verde e a atualização documental.

**Estado do PCA:** concluída e validada em 10/08/2026 após a auditoria, a
correção da Sprint 4.11 e a complementação exclusiva das evidências pendentes.
O UseCase global demonstrou uma unidade concluída, várias unidades sem
pendências produzindo `CONCLUIDA` e `FALHA_RECONCILIAVEL` produzindo
`CONCLUIDA_COM_PENDENCIAS`. Os testes focados aprovaram 45 testes e 235
asserções; a regressão completa aprovou 321 testes e 1068 asserções. O autoload
PSR-4 estrito, a direção das dependências e a ausência de detalhes de
Infrastructure em Domain e Application também foram validados.

**Reabertura da Fase 4 em 10/08/2026:** uma nova auditoria comprovou que a
conclusão anterior não cobria rollback após exceção na estratégia anual,
limite de 1000 no estado desejado de Retificação, decimais sem `float` e os
caminhos concretos de escrita pelo grafo completo da Application. A Fase 5 foi
novamente bloqueada até uma correção conservadora.

#### Sprint 4.13 — Estabilização e compreensão da Application

- garantir rollback de toda transação anual iniciada diante de exceção;
- validar o limite de 1000 pela fotografia desejada, preservando a ordem
  excluir, incluir e observar;
- substituir valores monetários em `float` por representação decimal interna
  mínima e exata;
- demonstrar Publicação, Retificação, Exclusão integral e modo consolidado com
  colaboradores concretos e doubles somente nas Ports;
- retirar ruído ativo, reconciliar documentos e produzir mapas de leitura;
- não redesenhar a Application, consolidar seus tipos ou fragmentar o
  finalizador.

**Estado da Sprint 4.13:** concluída e validada em 10/08/2026. Os testes focados
e a regressão completa aprovaram 339 testes e 1139 asserções. Lint PHP, parser
direcionado ao PHP 8.1, autoload PSR-4 estrito, direção de dependências e
ausência de detalhes tecnológicos ou `float` em Domain/Application foram
validados.

**Estado final da Fase 4:** concluída após reabertura e correção. O fluxo
unitário e global está demonstrado sem adapter concreto.

**Critério de liberação:** somente a Sprint 5.1 pode iniciar. Ela deve produzir
as evidências contratuais reais antes de qualquer adapter das sprints seguintes.

### Fase 5 — Infrastructure concreta

**Objetivo:** implementar adapters para contratos internos já demonstrados.

**Por que existe:** mecanismos externos devem traduzir contratos, não descobrir
regras ou decidir convergência.

**Entradas:** Application completa com mocks, Ports estáveis e DI vigentes.

**Entregas:** adapters Oracle, PNCP, configuração, contexto e observabilidade.

**Critérios de saída:** contratos internos e externos demonstrados; mesmos
recursos compartilhados quando exigido; nenhuma regra migrou para adapters.

**Riscos:** avançar com gate incompatível, esconder divergência ou inventar
defaults para parâmetros pendentes.

#### Sprint 5.1 — Gates contratuais Oracle e PNCP

- validar o contrato ADOdb/OCI8 no Scriptcase real;
- confirmar schema, joins, tipos, datas, decimais e constraints;
- validar cURL, OpenSSL, CA bundle e runtime;
- revalidar manual e OpenAPI e executar os testes PNCP autorizados;
- registrar evidência e reabrir somente a DI incompatível.

Referências: DI-008 e DI-009.

**Ponto em aberto:** parâmetros da DI-009 que permanecem configuráveis só
recebem valores depois do respectivo teste contratual.

Relatório não autoritativo de suporte:
[`Evidências da Sprint 5.1 — gates Oracle e PNCP`](../../investigacoes/evidencias_sprint_5_1_gates_oracle_pncp_2026-08-17.md).

#### Sprint 5.2 — Sessão Oracle, unidade de trabalho, estratégia e locks

- encapsular a conexão emprestada ou ativar o fallback aprovado;
- compartilhar a mesma sessão entre os adapters;
- implementar transação local e locks por controles de concorrência permanentes;
- implementar leitura e adoção da estratégia;
- nunca fechar conexão emprestada.

Referências: DI-003, DI-004 e DI-008.

#### Sprint 5.3 — Mapeamento e PlanoRepository

- mapear tipos Oracle para conceitos do Domain;
- descobrir identidades e reconstruir sob lock;
- selecionar a maior versão da UO e exercício;
- reunir Demandas de todas as versões de origem;
- reconstruir Plano vazio e persistir confirmação orientada à intenção.

Referências: RN-DEM-009, RN-PLA-001 a RN-PLA-007 e DI-008.

#### Sprint 5.4 — Estado operacional, contexto, auditoria e observabilidade

- implementar a projeção operacional em PAC_PLANOS;
- implementar contexto do usuário;
- persistir fatos confirmados em PAC_HISTORICOS;
- adaptar PSR-3 ao destino técnico;
- manter auditoria e logs com semânticas separadas.

Referências: RN-PUB-008, RN-AUD-001 a RN-AUD-005, DI-005, DI-006 e
DI-008.

#### Sprint 5.5 — Transporte cURL, TLS e autenticação

- encapsular uma requisição por chamada;
- validar TLS, hostname, limites e timeouts;
- autenticar uma vez e tratar JWT como opaco;
- impedir exposição de credenciais e token.

Referência: DI-009.

#### Sprint 5.6 — Serialização e payloads exatos

- serializar datas de calendário no formato contratual;
- converter a representação decimal interna exata para o contrato de transporte sem usar `float`;
- validar tokens numéricos antes da emissão;
- construir payloads sem mover regras para o serializer;
- preservar totais autoritativos.

#### Sprint 5.7 — Observação, paginação e ausência definitiva

- implementar leituras completas e paginação segura;
- validar coerência entre páginas;
- distinguir ausência definitiva, resposta inconclusiva e conflito;
- impedir adoção automática de PCA sem vínculo.

Referências: DI-002 e DI-009.

#### Sprint 5.8 — Escritas e resultados incertos

- implementar publicação, inclusão, exclusão de itens e exclusão integral;
- classificar aceitação, rejeição, resultado incerto e falha sistêmica;
- excluir antes de incluir na Retificação;
- impedir retry cego e particionamento não aprovado.

Referências: DI-001, DI-004 e DI-009.

#### Sprint 5.9 — Suíte de contratos dos adapters

- executar testes unitários dos adapters;
- executar contratos Oracle e PNCP autorizados;
- validar sessão compartilhada, rollback, segurança e tradução de erros;
- documentar limitações e evidências efetivamente observadas.

#### Sprint 5.10 — Simplificação da justificativa e encerramento da Infrastructure

- revisar `RN-PUB-003` para usar justificativa institucional fixa na exclusão
  integral, preservando o gatilho da fotografia vigente vazia com PCA ativo;
- simplificar a Application em TDD, sem alterar o contrato PNCP ou adapters;
- remover a capacidade histórica sem consumidor e encerrar sua investigação
  Oracle sem execução real;
- reconciliar a DI-008 e auditar todas as Ports e gates da Infrastructure.

Referências: RN-PUB-003, DI-007 e DI-008.

**Estado do PCA:** concluída em 31/08/2026 após quatro microciclos autorizados.
A auditoria final confirmou que todas as nove Ports vigentes possuem adapters
concretos, os gates reais Oracle e PNCP da Sprint 5.9 permanecem válidos e
nenhuma regra migrou para a Infrastructure.

**Estado final da Fase 5:** concluída. A entrada da Fase 6 foi satisfeita e a
Sprint 6.1 avançou posteriormente mediante planejamento e autorização próprios.

### Fase 6 — Wiring controlado do núcleo

**Objetivo:** montar um núcleo executável com implementações concretas sem
depender da Presentation final.

**Por que existe:** o wiring intermediário comprova composição e ciclo de
recursos antes de adicionar a fronteira pública.

**Entradas:** adapters e contratos verdes.

**Entregas:** grafo manual controlado e testes de integração interna.

**Critérios de saída:** dependências são injetadas por construtor; recursos
compartilham o ciclo correto; Domain permanece independente.

**Riscos:** transformar o wiring intermediário no Composition Root final ou
colocar construção no Controller.

#### Sprint 6.1 — Grafo manual sem Presentation

- criar composição explícita do UseCase e de seus adapters;
- manter o ponto de montagem dentro do backend;
- impedir container e Service Locator.

**Estado do PCA:** concluída em 31/08/2026 após três microciclos autorizados.
`MontadorNucleoPublicacaoPca` recebe as nove Ports vigentes e o logger PSR-3,
constrói explicitamente o grafo da Application e devolve o UseCase sem depender
de Presentation, container, Service Locator ou adapters concretos específicos.

#### Sprint 6.2 — Integração controlada dos adapters

- testar reconstrução, transporte e coordenação com ambientes controlados;
- validar compartilhamento da sessão e ordem de encerramento;
- usar doubles onde chamadas reais ainda não forem autorizadas.

**Estado do PCA:** concluída em 31/08/2026 após quatro microciclos autorizados.
A suíte de integração monta os nove adapters concretos pelo montador do núcleo
sobre ADOdb e cURL controlados, sem Oracle, PNCP ou Scriptcase reais. Foram
demonstrados o caminho individual já convergente, a retificação com sessão
Bearer compartilhada e o rollback reconciliável após o retry final inconclusivo,
sempre sem fechar a conexão emprestada.

#### Sprint 6.3 — Gate do núcleo executável

- executar suítes unitárias e de integração controlada;
- verificar direção das dependências;
- confirmar que nenhuma dependência de Presentation é necessária.

**Estado do PCA:** concluída em 31/08/2026 após quatro microciclos autorizados.
As suítes unitárias, de Composition, de integração controlada, dos adapters e
dos contratos permaneceram verdes. A auditoria confirmou as nove Ports com
implementações concretas, a direção interna das dependências e a execução do
núcleo sem Presentation, container ou Service Locator.

**Estado final da Fase 6:** concluída. As dependências do núcleo são injetadas
por construtor, os adapters compartilham corretamente os recursos controlados e
o Domain permanece independente. A entrada da Fase 7 foi satisfeita, e a Sprint
7.1 avançou posteriormente mediante planejamento e autorização próprios.

### Fase 7 — Presentation

**Objetivo:** proteger a fronteira pública e transformar entrada e saída sem
regra de negócio.

**Por que existe:** o consumidor não deve conhecer o grafo interno, e a
Application não deve renderizar a interface hospedeira.

**Entradas:** núcleo executável e contratos de resposta estáveis.

**Entregas:** Controller, Presenter, ViewModels e fachada.

**Critérios de saída:** nenhuma exceção atravessa a fachada; Presentation não
acessa adapters nem realiza wiring.

**Riscos:** Controller concentrar orquestração, Presenter gerar HTML ou
Scriptcase conhecer componentes internos.

#### Sprint 7.1 — Controller e entrada tipada

- adaptar a entrada para CNPJ e Ano de Execução;
- chamar o UseCase já montado;
- impedir macros, credenciais e regras no Controller.

**Estado do PCA:** concluída em 01/09/2026 após quatro microciclos autorizados.
`EntradaPublicarPca` preserva os primitivos tipados, o Controller materializa
`Cnpj` e `AnoExecucao`, cria a Request válida e delega pelo contrato
`PublicadorPca`. Exceções de entrada permanecem internas até a futura fachada,
sem iniciar o UseCase, e o Controller não acessa macros, configuração, adapters
ou wiring.

#### Sprint 7.2 — Presenter e ViewModels

- transformar Response em ViewModel estruturado;
- representar resultados globais e unitários;
- preservar somente dados seguros.

#### Sprint 7.3 — Fachada e fronteira de exceções

- expor um único ponto público;
- converter falhas inesperadas em resultado seguro;
- preservar correlação sem expor stack, SQL, payload ou segredos.

#### Sprint 7.4 — Suíte da Presentation

- testar entrada, Controller, Presenter, fachada e sanitização;
- demonstrar que o hospedeiro somente renderiza o ViewModel.

**Estado do PCA:** concluída em 01/09/2026 após um microciclo autorizado. A
suíte transversal comprovou o fluxo da entrada até os ViewModels, a preservação
dos resultados conhecidos e a sanitização de falhas inesperadas. A fronteira
entrega somente ViewModels, escalares e valores nulos; a renderização pela Blank
real permanece para a Fase 8. O registro PSR-3 da falha inesperada capturada
pela fachada permanece pendente e rastreado para a Sprint 8.1, junto do grafo
final e do acumulador compartilhado.

**Estado final da Fase 7:** concluída. Entrada, Controller, Presenter,
ViewModels e fachada formam uma fronteira protegida, sem acesso a adapters,
wiring, HTML, JSON ou macros do Scriptcase.

Referência principal: DI-010.

### Fase 8 — Integração real e Composition Root final

**Objetivo:** fechar a composição definitiva e validar os contratos no ambiente
hospedeiro e nos serviços reais autorizados.

**Por que existe:** adapters isolados não comprovam o ciclo completo da
aplicação Blank até a confirmação local.

**Entradas:** Presentation, núcleo e gates contratuais aplicáveis.

**Entregas:** Composition Root final, integração Scriptcase, testes Oracle,
suíte PNCP de treinamento e smoke test.

**Critérios de saída:** fluxo completo comprovado em homologação; conexão e
segredos possuem ciclo correto; falhas são seguras e observáveis.

**Riscos:** usar produção em testes, depender de estado residual desconhecido
ou tratar integração real como autorização automática de implantação.

#### Sprint 8.1 — Composition Root e perfil Scriptcase

- expor `PublicadorPcaScriptcase` como única API conhecida pela Blank, recebendo
  conexão emprestada, login, `SERVER_NAME`, ano e `X-Request-ID`;
- localizar por `PCA_PNCP_PROFILE_REGISTRY_FILE` o registro externo que contém
  somente a allowlist exata `SERVER_NAME => caminho absoluto do perfil`;
- carregar CNPJ, ambiente, credenciais e parâmetros exclusivamente do perfil
  externo validado, sem `.env`, defaults ou caminhos fornecidos pela Blank;
- preservar o UUID v4 fornecido pelo servidor HTTP em todo o fluxo e falhar
  fechado antes de I/O quando estiver ausente ou inválido;
- construir um grafo novo por chamada, compartilhando conexão, transporte,
  logger e correlação sem fechar a conexão emprestada;
- manter registro, perfis e segredos fora do repositório e do webroot e provar
  o fluxo transversal sem Oracle, PNCP ou Scriptcase reais.

#### Sprint 8.2 — Integração Oracle real

- executar begin, commit, rollback, binds e locks controlados;
- validar a mesma sessão entre todos os adapters;
- comprovar reconstrução e confirmação em massa dedicada.

#### Sprint 8.3 — Suíte PNCP de treinamento

- executar preflight que proíbe produção;
- comprovar autenticação, paginação, ausência, sequencial e identificadores;
- testar decimais, escritas incertas e limpeza best-effort;
- incorporar a evidência real de que `400` no consolidado conhecido exige
  confirmação pela listagem completa e de que `404` encerra a listagem de
  unidades somente após página `200` válida e com página maior que 1;
- registrar versão do contrato, data e evidências;
- gate nominal aprovado no treinamento em 03/09/2026, com limpeza confirmada e
  SHA-256 sanitizado
  `0688185978654e374ddf959f377d8cf619314bc906f81e19cafbdff1388aae2c`;
- gate de escrita incerta aprovado no treinamento em 03/09/2026, após
  exatamente um POST, recuperação somente por leitura e limpeza confirmada,
  com SHA-256 sanitizado
  `f68ee8ee61db4ea11598f88a52100bc9e1cb5ca924eb6bcbe16930d458a149ba`;
- regressão final aprovada em PHP 8.4.7: Infrastructure PNCP 347/1.110,
  Composition 11/187, Presentation 31/319, Integration 5/103, contratos locais
  5.1, 5.9, 8.2 e 8.3 verdes e suíte padrão 882/3.846;
- Sprint concluída em 03/09/2026. A compatibilidade observada manteve a DI-009
  sem nova alteração semântica; a Sprint 8.4 é a próxima na ordem.

#### Sprint 8.4 — Fluxos completos dos dois modos

- demonstrar PUBLICAR, RETIFICAR, EXCLUIR_PCA e NENHUMA_ACAO;
- cobrir modo individual e consolidado;
- validar confirmação, estado operacional, auditoria e resultados.

#### Sprint 8.5 — Falhas, segurança e smoke test do pacote

- demonstrar contenção, falha reconciliável e interrupção sistêmica;
- validar sanitização e preservação de resultados conhecidos;
- testar a aplicação Blank e o pacote autocontido em homologação.

Referência principal: DI-010 e gates das DI-008 e DI-009.

### Fase 9 — Entrega e operação

**Objetivo:** tornar a solução implantável, observável, segura e recuperável.

**Por que existe:** integração funcional não substitui preparação de produção,
suporte e recuperação consciente.

**Entradas:** smoke test de homologação aprovado.

**Entregas:** configuração de produção, pacote, observabilidade, runbooks,
implantação e rotinas de suporte.

**Critérios de saída:** produção possui perfil explícito, segredos externos,
rollback de release, monitoramento e procedimentos de recuperação validados.

**Riscos:** implantar sem reconciliação do legado, registrar segredos ou tratar
falha distribuída como rollback simples.

#### Sprint 9.1 — Configuração e segredos

- provisionar perfil de produção fora do artefato e do webroot;
- validar permissões, CA bundle, timeouts e parâmetros aprovados;
- falhar fechado em configuração ausente ou incoerente.

#### Sprint 9.2 — Observabilidade e segurança operacional

- definir correlação, métricas, alertas e retenção;
- proteger credenciais, JWT, payloads e dados sensíveis;
- distinguir logs técnicos de auditoria de negócio.

#### Sprint 9.3 — Empacotamento e implantação

- produzir release autocontida com vendor;
- impedir Composer em produção;
- documentar instalação, atualização e rollback do release.

#### Sprint 9.4 — Migração assistida e recuperação

- reconciliar publicações legadas de forma consciente;
- documentar recuperação após efeito PNCP e falha Oracle;
- tratar resíduos de homologação e produção sem adoção automática.

#### Sprint 9.5 — Prontidão de produção e suporte

- executar checklist operacional e smoke test autorizado;
- validar responsáveis, contatos, monitoramento e rotina de suporte;
- registrar limitações aceitas e critérios de reabertura.

## 6. Gates das decisões de integração

O contexto, as alternativas e as consequências completas permanecem no
[registro de decisões de integração](decisoes_de_integracao.md).

| ID | Título | Resumo | Status | Gate |
| --- | --- | --- | --- | --- |
| DI-001 | Contrato de publicação e sincronização do PNCP | Distingue criação, sincronização e exclusão do PCA | Aceita | Compatibilidade de ID_DEMANDA como numeroItem |
| DI-002 | Autoridade, reconciliação e vínculo externo | Separa conjunto desejado, observação externa e vínculo conhecido | Aceita | Contratos de identidade e ausência definitiva |
| DI-003 | Modos de publicação e unidade externa | Define unidades individual e consolidada e estratégia anual | Aceita | Persistência e validação da estratégia na DI-008 |
| DI-004 | Transações, exclusividade e falhas parciais | Coordena atomicidade local, observação e incerteza externa | Aceita | Revisão se volume ou concorrência mudarem |
| DI-005 | Estado operacional e confirmação versionada | Separa publicação externa, sequencial e confirmação da versão | Aceita | Coerência física validada pela DI-008 |
| DI-006 | Auditoria e procedimentos PNCP | Registra somente fatos concluídos e confirmados | Aceita | Contratos Oracle de auditoria e contexto |
| DI-007 | Portas necessárias na Application | Define capacidades internas sem vazamento tecnológico | Aceita | UseCase completo demonstrado com mocks |
| DI-008 | Mecanismo Oracle | Define conexão, transação, lock, reconstrução e projeção | Aceita | Reabrir somente diante de incompatibilidade Oracle concreta |
| DI-009 | Transporte HTTP | Define cURL, TLS, autenticação, leitura e escrita segura | Aceita | Reabrir somente diante de incompatibilidade externa concreta |
| DI-010 | Composition Root e Scriptcase | Define fachada, composição, perfil e ciclo da execução | Aceita condicionalmente | Fachada, identidade, conexão e pacote em homologação |

## 7. Rastreabilidade por grupos de regras

Esta matriz orienta a navegação. Ela não substitui o catálogo nem exige que as
42 regras sejam reescritas no Roadmap.

| Grupo | Assunto | Incidência principal |
| --- | --- | --- |
| RN-CAD-* | Cadastros, identidades e referências | Fases 1, 2, 4 e 5 |
| RN-FLX-* | Fluxo de planejamento | Fase 1 |
| RN-DEM-* | Identidade e ciclo das Demandas | Fases 2, 4 e 5 |
| RN-PLA-* | Plano, versão, fotografia e confirmação | Fases 2, 3, 4 e 5 |
| RN-PUB-* | Decisões e modos de publicação | Fases 2, 4, 5 e 8 |
| RN-AUD-* | Fatos e unidade da auditoria | Fases 2, 4 e 5 |

IDs individuais aparecem nas sprints quando alteram diretamente o resultado ou
o critério de saída. Toda interpretação normativa deve retornar ao catálogo.

## 8. Mapeamento Roadmap V1 → Roadmap V2

O mapeamento serve somente à rastreabilidade. Nomes e números da V1 não voltam
à sequência ativa.

| Estrutura da V1 | Destino na V2 | Tratamento |
| --- | --- | --- |
| Fase 0 e Sprints 0.1 a 0.11 | Fase 0, Sprints 0.1 a 0.4 | Preparação agrupada por resultados, sem tutorial excessivo |
| Descoberta citada no resumo e Domain detalhado como Fase 1 | Fases 1 e 2 | Descoberta separada da implementação do Domain |
| Fase 1, Sprints 1.1 a 1.9 | Fase 2, Sprints 2.1 a 2.7 | Conceitos reorganizados pela modelagem vigente |
| Fase 2, Sprints 2.1 a 2.4 | Fase 3 e Sprints 4.7 a 4.8 | PlanoRepository permanece no Domain; capacidades operacionais migram para Application |
| Fase 3A | Sprints 4.1 a 4.5 e convergência na 4.7 | Incrementos básicos preservados, premissas substituídas são refatoradas |
| Fase 3B | Sprints 4.6 e 4.8 a 4.12 | DI, Ports e UseCase completo tornam-se um único estágio ordenado |
| Fase 4 | Fase 5 | Infrastructure só começa depois do gate da Application |
| Fase 5 | Fase 6 | Wiring intermediário validando o núcleo |
| Fase 6 | Fase 7 | Presentation inclui Controller, Presenter, ViewModels e fachada |
| Fase 7 | Fase 8 | Integração real separada da operação |
| Entrega e recuperação dispersas | Fase 9 | Operação recebe fase própria |

## 9. Política de manutenção

- mudanças estruturais relevantes geram versão histórica antes de alterar a
  versão ativa;
- correções editoriais pequenas atualizam este arquivo e permanecem
  rastreadas pelo Git;
- mudança de RN, modelo, DI ou fluxo deve ocorrer primeiro na fonte
  responsável;
- referências e índice documental devem ser atualizados na mesma alteração;
- fases concluídas no PCA não tornam suas decisões imutáveis diante de nova
  evidência autorizada, mas qualquer reabertura deve respeitar os gates.

## 10. Encerramento do conteúdo prescritivo

A sequência autoritativa de implementação termina na Fase 9. O conteúdo
seguinte preserva contexto histórico e aprendizados, sem criar tarefas,
precedência, regras ou decisões vigentes.

---

## Apêndice A — Evolução e Aprendizados do Projeto PCA

> **Natureza desta seção:** histórica e didática.
>
> Este apêndice não possui autoridade prescritiva. Não deve ser usado para
> determinar regras de negócio, modelo, decisões de integração, fluxo ou ordem
> de implementação. Para esses assuntos, prevalecem as fontes vigentes e as
> Fases 0 a 9 deste Roadmap.

### A.1 — Evolução do problema

O sistema nasceu orientado ao registro e à aprovação de Demandas. Consolidação
em Planos e publicação no PNCP surgiram depois. Essa evolução explicou por que
alguns mecanismos antigos foram modelados antes de existir uma visão completa
da publicação externa.

A V2 separou descoberta, Domain, Application e integração para tornar a ordem
reutilizável sem apagar a cronologia real.

### A.2 — Catálogo de regras

O catálogo RN foi reconhecido como necessário depois de parte do código já
existir. Antes dele, regras estavam distribuídas entre documentos, testes e
conversas.

Na V2, esse aprendizado foi incorporado à Fase 1, e a redação normativa passou
a residir em [Regras de negócio](regras_de_negocio.md).

### A.3 — Identidade e fotografia do Plano

A modelagem evoluiu ao distinguir chave técnica, versão homologada e identidade
lógica. Também foi reconhecido que a fotografia vigente reúne Demandas de
versões de origem diferentes e pode ficar vazia.

A resolução vigente encontra-se no
[Modelo de domínio](modelo_de_dominio.md) e nas regras RN-PLA-*.

### A.4 — Reclassificações do modelo

Unidade Orçamentária foi reconhecida como conceito com identidade contínua. O
Histórico de Procedimento deixou de ser tratado como Entity apenas por possuir
chave técnica e passou a representar um fato imutável.

Esses aprendizados reforçaram que identidade decorre do negócio, não da
estrutura física.

### A.5 — Descoberta das Ports da Application

As capacidades de autenticação, observação, estratégia, exclusividade,
contexto, auditoria e estado operacional ficaram claras durante a evolução do
caso de uso. O ownership dessas Ports foi consolidado depois da primeira
Application básica.

A V2 preservou o aprendizado ao posicionar o gate das DI e as Ports dentro da
Fase 4, antes de qualquer adapter.

### A.6 — Revisões das premissas de integração

O planejamento das DI distinguiu sincronização de recriação, retirou o baseline
detalhado da primeira integração, separou sequencial externo do Aggregate e
definiu gates objetivos para Oracle, PNCP e Scriptcase.

O entendimento vigente está no
[Registro de decisões de integração](decisoes_de_integracao.md). O
[Roadmap V1 legado](../../historico/roadmap_v1_legado.md) preserva a formulação
anterior para auditoria e estudo.

### A.7 — Cronologia real e sequência recomendada

No PCA, algumas decisões de integração foram consolidadas antes da revisão
final do modelo e do fluxo. A documentação posterior reconciliou essa
cronologia sem fingir que o trabalho ocorreu na ordem ideal.

A V2 apresenta a sequência recomendada e mantém a evolução real neste
apêndice e nos registros históricos.
