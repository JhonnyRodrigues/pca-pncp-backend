# Handoff do Planejamento do Roadmap V2

> Checkpoint de contexto para retomada em outra conversa.
>
> Data do checkpoint: 27/07/2026.
>
> Estado: planejamento em andamento. As decisões DI-001 a DI-005 foram
> aprovadas. A próxima discussão é a DI-006.

---

## 1. Finalidade deste documento

Este arquivo preserva o raciocínio e as decisões tomadas durante a revisão do
roadmap do backend PCA.

Ele foi criado para permitir que uma nova conversa retome o trabalho sem:

- reconstruir todo o contexto histórico;
- repetir perguntas já respondidas;
- confundir decisões aprovadas com hipóteses ainda abertas;
- iniciar a Infrastructure antes de fechar as pendências da Application;
- tratar a documentação atual como se ela já refletisse todas as decisões.

Este documento e um **handoff de planejamento**, não uma fonte normativa de
regras de negócio e não substitui:

- `docs/vigentes/autoritativos/regras_de_negocio.md`, que ainda seria criado
  naquele momento;
- `docs/vigentes/autoritativos/decisoes_de_integracao.md`, que ainda seria
  criado naquele momento;
- `docs/vigentes/autoritativos/roadmap.md`, cuja V2 ativa ainda será
  consolidada;
- os demais documentos temáticos do projeto.

Ao retomar:

1. ler este arquivo integralmente;
2. inspecionar o estado atual do workspace;
3. não implementar a Infrastructure;
4. não reabrir decisões marcadas como aprovadas sem nova evidência ou pedido
   explícito do usuário;
5. continuar pela **DI-006 - Auditoria e procedimento PUBLICAÇÃO DE PLANO**.

---

## 2. Estado atual do repositório

### 2.1 Roadmap

Na data deste checkpoint, a V1 ainda era o roadmap canônico e continha
observações, dúvidas e partes que já haviam ficado obsoletas diante das
decisões do planejamento.

Após a refatoração da arquitetura documental, o destino canônico dessa versão
histórica é:

```text
docs/historico/roadmap_v1_legado.md
```

O destino reservado para a futura versão ativa, V2, é:

```text
docs/vigentes/autoritativos/roadmap.md
```

Esta atualização de navegação não altera a cronologia nem marca como concluída
uma etapa da seção 17.

O motivo e evitar links permanentes apontando para uma versão numerada e
preservar a V1 como registro histórico e didático.

### 2.2 Alterações locais

Na criação deste checkpoint, o workspace já possuía diversas modificações
locais feitas anteriormente pelo usuário, inclusive em:

- prompts da pasta `.agents/`;
- `docs/vigentes/autoritativos/modelo_de_dominio.md`;
- `docs/historico/roadmap_v1_legado.md`;
- `docs/vigentes/apoio/glossario.md`;
- `src/Domain/Entities/Plano.php`;
- `src/Domain/Exceptions/PlanoException.php`.

Essas alterações não devem ser revertidas nem sobrescritas sem inspeção.

O planejamento que originou este handoff não implementou as decisões no código
nem consolidou a V2 do roadmap.

### 2.3 Código atual que exigira revisão

A inspeção feita durante o planejamento encontrou no modelo atual de `Plano`:

- `idPlano` tratado como identidade de domínio;
- `Demanda::idPlano` usado para validar pertencimento;
- proibição de Plano sem Demandas;
- decisão entre publicação e retificação baseada no número da versão;
- `dataPublicacao` usada como único indicador de publicação;
- uma provável inconsistência de nome no método
  `decidirPfrocessamento()`, enquanto testes e consumidores usam
  `decidirProcessamento()`.

Esses pontos devem ser verificados novamente antes de qualquer alteração.
Vários deles serão deliberadamente refatorados, mas somente depois da
consolidação documental e seguindo TDD.

---

## 3. Objetivo da revisão do roadmap

O usuário utiliza o roadmap com duas finalidades:

1. orientar a construção deste backend;
2. servir como guia de aprendizagem reutilizável em projetos futuros.

Por isso, a V2 deve ser:

- tecnicamente prescritiva;
- didática;
- coerente com Clean Architecture e DDD;
- separada da cronologia acidental deste projeto;
- capaz de mostrar a sequência recomendada de trabalho;
- capaz de registrar os aprendizados reais sem transformá-los no método
  recomendado.

O roadmap não deve sugerir que cada fase corresponde necessariamente a uma
camada. As fases representam uma **sequência de trabalho**.

---

## 4. Decisões editoriais aprovadas para a V2

### 4.1 Formato

Foi aprovado:

> Blocos estruturados no nível da fase e explicações didáticas intercaladas no
> nível das sprints e tarefas.

Cada fase deve apresentar, quando aplicável:

- objetivo;
- por que ela existe;
- entradas;
- entregas;
- critérios de saída;
- riscos;
- sprints;
- explicações didáticas junto das tarefas.

### 4.2 Versão prescritiva e histórico

A V2 principal deve apresentar o caminho recomendado, não reproduzir erros na
sequência principal.

O histórico sera preservado de duas formas:

- `docs/historico/roadmap_v1_legado.md`: cronologia integral da versão anterior;
- `Apêndice A - Evolução e Aprendizados do Projeto PCA`: resumo curado dos
  aprendizados mais importantes.

O apêndice deve registrar, entre outros:

- evolução de Demandas para Planos e depois PNCP;
- descoberta tardia da necessidade de catálogo de regras;
- descoberta posterior das portas da Application;
- reclassificações do modelo de domínio;
- decisões de integração que alteraram premissas antigas.

O apêndice não deve duplicar integralmente a V1.

### 4.3 Acompanhamento do projeto PCA

A situação concreta do PCA deve aparecer em uma tabela separada no topo do
roadmap.

Isso permite:

- manter as fases prescritivas e reutilizáveis;
- mostrar o progresso real do projeto;
- evidenciar convergências e refatorações ainda necessárias.

### 4.4 Numeração

Foi aprovada uma numeração limpa por fase:

```text
Sprint 4.1
Sprint 4.2
Sprint 4.3
```

Não usar numerações como `3A` e `3B`.

A V2 deve conter um mapeamento V1 -> V2 quando isso ajudar a rastrear o
histórico.

### 4.5 Marcadores didáticos

Usar explicitamente:

- `Princípio reutilizável`
- `Decisão do PCA`
- `Ponto em aberto`

### 4.6 Relação com o registro de decisões

O roadmap deve conter apenas:

- identificador da decisão;
- título;
- resumo;
- status;
- gate que ela bloqueia ou libera.

O contexto completo, alternativas e consequências devem ficar em:

```text
docs/vigentes/autoritativos/decisoes_de_integracao.md
```

### 4.7 Política de versões

- Mudanças estruturais relevantes geram uma versão histórica do roadmap.
- Pequenas correções editoriais atualizam o roadmap ativo e ficam rastreadas
  pelo Git.

### 4.8 Documento de contexto antigo

O conteúdo útil do documento agora preservado em:

```text
docs/historico/contexto_do_planejamento_do_projeto_backend.md
```

foi incorporado aos documentos apropriados no passo 5 da seção 17. O arquivo
foi arquivado sem correções retroativas em seu corpo histórico.

---

## 5. Macroestrutura aprovada do Roadmap V2

### Fase 0 - Preparação do projeto

Prepara repositório, ambiente, ferramentas, estrutura, padrões e documentação
inicial.

