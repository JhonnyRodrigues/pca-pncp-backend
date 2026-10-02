# Modelo de Domínio do PCA

**Classificação documental:** Autoritativo  
**Status:** Vigente  
**Autoridade/Escopo:** conceitos, identidades, relações e invariantes do modelo-alvo  
**Versão documental:** V2  
**Substitui:** `docs/historico/modelo_de_dominio_v1_legado.md`

Este documento responde à pergunta: **o que existe no negócio e como esses
conceitos se relacionam?**

## 1. Finalidade, autoridade e escopo

O objetivo deste documento é explicar o modelo conceitual usado pelo backend de
publicação do Plano de Contratações Anual (PCA). Ele descreve a linguagem
ubíqua, as identidades, as relações, as invariantes e as responsabilidades de
domínio relevantes para o planejamento, a publicação e a auditoria.

A autoridade documental é dividida por assunto:

- `docs/vigentes/autoritativos/regras_de_negocio.md` é a fonte autoritativa da
  redação normativa das regras de negócio;
- este documento explica como os conceitos do domínio representam essas
  regras;
- `docs/vigentes/autoritativos/decisoes_de_integracao.md` é a fonte
  autoritativa das decisões de integração e dos fatos operacionais externos;
- `docs/vigentes/autoritativos/fluxo_de_implementacao.md` descreve a
  colaboração dinâmica entre camadas e portas;
- [`docs/vigentes/autoritativos/roadmap.md`](roadmap.md) organiza a ordem de
  construção.

Quando uma explicação deste modelo resumir uma regra, o identificador `RN-*`
apontará para sua redação completa. Quando uma fronteira depender de uma escolha
de integração, o identificador `DI-*` apontará para a decisão correspondente.

O documento descreve o **modelo-alvo aprovado**. O código atual pode ainda
representar premissas substituídas e será refatorado posteriormente com TDD.
Não se devem inferir daqui assinaturas de classes, estruturas de persistência,
contratos de transporte ou detalhes de composição.

## 2. Sequência didática e linguagem ubíqua

### 2.1 Como ler o modelo

Para fins de estudo, a leitura segue uma ordem que parte do significado e só
depois chega à estrutura:

1. compreender o vocabulário do negócio;
2. reconhecer os contextos e suas fronteiras;
3. identificar objetos definidos por valor e conceitos com identidade;
4. delimitar o Aggregate que protege a fotografia do Plano;
5. separar o Plano dos fatos externos usados para decidir a publicação;
6. distinguir confirmação, estado operacional e auditoria.

Essa ordem ajuda a evitar que identificadores técnicos ou limitações de
integração sejam confundidos com conceitos constitutivos do negócio.

### 2.2 Linguagem ubíqua

| Termo | Significado no modelo |
| --- | --- |
| **Plano** | Planejamento homologado de uma Unidade Orçamentária para um Ano de Execução, representado por sua fotografia vigente completa |
| **Demanda** | Necessidade de material ou serviço com identidade própria e ciclo de vida controlado |
| **DFD-E** | Demanda extraordinária submetida a fluxo próprio e homologação individual |
| **Homologação** | Aprovação formal que origina o Plano ou produz novo marco de versão |
| **Versão** | Marcador incremental da evolução homologada de um mesmo Plano lógico |
| **Fotografia vigente** | Conjunto completo das Demandas ativas da mesma UO e exercício, independentemente da versão em que se originaram |
| **Publicação inicial** | Criação da representação externa do PCA quando ainda não existe publicação ativa conhecida |
| **Retificação** | Sincronização de inclusões e exclusões homologadas em uma publicação externa ativa |
| **Exclusão integral** | Encerramento da publicação externa quando sua unidade de publicação não possui Demandas vigentes |
| **Confirmação de sincronização** | Registro, por versão homologada, de que a convergência foi comprovada |
| **Estado operacional da publicação** | Situação conhecida da representação externa: `NAO_PUBLICADO`, `PUBLICADO` ou `EXCLUIDO` |
| **Estado observado** | Fato obtido da publicação externa e usado para verificar a convergência |
| **Unidade externa de publicação** | Escopo que possui uma publicação e um resultado: uma UO no modo individual ou o conjunto do exercício no modo consolidado |
| **Procedimento** | Classificação estável de uma ação de negócio auditável |
| **Histórico de procedimento** | Fato imutável que registra uma operação concluída e confirmada |
| **Representante** | Pessoa responsável pela ação de negócio |
| **Lotação** | Unidade organizacional relacionada à autoria, distinta de Unidade Orçamentária |
| **Unidade Orçamentária (UO)** | Unidade responsável pelo Plano no sistema de planejamento |
| **UASG** | Identificação da unidade compradora usada na publicação externa |

