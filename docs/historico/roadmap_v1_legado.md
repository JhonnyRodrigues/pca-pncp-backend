# ROADMAP V1 (LEGADO)
> Arquivado em 2026-07-30.
> A versão V2 ainda não foi criada.
> Consultar `docs/README.md` para o status documental vigente.
> Não utilizar este arquivo como referência arquitetural atual.

---

Este documento responde à pergunta: EM QUAL ORDEM devo construir?

---

Esse roadmap define uma progressão lógica, evita que a infraestrutura contamine o domínio e incentiva a implementação incremental seguindo TDD.

# Fases 

- FASE 0 — **Preparação do projeto**: estrutura de organização dos arquivos; instalação de ferramentas: git, composer, phpunit; documentação inicial (README.md, AGENTS.md)
- FASE 1 — **Descoberta das regras de negócio**: elaboração de documentos e fluxogramas
- FASE 2 — Modelagem da camada de **Domain**: ValueObjects, Entities, Aggregate Root, Enums de domínio, invariantes, comportamentos de negócio, eventos, Exceptions de domínio
- FASE ? — **Portas do Domain**: interfaces / Contratos que representam persistência do modelo de domínio / repositories ligados a Aggregates
- FASE ? — Implementação da camada **Application** básica: processar fluxo através de UseCases, DTOs, portas de entrada e saída, testes com mocks.
  1. Request
  2. Validator
  3. UseCase inicial
  4. Response
- FASE ? — **Portas da Application** e teste do Fluxo Completo com Mocks: contratos de PNCP, contratos de transação, contratos de configuração, __evolução do UseCase__, testes do UseCase real com mocks (sem Oracle/HTTP)
- FASE ? — Implementação da camada **Infrastructure**: Oracle, PNCP, HTTP, configuração, mapeadores e implementações concretas das portas.
- FASE ? — **Composition / Wiring**: ponto onde as implementações concretas são injetadas no UseCase.
- FASE ? — Implementação da camada **Presentation**: Controller, Presenter e adaptação do Scriptcase.
- FASE ? — **Integração** real: : banco real, homologação PNCP, fluxo completo, observabilidade, recuperação de falhas, testes end to end.

Essa sequência respeita a Clean Architecture:

- Domain: não conhece ninguém
- Application: conhece Domain
- Infrastructure: implementa as Ports (contratos / interfaces)
- Presentation: depende da resposta retornada pela Application

---

## FASE 0 — Preparação do projeto - Objetivo: deixar o ambiente pronto.

### Sprint 0.1
* Estrutura do projeto

### Sprint 0.2
* Git

### Sprint 0.3
* README.md

### Sprint 0.4
* AGENTS.md

### Sprint 0.5
* arquitetura.md

### Sprint 0.6
* modelo_dominio.md

### Sprint 0.7
* fluxo_implementacao.md

### Sprint 0.8
* roadmap.md

### Sprint 0.9
* iniciar Composer para obter Autoload PSR-4, gerenciar pacotes e o Intelephense reconhecer corretamente as classes

### Sprint 0.10
* instalar biblioteca de testes PHPUnit
* criar phpunit.xml para configurar bootstrap="vendor/autoload.php"

### Sprint 0.11
* Criar tests/PrimeiroTeste.php aplicando corretamente o namespace
* execute php.exe vendor/bin/phpunit e verifique o resultado

## FASE 1 — Modelagem do Domínio - Objetivo: construir o domínio rico antes de qualquer infraestrutura.

### Sprint 1.1 - Introdução ao DDD
* Criar Value Object Cnpj
* CnpjTest

### Sprint 1.2
* Criar Value Object AnoExecucao
* AnoExecucaoTest
  - A interface do sistema somente permite seleção do ano atual ou do próximo ano, reforçando as restrições definidas pelo Value Object.

### Sprint 1.3
* Criar Value Object Lotacao
* LotacaoTest

### Sprint 1.4
* Criar Value Object Representante
* RepresentanteTest

### Sprint 1.5
* Criar Entity UnidadeOrcamentaria
* UnidadeOrcamentariaTest

UnidadeOrcamentaria
├── Lotacao
└── Uasg

Observação: posteriormente foi descoberto que UnidadeOrcamentaria possui identidade própria e não deve ser tratada apenas como Value Object

