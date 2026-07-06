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

# Fluxo

## 1. Receber solicitação

Entradas:

- ambiente (produção ou homologação)
- CNPJ do órgão
- ano de execução
- credenciais do PNCP

---

## 2. Validar entrada

Validar:

- ano informado
- CNPJ
- credenciais
- parâmetros obrigatórios

Caso inválido, interromper imediatamente.

---

## 3. Buscar demandas homologadas

Recuperar todas as demandas:

- homologadas
- ainda não publicadas
- pertencentes ao ano de execução informado

As demandas já devem ser retornadas agrupadas por Unidade Orçamentária.

Caso nenhuma demanda seja encontrada:

Retornar resposta informativa ao usuário.

---

## 4. Autenticar no PNCP

Realizar autenticação apenas uma vez.

O token obtido será reutilizado durante toda a execução.

---

## 5. Processar cada Unidade Orçamentária

Para cada unidade:

### 5.1 Iniciar transação

Abrir transação Oracle.

---

### 5.2 Obter o último plano

Recuperar o plano mais recente da unidade para o exercício informado.

A entidade Plano será responsável por decidir:

- Publicação
- Retificação

Essa decisão pertence exclusivamente ao domínio.

---

### 5.3 Construir o payload

Transformar o Plano em um payload compatível com o PNCP.

Essa responsabilidade pertence ao PncpPayloadBuilder.

---

### 5.4 Enviar ao PNCP

Conforme decisão do Plano:

- publicar
- ou retificar

---

### 5.5 Obter número sequencial

Consultar o endpoint responsável pelo número sequencial do plano publicado.

Esse número identifica a versão publicada no PNCP.

---

### 5.6 Atualizar banco

Persistir:

- data de publicação do plano
- número sequencial PNCP das demandas
- histórico da operação

---

### 5.7 Confirmar transação

Executar COMMIT.

---

### 5.8 Tratar falhas

Caso qualquer etapa falhe:

- executar ROLLBACK
- registrar erro
- adicionar resultado da unidade
- continuar a próxima unidade

---

## 6. Finalizar

Após todas as unidades:

Construir uma resposta contendo:

- unidades publicadas
- unidades com erro
- mensagens
- estatísticas

A resposta será encaminhada ao Presenter.

---

# Regras de Negócio

## Publicação

Quando existir uma versão homologada ainda não publicada.

---

## Retificação

Quando existir uma versão homologada mais recente que a última versão publicada.

A retificação será realizada enviando novamente o plano completo.

---

## Versões

Uma unidade pode possuir diversas versões homologadas.

Sempre deve ser utilizada a versão homologada mais recente.

---

## Independência entre unidades

Cada Unidade Orçamentária possui seu próprio plano.

O sucesso ou falha de uma unidade não interfere nas demais.

---

# Responsabilidades

Plano
- decidir publicação ou retificação

UseCase
- orquestrar o fluxo

Repository
- persistência

PncpClient
- comunicação HTTP

PncpPayloadBuilder
- construir payload

Presenter
- preparar resposta ao Scriptcase

Controller
- adaptar entrada e saída