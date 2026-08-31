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
- [Governança documental](../../README.md) — Docs;
- [Governança dos agentes](../../../.agents/README.md) — Agentes.

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
| Adapter | Implementação concreta de uma Port para um mecanismo externo. | Arquitetura §§5 e 8 |
| Application | Camada de casos de uso, orquestração e contratos necessários ao fluxo. | Arquitetura §4.2; `DI-007` |
| Composition Root | Ponto do backend que cria o grafo e injeta dependências concretas. | Arquitetura §7; `DI-010` |
| Conexão emprestada | Conexão criada e administrada pela aplicação hospedeira, reutilizada pelo backend sem transferência de seu ciclo de vida; o backend não a fecha. | `DI-008`; Roadmap Sprint 5.2 |
| Controller | Componente que adapta a entrada pública para o caso de uso. | Arquitetura §§4.4 e 8; `DI-010` |
| Domain | Camada que representa o negócio, protege invariantes e decide por políticas puras. | Modelo; Arquitetura §4.1 |
| Double de teste | Substituto controlado usado para demonstrar como um consumidor utiliza um contrato. | Arquitetura §9; `DI-007` |
| Fachada pública | Único ponto do backend consumido pela aplicação hospedeira. | Arquitetura §7; `DI-010` |
| Infrastructure | Camada dos adapters concretos de persistência, transporte e recursos externos. | Arquitetura §4.3 |
| Mapeamento físico explícito | Descrição injetada e validada dos nomes físicos e literais usados pelo adapter, sem incorporá-los aos contratos internos nem assumir valores implícitos. | `DI-008`; Arquitetura §4.3 |
| Port | Contrato interno pertencente à camada que necessita da capacidade. | Arquitetura §5; `DI-007` |
| Presentation | Camada que protege a entrada pública e apresenta o resultado. | Arquitetura §4.4; `DI-010` |
| Presenter | Componente que transforma a Response em ViewModel estruturado. | Arquitetura §§4.4 e 8; `DI-010` |
| Repository | Port especializada em reconstrução e persistência de um Aggregate. | Arquitetura §§5 e 8; `DI-007` |
| Request | Contrato de entrada de um caso de uso. | Arquitetura §8; `DI-010` |
| Response | Contrato de saída de um caso de uso. | Arquitetura §8; `DI-010` |
| Sessão compartilhada | Mesma sessão Oracle e sua transação reutilizadas pelos adapters que participam da unidade, preservando contenção, visibilidade e atomicidade locais. | `DI-004`, `DI-008` |
| Sessão opaca | Capacidade autenticada do PNCP usada sem interpretar nem expor o token que a representa e descartada ao fim da execução. | `DI-009`, `DI-010` |
| Teste contratual | Teste que demonstra compatibilidade com um contrato interno ou externo. | Arquitetura §9; `DI-007`, `DI-009` |
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
| Contrato controlado | Prova contratual executada com condições, fixtures ou doubles controlados; demonstra o comportamento do adapter, mas não substitui a evidência obtida no ambiente externo real. | Arquitetura §9; Roadmap Sprint 5.9 |
| Fail-closed | Postura que recusa a execução antes da interação externa quando configuração, destino, autorização ou pré-condição não estão comprovados, sem recorrer a valores alternativos silenciosos. | `DI-009`, `DI-010` |
| Gate real | Condição de avanço comprovada no ambiente efetivo por meio dos adapters concretos; a aprovação em ambiente controlado, isoladamente, não o encerra. | `DI-008`, `DI-009`; Roadmap Sprint 5.9 |
| Limpeza best-effort | Tentativa de remover os artefatos criados pelo teste sem presumir que a remoção sempre será possível; qualquer resíduo possível deve permanecer diagnosticado. | `DI-009` |
| Massa dedicada | Conjunto de registros expressamente reservado à execução contratual, usado para evitar escritas sobre dados operacionais arbitrários e permitir rastreamento e limpeza. | `DI-009` |
| Preflight | Verificação anterior à interação externa que confirma configuração, destino, autorização de escrita e demais pré-condições exigidas; sua falha impede a execução. | `DI-009`, `DI-010` |
| Relatório sanitizado | Evidência de execução limitada a estados, códigos, mensagens seguras e identificadores não sensíveis, sem credenciais, sessões, headers, corpos ou payloads integrais. | Logs; `DI-006`, `DI-009` |
| Resíduo | Artefato externo que pode ter permanecido após uma execução interrompida, incerta ou cuja limpeza não foi comprovada. | `DI-009` |
| Suíte opt-in | Conjunto de testes excluído da execução padrão e iniciado apenas por comando e configuração explícitos. Pode realizar interação externa autorizada em ambiente protegido e nunca deve ser acionado implicitamente. | `DI-009`; Fluxo §11; Roadmap Sprint 5.9 |

