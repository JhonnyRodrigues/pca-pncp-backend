Este documento responde à pergunta: EM QUAL ORDEM devo construir?

---

Esse roadmap define uma progressão lógica, evita que a infraestrutura contamine o domínio e incentiva a implementação incremental seguindo TDD.

# Fases 

- FASE 0 — Preparação do projeto
- FASE 1 — Modelagem do Domínio
- FASE 2 — Portas
- FASE 3 — Caso de Uso
- FASE 4 — Infraestrutura
- FASE 5 — Apresentação
- FASE 6 — Integração

Essa sequência respeita a Clean Architecture:

- Domain: não conhece ninguém
- Application: conhece Domain
- Infrastructure: implementa interfaces (portas)
- Presentation: depende de Application

---

## FASE 0 — Preparação do projeto - Objetivo: deixar o ambiente pronto.

Sprint 0.1
□ Estrutura do projeto

Sprint 0.2
□ Git

Sprint 0.3
□ README.md

Sprint 0.4
□ AGENTS.md

Sprint 0.5
□ arquitetura.md

Sprint 0.6
□ modelo_dominio.md

Sprint 0.7
□ fluxo_implementacao.md

Sprint 0.8
□ roadmap.md

Sprint 0.9
□ iniciar Composer para obter Autoload PSR-4, gerenciar pacotes e o Intelephense reconhecer corretamente as classes

Sprint 0.10
□ instalar biblioteca de testes PHPUnit
□ criar phpunit.xml para configurar bootstrap="vendor/autoload.php"

Sprint 0.11
□ Criar tests/PrimeiroTeste.php aplicando corretamente o namespace
□ execute php.exe vendor/bin/phpunit e verifique o resultado

## FASE 1 — Modelagem do Domínio - Objetivo: construir o domínio rico antes de qualquer infraestrutura.

Sprint 1.1 - Introdução ao DDD

□ Criar Value Object Cnpj
□ CnpjTest

Sprint 1.2
□ Criar Value Object AnoExecucao
□ AnoExecucaoTest

Sprint 1.3
□ Criar Value Object Lotacao
□ LotacaoTest

Sprint 1.4
□ Criar Value Object Representante
□ RepresentanteTest

Sprint 1.5
□ Criar Entity UnidadeOrcamentaria
□ UnidadeOrcamentariaTest
UnidadeOrcamentaria
├── Lotacao
└── Uasg
  - Observação: posteriormente foi descoberto que UnidadeOrcamentaria possui identidade própria e não deve ser tratada apenas como Value Object

Sprint 1.6
□ Criar Entity Demanda
□ DemandaTest
Demanda
  ├── AnoExecucao
  └── UnidadeOrcamentaria

Sprint 1.7
□ Criar Entity Procedimento
□ ProcedimentoTest

Sprint 1.8
□ Criar Entity HistoricoProcedimento
□ HistoricoProcedimentoTest
HistoricoProcedimento
 ├── Procedimento
 ├── Representante
 └── Lotacao

Sprint 1.9
□ Criar Aggregate Root Plano
□ PlanoTest
O Plano é o Aggregate Root do domínio.
O Plano nasce a partir da homologação de Demandas
consolidadas.

A identidade lógica de um Plano é composta por:
- AnoExecucao
- UnidadeOrcamentaria
- SequencialVersao

Todo Plano:
- possui uma ou mais Demandas;
- não pode existir sem Demandas;
- possui data de homologação;
- pode possuir data de publicação;
- representa uma versão específica do planejamento.

Procedimentos e históricos não pertencem ao agregado Plano.
Plano
  ├── idPlano
  ├── AnoExecucao
  ├── UnidadeOrcamentaria
  ├── SequencialVersao
  └── Demandas


## FASE 2 — Portas

Objetivo: definir os contratos (interfaces) necessários para a Application sem implementar infraestrutura.

Sprint 2.1
□ Criar PlanoRepository
  Regras:
  - Plano é o Aggregate Root principal do domínio.
  - O acesso de persistência ao Plano deve ocorrer através de PlanoRepository.
  - Demanda não possui repositório próprio neste momento.
  - PlanoRepository deve retornar o Aggregate Root Plano já reconstruído com suas Demandas.

