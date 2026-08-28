# Refatoração Arquitetural — UnidadeOrcamentaria passa a ser Entity e criação do Value Object Uasg

## Contexto

Durante a evolução do domínio e implementação das Sprints 1.1 a 2.4, novas regras de negócio foram descobertas e formalizadas no arquivo `modelo_dominio.md`.

Após revisão do modelo de domínio, foi identificado que:

```text
UnidadeOrcamentaria não se comporta mais como um simples Value Object.
```

A entidade passou a possuir:

- identidade própria;
- histórico de versões de Plano;
- histórico de publicações;
- responsabilidade direta na decisão de publicação;
- relacionamento com UASG;
- presença como conceito central do negócio.

Portanto:

```text
UnidadeOrcamentaria deve ser promovida de Value Object para Entity.
```

Além disso:

```text
UASG é um conceito próprio do domínio.
```

UASG identifica a unidade compradora perante o Governo Federal e é obrigatória para publicação no PNCP.

Portanto:

```text
Uasg deve ser modelado como Value Object.
```

---

# Objetivo

Realizar a refatoração completa do domínio para refletir as novas descobertas.

---

# Alterações Esperadas

## 1. UnidadeOrcamentaria

Migrar:

```text
Domain/ValueObjects/UnidadeOrcamentaria
```

para:

```text
Domain/Entities/UnidadeOrcamentaria
```

A classe deixa de ser Value Object e passa a ser Entity.

---

## 2. Uasg

Criar:

```text
Domain/ValueObjects/Uasg
```

e respectivo teste:

```text
UasgTest
```

---

# Modelo de Domínio Atualizado

## UnidadeOrcamentaria

Representa uma Unidade Orçamentária responsável pela publicação do Plano de Contratações Anual junto ao PNCP.

Possui:

```text
Lotacao
Descricao
Uasg
```

Regras:

```text
Lotacao é obrigatória.

Descricao é obrigatória.

Descricao deve possuir ao menos 3 caracteres.

Descricao pode conter acentos.

Descricao pode conter números.

Uasg é obrigatória.

O objeto é imutável após criado.
```

Observações:

```text
- Toda Demanda pertence a uma Unidade Orçamentária.
- Todo Plano pertence a exatamente uma Unidade Orçamentária.
- Cada unidade possui seu próprio histórico de versões homologadas e publicadas.
- Toda UnidadeOrcamentaria utilizada no processo de publicação deve possuir uma UASG associada. Não existe unidadeOrcamentaria válida sem UASG
- UASG é Unidade Administrativa de Serviços Gerais, um código criado pelo Governo Federal para identificar o Óregão ou o Setor exato que está realizando a compra. Utilizado para diferenciar as entidades compradoras
- A UASG é uma informação de configuração utilizada para integração com o PNCP.
```

---

## Uasg

Representa a Unidade Administrativa de Serviços Gerais.

Possui:

```text
codigo
descricao
```

Regras:

```text
codigo é obrigatório.

descricao é obrigatória.

o objeto é imutável.
```

Observação:

```text
Uasg identifica a unidade compradora perante o Governo Federal.
```

---

# Análise Obrigatória

Antes de escrever qualquer código:

1. Revisar integralmente:
   - modelo_dominio.md
   - roadmap.md
   - arquitetura.md

2. Identificar impactos da promoção de UnidadeOrcamentaria para Entity.

3. Identificar todos os locais que dependem de:

```text
UnidadeOrcamentaria
```

incluindo:

```text
Demanda
Plano
HistoricoProcedimento
Repositories
Requests futuros
Testes
```

4. Explicar detalhadamente:

```text
Por que UnidadeOrcamentaria deixou de ser Value Object.

Por que passou a ser Entity.

Por que Uasg é Value Object.

Quais classes precisam ser ajustadas.
```

---

# Processo Obrigatório

Seguir:

```text
RED
↓
GREEN
↓
REFACTOR
```

Fluxo:

1. Atualizar os testes impactados.
2. Explicar as falhas esperadas.
3. Implementar o mínimo necessário.
4. Garantir compatibilidade com todas as sprints anteriores.
5. Refatorar.
6. Executar toda a suíte de testes.

---

# Escopo Proibido

Não criar:

```text
Infrastructure

OracleRepository

UseCases

Controllers

DTOs

PayloadBuilder

PncpClient
```

Não alterar regras de negócio não relacionadas à refatoração.

Não antecipar futuras sprints da Fase 3.

---

# Critérios de Conclusão

A refatoração estará concluída quando:

```text
UnidadeOrcamentaria for Entity.

Uasg existir como Value Object.

Todas as referências forem atualizadas.

Todos os testes estiverem verdes.

Nenhuma regra existente tiver sido perdida.

O domínio estiver alinhado ao modelo_dominio.md atualizado.
```

---

# Resposta Final

Apresente:

## Resumo

## Impactos Arquiteturais

## Classes Alteradas

## Novas Classes Criadas

## Regras Atualizadas

## Compatibilidade com as Sprints Anteriores

## Resultado da Execução dos Testes

## Riscos ou Pontos de Atenção