### Fase 1 - Descoberta e documentação do negócio

Consolida contexto, atores, linguagem ubíqua, regras de negócio e cenários antes
da modelagem.

### Fase 2 - Modelagem e implementação do Domain

Implementa conceitos, invariantes, comportamentos e exceções do domínio por
ciclos TDD.

### Fase 3 - Contratos requeridos pelo Domain

Define somente contratos realmente pertencentes ao Domain, especialmente
persistência de Aggregates.

### Fase 4 - Application, construída em estágios

Foi aprovada uma única fase de Application com três grandes estágios:

1. fluxo básico e contratos iniciais;
2. gate de decisões de integração e definição das portas da Application;
3. UseCase completo testado com mocks.

### Fase 5 - Infrastructure concreta

Implementa adaptadores Oracle, PNCP, configuração, mapeamentos e mecanismos
técnicos.

### Fase 6 - Wiring controlado do núcleo executável

Conecta UseCase e adaptadores concretos sem ainda depender da Presentation
final.

### Fase 7 - Presentation

Implementa Controller, Presenter, contratos de entrada e saída e adaptação
HTTP/Scriptcase.

### Fase 8 - Integração real e Composition Root final

Executa integração com Oracle e PNCP reais e fecha o bootstrap definitivo com
Scriptcase.

### Fase 9 - Entrega e operação

Fase própria aprovada para:

- configuração de produção;
- observabilidade;
- segurança operacional;
- implantação;
- recuperação;
- rotinas de suporte.

### Sequência externa aprovada

```text
Application completa com mocks
-> Infrastructure concreta
-> Wiring controlado
-> Presentation
-> Integração real e Composition Root final
-> Entrega e operação
```

O wiring anterior a Presentation testa o núcleo executável. O Composition Root
final ocorre depois que o Controller existe.

---

## 6. Detalhamento de fases já discutido

## 6.1 Fase 0 - Preparação

Status do PCA: concluída, sujeita apenas a consolidação editorial.

Estrutura recomendada:

### Sprint 0.1 - Repositório e ambiente canônico

- estrutura inicial;
- Git;
- definição do ambiente de execução;
- WSL como runtime PHP canônico.

`php.exe` foi útil como diagnóstico histórico no Git Bash, mas não deve ser o
runtime canônico da arquitetura de desenvolvimento.

### Sprint 0.2 - Identidade e governança

- `README.md`;
- `.agents/AGENTS.md`;
- princípios arquiteturais iniciais;
- regras de colaboração.

### Sprint 0.3 - Estrutura PHP

- Composer;
- autoload PSR-4;
- organização de `src/` e `tests/`;
- compatibilidade com PSR-12.

### Sprint 0.4 - Ambiente de testes

- PHPUnit;
- `phpunit.xml`;
- teste temporário de bootstrap.

O `PrimeiroTeste` e apenas um checkpoint temporário e deve ser removido quando o
primeiro teste real estiver funcionando.

Os comandos da fase devem indicar o resultado esperado, mas não precisam virar
um tutorial completo de instalação.

Documentos de regras de negócio pertencem conceitualmente à Fase 1, não à
preparação técnica.

## 6.2 Fase 1 - Descoberta e documentação do negócio

Formato aprovado para a fase:

1. contexto e atores;
2. linguagem ubíqua;
3. catálogo canônico de regras de negócio;
4. cenários e modelo conceitual referenciando as regras;
5. fluxo inicial do negócio e revisão de consistência.

Responsabilidade prevista dos documentos:

- `docs/vigentes/apoio/contexto_explicando_o_sistema_PCA.md`: contexto,
  usuários e processo;
- `docs/vigentes/apoio/glossario.md`: índice semântico da linguagem do projeto;
- `docs/vigentes/autoritativos/regras_de_negocio.md`: redação normativa
  vigente das regras;
- `docs/vigentes/autoritativos/modelo_de_dominio.md`: explicação do modelo e
  referência aos IDs;
- `docs/vigentes/autoritativos/fluxo_de_implementacao.md`: fluxo evolutivo
  planejado;
- `docs/vigentes/apoio/arquitetura.md`: arquitetura;
- `docs/vigentes/autoritativos/roadmap.md`: ordem de construção planejada;
- `docs/vigentes/autoritativos/decisoes_de_integracao.md`: decisões de
  integração.

Autoridade sera dividida por assunto. O catálogo e a fonte normativa da redação
das regras de negócio; documentos arquiteturais e ADRs continuam sendo
autoridade sobre seus próprios assuntos.

Conflito documental bloqueia implementação ate ser resolvido na fonte
apropriada.

O PNCP aparece na descoberta apenas como contexto e intenção. Endpoints,
autenticação, sincronização e falhas são fechados no gate da Application.

## 6.3 Catálogo de regras de negócio

Arquivo canônico aprovado:

```text
docs/vigentes/autoritativos/regras_de_negocio.md
```

Objetivo:

- responder rapidamente quais regras estão vigentes;
- evitar reconstrução a partir de conversas, testes e documentos dispersos;
- dar identificadores estáveis às regras;
- ligar regra, modelo, fluxo, decisão e evidência de teste.

### Identificadores

Formato aprovado:

```text
RN-CONTEXTO-NNN
```

Exemplos:

```text
RN-DEM-001
RN-PLA-001
```

Política:

- ID nunca e reutilizado;
- ID não muda apenas porque a classificação foi refinada;
- mudança semântica cria novo ID e marca a regra anterior como substituída;
- refinamento textual sem mudança semântica mantém o ID.

### Estrutura do catálogo

O arquivo deve conter:

- tabela de índice para consulta rápida;
- entradas detalhadas agrupadas por contexto.

Cada entrada deve possuir:

- enunciado normativo;
- status normativo;
- origem ou autoridade;
- contexto histórico e justificativa;
- exemplos e contraexemplos;
- elementos do modelo afetados;
- evidências de teste;
- ADRs e fluxos relacionados;
- histórico de alterações.

Status previstos:

- `Em validação`
- `Vigente`
- `Substituída`

### Fluxo de manutenção

1. A descoberta entra como candidata, com sua origem.
2. Após confirmação pela autoridade de negócio, recebe ou atualiza um ID.
3. O catálogo e atualizado antes de modelo, fluxo, roadmap e código.
4. Documentos temáticos referenciam o ID, sem reescrever a regra de modo
   concorrente.
5. Testes que evidenciam regra de negócio usam:

   ```text
   @regra RN-...
   ```

6. Testes estritamente técnicos não precisam de RN.

Não existe relação obrigatória 1:1 entre regra e teste:

- uma regra pode ter vários testes;
- um teste pode evidenciar várias regras.

Um grupo PHPUnit como `#[Group('RN-...')]` pode ser usado para filtragem
executável, mas não sera a fonte normativa.

A validação automatizada do catálogo foi adiada ate o formato amadurecer com uso
real. Depois, um verificador simples pode detectar:

- IDs duplicados;
- referências inexistentes;
- inconsistências básicas.

Não introduzir dependência externa antes dessa necessidade existir.

### Posição histórica

Na sequência recomendada, o catálogo nasce na Fase 1.

No projeto PCA real, a necessidade foi percebida durante a evolução da
Application. Isso deve aparecer:

- na tabela de acompanhamento como trabalho de convergência;
- no apêndice histórico como aprendizado.

## 6.4 Fase 2 - Domain

Princípio aprovado:

> Uma sprint representa um conceito de negócio desenvolvido por um ciclo TDD,
> não necessariamente uma única classe.

