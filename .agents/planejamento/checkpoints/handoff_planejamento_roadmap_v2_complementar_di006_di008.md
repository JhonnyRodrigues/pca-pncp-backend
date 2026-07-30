# Handoff complementar do planejamento do Roadmap V2

## Delta posterior à DI-005: DI-006, DI-007 e estado parcial da DI-008

---

## 1. Finalidade e relação com o checkpoint histórico

Este documento complementa, mas não substitui:

```text
.agents/planejamento/checkpoints/handoff_planejamento_roadmap_v2.md
```

O arquivo original deve ser lido integralmente antes deste complemento. Ele é
o checkpoint histórico que contém o contexto do projeto, o planejamento do
Roadmap V2 e as decisões DI-001 a DI-005.

Este arquivo registra **somente o delta de conhecimento produzido depois
daquele checkpoint**. Seu intervalo começa na DI-006 e termina dentro da
DI-008, que ainda não foi concluída.

| Decisão | Estado |
|---|---|
| DI-006 — Auditoria e procedimento PUBLICAÇÃO DE PLANO | Aceita |
| DI-007 — Portas necessárias na Application | Aceita |
| DI-008 — Mecanismo Oracle | Parcialmente aceita; ainda aberta |
| DI-009 — Transporte HTTP | Não iniciada |
| DI-010 — Composition Root e Scriptcase | Não iniciada |

Este complemento:

- não reescreve o checkpoint histórico;
- não consolida ainda o roadmap;
- não autoriza o início da Infrastructure;
- preserva Clean Architecture, DDD, TDD e Dependency Injection;
- mantém Domain e Application independentes de Scriptcase, ADOdb, OCI8,
  Oracle, HTTP e JSON.

Permanecem válidos os gates definidos no handoff original:

1. concluir DI-008, DI-009 e DI-010;
2. consolidar as decisões na documentação oficial;
3. executar a refatoração arquitetural prevista;
4. concluir e testar o gate da Application;
5. somente então iniciar a Infrastructure concreta.

---

## 2. Convenção de status

### Aceita

Decisão aprovada e vigente.

### Aceita condicionalmente

Direção arquitetural aprovada, mas dependente de teste de contrato objetivo. A
alternativa de contingência já está definida.

### Rejeitada

Alternativa analisada e descartada para a V2 atual.

### Substituída

Decisão ou premissa anterior que deixou de ser vigente diante de nova evidência
ou análise.

### Hipótese apoiada por evidência

Interpretação plausível, mas ainda insuficiente para definir contrato ou SQL.

### Pendente

Questão aberta que não pode ser resolvida por inferência no Repository, mapper,
adapter ou UseCase.

---

## 3. Resumo executivo do delta

1. `PAC_HISTORICOS` representa fatos de negócio concluídos e confirmados; não é
   diário de tentativas ou falhas técnicas.
2. A DI-007 fechou as portas necessárias para a Application autenticar,
   observar, decidir, escrever, confirmar, auditar e controlar a unidade de
   trabalho sem conhecer tecnologias externas.
3. A necessidade de baseline local detalhado foi retirada da V1. Para PCA
   conhecido e vinculado, a reconciliação será por pertencimento:
   `ID_DEMANDA` no SGI contra `numeroItem` no PNCP.
4. O código exportado pelo Scriptcase demonstrou que `$this->Db` disponibiliza
   uma conexão ADOdb real sobre OCI8. Reutilizá-la é a estratégia principal,
   condicionada a teste de contrato.
5. A exclusividade será feita por linhas permanentes em tabela técnica,
   bloqueadas com `SELECT ... FOR UPDATE NOWAIT`.
6. A estratégia anual será congelada na primeira execução válida, e não apenas
   depois da primeira publicação confirmada.
7. Foi abandonada a criação imediata de tabelas próprias para cabeçalho anual e
   instâncias externas. `PAC_PLANOS` será a projeção canônica do estado
   operacional corrente.
8. `PAC_DEMANDAS.SEQUENCIAL_PUBLICACAO_PNCP` não será fonte nem destino
   funcional no V2. O `sequencialPca` pertencerá exclusivamente à projeção em
   `PAC_PLANOS`.
9. A fotografia será reconstruída em duas etapas: descoberta preliminar e
   reconstrução autoritativa somente após o lock correto.
10. O principal ponto aberto é o predicado Oracle que define a Demanda vigente
    e o critério exato da versão vigente de `PAC_PLANOS`.

---

## 4. Decisões anteriores revisadas ou substituídas

Estas revisões são deliberadas e baseadas em nova análise. Elas não reabrem
indiscriminadamente DI-001 a DI-005.

### 4.1 DI-001 — Fonte física do `sequencialPca`

#### Entendimento anterior

O conceito já pertencia ao Plano/publicação, mas a Infrastructure poderia ler e
gravar a coluna repetida em `PAC_DEMANDAS` por compatibilidade.

#### Nova evidência

`SEQUENCIAL_PUBLICACAO_PNCP` é um campo novo e ainda não possui consumidores.
Não existe legado funcional que justifique preservar uma fonte na cardinalidade
de Demanda, inadequada para publicação consolidada, exclusão integral,
recriação e projeção única da publicação externa.

#### Decisão substituta

```text
PAC_PLANOS = fonte canônica exclusiva do sequencialPca operacional
PAC_DEMANDAS = nenhuma leitura ou escrita funcional desse sequencial
```

Se a coluna de `PAC_DEMANDAS` já existir, poderá ser removida na migração ou
permanecer temporariamente sem uso. Não haverá leitura dupla, fallback
silencioso nem sincronização entre fontes.

### 4.2 DI-002 — Baseline de conteúdo

#### Entendimento anterior

Era considerado obrigatório reconstruir ou persistir o último conteúdo cuja
publicação havia sido confirmada.

#### Decisão substituta

Demandas são reconciliadas na V1 por:

```text
ID_DEMANDA no SGI ↔ numeroItem no PNCP
```

Para um PCA conhecido e vinculado:

| Presença no SGI | Presença no PNCP | Resultado |
|---|---|---|
| Sim | Não | Incluir item |
| Não | Sim | Excluir item |
| Sim | Sim | Convergente quanto ao pertencimento |

Consequências:

- baseline detalhado deixa de ser requisito da V1;
- não haverá comparação automática de conteúdo;
- não haverá `PATCH` na V1;
- o SGI governa o conjunto desejado;
- o PNCP comprova o conjunto efetivamente publicado.

