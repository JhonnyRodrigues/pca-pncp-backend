# Sprint 3.3 — Decisão de Processamento do Plano

## Objetivo

Implementar exclusivamente:

* Criar DecisaoProcessamentoPlano
* Evoluir o Aggregate Root Plano para decidir o processamento
* Criar testes para os cenários:
  - primeira publicação
  - retificação
  - nenhuma ação

Não implementar nenhuma sprint futura.

---

## Contexto

Leia integralmente:

- README.md
- AGENTS.md
- docs/arquitetura.md
- docs/modelo_dominio.md
- docs/fluxo_implementacao.md
- docs/roadmap.md

As Fases 1 e 2 já foram concluídas.

As Sprints 3.1 e 3.2 já foram implementadas.

---

## Motivação

O principal conhecimento de negócio deste backend é determinar:

> O que deve ser feito com o Plano?

Possíveis respostas:

- PUBLICAR
- RETIFICAR
- NENHUMA_ACAO

Essa decisão pertence ao domínio.

Ela não deve ficar em:

- Controller
- UseCase
- Repository
- Infrastructure
- PncpClient
- PayloadBuilder

---

## Decisão Arquitetural

Após análise do domínio foi concluído que:

A decisão depende apenas do estado do Plano mais recente.

A regra utiliza informações já pertencentes ao Aggregate Root:

- SequencialVersao
- DataPublicacao

Portanto:

✓ a decisão deve permanecer dentro do Aggregate Root Plano

Não criar:

✗ DecisorProcessamentoPlano
✗ Domain Service
✗ Strategy
✗ Factory

Evitar abstrações prematuras.

---

## Novo Conceito de Domínio

Criar:

```php
DecisaoProcessamentoPlano
```

Representa os únicos resultados possíveis da decisão de negócio:

- PUBLICAR
- RETIFICAR
- NENHUMA_ACAO

Esse conceito pertence ao Domain.

Ele representa linguagem do negócio.

Não representa infraestrutura.

Não representa Application.

---

## Evolução do Aggregate Plano

O Aggregate Root Plano deverá possuir comportamento responsável por determinar o processamento.

Exemplo conceitual:

```php
$plano->decidirProcessamento();
```

Retornando:

```php
DecisaoProcessamentoPlano
```

---

## Regras de Negócio

### Regra 1 — Primeira Publicação

Quando:

- SequencialVersao = 1
- DataPublicacao = null

Resultado:

```text
PUBLICAR
```

---

### Regra 2 — Retificação

Quando:

- SequencialVersao > 1
- DataPublicacao = null

Resultado:

```text
RETIFICAR
```

---

### Regra 3 — Nenhuma Ação

Quando:

- DataPublicacao estiver preenchida

Resultado:

```text
NENHUMA_ACAO
```

---

## O que NÃO Fazer

Não criar:

- UseCase
- Controller
- Repository
- Infrastructure
- Oracle
- PNCP
- Validator
- Response
- DTOs de Application

Não antecipar a Sprint 3.4.

---

## Antes de Implementar

Apresente obrigatoriamente:

### Goal

O que será implementado.

### Context

- documentos consultados;
- regras identificadas;
- relação com o Aggregate Plano.

### Constraints

- restrições arquiteturais;
- restrições do domínio;
- restrições do roadmap.

### Done When

Critérios de conclusão.

---

## Análise Obrigatória

Antes de escrever código:

- explicar por que a decisão pertence ao Aggregate Plano;
- explicar por que não foi criado um Domain Service;
- explicar por que a decisão não pertence ao UseCase;
- explicar por que DecisaoProcessamentoPlano pertence ao Domain;
- explicar como essa modelagem representa o negócio.

---

## Processo TDD Obrigatório

Seguir:

RED
↓
GREEN
↓
REFACTOR

Fluxo:

1. Criar testes.
2. Explicar a falha esperada.
3. Implementar o mínimo necessário.
4. Executar os testes.
5. Refatorar se necessário.

---

## Cenários de Teste Esperados

Criar testes para validar:

✓ deveDecidirPublicacaoQuandoVersaoForUmENaoEstiverPublicado

✓ deveDecidirRetificacaoQuandoVersaoForMaiorQueUmENaoEstiverPublicado

✓ deveDecidirNenhumaAcaoQuandoPlanoJaEstiverPublicado

✓ deveRetornarDecisaoProcessamentoPlano

---

## Escopo Permitido

Pode alterar apenas os elementos necessários para atender a Sprint 3.3.

Esperado:

- DecisaoProcessamentoPlano
- testes correspondentes
- evolução do Aggregate Plano
- testes do Aggregate Plano

Ajustes mínimos nos testes existentes são permitidos, caso necessários.

---

## Critérios de Conclusão

- DecisaoProcessamentoPlano criado
- Aggregate Plano decide o processamento
- Nenhum Domain Service criado
- Nenhuma regra colocada no UseCase
- Nenhuma regra colocada na Infrastructure
- Testes criados
- Testes verdes
- Código aderente ao domínio documentado

---

## Resposta Final

Apresente:

### Resumo

### Justificativa de Domínio (DDD)

- qual regra de negócio foi modelada;
- por que ela pertence ao Aggregate Plano;
- por que DecisaoProcessamentoPlano representa corretamente o negócio;
- alternativas descartadas e justificativa.

### Justificativa Arquitetural

- por que a solução pertence ao Domain;
- por que não pertence à Application;
- por que não pertence à Infrastructure.

### Validação

- testes criados;
- comportamentos validados;
- resultado da execução.

### Arquivos Alterados

### Riscos ou Pontos de Atenção

Especialmente para:

- Sprint 3.4 — ProcessarPublicacaoPcaUseCase
- Sprint 3.5 — PublicarPcaResponse