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

# Cenários de Negócio

## Primeira Publicação

Quando não existe publicação anterior para uma combinação de AnoExecucao + UnidadeOrcamentaria, o sistema deverá realizar uma Publicação.

A publicação corresponde ao primeiro envio do plano homologado ao PNCP.

## Retificação

Após a publicação, novas demandas podem ser homologadas.

Quando isso acontece, gera uma nova versão do Plano para a mesma combinação de: AnoExecucao + UnidadeOrcamentaria, então o sistema realiza uma Retificação.

A retificação consiste no envio da versão mais recente e completa do Plano ao PNCP.

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

---

# Regra Central de Decisão

Analisar histórico de Planos para decidir qual processo executar:
Para cada UnidadeOrcamentaria:

- obter a última versão homologada do Plano;
- se SequencialVersao = 1 e DataPublicacao = null, executar Publicação;
- se SequencialVersao > 1 e DataPublicacao = null, executar Retificação;
- se DataPublicacao estiver preenchida, nenhuma ação será necessária.

A decisão entre Publicação e Retificação pertence ao domínio,
mas depende do cadastro de planos e não pode ser determinada apenas pelo estado isolado de um Plano.

Essa decisão faz parte do domínio. Ela não deve ficar em Controller, Repository ou Infrastructure.

---

# Value Objects

## Cnpj

Representa o Cadastro Nacional da Pessoa Jurídica do órgão responsável pela publicação do Plano de Contratações Anual junto ao PNCP.

Possui:

- numero

Regras:

- é obrigatório;
- deve conter exatamente 14 dígitos;
- caracteres não numéricos devem ser removidos antes da validação;
- deve ser validado pelo algoritmo oficial do CNPJ;
- quando inválido, deve lançar exceção de domínio apropriada;
- o objeto é imutável.

Observações:

- o mesmo órgão pode possuir diversas UnidadesOrcamentarias;
- o CNPJ identifica o órgão responsável pela publicação perante o PNCP;
- o CNPJ faz parte da entrada do processo de Publicação e Retificação;
- o objeto representa apenas o conceito de CNPJ dentro do domínio, sem conhecer banco de dados, APIs ou formatos externos.

## AnoExecucao

Representa o ano exercício da execução (compra ou contratação) do item presente no Plano de Contratações Anual.

Regras:
- deve aceitar apenas o ano atual ou o próximo ano;
- anos anteriores ao ano atual são inválidos;
- anos posteriores ao próximo ano são inválidos;
- quando inválido, deve lançar exceção de domínio apropriada.


## Representante

Representa a pessoa responsável pela execução de uma ação registrada pelo sistema.

Possui:

- id
- nome

Regras:

- id é obrigatório;
- nome é obrigatório;
- o objeto é imutável.

Observações:

- Representante identifica o responsável por uma ação registrada em um HistoricoProcedimento;
- pode representar operador, avaliador, consolidador ou presidente;
- para os procedimentos de Publicação e Retificação, os dados do representante serão obtidos a partir do usuário autenticado no sistema.


## Lotacao

Representa uma unidade organizacional da autarquia.

Possui:

- id
- descricao

Regras:

- id é obrigatório;
- descrição é obrigatória;
- lotação deve ser um número inteiro positivo maior que zero;
- o objeto é imutável;

Importante:

Lotação e UnidadeOrcamentaria são conceitos distintos.

Nem toda Lotação é uma UnidadeOrcamentaria.

Exemplos de lotações:

- Presidência
- Procuradoria Jurídica
- Diretoria Geral Administrativa e Financeira
- Coordenadoria de Tecnologia


## UnidadeOrcamentaria

Representa uma Unidade Orçamentária responsável pela publicação do
Plano de Contratações Anual junto ao PNCP.

Possui:
- lotacao
- descricao
- UASG

Regras:

- lotacao, descricao e UASG são obrigatórios;
- UASG identifica a unidade compradora perante o Governo Federal;
- o objeto é imutável após sua criação.
- descricao deve possuir ao menos 3 caracteres;
- espaços em branco nas extremidades devem ser removidos;
- descricao pode conter acentos e números;

Observações:

- Toda Demanda pertence a uma Unidade Orçamentária.
- Todo Plano pertence a exatamente uma Unidade Orçamentária.
- Cada unidade possui seu próprio histórico de versões homologadas e publicadas.
- Toda UnidadeOrcamentaria utilizada no processo de publicação deve possuir uma UASG associada. Não existe unidadeOrcamentaria válida sem UASG
- UASG é Unidade Administrativa de Serviços Gerais, um código criado pelo Governo Federal para identificar o Óregão ou o Setor exato que está realizando a compra. Utilizado para diferenciar as entidades compradoras
- A UASG é uma informação de configuração utilizada para integração com o PNCP.


---

# Entidades 

## Demanda

Representa uma necessidade de contratação.