### Sprint 1.6
* Criar Entity Demanda
* DemandaTest

Demanda
  ├── AnoExecucao
  └── UnidadeOrcamentaria

### Sprint 1.7
* Criar Entity Procedimento
* ProcedimentoTest

### Sprint 1.8
* Criar Entity HistoricoProcedimento
* HistoricoProcedimentoTest

HistoricoProcedimento
 ├── Procedimento
 ├── Representante
 └── Lotacao

### Sprint 1.9
* Criar Aggregate Root Plano
* PlanoTest

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

### Sprint 2.1
* Criar PlanoRepository

Regras:
  - Plano é o Aggregate Root principal do domínio.
  - O acesso de persistência ao Plano deve ocorrer através de PlanoRepository.
  - Demanda não possui repositório próprio neste momento.
  - PlanoRepository deve retornar o Aggregate Root Plano já reconstruído com suas Demandas.

### Sprint 2.2
* Criar HistoricoProcedimentoRepository

Regras:
  - HistoricoProcedimento não pertence ao agregado Plano.
  - HistoricoProcedimentoRepository existe para registrar auditoria das ações executadas.

### Sprint 2.3
* Criar RepresentanteRepository

Responsável por localizar um Representante a partir do usuário autenticado.

### Sprint 2.4
* Criar LotacaoRepository

Responsável por localizar a Lotacao associada a um Representante.


## FASE 3 — Casos de Uso - Agora começamos a Application.

### FASE 3A — Application: decisão e resposta   [concluída]

#### Sprint 3A.1
* PublicarPcaRequest
* PublicarPcaRequestTest

Representa todos os dados necessários para iniciar o caso de uso. Inclui:
  - Cnpj
  - AnoExecucao
  - usuarioLogado
  - credenciais

Observações:
  - Cnpj e AnoExecucao devem utilizar os respectivos Value Objects do domínio.
  - usuarioLogado representa o usuário autenticado na aplicação.
  - credenciais são necessárias para a integração com o PNCP, não pertencem ao domínio e devem permanecer na camada Application/Infrastructure.
  - PublicarPcaRequest é apenas um DTO de entrada.
  - Não contém validações ou regras de negócio.
  - Deve ser imutável.

#### Sprint 3A.2
* PublicarPcaValidator
* PublicarPcaValidatorTest
Artefatos auxiliares (se necessários):
* ValidationResult
* ValidationResultTest

Responsabilidades:
  - validar os dados recebidos em PublicarPcaRequest;
  - validar usuarioLogado;
  - validar credenciais;
  - retornar todos os erros encontrados.

Não deve validar Cnpj ou AnoExecucao

Observações:
  - As invariantes de Cnpj pertencem ao Value Object Cnpj.
  - As invariantes de AnoExecucao pertencem ao Value Object AnoExecucao.
  - O Validator não deve duplicar validações já existentes no Domain.
  - O Validator não deve conter regras de negócio.
  - O Validator pertence à camada Application.

#### Sprint 3A.3 - DECISÃO de processamento da publicacao
* Criar DecisaoProcessamentoPlano
* Evoluir Aggregate Root Plano para decidir o processamento
* Criar testes para os cenários:
  - primeira publicação
  - retificação
  - nenhuma ação

Objetivo: Permitir que o próprio Aggregate Plano determine qual ação deve ser executada.

Possíveis resultados:
  - PUBLICAR
  - RETIFICAR
  - NENHUMA_ACAO

Observações:
  - A decisão pertence ao domínio.
  - A decisão não pertence ao UseCase, Controller, Repository ou Infrastructure.
  - O UseCase deverá apenas consumir a decisão retornada pelo Aggregate Root.

#### Sprint 3A.4
* Criar ProcessarPublicacaoPcaUseCase
* Criar ProcessarPublicacaoPcaUseCaseTest
Objetivo:
Provar que a camada Application consegue orquestrar o Domain sem depender da Infrastructure.

Fluxo:
Receber Request
↓
Validar Request
↓
Consultar Planos homologados para processamento
↓
Perguntar ao Domínio
↓
Produzir resultado de aplicação


Responsabilidades:
- receber e validar o PublicarPcaRequest;
- consultar o PlanoRepository através da operação:obterPlanosHomologadosParaProcessamento(AnoExecucao);
- trabalhar com zero, um ou múltiplos Planos;
- delegar a decisão ao Aggregate Root Plano;
- consumir a decisão retornada por Plano::decidirProcessamento();
- organizar o resultado do processamento;
- retornar uma resposta simples e provisória de aplicação.