O registro documental do TDD deve preservar intenção e evidência final. Não
fabricar transcrições históricas de RED quando elas não foram registradas.

Na versão prescritiva, o Domain deve apresentar diretamente a classificação
correta conhecida hoje:

- CNPJ com o contrato alfanumérico vigente;
- `UnidadeOrcamentaria` como Entity;
- `Uasg` como Value Object;
- `Plano` como Aggregate Root.

Erros e correções históricas, como a classificação inicial de
`UnidadeOrcamentaria` como Value Object, pertencem ao apêndice.

A classificação atual de `HistoricoProcedimento` ainda esta em revisão e depende
da DI-006.

## 6.5 Fase 3 - Contratos requeridos pelo Domain

Decisões de ownership:

- `PlanoRepository`: pertence ao Domain;
- `RepresentanteRepository`: pertence à Application;
- `LotacaoRepository`: pertence à Application;
- `HistoricoProcedimentoRepository`: ownership ainda pendente da DI-006.

Motivação:

- resolver usuário autenticado e contexto de lotação e necessidade do fluxo da
  Application;
- persistir o Aggregate `Plano` e contrato do Domain;
- auditoria ainda precisa ser corretamente modelada antes de fixar o contrato.

Interfaces não devem possuir testes que apenas inspecionam sua existência por
reflection.

Necessidades devem ser demonstradas por:

- testes do consumidor/UseCase com mocks;
- testes de contrato e integração dos adaptadores concretos.

O histórico real de portas criadas na camada errada deve aparecer no apêndice,
não na sequência recomendada.

## 6.6 Fase 4 - Application

### Estágio básico já existente

Manter conceitualmente cinco sprints:

1. Request imutável;
2. Validator;
3. decisão de processamento no Domain;
4. UseCase inicial;
5. Response DTOs.

O UseCase atual apenas:

- valida a solicitação;
- consulta Planos;
- pede uma decisão a cada Plano;
- devolve DTOs de resposta.

Ele ainda não:

- autentica no PNCP;
- consulta estado externo;
- publica;
- sincroniza itens;
- persiste confirmação;
- registra auditoria;
- controla transações;
- resolve modo de publicação.

### Gate obrigatório dentro da Application

Antes de criar novas portas e completar o UseCase, fechar as decisões DI-001 a
DI-010.

### Refatoração arquitetural obrigatória

Depois das decisões da Application e antes da Infrastructure, criar uma sprint
explícita para:

- consolidar ownership de contratos;
- mover responsabilidades entre Domain e Application;
- finalizar o modelo de auditoria;
- corrigir premissas antigas do Aggregate `Plano`;
- ajustar documentação e testes;
- remover contratos provisórios incorretos.

---

## 7. Contexto histórico do negócio confirmado pelo usuário

O sistema não nasceu orientado a PCA nem ao PNCP.

Evolução:

```text
Demandas
-> Unidade Avaliadora
-> Unidade Orçamentária
-> Workflow de Aprovação
-> Homologação
-> Consolidação em Planos
-> Publicação no PNCP
```

O objetivo inicial era:

```text
Registrar Demandas
-> Submeter ao workflow de avaliação
-> Homologar Demandas
```

Planos e PNCP surgiram depois. Varias decisões atuais são resultado dessa
evolução natural, e não de um desenho feito desde o início.

### Regras de negócio já confirmadas

- Demandas homologadas não podem ser alteradas.
- Para modificar uma Demanda homologada, ela deve ser cancelada com
  justificativa e uma nova Demanda deve ser criada.
- A nova Demanda percorre novamente workflow, avaliação e homologação.
- O planejamento ordinário ocorre no ano anterior à execução.
- Demandas ordinárias são homologadas em lote pelo presidente.
- Demandas extraordinárias, DFD-E, possuem workflow próprio e homologação
  individual.
- A homologação de uma DFD-E cria nova versão do Plano.
- O cancelamento de uma Demanda homologada também cria nova versão do Plano.
- Cada nova versão gera novo `PAC_PLANOS.ID_PLANO`.
- O sequencial da versão e incrementado para a combinação:

  ```text
  AnoExecucao + UnidadeOrcamentaria
  ```

- O cancelamento da última Demanda ativa e permitido.
- Se não restar Demanda ativa, a publicação externa deve ser excluída.
- A justificativa do cancelamento esta estruturada em:

  ```text
  PAC_DEMANDAS.JUSTIFICATIVA_CANCELAMENTO
  ```

- `PAC_HISTORICOS` não e a fonte da justificativa do cancelamento.

Essas regras precisam ser migradas para o catálogo com IDs estáveis antes da
refatoração do código.

---

## 8. Decisão central sobre o Aggregate Plano

### 8.1 Modelo físico atual

Exemplo:

```text
Plano v1:
  D1 -> FK_PLANO = v1
  D2 -> FK_PLANO = v1
  D3 -> FK_PLANO = v1

DFD-E homologada:
  D4 -> FK_PLANO = v2
```

As Demandas antigas não são copiadas nem reassociadas fisicamente à v2.

### 8.2 Significado de negócio aprovado

O Aggregate `Plano` representa o **estado homologado vigente completo** para:

```text
AnoExecucao + UnidadeOrcamentaria
```

Depois da DFD-E:

```text
Plano vigente:
  D1
  D2
  D3
  D4
```

Portanto:

- a fotografia completa e semântica, reconstruída por consulta;
- ela não significa que todas as Demandas apontam fisicamente para o último
  `ID_PLANO`;
- `PAC_DEMANDAS.FK_PLANO` registra a versão de homologação de origem;
- a última linha de `PAC_PLANOS` fornece metadados atuais;
- o Repository reconstrói o Aggregate com todas as Demandas homologadas vigentes
  da UO e do exercício.

### 8.3 Identidade lógica

A identidade lógica do Plano e:

```text
AnoExecucao + UnidadeOrcamentaria
```

Não fazem parte da identidade lógica:

- `PAC_PLANOS.ID_PLANO`;
- `SequencialVersao`;
- datas;
- sequencial do PNCP.

`SequencialVersao` e `ID_PLANO` representam marcos/metadados de homologação e
persistência.

### 8.4 Consequências aprovadas

- Remover `PAC_PLANOS.ID_PLANO` do Domain `Plano`.
- Remover `PAC_DEMANDAS.FK_PLANO` do Domain `Demanda`.
- O mapper/repository mantém os identificadores técnicos internamente.
- Remover a invariante que exige que toda Demanda tenha o mesmo `idPlano`.
- Preservar as invariantes de AnoExecucao e UnidadeOrcamentaria.
- Permitir Aggregate com zero Demandas ativas para representar a exclusão do PCA.
- Substituir o teste `testDeveImpedirDemandasDeOutroPlano`.
- O número da versão, sozinho, não decide entre publicar e sincronizar.

Exemplo importante:

- pode existir v2 antes de qualquer publicação no PNCP;
- nesse caso, v2 ainda exige publicação inicial, não retificação.

O Domain continuara decidindo, mas recebera fatos externos tipados e abstratos
pela Application. Ele não conhecerá HTTP, JSON, Oracle ou Client PNCP.

---

## 9. Registro de decisões de integração

Arquivo previsto:

```text
docs/vigentes/autoritativos/decisoes_de_integracao.md
```

Formato aprovado: ADR-lite em arquivo único.

Cada decisão deve conter:

