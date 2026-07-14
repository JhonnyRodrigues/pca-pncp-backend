# Sprint 1.5 — Entity Procedimento

## Pré-requisitos

Leia integralmente:
- .agents/AGENTS.md
- todos os arquivos da pasta docs/

Em especial:

- docs/arquitetura.md
- docs/modelo_dominio.md
- docs/fluxo_implementacao.md
- docs/roadmap.md

---

## Objetivo

Implementar exclusivamente a próxima sprint pendente do roadmap:

```text
Sprint 1.5

□ Criar Entity Procedimento
□ Criar ProcedimentoTest
```

---

## Contexto de Negócio

O sistema PCA possui um catálogo de procedimentos que representam ações de negócio realizadas durante o ciclo de vida do planejamento.

Exemplos:

```text
CONSOLIDAÇÃO DE PLANO
DESCONSOLIDAÇÃO DE PLANO
REVISÃO DE DEMANDA AVALIADA
HOMOLOGAÇÃO DO PLANEJAMENTO
HOMOLOGAÇÃO DE DEMANDA EXTRAORDINÁRIA
CANCELAMENTO DE DEMANDA HOMOLOGADA
PUBLICAÇÃO DE PLANO
```

Importante:

```text
Procedimento = catálogo

Histórico = registro da execução do procedimento
```

Exemplo:

Procedimento:

```text
HOMOLOGAÇÃO DO PLANEJAMENTO
```

Registro histórico:

```text
Data: 23/06/2026
Responsável: 709

Mensagem:
"O Plano de Compras e Contratações da unidade
orçamentária Procuradoria Jurídica para o ano
2027 foi publicado."
```

---

## Regras do Domínio

A Entity Procedimento deve representar exclusivamente:

```text
idProcedimento
descricao
```

Regras conhecidas:

### Regra 1

Procedimento possui:

```text
idProcedimento
descricao
```

### Regra 2

O identificador possui significado de negócio.

Não é apenas uma chave técnica do banco.

### Regra 3

Procedimento é imutável.

Não criar setters.

### Regra 4

Procedimento representa um catálogo fechado do sistema.

### Regra 5

Procedimento não representa uma ocorrência.

As ocorrências pertencem ao histórico.

### Regra 6

Procedimento não conhece:

```text
Plano
Demanda
Histórico
Oracle
PNCP
Infrastructure
Application
Presentation
```

---

## Antes de Implementar

Apresente obrigatoriamente:

### Goal

O que será implementado.

### Context

- documentos consultados;
- regras encontradas;
- componentes afetados.

### Constraints

- restrições arquiteturais;
- restrições técnicas;
- restrições de negócio.

### Done When

Critérios de conclusão da sprint.

---

## Análise Obrigatória

Antes de gerar testes ou código:

1. Verifique a próxima sprint pendente no roadmap.
2. Confirme que a Sprint 1.4 está concluída.
3. Liste as regras de negócio aplicáveis.
4. Informe em qual documento cada regra foi encontrada.
5. Explique o papel da Entity Procedimento.
6. Explique por que ela pertence ao Domain.
7. Explique quais comportamentos serão testados.

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

Fluxo:

1. Identificar regras de negócio.
2. Criar os testes primeiro.
3. Explicar a falha esperada.
4. Implementar apenas o código mínimo necessário.
5. Reexecutar o raciocínio dos testes.
6. Refatorar apenas se necessário.
7. Verificar aderência à documentação.

---

## Restrições

Não implementar:

```text
Plano
Repository
Port
UseCase
Controller
DTO
Service
Factory
Infrastructure
Client
PayloadBuilder
Presenter
```

Não antecipar a Sprint 1.6.

Não criar regras de negócio não documentadas.

Não criar validações arbitrárias.

Não realizar refatorações fora do escopo da sprint.

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

- Procedimento.php estiver implementada;
- ProcedimentoTest.php estiver implementado;
- os testes cobrirem as regras documentadas;
- a entidade for imutável;
- nenhuma funcionalidade futura tiver sido antecipada;
- a solução respeitar DDD, TDD e Clean Architecture.

---

## Formato da Resposta Final

### Resumo

O que foi implementado.

### Regras de Negócio Aplicadas

Lista das regras utilizadas e suas respectivas fontes.

### Justificativa Arquitetural

- responsabilidade da classe;
- motivo de existir;
- motivo de pertencer ao Domain.

### Validação

- testes criados;
- comportamentos validados;
- resultado obtido.

### Arquivos Alterados

Lista dos arquivos modificados e justificativa.

### Riscos ou Pontos de Atenção

Limitações, dúvidas identificadas e impactos para a Sprint 1.6.