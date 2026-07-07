# AGENTS.md

## Objetivo do projeto

Este projeto implementa um backend em PHP 8.1 responsável pela publicação e retificação do Plano de Contratações Anual (PCA) junto ao Portal Nacional de Contratações Públicas (PNCP).

O backend será consumido inicialmente por uma aplicação Scriptcase.

---

## Antes de qualquer implementação

Leia toda a documentação da pasta `docs/`.

A ordem recomendada é:

1. arquitetura.md
2. modelo_dominio.md
3. fluxo_implementacao.md
4. roadmap.md
5. contexto_planejamento.md
6. explicacao_sistema_PCA.md

---

## Arquitetura

Este projeto utiliza:

- PHP 8.1
- Clean Architecture
- Oracle 19c
- PSR-12
- PSR-4
- PSR-3
- Dependency Injection
- TDD

Nunca viole a direção das dependências.

Presentation
↓

Application
↓

Domain

Infrastructure implementa interfaces do domínio.

---

## Regras importantes

Nunca coloque regras de negócio em:

- Repository
- Controller
- Client
- PayloadBuilder

As regras pertencem ao Domain.

- nunca acessar Oracle diretamente pelo UseCase
- toda regra de negócio fica no Domain, nunca colocar regras de negócio em Repository;
- Mapeamentos entre sistemas externos não devem ser implementados
no domínio.
- Conversões de identificadores entre SGI, Oracle, PNCP ou outros
sistemas pertencem à camada Infrastructure.

- escrever teste antes da implementação
- não criar código que não foi solicitado
- explicar cada decisão arquitetural
- gerar código comentado apenas quando necessário

---

## Ordem de desenvolvimento

Sempre siga o roadmap.

Nunca implemente classes futuras.

Sempre implemente apenas a próxima tarefa.

---

## TDD

Para cada classe:

1. escrever teste
2. executar teste
3. implementar código mínimo
4. executar novamente
5. refatorar

---

## Estilo

Priorizar:

- simplicidade
- baixo acoplamento
- nomes claros
- métodos pequenos
- alta legibilidade

---

## Ao responder

Explique rapidamente:

- por que aquela classe existe
- qual responsabilidade ela possui
- por que ela pertence àquela camada

Antes de gerar código, confirme que a implementação está de acordo com a arquitetura do projeto.

Nunca altere arquivos não relacionados à tarefa atual.