## 7. Termos de documentação e planejamento

| Termo | Definição curta | Fonte |
| --- | --- | --- |
| ADR-lite | Registro conciso de contexto, decisão, alternativas, consequências e gates. | `DI`; Docs |
| Apoio | Documento que explica e contextualiza sem criar norma concorrente. | Docs |
| Autoritativo | Documento que constitui fonte oficial para seu escopo declarado. | Docs |
| Checkpoint | Registro incremental que preserva continuidade e precedência do planejamento. | Agentes |
| Decisão de Integração — DI | Escolha registrada sobre contratos externos, arquitetura de integração ou operação. | `DI`; Docs |
| Fluxo de implementação | Documento vigente que descreve colaboração dinâmica entre camadas e portas. | Docs; seção 17 dos checkpoints |
| Gate | Condição de validação baseada em evidências que libera um avanço específico do projeto. Um gate existe quando uma decisão depende de hipóteses ainda não comprovadas. Enquanto permanecer aberto, os avanços que dependem dessa evidência permanecem bloqueados. | `DI`; Roadmap; checkpoints |
| Histórico | Documento substituído preservado para auditoria e aprendizado. | Docs |
| Investigação | Evidência de pesquisa ou experimentação sem autoridade normativa. | Docs |
| Regra de Negócio — RN | Enunciado normativo estável do comportamento exigido pelo negócio. | `RN`; Docs |
| Roadmap | Documento que organiza a ordem de implementação sem descobrir regras. | Docs; checkpoints |
| Síntese | Fonte independente de conferência que não substitui o checkpoint correspondente. | Agentes |
| Status documental | Situação atual de um documento, distinta de sua classificação. | Docs |

## 8. Distinções essenciais

| Não confundir | Distinção | Fonte |
| --- | --- | --- |
| Plano × versão | Identidade lógica versus marco homologado. | `RN-PLA-001` a `RN-PLA-003`; Modelo §7 |
| Fotografia vigente × versão de origem | Conjunto atual versus momento em que a Demanda entrou no planejamento. | `RN-PLA-004` |
| UO × Lotação × UASG | Responsabilidade pelo Plano, autoria organizacional e unidade compradora. | `RN-CAD-004`, `RN-CAD-005` |
| Publicação ativa × confirmação | Existência do recurso externo versus convergência da versão. | `RN-PLA-007`; `DI-005` |
| Estado desejado × observado × operacional | Planejamento homologado, fato externo e referência local cross-Plan. | `DI-002`, `DI-005` |
| Sequencial PCA × identidade do Plano | Referência externa versus UO e Ano de Execução. | `RN-PLA-001`; `DI-005` |
| Port × Adapter × Repository | Contrato, implementação concreta e especialização ligada a Aggregate. | Arquitetura §§5 e 8; `DI-007` |
| Suíte padrão × suíte opt-in | Execução ordinária, sem interação externa real implícita, versus execução deliberadamente ativada e protegida por configuração e preflight. | `DI-009`; Fluxo §11 |
| Contrato controlado × gate real | Evidência determinística em condições controladas versus comprovação com os adapters concretos no ambiente externo efetivo. | `DI-008`, `DI-009`; Roadmap Sprint 5.9 |
| Conexão emprestada × conexão própria | Recurso cujo ciclo de vida pertence à aplicação hospedeira versus recurso criado e administrado pelo próprio backend. | `DI-008` |
| Fachada × Controller × Composition Root | Entrada pública, adaptação da chamada e wiring. | Arquitetura §§7 e 8; `DI-010` |
| Auditoria × observabilidade | Fato de negócio confirmado versus diagnóstico técnico. | `DI-006` |
| Regra × decisão × fluxo | Norma de negócio, escolha arquitetural e colaboração dinâmica. | Docs; `RN`; `DI` |