Essa revisão não autoriza adoção automática de publicação externa desconhecida.

### 4.3 DI-002 — PCA conhecido e ausente

#### Entendimento anterior

Um PCA conhecido localmente e ausente no PNCP sempre geraria conflito.

#### Decisão substituta

É necessário distinguir:

1. ausência definitivamente comprovada do PCA conhecido e vinculado;
2. ausência incerta, erro de consulta ou resposta ambígua;
3. PCA externo encontrado sem vínculo local conhecido.

Somente o primeiro caso permite recriação com novo `sequencialPca`. Nos demais:

- não recriar;
- não adotar automaticamente;
- bloquear ou classificar como falha reconciliável;
- preservar diagnóstico para ação consciente.

Mudanças ordinárias preservam o sequencial. Exclusão seguida de recriação, ou
ausência definitiva seguida de recriação, gera novo sequencial.

### 4.4 DI-003 — Momento do congelamento anual

#### Entendimento anterior

Modo e UASG consolidadora seriam congelados depois da primeira publicação
confirmada.

#### Problema e revisão

PNCP e Oracle não participam de transação distribuída. O PNCP pode confirmar e
o Oracle falhar antes do commit local. Congelar somente depois deixaria uma
janela para alteração da topologia externa no retry.

A estratégia será congelada na **primeira execução válida**, em transação
Oracle curta, serializada e confirmada antes de autenticar ou acessar o PNCP.

### 4.5 DI-005 — Histórico de sequenciais

A preservação do sequencial anterior continua válida, com materialização
esclarecida:

- `PAC_PLANOS` representa a projeção operacional corrente;
- `PAC_HISTORICOS` fornece memória mínima dos procedimentos e sequenciais;
- não será criada agora tabela própria de instâncias externas;
- após recriação, `PAC_PLANOS` reflete o novo sequencial ativo.

Modelagem histórica mais rica só deve ser reavaliada diante de requisito
concreto de consulta estruturada, retenção independente, múltiplas instâncias
simultâneas ou migração que exija histórico próprio.

---

## 5. DI-006 — Auditoria e procedimento PUBLICAÇÃO DE PLANO

Status: **Aceita**

### 5.1 Semântica de `PAC_HISTORICOS`

Decisão:

> `PAC_HISTORICOS` registra somente procedimentos de negócio concluídos,
> observados e confirmados.

Geram histórico:

- publicação inicial confirmada;
- retificação/sincronização confirmada;
- exclusão integral confirmada.

Não geram histórico:

- tentativa ainda não confirmada;
- validação rejeitada;
- falha técnica ou de transporte;
- resultado externo incerto;
- conflito de reconciliação;
- contenção de lock;
- rollback;
- `NENHUMA_ACAO`.

O histórico é trilha de fatos de negócio, não telemetria. Registrar antes da
confirmação poderia afirmar um procedimento não concluído ou posteriormente
desfeito.

### 5.2 Procedimentos aprovados

| Código | Fato representado |
|---:|---|
| 5 | Publicação inicial do PCA |
| 8 | Retificação/sincronização do PCA ativo |
| 9 | Exclusão integral do PCA |

Não será criada porta para consultar dinamicamente o catálogo apenas para esses
três valores conhecidos. A Application usa tipo orientado à intenção e o
adapter Oracle traduz esse tipo para o código persistido.

### 5.3 Unidade da auditoria

A unidade acompanha a publicação externa:

- modo individual: um fato por PCA da UO;
- modo consolidado: um fato para o PCA consolidado, não um por Plano/UO.

Isso evita registrar várias operações quando houve uma única requisição externa
e uma confirmação conjunta.

### 5.4 Responsável e lotação

O responsável é o usuário autenticado que iniciou a execução, acompanhado de
sua lotação. A autoria não será atribuída a usuário técnico do banco, adapter,
Scriptcase genérico ou usuário inferido da UO.

A Application recebe representante e lotação por uma porta de contexto do
usuário.

### 5.5 Mensagem padronizada

A Application materializa a mensagem com:

- exercício;
- UO ou indicação explícita de consolidado;
- UASG externa;
- `sequencialPca`;
- ação confirmada;
- na retificação, quantidades de itens incluídos e excluídos.

A mensagem não deve ser construída pelo Repository, depender do texto do PNCP,
conter payload completo/segredo ou afirmar sucesso antes da observação final.

### 5.6 Modelagem e ownership

`ID_HISTORICO` é chave técnica. Não confere identidade de negócio nem torna
`HistoricoProcedimento` uma Entity ou Aggregate Root.

O conceito aprovado é um fato imutável, criado/materializado pela Application,
persistido append-only e não usado como fonte primária da reconciliação.

A porta de persistência pertence à Application. A Infrastructure a implementa
para `PAC_HISTORICOS`. O Domain não conhece tabela, catálogo, SQL ou texto de
auditoria dependente da integração.

### 5.7 Transação e tempo

O insert em `PAC_HISTORICOS` participa da mesma transação Oracle que:

- confirma versões vigentes;
- atualiza o estado operacional;
- grava o sequencial corrente;
- registra metadados de confirmação.

```text
confirmação + estado operacional + auditoria -> commit conjunto
qualquer falha                              -> rollback conjunto
```

O instante vem do Oracle por `SYSDATE`, evitando relógios concorrentes entre
PHP e banco para fatos do mesmo commit.

### 5.8 Observabilidade técnica

Tentativas e falhas pertencem à observabilidade PSR-3. O destino padrão previsto
é `SEGURANCA_LOG`.

Os logs cobrem início/fim, autenticação, consultas, decisões, comandos,
resultados incertos, conflitos, locks, rollback e falhas. Devem sobreviver ao
rollback da transação de negócio e não registrar credenciais, JWT ou segredos.

### 5.9 Alternativas rejeitadas

| Alternativa | Motivo |
|---|---|
| Tentativas/falhas em `PAC_HISTORICOS` | Mistura fato de negócio com telemetria |
| Histórico para `NENHUMA_ACAO` | Não houve transição de negócio |
| Um histórico por UO no consolidado | Multiplica artificialmente a operação externa |
| Repository escolher procedimento/mensagem | Coloca decisão na Infrastructure |
| Aggregate por possuir ID Oracle | Confunde chave técnica com identidade |
| Auditoria fora da transação de confirmação | Permite estados locais contraditórios |