Os significados normativos desses termos estão nas regras `RN-FLX-001` a
`RN-FLX-004`, `RN-PLA-001` a `RN-PLA-007`, `RN-PUB-001` a `RN-PUB-011` e
`RN-AUD-001` a `RN-AUD-005`.

## 3. Contextos e fronteiras do domínio

O modelo é organizado em três recortes conceituais. Eles não pressupõem
processos separados nem uma forma específica de implementação.

### 3.1 Planejamento

Reúne:

- Demandas ordinárias e extraordinárias;
- avaliação, consolidação e homologação;
- Unidade Orçamentária;
- Plano, versões e fotografia vigente.

O planejamento ordinário e o extraordinário possuem fluxos de aprovação
distintos (`RN-FLX-001` e `RN-FLX-002`). Consolidação, desconsolidação e revisão
controlam quando uma Demanda pode ser modificada antes da homologação
(`RN-FLX-003` e `RN-FLX-004`).

### 3.2 Publicação e sincronização

Reúne:

- a política que compara o estado desejado com fatos externos tipados;
- as decisões `PUBLICAR`, `RETIFICAR`, `EXCLUIR_PCA` e `NENHUMA_ACAO`;
- a distinção entre publicação ativa e confirmação da versão;
- as modalidades individual e consolidada.

O Domain decide a intenção de negócio, mas não conhece mecanismos externos.
Agrupamento e unidade externa são fornecidos à política sob coordenação da
Application (`DI-003` e `DI-007`).

### 3.3 Auditoria

Reúne:

- o catálogo conceitual de Procedimentos;
- o Representante e sua Lotação;
- o fato imutável de Histórico de Procedimento.

Auditoria de negócio registra apenas operações concluídas, observadas e
confirmadas. Tentativas, falhas e ausência de transição não são fatos
históricos (`RN-AUD-001` e `DI-006`).

## 4. Visão conceitual do modelo

### 4.1 Fronteira do Aggregate

```text
Plano [Aggregate Root]
│
├── identidade lógica
│   ├── UnidadeOrcamentaria
│   └── AnoExecucao
│
├── marco homologado vigente
│   ├── SequencialVersao
│   └── dataConfirmacaoSincronizacao
│
└── fotografia vigente
    └── Demanda [0..*]
```

O diagrama contém apenas os conceitos necessários para representar o Plano
homologado vigente. Estado operacional externo, UASG de publicação e
sequencial externo não integram essa fronteira.

### 4.2 Conceitos externos à fronteira do Aggregate

```text
Unidade externa de publicação
│
├── participa: um ou mais Planos
├── possui: estado operacional da publicação
└── é confrontada com: estado externo observado
                         │
                         ▼
                política pura do Domain
                         │
                         ▼
     PUBLICAR | RETIFICAR | EXCLUIR_PCA | NENHUMA_ACAO
```

No modo individual, a unidade externa corresponde a um Plano/UO. No modo
consolidado, ela reúne Planos distintos sem fundi-los em um novo Aggregate
(`RN-PUB-009`, `RN-PUB-010` e `DI-003`).

