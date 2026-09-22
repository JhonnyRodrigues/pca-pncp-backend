# Glossário Integrado do Projeto PCA/PNCP

**Classificação documental:** Apoio
**Status:** Vigente
**Autoridade/Escopo:** índice semântico da linguagem de negócio, domínio, integração, arquitetura e documentação

## 1. Finalidade, autoridade e uso

Este glossário oferece definições curtas e caminhos de consulta. Ele não cria
regras de negócio, decisões de integração, comportamento ou arquitetura.

As fontes usadas nas referências são:

- [Regras de negócio](../autoritativos/regras_de_negocio.md) — `RN-*`;
- [Modelo de domínio](../autoritativos/modelo_de_dominio.md) — Modelo;
- [Decisões de integração](../autoritativos/decisoes_de_integracao.md) — `DI-*`;
- [Fluxo de implementação](../autoritativos/fluxo_de_implementacao.md) — Fluxo;
- [Roadmap de implementação](../autoritativos/roadmap.md) — Roadmap;
- [Arquitetura](arquitetura.md) — Arquitetura;
- [Especificação de logs estruturados](../autoritativos/especificacao_de_logs_estruturados.md) — Logs;
- [Estado operacional](../operacional/estado_atual.md) — Estado;
- [Governança documental](../../README.md) — Docs;
- [Governança dos agentes](../../../.agents/README.md) — Agentes;
- [Instruções operacionais do repositório](../../../AGENTS.md) — `AGENTS.md`.

Para os termos gerais de engenharia de software, a coluna `Fonte` indica onde
o conceito aparece ou é aplicado neste projeto. A definição curta preserva o
sentido consolidado no mercado, sem transformar o glossário em fonte normativa.

Quando houver divergência, prevalecem:

1. regras de negócio;
2. modelo de domínio;
3. decisões de integração;
4. fluxo de implementação;
5. arquitetura;
6. glossário.

A divergência deve ser corrigida neste glossário. Código existente, documentos
históricos, investigações e prompts antigos não definem o significado vigente.

## 2. Termos de negócio e planejamento

| Termo | Definição curta | Fonte |
| --- | --- | --- |
| Ano de Execução | Exercício ao qual o planejamento se aplica. | `RN-CAD-002`; Modelo §5.2 |
| Cancelamento | Retirada formal de uma Demanda do conjunto vigente. | `RN-DEM-005`, `RN-DEM-006`, `RN-DEM-008` |
| Consolidação | Ato anterior à homologação que reúne Demandas autorizadas e bloqueia alterações ordinárias. | `RN-FLX-003` |
| Data desejada | Data de referência para a contratação, ajustável pelo Núcleo de Compras nos limites aprovados. | `RN-DEM-003`, `RN-DEM-004` |
| Demanda | Necessidade de material ou serviço com identidade global e ciclo de vida controlado. | `RN-DEM-001`, `RN-DEM-002`; Modelo §6.2 |
| Demanda ativa | Demanda que, por sua situação vigente, integra a fotografia do Plano. | `RN-DEM-009` |
| Desconsolidação | Reabertura do fluxo de Demandas consolidadas antes da homologação. | `RN-FLX-003` |
| DFD | Documento de Formalização da Demanda usado no planejamento ordinário. | `RN-FLX-001` |
| DFD-E | Demanda extraordinária submetida a fluxo e homologação próprios. | `RN-FLX-002`, `RN-DEM-007` |
| Fotografia vigente | Conjunto completo das Demandas ativas de uma UO e exercício, independentemente da versão de origem. | `RN-PLA-004`, `RN-PLA-006`; Modelo §7.3 |
| Homologação | Aprovação formal que origina o Plano ou produz novo marco de versão. | `RN-FLX-001`, `RN-FLX-002`, `RN-PLA-002` |
| Lotação | Unidade organizacional da autoria, distinta de Unidade Orçamentária. | `RN-CAD-004`; Modelo §5.4 |
| PCA | Plano de Contratações Anual representado internamente e publicado no PNCP conforme a unidade externa aplicável. | Modelo §§2.2 e 4 |
| Plano | Planejamento lógico de uma UO para um Ano de Execução, representado por sua fotografia vigente. | `RN-PLA-001`, `RN-PLA-004`; Modelo §7 |
| Representante | Pessoa responsável pela ação de negócio registrada. | `RN-CAD-003`, `RN-AUD-004`; Modelo §5.5 |
| Unidade Orçamentária — UO | Unidade responsável pelo Plano no sistema de planejamento. | `RN-CAD-005`, `RN-PLA-001`; Modelo §6.1 |
| UASG | Identificação da unidade compradora utilizada na publicação externa. | `RN-CAD-005`, `RN-PUB-011`; Modelo §5.3 |
| Versão do Plano | Marco positivo e incremental de uma evolução homologada do mesmo Plano lógico. | `RN-PLA-002`, `RN-PLA-003`; Modelo §7.2 |

## 3. Termos de modelagem de domínio

