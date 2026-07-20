# Sprint 3.2 — PublicarPcaValidator

## Objetivo

Implementar exclusivamente:

□ PublicarPcaValidator
□ PublicarPcaValidatorTest

Além disso, caso necessário para suportar a validação:

□ ValidationResult
□ ValidationResultTest

Não implementar nenhuma outra sprint.

---

## Contexto

Leia integralmente:

- README.md
- .agents/AGENTS.md
- 1_docs/arquitetura.md
- docs/2_modelo_dominio.md
- docs/3_fluxo_implementacao.md
- docs/4_roadmap.md
- docs/5_contexto_planejamento.md
- docs/6_explicacao_sistema_PCA.md

As Fases 1 e 2 já foram concluídas.

A Sprint 3.1 já foi implementada.

Atualmente existe:

```php
PublicarPcaRequest
```

contendo:

- Cnpj
- AnoExecucao
- usuarioLogado
- credenciais

---

## Papel do Validator

PublicarPcaValidator pertence à camada Application.

Sua responsabilidade é validar os dados da Request antes que o caso de uso seja executado.

O Validator:

✓ valida dados de entrada

Não:

✗ contém regras de negócio
✗ acessa banco de dados
✗ chama PNCP
✗ conhece Oracle
✗ conhece HTTP
✗ conhece Scriptcase

---

## Regras de Validação

### usuarioLogado

Validar:

- obrigatório
- não pode ser vazio
- não pode conter apenas espaços em branco

---

### credenciais

Validar:

- obrigatório
- não pode ser vazio
- testar se o array retorna as chaves não-nulo e não vazias: ambiente, baseUrl, usuario, senha

Validar apenas estrutura mínima necessária.

Não validar comunicação com PNCP.

Não realizar autenticação.

---

## Não Validar

Não validar:

### Cnpj

Motivo: As invariantes já pertencem ao Value Object Cnpj.

---

### AnoExecucao

Motivo: As invariantes já pertencem ao Value Object AnoExecucao.

---

## Resultado Esperado

A validação deve retornar todos os erros encontrados.

Exemplo:

```text
[
    "A identificação do Usuário logado é obrigatória.",
    "Credenciais de autenticação PNCP são obrigatórias."
]
```

Não interromper na primeira falha.

---

## ValidationResult

Caso necessário, criar:

```php
ValidationResult
```

Responsabilidades:

- armazenar erros
- informar se a validação é válida
- disponibilizar lista de erros

Exemplos:

```php
$result->isValid();
$result->getErrors();
```

ValidationResult:

- pertence à Application
- não pertence ao Domain
- não contém regras de negócio

---

## O que NÃO criar

Não criar:

- ValidationException
- Notification Pattern
- Factories
- Interfaces
- Strategies
- UseCase
- Response
- Controller
- Repository
- Infrastructure
- Oracle
- PNCP

Evitar abstrações prematuras.

---

## Antes de Implementar

Apresente obrigatoriamente:

### Goal

O que será implementado.

### Context

- documentos consultados;
- responsabilidades identificadas;
- relação com a Sprint 3.1.

### Constraints

- restrições arquiteturais;
- restrições do roadmap;
- restrições de DDD.

### Done When

Critérios de conclusão.

---

## Análise Obrigatória

Antes de escrever código:

- explicar por que PublicarPcaValidator pertence à Application;
- explicar por que não pertence ao Domain;
- explicar por que Cnpj não deve ser validado novamente;
- explicar por que AnoExecucao não deve ser validado novamente;
- explicar a diferença entre validação de entrada e regra de negócio;
- justificar a criação ou não de ValidationResult.

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
4. Executar testes.
5. Refatorar se necessário.

---

## Cenários Esperados

Exemplos:

✓ deveValidarRequestSemErros

✓ deveRetornarErroQuandoUsuarioLogadoForVazio

✓ deveRetornarErroQuandoUsuarioLogadoContiverApenasEspacos

✓ deveRetornarErroQuandoCredenciaisForemObrigatorias

✓ deveRetornarMultiplosErros

✓ deveInformarQuandoValidacaoForValida

✓ deveRetornarListaDeErros

---

## Escopo Permitido

Pode criar apenas:

- PublicarPcaValidator.php
- PublicarPcaValidatorTest.php
- ValidationResult.php (se necessário)
- ValidationResultTest.php (se necessário)

---

## Critérios de Conclusão

- PublicarPcaValidator criado
- Testes criados
- Todos os erros retornados
- Sem regras de negócio
- Sem validação duplicada de Value Objects
- Testes verdes
- Código aderente ao roadmap

---

## Resposta Final

Apresente:

### Resumo

### Justificativa de Domínio (DDD)

- qual problema está sendo resolvido;
- por que esta validação não pertence ao Domain;
- relação com os Value Objects existentes.

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

- Sprint 3.3
- Sprint 3.4