# Arquitetura

**Classificação documental:** Apoio  
**Status:** Vigente  
**Autoridade/Escopo:** apoio para limites arquiteturais e direção de dependências, sem competir com regras, modelo ou Decisões de Integração

Este documento responde à pergunta: COMO o sistema será construído?

## Objetivo

Este projeto implementa o backend responsável pela publicação do Plano de Contratações Anual (PCA) no PNCP.

O projeto foi desenvolvido para servir como biblioteca PHP, sendo consumido inicialmente por uma aplicação Scriptcase.

Priorizar:

- simplicidade
- baixo acoplamento
- alta legibilidade
- facilidade de testes
- independência entre camadas

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

 ┌──────────────┐
 │ Presentation │
 └──────┬───────┘
        │
        ▼
 ┌──────────────┐
 │ Application  │
 └──────┬───────┘
        │
        ▼
 ┌──────────────┐
 │   Domain     │
 └──────────────┘

Infrastructure implementa interfaces definidas pelo Domain/Application.
O domínio nunca conhece infraestrutura.

---

# Modelagem de Domínio

O Domain deve representar conceitos reais do negócio de PCA e PNCP.

Evitar:

- modelagem baseada apenas no banco de dados
- entidades anêmicas
- serviços de domínio sem justificativa
- uso excessivo de DTOs dentro do Domain

Priorizar:

- linguagem ubíqua
- encapsulamento de invariantes
- comportamento próximo dos dados
- nomes alinhados ao negócio

O objetivo é que o código reflita como o processo funciona no mundo real.

---

# Responsabilidade de cada camada

## Domain

Contém:

- entidades
- value objects
- interfaces (Ports)
- serviços de domínio
- regras de negócio

Nunca acessa:

- Oracle
- HTTP
- JSON
- Scriptcase


## Application

Coordena os casos de uso.

Não conhece SQL.

Não conhece Oracle.

Não conhece HTTP.


## Infrastructure

Implementa recursos externos.

Exemplos:

- Oracle
- PNCP
- Logger
- Configuração


## Presentation

Adapta entradas e saídas.

No projeto atual:

Scriptcase → Controller → UseCase

---

# Convenções

## Repository

Somente acesso aos dados.

Nenhuma regra de negócio.


## Entity

Representa um conceito de negócio com identidade própria.

Deve:

- proteger invariantes
- encapsular comportamento
- evitar setters desnecessários
- manter estado consistente

Evitar entidades anêmicas.


## Value Object

Representa conceitos definidos apenas por seus valores.

Características:

- imutável
- sem identidade própria
- igualdade por valor

Exemplos possíveis:

- AnoPca
- CodigoPncp
- Cnpj
- SequencialItem


## UseCase

Orquestra o fluxo.

Não monta SQL.

Não monta JSON.


## Client

Responsável exclusivamente pela comunicação HTTP.


## PayloadBuilder

Converte objetos do domínio para contratos externos.

- Não contém regra de negócio.
- Não valida regras do domínio.
- Responsável apenas pelo mapeamento técnico.

## Presenter

Converte Response em estrutura adequada ao consumidor.

Inicialmente:

Scriptcase.

Futuramente:

- REST
- CLI
- outro consumidor


## Sistemas Externos

Conversões entre identificadores internos e identificadores de sistemas externos não pertencem ao Domain.

Exemplos:

- código Oracle
- identificador SGI
- identificador PNCP

Esses mapeamentos pertencem à Infrastructure.

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
