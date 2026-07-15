# Sprint 1.8 — Entity HistoricoProcedimento

## Leia antes de implementar

Leia novamente:

- README.md
- .agents/AGENTS.md
- todos os arquivos da pasta docs/

Principalmente:

- docs/modelo_dominio.md
- docs/roadmap.md
- docs/arquitetura.md

O domínio foi refinado recentemente.

---

## Objetivo

Implementar exclusivamente:

```text
Sprint 1.8

□ Criar Entity HistoricoProcedimento
□ HistoricoProcedimentoTest
```

---

## Regras de Negócio

HistoricoProcedimento representa o registro da execução de um Procedimento.

Possui:

```text
procedimento
representante
lotacao
dataInsercao
mensagem
```

Observações:

```text
idHistorico é apenas uma PK técnica do Oracle.
Não faz parte do domínio neste momento.
```

```text
HistoricoProcedimento é imutável.
```

```text
HistoricoProcedimento referencia Procedimento.
```

```text
HistoricoProcedimento utiliza Lotacao.
```

```text
HistoricoProcedimento pode possuir Representante.
```

---

## Regras Específicas

### PUBLICAÇÃO DE PLANO

Quando o procedimento for:

```text
PUBLICAÇÃO DE PLANO
```

então:

```text
Representante é obrigatório.
Lotacao é obrigatória.
Mensagem é obrigatória.
```

Exemplo:

```text
"O Plano de Compras e Contratações da unidade
orçamentária Procuradoria Jurídica para o ano
2027 foi publicado."
```

### Demais Procedimentos

Não criar regras não documentadas.

Utilizar apenas as regras explicitamente existentes na documentação.

---

## Antes de Implementar

Apresente:

### Goal

### Context

### Constraints

### Done When

---

## TDD Obrigatório

Fluxo:

```text
RED
↓
GREEN
↓
REFACTOR
```

Criar os testes primeiro.

---

## Sugestão de Cenários

```text
✓ deveCriarHistoricoProcedimentoValido

✓ deveRepresentarHistoricoDePublicacao

✓ deveExigirMensagemQuandoProcedimentoForPublicacao

✓ deveExigirRepresentanteQuandoProcedimentoForPublicacao

✓ deveExigirLotacaoQuandoProcedimentoForPublicacao

✓ deveSerImutavel
```

---

## Restrições

Não implementar:

```text
Plano
Repository
UseCase
Infrastructure
Controller
DTO
PayloadBuilder
PNCP
```

Não antecipar a Sprint 1.9.

Não criar regras de negócio não documentadas.

---

## Critério de Conclusão

A sprint estará concluída quando:

```text
HistoricoProcedimento.php criado
HistoricoProcedimentoTest.php criado
Testes cobrindo as regras documentadas
Entidade imutável
Sem antecipação de funcionalidades futuras
```

---

## Resposta Final

Apresente:

- Resumo
- Regras de Negócio Aplicadas
- Justificativa Arquitetural
- Validação
- Arquivos Alterados
- Riscos ou Pontos de Atenção