## 5. Objetos de Valor e referências imutáveis do negócio

### 5.1 `Cnpj`

`Cnpj` é um Value Object que identifica o órgão responsável pela publicação.
Seu valor possui 14 posições no padrão alfanumérico vigente, normalização e
dígitos verificadores próprios (`RN-CAD-001`).

O CNPJ não representa uma UO, uma UASG ou uma publicação específica.

### 5.2 `AnoExecucao`

`AnoExecucao` é um Value Object que delimita o exercício ao qual pertencem o
Plano e suas Demandas. O intervalo aceito é o ano corrente ou o imediatamente
seguinte (`RN-CAD-002`).

Ele participa da identidade do Plano em conjunto com a UO, mas não identifica o
Plano isoladamente (`RN-PLA-001`).

### 5.3 `Uasg`

`Uasg` é um Value Object que representa a identificação da unidade compradora
associada a uma UO ou à unidade consolidadora (`RN-CAD-005` e `RN-PUB-011`).

Seu preenchimento é obrigatório para a UO participante (`RN-CAD-006`). O
formato lexical completo permanece uma lacuna conhecida e não será inferido
neste modelo.

### 5.4 `Lotacao`

`Lotacao` é uma referência imutável composta por identificador positivo e
descrição. Ela representa a unidade organizacional relacionada à autoria e não
deve ser confundida com Unidade Orçamentária (`RN-CAD-004`).

### 5.5 `Representante`

`Representante` é uma referência imutável composta por identificador e nome. No
contexto auditável, representa a pessoa responsável pela ação de negócio
(`RN-CAD-003`).

O modelo não atribui a `Representante` ou `Lotacao` a responsabilidade por
resolver o usuário que iniciou a execução. Essa resolução pertence à fronteira
da Application (`RN-AUD-004`, `DI-006` e `DI-007`).

### 5.6 O que não pertence a esta categoria

O sequencial da publicação externa não é Objeto de Valor constitutivo do Plano.
Se futuramente houver um tipo `SequencialPcaPncp`, ele servirá apenas para
representar com segurança uma referência externa dentro do estado operacional.
Sua forma concreta e sua camada não são antecipadas por este documento
(`DI-005`, `DI-007` e `DI-008`).

## 6. Entidades

### 6.1 `UnidadeOrcamentaria`

`UnidadeOrcamentaria` é uma Entity: sua continuidade no negócio não se reduz ao
texto de sua descrição ou à UASG corrente.

Ela reúne conceitualmente:

- Lotação;
- descrição;
- UASG.

Sua descrição é normalizada e possui as restrições definidas em
`RN-CAD-005`. Para participar da publicação, a UO precisa ser resolvida por um
único cadastro ativo com UASG preenchida (`RN-CAD-006`).

A exata representação de seu identificador não é deduzida de chaves técnicas.
O modelo exige apenas que a mesma UO seja reconhecida de forma consistente na
identidade do Plano e no pertencimento das Demandas.

### 6.2 `Demanda`

`Demanda` é uma Entity que representa uma necessidade de contratação. Sua
identidade é o `ID_DEMANDA`, globalmente único e estável durante todo o ciclo de
vida (`RN-DEM-002`).

Ela reúne conceitualmente:

- Unidade Orçamentária e Ano de Execução;
- dados que caracterizam a necessidade;
- quantidade;
- valor total e orçamento do exercício preservados como `DecimalExato`, sem
  conversão para ponto flutuante;
- categoria do item PCA pertencente ao domínio vigente de `1` a `8`;
- catálogo e classificação Material ou Serviço como conceitos independentes;
- origem Normal ou Extraordinária;
- data desejada;
- situação no planejamento.

Quantidade, categoria, classificação e origem obedecem a `RN-DEM-001`. Os demais atributos não
ganham neste documento regras que não estejam registradas no catálogo.

