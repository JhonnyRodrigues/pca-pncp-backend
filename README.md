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
- [`docs/vigentes/operacional/estado_atual.md`](docs/vigentes/operacional/estado_atual.md)
  concentra o progresso corrente, os gates, as pendências e as restrições;
- [`AGENTS.md`](AGENTS.md) define como agentes devem trabalhar
  no repositório.

## Objetivo do produto

O backend coordena a publicação, a retificação e a exclusão integral de PCA,
preservando as regras do planejamento interno e a consistência observável com
o PNCP.

O produto é desenvolvido como backend independente e será consumido
inicialmente, em processo, por uma aplicação Scriptcase.

## Estado atual

O progresso, a última sprint concluída, a próxima sprint na ordem, os gates,
as pendências e as restrições estão centralizados no
[estado operacional vigente](docs/vigentes/operacional/estado_atual.md).

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

Essa é a arquitetura-alvo. O grau de implementação de cada camada permanece
registrado no [estado operacional vigente](docs/vigentes/operacional/estado_atual.md).

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

O estado dos gates e dos adapters está no
[estado operacional vigente](docs/vigentes/operacional/estado_atual.md). A
presença de uma integração nesta lista não constitui autorização irrestrita
para avançar no Roadmap.

## Preparação e testes

Instale as dependências de desenvolvimento:

```bash
composer install
```

Execute a suíte configurada:

```bash
composer tests
```

Os adapters e seus contratos opt-in possuem comandos separados:

```bash
composer adapters:tests
composer contract:adapters:tests
composer contract:adapters:preflight
composer contract:adapters:preflight:oracle
composer contract:adapters:preflight:pncp
composer contract:adapters:pncp:cleanup
composer contract:adapters:pncp
```

Os cinco comandos da Sprint 5.9 iniciados por
`contract:adapters:preflight` ou `contract:adapters:pncp` exigem a configuração
externa indicada por `PCA_SPRINT59_CONFIG_FILE`; o gate PNCP realiza escritas
reais somente no ambiente de treinamento. O gate Oracle é executado no
Scriptcase pelo entrypoint contratual documentado em
`tests/Contract/Sprint59/scriptcase_probe.php`. Nenhum desses gates é alcançado
por `composer tests`.

Quando uma execução PNCP deixar resíduo conhecido, informe exclusivamente esse
valor em `owned_residue_sequence` e execute primeiro:

```bash
composer contract:adapters:preflight:pncp
composer contract:adapters:pncp:cleanup
```

A limpeza isolada não publica. Ela observa o PCA conhecido, exclui somente o
alvo coerente com a massa dedicada e exige ausência definitiva. O gate normal
recusa iniciar enquanto `owned_residue_sequence` estiver preenchido. Depois de
um relatório com `confirmado: true`, `ausencia_definitiva_confirmada: true` e
`publicacao_executada: false`, remova o valor da configuração, repita o
preflight e somente então execute `composer contract:adapters:pncp`.

### Diagnóstico do gate Oracle da Sprint 5.9

O diagnóstico pertence somente ao modo `adapters` do entrypoint:

```php
$relatorio = executar_contratos_adapters_oracle_sprint_59(
    $db,
    $login,
    'adapters'
);
```

O host Scriptcase deve carregar previamente `vendor/autoload.php` e
`tests/Contract/Sprint59/scriptcase_probe.php`. A variável
`PCA_SPRINT59_CONFIG_FILE` deve apontar para a configuração externa já validada
pelo preflight Oracle. Não copie credenciais nem o conteúdo dessa configuração
para o repositório, para a aplicação Blank ou para o relatório.

Quando `confirmado` for `false`, `falha.etapa` indica o último estágio iniciado:

| Etapa | Operação investigada |
| --- | --- |
| `bind` | Identificação da sessão e bind básico em `DUAL`. |
| `estrategia_corrente` | Leitura da configuração corrente da estratégia. |
| `estrategia_adotada` | Leitura da estratégia anual já adotada. |
| `contexto_usuario` | Resolução do Representante e da Lotação pelo login confiável. |
| `identidade_plano` | Descoberta da identidade única da massa dedicada. |
| `inicio_transacao` | Início da unidade de trabalho e aquisição da exclusividade. |
| `reconstrucao_plano` | Reconstrução da fotografia vigente e conferência da sessão. |
| `consulta_estado` | Leitura e validação do estado operacional. |
| `confirmacao_plano` | Confirmação temporária e releitura do Plano na transação. |
| `registro_estado` | Escrita e confirmação do estado operacional temporário. |
| `registro_auditoria` | Contagem e escrita do histórico temporário. |
| `rollback` | Desfazimento explícito da transação de prova. |
| `verificacao_rollback` | Nova transação e comprovação da restauração da massa. |

Por padrão, a etapa é informada, mas `falha.mensagem` permanece genérica e
`falha.tipo` não é emitido. Em ambiente controlado de desenvolvimento, habilite
temporariamente:

```text
PCA_SPRINT59_DEBUG_ORACLE=true
```

Execute novamente apenas o modo `adapters`. O relatório passa a incluir o tipo
da exceção e a mensagem técnica que chegou ao runner, após redação de valores
conhecidos, credenciais, Authorization, corpos, CNPJ, URLs e SQL. Quando a
Infrastructure já tiver traduzido uma exceção para um resultado seguro, a
mensagem disponível pode continuar genérica; nesse caso, `falha.etapa` é a
evidência diagnóstica principal.

Remova `PCA_SPRINT59_DEBUG_ORACLE` imediatamente depois da investigação. Não
use o opt-in em produção nem trate um relatório de falha como aprovação do gate.
Os modos `lock_holder`, `lock_expect_blocked` e `lock_expect_available` não
expõem detalhes técnicos mesmo com debug. O rollback best-effort e a proteção
contra `Close()` permanecem ativos; somente relatórios aprovados recebem o hash
sanitizado do gate.

O Composer usa `vendor/autoload.php` como autoloader. O antigo exemplo manual e
os exercícios iniciais foram preservados apenas no
[histórico didático](docs/historico/didatico/README.md).

## Como trabalhar neste projeto

Antes de implementar:

1. leia este README para compreender o projeto e sua finalidade;
2. se estiver atuando como agente, leia [`AGENTS.md`](AGENTS.md)
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