| Termo | Definição curta | Fonte |
| --- | --- | --- |
| Aggregate | Fronteira de consistência que protege invariantes relacionadas. | Modelo §7; Arquitetura §8 |
| Aggregate Root | Entity que controla o acesso e a consistência de um Aggregate; neste projeto, `Plano`. | Modelo §7; Arquitetura §8 |
| Entity | Conceito reconhecido por identidade contínua, como `Demanda`. | Modelo §6; Arquitetura §8 |
| Estado desejado | Fotografia homologada que o SGI pretende manter publicada para uma unidade externa. | Modelo §8; `DI-002` |
| Exceção de domínio | Representação de violação de uma invariante do Domain. | Modelo §§7 e 11; Arquitetura §4.1 |
| Fato imutável | Registro conceitual de algo concluído, sem adquirir identidade de negócio por possuir chave técnica. | Modelo §10; `DI-006` |
| Identidade lógica | Valores pelos quais o negócio reconhece a continuidade de um conceito. | `RN-PLA-001`; Modelo §7.1 |
| Invariante | Condição que deve permanecer verdadeira dentro da fronteira que a protege. | Modelo §11; Arquitetura §4.1 |
| Objeto de Valor — Value Object | Valor imutável com igualdade por conteúdo, como `Cnpj`, `AnoExecucao` ou `Uasg`. | Modelo §5; Arquitetura §8 |
| Política de Domain | Decisão pura que relaciona conceitos e fatos sem acessar mecanismos externos. | Modelo §8; `DI-007` |

## 4. Termos de publicação e integração

| Termo | Definição curta | Fonte |
| --- | --- | --- |
| Ausência definitiva | Ausência externa confirmada pelos critérios de observação aprovados, permitindo tratar o PCA conhecido como inexistente. | `DI-002`, `DI-009` |
| Baseline de conteúdo | Representação local detalhada do último conteúdo externo cuja convergência foi confirmada; não é mantida pela V1 da integração. | `DI-002` |
| Convergência | Compatibilidade comprovada entre o conjunto desejado e o estado publicado observado. | `RN-PUB-004`; `DI-002`, `DI-004` |
| Escrita incerta | Escrita externa cujo efeito não pôde ser confirmado nem descartado. | `DI-004`, `DI-009` |
| Estado externo observado | Fato obtido do PNCP sobre o que está efetivamente publicado. | `DI-002`; Modelo §8.3 |
| Estado operacional da publicação | Referência local cross-Plan sobre a situação conhecida da publicação externa. | `RN-PUB-008`; `DI-005`, `DI-007` |
| Estados operacionais | `NAO_PUBLICADO`, `PUBLICADO` e `EXCLUIDO`. | `RN-PUB-008`; `DI-005` |
| Exclusão integral | Encerramento do PCA externo quando a unidade de publicação fica sem Demandas vigentes. | `RN-PUB-003`; `DI-001` |
| Justificativa institucional da exclusão integral | Texto fixo enviado pela Application ao excluir um PCA por ausência de Demandas vigentes, sem depender da causa histórica de uma Demanda específica. | `RN-PUB-003`; `DI-007`, `DI-009` |
| Falha reconciliável | Resultado de unidade que exige nova observação ou atuação consciente sem caracterizar falha sistêmica global. | `DI-004`, `DI-007` |
| Modo consolidado | Estratégia em que uma publicação representa a união completa dos Planos participantes do exercício. | `RN-PUB-010`; `DI-003` |
| Modo individual | Estratégia em que cada UO possui publicação e resultado independentes. | `RN-PUB-009`; `DI-003` |
| `NENHUMA_ACAO` | Decisão de não escrever externamente quando já há convergência ou ambos os lados estão inativos. | `RN-PUB-004` |
| PCA externo sem vínculo local | Publicação encontrada no PNCP sem associação operacional previamente conhecida pelo SGI. | `DI-002` |
| PCA vinculado | Publicação externa associada ao estado operacional local conhecido. | `DI-002`, `DI-005` |
| Perfil de implantação | Configuração autorizada para uma instalação, selecionada por critério explícito e usada para determinar ambiente, identidade institucional, credenciais e parâmetros de integração. | `DI-010` |
| `PUBLICAR` | Decisão de criar um PCA quando há conjunto vigente e não existe publicação ativa conhecida. | `RN-PUB-001` |
| Publicação inicial | Criação da representação externa do PCA com seu conjunto inicial de itens. | `RN-PUB-001`; `DI-001` |
| Reconciliação | Processo consciente de observar fatos e buscar convergência após diferença ou resultado incerto. | `DI-002`, `DI-004` |
| Reconciliação por pertencimento | Estratégia que compara identidades desejadas e observadas sem comparar o conteúdo de cada item. | `RN-PUB-006`; `DI-001`, `DI-002` |
| Referência externa | Identificador ou valor usado para localizar um recurso fora do domínio. | Modelo §9; `DI-005` |
| `RESULTADO_INCERTO` | Resultado tipado de uma escrita cuja aceitação ou efeito externo não pôde ser determinado. | `DI-009` |
| Resultados globais | `SOLICITACAO_INVALIDA`, `CONCLUIDA`, `CONCLUIDA_COM_PENDENCIAS` e `INTERROMPIDA`. | `DI-010` |
| Resultados por unidade | `CONCLUIDO`, `SEM_ALTERACAO`, `FALHA_RECONCILIAVEL` e `BLOQUEADO`. | `DI-007` |
| `RETIFICAR` | Decisão de sincronizar inclusões ou exclusões homologadas em um PCA ativo. | `RN-PUB-002`, `RN-PUB-006` |
| Retificação | Execução de inclusões e exclusões no PCA ativo, preservando seu sequencial. | `RN-PUB-002`; `DI-001` |
| Sequencial PCA — `sequencialPca` | Referência operacional do recurso externo, sem participação na identidade do Plano. | `RN-PUB-008`; `DI-005`, `DI-008` |
| Sincronização | Busca de correspondência entre o conjunto homologado vigente e o conjunto publicado observado. | `RN-PUB-006`; `DI-002` |
| Unidade externa de publicação | Escopo que possui uma publicação e um resultado: uma UO ou um conjunto consolidado. | `RN-PUB-009`, `RN-PUB-010`; `DI-003` |
| V1 da integração | Primeira geração operacional da estratégia de publicação, sincronização e reconciliação, distinta do Fluxo de Implementação V2 e da documentação histórica V1. | `DI-001`, `DI-002`, `DI-004`, `DI-009`, `DI-010`; Fluxo §1; Docs §5.4 |

