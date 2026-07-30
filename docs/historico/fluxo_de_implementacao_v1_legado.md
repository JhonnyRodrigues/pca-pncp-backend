# FLUXO DE IMPLEMENTAÇÃO V1 (LEGADO)
> Arquivado em 2026-07-30.
> A versão V2 ainda não foi criada.
> Consultar `docs/README.md` para o status documental vigente.
> Não utilizar este arquivo como referência arquitetural atual.

---

Este documento responde à pergunta: COMO o negócio funciona?

---

# Fluxo de Implementação – Publicação de Planos no PNCP

## Objetivo

Implementar o backend responsável pela publicação e retificação dos Planos de Contratações Anuais (PCA) junto ao Portal Nacional de Contratações Públicas (PNCP), preservando a consistência entre o banco de dados Oracle e o ambiente do PNCP.

---

# Visão Geral

O sistema recebe uma solicitação de publicação proveniente da aplicação Scriptcase.
Uma única solicitação pode resultar na publicação de um ou mais planos, sendo um plano para cada Unidade Orçamentária (UASG).
Cada unidade é processada de forma independente, garantindo que falhas em uma unidade não impeçam a publicação das demais.
Cada unidade corresponde a uma transação independente no banco de dados.

---

# Regras de Negócio

## Publicação

- Quando existir uma versão homologada ainda não publicada.

## Retificação

- Quando existir uma versão homologada mais recente que a última versão publicada.
- A retificação será realizada enviando novamente o plano completo.

## Versões

- Uma unidade pode possuir diversas versões homologadas.
- Sempre deve ser utilizada a versão homologada mais recente.
- Versão é o conjunto de Ano Execução + Unidade Orçamentária + Sequencial

## Independência entre unidades

- Cada Unidade Orçamentária possui seu próprio plano.
- O sucesso ou falha de uma unidade não interfere nas demais.

---

# Responsabilidades

Domínio
- decidir entre Publicação, Retificação ou Nenhuma Ação
- aplicar as regras de negócio relacionadas ao estado dos Planos

UseCase
- orquestrar o fluxo

Repository
- persistência

PncpClient
- comunicação HTTP

PncpPayloadBuilder
- construir payload

Response DTO
- preparar resposta ao Scriptcase (módulo PCA do SGI)

Controller
- adaptar entrada e saída

---

# Fluxo

Resumo:

1. Receber solicitação
2. Validar entrada
3. Buscar planos elegíveis
4. Determinar ação
5. Publicar ou retificar
6. Atualizar o sistema
7. Retornar resultado


## 1. Receber solicitação

Entradas:

- ambiente (produção ou homologação)
- CNPJ do órgão
- ano de execução
- credenciais do PNCP


## 2. Validar entrada

Validar:

- ano informado
- CNPJ
- credenciais
- parâmetros obrigatórios

Caso inválido, interromper imediatamente.


## 3. Buscar demandas homologadas

- com situação homologada
- vinculada a um plano ainda não publicado
- pertencentes ao ano de execução informado
- para cada Unidade Orçamentária

As demandas já devem ser retornadas agrupadas por Unidade Orçamentária.
Caso nenhuma demanda seja encontrada, então retornar resposta informativa ao usuário.


## 4. Autenticar no PNCP

Realizar autenticação apenas uma vez.
O token obtido será reutilizado durante toda a execução.


## 5. Processar cada Unidade Orçamentária

Para cada unidade:

### 5.1 Preparar processamento da unidade

Garantir isolamento da operação da unidade (Abrir transação no banco de dados).


### 5.2 Obter o último plano

Recuperar o plano mais recente da unidade para o exercício informado.

O domínio será responsável por decidir:
- Publicação
- Retificação
- a decisão também poderá resultar em Nenhuma Ação

A decisão deverá considerar:
- Plano
- Cadastro histórico de Planos

Observação: Embora o SGI mantenha histórico completo de procedimentos para auditoria, a decisão entre Publicação e Retificação deverá ser realizada a partir do estado dos Planos homologados/publicados.
O histórico de procedimentos não constitui a principal fonte dessa decisão.

A regra não pertence ao Controller, Repository ou Infrastructure.

### 5.3 Construir o payload

Transformar o Plano em um payload compatível com o PNCP.

Essa responsabilidade pertence ao PncpPayloadBuilder.

### 5.4 Enviar ao PNCP

Conforme decisão do Plano:

- publicar
- ou retificar

### 5.5 Obter número sequencial

Consultar o endpoint responsável pelo número sequencial do plano publicado.

Esse número identifica a versão publicada no PNCP.

### 5.6 Atualizar banco

Persistir:

- data de publicação do plano
- número sequencial PNCP nas demandas
- histórico do procedimento PUBLICAÇÃO DE PLANO.

### 5.7 Confirmar persistência

Persistir as alterações realizadas (commitar).

### 5.8 Tratar falhas

Em caso de erro:

- descartar alterações parciais (rollback)
- registrar falha
- continuar o processamento das demais unidades


## 6. Finalizar

Após todas as unidades:

Construir uma resposta contendo:

- unidades publicadas
- unidades com erro
- mensagens

A resposta será encaminhada ao DTO / Presenter.

---

# Observações:

As regras que determinam quando um plano deve ser:
- publicado
- retificado
- ignorado (nenhuma ação)

estão descritas em detalhes no documento modelo_dominio.md.
