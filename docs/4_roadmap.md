Este documento responde à pergunta: EM QUAL ORDEM devo construir?

Esse roadmap define uma progressão lógica, evita que a infraestrutura contamine o domínio e incentiva a implementação incremental seguindo TDD.

# FASE 0 — Preparação do projeto
# FASE 1 — Modelagem do Domínio
# FASE 2 — Portas
# FASE 3 — Caso de Uso
# FASE 4 — Infraestrutura
# FASE 5 — Apresentação
# FASE 6 — Integração

Essa sequência respeita a Clean Architecture. Principalmente por causa disso:

- Domain: não conhece ninguém
- Application: conhece Domain
- Infrastructure: implementa portas
- Presentation: depende de Application

---



---

# FASE 0 — Preparação do projeto - Objetivo: deixar o ambiente pronto.

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
□ execute vendor/bin/phpunit e verifique o resultado

# FASE 1 — Modelagem do Domínio - Objetivo: construir o domínio rico antes de qualquer infraestrutura.

Sprint 1.1 - Introdução ao DDD

□ Criar Value Object Cnpj
□ CnpjTest

Sprint 1.2
□ Criar Value Object AnoExecucao
□ AnoExecucaoTest

Sprint 1.3
□ Criar Value Object UnidadeOrcamentaria
□ UnidadeOrcamentariaTest

Sprint 1.4
□ Criar Entity Demanda
□ DemandaTest

Sprint 1.5
□ Criar Entity Procedimento
□ ProcedimentoTest

Sprint 1.6
□ Criar Aggregate Root Plano
□ PlanoTest
Aqui o Plano já nasce contendo Demanda e Procedimento.

# FASE 2 — Portas - Agora definimos as interfaces.

Sprint 2.1
□ PlanoRepository

Plano é Aggregate Root

# FASE 3 — Casos de Uso - Agora começamos a Application.

Sprint 3.1
□ PublicarPcaRequest
□ PublicarPcaRequestTest

Sprint 3.2
□ PublicarPcaValidator
□ PublicarPcaValidatorTest

Sprint 3.3
□ PublicarPcaResponse
□ ResultadoProcessamentoPlano

Sprint 3.4
□ PublicarPcaUseCase
□ PublicarPcaUseCaseTest

Sprint 3.5
□ RetificarPcaUseCase
□ RetificarPcaUseCaseTest

Usar DTO pois é mais simples que Presenter

# FASE 4 — Infraestrutura - Agora implementamos.

Sprint 4.1
□ OracleConnection

Sprint 4.2
□ OraclePlanoRepository

Sprint 4.3
□ OracleDemandaRepository

Sprint 4.4
□ OracleProcedimentoRepository

Sprint 4.5
□ PncpAuthenticator

Sprint 4.6
□ PncpPayloadBuilder
□ PncpPayloadBuilderTest (transforma entidades do domínio em JSON)

Sprint 4.7
□ PncpClient
□ PncpClientTest (Usando mocks para as respostas HTTP.)

# FASE 5 — Apresentação

Sprint 5.1
□ PcaController
□ PcaControllerTest

# FASE 6 — Integração

Sprint 6.1
□ Teste OracleRepository

Sprint 6.2
□ Teste PNCP

Sprint 6.3
□ Teste fluxo completo