Importante! O UseCase NÃO decide:
- PUBLICAR;
- RETIFICAR;
- NENHUMA_ACAO.
Essas decisões pertencem exclusivamente ao domínio.

Observações:
Nesta sprint o UseCase deve trabalhar com múltiplos Planos, representando múltiplas Unidades Orçamentárias.
A publicação efetiva junto ao PNCP ainda não será realizada.
O objetivo é validar a orquestração entre:
- PublicarPcaRequest;
- PublicarPcaValidator;
- PlanoRepository;
- Aggregate Root Plano;
- DecisaoProcessamentoPlano.

Observação:
O modo de publicação poderá ser alterado por configuração.
* PUBLICAR_EM_UNICA_UASG = SIM
  - consolida múltiplos Planos para fins de publicação.
* PUBLICAR_EM_UNICA_UASG = NÃO
  - publica um Plano por UnidadeOrcamentaria.
Essa configuração não altera:
- o modelo de domínio;
- a composição dos Planos homologados;
- a forma como as Demandas são agrupadas internamente.
A alteração ocorre apenas no fluxo de publicação.


#### Sprint 3A.5
* PublicarPcaResponse
* ResultadoProcessamentoPlano

Objetivo:
Substituir a estrutura provisória retornada pelo UseCase por DTOs explícitos da camada Application.

Observações:
- ambos são DTOs da camada Application;
- não representam conceitos do domínio;
- representam apenas a saída produzida pelo caso de uso;
- encapsulam o resultado do processamento realizado na Sprint 3.4;
- não devem conter regras de negócio.

Neste momento, utilizar DTOs de resposta na camada Application. Presenter poderá ser introduzido futuramente caso surjam múltiplos consumidores ou formatos de saída.

---

### FASE 3B — Application: fluxo efetivo  [próxima]
- Decisões pendentes documentadas
- Porta PNCP
- Porta de transação
- Contrato de configuração
- Resultado da operação PNCP
- Evolução do UseCase com mocks
- Comportamento do Domain após publicação confirmada
- Tratamento independente das unidades

### Sprint 3B.6 — Preparação obrigatória da integração (Decisões técnicas e pendências do fluxo)

Antes de escrever código de Infrastructure, documentar e confirmar:

- qual operação da API PNCP representa a Retificação e como o plano completo será sincronizado (itens incluídos, alterados e removidos);
- se a Retificação mantém ou gera novo sequencial do PCA no PNCP;
- como consultar o PNCP antes de uma escrita, preservando-o como fonte da verdade e evitando publicação duplicada após falha de persistência no Oracle;
  Existe base técnica para consultar o estado no PNCP antes de decidir persistir algo como publicado no banco de dados. O PNCP disponibiliza endpoints de consulta de PCA (Planos) por órgão e ano, de consulta de planos por unidade e ano, e os retornos incluem informações como sequencialPca, numeroControlePNCP, dataPublicacaoPncp e dataAtualizacao.
  Veja trecho extraído da documentação oficial:
    6.4. Número de Controle do PCA (id pca pncp)
    Máscara: 99999999999999-0-999999/9999
    Cada PCA receberá um número de controle composto por:
    - CNPJ do Órgão/Entidade do PCA (14 dígitos)
    - Dígito 0 — marcador que indica tratar-se de um plano de contratação anual
    - Número sequencial do Plano no PNCP *
    - Ano do Plano (4 dígitos)

- qual é a unidade de processamento e de falha quando `PUBLICAR_EM_UNICA_UASG = SIM`;
    - A unidade de processamento pode ser consultada na query SELECT VALOR FROM PAC_CONFIGURACOES WHERE CHAVE = 'UASG_CONSOLIDADORA'
    - Se ao enviar um único payload consolidado ao PNCP, então não existirá mais falhas independentes por Unidades Orçamentárias. Nesse caso, existirá uma falha da publicação consolidada inteira.
