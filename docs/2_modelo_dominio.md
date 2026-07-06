# Modelo de Domínio

## Objetivo

Descrever os principais conceitos de negócio utilizados pelo sistema, independentemente da tecnologia utilizada.

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

- decidir se deve publicar
- decidir se deve retificar
- conhecer sua versão
- conhecer a última publicação

---

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

Após homologação no sistema interno PCA:

- número do plano (foreign key)

Após publicação no sistema externo PNCP:

- número sequencial PNCP

---

# Unidade Orçamentária

Representa uma UASG responsável pela publicação de um plano.

Cada unidade possui:

- código
- nome

Cada unidade possui seu próprio histórico de versões.

---

# Publicação

Primeiro envio de um plano homologado ao PNCP.

Resultado esperado:

- plano publicado
- número sequencial obtido
- banco atualizado

---

# Retificação

Publicação de uma nova versão de plano homologada.

O sistema envia novamente o plano completo.

O PNCP cria um novo número sequencial.

---

# Histórico

Registra todas as operações relevantes.

Exemplos:

- publicação
- retificação
- falhas
- mensagens

---

# Resultado da Publicação

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

Uma Unidade Orçamentária pode possuir diversas versões de plano.

Sempre será utilizada para publicar a versão homologada mais recente.

---

## Publicação

Somente planos homologados podem ser publicados.

---

## Retificação

Sempre envia o plano completo.

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