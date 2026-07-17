# Sprint 2.3 — RepresentanteRepository

## Objetivo

Implementar exclusivamente:

```text
Sprint 2.3

□ Criar RepresentanteRepository
```

---

## Contexto

Leia:

- README.md
- .agents/AGENTS.md
- docs/modelo_dominio.md
- docs/arquitetura.md
- docs/roadmap.md

O caso de uso de Publicação receberá:

```text
AnoExecucao
Cnpj
NomeUsuario
```

A partir de:

```text
NomeUsuario (usr_login)
```

o sistema deverá localizar:

```text
Representante
```

---

## Responsabilidade da Porta

Resolver um Representante a partir do usuário autenticado.

A interface deve refletir essa necessidade do domínio.

Não criar métodos genéricos de CRUD.

---

## Método Esperado

Espera-se algo equivalente a:

```php
obterPorNomeUsuario(string $nomeUsuario): ?Representante
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

1. Por que o caso de uso precisa de Representante.
2. Como o usuário autenticado se relaciona com Representante.
3. Por que a interface possui apenas os métodos definidos.

---

## Critérios de Conclusão

```text
RepresentanteRepository criado

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