`DecimalExato` é a representação interna mínima exigida pelas DI-008 e DI-009:
ele apenas valida e preserva a forma decimal recebida. Divisão para valor
unitário, escala de payload, `HALF_UP` e emissão JSON não pertencem ao Domain e
continuam reservados à borda PNCP da Sprint 5.6.

#### Ciclo após homologação

Depois de homologada, a Demanda preserva seus dados de negócio, ressalvado o
ajuste de data desejada pelo Núcleo de Compras (`RN-DEM-003` e `RN-DEM-004`).
Outra mudança requer cancelamento justificado, uma nova Demanda e novo fluxo de
aprovação (`RN-DEM-005`).

O cancelamento efetivo de uma Demanda homologada e a homologação de uma DFD-E
produzem novos marcos de versão do Plano (`RN-DEM-006` e `RN-DEM-007`). O
cancelamento da última Demanda ativa é válido e resulta em fotografia vazia
(`RN-DEM-008`).

#### Participação na fotografia

Demandas Homologadas, Compradas ou Publicadas integram a fotografia vigente.
Demandas Planejadas ou Canceladas não a integram (`RN-DEM-009`).

A Demanda não contém:

- identidade técnica de uma versão do Plano;
- vínculo conceitual baseado em chave física;
- sequencial da publicação externa.

### 6.3 Justificativa da exclusão integral

A justificativa enviada na exclusão integral não representa uma Demanda nem um
fato histórico do Domain. Ela descreve a ação institucional decorrente da
fotografia vigente vazia e é fornecida pela Application como texto fixo,
conforme `RN-PUB-003`.

Demandas Canceladas continuam fora da fotografia vigente (`RN-DEM-009`), e o
modelo não precisa reconstruí-las para decidir ou justificar `EXCLUIR_PCA`.

## 7. Aggregate Root `Plano`

### 7.1 Significado

`Plano` representa o estado homologado vigente completo de uma UO em um Ano de
Execução. Ele é o Aggregate Root usado para proteger a coerência dessa
fotografia.

### 7.2 Identidade lógica

A identidade do Plano é:

```text
UnidadeOrcamentaria + AnoExecucao
```

Versão, identificadores técnicos, datas e sequencial externo não participam
dessa identidade (`RN-PLA-001`).

Assim, versões sucessivas não são Planos de negócio diferentes. Elas registram
a evolução homologada de um mesmo Plano lógico.

### 7.3 Origem e versão vigente

O Plano nasce da homologação. Cada novo marco homologado que altera a fotografia
gera uma versão positiva e incremental para a mesma identidade (`RN-PLA-002`).

A maior versão da combinação UO + Ano de Execução fornece os metadados vigentes.
A confirmação de sincronização não participa da escolha dessa versão
(`RN-PLA-003`).

### 7.4 Fotografia completa

A fotografia do Plano reúne todas as Demandas vigentes da mesma UO e exercício,
mesmo que tenham sido homologadas originalmente em versões diferentes
(`RN-PLA-004` e `RN-PLA-006`).

Exemplo conceitual:

```text
versão 1 homologou: D1, D2, D3
versão 2 homologou: D4

fotografia vigente, se todas continuarem ativas:
D1, D2, D3, D4
```

Versão de origem e pertencimento à fotografia vigente são relações diferentes.
O modelo não exige cópia ou reassociação de Demandas para expressar essa
fotografia.

O Plano continua representável quando não restar nenhuma Demanda ativa
(`RN-PLA-005`).

### 7.5 Confirmação versionada

`dataConfirmacaoSincronizacao` representa a confirmação de convergência da
versão homologada. Cada versão possui sua própria confirmação:

- uma nova versão nasce pendente;
- a versão vigente é confirmada somente depois da convergência;
- confirmações anteriores permanecem preservadas.

Essas invariantes pertencem a `RN-PLA-007` e são distintas da existência da
publicação externa, conforme `DI-005`.

