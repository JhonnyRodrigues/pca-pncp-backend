# BACKEND

Backend em PHP responsável pela publicação e retificação do Plano de Contratações Anual (PCA) junto ao PNCP.

## Objetivo

Este projeto está sendo desenvolvido do zero com foco em:

- PHP 8.1
- DDD (Domain Driven Design)
- Clean Architecture
- SOLID
- TDD
- Integração com Oracle 19c
- Integração com a API do PNCP

O backend será consumido por uma aplicação desenvolvida em Scriptcase.

---

## Arquitetura

O projeto segue os princípios da Clean Architecture.

```
src/
├── Domain/
├── Application/
├── Infrastructure/
└── Presentation/
```

### Responsabilidade das camadas

- **Domain:** regras de negócio e entidades, value objects, agregados, serviços de domínio e invariantes.
- **Application:** casos de uso.
- **Infrastructure:** banco de dados, API PNCP e recursos externos.
- **Presentation:** controllers e entrada da aplicação.

---

## Princípios de DDD adotados

O domínio deve representar o processo de negócio relacionado ao PCA e ao PNCP.

Diretrizes:

- utilizar linguagem ubíqua alinhada ao negócio
- modelar conceitos do domínio e não apenas estruturas de banco
- proteger invariantes dentro do Domain
- utilizar Value Objects quando apropriado
- evitar entidades anêmicas
- evitar lógica de negócio fora do Domain
- manter casos de uso focados em orquestração
- manter integrações externas na Infrastructure

O código deve refletir como o negócio funciona no mundo real.

---

## Tecnologias

- PHP 8.1
- PHPUnit 10.5.64
- Oracle 19c
- PSR-4
- Git

---

## Estrutura

```
src/
tests/
docs/
```

---

## Executando os testes

preferencialmente:
```bash
vendor/bin/phpunit
```

ou a maneira descontinuada:

```bash
php phpunit-10.phar --bootstrap autoload.php
```

---

## Objetivos de aprendizado

Além do desenvolvimento do sistema, este projeto serve como estudo de:

- Domain Driven Design
- Clean Architecture
- Design Patterns
- SOLID
- TDD
- Testes unitários
- Boas práticas de programação em PHP

---

## Status

🚧 Em desenvolvimento.

## Como trabalhar neste projeto

Antes de implementar qualquer código:

1. Leia toda a documentação da pasta docs/.
2. Siga o roadmap antes de implementar qualquer funcionalidade.
3. Não viole a Clean Architecture.
4. Aplique TDD (Red → Green → Refactor).
5. Implemente apenas o escopo solicitado.
6. Explique a relação da solução com o domínio do negócio.
7. Explique as decisões de DDD adotadas.
8. Explique as decisões arquiteturais.
9. Não altere arquivos não relacionados à tarefa.

## Processo de Aprendizado

Ao propor implementações ou refatorações relevantes, é desejável explicar:

- qual problema de negócio está sendo resolvido
- por que a modelagem escolhida representa o domínio
- por que determinada classe pertence à camada atual
- quais padrões de DDD estão sendo utilizados
- quais alternativas foram descartadas e por quê

O objetivo não é apenas implementar funcionalidades, mas compreender os conceitos envolvidos.