## 5. Termos de auditoria

| Termo | Definição curta | Fonte |
| --- | --- | --- |
| Auditoria de negócio | Registro de uma operação concluída e confirmada, distinto de diagnóstico técnico. | `RN-AUD-001`; `DI-006` |
| Confirmação de sincronização | Registro versionado de que a convergência daquela versão foi comprovada. | `RN-PLA-007`; `DI-005` |
| Histórico de procedimento | Fato imutável que registra uma ação auditável concluída e confirmada. | `RN-AUD-001`, `RN-AUD-003`; Modelo §10 |
| Observabilidade técnica | Registros de tentativas, falhas, conflitos e diagnóstico que não integram o histórico de negócio. | `DI-006` |
| Procedimento | Classificação estável de uma ação formal auditável. | `RN-AUD-002`; Modelo §10.1 |
| Responsável pela operação | Usuário autenticado associado à sua Lotação no fato auditado. | `RN-AUD-004`; `DI-006` |

Publicação ativa e confirmação não são equivalentes: a primeira descreve o
recurso externo; a segunda pertence à versão homologada (`DI-005`).

## 6. Termos arquiteturais

| Termo | Definição curta | Fonte |
| --- | --- | --- |
| Adapter | Implementação concreta de uma Port para um mecanismo externo; a implementação concreta de um Repository também exerce esse papel, mesmo quando conserva o sufixo `Repository`. | Arquitetura §§5 e 8 |
| Application | Camada de casos de uso, orquestração e contratos necessários ao fluxo. | Arquitetura §4.2; `DI-007` |
| Aplicação hospedeira | Sistema que inicia e sustenta a execução do backend; neste projeto, a Blank do Scriptcase fornece conexão, login e dados HTTP. | Fluxo §2; `DI-010` |
| Autoload | Mecanismo que localiza e carrega classes PHP quando elas são usadas, evitando arquivos `require` espalhados pelo código. | Arquitetura §7; `DI-010` |
| Bootstrap | Passos iniciais necessários para tornar a aplicação executável, como carregar o autoload e preparar o entrypoint. | Arquitetura §7; `DI-010` |
| Classe `final` | Classe que não pode ser herdada. É usada quando sua responsabilidade deve permanecer fechada, sem variações por subclasses. | Arquitetura §§3 e 8 |
| Clean Architecture | Organização em camadas na qual as regras internas não dependem dos mecanismos externos; as dependências apontam para o núcleo. | Arquitetura §§3 e 4 |
| Composition Root | Ponto do backend que cria o grafo e injeta dependências concretas. | Arquitetura §7; `DI-010` |
| Montador do núcleo | Componente intermediário que recebe Ports e logger já construídos e compõe a topologia da Application, sem selecionar perfil ou administrar recursos externos. | Roadmap Sprint 6.1 |
| Conexão emprestada | Conexão criada e administrada pela aplicação hospedeira, reutilizada pelo backend sem transferência de seu ciclo de vida; o backend não a fecha. | `DI-008`; Roadmap Sprint 5.2 |
| Construtor de objeto — `__construct` | Método chamado ao criar um objeto. Recebe dependências e dados necessários para deixar a instância válida; não é o construtor do pacote de implantação. | Arquitetura §§3 e 7 |
| Contrato | Forma estável pela qual um componente pode ser usado: operações, entradas, saídas e comportamentos observáveis esperados. Pode ser expresso por interface, DTO, método público ou especificação externa. | Arquitetura §§5 e 8 |
| Controller | Componente que adapta a entrada pública para o caso de uso. | Arquitetura §§4.4 e 8; `DI-010` |
| DDD | Abordagem que modela o software a partir da linguagem, das regras e das fronteiras do negócio. Neste projeto orienta Domain, Aggregates, Entities e Value Objects. | Modelo; Arquitetura §8 |
| Dependência | Colaborador de que um componente precisa para cumprir sua responsabilidade, como uma Port, logger ou relógio. | Arquitetura §§3 e 5 |
| Enum | Tipo com um conjunto fechado de valores nomeados, usado para impedir estados textuais arbitrários. | Modelo §3; Arquitetura §8 |
| Grafo de dependências | Conjunto de objetos ligados pelas dependências necessárias a uma execução. O Composition Root monta um grafo novo por chamada. | Arquitetura §7; `DI-010` |
| Imutabilidade | Propriedade de um objeto cujo estado não muda depois da criação; uma alteração conceitual produz outro valor ou resultado. | Modelo §3; Arquitetura §8 |
| Injeção de dependências | Entrega explícita dos colaboradores a um objeto, geralmente pelo construtor, em vez de o próprio objeto procurá-los ou criá-los escondidamente. | Arquitetura §§3, 5 e 7 |
| Domain | Camada que representa o negócio, protege invariantes e decide por políticas puras. | Modelo; Arquitetura §4.1 |
| Double de teste | Substituto controlado usado para demonstrar como um consumidor utiliza um contrato. | Arquitetura §9; `DI-007` |
| Entrypoint Scriptcase | Única API do backend conhecida pela Blank; recebe recursos naturais do host, seleciona o perfil externo e aciona o grafo interno. | Arquitetura §7; `DI-010` |
| Fake | Double de teste com implementação simplificada, mas funcional, como um repositório em memória usado sem acessar o Oracle. | Arquitetura §9 |
| Fachada pública do backend | Papel exercido por `PublicadorPcaScriptcase`: oferece ao hospedeiro uma API pequena e segura, escondendo configuração, Composition Root e camadas internas. | Arquitetura §7; `DI-010` |
| Fachada da Presentation | Fronteira interna que converte a execução em ViewModel seguro e impede a passagem de exceções ao consumidor. | Arquitetura §7; `DI-010` |
| Infrastructure | Camada dos adapters concretos de persistência, transporte e recursos externos. | Arquitetura §4.3 |
| Integração controlada do núcleo | Prova interna que monta adapters concretos sobre fronteiras programáveis e sem I/O real para verificar composição, compartilhamento de sessão e encerramento transacional. Não substitui gate real nem constitui o Composition Root final. | Roadmap Sprint 6.2; Arquitetura §9 |
| Mapeamento físico explícito | Descrição injetada e validada dos nomes físicos e literais usados pelo adapter, sem incorporá-los aos contratos internos nem assumir valores implícitos. | `DI-008`; Arquitetura §4.3 |
| Mapeador de dados | Tradutor dos valores retornados por um mecanismo externo para tipos internos, sem definir nomes físicos nem decidir regras de negócio. | Arquitetura §4.3 |
| Mock | Double de teste configurado principalmente para verificar se uma interação esperada ocorreu, por exemplo se uma Port recebeu determinada chamada. | Arquitetura §9 |
| Namespace | Nome hierárquico que organiza classes PHP e evita colisões entre nomes iguais; o PSR-4 o relaciona aos diretórios. | Arquitetura §3 |
| Port | Contrato interno pertencente à camada que necessita da capacidade. | Arquitetura §5; `DI-007` |
| PSR-4 | Convenção que relaciona namespace e caminho de arquivo para o autoload localizar classes PHP. | Arquitetura §3; catálogo de arquivos |
| Presentation | Camada que protege a entrada pública e apresenta o resultado. | Arquitetura §4.4; `DI-010` |
| Presenter | Componente que transforma a Response em ViewModel estruturado. | Arquitetura §§4.4 e 8; `DI-010` |
| Repository | Port especializada em reconstrução e persistência de um Aggregate; sua implementação concreta também é um Adapter. | Arquitetura §§5 e 8; `DI-007` |
| Request | Contrato de entrada de um caso de uso. | Arquitetura §8; `DI-010` |
| Response | Contrato de saída de um caso de uso. | Arquitetura §8; `DI-010` |
| SOLID | Conjunto de princípios para manter responsabilidades claras, dependências direcionadas e componentes substituíveis. É um meio de reduzir acoplamento, não uma exigência de criar mais classes. | Arquitetura §§3 e 5 |
| Stub | Double de teste que fornece respostas previamente definidas para conduzir um cenário, sem ter como finalidade principal verificar chamadas. | Arquitetura §9 |
| Sessão compartilhada | Mesma sessão Oracle e sua transação reutilizadas pelos adapters que participam da unidade, preservando contenção, visibilidade e atomicidade locais. | `DI-004`, `DI-008` |
| Registro externo de perfis | Índice provisionado fora da release que associa literalmente `SERVER_NAME` ao caminho absoluto de um perfil, sem duplicar sua configuração. | `DI-010` |
| Sessão opaca | Capacidade autenticada do PNCP usada sem interpretar nem expor o token que a representa e descartada ao fim da execução. | `DI-009`, `DI-010` |
| Teste contratual | Teste que demonstra compatibilidade com um contrato interno ou externo. | Arquitetura §9; `DI-007`, `DI-009` |
| TDD | Ciclo de desenvolvimento no qual primeiro se demonstra a falta do comportamento com um teste, depois se implementa o mínimo e por fim se melhora o código: Red, Green e Refactor. | Arquitetura §9 |
| Tradução de erro externo | Conversão de falhas do mecanismo externo em resultados internos tipados e seguros, sem fazer sua representação física atravessar a Port. | `DI-008`, `DI-009`; Arquitetura §4.3 |
| Unidade de trabalho | Abstração usada para coordenar atomicidade local e conclusão de uma unidade. | `DI-004`, `DI-007` |
| UseCase | Componente da Application que orquestra uma intenção usando Domain e Ports. | Arquitetura §§4.2 e 8 |
| ViewModel | Estrutura de apresentação produzida pelo Presenter e renderizada pelo consumidor. | Arquitetura §7; `DI-010` |
| Wiring | Composição das implementações concretas no grafo de dependências. | Arquitetura §§3 e 7; `DI-010` |

