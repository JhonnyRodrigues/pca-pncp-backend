# Sprint 2.1 — PlanoRepository

## Importante: Contexto Atualizado

Antes de iniciar a implementação, releia integralmente:

- README.md
- .agents/AGENTS.md
- todos os arquivos da pasta docs/

Principalmente:

- docs/modelo_dominio.md
- docs/arquitetura.md
- docs/fluxo_implementacao.md
- docs/roadmap.md

O domínio foi refinado e o Aggregate Root Plano já foi implementado.

---

# Objetivo

Implementar exclusivamente:

```text
Sprint 2.1

□ Criar PlanoRepository
```

Nesta sprint deve existir apenas a definição da Porta (interface).

Não implementar Oracle.

Não implementar Infrastructure.

Não implementar consultas SQL.

Não implementar repositórios concretos.

---

# Contexto de Negócio

O Plano é o Aggregate Root do domínio.

Todo acesso ao agregado deve ocorrer através de:

```text
PlanoRepository
```

O caso de uso de publicação trabalha com:

```text
AnoExecucao
↓
Última versão de cada UnidadeOrcamentaria
↓
Decisão:
PUBLICAR
RETIFICAR
NÃO FAZER NADA
```

A decisão será baseada exclusivamente nos dados do próprio Plano:

```text
SequencialVersao
DataPublicacao
```

Não é necessário consultar HistoricoProcedimento para tomar essa decisão.

---

# Responsabilidade do Repository

O repositório deve ser capaz de:

## Recuperar

As últimas versões dos Planos para um determinado AnoExecucao.

A consulta deve retornar:

```text
Plano[]
```

Já reconstruídos como Aggregate Root completo:

```text
Plano
└── Demandas
```

O domínio não trabalha com Plano incompleto.

---

## Persistir

Alterações realizadas em um Plano após o processo de publicação.

Exemplos:

```text
DataPublicacao

SequencialPncp das Demandas
```

---

# O que NÃO deve existir

Não criar métodos genéricos apenas porque são comuns em CRUD.

Evitar:

```php
findAll()

findById()

delete()

update()
```

caso não sejam necessários ao domínio.

A interface deve refletir necessidades reais dos casos de uso documentados.

---

# Antes de Implementar

Apresente obrigatoriamente:

## Goal

O que será implementado.

## Context

- documentos consultados;
- regras encontradas;
- Aggregate Root relacionado;
- futuras dependências dos casos de uso.

## Constraints

- restrições arquiteturais;
- restrições técnicas;
- restrições de negócio.

## Done When

Critérios de conclusão.

---

# Análise Obrigatória

Antes de escrever código:

1. Confirmar que a Fase 1 foi concluída.
2. Explicar por que Plano possui Repository próprio.
3. Explicar por que Demanda não possui Repository nesta fase.
4. Explicar por que HistoricoProcedimento não faz parte desta sprint.
5. Justificar cada método definido na interface.
6. Relacionar cada método com uma necessidade real do domínio.

---

# Diretrizes Arquiteturais

Plano é o Aggregate Root.

Portanto:

```text
PlanoRepository
↓
opera sobre Plano
```

e não sobre:

```text
Demanda isoladamente
```

O domínio deve receber:

```text
Plano completo
```

e não estruturas parciais.

---

# Escopo Permitido

Pode criar apenas:

```text
src/Domain/Ports/PlanoRepository.php
```

ou localização equivalente já adotada pelo projeto.

---

# Escopo Proibido

Não criar:

```text
OraclePlanoRepository
OracleConnection
UseCase
Controller
DTO
Client
PayloadBuilder
Infrastructure
```

Não antecipar a Fase 3.

Não criar implementações concretas.

---

# Tratamento de Inconsistências

Caso a documentação atual gere dúvidas sobre:

- métodos necessários;
- responsabilidades do repositório;
- limites do agregado;

interrompa a implementação e apresente a análise antes de escrever a interface.

---

# Critérios de Conclusão

A sprint estará concluída quando:

```text
PlanoRepository criado

Métodos justificados por necessidades reais do domínio

Interface aderente ao Aggregate Root Plano

Nenhuma dependência de Oracle

Nenhuma implementação concreta criada

Clean Architecture preservada
```

---

# Resposta Final

Apresente:

## Resumo

## Métodos Definidos

Explique a responsabilidade de cada método.

## Justificativa Arquitetural

- por que a interface existe;
- por que pertence ao Domain;
- por que está associada ao Aggregate Root Plano.

## Validação

Verificações realizadas.

## Arquivos Alterados

## Riscos ou Pontos de Atenção

Especialmente para:

```text
Sprint 3.4 — PublicarPcaUseCase

Sprint 3.5 — RetificarPcaUseCase
```