# Sprint 3.1 — PublicarPcaRequest

## Objetivo

Implementar exclusivamente:

```text
Sprint 3.1

□ Criar PublicarPcaRequest
□ Criar PublicarPcaRequestTest
```

---

# Contexto

Leia integralmente:

- README.md
- .agents/AGENTS.md
- docs/modelo_dominio.md
- docs/arquitetura.md
- docs/fluxo_implementacao.md
- docs/roadmap.md

As portas da Fase 2 já foram implementadas.

A próxima etapa é iniciar a camada Application.

---

# Papel do Request

PublicarPcaRequest não representa:

```text
HTTP
JSON
POST
Scriptcase
Controller
```

PublicarPcaRequest representa:

```text
os dados necessários para iniciar
o caso de uso de publicação do PCA.
```

---

# Dados Necessários

O caso de uso recebe:

```text
AnoExecucao
Cnpj
usuarioLogado
```

---

## AnoExecucao

Representa o ano das compras e contratações que serão processadas.

Utilizar o Value Object:

```php
AnoExecucao
```

---

## Cnpj

Representa o órgão responsável pela publicação.

Utilizar o Value Object:

```php
Cnpj
```

---

## usuarioLogado

Representa o usuário autenticado no sistema.

Origem:

```text
usr_login (Scriptcase)
```

Importante:

```text
usuarioLogado não é Representante.
```

O UseCase utilizará posteriormente:

```text
RepresentanteRepository
```

para localizar o Representante correspondente.

---

# O que NÃO pertence ao Request

Não incluir:

```text
Credenciais PNCP

Token

Ambiente

isAmbienteProducao

Representante

Lotacao

Uasg
```

Essas informações serão obtidas posteriormente pela Application ou Infrastructure.

---

# Regras

## Regra 1

PublicarPcaRequest é imutável.

PHP 8.1:

```text
Utilizar propriedades readonly.
```

Não utilizar:

```text
readonly class
```

---

## Regra 2

Request apenas transporta dados.

Não validar.

Não lançar exceções.

Não implementar regras de negócio.

---

## Regra 3

A validação pertence à próxima sprint:

```text
Sprint 3.2

PublicarPcaValidator
```

---

# Antes de Implementar

Apresente obrigatoriamente:

## Goal

O que será implementado.

## Context

- documentos consultados;
- informações identificadas;
- relação com o caso de uso.

## Constraints

- restrições arquiteturais;
- restrições técnicas;
- restrições do roadmap.

## Done When

Critérios de conclusão.

---

# Análise Obrigatória

Antes de escrever código:

1. Confirmar conclusão das Fases 1 e 2.
2. Explicar a responsabilidade de um Request na camada Application.
3. Explicar por que PublicarPcaRequest não deve validar dados.
4. Explicar por que usuarioLogado pertence ao Request.
5. Explicar por que Representante não pertence ao Request.
6. Justificar o uso de Value Objects para AnoExecucao e Cnpj.

---

# Processo TDD Obrigatório

Seguir:

```text
RED
↓
GREEN
↓
REFACTOR
```

Fluxo:

1. Criar os testes primeiro.
2. Explicar a falha esperada.
3. Implementar o mínimo necessário.
4. Executar os testes.
5. Refatorar se necessário.

---

# Cenários Esperados

Exemplos:

```text
✓ deveCriarPublicarPcaRequestValido

✓ devePermitirConsultarAnoExecucao

✓ devePermitirConsultarCnpj

✓ devePermitirConsultarUsuarioLogado

✓ deveSerImutavel
```

Não criar testes para regras que pertencem ao Validator.

---

# Escopo Permitido

Pode criar apenas:

```text
PublicarPcaRequest.php

PublicarPcaRequestTest.php
```

---

# Escopo Proibido

Não criar:

```text
Validator

UseCase

Response

Controller

Infrastructure

Oracle

PNCP

PayloadBuilder
```

Não antecipar as próximas sprints.

---

# Critérios de Conclusão

```text
PublicarPcaRequest criado

PublicarPcaRequestTest criado

Objeto imutável

Uso de AnoExecucao e Cnpj como Value Objects

Sem validações

Sem regras de negócio

Testes verdes
```

---

# Resposta Final

Apresente:

## Resumo

## Estrutura do Request

## Justificativa Arquitetural

- por que pertence à Application;
- por que não pertence ao Domain;
- por que não valida dados.

## Validação

- testes criados;
- comportamentos validados;
- resultado da execução.

## Arquivos Alterados

## Riscos ou Pontos de Atenção

Especialmente para:

```text
Sprint 3.2 — PublicarPcaValidator

Sprint 3.3 — PublicarPcaResponse

Sprint 3.4 — PublicarPcaUseCase
```