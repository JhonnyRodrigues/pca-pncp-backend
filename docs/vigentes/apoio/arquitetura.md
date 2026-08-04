# Arquitetura do Backend PCA/PNCP

**Classificação documental:** Apoio  
**Status:** Vigente  
**Autoridade/Escopo:** síntese didática dos limites arquiteturais, das responsabilidades e da direção das dependências

## 1. Finalidade, autoridade e limites

Este documento responde à pergunta:

> Como as responsabilidades são separadas e as dependências são protegidas?

Ele apresenta a arquitetura estática do backend e serve como ponte para as
fontes autoritativas. Por ser um documento de apoio, não cria regras de
negócio, não redefine o modelo conceitual e não aprova decisões de integração.

A autoridade é distribuída da seguinte forma:

| Assunto | Fonte autoritativa |
|---|---|
| Comportamento normativo do negócio | [Regras de negócio](../autoritativos/regras_de_negocio.md) |
| Conceitos, identidades, relações e invariantes | [Modelo de domínio](../autoritativos/modelo_de_dominio.md) |
| Portas, integração, composição e operação | [Decisões de integração](../autoritativos/decisoes_de_integracao.md) |
| Colaboração dinâmica entre componentes | `docs/vigentes/autoritativos/fluxo_de_implementacao.md` |
| Ordem de construção | [Roadmap de implementação](../autoritativos/roadmap.md) |
| Classificação e precedência documental | [Índice documental](../../README.md) |

Quando uma explicação deste documento divergir de uma fonte autoritativa,
prevalece a fonte responsável pelo assunto e a divergência deve ser corrigida
aqui.

O documento descreve a **arquitetura-alvo aprovada**. O código existente pode
conter estruturas anteriores e será reconciliado posteriormente por
refatoração com TDD. A implementação atual não redefine o alvo documental.

## 2. Princípios arquiteturais

O projeto adota:

- Clean Architecture;
- Domain-Driven Design;
- SOLID;
- injeção de dependências;
- PSR-4, PSR-12 e PSR-3;
- Test-Driven Development.

As escolhas devem favorecer:

- simplicidade;
- alta coesão;
- baixo acoplamento;
- linguagem ubíqua;
- legibilidade;
- testabilidade;
- independência entre regras de negócio e mecanismos externos.

A regra estrutural central é:

> Dependências de código apontam para as camadas internas e para abstrações
> estáveis, nunca dos conceitos de negócio para mecanismos externos.

## 3. Arquitetura-alvo e direção das dependências

```text
Aplicação Blank (Scriptcase)
            │
            │ invoca
            ▼
      Fachada pública
            │
            ▼
      Presentation ───────► Application ───────► Domain
                                  ▲                 ▲
                                  │                 │
                                  └── Infrastructure┘
                                      implementa portas

Composition Root do backend
    └── constrói o grafo e injeta implementações concretas
```

O diagrama representa dependências de código, não a ordem temporal completa de
uma execução.

- A aplicação Blank consome somente a fachada pública do backend.
- Presentation depende dos contratos e casos de uso da Application.
- Application depende do modelo e das políticas do Domain.
- Infrastructure depende das abstrações internas que implementa.
- Domain não depende de Presentation, Infrastructure ou do ambiente
  hospedeiro.
- O Composition Root pertence ao backend e é o único ponto autorizado a
  conhecer o conjunto de implementações concretas necessário ao wiring
  (`DI-010`).

A sequência dinâmica de validação, observação, decisão, escrita, confirmação e
auditoria pertence ao fluxo de implementação, não a este documento.

## 4. Responsabilidade das camadas

### 4.1 Domain

O Domain representa o negócio de planejamento do PCA. Ele contém:

- Aggregate `Plano`;
- entidades e Objetos de Valor;
- invariantes;
- fatos imutáveis de negócio;
- exceções de domínio;
- políticas puras;
- o contrato de Repository ligado ao Aggregate.

O Domain decide intenções de negócio a partir de informações tipadas. Ele não
conhece mecanismo PNCP, Oracle, transporte, serialização, Scriptcase,
configuração de implantação ou observabilidade técnica.

### 4.2 Application

Application coordena os casos de uso. Ela é responsável por:

- validar a solicitação em sua fronteira;
- obter contexto e estratégia por contratos abstratos;
- agrupar Planos em unidades externas sem fundir Aggregates;
- obter fatos internos, operacionais e observados;
- fornecer esses fatos às políticas do Domain;
- ordenar intenções, comandos, confirmações e auditoria;
- produzir resultados tipados por unidade e para a execução global.

