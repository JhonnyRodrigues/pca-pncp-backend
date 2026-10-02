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
| --- | --- |
| Comportamento normativo do negócio | [Regras de negócio](../autoritativos/regras_de_negocio.md) |
| Conceitos, identidades, relações e invariantes | [Modelo de domínio](../autoritativos/modelo_de_dominio.md) |
| Portas, integração, composição e operação | [Decisões de integração](../autoritativos/decisoes_de_integracao.md) |
| Colaboração dinâmica entre componentes | `docs/vigentes/autoritativos/fluxo_de_implementacao.md` |
| Ordem de construção | [Roadmap de implementação](../autoritativos/roadmap.md) |
| Classificação e precedência documental | [Índice documental](../../README.md) |

Quando uma explicação deste documento divergir de uma fonte autoritativa,
prevalece a fonte responsável pelo assunto e a divergência deve ser corrigida
aqui.

O documento descreve a **arquitetura vigente do sistema construído**. Código e
documentação devem permanecer coerentes com essas fronteiras; divergências não
alteram silenciosamente o desenho e precisam ser reconciliadas na fonte
responsável pelo assunto.

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
    Entrypoint Scriptcase
            │
            ▼
      Composition Root
            │
            ▼
      Presentation ───────► Application ───────► Domain
                                  ▲                 ▲
                                  │                 │
                                  └── Infrastructure┘
                                      implementa portas
```

O diagrama representa dependências de código, não a ordem temporal completa de
uma execução.

- A aplicação Blank consome somente o entrypoint Scriptcase do backend.
- Presentation depende dos contratos e casos de uso da Application.
- Application depende do modelo e das políticas do Domain.
- Infrastructure depende das abstrações internas que ela implementa.
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
| --- | --- | --- |
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

PSR-3 atende ao contrato de observabilidade técnica. Portanto, não será criada uma Port
proprietária de logging.

## 6. Domain versus fatos externos

Quatro conceitos precisam permanecer separados:

| Conceito | Camada ou fronteira conceitual |
| --- | --- |
| Identidade e fotografia do Plano | Domain |
| Política pura de decisão | Domain |
| Orquestração e unidade externa de publicação | Application |
| Estado operacional e estado externo observado | Fora do Aggregate, obtidos por portas da Application |

A política do Domain recebe a fotografia desejada e fatos tipados suficientes
para expressar sua decisão: `PUBLICAR`, `RETIFICAR`, `EXCLUIR_PCA` ou `NENHUMA_ACAO`. A
forma de observar, transportar ou persistir esses fatos fica fora da política
(`DI-002`, `DI-003`, `DI-005` e `DI-007`).

O `sequencialPca` é referência operacional externa. Ele não identifica o Plano,
não pertence à Demanda, não compõe a fotografia e não determina a ação de
negócio.

Application produz intenções e comandos tipados. Infrastructure converte esses
contratos para as representações exigidas pelos sistemas externos. Um adapter
não deve receber um Aggregate completo apenas para descobrir o que fazer.

## 7. Fachada, Composition Root, Scriptcase e configuração externa

### 7.1 Fronteira pública e composição

O backend é um produto independente consumido em processo pela aplicação
Blank:

```text
Scriptcase
    └── autoloader + PublicadorPcaScriptcase
                            │
                            ▼
                  Composition Root interno
                            │
                            ▼
                Fachada + grafo encapsulados
```

Conforme a `DI-010`:

- Scriptcase fornece somente conexão emprestada ($this->db), login, `SERVER_NAME`, ano e
  `X-Request-ID` ao entrypoint público;
- Scriptcase não fornece CNPJ, perfil, allowlist ou caminho e não conhece
  Controller, UseCase, Repository concreto, cliente PNCP ou política
  transacional;
- o Composition Root pertence ao backend;
- o entrypoint seleciona o perfil (arquivo `perfil.php`) através do arquivo de registro externo (`registro.php`) e o Composition Root recebe perfil já carregado e correlação validada;
- o wiring é manual por construtores, ou seja, as dependências das classes são montadas explicitamente pelo código, chamando os construtores com `new`, em vez de usar um container de injeção de dependência ou Service Locator;
- o Controller não usa macros e não faz wiring, ficando responsável apenas por receber a entrada já preparada, acionar o caso de uso e encaminhar a saída. Ele não conhece detalhes do Scriptcase nem sabe como as dependências são construídas;
- o Presenter produz o ViewModel (um objeto de saída já preparado para ser consumido pela camada externa). Sem renderizar HTML, JSON ou macros;
- nenhuma exceção atravessa a fachada: toda falha interna é capturada e convertida em uma resposta controlada antes de sair do backend. Exceções de banco, HTTP, configuração ou regra interna permanecem encapsuladas, evitando a exposição de detalhes internos e credenciais ao consumidor.

O registro externo é somente o índice literal `SERVER_NAME => caminho absoluto
do perfil`. Seu próprio caminho vem da variável de processo provisionada
`PCA_PNCP_PROFILE_REGISTRY_FILE`; não existe `.env`, default ou derivação de
caminho. O mesmo correlationID UUIDv4 recebido pelo servidor através da variável `X-Request-ID` atravessa logger, fachada,
Controller, Application e ViewModel sem normalização ou regeneração.

Configuração, ciclo de vida de recursos, distribuição e tratamento global de falhas permanecem detalhados na `DI-010`.

### 7.2 Do localizador ao grafo de objetos

Antes de montar os objetos que executarão a publicação, o backend precisa
descobrir e validar a configuração da implantação. O fluxo é:

```text
PCA_PNCP_PROFILE_REGISTRY_FILE
            │
            ▼
       registro.php
            │  procura o SERVER_NAME literal
            ▼
        perfil.php
            │  carrega o array
            ▼
