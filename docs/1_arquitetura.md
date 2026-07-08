Este documento responde à pergunta: COMO o sistema será construído?

# Arquitetura

## Objetivo

Este projeto implementa o backend responsável pela publicação do Plano de Contratações Anual (PCA) no PNCP.

O projeto foi desenvolvido para servir como biblioteca PHP, sendo consumido inicialmente por uma aplicação Scriptcase.

---

# Princípios

Este projeto segue:

- Clean Architecture
- SOLID
- PSR-12
- PSR-4
- Injeção de Dependências
- TDD

---

# Estrutura

```
src/

Application/
Domain/
Infrastructure/
Presentation/
```

---

# Dependências entre camadas

A direção das dependências é sempre para dentro.

```
Presentation
        │
        ▼
Application
        │
        ▼
Domain

Infrastructure
        ▲
        │
Application
```

O domínio nunca conhece infraestrutura.

---

# Responsabilidade de cada camada

## Domain

Contém:

- entidades
- regras de negócio
- interfaces (Ports)

Nunca acessa:

- Oracle
- HTTP
- JSON
- Scriptcase

---

## Application

Coordena os casos de uso.

Não conhece SQL.

Não conhece Oracle.

Não conhece HTTP.

---

## Infrastructure

Implementa recursos externos.

Exemplos:

- Oracle
- PNCP
- Logger
- Configuração

---

## Presentation

Adapta entradas e saídas.

No projeto atual:

Scriptcase → Controller → UseCase

---

# Convenções

## Repository

Somente acesso aos dados.

Nenhuma regra de negócio.

---

## Entity

Contém comportamento.

Evitar entidades anêmicas.

---

## UseCase

Orquestra o fluxo.

Não monta SQL.

Não monta JSON.

---

## Client

Responsável exclusivamente pela comunicação HTTP.

---

## PayloadBuilder

Converte entidades do domínio para o formato esperado pelo PNCP.

---

## Presenter

Converte Response em estrutura adequada ao consumidor.

Inicialmente:

Scriptcase.

Futuramente:

- REST
- CLI
- outro consumidor

---

# Testes

Toda classe criada deverá possuir teste correspondente.

Fluxo:

Teste

↓

Falha

↓

Implementação mínima

↓

Teste passa

↓

Refatoração

---

# Objetivo

Priorizar:

- simplicidade
- baixo acoplamento
- alta legibilidade
- facilidade de testes
- independência entre camadas