---

## 6. DI-007 — Portas necessárias na Application

Status: **Aceita**

### 6.1 Princípio

As portas são definidas pelas necessidades do caso de uso e não expõem URL,
HTTP, JSON, cURL, JWT, ADOdb, OCI8, SQL, tabelas ou macros Scriptcase. Seus
contratos serão demonstrados por testes da Application com doubles antes da
Infrastructure.

### 6.2 Autenticação PNCP

Uma porta autentica uma vez por execução e devolve uma `SessaoPncp` opaca,
reutilizada nas leituras e escritas. Token, headers e renovação não vazam para
Domain/Application; detalhes serão fechados na DI-009.

### 6.3 Leitura PNCP

Leitura e escrita ficam em portas distintas. A leitura permite:

1. consultar PCA conhecido pelo `sequencialPca`;
2. localizar candidatos por CNPJ/UASG/exercício somente para diagnóstico ou
   migração assistida.

Encontrar candidato não autoriza adoção automática. O retorno tipado deve
distinguir PCA encontrado, ausência definitivamente comprovada, consulta
inconclusiva, conflito/resposta inválida e falha sistêmica.

### 6.4 Escritas PNCP

Comandos orientados às capacidades:

- publicar PCA inicial;
- inserir itens;
- excluir itens;
- excluir PCA integralmente.

Não existe `PATCH` no fluxo V1. Resultados distinguem aceitação conhecida,
rejeição conhecida, resultado incerto e falha sistêmica. Essa classificação
impede retry cego de escrita externa.

### 6.5 Estratégia corrente e adotada

Uma porta coesa fornece estratégia corrente, estratégia adotada/congelada e a
operação orientada à intenção para adotá-la. O adapter não escolhe modo, não
aplica default, não esconde divergência e não altera estratégia adotada.

### 6.6 Unidade de trabalho e exclusividade

A Application solicita o ciclo conceitual:

```text
iniciar para chave lógica -> executar/reler -> confirmar
                                      ou     -> desfazer
```

Ela não conhece `FOR UPDATE`, `NOWAIT`, tabela de lock, sessão ou chamadas
ADOdb de transação.

### 6.7 Contexto do usuário e auditoria

Uma porta coesa fornece representante autenticado e lotação. A Application
possui também a porta de persistência do fato de auditoria da DI-006. Não há
porta dinâmica para catálogo dos procedimentos 5, 8 e 9.

### 6.8 `PlanoRepository`

Permanece porta do Domain e deve permitir:

- descobrir identidades lógicas;
- reconstruir fotografia homologada vigente completa;
- reconstruir Plano com zero Demandas ativas;
- confirmar versões convergidas por operação orientada à intenção.

Não deve escolher modo, consultar PNCP, consolidar por estratégia externa,
decidir ação, administrar estado cross-Plan ou construir auditoria.

### 6.9 Porta de metadados operacionais

Embora a DI-008 tenha escolhido `PAC_PLANOS` como persistência física, estado
compartilhado entre versões ou vários Planos consolidados não pertence ao
`PlanoRepository`.

Uma porta própria da Application representa o estado operacional da publicação.
Seu adapter pode atualizar `PAC_PLANOS`, mas o contrato permanece orientado à
publicação externa, não à tabela.

### 6.10 Política pura no Domain

O Domain recebe fotografia desejada, estado externo tipado e estado operacional
e devolve:

```text
PUBLICAR
RETIFICAR
EXCLUIR_PCA
NENHUMA_ACAO
```

Quando aplicável, inclui os `ID_DEMANDA` envolvidos. Não conhece endpoint,
payload, HTTP, SQL, coluna repetida ou lock Oracle.

### 6.11 Resultado da execução

O resultado é por unidade externa:

```text
CONCLUIDO
SEM_ALTERACAO
FALHA_RECONCILIAVEL
BLOQUEADO
```

No modo individual, normalmente há um resultado por UO/PCA. No consolidado,
um único resultado representa todas as UO do PCA conjunto.

### 6.12 Observabilidade e alternativas rejeitadas

A Application usa PSR-3 e não depende diretamente de `SEGURANCA_LOG`.

| Alternativa | Motivo |
|---|---|
| Gateway genérico expondo HTTP/JSON/JWT | Vazamento tecnológico |
| Operação “sincronizar” sem resultados tipados | Esconde incerteza e capacidades |
| Adoção automática de candidato externo | Pode vincular publicação alheia |
| Porta de catálogo para três valores | Abstração especulativa |
| Repositories separados para representante/lotação | Fragmenta a autoria |
| Resposta sempre por UO | Não representa consolidado |
| Metadados cross-Plan no `PlanoRepository` | Mistura Aggregate e projeção externa |

---

## 7. Refinamento da reconciliação V1

Esta conclusão surgiu durante a DI-007 e revisa parte da DI-002.

### 7.1 Autoridade por fato

```text
SGI  = autoridade sobre o conjunto desejado de Demandas
PNCP = autoridade sobre o conjunto efetivamente publicado
```

Para PCA conhecido e vinculado:

```text
somente SGI  -> inserir
somente PNCP -> excluir
interseção   -> manter
```

### 7.2 Fora do escopo da V1

- baseline detalhado;
- comparação atributo a atributo;
- correção automática de conteúdo;
- `PATCH`;
- adoção automática de PCA sem vínculo;
- retry cego de comando incerto.

### 7.3 Limitação aceita: `dataDesejada`

`dataDesejada` pode mudar depois da publicação sem troca de `ID_DEMANDA`. Como
a V1 compara pertencimento, essa alteração não será detectada nem propagada.

Se conteúdo mutável entrar no escopo futuro, será necessário decidir atributos,
baseline, normalização, conflitos e uso seguro de `PATCH`.

### 7.4 Histórico mínimo

O estado atual fica em `PAC_PLANOS`; sequenciais anteriores permanecem no
histórico de sucessos. `PAC_HISTORICOS` é suficiente nesta versão, sem tabela de
instâncias externas antecipada.

---

## 8. DI-008 — Mecanismo Oracle

Status: **Parcialmente aceita; ainda aberta**

As subseções distinguem decisões fechadas, aprovações condicionais e pontos
ainda dependentes de evidência.

### 8.1 Conexão fornecida pelo Scriptcase