### 6.1 Termos de validação da Infrastructure

| Termo | Definição curta | Fonte |
| --- | --- | --- |
| Controle de concorrência (antes “âncora”) | Linha técnica da tabela PAC_CONTROLES_CONCORRENCIA, sem identidade de negócio, utilizada para serializar execuções por ESCOPO + ANO_EXECUCAO + IDENTIFICADOR_ALVO. Ela funciona como um "ponto de cadeado" persistente: se duas execuções disputarem a mesma publicação, ambas encontrarão essa mesma linha e uma delas será bloqueada via FOR UPDATE NOWAIT. A alteração do termo "âncora" para "controle de concorrência" é apenas semântica e não altera o comportamento do adapter. | `DI-008`; Sprint 5.9 |
| Ambiente de treinamento | Ambiente oficial protegido, distinto da produção, no qual integrações externas autorizadas podem ser exercitadas com massa dedicada. | `DI-009` |
| Cardinalidade esperada | Quantidade exata ou mínima de linhas que uma consulta deve retornar para satisfazer um checkpoint. Divergência interrompe o gate. | Fluxo §11; Roadmap Sprint 8.4 |
| Checkpoint Oracle | Fotografia verificável do estado Oracle, formada por consultas, colunas e cardinalidades conhecidas e identificada por hash canônico. | Roadmap Sprints 8.4 e 8.5 |
| Contrato de gate | Verificação executável das pré-condições, permissões e resultados esperados de um gate. Preparar ou aprovar o contrato não autoriza a execução real. | `DI-008` a `DI-010`; Roadmap Sprint 8.5 |
| Fixture | Cenário pré-montado com dados pequenos e controlados, preparado para simular uma situação específica sem depender dos sistemas reais. | Arquitetura §9 |
| Contrato controlado | Prova contratual executada com condições, fixtures ou doubles controlados; demonstra o comportamento do adapter, mas não substitui a evidência obtida no ambiente externo real. | Arquitetura §9; Roadmap Sprints 5.9 e 6.2 |
| Fail-closed | Postura que recusa a execução antes da interação externa quando configuração, destino, autorização ou pré-condição não estão comprovados, sem recorrer a valores alternativos silenciosos. | `DI-009`, `DI-010` |
| Gate real | Condição de avanço comprovada no ambiente efetivo por meio dos adapters concretos; a aprovação em ambiente controlado, isoladamente, não o encerra. | `DI-008`, `DI-009`; Roadmap Sprint 5.9 |
| Hash canônico | Resumo criptográfico calculado depois que os dados são organizados e normalizados por uma regra única. O mesmo estado produz o mesmo SHA-256; uma divergência relevante produz outro valor. | Roadmap Sprints 8.4 e 8.5 |
| Limpeza best-effort | Tentativa de remover os artefatos criados pelo teste sem presumir que a remoção sempre será possível; qualquer resíduo possível deve permanecer diagnosticado. | `DI-009` |
| Massa dedicada | Conjunto de registros expressamente reservado à execução contratual, usado para evitar escritas sobre dados operacionais arbitrários e permitir rastreamento e limpeza. | `DI-009` |
| Preflight | Verificação anterior à interação externa que confirma configuração, destino, autorização de escrita e demais pré-condições exigidas; sua falha impede a execução. | `DI-009`, `DI-010` |
| Probe | Ponto de execução restrito usado para investigar ou comprovar um contrato no ambiente real. Não é a API normal da aplicação. | Fluxo §11; guia de execução das probes |
| Regressão | Reexecução de testes já existentes para verificar que uma mudança nova não quebrou comportamentos anteriormente aprovados. | Arquitetura §9 |
| Relatório sanitizado | Evidência de execução limitada a estados, códigos, mensagens seguras e identificadores não sensíveis, sem credenciais, sessões, headers, corpos ou payloads integrais. | Logs; `DI-006`, `DI-009` |
| Resíduo | Artefato externo que pode ter permanecido após uma execução interrompida, incerta ou cuja limpeza não foi comprovada. | `DI-009` |
| Runner | Coordenador de um cenário contratual: executa etapas na ordem, aplica guardas e reúne o relatório. Não pertence ao fluxo normal de produção. | Roadmap Sprints 5.9, 8.3 e 8.4 |
| Snapshot | Representação dos resultados de um conjunto de consultas em um instante lógico, normalizada para comparação e cálculo de hash. | Roadmap Sprints 8.4 e 8.5 |
| Suíte opt-in | Conjunto de testes excluído da execução padrão e iniciado apenas por comando e configuração explícitos. Pode realizar interação externa autorizada em ambiente protegido e nunca deve ser acionado implicitamente. | `DI-009`; Fluxo §11; Roadmap Sprint 5.9 |
| Teste de integração | Teste que verifica a colaboração entre componentes reais, mantendo fronteiras externas controladas quando não se deseja I/O real. | Arquitetura §9 |
| Teste unitário | Teste focado no comportamento de uma unidade pequena, usando colaboradores simples ou doubles quando necessário. | Arquitetura §9 |