- ID;
- título;
- status;
- contexto;
- fontes;
- alternativas;
- decisão;
- consequências;
- pendências;
- impacto no roadmap.

Status previstos:

- `Proposta`
- `Em análise`
- `Aceita`
- `Substituída`
- `Bloqueada`

### Sequência de decisões

| ID | Tema | Estado |
|---|---|---|
| DI-001 | Contrato de publicação e sincronização do PNCP | Aceita |
| DI-002 | Fonte da verdade e reconciliação com Oracle | Aceita |
| DI-003 | Modos de publicação e unidade de processamento | Aceita |
| DI-004 | Transações e falhas parciais | Aceita |
| DI-005 | Estado do Plano após confirmação | Aceita |
| DI-006 | Auditoria e procedimento PUBLICAÇÃO DE PLANO | Próxima |
| DI-007 | Portas necessárias na Application | Pendente |
| DI-008 | Escolha do mecanismo Oracle | Pendente |
| DI-009 | Escolha do transporte HTTP | Pendente |
| DI-010 | Composition Root e integração com Scriptcase | Pendente |

---

## 10. DI-001 - Contrato de publicação e sincronização do PNCP

Status: **Aceita**

### 10.1 Publicação inicial

A publicação inicial cria um PCA:

```text
POST /v1/orgaos/{cnpj}/pca
```

O payload vigente inclui:

- `codigoUnidade`;
- `anoPca`;
- `itensPlano`.

A publicação inicial:

- exige pelo menos um item;
- aceita no máximo 1000 itens;
- retorna referência ao recurso criado;
- gera `sequencialPca`.

### 10.2 Sincronização posterior

Uma nova versão interna não cria automaticamente outro PCA.

Enquanto a publicação externa estiver ativa:

- o mesmo `sequencialPca` e mantido;
- novas Demandas homologadas são incluídas;
- Demandas homologadas posteriormente canceladas são excluídas;
- o estado externo e reconciliado com a fotografia vigente.

Operações normais:

- `POST .../itens`: incluir itens novos;
- `DELETE .../itens`: excluir itens cancelados;
- `PATCH .../itens`: não faz parte do fluxo normal atual.

O `PATCH` não e necessário no fluxo normal porque Demanda homologada e imutável.
Alteração exige cancelamento e criação de outra Demanda.

### 10.3 Significado de retificação

No projeto, `RETIFICAR` passa a significar:

> Sincronizar no PCA externo ativo as mudanças homologadas do estado vigente,
> preservando o mesmo sequencial externo.

Não significa:

- reenviar sempre o Plano completo;
- gerar nova publicação;
- gerar novo `sequencialPca`;
- criar nova versão externa para cada versão interna.

### 10.4 Exclusão integral

Se não existir nenhuma Demanda homologada ativa e houver PCA externo ativo:

```text
EXCLUIR_PCA
```

A decisão `DecisaoProcessamentoPlano` deve incluir:

```text
EXCLUIR_PCA
```

A justificativa vem do cancelamento estruturado que retirou a última Demanda
ativa.

### 10.5 Identificadores

Decisão aprovada:

```text
ID_DEMANDA -> numeroItem
```

O usuário confirmou que `ID_DEMANDA` e globalmente único.

Pendência obrigatória:

- o manual exige `numeroItem` único e sequencial crescente;
- validar no ambiente de homologação do PNCP se os IDs reais, inclusive com
  lacunas, são aceitos;
- não implementar o mapeamento em produção antes desse teste contratual.

### 10.6 Sequencial externo

`SEQUENCIAL_PUBLICACAO_PNCP`, hoje repetido fisicamente em Demandas, representa
o `sequencialPca` do PCA, não o número individual do item.

No modelo:

- o conceito pertence ao estado do Plano/publicação;
- deve existir um Value Object `SequencialPcaPncp`;
- o adaptador Oracle pode ler e gravar a coluna repetida por compatibilidade;
- o Aggregate enxerga um único valor;
- inconsistências entre valores repetidos devem ser detectadas.

`numeroControlePNCP` permanece metadado de integração ate existir necessidade de
negócio documentada.

### 10.7 Fontes oficiais consultadas

- Inserir Plano:
  https://pncp.gov.br/manual/pt-br/latest/plano_de_contratacao/inserir_plano_de_contratacoes.html
- Inserir Itens:
  https://pncp.gov.br/manual/pt-br/latest/plano_de_contratacao/inserir_itens_de_plano_de_contratacao.html
- Retificar Itens:
  https://pncp.gov.br/manual/pt-br/latest/plano_de_contratacao/retificar_parcialmente_itens_de_um_plano_de_contratacao.html
- Excluir Item:
  https://pncp.gov.br/manual/pt-br/latest/plano_de_contratacao/excluir_item_de_plano_de_contratacao.html
- Excluir Itens:
  https://pncp.gov.br/manual/pt-br/latest/plano_de_contratacao/excluir_itens_de_um_plano_de_contratacao.html
- Excluir Plano:
  https://pncp.gov.br/manual/pt-br/latest/plano_de_contratacao/excluir_plano_de_contratacoes.html
- Manual completo v2.5:
  https://pncp.gov.br/manual/pt-br/latest/singlehtml/

---

## 11. DI-002 - Fonte da verdade e reconciliação com Oracle

Status: **Aceita**

### 11.1 Autoridade dividida por fato

Princípio aprovado:

```text
SGI = sistema mestre do estado de negócio desejado
PNCP = autoridade sobre o estado efetivamente publicado
Oracle local = espelho operacional reconciliável
```

O SGI governa:

- Demandas homologadas vigentes;
- cancelamentos;
- justificativas;
- composição desejada do Plano.

O PNCP governa:

- existência real do PCA externo;
- `sequencialPca`;
- número de controle;
- itens realmente visíveis no portal;
- resultado externo efetivamente observado.

O PNCP:

- pode revelar divergências;
- não cria automaticamente uma nova verdade de negócio no SGI;
- não altera Demandas, homologações ou cancelamentos internos;
- não deve ser importado automaticamente para substituir o domínio.

### 11.2 Fluxo de reconciliação

O ciclo normal aprovado e:

```text
observar
-> decidir
-> executar
-> observar novamente
-> confirmar localmente
```

Consultar antes permite decidir entre:

- `PUBLICAR`;
- `RETIFICAR`;
- `EXCLUIR_PCA`;
- `NENHUMA_ACAO`.

Consultar depois permite comprovar convergência antes de confirmar o Oracle.

### 11.3 Divergências sincronizáveis

Somente mudanças internas homologadas podem gerar sincronização automática:

- nova Demanda homologada ainda não confirmada externamente;
- Demanda anteriormente publicada e depois cancelada.

Casos esperados:

- item novo homologado ausente no PNCP: incluir;
- item anteriormente publicado e agora cancelado: excluir.

Casos inesperados:

- item ativo anteriormente confirmado desapareceu do PNCP;
- item externo sem correspondente conhecido no SGI;
- mesmo `numeroItem` com conteúdo divergente;
- PCA conhecido localmente desapareceu do PNCP;
- alteração sem evento interno homologado que a explique.

### 11.4 Política de conflito

Quando SGI e PNCP divergem sem causa interna conhecida:

- não atualizar automaticamente o SGI;
- não sobrescrever automaticamente o PNCP;
- registrar diagnóstico;
- interromper a unidade afetada;
- exigir análise e ação explícita.

Motivação:

> Sem evidência suficiente, não e possível saber automaticamente qual lado esta
> incorreto. Uma correção automática pode destruir informação válida.

