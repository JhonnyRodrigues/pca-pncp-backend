# BACKEND

Backend em PHP responsável pela publicação e retificação do Plano de Contratações Anual (PCA) junto ao PNCP.

## Objetivo

Este projeto está sendo desenvolvido do zero com foco em:

- PHP 8.1
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

- **Domain:** regras de negócio e entidades.
- **Application:** casos de uso.
- **Infrastructure:** banco de dados, API PNCP e recursos externos.
- **Presentation:** controllers e entrada da aplicação.

---

## Tecnologias

- PHP 8.1
- PHPUnit 10
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

```bash
php phpunit-10.phar --bootstrap autoload.php
```

---

## Objetivos de aprendizado

Além do desenvolvimento do sistema, este projeto serve como estudo de:

- Clean Architecture
- TDD
- Design Patterns
- SOLID
- Testes unitários
- Boas práticas em PHP

---

## Status

🚧 Em desenvolvimento.

## Como trabalhar neste projeto

Antes de implementar qualquer código:

1. Leia a documentação da pasta `docs/`.
2. Não viole a Clean Architecture.
3. Implemente uma classe por vez.
4. Escreva o teste antes da implementação.
5. Explique as decisões arquiteturais.
6. Não altere arquivos não relacionados à tarefa.