CompositionRootPublicacaoPca
    ├── PerfilPublicacaoPca valida estrutura, tipos e valores
    └── wiring produz o grafo de objetos da execução
```

A variável de ambiente não guarda o perfil: ela informa somente o caminho
absoluto do registro. O `registro.php` funciona como uma **allowlist**, isto é,
uma lista explícita dos servidores autorizados, e associa cada `SERVER_NAME` ao
seu perfil. O perfil reúne os valores daquela implantação. Na entrada do
Composition Root, `PerfilPublicacaoPca` transforma o array em configuração
tipada e rejeita estrutura incompleta, chave adicional ou valor inválido antes
de qualquer I/O externo. Somente depois o Composition Root realiza o wiring das
implementações.

O nome **perfil** é semântico neste projeto porque o arquivo agrupa os valores
de uma implantação. Não é uma convenção universal: outros projetos usam nomes
como `config`, `settings` ou `environment`. Já **registro** descreve o papel de
índice que localiza um perfil sem repetir seu conteúdo.

Nesse contexto, **externo** significa fora do repositório, da release e do
webroot; o arquivo ainda pode estar no mesmo servidor. Registro e perfil não
são APIs da Blank: `PublicadorPcaScriptcase` os localiza internamente e a Blank
não escolhe schema, tabela nem caminho de arquivo.

### 7.3 Segredo vs configuração da implantação vs mapeamento físico

Essas três categorias podem coexistir no mesmo perfil atual, mas não significam
a mesma coisa:

| Categoria | Significado | Exemplos no projeto | Proteção necessária |
| --- | --- | --- | --- |
| Segredo | Valor cujo conhecimento concede acesso ou aumenta materialmente o risco de abuso. | Login e senha do PNCP. | Não versionar, não registrar em logs e limitar a leitura. |
| Configuração da implantação | Valor que varia conforme o ambiente no qual a mesma aplicação é instalada. | Ambiente, CNPJ autorizado, URL, timeouts, fuso, charset e caminho do CA bundle. | Validar, controlar alterações e expor apenas o necessário. |
| Mapeamento físico Oracle | Tradução entre os conceitos usados pelo código e os nomes ou literais do banco. | Schema, tabelas, colunas e valor que representa um registro ativo. | Validar como identificadores permitidos; manter fora de Domain e Application. |

Nem toda configuração é segredo. O caminho e o conteúdo público de um CA
bundle, por exemplo, não concedem acesso ao PNCP; sua integridade ainda importa
porque ele participa da validação TLS. Da mesma forma, nomes de tabelas e
colunas costumam ser detalhes internos sensíveis à operação, mas não são
credenciais por definição.

O formato PHP externo foi escolhido pela `DI-010` para este projeto. Ele permite
retornar arrays, usar `__DIR__` para caminhos vizinhos e ser carregado sem nova
dependência. Em contrapartida, é um arquivo executável: deve ser gravado apenas
por administradores autorizados, lido pelo usuário do PHP e validado antes de
ser transformado em objetos.

Colocar os mapeamentos em classes da Infrastructure protege as camadas internas
porque apenas o adapter Oracle conhece os nomes físicos. Isso **não exige**, por
si só, que esses nomes venham de um arquivo externo: um mapeamento estável
poderia continuar na Infrastructure e ser versionado. A localização externa é
uma decisão adicional sobre implantação e segurança, não uma consequência
automática da Clean Architecture.

### 7.4 Inversão de dependência no repositório

**Inversão de dependência** é o princípio segundo o qual regras de alto nível
dependem de abstrações estáveis, enquanto detalhes externos dependem desses
contratos internos. Em linguagem simples: o núcleo declara de que capacidade
precisa; Oracle, HTTP ou outro mecanismo se adapta a essa necessidade.

Neste repositório, o fluxo de chamadas em tempo de execução vai da Application
para o Repository, mas a dependência do código aponta para dentro:

```text
Application ── chama ──► PlanoRepository ◄── implementa ── OraclePlanoRepository
                         contrato do Domain                  adapter da Infrastructure