### 6.2 Termos técnicos de execução e integração

| Termo | Definição curta | Fonte |
| --- | --- | --- |
| ADOdb | Biblioteca usada pelo Scriptcase para oferecer a conexão Oracle ao backend. O backend a recebe pronta e não administra seu ciclo de vida. | `DI-008`, `DI-010` |
| API | Contrato pelo qual um software oferece operações para outro componente ou sistema. Neste projeto, a fachada pública é a API consumida pela Blank, e o PNCP possui uma API HTTP externa. | Arquitetura §7; `DI-009`, `DI-010` |
| Bind ou parâmetro vinculado | Valor enviado separadamente do texto SQL por um placeholder. Evita concatenar dados na consulta e preserva tipagem e tratamento pelo driver. | `DI-008`; Fluxo §9 |
| CA bundle | Arquivo com uma coleção de certificados públicos confiáveis. O cURL/OpenSSL usa essa coleção para verificar se o certificado HTTPS apresentado pelo PNCP pertence a uma cadeia confiável. Ele não contém senha, token ou chave privada. Neste projeto, ele foi copiado integralmente do bundle já usado em DEV, sem gerar certificados novos. | `DI-009`, `DI-010` |
| CLOB | Tipo Oracle para texto grande. `SEGURANCA_LOG.DESCRIPTION` usa CLOB para armazenar o JSON integral dos eventos técnicos. | `DI-006`, `DI-010` |
| Commit | Confirmação de uma transação, tornando permanentes suas alterações locais. | `DI-004`, `DI-008` |
| Configuração externa | Configuração mantida fora da release e do webroot, com parâmetros e possíveis segredos específicos do ambiente. | `DI-010` |
| Correlação ou Request ID | UUID v4 que identifica a mesma execução através das camadas, resultados e eventos técnicos. | Logs; `DI-010` |
| DML | Comandos SQL que alteram dados, como `INSERT`, `UPDATE`, `DELETE` e `MERGE`. Um PRECHECK não executa DML. | `DI-008`; Fluxo §11 |
| Envelope de evento | Estrutura completa de um evento técnico, com campos institucionais, contexto sanitizado e metadados. | Logs; `DI-006` |
| Endpoint | Endereço ou operação específica oferecida por uma API. Em uma API HTTP, normalmente combina um caminho e um método, como `POST /recurso`. | `DI-009` |
| HTTP | Protocolo de comunicação baseado em requisições e respostas, usado pelo backend para acessar a API do PNCP. | `DI-009` |
| I/O | Entrada ou saída que interage com algo fora do cálculo em memória, como banco, rede, arquivo ou resposta HTTP. | Arquitetura §§4 e 9 |
| Idempotência | Propriedade de uma operação que pode ser repetida sem produzir efeito adicional depois da primeira aplicação bem-sucedida. Não significa que toda requisição possa ser repetida com segurança. | `DI-004`, `DI-009` |
| JSON | Formato textual usado para transportar estruturas de dados. Na Blank, o resultado HTTP e cada evento técnico são serializados em JSON. | `DI-006`, `DI-010` |
| JWT | Formato compacto de token assinado que pode transportar informações de autenticação. Deve ser tratado como dado sensível e não aparecer em logs. | `DI-006`, `DI-009` |
| caminho portátil | `__DIR__` indica em PHP o diretório do arquivo atual. Ele permite localizar arquivos vizinhos sem fixar um caminho específico do Windows ou Linux. | `DI-010` |
| Lint | Verificação automática de sintaxe e, conforme a ferramenta, de convenções básicas sem executar o fluxo funcional. | Arquitetura §9 |
| Macro Scriptcase | Comando reconhecido e transformado pelo Scriptcase, como `sc_log_add`; pode não funcionar como uma função PHP comum fora desse ambiente. | `DI-010`; Roadmap Sprint 8.5 |
| Payload | Conteúdo enviado ou recebido por uma requisição, separado dos headers e do status do transporte. | `DI-009`; Logs |
| Retry | Nova tentativa automática de uma operação após falha. Só é seguro quando o contrato permite repetição; neste projeto, escritas incertas no PNCP não recebem retry automático. | `DI-004`, `DI-009` |
| Rollback | Cancelamento das alterações ainda não confirmadas de uma transação. | `DI-004`, `DI-008` |
| Runtime | Ambiente efetivo no qual o código roda, incluindo versão do PHP, extensões, sistema operacional e comportamento das bibliotecas. | `DI-008` a `DI-010` |
| Sanitização | Remoção, substituição ou limitação de dados que não podem atravessar uma fronteira, como credenciais, SQL, stack trace ou payload sensível. | Logs; `DI-006`, `DI-010` |
| Serialização | Conversão de um objeto ou estrutura em uma representação transportável ou persistível, como JSON. | Logs; `DI-009`, `DI-010` |
| Status HTTP | Código numérico da resposta HTTP que resume o resultado do transporte, como `200`, `400` ou `500`; ele não substitui o resultado de negócio da aplicação. | `DI-009`, `DI-010` |
| Timeout | Limite máximo de espera por uma operação. Ao expirar, interrompe a espera, mas não prova sozinho se uma escrita externa produziu efeito. | `DI-004`, `DI-009` |
| Transação | Fronteira que agrupa operações Oracle para que sejam confirmadas juntas por commit ou desfeitas por rollback. | `DI-004`, `DI-008` |
| TLS | Protocolo que protege a conexão de rede e autentica o servidor por meio de certificados. | `DI-009` |
| Variável de ambiente | Valor provisionado pelo sistema operacional ou servidor para informar caminhos e configurações sem incorporá-los ao código. | `DI-010` |