O Plano carregado é uma fotografia imutável. A confirmação persistida não exige
mutar a instância já carregada nem criar outra somente para espelhar a escrita;
uma leitura posterior reconstrói a fotografia confirmada (`DI-005`).

### 7.6 Responsabilidades

O Aggregate:

- expressa sua identidade lógica;
- expõe o marco homologado vigente;
- representa zero ou mais Demandas vigentes;
- protege a uniformidade de UO e Ano de Execução;
- fornece o conjunto desejado à política de publicação.

O Aggregate isolado não consegue decidir a publicação, porque essa decisão
também depende do estado operacional e do estado externo observado
(`RN-PUB-001` a `RN-PUB-005` e `DI-002`).

## 8. Estados e política de publicação

### 8.1 Entradas conceituais da decisão

A política de publicação é uma regra pura do Domain. Ela relaciona:

1. a fotografia desejada de um ou mais Planos;
2. o estado operacional tipado da unidade externa;
3. o estado externo observado;
4. as diferenças homologadas entre o conjunto desejado e o observado.

Seu resultado expressa uma ação e, quando aplicável, as identidades das
Demandas envolvidas. A forma de obter, transportar ou persistir essas
informações fica fora da política (`DI-002` e `DI-007`).

### 8.2 Decisões

| Situação conceitual | Decisão | Autoridade |
| --- | --- | --- |
| Há Demandas vigentes e não há PCA externo ativo conhecido | `PUBLICAR` | `RN-PUB-001` |
| Há PCA externo ativo e mudanças homologadas no conjunto vigente | `RETIFICAR` | `RN-PUB-002` e `RN-PUB-006` |
| A unidade de publicação ficou vazia e há PCA externo ativo | `EXCLUIR_PCA` | `RN-PUB-003` |
| O estado já está convergente, ou ambos os lados estão vazios/inativos | `NENHUMA_ACAO` | `RN-PUB-004` |

O número da versão, isoladamente, não determina nenhuma dessas ações
(`RN-PUB-005`). O valor do sequencial externo também não determina a ação.

Depois de uma exclusão integral, uma nova Demanda homologada exige nova
Publicação, não uma Retificação do recurso excluído (`RN-PUB-007`).

### 8.3 Autoridade por fato

Para um PCA com vínculo local conhecido:

- o SGI governa o conjunto desejado de Demandas homologadas vigentes;
- o estado publicado externamente é um fato observado;
- a política procura convergência entre esses fatos sem importar alterações
  externas como novas homologações ou cancelamentos internos.

Não existe precedência absoluta e genérica de um sistema sobre o outro. PCA
externo sem vínculo conhecido e divergências não explicadas pertencem à
reconciliação assistida definida na `DI-002`.

### 8.4 Modos de publicação

No modo individual, cada UO governa sua própria unidade externa e produz
resultado independente (`RN-PUB-009`).

No modo consolidado, os Planos continuam separados por UO, mas suas fotografias
contribuem para uma única unidade externa. Uma UO vazia apenas deixa de
contribuir; a exclusão ocorre quando a união completa fica vazia
(`RN-PUB-010`).

As restrições de UASG de cada modo estão em `RN-PUB-011`. A escolha do modo e o
agrupamento são responsabilidades externas ao Aggregate, conforme `DI-003`.

## 9. Estado operacional externo, fora do Aggregate

### 9.1 Quatro conceitos independentes

| Conceito | Conteúdo | Responsabilidade |
| --- | --- | --- |
| Identidade do Plano | UO + Ano de Execução | Distinguir o Plano lógico |
| Fotografia vigente | Demandas ativas da mesma UO e exercício | Representar o planejamento homologado atual |
| Confirmação da sincronização | Pendência ou instante de confirmação de cada versão | Registrar que a versão vigente convergiu |
| Estado operacional externo | Modo, unidade externa, estado do PCA e eventual sequencial | Referenciar e acompanhar a publicação externa |

