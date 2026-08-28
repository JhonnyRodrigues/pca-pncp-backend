# Sprint 1.6 — Value Object Lotacao

## Importante: Documentação Atualizada

Antes de iniciar a análise desta sprint, releia toda a documentação do projeto.

O domínio foi refinado e alguns conceitos foram alterados desde as últimas implementações.

Principais mudanças:

### Modelo de Domínio

- Foi criada a distinção formal entre:
  - UnidadeOrcamentaria
  - Lotacao

Importante:

```text
Nem toda Lotacao é uma UnidadeOrcamentaria.
```

UnidadeOrcamentaria representa uma unidade responsável pela publicação do PCA junto ao PNCP.

Lotacao representa uma unidade organizacional da autarquia.

Exemplos de Lotação:

```text
Presidência
Procuradoria Jurídica
Diretoria Geral Administrativa e Financeira
Coordenadoria de Tecnologia
```

### Roadmap

A sequência atual da Fase 1 passou a ser:

```text
Sprint 1.4
✓ Demanda

Sprint 1.5
✓ Procedimento

Sprint 1.6
□ Lotacao

Sprint 1.7
□ Representante

Sprint 1.8
□ HistoricoProcedimento

Sprint 1.9
□ Plano
```

HistoricoProcedimento dependerá de:

```text
Procedimento
Lotacao
Representante
```

Portanto a Sprint 1.6 deve implementar apenas o Value Object Lotacao.

---

## Pré-requisitos

Leia integralmente:

- README.md
- .agents/AGENTS.md
- todos os arquivos da pasta docs/

Especialmente:

- docs/arquitetura.md
- docs/modelo_dominio.md
- docs/fluxo_implementacao.md
- docs/roadmap.md

---

## Objetivo

Implementar exclusivamente a próxima sprint pendente definida no roadmap:

```text
Sprint 1.6

□ Criar Value Object Lotacao
□ LotacaoTest
```

---

## Contexto de Negócio

Lotacao representa uma unidade organizacional da autarquia.

Exemplos:

```text
Presidência
Procuradoria Jurídica
Diretoria Geral Administrativa e Financeira
Coordenadoria de Tecnologia
```

Importante:

```text
Lotacao ≠ UnidadeOrcamentaria
```

Nem toda Lotacao é uma UnidadeOrcamentaria.

Uma Lotacao representa uma estrutura organizacional.

Uma UnidadeOrcamentaria representa uma estrutura responsável por orçamento e publicação do PCA.

---

## Regras do Domínio

Lotacao possui:

```text
id
descricao
```

### Regra 1

O id é obrigatório.

### Regra 2

A descrição é obrigatória.

### Regra 3

Lotacao é imutável.

### Regra 4

Lotacao é um Value Object.

### Regra 5

Lotacao não possui comportamento relacionado a:

```text
Oracle
PNCP
Plano
Demanda
HistoricoProcedimento
Application
Infrastructure
Presentation
```

### Regra 6

Lotacao e UnidadeOrcamentaria representam conceitos diferentes.

Não reutilizar UnidadeOrcamentaria para representar Lotacao.

---

## Antes de Implementar

Apresente obrigatoriamente:

### Goal

O que será implementado.

### Context

- documentos consultados;
- regras encontradas;
- arquivos relacionados;
- componentes impactados.

### Constraints

- restrições arquiteturais;
- restrições técnicas;
- restrições de negócio.

### Done When

Critérios de conclusão da sprint.

---

## Análise Obrigatória

Antes de gerar qualquer teste ou código:

1. Verifique a próxima sprint pendente no roadmap.
2. Confirme que a Sprint 1.5 está concluída.
3. Liste todas as regras de negócio aplicáveis.
4. Informe em qual documento cada regra foi encontrada.
5. Explique a responsabilidade do Value Object Lotacao.
6. Explique por que ele pertence ao Domain.
7. Explique quais comportamentos serão validados em LotacaoTest.

---

## Processo TDD Obrigatório

Seguir rigorosamente:

```text
RED
↓
GREEN
↓
REFACTOR
```

Fluxo obrigatório:

1. Identificar as regras de negócio.
2. Criar os testes primeiro.
3. Explicar a falha esperada.
4. Implementar apenas o código mínimo necessário.
5. Reexecutar o raciocínio dos testes.
6. Refatorar apenas se necessário.
7. Verificar aderência à documentação.

---

## Comportamentos Esperados para o Teste

Validar apenas regras documentadas.

Sugestões de cenários:

```text
✓ deveCriarLotacaoValida

✓ devePermitirConsultarIdEDescricao

✓ deveSerImutavel
```

Não criar testes para regras que não estejam documentadas.

---

## Escopo Permitido

Pode criar apenas:

```text
Lotacao.php
LotacaoTest.php
```

e eventuais ajustes mínimos necessários para suporte da sprint.

---

## Escopo Proibido

Não criar:

```text
Representante
HistoricoProcedimento
Plano
Repository
UseCase
DTO
Controller
Service
Factory
Client
Infrastructure
PayloadBuilder
Presenter
```

Não antecipar:

```text
Sprint 1.7
Sprint 1.8
Sprint 1.9
```

Não realizar refatorações fora do escopo da sprint.

Não criar regras de negócio não documentadas.

Não criar validações arbitrárias.

---

## Tratamento de Inconsistências

Caso encontre:

- regras ausentes;
- regras contraditórias;
- regras ambíguas;
- conflito entre documentação e roadmap;

interrompa a implementação e apresente a análise antes de escrever qualquer teste ou código.

---

## Critérios de Conclusão

A sprint estará concluída quando:

- Lotacao.php estiver implementada;
- LotacaoTest.php estiver implementado;
- os testes cobrirem as regras documentadas;
- o objeto for imutável;
- nenhuma funcionalidade futura tiver sido antecipada;
- a solução respeitar DDD, TDD e Clean Architecture.

---

## Resposta Final

Apresente obrigatoriamente:

### Resumo

O que foi implementado.

### Regras de Negócio Aplicadas

Lista das regras utilizadas e sua origem na documentação.

### Justificativa Arquitetural

- responsabilidade da classe;
- motivo de existir;
- motivo de pertencer ao Domain;
- motivo de ser um Value Object.

### Validação

- testes criados;
- comportamentos validados;
- resultado obtido.

### Arquivos Alterados

Lista dos arquivos modificados e justificativa.

### Riscos ou Pontos de Atenção

Limitações identificadas e impactos para:

```text
Sprint 1.7 — Representante
Sprint 1.8 — HistoricoProcedimento
Sprint 1.9 — Plano
```