Status: **Aceita condicionalmente**

#### Evidência prática

A análise do código exportado da aplicação Scriptcase `PCA_PUBLICAR_B` e o
teste com `sc_select` demonstraram que:

- o host não fornece apenas macros `sc_*`;
- `$this->Db` é um objeto ADOdb real;
- o driver observado é `ADODB_oci805` sobre OCI8;
- a conexão declara suporte a transações;
- `_connectionID` expõe internamente o recurso OCI8.

Isso tornou plausível reutilizar a sessão Oracle do host, mas não elimina o
teste de contrato.

#### Decisão

Estratégia principal:

1. receber `$this->Db` no Composition Root;
2. encapsulá-la em adapter de Infrastructure;
3. compartilhar o adapter/sessão entre Repository, Unit of Work, lock,
   confirmação, metadados e auditoria;
4. tratar a conexão como emprestada.

O backend poderá controlar a unidade transacional se o contrato for aprovado,
mas não possui o ciclo de vida completo e nunca chamará `Close()`.

Domain e Application não conhecem `$this->Db`, ADOdb, Scriptcase ou OCI8.

#### Gate obrigatório

Antes do adapter definitivo, validar no ambiente real:

1. início explícito de transação;
2. `commit`;
3. `rollback`;
4. bind de parâmetros;
5. captura e tradução de erros;
6. mesma sessão Oracle em todos os adapters;
7. rollback efetivo de alteração controlada.

Registrar também a versão de ADOdb embarcada. Como `oci805` é legado, o teste
deverá ser repetido em upgrades do Scriptcase.

#### Fallback oficial

Se qualquer requisito falhar, a alternativa oficial será uma conexão OCI8
própria. Nesse caso, todos os adapters continuarão compartilhando a mesma
conexão, mas o backend passa a possuir seu ciclo de vida.

PDO_OCI não foi escolhido como fallback.

#### Alternativas abandonadas

| Alternativa | Resultado |
|---|---|
| Somente macros `sc_*` | Insuficientes para o contrato transacional interno |
| Uso direto de `_connectionID` | Rejeitado por acoplamento a detalhe interno |
| OCI8 próprio desde o início | Rejeitado como primeira opção; mantido como fallback |
| PDO_OCI | Não selecionado |
| Chamar `Close()` no objeto do host | Proibido; a conexão é emprestada |

### 8.2 Exclusividade por unidade lógica

Status: **Aceita**

#### Objetivo

Impedir processamento simultâneo da mesma unidade externa. A concorrência
esperada é baixa, mas o impacto de duas execuções sobre o mesmo PCA é alto.

#### Mecanismo aprovado

Será usada uma tabela técnica com linhas-alvo permanentes.

Fluxo:

1. provisionar idempotentemente a linha-alvo em transação técnica curta;
2. iniciar a transação da unidade;
3. adquirir a linha por `SELECT ... FOR UPDATE NOWAIT`;
4. em contenção, retornar `BLOQUEADO` imediatamente;
5. manter o lock durante releitura, observação, decisão, escrita externa,
   confirmação e auditoria;
6. liberar por `commit`, `rollback` ou término da sessão.

A tabela não é fila nem histórico. A presença da linha não indica lock; o lock
pertence à transação Oracle.

#### Chaves lógicas

Modo individual:

```text
exercício + unidade externa da UO
```

Modo consolidado:

```text
exercício + UASG consolidadora
```

Congelamento:

```text
ESTRATEGIA_ANUAL + AnoExecucao
```

Formato físico e nomes de colunas serão definidos no DDL e não vazarão para a
Application.

#### Observabilidade

Registrar por PSR-3 tentativa, aquisição, contenção, encerramento e falha
inesperada do lock.

#### Alternativas rejeitadas

`SELECT ... FOR UPDATE` em linha de negócio:

- não existe linha natural única para o consolidado;
- Plano vazio ainda precisa ser protegido;
- novas versões mudam o conjunto físico;
- uma versão não representa a unidade externa.

`DBMS_LOCK`:

- é tecnicamente adequado;
- exige privilégios e governança adicionais;
- aumenta a complexidade Oracle;
- não se justifica para a concorrência atual.

Tabela com `INSERT` na aquisição e `DELETE` ao final:

- faz a existência da linha representar lock;
- introduz limpeza explícita e discussão de registros órfãos;
- acrescenta corridas de criação/remoção;
- tem recuperação menos simples que o lock transacional sobre linha permanente.

Lock global:

- rejeitado por bloquear unidades externas independentes.

### 8.3 Datas e horários

Status: **Aceita**

Datas puras são datas de calendário e não sofrem conversão de timezone.

Instantes são representados internamente por `DateTimeImmutable` no fuso
operacional explicitamente configurado.

No Oracle:

- usar máscaras explícitas;
- não depender de `NLS_DATE_FORMAT` implícito;
- preservar `NULL` como `NULL`;
- não usar datas sentinela;
- usar `SYSDATE` para confirmação e auditoria;
- validar relógio e fuso efetivo no teste de contrato.

### 8.4 Valores decimais

Status: **Aceita, com testes PNCP pendentes**

#### Evidências do SGI

```text
VALOR_TOTAL NUMBER(18,2)
QUANTIDADE  NUMBER(38,0)
valor unitário derivado
```

#### Evidências do PNCP

O manual PNCP v2.5 descreve os campos relevantes como `Decimal(17,4)`. O
OpenAPI consultado os descreve genericamente como `type: number`, sem explicitar
todos os limites de escala.

Fontes consultadas durante o planejamento:

- `https://pncp.gov.br/manual/pt-br/latest/singlehtml/`
- `https://pncp.gov.br/pncp-api/v3/api-docs`

A DI-009 deverá revalidar a documentação oficial vigente antes da implementação
do transporte.

#### Decisões

- usar representação decimal exata;
- não usar `float` no domínio ou na integração;
- reduzir escala somente na borda PNCP;
- emitir `valorUnitario` com até quatro casas;
- arredondar com `HALF_UP`;
- preservar `valorTotal` e `valorOrcamentoExercicio` autoritativos do SGI;
- não recalcular total a partir do unitário já arredondado;
- validar se a quantidade Oracle cabe em `Decimal(17,4)` antes da chamada.

Exemplo obrigatório para ADR e testes:

```text
valor total SGI = 1,00
quantidade      = 6
valor unitário  = 0,166666...

valorUnitario enviado = 0,1667
valorTotal preservado = 1,0000
```