```

- [`PlanoRepository`](../../../src/Domain/Ports/PlanoRepository.php) declara
  operações com a linguagem do negócio, como `listarIdentidades()` e
  `reconstruirFotografiaVigente()`, sem SQL ou ADOdb;
- [`PreparadorExecucaoPublicacaoPca`](../../../src/Application/UseCases/PublicarPca/PreparadorExecucaoPublicacaoPca.php)
  usa esse contrato para coordenar o caso de uso, sem escolher Oracle;
- [`OraclePlanoRepository`](../../../src/Infrastructure/Oracle/Plano/OraclePlanoRepository.php)
  depende de `PlanoRepository`, implementa suas operações e traduz a chamada
  para SQL sobre a conexão ADOdb;
- [`MapeamentoPlanosOracle`](../../../src/Infrastructure/Oracle/Plano/MapeamentoPlanosOracle.php)
  valida e fornece os identificadores físicos usados somente pelo adapter.

#### Inversão de dependência não é sinônimo de injeção de dependências

A primeira define para onde as dependências de código devem apontar; a segunda é
o mecanismo usado para entregar uma implementação concreta a quem depende do
contrato. O Composition Root injeta `OraclePlanoRepository` onde se espera um
`PlanoRepository`.

### 7.5 Exemplos corretos e contraexemplos

O desenho correto mantém linguagem de negócio nas camadas internas e detalhes
físicos no adapter:

```php
// Application: depende da capacidade, não de Oracle.
$identidades = $this->planoRepository->listarIdentidades($anoExecucao);

// Infrastructure: traduz a capacidade para o mecanismo concreto.
final class OraclePlanoRepository implements PlanoRepository
{
    // SQL, ADOdb e mapeamentos Oracle permanecem aqui.
}
```

Os trechos seguintes são **contraexemplos hipotéticos e incorretos**; não devem
ser copiados:

```php
// INCORRETO: Application passou a conhecer ADOdb, Oracle e schema físico.
$linhas = $conexaoAdodb->Execute(
    'SELECT * FROM SCHEMA_FISICO.PAC_PLANOS WHERE ANO_EXECUCAO = ?',
    [$ano]
);

// INCORRETO: a entidade do Domain foi modelada como uma linha de PAC_PLANOS.
final class Plano
{
    public int $idPacPlano;
    public string $colunaSituacaoOracle;
}