Application define as portas necessárias à orquestração, mas não conhece
representações físicas de persistência ou transporte (`DI-003` e `DI-007`).

### 4.3 Infrastructure

Infrastructure contém adapters concretos para capacidades externas, como:

- persistência e reconstrução;
- comunicação com o PNCP;
- configuração;
- transação e exclusividade;
- contexto fornecido pelo ambiente hospedeiro;
- observabilidade.

Um adapter traduz contratos internos para representações externas e devolve
fatos tipados. Ele não escolhe ações de negócio, não altera invariantes e não
decide convergência.

As escolhas concretas para Oracle e transporte pertencem às `DI-008` e
`DI-009`, incluindo seus gates condicionantes.

### 4.4 Presentation

Presentation adapta a entrada pública e apresenta o resultado:

- a fachada protege a fronteira do backend;
- o Controller transforma a entrada tipada em Request e aciona o UseCase;
- o Presenter converte a Response em ViewModel estruturado;
- a aplicação Blank renderiza o ViewModel.

Presentation não contém regras de negócio, não acessa adapters diretamente e
não realiza wiring.

## 5. Ownership das portas

Uma Port é um contrato interno definido pela camada que necessita da
capacidade. Sua implementação concreta é um Adapter.

| Capacidade | Ownership do contrato | Implementação |
|---|---|---|
| Reconstruir identidades e fotografias de Plano; registrar confirmação orientada à intenção | Domain — `PlanoRepository` | Infrastructure |
| Autenticar no PNCP | Application | Infrastructure |
| Observar PCA conhecido e candidatos de diagnóstico | Application | Infrastructure |
| Publicar PCA, inserir itens, excluir itens e excluir PCA | Application | Infrastructure |
| Obter estratégia corrente e estratégia adotada | Application | Infrastructure |
| Coordenar unidade de trabalho e exclusividade | Application | Infrastructure |
| Resolver usuário, Representante e Lotação | Application | Infrastructure |
| Persistir fato de auditoria | Application | Infrastructure |
| Obter e persistir estado operacional cross-Plan | Application | Infrastructure |

O `PlanoRepository` permanece no Domain porque seu contrato expressa
reconstrução e confirmação do Aggregate. Metadados cross-Plan e coordenação
operacional não pertencem a esse Repository (`DI-005`, `DI-006` e `DI-007`).

PSR-3 atende ao contrato de observabilidade técnica. Não será criada uma Port
proprietária de logging.

## 6. Domain versus fatos externos

Quatro conceitos precisam permanecer separados:

| Conceito | Camada ou fronteira conceitual |
|---|---|
| Identidade e fotografia do Plano | Domain |
| Política pura de decisão | Domain |
| Orquestração e unidade externa de publicação | Application |
| Estado operacional e estado externo observado | Fora do Aggregate, obtidos por portas da Application |

A política do Domain recebe a fotografia desejada e fatos tipados suficientes
para expressar `PUBLICAR`, `RETIFICAR`, `EXCLUIR_PCA` ou `NENHUMA_ACAO`. A
forma de observar, transportar ou persistir esses fatos fica fora da política
(`DI-002`, `DI-003`, `DI-005` e `DI-007`).

O `sequencialPca` é referência operacional externa. Ele não identifica o Plano,
não pertence à Demanda, não compõe a fotografia e não determina a ação de
negócio.

Application produz intenções e comandos tipados. Infrastructure converte esses
contratos para as representações exigidas pelos sistemas externos. Um adapter
não deve receber um Aggregate completo apenas para descobrir o que fazer.

## 7. Fachada, Composition Root e Scriptcase

O backend é um produto independente consumido em processo pela aplicação
Blank:

```text
Scriptcase
    └── autoloader + fachada pública do backend
                            │
                            ▼
                  grafo interno encapsulado
```

Conforme a `DI-010`:

- Scriptcase não conhece Controller, UseCase, Repository concreto, cliente
  PNCP ou política transacional;
- o Composition Root pertence ao backend;
- o wiring é manual por construtores;
- não há container nem Service Locator;
- o Controller não usa macros e não faz wiring;
- o Presenter produz ViewModel, sem renderizar HTML, JSON ou macros;
- nenhuma exceção atravessa a fachada;
- detalhes internos e credenciais não são expostos ao consumidor.