Sprint 2.2
□ Criar HistoricoProcedimentoRepository
  Regras:
  - HistoricoProcedimento não pertence ao agregado Plano.
  - HistoricoProcedimentoRepository existe para registrar auditoria das ações executadas.

Sprint 2.3
□ Criar RepresentanteRepository
  - Responsável por localizar um Representante a partir do usuário autenticado.

Sprint 2.4
□ Criar LotacaoRepository
  - Responsável por localizar a Lotacao associada a um Representante.


## FASE 3 — Casos de Uso - Agora começamos a Application.

Sprint 3.1
□ PublicarPcaRequest
□ PublicarPcaRequestTest
  - São os Dados necessários para iniciar o caso de uso de publicação.

Sprint 3.2
□ PublicarPcaValidator
□ PublicarPcaValidatorTest

Sprint 3.3
□ PublicarPcaResponse
□ ResultadoProcessamentoPlano (é um DTO de aplicação e não um conceito de domínio.)

Sprint 3.4
□ Modelar decisão de processamento por UnidadeOrcamentaria
□ Criar testes para cenários:
  - primeira publicação
  - retificação
  - nenhuma ação
Observação:
Avaliar a necessidade de modelar um serviço de domínio ou objeto de decisão para determinar se uma UnidadeOrcamentaria deve:
  - publicar
  - retificar
  - não executar nenhuma ação
Essa decisão depende do estado dos Planos homologados/publicados e não deve ficar no UseCase, Repository, Controller ou Infrastructure.

Sprint 3.5
□ Criar ProcessarPublicacaoPcaUseCase
□ Criar ProcessarPublicacaoPcaUseCaseTest

Responsabilidade:
- orquestrar o fluxo de publicação/retificação
- consultar portas necessárias
- delegar a decisão ao domínio
- acionar infraestrutura através de interfaces
- montar resposta de aplicação

Neste momento, utilizar DTOs de resposta na camada Application.
Presenter poderá ser introduzido futuramente caso surjam múltiplos consumidores ou formatos de saída.

## FASE 4 — Infraestrutura - Agora implementamos.

Sprint 4.1
□ OracleConnection

Sprint 4.2
□ OraclePlanoRepository
  - Responsável por reconstruir o Aggregate Plano com suas Demandas.

Sprint 4.3
□ OracleRepresentanteRepository
  - Responsável por localizar o Representante a partir do usuário autenticado.

Sprint 4.4
□ OracleLotacaoRepository
  - Responsável por localizar a Lotacao associada ao Representante.

Sprint 4.5
□ OracleHistoricoProcedimentoRepository
  - Responsável por registrar auditoria do procedimento executado.

Sprint 4.6
□ PncpAuthenticator

Sprint 4.7
□ PncpPayloadBuilder
□ PncpPayloadBuilderTest
  Responsabilidade:
  - transformar objetos do domínio em payload compatível com o PNCP
  - não conter regra de negócio
  - não validar invariantes do dom


Sprint 4.8
□ PncpClient
□ PncpClientTest (Usando mocks para as respostas HTTP.)

## FASE 5 — Apresentação

Sprint 5.1
□ PcaController
□ PcaControllerTest


## FASE 6 — Integração

Objetivo: validar o funcionamento do backend com integrações reais ou simuladas de ponta a ponta.

Sprint 6.1
□ Testar OraclePlanoRepository com dados reais ou base controlada

Sprint 6.2
□ Testar autenticação PNCP em ambiente de homologação

Sprint 6.3
□ Testar envio de Publicação ao PNCP em ambiente de homologação

Sprint 6.4
□ Testar envio de Retificação ao PNCP em ambiente de homologação

Sprint 6.5
□ Testar fluxo completo:
  - entrada Scriptcase
  - caso de uso
  - Oracle
  - PNCP
  - atualização de dados
  - resposta final

Sprint 6.6
□ Validar comportamento em falha parcial:
  - uma UnidadeOrcamentaria falha
  - demais unidades continuam sendo processadas