O manual não comprovou exigência de igualdade algébrica exata entre o produto
do unitário arredondado e o total. Um teste deve verificar se o PNCP aceita a
diferença. Rejeição concreta reabre a decisão.

#### Pendência transferida à DI-009

Serializar como número JSON até 17 dígitos sem perda:

- `float` é inseguro;
- string pode violar `type: number`;
- a solução deve ser definida e testada junto ao transporte.

### 8.5 Configuração e congelamento anual

Status: **Aceita**

#### Configuração corrente

Continua vindo de:

```text
PAC_CONFIGURACOES.PUBLICAR_EM_UNICA_UASG
PAC_CONFIGURACOES.UASG_CONSOLIDADORA
```

Configuração ausente, inválida ou incoerente não recebe default e bloqueia antes
de autenticação ou acesso ao PNCP.

#### Execução válida

Pode adotar a estratégia somente quando:

- request e exercício são válidos;
- configuração está completa e coerente;
- regras de UASG são atendidas;
- existe ao menos uma identidade lógica de Plano no exercício.

A descoberta preliminar apenas comprova que existe contexto de trabalho. A
fotografia será relida sob o lock da unidade.

#### Fluxo de adoção

1. adquirir o lock `ESTRATEGIA_ANUAL + AnoExecucao`;
2. reler sob lock as configurações relevantes;
3. reler a estratégia adotada;
4. se ausente, persistir a estratégia corrente;
5. propagar a estratégia a todas as linhas de `PAC_PLANOS` do exercício;
6. confirmar a transação curta;
7. somente depois autenticar e acessar o PNCP.

Se já houver estratégia adotada, a Application compara com a corrente. Em
divergência, bloqueia sem corrigir, migrar ou sobrescrever automaticamente.

A transação é curta porque o congelamento é uma escolha anual durável e precisa
sobreviver a falha externa ou rollback posterior da unidade.

---

### 8.6 Persistência do estado operacional em `PAC_PLANOS`

Status: **Aceita quanto à direção; DDL pendente**

#### Mudança de direção arquitetural

Foram analisadas:

1. tabela própria de cabeçalho por exercício;
2. tabela própria com uma linha por instância externa;
3. evolução de `PAC_PLANOS` como projeção operacional.

A decisão final é a terceira.

Não serão criadas agora tabelas de cabeçalho ou instâncias porque não há
requisito atual para múltiplas instâncias simultâneas, retenção independente,
consulta estruturada de todo o ciclo de cada sequencial ou ciclo de vida
autônomo do registro operacional.

`PAC_HISTORICOS` atende ao histórico mínimo. Criar novas tabelas e entidades
agora seria modelagem antecipada. A hipótese deverá ser reaberta apenas diante
de requisito concreto.

#### Papel de `PAC_PLANOS`

`PAC_PLANOS` continua sendo criada pela homologação. O backend:

- não insere Planos;
- não assume a criação das versões;
- atualiza somente metadados operacionais;
- mantém `DATA_PUBLICACAO` como confirmação versionada da convergência.

#### Propagação da projeção

Estratégia anual:

- propagar a todas as linhas de `PAC_PLANOS` do exercício.

Modo individual:

- propagar estado, UASG externa e sequencial a todas as versões da mesma
  UO/exercício.

Modo consolidado:

- propagar estado, UASG consolidadora e sequencial a todas as linhas do
  exercício.

Cada atualização lógica usa um statement, ou operação atômica equivalente, e
o mesmo instante Oracle. Quando representa confirmação externa, participa da
transação da unidade.

`DATA_PUBLICACAO` é diferente:

- pertence à versão;
- confirma versões vigentes pendentes que convergiram;
- não é sobrescrita indiscriminadamente em versões antigas.

#### Novas versões e coerência

Uma nova linha criada pela homologação pode nascer com metadados operacionais
nulos.

Na leitura:

- o adapter reconstrói a projeção lógica conhecida no contexto;
- valores não nulos coerentes podem compor a projeção;
- valores não nulos divergentes bloqueiam;
- não há autorreparo silencioso.

Na próxima atualização válida, os dados são propagados novamente ao contexto
completo.

#### Ownership arquitetural

Embora os dados estejam em `PAC_PLANOS`:

- `PlanoRepository` continua responsável pelo Aggregate;
- uma porta própria da Application representa a publicação operacional;
- o adapter implementa leitura, validação de coerência e propagação;
- nenhuma regra de reconciliação pertence ao adapter.

#### DDL ainda aberto

Permanecem pendentes nomes de colunas, tipos, nulabilidade, migração,
constraints e índices. Esses detalhes não devem ser inventados antes do
contrato do schema.

### 8.7 Estado operacional e invariantes

Status: **Aceita**

Estados:

```text
NAO_PUBLICADO
PUBLICADO
EXCLUIDO
```

| Estado | Invariante do `sequencialPca` |
|---|---|
| `NAO_PUBLICADO` | Deve ser nulo |
| `PUBLICADO` | Deve ser positivo |
| `EXCLUIDO` | Preserva o último valor positivo |

Recriação:

```text
EXCLUIDO -> PUBLICAR -> PUBLICADO com novo sequencialPca
```

Após recriação, a projeção corrente recebe o novo sequencial e o anterior fica
no histórico mínimo. A mera presença de número antigo não significa PCA ativo.

As invariantes serão protegidas por tipos internos, validação do adapter e
constraints Oracle compatíveis com o DDL final.

### 8.8 Fonte canônica do `sequencialPca`

Status: **Aceita**

```text
sequencialPca pertence exclusivamente à projeção operacional em PAC_PLANOS
```

`PAC_DEMANDAS.SEQUENCIAL_PUBLICACAO_PNCP`:

- não é lida;
- não é gravada;
- não é fallback;
- não é comparada com `PAC_PLANOS`;
- não participa do Aggregate;
- pode ser removida ou permanecer temporariamente sem uso.

Não haverá compatibilidade porque não existem consumidores legados. A decisão
corrige a cardinalidade antes que novos consumidores sejam construídos.

### 8.9 Reconstrução em duas etapas

Status: **Aceita quanto à arquitetura; predicados pendentes**

#### Etapa 1 — Descoberta