### 11.5 PCA conhecido localmente e ausente no PNCP

Se o Oracle registra uma publicação anterior e ela não e encontrada no PNCP:

- bloquear;
- diagnosticar;
- não recriar automaticamente;
- não apagar o vínculo local automaticamente.

### 11.6 Publicações legadas

Existe atualmente uma publicação manual conhecida, feita via Swagger.

Decisão:

- publicações legadas não serão adotadas automaticamente;
- serão tratadas por migração/reconciliação assistida durante implantação;
- a existência de um PCA externo, sozinha, não cria vínculo no SGI.

### 11.7 Falha externa seguida de falha local

Não sera criada nesta versão uma infraestrutura durável de tentativa apenas para
distinguir automaticamente:

- publicação feita pelo backend;
- publicação legada;
- confirmação externa seguida de rollback local.

Se o PNCP confirmar e o Oracle falhar antes do commit:

- o risco e aceito devido ao baixo volume, baixa concorrência e natureza
  administrativa;
- o caso deve gerar diagnóstico;
- a recuperação do vínculo local exige reconciliação consciente;
- não adotar automaticamente um PCA externo desconhecido localmente.

Durante a **mesma execução**, uma escrita incerta pode ser observada e
reconciliada uma vez, conforme DI-004. Isso não equivale a adotar
automaticamente um PCA legado em execução posterior.

### 11.8 Matriz conceitual

| Estado desejado SGI | Estado PNCP | Evidência local | Resultado |
|---|---|---|---|
| Com itens | Ausente | Nunca publicado | PUBLICAR |
| Com itens | Ausente | Publicação ativa conhecida | CONFLITO |
| Com itens | Presente e convergente | Coerente | NENHUMA_ACAO |
| Com itens | Presente e diferença homologada | Coerente | RETIFICAR |
| Com itens | Presente e diferença inexplicada | Qualquer | CONFLITO |
| Vazio | Presente e conhecido | Coerente | EXCLUIR_PCA |
| Vazio | Ausente | Exclusão confirmada/nunca publicado | NENHUMA_ACAO |
| Qualquer | Presente | Sem vínculo local conhecido | MIGRAÇÃO ASSISTIDA |

### 11.9 Requisito de baseline

Para distinguir mudança pendente de divergência inesperada, a Application deve
conhecer o último estado cuja publicação foi confirmada.

A necessidade esta aprovada. A forma de:

- expor essa informação por porta;
- reconstruí-la do Oracle;
- persistir o espelho;

sera fechada nas DI-007 e DI-008.

---

## 12. DI-003 - Modos de publicação e unidade de processamento

Status: **Aceita**

### 12.1 Configuração

As chaves vêm de:

```text
PAC_CONFIGURACOES
```

Chaves:

```text
PUBLICAR_EM_UNICA_UASG
UASG_CONSOLIDADORA
```

A Application acessa essas informações por uma porta de configuração.

O Domain e o Repository não:

- leem configuração;
- escolhem modo;
- consolidam Planos por estratégia externa.

Configuração ausente ou inválida:

- bloqueia a execução antes de autenticação ou comunicação com PNCP;
- não possui valor padrão silencioso.

### 12.2 Modo NÃO

```text
PUBLICAR_EM_UNICA_UASG = NÃO
```

Unidade lógica:

```text
AnoExecucao + UnidadeOrcamentaria
```

Características:

- um PCA externo por UO;
- transação por UO;
- resultado por UO;
- falha específica de uma UO não impede as demais.

Regra de configuração:

- duas UO do mesmo exercício não podem compartilhar a mesma UASG;
- UASG duplicada faria duas unidades internas governarem o mesmo PCA;
- a inconsistência bloqueia antes de qualquer chamada ao PNCP.

O usuário confirmou que, atualmente, todas as UO possuem UASG distintas.

### 12.3 Modo SIM

```text
PUBLICAR_EM_UNICA_UASG = SIM
```

UASG de destino:

```text
PAC_CONFIGURACOES.CHAVE = 'UASG_CONSOLIDADORA'
```

Unidade lógica:

```text
AnoExecucao + UASG_CONSOLIDADORA
```

Características:

- todos os Planos internos do exercício formam uma unidade externa;
- existe uma reconciliação conjunta;
- existe um único resultado conjunto;
- falha pertence ao consolidado inteiro;
- não existem sucessos externos independentes por UO.

### 12.4 Composição do consolidado

O estado desejado e a união completa de:

- todas as Demandas homologadas;
- ativas;
- pertencentes a todos os Planos do exercício.

Não consolidar apenas Planos alterados nem apenas novas Demandas.

Os Aggregates `Plano` continuam separados por UO. A consolidação e uma decisão do
fluxo da Application e não modifica o modelo do Domain.

### 12.5 Exclusão no modo consolidado

- uma UO sem Demandas ativas apenas deixa de contribuir com itens;
- o PCA compartilhado continua se outras UO ainda possuem Demandas;
- o PCA inteiro e excluído somente quando a união vigente fica vazia;
- a justificativa e a do cancelamento homologado que retirou o último item
  ativo.

### 12.6 Congelamento por exercício

Após a primeira publicação confirmada do exercício, ficam congelados:

- modo de publicação;
- UASG consolidadora, quando aplicável.

Motivação:

- mudar de vários PCA para um PCA, ou vice-versa, altera a topologia externa;
- mudar UASG altera a identidade externa;
- a mudança exigiria exclusão, recriação, migração e reconciliação.

Mudança posterior:

- exige processo assistido explícito;
- não e automática.

Exercícios novos podem adotar outra estratégia.

A forma de persistir o modo efetivamente usado por exercício ainda precisa ser
definida na Infrastructure.

### 12.7 Identificador dos itens

O usuário confirmou:

```text
ID_DEMANDA e globalmente único
```

Isso evita colisão entre UO no PCA consolidado, mas não elimina o teste
contratual sobre a exigência de sequência crescente do PNCP.

---

## 13. DI-004 - Transações e falhas parciais

Status: **Aceita**

### 13.1 Princípio

Uma transação Oracle não inclui o PNCP.

Ela:

- protege atomicidade local;
- pode sustentar serialização;
- não desfaz requisição HTTP confirmada;
- não elimina falha distribuída.

### 13.2 Serialização

Somente uma execução pode processar a mesma unidade lógica por vez.

Chaves conceituais:

- modo NÃO: AnoExecucao + UO/UASG externa;
- modo SIM: AnoExecucao + UASG consolidadora.

Unidades diferentes podem continuar independentes.

O mecanismo concreto de lock ainda sera escolhido na DI-008. A Application deve
pedir exclusividade sem conhecer:

- `SELECT FOR UPDATE`;
- `DBMS_LOCK`;
- conexão Oracle concreta.

### 13.3 Limite transacional aprovado

Fluxo lógico:

1. validar configuração;
2. autenticar no PNCP uma vez por execução;
3. para cada unidade, abrir transação e obter exclusividade;
4. reler o estado desejado no Oracle sob exclusividade;
5. consultar o PNCP;
6. decidir o plano de operações;
7. executar operações;
8. consultar novamente;
9. persistir confirmação, metadados e auditoria;
10. commit;
11. liberar a unidade.

A transação Oracle permanece aberta durante chamadas HTTP.

Trade-off aceito:

- locks mais longos;
- simplicidade;
- serialização;
- baixo volume e baixa concorrência atuais.