Configuração, ciclo de vida de recursos, distribuição e tratamento global de
falhas permanecem detalhados na `DI-010`.

## 8. Convenções arquiteturais

| Conceito | Significado arquitetural |
|---|---|
| Entity | Conceito de negócio reconhecido por identidade contínua, como `Demanda` |
| Value Object | Valor imutável com igualdade por conteúdo, como `Cnpj`, `AnoExecucao` ou `Uasg` |
| Aggregate | Fronteira de consistência protegida por uma raiz, como `Plano` |
| Fato imutável | Registro conceitual de algo concluído, sem ganhar identidade de negócio por possuir chave técnica |
| Política de Domain | Decisão pura que relaciona conceitos e fatos sem acessar mecanismos externos |
| UseCase | Orquestra uma intenção da aplicação usando Domain e Ports |
| Port | Contrato interno pertencente à camada consumidora da capacidade |
| Adapter | Implementação concreta de uma Port para um mecanismo externo |
| Repository | Port especializada na reconstrução e persistência de um Aggregate |
| Request/Response | Contratos de entrada e saída do caso de uso |
| Controller | Adapta a entrada pública para o caso de uso |
| Presenter | Transforma a Response em ViewModel estruturado |
| Fachada | Único ponto público consumido pela aplicação hospedeira |
| Composition Root | Ponto do backend que cria o grafo e injeta dependências concretas |

Repository não é sinônimo de acesso físico a banco. A interface pertence à
camada que define sua semântica; o adapter de persistência pertence à
Infrastructure.

Objetos de transporte não devem carregar regras do Domain. Da mesma forma,
conceitos externos não são promovidos a Value Objects do Aggregate apenas
porque precisam de validação técnica.

## 9. Estratégia de testes

O projeto usa TDD:

```text
Red → Green → Refactor
```

Os testes demonstram comportamentos e contratos relevantes, não a existência
isolada de cada classe.

| Fronteira | Evidência esperada |
|---|---|
| Domain | Invariantes, transições e políticas puras |
| Application | Casos de uso, coordenação de resultados e consumo das Ports com doubles |
| Infrastructure | Contratos e integração concreta depois da liberação dos gates correspondentes |
| Presentation | Adaptação de entrada, fachada e ViewModel sem vazamento de detalhes internos |

Quando um teste evidenciar regra de negócio, deve referenciar `@regra RN-*`.
Testes técnicos não precisam forçar associação artificial com uma RN. Não
existe relação obrigatória de um para um entre regra, classe e teste, conforme
a política do catálogo.

## 10. Arquitetura-alvo versus código atual

Este documento não é inventário das classes existentes. Ele descreve a
separação aprovada para orientar:

1. o fluxo de implementação;
2. a refatoração arquitetural com TDD;
3. a conclusão da Application com mocks;
4. a implementação posterior da Infrastructure.

Ao encontrar divergência:

- não promover a estrutura atual a regra arquitetural;
- não alterar uma fonte autoritativa para acomodar legado;
- registrar o conflito;
- corrigir o código somente na etapa autorizada.

## 11. Exclusões deliberadas e referências

Este documento não define:

- fluxo operacional passo a passo;
- cronograma, fases ou sprints;
- formatos físicos de entrada e saída;
- endpoints ou serialização;
- estruturas de banco ou comandos de persistência;
- mecanismos concretos de conexão, transporte ou lock;
- configuração de ambientes;
- detalhes de empacotamento.

Esses assuntos pertencem ao fluxo, ao roadmap ou às decisões de
integração.

### 11.1 Matriz de referências

| Assunto resumido aqui | Autoridade |
|---|---|
| Identidade, fotografia e confirmação do Plano | `RN-PLA-001` a `RN-PLA-007` e [modelo de domínio](../autoritativos/modelo_de_dominio.md) |
| Decisões de publicação | `RN-PUB-001` a `RN-PUB-011` e modelo de domínio |
| Auditoria | `RN-AUD-001` a `RN-AUD-005`, `DI-006` e modelo de domínio |
| Agrupamento e unidade externa | `DI-003` |
| Estado operacional externo | `DI-005` |
| Ownership das portas | `DI-006` e `DI-007` |
| Mecanismos Oracle e HTTP | `DI-008` e `DI-009` |
| Fachada, Composition Root e Scriptcase | `DI-010` |

Este resumo deve ser atualizado quando uma fonte autoritativa alterar uma
fronteira arquitetural.