- qual UASG recebe a publicação consolidada e de onde vem essa configuração;
- qual identificador de negócio corresponde ao procedimento `PUBLICAÇÃO DE PLANO` e como o catálogo será consultado;
- como os códigos de Lotação do SGI são mapeados para UASG;
- qual mecanismo de conexão Oracle será usado no ambiente Scriptcase (`OCI8`, `PDO_OCI` ou conexão fornecida pelo host);
- qual transporte HTTP será usado e quais extensões/dependências estão disponíveis no PHP de desenvolvimento e de produção;
- como conciliar o CNPJ alfanumérico do Domain com o formato de CNPJ documentado pelo contrato vigente do PNCP.
    [RESOLVIDO] Não é necessária uma adaptação entre o Domain e o PNCP para converter CNPJ alfanumérico em numérico. O PNCP já aceita o novo padrão.
A implementação atual de Cnpj está alinhada ao formato: /^[A-Z0-9]{12}[0-9]{2}$/

Critério de conclusão:

- `modelo_dominio.md` e `fluxo_implementacao.md` não possuem contradições sobre Retificação, sequencial PNCP, fonte da verdade e isolamento de falhas;
- contratos Oracle e PNCP utilizados pelo projeto estão registrados com versão;
- nenhuma regra ausente é inferida pela implementação.

### Sprint 3B.7 — Portas para o fluxo efetivo (Portas faltantes para o fluxo real)

Criar contratos que o UseCase precisa para executar o fluxo real.
Definir na camada Application, com nomes orientados à intenção:
- porta para publicar, retificar e consultar o estado do PCA no PNCP;
- porta para controlar transação sem expor Oracle ao UseCase. Sugestão: adotar nome neutro como TransactionManager, métodos como: iniciar(), confirmar(), desfazer(); A implementação concreta na Infrastructure poderia ser: OracleTransactionManager;
- contrato tipado para obter a configuração do modo de publicação;
- DTO de retorno da integração PNCP, sem tipos HTTP ou estruturas JSON;
- tratamento de falhas externas que permita identificar a unidade afetada.

Se a Sprint 3B.6 confirmar necessidade, ajustar ou criar o contrato para localizar o procedimento `PUBLICAÇÃO DE PLANO`.

Não implementar Oracle ou HTTP nesta sprint.

### Sprint 3B.8 — Evoluir o UseCase antes dos adaptadores (Contemplar Fluxo completo com mocks/fakes)

Esta sprint concentra a evolução do fluxo efetivo do UseCase e poderá ser dividida em subtarefas menores, mantendo TDD e sem criar adaptadores concretos. Sugestão:
#### Sprint 3B.2A — Evoluir UseCase com portas PNCP e configuração
#### Sprint 3B.2B — Evoluir UseCase com transação e persistência
#### Sprint 3B.2C — Evoluir UseCase com falhas, rollback e recuperação
#### Sprint 3B.2D — Adicionar comportamento mínimo no Domain para registrar publicação confirmada

Evoluir `ProcessarPublicacaoPcaUseCase` usando apenas mocks/fakes das portas.

Cobrir por TDD:

- autenticação realizada uma vez por execução;
- zero, um ou múltiplos Planos;
- `NENHUMA_ACAO` sem chamada de escrita ao PNCP;
- `PUBLICAR` e `RETIFICAR` delegadas à porta PNCP;
- aplicação do modo de publicação configurado fora do Repository e do Domain;
- persistência de Plano, Demandas e histórico somente após sucesso no PNCP;
- commit no sucesso e rollback na falha;
- falha de uma unidade sem impedir as demais, respeitando a decisão da Sprint 3B.6;
- resposta com sucesso ou erro de cada unidade processada;
- comportamento de recuperação quando o PNCP confirma a operação, mas a persistência Oracle falha.

Nesta sprint também deve ser adicionado ao Domain, com testes, apenas o comportamento mínimo necessário para registrar uma publicação confirmada sem expor setters de persistência. Explicação: O campo dataPublicacao define o estado do Plano, mas se a publicação for confirmada pelo PNCP, o domínio precisa permitir algo como: $plano->registrarPublicacaoConfirmada(...);

— Application completa com mocks
Antes de escrever Oracle e PNCP reais, você evolui o UseCase usando fakes/mocks.
Aqui você testa:
1. autentica uma vez
2. publica
3. retifica
4. não chama PNCP em NENHUMA_ACAO
5. controla transação
6. registra histórico
7. persiste só depois de sucesso no PNCP
8. trata falha parcial
Essa fase é muito valiosa porque prova o fluxo sem brigar ainda com Oracle, HTTP, token, JSON etc.