Revisitar se essas premissas mudarem.

### 13.4 Granularidade real das requisições

Publicação inicial com ate 1000 itens:

- uma chamada `POST /pca`;
- do ponto de vista do cliente, requisição aceita ou rejeitada;
- a API não devolve resultado individual por item;
- a consulta posterior valida a fotografia observável.

Sincronização posterior pode exigir mais de uma chamada:

- um `POST` em lote para inclusões;
- um `DELETE` em lote para exclusões;
- outras chamadas se houver particionamento.

A falha parcial relevante ocorre **entre requisições**, por exemplo:

1. inclusão aceita;
2. exclusão posterior falha.

Não assumir que o terceiro item de um mesmo payload recebeu resultado individual.

### 13.5 Política de continuidade dentro da unidade

Foi aprovada a estratégia:

> Continuar com critério.

- Falha específica e conhecida de um lote: registrar e continuar operações
  independentes.
- Falha sistêmica ou resultado impossível de observar: interromper.
- Não executar compensação automática.
- Ao final, consultar novamente o PNCP.
- A observação final, não apenas os códigos HTTP intermediários, determina
  convergência.

Se a consulta final mostrar convergência:

- persistir localmente;
- fazer commit;
- considerar sucesso mesmo se houve erro intermediário.

Se restarem somente mudanças homologadas pendentes:

- rollback;
- retornar falha reconciliável;
- permitir nova execução.

Se houver diferença inesperada:

- rollback;
- conflito bloqueante;
- ação consciente.

### 13.6 Escrita de resultado incerto

Não repetir `POST`, `DELETE` ou `PATCH` cegamente.

Fluxo:

1. registrar a ocorrência;
2. consultar estado atual;
3. comparar desejado e publicado;
4. recalcular operações;
5. escrever novamente apenas se ainda necessário.

Foi aprovado:

- no máximo uma reconciliação controlada adicional na mesma execução;
- nova incerteza encerra como falha reconciliável;
- não criar loop ate convergir.

### 13.7 Falhas entre unidades

No modo NÃO:

- erro específico de uma UO não impede as outras;
- erro sistêmico de autenticação, conectividade ou serviço encerra a execução.

No modo SIM:

- existe apenas uma unidade consolidada;
- qualquer falha pertence ao conjunto.

### 13.8 Falha PNCP confirmado / Oracle falhou

Se a consulta final comprovar convergência, mas persistência ou commit Oracle
falhar:

- rollback local;
- não tentar desfazer PNCP automaticamente;
- registrar diagnóstico;
- exigir reconciliação consciente;
- risco aceito para a primeira versão.

### 13.9 Limite de 1000 itens

O usuário informou media aproximada de 500 Demandas por exercício.

Decisão:

- não implementar particionamento acima de 1000 na primeira versão;
- registrar a limitação;
- monitorar volume;
- se uma unidade ultrapassar 1000 itens, bloquear antes da escrita externa;
- nunca publicar silenciosamente somente os primeiros 1000;
- reabrir a decisão se o volume se aproximar do limite.

---

## 14. DI-005 - Estado do Plano após confirmação

Status: **Aceita**

### 14.1 Dois fatos diferentes

O modelo deve distinguir:

1. existência de PCA externo;
2. confirmação de que a versão homologada vigente convergiu com esse PCA.

Exemplo:

- v1 publicada;
- DFD-E cria v2;
- o PCA externo continua ativo com o mesmo sequencial;
- v2 ainda não esta confirmada.

Logo:

```text
dataPublicacao != null
```

não e mais uma descrição suficiente de todo o estado.

### 14.2 Semântica de PAC_PLANOS.DATA_PUBLICACAO

Decisão aprovada:

> Momento em que o SGI confirmou que esta versão homologada convergiu com o
> estado publicado.

Consequências:

- cada linha versionada possui sua própria confirmação;
- nova versão nasce com `DATA_PUBLICACAO = NULL`;
- após convergência, a versão vigente pendente recebe data;
- datas de versões anteriores não são sobrescritas;
- retificação não cria outra versão;
- a coluna não e cópia da data informada pelo PNCP.

Fonte temporal:

```text
Oracle SYSDATE
```

No Domain, o nome aprovado e:

```text
dataConfirmacaoSincronizacao
```

O mapper Oracle traduz para a coluna legada:

```text
PAC_PLANOS.DATA_PUBLICACAO
```

### 14.3 Aggregate como fotografia imutável

Escolha aprovada:

- não mutar o `Plano` ao final do UseCase;
- não criar outra instância apenas para refletir persistência;
- uma nova consulta reconstrói o estado confirmado.

Responsabilidades:

- Domain decide;
- Application executa e comprova convergência;
- Application ordena a confirmação;
- Repository persiste `SYSDATE` e metadados;
- Repository não decide se a confirmação e permitida.

O contrato deve usar um método orientado à intenção, e não setter genérico.

A sugestão antiga:

```php
$plano->registrarPublicacaoConfirmada(...)
```

não deve ser levada automaticamente à V2.

### 14.4 Estado tipado

Foi aprovada uma representação explícita no Domain, conceitualmente:

```text
NAO_PUBLICADO
PUBLICADO
EXCLUIDO
```

O estado influencia:

- `PUBLICAR`;
- `RETIFICAR`;
- `EXCLUIR_PCA`;
- `NENHUMA_ACAO`.

Ele não conhece:

- HTTP;
- JSON;
- Oracle;
- endpoint;
- status code.

A Application fornece fatos observados por tipos internos.

### 14.5 Sequencial após exclusão

Depois de exclusão integral:

- preservar o último `sequencialPca` como histórico;
- não interpretar a mera presença desse número como PCA ativo;
- estado passa a `EXCLUIDO`.

Sequência conceitual:

```text
NAO_PUBLICADO
-> PUBLICADO
-> EXCLUIDO
-> PUBLICADO com novo sequencial
```

Se uma nova DFD-E for homologada após exclusão:

- decidir `PUBLICAR`;
- criar novo PCA;
- receber novo sequencial;
- manter o sequencial antigo apenas no histórico.

### 14.6 Confirmação por modo

Modo NÃO:

- confirmar a versão vigente pendente da UO processada.

Modo SIM:

- após convergência da união completa, confirmar as versões vigentes pendentes
  que compõem a unidade consolidada;
- não sobrescrever versões já confirmadas.

### 14.7 Metadado fora do Aggregate

`numeroControlePNCP` permanece fora do Aggregate enquanto não houver necessidade
de negócio comprovada.

Eventos e auditoria foram deliberadamente adiados para a DI-006.

---

## 15. Próxima decisão: DI-006 - Auditoria e procedimento PUBLICAÇÃO DE PLANO

Status: **não iniciada**

Retomar exatamente aqui.

### 15.1 Contexto já conhecido

`HistoricoProcedimento` atualmente:

- possui `ID_HISTORICO` técnico;
- não possui identidade de negócio própria;
- e imutável;
- e append-only;
- representa auditoria cronológica;
- não e manipulado individualmente pelo negócio.

Conclusão provisória:

- não deve ser tratado como Entity/Aggregate Root apenas por ter chave no banco;
- ha forte indicação de que representa um evento de domínio persistido;
- a classificação final depende desta decisão.

`HistoricoProcedimentoRepository` não deve orientar a Infrastructure antes da
DI-006.

### 15.2 Pontos que a DI-006 deve decidir

Discutir um ponto por vez:

1. Qual fato gera auditoria:
   - somente convergência confirmada;
   - tentativa;
   - falha;
   - nenhuma ação.
2. Se falhas operacionais pertencem a `PAC_HISTORICOS` ou somente a logs
   técnicos.
3. Qual procedimento representa:
   - publicação inicial;
   - sincronização/retificação;
   - exclusão integral.
4. Qual e o identificador de negócio de `PUBLICAÇÃO DE PLANO`.
5. Se e necessário mais de um procedimento de catálogo.
6. Qual e a unidade de auditoria:
   - um evento por Plano/UO;
   - um evento por publicação externa;
   - comportamento no modo consolidado.
7. Como registrar responsável e lotação.
8. Como construir a mensagem sem colocar regra em Repository, Client ou
   PayloadBuilder.
9. Se a auditoria participa da mesma transação Oracle da confirmação.
10. Como modelar o evento e quem possui a porta de persistência.

### 15.3 Primeira pergunta recomendada

Começar pela semântica:

> `PAC_HISTORICOS` deve registrar apenas procedimentos de negócio concluídos e
> confirmados, enquanto tentativas e falhas ficam na observabilidade técnica, ou
> ele também deve registrar tentativas malsucedidas?

Não assumir a resposta.

---

## 16. Decisões ainda pendentes depois da DI-006

## 16.1 DI-007 - Portas necessárias na Application

Somente depois de DI-001 a DI-006.

Possíveis necessidades já identificadas, ainda não consolidadas:

- autenticação PNCP;
- consulta de estado publicado;
- publicação inicial;
- inclusão de itens;
- exclusão de itens;
- exclusão de PCA;
- configuração do modo de publicação;
- controle de transação;
- exclusividade/lock por unidade;
- obtenção do responsável autenticado;
- obtenção da lotação;
- persistência de auditoria;
- tipos internos para estado observado e resultado de sincronização.

Regras:

- nomes orientados à intenção;
- sem tipos HTTP, JSON, cURL ou Oracle nas interfaces internas;
- portas de Application pertencem à Application;
- `PlanoRepository` continua no Domain;
- contratos devem ser demonstrados por testes do UseCase com mocks;
- evitar interfaces especulativas.

Também fechar:

- formato do baseline de última confirmação;
- DTO tipado para estado externo;
- classificação de falhas;
- comando de confirmação para o Repository;
- retorno por unidade de processamento.

## 16.2 DI-008 - Mecanismo Oracle

Decidir:

- OCI8;
- PDO_OCI;
- conexão fornecida pelo Scriptcase;
- estratégia de teste;
- mesma conexão para Repository, lock, auditoria e transação;
- mecanismo concreto de exclusividade;
- mapeamento de tipos e datas;
- leitura da configuração;
- reconstrução da fotografia completa;
- persistência do modo congelado por exercício;
- persistência do estado `EXCLUIDO`;
- tratamento da coluna repetida de sequencial nas Demandas.

O usuário informou anteriormente ter instalado as extensões ausentes no PHP do
WSL. Confirmar novamente no momento técnico, não presumir.

## 16.3 DI-009 - Transporte HTTP

Decidir:

- cURL nativo ou biblioteca;
- autenticação e renovação de JWT;
- token valido por aproximadamente uma hora conforme manual;
- timeouts;
- TLS;
- classificação de erros;
- retries de leitura;
- proibição de retry cego de escrita;
- ambientes de homologação e produção;
- serialização dos payloads;
- testes de contrato.

Usar somente documentação oficial vigente do PNCP.

## 16.4 DI-010 - Composition Root e Scriptcase

Decidir:

- entrada real do Scriptcase;
- propriedade das credenciais;
- Controller;
- Presenter;
- criação do UseCase;
- injeção dos adaptadores;
- lifecycle de conexão e token;
- resposta por unidade;
- bootstrap para produção e homologação.

O wiring do núcleo vem antes de Presentation. O Composition Root definitivo,
com Controller e Scriptcase, vem na integração real.

---

## 17. Refatorações documentais e arquiteturais antes da Infrastructure

Depois das decisões DI-001 a DI-010, a ordem foi revisada para que o Roadmap
V2 seja consequência de regras, modelo e fluxo já compreendidos, e não uma
fonte tardia de descoberta de requisitos.

Sequência natural da documentação:

```text
Regras de negócio
        ↓
Modelo de domínio
        ↓
Decisões de integração
        ↓
Fluxo de implementação
        ↓
Roadmap
        ↓
Código
```

Na sequência prescritiva, as decisões de integração aparecem depois do modelo
porque dependem dos conceitos e limites do domínio. O fluxo de implementação
depende tanto do modelo quanto das decisões que definem as portas e os
comportamentos externos.

No projeto real,
`docs/vigentes/autoritativos/decisoes_de_integracao.md` foi consolidado antes da
revisão do modelo. Isso não exige retrabalho: o modelo deve ser guiado pelas RN,
enquanto as DI são usadas somente para definir fronteiras e fatos externos.
Essa diferença entre sequência ideal e cronologia real deve ser registrada no
apêndice histórico do Roadmap V2.

Ordem vigente:

1. Criar e preencher
   `docs/vigentes/autoritativos/regras_de_negocio.md`. **Concluído.**
2. Criar e preencher
   `docs/vigentes/autoritativos/decisoes_de_integracao.md`. **Concluído.**
3. Atualizar
   `docs/vigentes/autoritativos/modelo_de_dominio.md`. **Concluído.**
   - derivar conceitos, identidades, relações e invariantes das RN vigentes;
   - remover identificadores físicos e premissas substituídas;
   - referenciar RN para regras e DI somente nas fronteiras externas;
   - não antecipar adapters ou classes de Infrastructure.

Como refinamento documental descoberto após a consolidação do modelo e das DI,
foi incorporado o seguinte gate de coerência, sem renumerar as etapas
originais:

**Gate 3A.** Revisar
`docs/vigentes/apoio/arquitetura.md`. **Concluído.**

- alinhar responsabilidades e direção das dependências ao modelo e às DI;
- corrigir ownership das portas;
- explicitar fachada, Composition Root e fronteira Scriptcase;
- distinguir arquitetura-alvo do código atual;
- manter o documento como apoio, sem criar regras ou decisões concorrentes.

Como segundo refinamento documental anterior ao fluxo, foi incorporado o gate:

**Gate 3B.** Revisar e integrar
`docs/vigentes/apoio/glossario.md`. **Concluído.**

- consolidar linguagem de negócio, domínio, integração e arquitetura;
- remover termos antigos e definições concorrentes;
- referenciar as fontes autoritativas;
- preservar o caráter exclusivamente de apoio.

Como terceiro refinamento documental anterior ao fluxo, foi incorporado o
seguinte gate de governança, sem renumerar as etapas originais:

**Gate 3C.** Consolidar a governança operacional dos agentes. **Concluído.**

- arquivar o prompt inicial reutilizável como registro histórico;
- manter `AGENTS.md` como única instrução operacional permanente;
- alinhar suas orientações à arquitetura e à governança documental vigentes;
- preservar a finalidade didática por meio de feedback adaptativo e
  proporcional à tarefa;
- distinguir instruções, checkpoints, sínteses e prompts históricos.

4. Criar
   `docs/vigentes/autoritativos/fluxo_de_implementacao.md`. **Concluído.**
   - partir do modelo revisado;
   - descrever a colaboração entre Presentation, Application, Domain e portas;
   - referenciar RN para comportamento e DI para contratos externos;
   - não conter cronograma, sprints ou tecnologia concreta.
