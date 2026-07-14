Este documento responde à pergunta: O QUE existe no negócio?

# Modelo de Domínio

## Objetivo

Independentemente da tecnologia utilizada, descrever:
- os conceitos do negócio;
- entidades;
- regras;
- eventos;
- a decisão de Publicar vs Retificar;

---

# Resumo do Processo de Negócio

O Plano de Contratações Anual (PCA) nasce a partir das demandas de contratação registradas pelos diversos setores da organização.

O fluxo ocorre da seguinte forma:

1. Os setores registram suas demandas de contratação.
2. As demandas são avaliadas.
3. As demandas aprovadas são consolidadas.
4. O Presidente da organização realiza a homologação.
5. A homologação gera uma nova versão do Plano de Contratações Anual.

Após a homologação:

- a data de homologação é registrada;
- a versão do plano é definida;
- o plano torna-se elegível para publicação no PNCP;
- a data de publicação permanece vazia até que o processo de publicação seja executado.

A identificação lógica de uma **versão** de plano é composta por:

- AnoExecucao
- UnidadeOrcamentaria
- SequencialVersao

## Primeira Publicação

Quando não existe publicação anterior para uma determinada combinação de AnoExecucao e UnidadeOrcamentaria, o sistema deverá realizar uma Publicação.

A publicação corresponde ao primeiro envio do plano homologado ao PNCP.

## Retificação

Após a publicação, podem surgir novas demandas que precisem ser executadas dentro do mesmo AnoExecucao.

Essas novas demandas passam novamente pelo processo de avaliação e homologação individual.

Quando a homologação gera uma nova versão do plano para a mesma combinação de AnoExecucao e UnidadeOrcamentaria, o sistema deverá realizar uma Retificação.

A retificação consiste no envio da versão mais recente e completa do plano ao PNCP.

# Regra Central de Decisão

Para cada combinação de AnoExecucao e UnidadeOrcamentaria:

- o sistema deve analisar o cadastro histórico de Planos para decidir qual processo executar.
- se não existir histórico de Publicação de plano, o sistema deverá executar o Processo de Publicação;
- se já existir histórico de Publicação de plano, o sistema deverá executar o Processo de Retificação.
- se a última versão já corresponder à última versão publicada, nenhuma ação será necessária.

Essa decisão faz parte do domínio. Ela não deve ficar em Controller, Repository ou Infrastructure.

---

# AnoExecucao

Representa o ano exercício da execução (compra ou contratação) do item presente no Plano de Contratações Anual.

Regras:
- deve aceitar apenas o ano atual ou o próximo ano;
- anos anteriores ao ano atual são inválidos;
- anos posteriores ao próximo ano são inválidos;
- quando inválido, deve lançar exceção de domínio apropriada.


# UnidadeOrcamentaria


Representa uma Unidade Orçamentária responsável pela publicação do
Plano de Contratações Anual junto ao PNCP.

Possui:
- código
- nome

Regras:

- código é obrigatório;
- código deve ser um número inteiro positivo;
- código não pode ser zero;
- código identifica unicamente uma Unidade Orçamentária;
- nome é obrigatório;
- nome deve possuir ao menos 3 caracteres;
- espaços em branco nas extremidades devem ser removidos;
- nome pode conter acentos;
- nome pode conter números;
- o objeto é imutável após sua criação.

Observações:

- Toda Demanda pertence a uma Unidade Orçamentária.
- Todo Plano pertence a exatamente uma Unidade Orçamentária.
- O código utilizado pela publicação corresponde ao código da unidade
  orçamentária cadastrada para integração com o PNCP.
- Cada unidade possui seu próprio histórico de versões homologadas / publicadas.
- O código da UnidadeOrcamentaria representa o identificador utilizado
dentro do domínio. Eventuais conversões entre códigos do PCA, SGI, Oracle, RH_LOTACOES,
PNCP ou outros sistemas externos não pertencem ao domínio e devem ser tratadas fora das entidades de domínio.

---

# Plano

Representa o Plano de Contratações Anual de uma Unidade Orçamentária.

Um plano pertence a:

- uma Unidade Orçamentária
- um exercício

Um plano possui:

- versão
- data de homologação
- data de publicação
- demandas

Responsabilidades:

- informar sua versão;
- informar sua situação (homologado vira publicado);
- decidir se está apto para publicação;
- decidir se está apto para retificação.

# Demanda

Representa uma necessidade de contratação.

Uma demanda pertence a:

- um Plano
- uma Unidade Requisitante

Uma demanda possui:

- descrição do objeto material ou da contratação
- quantidade
- valor
- data desejada
- categoria
- situação
- fkPlano (foreign key para vincular cada demanda a um Plano)
- numeroSequencialPncp (informado após confirmação do processo de publicação/retificação)

Após processo de homologação no sistema interno PCA:
- número do plano (foreign key)

Após publicação no sistema externo PNCP:
- número sequencial PNCP

# Procedimento

Representa um tipo de ação formal executada durante o ciclo de vida do Planejamento de Compras Anual.