Listar identidades lógicas no exercício sem montar Demandas. Isso valida que
existe unidade de trabalho, determina alvos, permite congelar a estratégia e
evita montar fotografia que envelheça antes do lock.

#### Etapa 2 — Reconstrução autoritativa

Depois do lock correto:

- reler as identidades pertinentes;
- selecionar a versão vigente;
- reconstruir todas as Demandas vigentes da UO/exercício;
- reconstruir também Plano com zero Demandas ativas.

Modo individual:

- reconstruir a UO depois do lock de sua unidade externa.

Modo consolidado:

- adquirir o lock consolidado;
- relistar identidades na mesma transação;
- reconstruir todas as UO da fotografia conjunta.

#### Regra sobre `FK_PLANO`

Não limitar a fotografia às Demandas cujo `FK_PLANO` aponta para a última linha
de `PAC_PLANOS`.

Demandas podem permanecer ligadas à versão em que foram homologadas. Portanto,
`FK_PLANO` registra origem/versionamento, não pertencimento exclusivo à última
fotografia.

A consulta precisa partir de `PAC_PLANOS`, ou usar semântica equivalente a
`LEFT JOIN`, para não eliminar Plano com zero Demandas ativas.

#### O que ainda não está comprovado

Não assumir:

- que somente `ID_SITUACAO = 2` participa;
- que `DATA_HOMOLOGACAO IS NOT NULL` basta;
- que `MAX(VERSAO)` absoluto sempre é vigente;
- que justificativa de cancelamento isolada define inatividade.

---

## 9. Evidências recentes do schema e seus limites

Status: **Hipóteses apoiadas por evidência**

### 9.1 `PAC_PLANOS`

Foram comprovadas as colunas:

- `FK_UNIDADE_ORCAMENTARIA`;
- `ANO_EXERCICIO`;
- `VERSAO`;
- `DATA_HOMOLOGACAO`;
- `DATA_PUBLICACAO`.

Os dados observados sustentam fortemente:

```text
identidade lógica = (FK_UNIDADE_ORCAMENTARIA, ANO_EXERCICIO)
VERSAO crescente  = revisões do Plano
```

Isso corrobora a identidade conceitual já aprovada, mas não comprova o
predicado de versão vigente.

É necessário descobrir se a versão vigente é a maior `VERSAO` absoluta, a
maior homologada, a maior que atende outro indicador ou combinação desses
fatos. Também é preciso verificar versões não homologadas, incompletas ou
abandonadas.

`DATA_PUBLICACAO` não deve ser usada para inferir vigência; sua semântica é a
confirmação da convergência daquela versão.

### 9.2 `PAC_UNIDADES_ORCAMENTARIAS`

Foi comprovada a existência de `PAC_UNIDADES_ORCAMENTARIAS`, associada por
`FK_UNIDADE_ORCAMENTARIA`. Isso sustenta a identificação da UO participante.

Ainda devem ser comprovados joins concretos, UASG associada, eventual vigência
do cadastro e possíveis duplicidades/históricos.

### 9.3 Domínio de situações da Demanda

| Código | Situação |
|---:|---|
| 1 | PLANEJADA |
| 2 | HOMOLOGADA |
| 3 | COMPRADA |
| 4 | CANCELADA |
| 5 | PUBLICADA |

A enumeração comprova os códigos, não a regra da fotografia vigente.

Ainda não se sabe por evidência autoritativa:

- se `COMPRADA` continua compondo o PCA;
- se `PUBLICADA` evolui de `HOMOLOGADA` e continua vigente;
- se somente `CANCELADA` é excluída;
- se existem outros campos de inatividade;
- como situação e versões sucessivas interagem.

### 9.4 Nome físico da tabela

Durante o levantamento apareceram:

```text
PAC_DEMANDA
PAC_DEMANDAS
```

O nome físico definitivo precisa ser comprovado. Este documento usa
`PAC_DEMANDAS` ao se referir ao conceito do checkpoint histórico, sem promover
essa grafia a contrato de schema.

---

## 10. Alternativas abandonadas e decisão vigente

Esta matriz impede que hipóteses intermediárias sejam retomadas como se ainda
estivessem aprovadas.

| Tema | Alternativa abandonada | Decisão vigente | Motivo principal |
|---|---|---|---|
| Auditoria | Tentativas e falhas em `PAC_HISTORICOS` | Somente fatos confirmados | Separar negócio de observabilidade |
| Auditoria consolidada | Um histórico por UO | Um histórico por publicação externa | A operação PNCP é única |
| Baseline | Persistir fotografia detalhada | Reconciliação por pertencimento | Escopo V1 não compara conteúdo |
| Conteúdo | Comparar atributos e usar `PATCH` | Sem comparação/PATCH na V1 | Sem contrato para conteúdo mutável |
| PCA ausente | Bloquear sempre | Recriar só após ausência definitiva | Recuperar sem agir sobre incerteza |
| Conexão | OCI8 próprio como primeira escolha | `$this->Db` condicionado ao contrato | Reutilizar sessão real do host |
| Conexão | PDO_OCI como fallback | OCI8 direto como fallback | Alternativa única e explícita |
| ADOdb | Usar `_connectionID` | Encapsular API pública | Evitar detalhe interno |
| Lock | `DBMS_LOCK` | Linha permanente + `FOR UPDATE NOWAIT` | Simplicidade e observabilidade |
| Lock | `INSERT`/`DELETE` | Lock transacional sobre linha permanente | Recuperação automática |
| Lock | Lock global | Lock por unidade lógica | Preservar independência |
| Lock | Linha de Plano/Demanda | Tabela técnica | Cobrir consolidado e Plano vazio |
| Congelamento | Após publicação confirmada | Primeira execução válida | Fechar janela distribuída |
| Estado operacional | Cabeçalho/tabela de instâncias | Projeção em `PAC_PLANOS` | Evitar modelagem sem requisito |
| Sequencial | Compatibilidade dual | Fonte única em `PAC_PLANOS` | Corrigir cardinalidade sem legado |
| Reconstrução | Só `FK_PLANO` da última versão | Fotografia completa por identidade | Demandas mantêm vínculo de origem |
| Reconstrução | Montar tudo antes do lock | Descoberta + releitura sob lock | Evitar fotografia obsoleta |
| Ownership | Metadados no `PlanoRepository` | Porta operacional da Application | Estado pode ser cross-Plan |

Algumas alternativas podem ser reabertas apenas diante de nova evidência:

- `PATCH` e baseline, se conteúdo mutável entrar no escopo;
- tabela de instâncias, se surgir histórico rico ou ciclo de vida próprio;
- `DBMS_LOCK`, se escala ou requisitos operacionais mudarem;
- OCI8 próprio, automaticamente, se o contrato do host falhar;
- outra serialização decimal, conforme o contrato da DI-009.

---

## 11. Impactos consolidados

### 11.1 Domain

O Domain:

- mantém política pura de reconciliação;
- trabalha com `Plano` como fotografia homologada vigente;
- aceita zero Demandas ativas;
- decide por pertencimento na V1;
- representa `NAO_PUBLICADO`, `PUBLICADO` e `EXCLUIDO`;
- devolve `PUBLICAR`, `RETIFICAR`, `EXCLUIR_PCA` ou `NENHUMA_ACAO`;
- preserva invariantes sem conhecer persistência ou transporte.

Não conhece Scriptcase, `$this->Db`, ADOdb/OCI8, SQL, tabelas, HTTP, JSON,
códigos físicos de procedimentos nem mensagem de auditoria dependente da
integração.

A revisão do baseline impede modelar fotografia publicada detalhada sem
necessidade. A limitação de `dataDesejada` deve permanecer explícita.

### 11.2 Application

A Application:

- valida entrada;
- obtém contexto do usuário;
- lê e congela estratégia;
- autentica uma vez;
- descobre unidades;
- solicita exclusividade;
- reconstrói sob lock;
- observa o PNCP;
- fornece fatos ao Domain;
- executa comandos tipados;
- observa novamente;
- confirma estado local;
- materializa auditoria;
- produz resultado por unidade externa.

Portas necessárias:

- autenticação PNCP;
- leitura PNCP;
- escrita PNCP;
- estratégia corrente/adotada;
- exclusividade;
- unidade de trabalho;
- contexto do usuário;
- auditoria;
- metadados operacionais;
- `PlanoRepository`;
- PSR-3.

A Application não manipula conexão, SQL, ADOdb, JSON, cURL, JWT ou tabela de
lock.

### 11.3 Infrastructure

Deverá:

- adaptar a conexão emprestada ou o fallback OCI8;
- garantir compartilhamento da sessão;
- implementar Unit of Work;
- implementar lock técnico com `NOWAIT`;
- reconstruir a fotografia completa;
- mapear datas explicitamente;
- mapear decimais sem `float`;
- persistir a projeção em `PAC_PLANOS`;
- persistir `PAC_HISTORICOS` na transação correta;
- adaptar PSR-3 ao destino técnico;
- detectar inconsistência física sem decidir regra de negócio.

Não poderá escolher reconciliação, adotar PCA, corrigir configuração, inferir
situação vigente, fechar conexão emprestada ou esconder valores divergentes.

### 11.4 Modelo Oracle

Mudanças conceituais previstas, ainda não implementadas:

- tabela técnica de exclusividade;
- metadados operacionais novos em `PAC_PLANOS`;
- constraints para estado/sequencial;
- eventual remoção do sequencial em `PAC_DEMANDAS`;
- queries de descoberta e reconstrução em duas etapas.

Ainda não estão aprovados nomes de colunas, DDL, índices, tipos, nulabilidade,
predicado da versão vigente nem predicado da Demanda vigente.

### 11.5 `PAC_PLANOS`

Acumula fisicamente, mas não conceitualmente, dois papéis:

1. linhas versionadas criadas pela homologação;
2. projeção operacional corrente da publicação externa.

Regras:

- backend não insere linhas;
- estratégia é propagada no exercício;
- estado, sequencial e UASG são propagados conforme a unidade externa;
- `DATA_PUBLICACAO` continua versionada;
- divergência não nula bloqueia;
- nova versão pode herdar logicamente a projeção até a próxima propagação.

### 11.6 `PAC_DEMANDAS`

Regras fechadas:

- `ID_DEMANDA` é usado como `numeroItem`;
- `SEQUENCIAL_PUBLICACAO_PNCP` não participa do V2;
- `FK_PLANO` não limita a fotografia à última versão.

Pendência central: definir com evidência quais linhas são Demandas vigentes.

### 11.7 `PAC_HISTORICOS`

Representa publicação, retificação e exclusão confirmadas e fornece o histórico
mínimo dos sequenciais. Não representa tentativa, falha, conflito, lock,
rollback ou `NENHUMA_ACAO`.

### 11.8 Integração PNCP

Impactos:

- uma `SessaoPncp` opaca por execução;
- leitura separada de escrita;
- reconciliação por pertencimento;
- comandos distintos de publicação, inclusão, exclusão de itens e exclusão de
  PCA;
- sem `PATCH` na V1;
- sem retry cego;
- ausência definitiva distinta de consulta inconclusiva;
- sem adoção automática de PCA desconhecido;
- decimais exatos e redução apenas na borda;
- testes contratuais antes de produção.

---

## 12. Gates, testes de contrato e limitações

### 12.1 Gate ADOdb/OCI8

Validar begin, commit, rollback, bind, tradução de erros, mesma sessão, rollback
real e versão do ADOdb. Falha em requisito obrigatório ativa OCI8 próprio.

### 12.2 Gate do schema SGI

Antes da query real, comprovar:

- nome físico da tabela de Demandas;
- colunas e constraints;
- join com `PAC_PLANOS`;
- critério da versão vigente;
- critério da Demanda vigente;
- semântica dos estados;
- cancelamento;
- Plano vazio;
- dados reais ou query autoritativa.

### 12.3 Testes de contrato PNCP

1. Verificar se `ID_DEMANDA` com lacunas é aceito como `numeroItem`.
2. Verificar se o total autoritativo pode diferir do produto do unitário
   arredondado.
3. Validar limites de `Decimal(17,4)`.
4. Validar serialização numérica exata na DI-009.
5. Confirmar classificação de ausência definitiva e resposta incerta.
6. Não habilitar retry de escrita sem contrato específico.

### 12.4 Limitações aceitas da V1

- sem comparação de conteúdo;
- sem `PATCH`;
- alteração de `dataDesejada` com mesmo `ID_DEMANDA` não é propagada;
- histórico de sequenciais mínimo;
- sem transação distribuída Oracle/PNCP;
- lock Oracle pode permanecer aberto durante HTTP;
- recuperação de publicação desconhecida é assistida;
- máximo de 1000 itens por unidade externa, conforme o checkpoint original.