## FASE 4 — Implementação da Infrastructure (Agora cada classe concreta implementa uma interface já conhecida.)

Objetivo: completar as fronteiras de integração e implementar os adaptadores de Oracle e PNCP sem transferir regras de negócio para a Infrastructure.

Esta fase deve continuar o desenvolvimento de dentro para fora:

```text
Decisões documentadas
        ↓
Portas da Application
        ↓
UseCase testado com mocks
        ↓
Adaptadores de Infrastructure
        ↓
Testes de integração
```

Importante:

- Infrastructure implementa contratos definidos pelas camadas internas;
- o UseCase depende de interfaces, nunca de `OracleConnection`, `OraclePlanoRepository` ou `PncpClient` concretos;
- Repository consulta e persiste, mas não decide Publicação, Retificação, agrupamento por UASG ou limites transacionais;
- o modo `PUBLICAR_EM_UNICA_UASG` pertence ao fluxo da Application e não altera a consulta do `PlanoRepository` nem a composição do Aggregate Plano;
- cada classe concreta deve ser criada por TDD e possuir teste correspondente;
- testes com Oracle e PNCP reais permanecem na Fase 6.


### Sprint 4.1 — Configuração e conexão Oracle (OracleConnection)

* Implementar o adaptador de configuração.
* Implementar `OracleConnection`.
* Implementar o gerenciador de transações Oracle.
* Criar testes para os três componentes usando doubles do mecanismo escolhido.

Responsabilidades:

- abrir/receber e liberar a conexão conforme o ciclo de vida definido;
- oferecer mecanismo seguro para preparação e execução de comandos parametrizados;
- iniciar, confirmar e desfazer transações;
- não conter SQL de negócio nem reconstruir entidades;
- não armazenar credenciais no código ou em mensagens de erro.

Todos os repositórios envolvidos em uma unidade de processamento devem receber a mesma conexão por Dependency Injection.

### Sprint 4.2 — Mapeamento Oracle do Aggregate Plano (Mapeadores Oracle)

* Criar o mapeamento entre linhas Oracle e objetos do Domain.
* Criar testes do mapeamento com linhas controladas, sem banco real.

Responsabilidades:

- converter nomes, tipos, datas e identificadores do Oracle;
- reconstruir `UnidadeOrcamentaria`, `Uasg`, `Demanda` e `Plano`;
- agrupar Demandas no respectivo Aggregate Plano;
- delegar invariantes aos construtores e comportamentos do Domain;
- manter mapeamentos SGI/Oracle/UASG fora do Domain.

### Sprint 4.3 — OraclePlanoRepository (Repositório agreggate)

* Implementar `OraclePlanoRepository`.
* Criar `OraclePlanoRepositoryTest` com conexão/resultado simulados.

Responsabilidades:

- implementar `PlanoRepository`;
- executar consulta parametrizada por `AnoExecucao`;
- obter somente os Planos homologados necessários ao processamento;
- devolver a versão homologada mais recente de cada UnidadeOrcamentaria, reconstruída com todas as suas Demandas;
- persistir somente as alterações previstas no contrato do Repository. Exemplo: data de publicação, sequencial PNCP e demais campos definidos pelos contratos internos.

O Repository não deve:

- chamar `Plano::decidirProcessamento()`;
- ler `PUBLICAR_EM_UNICA_UASG`;
- consolidar Planos;
- abrir, confirmar ou desfazer a transação do caso de uso.

### Sprint 4.4 — Repositórios de responsável e lotação (Repositórios auxiliares Oracle)

* Implementar `OracleRepresentanteRepository` e seu teste.
* Implementar `OracleLotacaoRepository` e seu teste.

Responsabilidades:

- localizar o Representante pelo usuário autenticado;
- localizar a Lotacao associada ao Representante;
- reconstruir os Value Objects sem regras adicionais.

### Sprint 4.5 — Auditoria no Oracle (Registro de histórico do procedimento publicação)

* Implementar a consulta do catálogo de Procedimentos definida na Sprint 4.1, caso necessária.
* Implementar `OracleHistoricoProcedimentoRepository`.
* Criar testes para os adaptadores.

Responsabilidades:

- localizar o procedimento pelo identificador de negócio documentado;
- registrar `HistoricoProcedimento` na mesma transação do Plano;
- não construir mensagens de auditoria nem decidir quando auditar.

### Sprint 4.6 — Transporte HTTP e autenticação PNCP (PncpAuthenticator)

* Definir/adotar o transporte HTTP conforme a decisão da Sprint 3B.6.
* Implementar `PncpAuthenticator`.
* Criar `PncpAuthenticatorTest` com respostas HTTP simuladas.

Cobrir:

- envio das credenciais ao endpoint correto;
- leitura do token no header documentado pelo PNCP;
- reutilização do token durante uma execução;
    - O manual informa que a API de login retorna JWT no header Authorization, após Bearer, e que o token tem validade de 1 hora. Isso justifica testar reutilização de token, expiração e leitura do header.
- expiração, respostas inválidas e falhas HTTP;
- ausência de credenciais e token em logs/exceções.

### Sprint 4.7 — Payload PNCP (PncpPayloadBuilder)

* Implementar `PncpPayloadBuilder`.
* Criar `PncpPayloadBuilderTest`.

Responsabilidades:

- transformar o Plano ou o grupo de Planos definido pela Application no contrato vigente do PNCP;
- mapear tipos, nomes de campos, datas e identificadores externos;
- não consultar banco ou configuração;
- não conter regras de negócio;
- não validar invariantes do Domain.

Os testes devem comparar a estrutura completa esperada pelo contrato do PNCP, incluindo múltiplas Demandas e numeração dos itens.

### Sprint 4.8 — Cliente PNCP

* Implementar `PncpClient` como adaptador da porta definida na Sprint 4.1.
* Criar `PncpClientTest` usando mocks do transporte HTTP.

Cobrir:

- consulta do estado atual do PCA;
- primeira publicação;
- estratégia de Retificação confirmada na Sprint 3B.6;
- uso do token e do payload recebidos;
- extração e conversão do identificador retornado pelo PNCP;
- mapeamento consistente de respostas de sucesso e erro;
- timeout e resposta malformada.

`PncpClient` conhece HTTP e o contrato PNCP, mas não decide se um Plano deve ser publicado ou retificado.

## Fase 5 — Composition / Wiring (Validação isolada da Infrastructure)

Objetivo: Montar o grafo de dependências da aplicação.

Executar a suíte unitária completa e adicionar testes de integração controlados, sem credenciais reais, para verificar:

- wiring/ligação/conexão entre portas e adaptadores por Dependency Injection;
- compartilhamento da conexão na unidade transacional;
- reconstrução do Aggregate a partir de fixtures de linhas Oracle;
- autenticação e chamadas PNCP por servidor HTTP simulado;
- ausência de dependências de Infrastructure no Domain.

Responsabilidades:
- instanciar implementações concretas;
- injetar dependências no UseCase;
- manter Controller fino;
- evitar que Controller conheça detalhes de construção da infraestrutura.

Não realizar ainda chamadas ao Oracle ou PNCP reais. Essas validações pertencem à Fase 7.

## FASE 6 — Apresentação

### Sprint 6.1
* PcaController
* PcaControllerTest
Controller não monta tudo. Controller recebe ou obtém o UseCase já montado.
Ele fica fino:
1. recebe array do Scriptcase
2. converte para Request
3. chama UseCase
4. converte Response para saída
Sem regra de negócio.

## FASE 7 — Integração
Objetivo: validar o funcionamento do backend com integrações reais ou simuladas de ponta a ponta.
Aqui sim entram:
- Oracle real
- PNCP homologação
- testes end to end
- credenciais reais controladas
- massa de dados real/controlada

### Sprint 7.1
* Testar OraclePlanoRepository com dados reais ou base controlada

### Sprint 7.2
* Testar autenticação PNCP em ambiente de homologação

### Sprint 7.3
* Testar envio de Publicação ao PNCP em ambiente de homologação

### Sprint 7.4
* Testar envio de Retificação ao PNCP em ambiente de homologação

### Sprint 7.5
* Testar fluxo completo:
  - entrada Scriptcase
  - caso de uso
  - Oracle
  - PNCP
  - atualização de dados
  - resposta final

### Sprint 7.6
* Validar comportamento em falha parcial:
  - uma UnidadeOrcamentaria falha
  - demais unidades continuam sendo processadas