Uma demanda pertence a:

- um Plano
- uma Unidade Requisitante

Uma demanda possui:

- descrição do objeto material ou da contratação
- quantidade
- valor
- dataDesejada
- AnoExecucao (derivada de dataDesejada)
- categoria
- situação
- fkPlano (foreign key para vincular cada demanda a um Plano)
- numeroSequencialPncp (informado após confirmação do processo de publicação/retificação)

Regras:

- quantidade deve ser maior que zero;
- origem deve ser:
  - N (Normal)
  - E (Extraordinária);
- categoria deve ser:
  - 1 (Material)
  - 2 (Serviço).


Após processo de homologação no sistema interno PCA:
- número do plano (foreign key)

Após publicação no sistema externo PNCP:
- número sequencial PNCP


## Procedimento

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


Importante:

Os processos de Publicação e Retificação realizados junto ao PNCP
são registrados utilizando o procedimento: PUBLICAÇÃO DE PLANO

A distinção entre Publicação inicial e Retificação
é uma decisão interna do backend. Para fins de auditoria do negócio,
ambos os eventos representam uma publicação
de informações ao PNCP.

## HistoricoProcedimento

Representa o registro da execução de um Procedimento.
O histórico possui finalidade exclusivamente de auditoria e rastreabilidade das ações realizadas no sistema.

Possui:

- procedimento
- representante
- lotacao (unidade organizacional)
- dataInsercao (data da ocorrência)
- mensagem de auditoria

Opcionalmente:
- idDemanda

Responsabilidades:

- registrar auditoria das ações realizadas;
- preservar rastreabilidade das operações;
- associar um Procedimento à sua execução.

Exemplo:
Procedimento: PUBLICAÇÃO DE PLANO
Mensagem: O Plano de Compras e Contratações da unidade orçamentária Procuradoria Jurídica para o ano 2027 foi publicado.

Regras:

- a mensagem é obrigatória para o procedimento PUBLICAÇÃO DE PLANO;
- a obrigatoriedade da mensagem para outros procedimentos depende das regras específicas de cada procedimento.

Observações:

- cada registro representa um evento ocorrido;
- o histórico não representa o catálogo de procedimentos;
- o histórico representa a execução de um procedimento.


As regras de Publicação e Retificação não dependem da consulta ao histórico de procedimentos, sendo baseadas no cadastro de Planos e em seus dados de homologação/publicação.


---

# Aggregate Root

## Plano

Representa o Plano de Contratações Anual de uma Unidade Orçamentária.

A identidade lógica de um Plano é composta por:

- AnoExecucao
- UnidadeOrcamentaria
- SequencialVersao

Além disso, possui:

- idPlano
- DataHomologacao
- DataPublicacao
- Demandas


Regras:

- deve possuir ao menos uma Demanda;
- não pode existir sem Demandas;
- SequencialVersao deve ser maior que zero;
- todas as Demandas do Plano devem referenciar o mesmo idPlano;
- todas as Demandas do Plano devem pertencer à mesma UnidadeOrcamentaria;
- todas as Demandas do Plano devem pertencer ao mesmo AnoExecucao;
- DataHomologacao é obrigatória;
- DataPublicacao é opcional.
Observação:
idPlano é utilizado para estabelecer o vínculo entre Plano e Demanda dentro do domínio.
Embora a identificação lógica da versão seja composta por AnoExecucao, UnidadeOrcamentaria e SequencialVersao, o identificador idPlano também faz parte do modelo de domínio.


Origem:

- Um Plano não existe antes da homologação.
- A homologação realizada pela Presidência gera
uma nova versão do Plano.

Responsabilidades:

- informar sua versão;
- informar sua situação (homologado vira publicado);
- decidir se está apto para publicação;
- decidir se está apto para retificação.

---

# Processos de Negócio

## Publicação

Primeiro envio de um Plano homologado ao PNCP.

Regras:

- deve existir Plano homologado;
- não deve existir publicação anterior para a combinacao unidadeOrcamentaria + AnoExecucao.

Resultado:

- plano enviado ao PNCP;
- sequencial PNCP obtido;
- demandas atualizadas com sequencialPncp;
- histórico registrado.

## Retificação

Envio de nova versão de Plano já publicado.

Regras:

- deve existir publicação anterior para a combinação:UnidadeOrcamentaria + AnoExecucao;
- o plano deve possuir versão mais recente que a última versão publicada

Resultado:

- plano completo reenviado ao PNCP;
- novo sequencial PNCP obtido;
- demandas atualizadas com sequencialPncp;
- histórico registrado.


---

# Princípios de Negócio

## Independência das Unidades

Cada Unidade Orçamentária é processada independentemente.

## Fonte da verdade

O PNCP é a autoridade sobre publicações.
O PCA é o sistema de origem, que armazena os dados em banco Oracle.
Em caso de divergência, prevalece o estado do PNCP.