// INCORRETO: a Blank da camada de apresentação passou a controlar detalhes internos e de implantação.
$publicador->executar(
    schema: $_POST['schema'],
    tabela: $_POST['tabela'],
    caminhoPerfil: $_POST['perfil']
);
```

- No primeiro caso, trocar o mecanismo de persistência afetaria o caso de uso.
- No segundo, uma estrutura física passaria a definir o modelo de negócio.
- No terceiro, entrada HTTP controlaria configuração privilegiada e romperia a fronteira pública mínima estabelecida pela `DI-010`.

### 7.6 Origem das decisões e avaliação do desenho

A origem rastreável do desenho atual é:

- a `DI-008` isolou conexão, transação, SQL, binds e traduções Oracle na
  Infrastructure;
- os [planos da Sprint 5.2](../../../.agents/sprints/planos/plano_sprint_5.2.md)
  e da [Sprint 5.3](../../../.agents/sprints/planos/plano_sprint_5.3.md)
  tornaram os mapeamentos físicos obrigatórios, validados e injetados nos
  adapters, sem defaults silenciosos;
- a `DI-010` e o [plano da Sprint 8.1](../../../.agents/sprints/planos/plano_sprint_8.1.md)
  definiram o registro allowlisted, os perfis externos e sua materialização
  tipada na entrada do Composition Root, antes do wiring.

Não foi encontrada nessas fontes uma decisão explícita exigindo nomes físicos
diferentes entre DEV, validaSGI e produção. Portanto, a justificativa correta
para os mapeamentos configuráveis não é afirmar que esses três bancos diferem.
O benefício comprovado hoje é tornar a dependência física explícita, validada e
isolada; a variação entre instalações é uma capacidade oferecida pelo desenho.

Não existe uma única forma universal de configurar aplicações. As alternativas
mais comuns têm custos diferentes:

| Forma | Vantagem | Limite principal |
| --- | --- | --- |
| Arquivo PHP externo, desenho atual | Agrupa a configuração tipada do perfil e integra-se diretamente ao runtime PHP. | Mistura categorias diferentes no mesmo arquivo e exige proteger código executável. |
| Variáveis de ambiente | Separam valores da release e funcionam bem para poucos valores que variam por implantação. | Muitos mapeamentos ficam difíceis de ler; valores podem vazar em diagnósticos como `phpinfo()`. |
| Configuração estável versionada + segredos externos | Permite revisar no Git o que não é sensível e reduz o conteúdo secreto provisionado. | Exige regras claras de precedência e migração do formato atual. |
| Secret manager | Centraliza controle de acesso, auditoria, rotação e, conforme a ferramenta, credenciais temporárias. | Acrescenta serviço, autenticação, disponibilidade e operação próprias. |

Nenhuma opção é sempre a mais simples, escalável e segura. O arquivo PHP é a
opção mais direta para o desenho atual; variáveis de ambiente são comuns e
simples para poucos valores; separar configuração estável de segredos facilita
revisão e manutenção à medida que o projeto cresce; e um secret manager oferece
mais recursos de governança dos segredos ao custo de maior complexidade
operacional. A segurança efetiva também depende de permissões, auditoria,
processo de atualização e proteção contra exposição acidental.

Essa comparação segue práticas amplamente documentadas: o
[The Twelve-Factor App](https://12factor.net/config) separa do código o que varia
entre implantações, mas distingue configuração interna estável; a
[documentação do Symfony](https://symfony.com/doc/current/configuration.html)
mostra a convivência de arquivos versionados, parâmetros e variáveis de
ambiente, inclusive seus riscos de exposição; e a
[documentação do Vault](https://developer.hashicorp.com/vault/docs/about-vault/how-vault-works)
exemplifica um sistema dedicado a controlar, auditar e rotacionar segredos.
Essas referências contextualizam práticas de mercado, mas não substituem a
autoridade das `DI-008` e `DI-010` neste projeto.

Uma possibilidade futura seria manter mapeamentos estáveis do adapter SEMAE
versionados, deixar no perfil somente a configuração que varia por implantação
e obter segredos de uma fonte externa dedicada. Isso é apenas material de
avaliação: não está aprovado, não constitui pendência do roadmap e não autoriza
refatoração. Até nova decisão, `registro.php`, perfil externo e mapeamentos no
perfil continuam obrigatórios.

## 8. Convenções arquiteturais

| Conceito | Significado arquitetural |
| --- | --- |
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
| Entrypoint Scriptcase | Único ponto público consumido pela aplicação hospedeira |
| Fachada | Fronteira interna da Presentation que converte a execução em ViewModel seguro |
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
| --- | --- |
| Domain | Invariantes, transições e políticas puras |
| Application | Casos de uso, coordenação de resultados e consumo das Ports com doubles |
| Infrastructure | Contratos e integração concreta depois da liberação dos gates correspondentes |
| Presentation | Adaptação de entrada, fachada e ViewModel sem vazamento de detalhes internos |

Quando um teste evidenciar regra de negócio, deve referenciar `@regra RN-*`.
Testes técnicos não precisam forçar associação artificial com uma RN. Não
existe relação obrigatória de um para um entre regra, classe e teste, conforme
a política do catálogo.

## 10. Considerações finais

### 10.1 Exclusões deliberadas e referências

Este documento não define:

- fluxo operacional passo a passo;
- cronograma, fases ou sprints;
- formatos físicos de entrada e saída;
- endpoints ou serialização;
- estruturas de banco ou comandos de persistência;
- mecanismos concretos de conexão, transporte ou lock;
- valores e procedimentos concretos de configuração dos ambientes;
- detalhes de empacotamento.

Esses assuntos pertencem ao fluxo, ao roadmap ou às decisões de
integração.

### 10.2 Matriz de referências

| Assunto resumido aqui | Autoridade |
| --- | --- |
| Identidade, fotografia e confirmação do Plano | `RN-PLA-001` a `RN-PLA-007` e [modelo de domínio](../autoritativos/modelo_de_dominio.md) |
| Decisões de publicação | `RN-PUB-001` a `RN-PUB-011` e modelo de domínio |
| Auditoria | `RN-AUD-001` a `RN-AUD-005`, `DI-006` e modelo de domínio |
| Agrupamento e unidade externa | `DI-003` |
| Estado operacional externo | `DI-005` |
| Ownership das portas | `DI-006` e `DI-007` |
| Mecanismos Oracle e HTTP | `DI-008` e `DI-009` |
| Fachada, Composition Root e Scriptcase | `DI-010` |

Este resumo deve ser atualizado quando uma fonte autoritativa alterar uma fronteira arquitetural.
