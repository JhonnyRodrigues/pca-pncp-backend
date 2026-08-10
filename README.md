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
- [`docs/vigentes/autoritativos/fluxo_de_implementacao.md`](docs/vigentes/autoritativos/fluxo_de_implementacao.md)
  contém a colaboração dinâmica entre as camadas e portas;
- [`docs/vigentes/autoritativos/roadmap.md`](docs/vigentes/autoritativos/roadmap.md)
  contém a ordem de construção vigente;
- [`.agents/AGENTS.md`](.agents/AGENTS.md) define como agentes devem trabalhar
  no repositório.

## Objetivo do produto

O backend coordena a publicação, a retificação e a exclusão integral de PCA,
preservando as regras do planejamento interno e a consistência observável com
o PNCP.

O produto é desenvolvido como backend independente e será consumido
inicialmente, em processo, por uma aplicação Scriptcase.

## Estado atual

O projeto concluiu a estabilização da Fase 4 — Application. O próximo passo
autorizado é somente a Sprint 5.1, dedicada aos gates reais de Scriptcase,
Oracle e PNCP antes de qualquer adapter concreto.

As regras de negócio, o modelo de domínio V2, as decisões de integração, o
fluxo de implementação V2 e o Roadmap V2 estão vigentes.

Para começar pelo fluxo em vez de abrir classes isoladas, consulte o
[guia de leitura do código](docs/vigentes/apoio/guia_de_leitura_do_codigo.md) e
o [catálogo navegável](docs/vigentes/apoio/catalogo_de_arquivos.md). O registro
atualizado dos documentos vigentes e planejados está em
[`docs/README.md`](docs/README.md).

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

Essa é a arquitetura-alvo. Domain e Application estão implementados; as
camadas externas permanecem futuras e condicionadas aos gates do Roadmap.

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

O Composer usa `vendor/autoload.php` como autoloader. O antigo exemplo manual e
os exercícios iniciais foram preservados apenas no
[histórico didático](docs/historico/didatico/README.md).

## Como trabalhar neste projeto

Antes de implementar:

1. leia este README para compreender o projeto e sua finalidade;
2. se estiver atuando como agente, leia [`.agents/AGENTS.md`](.agents/AGENTS.md)
   e [`.agents/README.md`](.agents/README.md);
3. consulte [`docs/README.md`](docs/README.md) e siga sua ordem de leitura,
   classificação e precedência;
4. inspecione o estado atual do Git e preserve alterações locais;
5. use o Roadmap V2 para a ordem de construção e os checkpoints aplicáveis
   para a continuidade do planejamento;
6. aplique TDD quando a tarefa envolver código;
7. implemente somente o escopo autorizado;
8. não trate documentos históricos, investigações ou prompts antigos como
   especificações vigentes.

O Roadmap V2 organiza a sequência de implementação sem redefinir regras,
domínio, decisões de integração ou fluxo.

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