Possui:

- idProcedimento
- descricao

Características:

- é imutável;
- representa um catálogo de procedimentos de negócio;
- seu identificador possui significado de negócio;
- seu identificador é utilizado pelo sistema em validações e consultas;
- não representa uma ocorrência realizada;
- as ocorrências realizadas são registradas no Cadastro Histórico de Procedimentos.

Observação:

Procedimento não representa um evento executado por um usuário. Ele representa apenas o tipo de procedimento disponível no sistema.

Exemplos:

- Consolidação de Plano
- Desconsolidação de Plano
- Revisão de Demanda Avaliada
- Homologação do Planejamento
- Homologação de Demanda Extraordinária
- Cancelamento de Demanda Homologada
- Publicação de Plano

---

# Cadastro de Planos

Após o plano ser homologado, um novo registro é gerado no cadastro de plano. 

Possui:

- idPlano
- unidadeOrcamentaria
- anoExecucao
- versaoPlano
- tipoProcedimento
- dataHomologacao
- dataPublicacao

Ele influencia decisões de negócio:
- Existe publicação anterior? NÃO, então PUBLICAR
- Existe publicação anterior? SIM, então RETIFICAR

# Cadastro historico de Procedimentos

Cada registro histórico representa a execução de um Procedimento por um responsável do sistema.
O histórico possui finalidade exclusivamente de auditoria e rastreabilidade das ações realizadas no sistema.

Possui:

- procedimento executado
- responsável
- unidade organizacional
- data da ocorrência
- demanda relacionada (quando aplicável)
- mensagem de auditoria

Exemplo:

Procedimento: HOMOLOGAÇÃO DO PLANEJAMENTO
Mensagem: O Plano de Compras e Contratações da unidade orçamentária Procuradoria Jurídica para o ano 2027 foi publicado.

As regras de Publicação e Retificação não dependem da consulta ao histórico de procedimentos, sendo baseadas no cadastro de Planos e em seus dados de homologação/publicação.

---

# Origem do plano: Homologação

Apenas para contextualizar sobre o funcionamento do sistema PCA responsável por gerar os planos, 
a ação de homologação, realizada pelo presidente da autarquia, representa a consolidação das demandas aprovadas. (ou das demandas extraordinárias)

Somente após a homologação nasce uma versão oficial do Plano.

Ao homologar:

- é gerada uma nova versão do Plano;
- a data de homologação é registrada;
- o Plano torna-se elegível para publicação ou retificação.

# Processo de Publicação

Evento de negócio responsável pelo primeiro envio de um Plano homologado ao PNCP.

Regras:

- o plano deve estar homologado
- NÃO deve existir histórico de publicação anterior para a mesma combinação de AnoExecucao e UnidadeOrcamentaria

Resultado esperado:

- O sistema envia o plano completo, de cada UnidadeOrcamentaria.
- obtenção de novo número sequencial PNCP, confirmando que o plao foi publicado
- registros atualizados no banco de dados

# Processo de Retificação

Evento de negócio responsável pelo envio de nova versão de um Plano já publicado.

Regras:

- o plano deve estar homologado
- deve existir histórico de publicação anterior para a mesma combinação de AnoExecucao e UnidadeOrcamentaria
- o plano deve possuir versão mais recente que a publicada

Resultado esperado:

- O sistema envia novamente o plano completo somente da UnidadeOrcamentaria que teve alterações no plano inicial
- obtenção de novo número sequencial PNCP, confirmando que o plao foi publicado
- registros atualizados no banco de dados- 

Publicação e Retificação não são entidades e sim eventos/processos de negócio.

---

# Resultado dos processos de Publicação/Retificação

Representa o resultado obtido para uma Unidade Orçamentária.

Possui:

- código da unidade
- status
- mensagem

Pode representar:

- sucesso
- erro
- aviso

---

# Regras Gerais

## Versionamento

ma Unidade Orçamentária pode possuir diversas versões de Plano para um mesmo AnoExecucao.

A identificação lógica da versão é composta por:

- AnoExecucao
- UnidadeOrcamentaria
- SequencialVersao

Sempre será considerada a versão homologada mais recente.

---

## Publicação

Somente planos homologados podem ser publicados, quando não existir, no historico de procedimentos, publicações para o ano exercicio (anoExecucao).

---

## Retificação

Sempre envia o plano completo. Esse evento/processo somente deverá ser chamado quando já existir, no historico de procedimentos, publicações para o ano exercicio (anoExecucao).

---

## Independência

Cada Unidade Orçamentária é processada independentemente.

---

## Fonte da verdade

O PNCP é a autoridade sobre publicações.

O PCA é o sistema de origem, que armazena os dados em banco Oracle.

Em caso de divergência, prevalece o estado do PNCP.

---

# Objetivo Arquitetural

As entidades deste documento representam conceitos do domínio.

Elas não conhecem:

- Oracle
- SQL
- HTTP
- JSON
- Scriptcase
- PNCP Client

Essas responsabilidades pertencem às camadas de infraestrutura e aplicação.