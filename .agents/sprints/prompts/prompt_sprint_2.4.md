# Sprint 2.4 — LotacaoRepository

## Objetivo

Implementar exclusivamente:

```text
Sprint 2.4

□ Criar LotacaoRepository
```

---

## Contexto

Leia:

- README.md
- .agents/AGENTS.md
- docs/modelo_dominio.md
- docs/arquitetura.md
- docs/roadmap.md

O caso de uso de Publicação precisará registrar:

```text
HistoricoProcedimento
```

E o histórico exige:

```text
Representante
Lotacao
```

A Lotacao será obtida a partir do Representante.

---

## Responsabilidade da Porta

Resolver a Lotacao associada a um Representante.

Não criar CRUD genérico.

---

## Método Esperado

Espera-se algo equivalente a:

```php
obterPorRepresentante(
    Representante $representante
): ?Lotacao
```

Justifique qualquer variação.

---

## Restrições

Não criar:

```text
create
update
delete
findAll
```

Não implementar Oracle.

Não implementar SQL.

Não criar implementação concreta.

Criar apenas a Porta.

---

## Análise Obrigatória

Explique:

1. Por que HistoricoProcedimento depende de Lotacao.
2. Como Lotacao será utilizada na auditoria.
3. Por que a interface possui apenas os métodos definidos.

---

## Critérios de Conclusão

```text
LotacaoRepository criado

Interface mínima

Sem implementação concreta

Clean Architecture preservada
```

---

## Resposta Final

- Resumo
- Métodos Definidos
- Justificativa Arquitetural
- Arquivos Alterados