## 7. Termos de operação e observabilidade

| Termo | Definição curta | Fonte |
| --- | --- | --- |
| Alerta | Notificação gerada quando uma condição relevante é atendida, como uma taxa de erros acima de um limite. Um alerta pede avaliação humana, mas não prova sozinho que existe um incidente. | Roadmap Sprint 9.2 |
| Dashboard | Painel visual que organiza gráficos, números e outros indicadores para facilitar o acompanhamento de um sistema. Ele apresenta dados obtidos de uma fonte de dados. | Roadmap Sprint 9.2; Estado |
| Disponibilidade | Capacidade de um serviço permanecer acessível e apto a cumprir sua função durante o período observado. | Roadmap Fase 9 |
| Fonte de dados | Sistema ou serviço do qual uma ferramenta consulta informações, como um banco Oracle ou uma plataforma de logs. A fonte armazena ou fornece os dados; o dashboard apenas os apresenta. | Estado |
| Grafana | Plataforma de visualização e alertas que consulta fontes de dados e organiza informações em dashboards. Ela não é, necessariamente, o local onde os dados ficam armazenados. | Estado |
| Incidente | Evento não planejado que interrompe ou degrada um serviço e exige análise, resposta ou recuperação. | Roadmap Fase 9 |
| Latência | Tempo decorrido entre iniciar uma operação e receber seu resultado; em uma requisição, corresponde ao tempo percebido até a resposta. | Logs; `DI-009` |
| Log | Registro datado de um evento ocorrido durante a execução de um sistema. Pode conter nível, mensagem, contexto e identificador de correlação. | Logs |
| Log técnico | Log voltado ao funcionamento e ao diagnóstico do software, como uma tentativa de autenticação ou uma falha de transporte; não equivale a um fato confirmado de negócio. | Logs; `DI-006` |
| Logging | Atividade de produzir, estruturar, transportar ou persistir logs durante a execução do software. | Logs; `DI-006`, `DI-010` |
| Métrica | Medida numérica acompanhada ao longo do tempo, como quantidade de erros, duração das execuções ou percentual de disponibilidade. | Roadmap Sprint 9.2 |
| Monitoramento | Acompanhamento contínuo de sinais e condições previamente escolhidos para detectar comportamentos esperados ou problemas conhecidos. | Roadmap Sprint 9.2 |
| Observabilidade | Capacidade de compreender o estado interno de um sistema a partir dos sinais que ele produz, principalmente logs, métricas e rastreamentos. | Logs; `DI-006`; Roadmap Sprint 9.2 |
| Política de retenção | Regra que determina por quanto tempo os dados são mantidos e quando devem ser arquivados ou excluídos, considerando necessidades operacionais, legais e de armazenamento. | Roadmap Sprint 9.2 |
| Rastreamento ou tracing | Representação do caminho percorrido por uma operação entre componentes, relacionando suas etapas para ajudar a localizar atrasos e falhas. | Roadmap Sprint 9.2 |
| Telemetria | Coleta e transmissão automática de sinais sobre a execução de um sistema, como logs, métricas e rastreamentos. | `DI-006`; Logs |