5. Consolidar o contexto de planejamento, hoje preservado em
   `docs/historico/contexto_do_planejamento_do_projeto_backend.md`. **Concluído.**
   - distribuir o conteúdo útil entre os documentos apropriados;
   - revisar novamente modelo e fluxo se a consolidação acrescentar contexto;
   - arquivar somente depois de confirmar que nada útil foi perdido;
   - tratar a consolidação como gate de coerência anterior ao roadmap.
6. Preservar a V1 em
   `docs/historico/roadmap_v1_legado.md`. **Concluído.**
   - caminho histórico canônico confirmado;
   - corpo histórico preservado integralmente, sem alterações semânticas
     posteriores ao arquivamento;
   - nenhuma correção retroativa aplicada à V1 histórica.
7. Criar a V2 ativa em
   `docs/vigentes/autoritativos/roadmap.md`. **Concluído.**
   - usar regras, modelo, DI e fluxo consolidados como entradas;
   - apresentar a sequência prescritiva e didática;
   - manter acompanhamento do PCA, mapeamento V1 -> V2 e apêndice histórico;
   - representar decisões condicionais como gates, não como fatos comprovados.
8. Executar a sprint de refatoração arquitetural com TDD. **Concluído em 04/08/2026 após reauditoria final.**
   - as desconformidades encontradas pela auditoria independente foram
     corrigidas e validadas;
   - licença permanece como ressalva transversal não bloqueante, e a
     representação decimal continua destinada à Sprint 5.6;
   - a conclusão não autoriza nem inicia a Sprint 4.8.
9. Completar a Application com mocks.
10. Somente então iniciar Infrastructure.

### Critérios de saída documentais

Antes de congelar a V1 e elaborar a V2:

- toda regra normativa do modelo e do fluxo referencia uma `RN-*`;
- toda escolha externa ou operacional referencia uma `DI-*`;
- modelo e fluxo não repetem premissas substituídas;
- o modelo não contém Oracle, HTTP, Scriptcase ou estrutura física;
- o fluxo não funciona como roadmap ou cronograma;
- o conteúdo útil de
  `docs/historico/contexto_do_planejamento_do_projeto_backend.md` foi
  preservado;
- conflitos entre catálogo, modelo, DI e fluxo foram resolvidos.

Para o congelamento:

- `docs/historico/roadmap_v1_legado.md` preserva integralmente o roadmap
  anterior;
- nenhuma correção retroativa é aplicada à V1 histórica.

Para a V2:

- cada fase decorre de dependências reais já documentadas;
- o roadmap não descobre novas regras;
- decisões condicionadas aparecem como gates;
- a finalidade didática declarada no `README.md` orienta explicações e
  aprendizados.

### Impactos já conhecidos no código

- `Plano` deixa de usar `idPlano` como identidade de domínio.
- `Demanda` deixa de expor `idPlano`.
- `Plano` aceita zero Demandas ativas.
- `Plano` representa fotografia vigente completa.
- `SequencialVersao` e metadado de homologação.
- `dataPublicacao` vira `dataConfirmacaoSincronizacao` no Domain.
- decisão deixa de depender apenas do número da versão.
- `DecisaoProcessamentoPlano` recebe `EXCLUIR_PCA`.
- adicionar estado tipado de publicação.
- adicionar Value Object `SequencialPcaPncp`.
- preservar estados e fatos externos por tipos internos abstratos.
- ajustar `PlanoRepository` para reconstrução e confirmação.
- revisar testes antigos baseados em FK/ID técnico.
- revisar ownership das portas.
- finalizar modelo de auditoria.

Nenhuma dessas alterações deve ser feita em lote sem:

- catálogo atualizado;
- documentação coerente;
- sprint correta;
- TDD RED -> GREEN -> REFACTOR;
- suíte completa verde.

---

## 18. Inconsistências conhecidas na documentação atual

Não tratar estes textos antigos como regras vigentes sem consolidação:

- “retificação reenvia o plano completo”;
- “nova versão sempre implica retificação”;
- “Plano não pode existir sem Demandas”;
- “identidade do Plano inclui SequencialVersao”;
- “FK_PLANO indica pertencimento exclusivo ao Plano vigente”;
- “dataPublicacao sozinha define todo o estado externo”;
- `HistoricoProcedimento` obrigatoriamente como Entity com Repository de Domain;
- Repository escolhendo modo de publicação;
- porta PNCP reduzida a publicar/retificar sem consulta;
- persistência local sem observação posterior do PNCP.

Esses pontos devem ser substituídos pelas decisões aprovadas, não simplesmente
apagados sem rastreabilidade.

---

## 19. Riscos e pendências importantes

### Bloqueantes antes da Infrastructure

- fechar DI-006 a DI-010;
- identificar o procedimento `PUBLICAÇÃO DE PLANO`;
- confirmar modelo de auditoria;
- confirmar mapeamento `ID_DEMANDA -> numeroItem` no PNCP de homologação;
- definir armazenamento do modo congelado;
- definir mecanismo de lock;
- definir estado local de PCA excluído;
- escolher mecanismo Oracle;
- escolher transporte HTTP;
- consolidar portas da Application.

### Restrições conhecidas da primeira versão

- ate 1000 itens por unidade externa;
- uma publicação legada reconciliada manualmente;
- sem coordenação distribuída Oracle/PNCP;
- sem compensação automática;
- falha PNCP confirmado/Oracle falhou exige tratamento consciente;
- locks Oracle durante HTTP, sujeitos a revisão futura.

### Premissas operacionais atuais

- baixo volume;
- cerca de 500 Demandas por exercício;
- baixa concorrência;
- processo administrativo;
- execução iniciada pelo Scriptcase;
- uma autenticação PNCP por execução;
- UASG distintas no modo não consolidado.

Se essas premissas mudarem, reabrir as ADRs afetadas.

---

## 20. Prompt sugerido para uma nova conversa

Usar o texto abaixo:

```text
Leia integralmente:

- .agents/AGENTS.md
- .agents/planejamento/checkpoints/handoff_planejamento_roadmap_v2.md
- os documentos atuais da pasta docs/

O arquivo de handoff preserva um planejamento em andamento. Não implemente
código nem reescreva o roadmap ainda.

Considere aprovadas as decisões DI-001 a DI-005 e não repita perguntas já
respondidas sem nova evidência.

Retome exatamente pela:

DI-006 - Auditoria e procedimento PUBLICAÇÃO DE PLANO

Faça uma pergunta de decisão por vez, explique as consequências arquiteturais
e registre mentalmente cada escolha para a consolidação posterior em:

- docs/vigentes/autoritativos/regras_de_negocio.md
- docs/vigentes/autoritativos/decisoes_de_integracao.md
- docs/vigentes/autoritativos/roadmap.md V2 (planejado, ainda não criado)

A primeira questão deve esclarecer se PAC_HISTORICOS registra apenas
procedimentos de negócio concluídos e confirmados ou também tentativas e
falhas.
```

---

## 21. Ponto exato de retomada

Última decisão aprovada:

```text
DI-005 - Estado do Plano após confirmação
```

Próximo tópico:

```text
DI-006 - Auditoria e procedimento PUBLICAÇÃO DE PLANO
```

Progresso estimado no encerramento da conversa anterior:

- decisões de integração: 5 de 10, aproximadamente 50%;
- planejamento completo do roadmap V2: aproximadamente 60%.
