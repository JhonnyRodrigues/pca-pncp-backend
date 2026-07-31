# Backend PCA/PNCP

Backend em PHP para publicação e sincronização do Plano de Contratações Anual
(PCA) com o Portal Nacional de Contratações Públicas (PNCP).

## Papel deste documento

Este README é a entrada cognitiva do repositório. Ele apresenta:

- o que é o projeto;
- seus objetivos técnicos e didáticos;
- o estado geral do trabalho;
- como localizar as fontes especializadas.

Ele é autoritativo sobre a visão geral e a finalidade de aprendizado, mas não
substitui as fontes específicas:

- [`docs/README.md`](docs/README.md) governa classificação, vigência e
  navegação documental;
- [`docs/vigentes/autoritativos/regras_de_negocio.md`](docs/vigentes/autoritativos/regras_de_negocio.md)
  contém as regras de negócio;
- [`docs/vigentes/autoritativos/modelo_de_dominio.md`](docs/vigentes/autoritativos/modelo_de_dominio.md)
  contém o modelo conceitual vigente;
- [`docs/vigentes/autoritativos/decisoes_de_integracao.md`](docs/vigentes/autoritativos/decisoes_de_integracao.md)
  contém as decisões de integração;
- [`.agents/AGENTS.md`](.agents/AGENTS.md) define como agentes devem trabalhar
  no repositório.

## Objetivo do produto

O backend coordena a publicação, a retificação e a exclusão integral de PCA,
preservando as regras do planejamento interno e a consistência observável com
o PNCP.

O produto é desenvolvido como backend independente e será consumido
inicialmente, em processo, por uma aplicação Scriptcase.

## Estado atual

O projeto está em desenvolvimento e passa por consolidação documental e
refatoração arquitetural antes da Infrastructure.

As regras de negócio, o modelo de domínio V2, as decisões de integração e o
fluxo de implementação V2 estão vigentes.

O Roadmap V2 ainda está planejado e não deve ser tratado como documento
existente.

O código atual contém resultados de etapas anteriores e pode representar
premissas que serão refatoradas com TDD. O registro atualizado dos documentos
vigentes e planejados está em [`docs/README.md`](docs/README.md).

## Objetivos de aprendizado

Além do desenvolvimento do sistema, este projeto serve como estudo de:

- Domain-Driven Design (DDD);
- Clean Architecture;
- Design Patterns;
- SOLID;
- Test-Driven Development (TDD);
- testes unitários;
- boas práticas de programação em PHP;
- SDD (Spec-Driven Development - Desenvolvimento Orientado por
  Especificações).

O objetivo não é apenas entregar funcionalidades, mas compreender como regras
de negócio, modelagem, decisões arquiteturais, testes e integração evoluem em
conjunto.

## Arquitetura

O projeto adota Clean Architecture, DDD, SOLID, injeção de dependências e TDD.
A direção das dependências aponta para o núcleo:

```text
Presentation
      ↓
Application
      ↓
Domain

Infrastructure implementa contratos definidos nas camadas internas.
```

Responsabilidades conceituais:

- **Domain:** modelo de negócio, invariantes e políticas puras;
- **Application:** casos de uso, orquestração e contratos necessários ao fluxo;
- **Infrastructure:** adapters de persistência, transporte e recursos externos;
- **Presentation:** fronteira de entrada e apresentação do resultado.

Essa é a arquitetura-alvo. O código existente ainda será reconciliado com os
documentos vigentes por meio de uma sprint própria de refatoração.

## Estrutura principal

```text
src/
├── Domain/
├── Application/
├── Infrastructure/
└── Presentation/

tests/
docs/
.agents/
```

## Tecnologias e integrações

### Utilizadas no desenvolvimento

- PHP `^8.1`;
- Composer;
- autoload PSR-4;
- PHPUnit `^10`;
- Git.

### Integrações-alvo

- Oracle 19c;
- API do PNCP;
- aplicação hospedeira Scriptcase.

As integrações-alvo possuem decisões e gates ainda condicionais. Sua presença
nesta lista não significa que a Infrastructure esteja pronta ou autorizada a
ser iniciada.

## Preparação e testes

Instale as dependências de desenvolvimento:

```bash
composer install
```

Execute a suíte configurada:

```bash
composer tests
```

O Composer usa `vendor/autoload.php` como autoloader principal. O arquivo
`autoload.php` da raiz preserva um autoloader manual apenas para fins de estudo
e não é o caminho recomendado para os testes.

## Como trabalhar neste projeto

Antes de implementar:

1. leia este README para compreender o projeto e sua finalidade;
2. se estiver atuando como agente, leia [`.agents/AGENTS.md`](.agents/AGENTS.md)
   e [`.agents/README.md`](.agents/README.md);
3. consulte [`docs/README.md`](docs/README.md) e siga sua ordem de leitura,
   classificação e precedência;
4. inspecione o estado atual do Git e preserve alterações locais;
5. use o plano aprovado e os checkpoints aplicáveis enquanto o Roadmap V2 não
   existir;
6. aplique TDD quando a tarefa envolver código;
7. implemente somente o escopo autorizado;
8. não trate documentos históricos, investigações ou prompts antigos como
   especificações vigentes.

Quando o Roadmap V2 for criado e classificado como vigente, ele passará a
organizar a sequência de implementação sem redefinir regras, domínio ou
decisões de integração.

## Processo de aprendizado

Ao propor implementações ou refatorações relevantes, explique:

- qual problema de negócio está sendo resolvido;
- quais regras e decisões sustentam a solução;
- por que a modelagem representa o domínio;
- por que cada responsabilidade pertence à camada escolhida;
- quais testes demonstram o comportamento;
- quais alternativas foram descartadas e por quê.

As explicações devem favorecer o aprendizado sem transformar documentos de
apoio, histórico ou código existente em fontes normativas concorrentes.