O `sequencialPca` pertence somente ao quarto conceito.

### 9.2 Unidade e cardinalidade

O estado operacional pertence à unidade externa de publicação:

- no modo individual, a unidade corresponde à UO;
- no modo consolidado, a mesma unidade reúne vários Planos do exercício.

Essa cardinalidade impede atribuir o estado operacional a um único Aggregate
`Plano`. Por isso existe uma porta própria da Application para esse estado,
separada do repositório do Aggregate (`DI-003` e `DI-007`).

### 9.3 Papel do sequencial externo

O sequencial é uma referência que permite localizar ou endereçar uma publicação
externa conhecida. Ele não:

- identifica o Plano;
- pertence à Demanda;
- compõe a fotografia vigente;
- confirma uma versão;
- determina a decisão de negócio.

As invariantes do estado operacional são:

- `NAO_PUBLICADO`: não possui sequencial;
- `PUBLICADO`: possui sequencial positivo;
- `EXCLUIDO`: preserva o último sequencial positivo apenas como referência
  histórica, sem indicar publicação ativa.

Essas invariantes pertencem a `RN-PUB-008`. A presença isolada do sequencial
jamais permite inferir o estado `PUBLICADO` (`DI-005`).

Uma nova Publicação posterior à exclusão adota um novo sequencial, preservando
o anterior apenas como histórico (`RN-PUB-007`).

### 9.4 Tipagem sem ownership pelo Plano

Um eventual tipo `SequencialPcaPncp` pode proteger apenas a validade da
referência externa, como a exigência de valor positivo quando presente. Isso
não o transforma em atributo central nem em Objeto de Valor do Aggregate
`Plano`.

A decisão futura sobre a forma e a camada desse tipo será tomada durante a
refatoração com TDD. Neste modelo, importa apenas que ele esteja contido no
estado operacional externo e permaneça fora do Aggregate.

A localização escolhida para persistir esse dado não transfere ownership
conceitual ao Plano. A projeção definida na `DI-008` é uma decisão de integração
e não uma redefinição do modelo.

A formulação histórica “Value Object opcional do Plano” foi substituída pela
separação entre Aggregate e estado operacional cross-Plan consolidada nas
`DI-005`, `DI-007` e `DI-008`.

## 10. Fatos de auditoria

### 10.1 `Procedimento`

`Procedimento` representa a classificação de uma ação formal auditável. Ele não
é a ocorrência da ação. Publicação inicial, Retificação e Exclusão integral
possuem classificações próprias, conforme `RN-AUD-002`.

A representação desse catálogo não é deduzida de seu armazenamento nem
detalhada neste modelo (`DI-006`).

### 10.2 `HistoricoProcedimento`

`HistoricoProcedimento` representa um fato de negócio imutável:

- uma operação externa foi concluída;
- seu resultado foi observado;
- a convergência foi confirmada;
- o fato recebeu classificação, autoria, Lotação, instante e mensagem.

Somente Publicação inicial, Retificação e Exclusão integral confirmadas geram
esse fato. `NENHUMA_ACAO`, tentativas e falhas não geram histórico de negócio
(`RN-AUD-001`).

Um histórico corresponde a um PCA externo confirmado: um por UO no modo
individual ou um único para a unidade consolidada (`RN-AUD-003`).

O responsável é o Representante associado ao usuário que iniciou a execução,
acompanhado de sua Lotação naquele momento (`RN-AUD-004`). O conteúdo exigido
da mensagem está definido em `RN-AUD-005`.

### 10.3 Classificação no modelo

O histórico não é Entity nem Aggregate Root apenas porque sua persistência
possui identificador técnico. O negócio não acompanha sua continuidade para
modificá-lo; ele preserva um fato ocorrido.

A Application materializa o fato com o contexto completo e possui a porta de
persistência correspondente (`DI-006` e `DI-007`).

### 10.4 Fatos não implicam infraestrutura de eventos

