# Sprint 3.4 — ProcessarPublicacaoPcaUseCase

## Objetivo

Implementar exclusivamente:

□ ProcessarPublicacaoPcaUseCase
□ ProcessarPublicacaoPcaUseCaseTest

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

As Sprints já implementadas:

- Sprint 3.1 — PublicarPcaRequest
- Sprint 3.2 — PublicarPcaValidator
- Sprint 3.3 — DecisaoProcessamentoPlano + Plano::decidirProcessamento()

---

## Objetivo Arquitetural

Esta sprint existe para provar que a camada Application consegue orquestrar o Domain sem depender da Infrastructure.

Fluxo esperado:

Request
↓
Validator
↓
PlanoRepository
↓
Plano::decidirProcessamento()
↓
Resultado simples de aplicação

---

## Responsabilidade do UseCase

ProcessarPublicacaoPcaUseCase deve:

- receber PublicarPcaRequest;
- validar a entrada utilizando PublicarPcaValidator;
- consultar PlanoRepository;
- obter os Planos homologados elegíveis para publicação;
- perguntar a cada Plano qual decisão deve ser executada;
- organizar o resultado da execução;
- retornar uma estrutura simples e provisória de aplicação.

---

## Importante

Nesta sprint o UseCase deve trabalhar com múltiplos Planos.

Uma solicitação de publicação pode processar:

- nenhuma UnidadeOrcamentaria;
- uma UnidadeOrcamentaria;
- diversas UnidadesOrcamentarias.

O UseCase deve estar preparado para todos os cenários.

---

## PlanoRepository

Utilizar exclusivamente:

```php
obterPlanosHomologadosParaProcessamento()
```

Objetivo:

Recuperar os Planos homologados aptos a participar do processo de publicação.

Não utilizar:

- HistoricoProcedimentoRepository
- RepresentanteRepository
- LotacaoRepository

Essas portas não fazem parte desta sprint.

---

## Relação com o Domínio

O UseCase NÃO decide:

- PUBLICAR
- RETIFICAR
- NENHUMA_ACAO

A decisão já pertence ao Aggregate Root Plano.

Exemplo conceitual:

```php
$decisao = $plano->decidirProcessamento();
```

O UseCase apenas consome o resultado.

---

## Estrutura de Retorno

Não implementar:

- PublicarPcaResponse
- ResultadoProcessamentoPlano

Esses objetos pertencem à Sprint 3.5.

Nesta sprint utilizar apenas uma estrutura simples e provisória que permita validar a orquestração.

Exemplo conceitual:

```text
Plano A → PUBLICAR
Plano B → RETIFICAR
Plano C → NENHUMA_ACAO
```

A forma exata da estrutura fica a critério da implementação.

---

## O que NÃO Fazer

Não criar:

- Controller
- Oracle
- PncpAuthenticator
- PncpClient
- PayloadBuilder
- PublicarPcaResponse
- ResultadoProcessamentoPlano
- Presenter
- Infrastructure concreta

Não antecipar a Sprint 3.5.

Não antecipar a Fase 4.

---

## Configuração Futura

Existe uma possível evolução futura:

```text
PUBLICAR_EM_UNICA_UASG = SIM
PUBLICAR_EM_UNICA_UASG = NÃO
```

Nesta sprint:

- não implementar essa funcionalidade;
- não criar lógica de consolidação;
- apenas manter o UseCase preparado para trabalhar com múltiplos Planos.

Essa configuração altera apenas o fluxo de publicação e não altera o modelo de domínio.

---

## Antes de Implementar

Apresente obrigatoriamente:

### Goal

O que será implementado.

### Context

- documentos consultados;
- responsabilidades identificadas;
- relação com as Sprints anteriores.

### Constraints

- restrições arquiteturais;
- restrições do roadmap;
- restrições de DDD.

### Done When

Critérios de conclusão.

---

## Análise Obrigatória

Antes de escrever código:

- explicar por que ProcessarPublicacaoPcaUseCase pertence à Application;
- explicar por que não pertence ao Domain;
- explicar por que a decisão permanece no Aggregate Plano;
- explicar por que o UseCase deve trabalhar com múltiplos Planos;
- explicar por que não utilizar Infrastructure real nesta sprint.

---

## Processo TDD Obrigatório

Seguir:

RED
↓
GREEN
↓
REFACTOR

Fluxo:

1. Criar testes primeiro.
2. Explicar a falha esperada.
3. Implementar o mínimo necessário.
4. Executar os testes.
5. Refatorar se necessário.

---

## Cenários Esperados

Criar testes para validar pelo menos:

✓ deveProcessarPlanoComDecisaoPublicar

✓ deveProcessarPlanoComDecisaoRetificar

✓ deveProcessarPlanoComDecisaoNenhumaAcao

✓ deveProcessarMultiplosPlanos

✓ deveRetornarResultadoQuandoNaoExistiremPlanosElegiveis

✓ deveAcionarValidatorAntesDoProcessamento

✓ deveConsultarPlanoRepository

✓ naoDeveDuplicarRegrasDoDominio

---

## Critérios de Conclusão

- UseCase criado
- Testes criados
- Trabalha com múltiplos Planos
- Utiliza PlanoRepository
- Utiliza PublicarPcaValidator
- Consome Plano::decidirProcessamento()
- Não contém regra de negócio de decisão
- Não depende de Infrastructure real
- Testes verdes

---

## Resposta Final

Apresente:

### Resumo

### Justificativa de Domínio (DDD)

- quais regras permanecem no Aggregate;
- quais responsabilidades ficam na Application;
- alternativas descartadas e justificativas.

### Justificativa Arquitetural

- por que pertence à Application;
- por que não pertence ao Domain;
- por que não pertence à Infrastructure.

### Validação

- testes criados;
- comportamentos validados;
- resultado da execução.

### Arquivos Alterados

### Riscos ou Pontos de Atenção

Especialmente para:

- Sprint 3.5 — PublicarPcaResponse
- Fase 4 — Infraestrutura
