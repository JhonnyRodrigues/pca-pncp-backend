# Sprint 2.2 — HistoricoProcedimentoRepository

## Objetivo

Implementar exclusivamente:

```text
Sprint 2.2

□ Criar HistoricoProcedimentoRepository
```

---

## Contexto

Leia:

- README.md
- .agents/AGENTS.md
- docs/modelo_dominio.md
- docs/arquitetura.md
- docs/roadmap.md

O domínio já definiu que:

```text
HistoricoProcedimento é apenas auditoria.
```

A decisão entre:

```text
PUBLICAR
RETIFICAR
NÃO FAZER NADA
```

não depende do histórico.

Ela depende apenas dos Planos.

---

## Responsabilidade da Porta

Registrar eventos de auditoria do domínio.

Exemplos:

```text
PUBLICAÇÃO DE PLANO

HOMOLOGAÇÃO DO PLANEJAMENTO

DESCONSOLIDAÇÃO DE PLANO
```

---

## Método Esperado

A linguagem do domínio utiliza o verbo:

```text
registrar
```

Portanto espera-se algo equivalente a:

```php
registrar(HistoricoProcedimento $historico): void;
```

Justifique qualquer variação.

---

## Restrições

Não criar:

```text
consultas
buscas
listagens
findById
findAll
update
delete
```

O histórico é imutável e não participa das decisões do domínio.

Não implementar Oracle.

Não implementar infraestrutura.

Não implementar classes concretas.

Criar apenas a Porta.

---

## Antes de Implementar

Apresente:

### Goal

### Context

### Constraints

### Done When

---

## Análise Obrigatória

Explique:

1. Por que HistoricoProcedimento possui Repository próprio.
2. Por que ele não pertence ao Aggregate Root Plano.
3. Por que a interface possui apenas operações de registro.
4. Como essa porta será utilizada futuramente pelos casos de uso.

---

## Critérios de Conclusão

```text
HistoricoProcedimentoRepository criado

Interface mínima e aderente ao domínio

Nenhuma implementação concreta criada

Clean Architecture preservada
```

---

## Resposta Final

Apresente:

- Resumo
- Métodos Definidos
- Justificativa Arquitetural
- Validação
- Arquivos Alterados
- Riscos ou Pontos de Atenção