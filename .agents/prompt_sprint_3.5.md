# Sprint 3.5 — PublicarPcaResponse e ResultadoProcessamentoPlano

## Objetivo

Implementar exclusivamente:

□ PublicarPcaResponse
□ ResultadoProcessamentoPlano

Não implementar funcionalidades futuras.

---

## Contexto

Leia integralmente:

- README.md
- AGENTS.md
- docs/arquitetura.md
- docs/modelo_dominio.md
- docs/fluxo_implementacao.md
- docs/roadmap.md

Sprints já concluídas:

- Sprint 3.1 — PublicarPcaRequest
- Sprint 3.2 — PublicarPcaValidator
- Sprint 3.3 — DecisaoProcessamentoPlano
- Sprint 3.4 — ProcessarPublicacaoPcaUseCase

Atualmente o UseCase retorna uma estrutura provisória.

Nesta sprint essa estrutura deverá ser substituída por DTOs explícitos da camada Application.

---

## Objetivo Arquitetural

Substituir a estrutura provisória atualmente retornada pelo UseCase por DTOs explícitos da camada Application, substituindo estruturas temporárias por objetos nomeados e tipados.

Exemplo conceitual:

ProcessarPublicacaoPcaUseCase
↓
PublicarPcaResponse
    ├── erros
    └── resultados

ResultadoProcessamentoPlano
    ├── plano processado
    └── decisão tomada


A Sprint 3.4 já provou que:

- a Application consegue orquestrar o fluxo;
- o Domain consegue tomar as decisões;
- o UseCase consegue processar múltiplos Planos.

Agora é necessário formalizar a saída do caso de uso.

---

## Responsabilidade dos DTOs

### PublicarPcaResponse

Representar o resultado global do caso de uso.

Responsabilidades:

- armazenar os erros encontrados;
- armazenar os resultados processados;
- disponibilizar consulta das informações produzidas pelo UseCase. Fornecendo uma estrutura clara de saída para a camada de apresentação.

Não deve:

- executar regras de negócio;
- validar dados;
- consultar repositórios;
- decidir PUBLICAR, RETIFICAR ou NENHUMA_ACAO.

---

### ResultadoProcessamentoPlano

Representa o resultado produzido para um único Plano processado.

Responsabilidades:

- identificar qual Plano foi processado;
- expor a decisão retornada pelo domínio;
- disponibilizar informações necessárias para composição da resposta.

Não deve:

- decidir o processamento;
- executar regras de domínio;
- alterar o estado do Plano

A decisão já deve chegar pronta do Aggregate Root Plano.

---

## Imutabilidade

Os DTOs devem ser imutáveis.

Utilizar:

```php
private readonly
```

Não utilizar:

```php
readonly class
```


## Relacionamento com o Domínio

O Aggregate Root Plano continua responsável por decidir:

- PUBLICAR
- RETIFICAR
- NENHUMA_ACAO

A Sprint 3.5 não altera essa regra.

O DTO apenas transporta a decisão já tomada.

---

## Observação Importante

Estes objetos pertencem à camada Application.

Não pertencem ao Domain.

Não representam conceitos do negócio.

Representam apenas a saída produzida pelo caso de uso.

---

## Relação com a Sprint 3.4

Atualmente o UseCase retorna uma estrutura provisória.

Exemplo conceitual:

```text
[
    erros,
    processamentos
]
```

Nesta sprint essa estrutura deverá ser substituída por objetos explícitos:

```text
PublicarPcaResponse
    ├── erros
    └── resultados

ResultadoProcessamentoPlano
    ├── identificação do Plano
    └── decisão
```

---

## Decisão de Projeto

Utilizar DTOs simples.

Não criar:

- Presenter
- ViewModel
- Transformer
- Mapper
- Serializer
- Factory

Evitar abstrações prematuras.

---

## O que NÃO Fazer

Não criar:

- Novos UseCases
- Novos Repositories
- Novos Services
- Novas regras de domínio
- Integração PNCP
- Oracle
- Controller
- Infrastructure

Não modificar a Sprint 3.4 além do necessário para utilizar os novos DTOs. Se possível, comentar em vez de sobrescrever para permitir comparação entre o estado anterior e o atual

---

## Antes de Implementar

Apresente obrigatoriamente:

### Goal

O que será implementado.

### Context

- documentos consultados;
- relação com a Sprint 3.4;
- papel dos DTOs na Application.

### Constraints

- restrições do roadmap;
- restrições arquiteturais;
- restrições de DDD.

### Done When

Critérios de conclusão.

---

## Análise Obrigatória

A
ntes de escrever código:

### DTOs na Application

Explicar:

- por que PublicarPcaResponse pertence à Application;
- por que ResultadoProcessamentoPlano pertence à Application;
- por que DTOs não representam conceitos do domínio.

### Separação entre Domain e Application

Explicar:

- como a decisão continua pertencendo ao Aggregate Plano;
- por que os DTOs apenas transportam informação;
- por que nenhuma regra de negócio deve migrar para os DTOs.

### Presenter

Explicar:

- por que Presenter não é necessário neste momento;
- em quais cenários futuros Presenter poderia ser introduzido.

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

✓ deveCriarResultadoProcessamentoPlano

✓ deveManterDecisaoRecebidaDoDominio

✓ deveCriarPublicarPcaResponseSemErros

✓ deveCriarPublicarPcaResponseComErros

✓ deveRetornarResultadosProcessados

✓ deveRetornarListaDeErros

✓ deveSerImutavel

✓ devePermitirMultiplosResultados

---

## Escopo Permitido

Pode criar:

- PublicarPcaResponse.php
- PublicarPcaResponseTest.php
- ResultadoProcessamentoPlano.php
- ResultadoProcessamentoPlanoTest.php

Pode realizar os ajustes mínimos necessários em:

- ProcessarPublicacaoPcaUseCase
- ProcessarPublicacaoPcaUseCaseTest

apenas para substituir a estrutura provisória pelos novos DTOs.

---

## Critérios de Conclusão

- DTOs criados;
- testes criados;
- DTOs imutáveis;
- UseCase passa a retornar PublicarPcaResponse;
- sem regras de negócio;
- sem dependência de Infrastructure;
- sem novas abstrações;
- testes verdes.

---

## Resposta Final

Apresente:

### Resumo

### Justificativa de Domínio (DDD)

- quais responsabilidades continuam no Domain;
- por que os DTOs não pertencem ao Domain;
- como a decisão do Aggregate é preservada.

### Justificativa Arquitetural

- por que os DTOs pertencem à Application;
- por que o UseCase retorna DTOs;
- motivo para não introduzir Presenter neste momento.

### Validação

- testes criados;
- comportamentos validados;
- resultado da execução.

### Arquivos Alterados

### Preparação para Próximas Etapas

Explique como a implementação facilita:

- Fase 4 — Infrastructure;
- integração com PNCP;
- futura introdução de Presenter caso necessário.