# Sprint 1.9 — Aggregate Root Plano

## Importante: Contexto Atualizado

Antes de iniciar a implementação, releia:

- README.md
- .agents/AGENTS.md
- todos os arquivos da pasta docs/

Principalmente:

- docs/modelo_dominio.md
- docs/arquitetura.md
- docs/fluxo_implementacao.md
- docs/roadmap.md

O domínio foi refinado durante as últimas sprints.

Principais descobertas:

- Plano é o Aggregate Root do domínio.
- Procedimento e HistoricoProcedimento não pertencem ao agregado Plano.
- Um Plano nasce somente após a homologação.
- Todo Plano já nasce homologado.
- Demandas extraordinárias homologadas geram uma nova versão do Plano.
- A decisão entre Publicar e Retificar pertence ao domínio, mas não deve ser implementada nesta sprint, pois depende de informações externas ao estado isolado do Plano.

---

# Objetivo

Implementar exclusivamente:

```text
Sprint 1.9

□ Criar Aggregate Root Plano
□ PlanoTest
```

---

# Regras de Negócio

## Identidade do Plano

A identidade lógica de uma versão de Plano é composta por:

```text
AnoExecucao
UnidadeOrcamentaria
SequencialVersao
```

Além disso, o Plano possui:

```text
idPlano
```

que faz parte do domínio e é utilizado por Demanda através da FK_PLANO.

---

## Estrutura do Plano

Todo Plano possui:

```text
idPlano
AnoExecucao
UnidadeOrcamentaria
SequencialVersao
DataHomologacao
Demandas
```

Opcionalmente:

```text
DataPublicacao
```

---

## Regras

### Regra 1

Todo Plano deve possuir ao menos uma Demanda.

### Regra 2

Um Plano não pode existir sem Demandas.

### Regra 3

Todo Plano nasce homologado.

Portanto:

```text
DataHomologacao é obrigatória.
```

### Regra 4

DataPublicacao é opcional.

Um Plano pode existir homologado e ainda não publicado.

### Regra 5

SequencialVersao deve ser maior que zero.

### Regra 6

Uma nova homologação gera uma nova versão do Plano.

O Plano existente não recebe novas Demandas.

Uma nova versão do Plano é criada.

---

# Comportamentos Esperados

Esta sprint deve implementar apenas comportamentos que dependem exclusivamente do estado interno do Plano.

Exemplos válidos:

```php
foiPublicado()
```

Implementação conceitual:

```php
return $this->dataPublicacao !== null;
```

Também podem existir comportamentos equivalentes como:

```php
possuiDemandas()
quantidadeDemandas()
```

desde que sejam justificáveis pelo domínio.

---

# Não Implementar Nesta Sprint

Não implementar:

```php
devePublicar()

deveRetificar()
```

Motivo:

Essas decisões dependem do histórico de versões/publicações e não podem ser determinadas apenas pelo estado isolado de um Plano.

A regra continua pertencendo ao Domain, mas ainda não deve ser modelada dentro do Aggregate Root.

---

# Antes de Implementar

Apresente obrigatoriamente:

## Goal

O que será implementado.

## Context

- documentos consultados;
- regras encontradas;
- componentes relacionados.

## Constraints

- restrições arquiteturais;
- restrições técnicas;
- restrições de negócio.

## Done When

Critérios de conclusão da sprint.

---

# Análise Obrigatória

Antes de gerar qualquer teste ou código:

1. Verifique a próxima sprint pendente do roadmap.
2. Confirme que a Sprint 1.8 está concluída.
3. Liste todas as regras de negócio aplicáveis.
4. Informe em qual documento cada regra foi encontrada.
5. Explique por que Plano é um Aggregate Root.
6. Explique quais entidades pertencem ao agregado.
7. Explique quais entidades explicitamente NÃO pertencem ao agregado.
8. Explique quais comportamentos serão validados em PlanoTest.

---

# Processo TDD Obrigatório

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
5. Reexecutar os testes.
6. Refatorar apenas se necessário.
7. Verificar aderência à documentação.

---

# Sugestão de Cenários de Teste

Validar comportamentos do domínio.

Exemplos:

```text
✓ deveCriarPlanoHomologadoComDemandas

✓ deveImpedirPlanoSemDemandas

✓ devePermitirPlanoAindaNaoPublicado

✓ deveReconhecerPlanoPublicado

✓ deveExigirSequencialVersaoMaiorQueZero

✓ deveRepresentarUmaVersaoEspecificaDoPlano
```

Não criar testes para regras não documentadas.

---

# Escopo Permitido

Pode criar apenas:

```text
Plano.php
PlanoTest.php
```

e ajustes mínimos necessários para suporte da sprint.

---

# Escopo Proibido

Não criar:

```text
Repository
UseCase
DTO
Controller
Infrastructure
Client
PayloadBuilder
Presenter
```

Não antecipar funcionalidades da Fase 2 ou posteriores.

Não criar regras não documentadas.

Não criar abstrações para decidir Publicação ou Retificação.

---

# Tratamento de Inconsistências

Caso encontre:

- regras ausentes;
- regras contraditórias;
- conflito entre documentação e roadmap;
- necessidade de modelagens adicionais para concluir a sprint;

interrompa a implementação e apresente a análise antes de escrever qualquer teste ou código.

---

# Critérios de Conclusão

A sprint estará concluída quando:

```text
Plano.php criado

PlanoTest.php criado

Plano modelado como Aggregate Root

Demandas pertencendo ao agregado

Regras documentadas cobertas por testes

Comportamentos do agregado implementados

Sem antecipação de funcionalidades futuras
```

---

# Resposta Final

Apresente:

## Resumo

## Regras de Negócio Aplicadas

## Justificativa Arquitetural

- por que Plano é Aggregate Root;
- por que Demandas pertencem ao agregado;
- por que Procedimento e HistoricoProcedimento não pertencem ao agregado.

## Validação

- testes criados;
- comportamentos validados;
- resultado da execução.

## Arquivos Alterados

## Riscos ou Pontos de Atenção

Especialmente relacionados à futura implementação:

```text
Fase 2 — PlanoRepository

Fase 3 — PublicarPcaUseCase

Fase 3 — RetificarPcaUseCase
```