## 8. Termos de entrega e implantação

| Termo | Definição curta | Fonte |
| --- | --- | --- |
| Artefato | Resultado concreto preparado por uma etapa do projeto, como um pacote, manifesto, relatório ou arquivo versionado. O termo não informa sozinho se o conteúdo é executável. | Roadmap; Docs |
| Build | Processo que transforma código-fonte e dependências em um artefato verificável e pronto para uma finalidade, como teste ou implantação. Também pode nomear o resultado desse processo. | `DI-010`; Roadmap Sprints 8.5 e 9.3 |
| Cache | Cópia temporária de dados ou arquivos reutilizada para acelerar operações posteriores. Pode ser descartada e não deve ser tratada como fonte autoritativa. | Catálogo de diretórios; Roadmap Sprint 8.5 |
| Commit do Git | Registro identificado no histórico do repositório que reúne uma fotografia das alterações versionadas do código-fonte. É diferente do commit que confirma uma transação de banco. | `AGENTS.md` §3 |
| Composer | Ferramenta usada durante o desenvolvimento e a construção para resolver dependências PHP e gerar o autoload. Não é executada no servidor de produção. | `DI-010` |
| `composer.lock` | Arquivo que fixa as versões exatas das dependências resolvidas, permitindo reconstruir o mesmo conjunto em outra máquina. | `DI-010`; Roadmap Sprint 8.5 |
| Construtor de pacote — package builder | Script de preparação que reúne o código permitido, instala dependências de produção e gera o manifesto. Não é um construtor `__construct` de classe. | `DI-010`; Roadmap Sprint 8.5 |
| Deploy ou implantação | Ato de colocar uma release em um ambiente e torná-la disponível para execução. Copiar arquivos é uma parte possível da implantação. | Roadmap Fase 9 |
| DocumentRoot ou webroot | Diretório cujos arquivos podem ser publicados pelo servidor HTTP. Configurações sensíveis ou específicas do ambiente devem permanecer fora dele. | `DI-010`; Roadmap Fase 9 |
| Manifesto de integridade | Arquivo que descreve o pacote e registra hashes de seu conteúdo para permitir conferência posterior. | Roadmap Sprint 8.5 |
| Pacote autocontido | (artefato final de uma build) É a pasta de entrega com o código e todas as dependências necessárias em `vendor/`, executável sem checkout e sem Composer no servidor. Configurações e segredos continuam externos. O processo de build pega o código-fonte bruto do repositório, baixa e resolve as dependências (como a pasta vendor/ via Composer), compila ou minifica arquivos (se houver frontend) e gera esse pacote autocontido e pronto para ser executado. | `DI-010`; Roadmap Sprint 8.5 |
| Princípio do menor privilégio | Prática de conceder a cada usuário ou processo somente os acessos necessários. Na configuração do PCA, o administrador escreve, o PHP apenas lê e os demais usuários não acessam os arquivos. | Roadmap Sprint 9.1 |
| Release | Conjunto versionado e preparado para implantação. Pode incluir um pacote executável e instruções, sem significar que já foi implantado. | `DI-010`; Roadmap Fase 9 |
| Runbook | Procedimento operacional passo a passo para executar e conferir uma atividade recorrente ou sensível, como configurar, implantar ou recuperar um serviço. | Roadmap Fase 9 |
| Smoke test | Validação preliminar pós-deploy que confirma se a build está estável e se os caminhos críticos produzem o resultado esperado antes de liberar o ambiente para testes mais profundos. Não substitui a suíte completa de testes. | `DI-010`; Roadmap Sprint 8.5 |
| `vendor/` | Diretório gerado pelo Composer com dependências e arquivos de autoload. No pacote, contém somente dependências de produção. | `DI-010` |

## 9. Termos de documentação e planejamento