O modelo reconhece fatos e transições:

- a homologação origina ou versiona o Plano (`RN-PLA-002`);
- o cancelamento homologado e a DFD-E homologada produzem novas versões
  (`RN-DEM-006` e `RN-DEM-007`);
- operações externas confirmadas produzem fatos de auditoria
  (`RN-AUD-001`).

Isso não aprova, por si só, classes de Domain Event, barramento ou publicação
assíncrona. A forma concreta será definida somente quando houver necessidade
demonstrada.

## 11. Invariantes e relacionamentos

| Conceito | Invariante ou relação protegida | Referência |
| --- | --- | --- |
| CNPJ | Identificação válida no padrão alfanumérico vigente | `RN-CAD-001` |
| Ano de Execução | Ano corrente ou imediatamente seguinte | `RN-CAD-002` |
| Representante | Identificador e nome da pessoa responsável | `RN-CAD-003` |
| Lotação | Identificador positivo, descrição e distinção de UO | `RN-CAD-004` |
| Unidade Orçamentária | Lotação, descrição válida e UASG | `RN-CAD-005` |
| UO participante | Exatamente um cadastro ativo com UASG preenchida | `RN-CAD-006` |
| Demanda | Identidade global estável, quantidade positiva, categoria e origem válidas | `RN-DEM-001` e `RN-DEM-002` |
| Demanda homologada | Imutabilidade, exceto data desejada; demais mudanças exigem substituição formal | `RN-DEM-003` a `RN-DEM-005` |
| Fotografia | Participação determinada pela situação vigente da Demanda | `RN-DEM-009` |
| Identidade do Plano | UO + Ano de Execução | `RN-PLA-001` |
| Versão do Plano | Marco homologado positivo e incremental; maior versão é a vigente | `RN-PLA-002` e `RN-PLA-003` |
| Fotografia do Plano | Conjunto completo, uniforme em UO e exercício, admitindo zero Demandas | `RN-PLA-004` a `RN-PLA-006` |
| Confirmação | Própria de cada versão e nunca herdada ou sobrescrita | `RN-PLA-007` |
| Estado da publicação | Estado semântico coerente com a referência externa | `RN-PUB-008` |
| Unidade individual | Um PCA e um resultado por UO | `RN-PUB-009` |
| Unidade consolidada | União completa dos Planos e um resultado conjunto | `RN-PUB-010` |
| Auditoria | Um fato por PCA externo confirmado, com autoria e mensagem | `RN-AUD-001` a `RN-AUD-005` |

Invariantes locais são protegidas pelos objetos correspondentes. Regras que
envolvem vários Planos ou fatos externos são aplicadas por políticas do Domain
sob coordenação da Application; elas não são forçadas para dentro de uma única
instância de `Plano` (`DI-003` e `DI-007`).

## 12. Fronteiras externas e exclusões deliberadas

### 12.1 Pertence ao modelo

- identidade e fotografia do Plano;
- identidade e ciclo homologado da Demanda;
- versão e confirmação de sincronização;
- vocabulário dos estados e das decisões de publicação;
- política pura de decisão;
- significado dos fatos auditáveis;
- tipos que protegem valores e referências do negócio.

### 12.2 Permanece fora do Aggregate `Plano`

- escolha e congelamento do modo de publicação;
- agrupamento de Planos em unidade consolidada;
- estado operacional da publicação;
- sequencial e outros identificadores externos;
- observação do estado publicado;
- resolução do usuário e de seu contexto;
- materialização e persistência da auditoria.

Essas fronteiras estão consolidadas nas `DI-003`, `DI-005`, `DI-006` e
`DI-007`.

### 12.3 Permanece fora deste documento

- conversão entre identificadores internos e externos;
- algoritmo e limitações da reconciliação V1;
- migração de publicações sem vínculo;
- mecanismos de transação, exclusividade e recuperação;
- formatos de entrada e saída;
- configuração de ambientes;
- estruturas físicas e adapters;
- composição do executável;
- cronograma, sprints e ordem de implementação.