### 12.5 Gate arquitetural

- Não iniciar Infrastructure antes do gate da Application.
- Não implementar contratos a partir de hipóteses do schema.
- Não atualizar o Roadmap V2 enquanto DI-008 a DI-010 estiverem abertas.
- Depois das decisões, consolidar em:
  - `docs/vigentes/autoritativos/regras_de_negocio.md`;
  - `docs/vigentes/autoritativos/decisoes_de_integracao.md`;
  - `docs/vigentes/autoritativos/roadmap.md` V2 (planejado, ainda não criado).
- Executar mudanças com TDD.

---

## 13. Estado Atual da DI-008

Status geral: **parcialmente fechada**

### 13.1 Decisões fechadas

- Reutilizar `$this->Db` como conexão principal, condicionado ao contrato.
- Tratar a conexão do host como emprestada e nunca chamar `Close()`.
- Usar OCI8 próprio como fallback se o contrato falhar.
- Compartilhar a mesma sessão em Repository, Unit of Work, lock, metadados,
  confirmação e auditoria.
- Encapsular Scriptcase/ADOdb na Infrastructure.
- Usar tabela técnica permanente e `SELECT ... FOR UPDATE NOWAIT`.
- Retornar `BLOQUEADO` imediatamente em contenção.
- Usar lock anual separado para adoção da estratégia.
- Tratar datas puras sem conversão de timezone.
- Usar `DateTimeImmutable`, fuso explícito, máscaras Oracle e `SYSDATE`.
- Representar decimais exatamente, sem `float`.
- Aplicar `HALF_UP` somente na borda PNCP.
- Preservar totais autoritativos do SGI.
- Congelar estratégia na primeira execução válida, antes do PNCP.
- Bloquear divergência entre estratégia corrente e adotada.
- Evoluir `PAC_PLANOS` como projeção operacional corrente.
- Não criar agora cabeçalho anual ou tabela de instâncias.
- Manter `DATA_PUBLICACAO` como confirmação versionada.
- Usar `NAO_PUBLICADO`, `PUBLICADO` e `EXCLUIDO`.
- Usar `PAC_PLANOS` como fonte exclusiva do `sequencialPca`.
- Não ler nem gravar sequencial em `PAC_DEMANDAS`.
- Reconstruir fotografia em duas etapas e sob o lock correto.
- Não limitar Demandas ao `FK_PLANO` da última versão.
- Permitir Plano com zero Demandas ativas.

### 13.2 Hipóteses apoiadas por evidência

Há forte evidência de que:

```text
(FK_UNIDADE_ORCAMENTARIA, ANO_EXERCICIO)
```

é a identidade lógica física e `VERSAO` crescente representa revisões.

Evidências observadas em `PAC_PLANOS`:

- `FK_UNIDADE_ORCAMENTARIA`;
- `ANO_EXERCICIO`;
- `VERSAO`;
- `DATA_HOMOLOGACAO`;
- `DATA_PUBLICACAO`.

`PAC_UNIDADES_ORCAMENTARIAS` sustenta a identificação da UO participante.

Isso ainda não comprova o predicado da versão vigente, os joins completos nem
o tratamento de versões não homologadas ou incompletas.

### 13.3 Decisões dependentes de evidência do schema

- Se versão vigente é a maior `VERSAO` absoluta ou maior homologada.
- Se e como `DATA_HOMOLOGACAO` participa do filtro.
- Quais situações compõem a fotografia:
  - `1 = PLANEJADA`;
  - `2 = HOMOLOGADA`;
  - `3 = COMPRADA`;
  - `4 = CANCELADA`;
  - `5 = PUBLICADA`.
- Como excluir Demandas canceladas.
- Se `COMPRADA` e `PUBLICADA` permanecem vigentes.
- Como reconstruir Demandas ligadas a versões antigas.
- Colunas, joins e filtros da consulta autoritativa.
- Nome físico: `PAC_DEMANDA` ou `PAC_DEMANDAS`.
- DDL das novas colunas operacionais de `PAC_PLANOS`.
- Constraints e índices da projeção e da tabela de lock.
- Resultado efetivo do contrato ADOdb/OCI8.

Não assumir que somente `ID_SITUACAO = 2` é vigente. Não usar
`JUSTIFICATIVA_CANCELAMENTO` isoladamente; ela informa o motivo, não substitui
o predicado estruturado.

### 13.4 Informações ainda necessárias do SGI

1. DDL ou descrição completa da tabela física de Demandas.
2. Nome e tipo da coluna de situação.
3. Chave `ID_DEMANDA` e comprovação de estabilidade.
4. Foreign key de origem para `PAC_PLANOS`.
5. Forma de derivar UO e exercício.
6. Query oficial/legada da fotografia vigente.
7. Exemplos reais de uma Demanda nos cinco estados.
8. Confirmação de quais estados continuam participantes do PCA.
9. Datas, flags ou relações adicionais de cancelamento/inatividade.
10. Exemplos de Demandas ainda ligadas à versão de origem.
11. Casos de nova versão, DFD-E e cancelamento após publicação.
12. Caso de Plano vigente com zero Demandas ativas.
13. Critério usado pelo SGI para selecionar a versão vigente.
14. Existência de versões não homologadas, incompletas ou abandonadas.
15. Join e dados de UASG em `PAC_UNIDADES_ORCAMENTARIAS`.

### 13.5 Ponto exato de retomada

A próxima conversa deve assumir DI-006 e DI-007 aceitas, preservar as decisões
fechadas desta DI-008 e considerar DI-009 ainda não iniciada.

Retomar exatamente por:

> Comprovar, por dados reais ou consulta autoritativa do SGI, o predicado
> Oracle que reconstrói as Demandas vigentes e o critério exato de seleção da
> versão vigente de `PAC_PLANOS`.

Sequência recomendada:

1. confirmar nome e schema de `PAC_DEMANDA(S)`;
2. analisar query ou amostras reais;
3. fechar um predicado por vez;
4. documentar `HOMOLOGADA`, `COMPRADA`, `PUBLICADA` e `CANCELADA`;
5. fechar a seleção da versão vigente;
6. concluir os demais gates da DI-008;
7. somente depois iniciar DI-009.

Não iniciar Infrastructure nem modificar o roadmap durante essa investigação.