| Termo | Definição curta | Fonte |
| --- | --- | --- |
| ADR-lite | Registro conciso que documenta o contexto, a decisão tomada, as alternativas avaliadas, suas consequências e os critérios de aprovação (gates). | `DI`; Docs |
| Apoio | Documento que explica e contextualiza sem criar norma concorrente. | Docs |
| Autoritativo | Documento que constitui fonte oficial para seu escopo declarado. | Docs |
| Checkpoint de planejamento | Registro incremental que preserva continuidade e precedência do planejamento entre sessões. Não é o checkpoint Oracle. | Agentes |
| DI — Decisão de Integração | Registro formal da escolha técnica sobre contratos de dados externos, padrões de arquitetura e modelos operacionais para a comunicação entre sistemas. | `DI`; Docs |
| Fluxo de implementação | Documento vigente que descreve colaboração dinâmica entre camadas e portas. | Docs; seção 17 dos checkpoints |
| Gate | Condição de validação baseada em evidências que libera um avanço específico do projeto. Um gate existe quando uma decisão depende de hipóteses ainda não comprovadas. Enquanto permanecer aberto, os avanços que dependem dessa evidência permanecem bloqueados. | `DI`; Roadmap; checkpoints |
| Histórico | Documento substituído preservado para auditoria e aprendizado. | Docs |
| Investigação | Evidência de pesquisa ou experimentação sem autoridade normativa. | Docs |
| Regra de Negócio — RN | Enunciado normativo estável do comportamento exigido pelo negócio. | `RN`; Docs |
| Roadmap | Documento que organiza a ordem de implementação sem descobrir regras. | Docs; checkpoints |
| Síntese | Fonte independente de conferência que não substitui o checkpoint correspondente. | Agentes |
| Status documental | Situação atual de um documento, distinta de sua classificação. | Docs |

## 10. Distinções essenciais

| Não confundir | Distinção | Fonte |
| --- | --- | --- |
| Plano × versão | Identidade lógica versus marco homologado. | `RN-PLA-001` a `RN-PLA-003`; Modelo §7 |
| Fotografia vigente × versão de origem | Conjunto atual versus momento em que a Demanda entrou no planejamento. | `RN-PLA-004` |
| UO × Lotação × UASG | Responsabilidade pelo Plano, autoria organizacional e unidade compradora. | `RN-CAD-004`, `RN-CAD-005` |
| Publicação ativa × confirmação | Existência do recurso externo versus convergência da versão. | `RN-PLA-007`; `DI-005` |
| Estado desejado × observado × operacional | Planejamento homologado, fato externo e referência local cross-Plan. | `DI-002`, `DI-005` |
| Sequencial PCA × identidade do Plano | Referência externa versus UO e Ano de Execução. | `RN-PLA-001`; `DI-005` |
| Port × Adapter × Repository | Port é o contrato; Adapter é sua implementação concreta; Repository é uma especialização de Port ligada a Aggregate, cuja implementação concreta também é Adapter. | Arquitetura §§5 e 8; `DI-007` |
| Suíte padrão × suíte opt-in | Execução ordinária, sem interação externa real implícita, versus execução deliberadamente ativada e protegida por configuração e preflight. | `DI-009`; Fluxo §11 |
| Contrato controlado × gate real | Evidência determinística em condições controladas versus comprovação com os adapters concretos no ambiente externo efetivo. | `DI-008`, `DI-009`; Roadmap Sprint 5.9 |
| Conexão emprestada × conexão própria | Recurso cujo ciclo de vida pertence à aplicação hospedeira versus recurso criado e administrado pelo próprio backend. | `DI-008` |
| Entrypoint × Fachada × Controller × Composition Root | Bootstrap público do host, proteção da Presentation, adaptação da chamada e wiring. | Arquitetura §§7 e 8; `DI-010` |
| Fachada pública do backend × fachada da Presentation | `PublicadorPcaScriptcase` é a API consumida pela Blank e prepara a execução; `FachadaPublicarPca` é interna à Presentation e protege o resultado contra falhas e exceções. | Arquitetura §7; `DI-010` |
| Construtor de objeto × construtor de pacote | `__construct` deixa uma instância válida em memória; o construtor de pacote prepara arquivos para entrega. | Arquitetura §7; `DI-010` |
| Artefato × pacote × release × deploy | Artefato é qualquer resultado concreto; pacote é a pasta executável; release é o conjunto preparado para entrega; deploy instala a release em um ambiente. | `DI-010`; Roadmap Fase 9 |
| Manifesto × pacote | O manifesto descreve e protege a integridade; o pacote contém o código que será executado. | Roadmap Sprint 8.5 |
| Checkpoint de planejamento × checkpoint Oracle | O primeiro preserva decisões e continuidade documental; o segundo fotografa dados Oracle para bloquear execução sobre estado divergente. | Agentes; Roadmap Sprints 8.4 e 8.5 |
| Preflight × gate real × smoke test | Preflight valida condições antes de I/O; gate real comprova uma condição no ambiente efetivo; smoke test verifica rapidamente o caminho principal já instalado. | `DI-009`, `DI-010`; Roadmap |
| Auditoria × observabilidade | Fato de negócio confirmado versus diagnóstico técnico. | `DI-006` |
| Observabilidade × monitoramento | Observabilidade fornece sinais para compreender inclusive situações ainda desconhecidas; monitoramento acompanha condições previamente escolhidas. | Logs; Roadmap Sprint 9.2 |
| Log × métrica | Log registra um evento com contexto; métrica representa uma medida numérica que pode ser agregada e acompanhada ao longo do tempo. | Logs; Roadmap Sprint 9.2 |
| Dashboard × fonte de dados | Dashboard apresenta informações; fonte de dados armazena ou fornece as informações consultadas. | Estado |
| Alerta × incidente | Alerta é uma notificação baseada em uma condição; incidente é uma degradação ou interrupção real que exige resposta. | Roadmap Fase 9 |
| Commit do Git × commit de transação | O primeiro registra uma fotografia no histórico do código; o segundo confirma alterações de uma transação no banco de dados. | `AGENTS.md` §3; `DI-004`, `DI-008` |
| Regra × decisão × fluxo | Norma de negócio, escolha arquitetural e colaboração dinâmica. | Docs; `RN`; `DI` |