Esses assuntos pertencem às `DI-001` a `DI-010`, ao fluxo de implementação ou
ao roadmap. Sua ausência aqui preserva a independência do modelo conceitual.

## 13. Matriz de referências

### 13.1 Regras de negócio por parte do modelo

| Parte do modelo | Regras autoritativas |
| --- | --- |
| Cadastros e referências | `RN-CAD-001` a `RN-CAD-006` |
| Ciclo de planejamento | `RN-FLX-001` a `RN-FLX-004` |
| Identidade e ciclo da Demanda | `RN-DEM-001` a `RN-DEM-009` |
| Aggregate Plano | `RN-PLA-001` a `RN-PLA-007` |
| Política e estados de publicação | `RN-PUB-001` a `RN-PUB-011` |
| Fatos de auditoria | `RN-AUD-001` a `RN-AUD-005` |

### 13.2 Decisões que delimitam o modelo

| Decisão | Uso neste documento |
| --- | --- |
| `DI-001` | Distingue criação, sincronização e exclusão sem incorporar referências externas ao Plano |
| `DI-002` | Separa conjunto desejado, estado observado e autoridade por fato |
| `DI-003` | Mantém Aggregates por UO e coloca o agrupamento fora deles |
| `DI-004` | Mantém coordenação de falhas e exclusividade fora do modelo conceitual |
| `DI-005` | Separa confirmação versionada, publicação ativa e sequencial |
| `DI-006` | Classifica o histórico como fato imutável e delimita sua materialização |
| `DI-007` | Separa repositório do Aggregate, estado cross-Plan e política pura |
| `DI-008` | Confirma fotografia completa e distingue projeção física de ownership conceitual |
| `DI-009` | Mantém transporte e validações contratuais fora do Domain |
| `DI-010` | Mantém composição e ciclo de execução fora do Domain |

Referências documentais:

- `docs/vigentes/autoritativos/regras_de_negocio.md`;
- `docs/vigentes/autoritativos/decisoes_de_integracao.md`;
- `docs/vigentes/apoio/contexto_funcional_do_sistema_pca.md`;
- `docs/vigentes/apoio/glossario.md`.

## 14. Aprendizados arquiteturais

### 14.1 Identidade de negócio não é chave técnica

Uma chave usada para armazenar uma versão não transforma essa versão em outro
Plano lógico. Da mesma forma, um identificador técnico de histórico não cria
uma Entity. A identidade deve decorrer da continuidade reconhecida pelo
negócio.

### 14.2 Fotografia vigente não é versão de origem

Uma Demanda pode ter surgido em versão anterior e continuar fazendo parte do
Plano atual. O Aggregate representa o conjunto vigente, não apenas os elementos
associados ao último marco de homologação.

### 14.3 Confirmação não é existência externa

Uma nova versão pode estar pendente enquanto a publicação externa anterior
continua ativa. Por isso, confirmação da versão e estado operacional são fatos
distintos.

### 14.4 Referência externa não é identidade do domínio

O sequencial identifica um recurso da integração. O Plano existe antes dele,
continua identificado sem ele e pode compartilhar a mesma publicação com outros
Planos no modo consolidado.

Em síntese:

> O Plano representa o negócio. O sequencial representa a integração.

### 14.5 Localização física não determina ownership

Um dado pode ser armazenado junto a registros de Plano por compatibilidade e
ainda assim pertencer conceitualmente a uma projeção operacional externa. A
modelagem deve seguir significado e cardinalidade, não a disposição física.

### 14.6 Política de domínio não é mecanismo de integração

O Domain decide a intenção a partir de fatos tipados. Obter esses fatos,
executar operações externas e persistir resultados são responsabilidades
separadas. Essa distinção mantém as regras compreensíveis e testáveis sem
subordinar o modelo a uma tecnologia específica.
