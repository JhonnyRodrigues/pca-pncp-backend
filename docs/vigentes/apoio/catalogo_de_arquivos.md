# Catálogo navegável de arquivos ativos

**Classificação documental:** Apoio  
**Status:** Vigente  
**Autoridade/Escopo:** índice do código ativo; não define comportamento

Este catálogo cobre código de produção, testes e configuração. `vendor/`,
documentação histórica e investigações não fazem parte do fluxo executável.

## 1. Domain

### Entities, Facts e Policy

| Arquivo | Função |
|---|---|
| `Entities/Demanda.php` | Representa uma necessidade homologada com identidade, situação e dados exatos. |
| `Entities/Plano.php` | Aggregate Root da fotografia homologada de uma UO e exercício. |
| `Entities/UnidadeOrcamentaria.php` | Entidade organizacional que relaciona Lotação e UASG. |
| `Facts/HistoricoProcedimento.php` | Fato imutável de procedimento concluído, preservado no modelo. |
| `Policies/PoliticaProcessamentoPlano.php` | Compara fotografia desejada, estado local e PCA observado para decidir a ação. |
| `Policies/ResultadoDecisaoProcessamentoPlano.php` | Resultado tipado da política, com inclusões e exclusões. |
| `Ports/PlanoRepository.php` | Contrato do Aggregate para descoberta, reconstrução e confirmação. |

### Value Objects

| Arquivo | Função |
|---|---|
| `ValueObjects/AnoExecucao.php` | Valida o exercício corrente ou seguinte. |
| `ValueObjects/Cnpj.php` | Normaliza e valida CNPJ alfanumérico. |
| `ValueObjects/DecimalExato.php` | Preserva uma string decimal válida sem `float`. |
| `ValueObjects/EstadoOperacionalPublicacao.php` | Representa não publicado, publicado ou excluído e seu sequencial. |
| `ValueObjects/EvidenciaCancelamentoHomologado.php` | Identifica o cancelamento homologado que esvaziou uma unidade. |
| `ValueObjects/IdentidadePlano.php` | Identidade lógica formada por UO e exercício. |
| `ValueObjects/IdentidadeUnidadeOrcamentaria.php` | Identificador estável da UO. |
| `ValueObjects/Lotacao.php` | Identifica e descreve uma lotação administrativa. |
| `ValueObjects/PcaObservado.php` | Fotografia abstrata do PCA encontrado no PNCP. |
| `ValueObjects/Representante.php` | Identifica a pessoa responsável pela execução. |
| `ValueObjects/SequencialPca.php` | Encapsula o sequencial externo do PCA. |
| `ValueObjects/Uasg.php` | Identifica a unidade externa de publicação. |

### Enums e Exceptions

| Arquivo | Função |
|---|---|
| `Enums/DecisaoProcessamentoPlano.php` | Nomeia intenção ainda não comprovada: PUBLICAR, RETIFICAR, EXCLUIR_PCA e NENHUMA_ACAO. |
| `Enums/EstadoPublicacao.php` | Nomeia os estados operacionais da publicação. |
| `Enums/ProcedimentoPublicacaoPca.php` | Classificação histórica de uma ação comprovada. Cataloga os procedimentos auditáveis. |
| `Enums/SituacaoDemanda.php` | Define situações e participação na fotografia vigente. |
| `Exceptions/AnoExecucaoInvalidoException.php` | Falha específica de exercício inválido. |
| `Exceptions/ConflitoEstadoPublicacaoException.php` | Sinaliza estado de publicação semanticamente conflitante. |
| `Exceptions/HistoricoProcedimentoException.php` | Protege invariantes do fato histórico. |
| `Exceptions/PlanoException.php` | Exceção-base das invariantes do Plano. |
| `Exceptions/UnidadeOrcamentariaInvalidaException.php` | Protege invariantes da UO. |

## 2. Application

### Use case e colaboradores

| Arquivo | Função |
|---|---|
| `UseCases/PublicarPca/PublicarPcaRequest.php` | Entrada tipada com CNPJ e exercício já válidos. |
| `UseCases/PublicarPca/PublicarPcaUseCase.php` | Orquestrador global e produtor da Response final. |
| `UseCases/PublicarPca/PreparadorExecucaoPublicacaoPca.php` | Resolve contexto, estratégia, unidades e sessão PNCP. |
| `UseCases/PublicarPca/CoordenadorVarreduraUnidades.php` | Visita unidades, preserva resultados e interrompe em falha sistêmica. |
| `UseCases/PublicarPca/ProcessadorUnidadeInterno.php` | Encadeia preparação, decisão e despacho de uma unidade. |
| `UseCases/PublicarPca/PreparadorUnidadeSobExclusividade.php` | Abre lock/transação e reconstrói a fotografia vigente. |
| `UseCases/PublicarPca/DecisorUnidadeSobExclusividade.php` | Observa o PNCP e consulta a política do Domain. |
| `UseCases/PublicarPca/DespachadorComandosUnidade.php` | Liga executor e finalizador, protegendo exceções e rollback. |
| `UseCases/PublicarPca/ExecutorComandosUnidade.php` | Executa os comandos externos correspondentes à decisão. |
| `UseCases/PublicarPca/FinalizadorProcessamentoUnidadeInterno.php` | Observa, reconcilia, persiste, audita e encerra a unidade. |
| `UseCases/PublicarPca/DesfazedorUnidadeProcessamento.php` | Centraliza rollback e prevalência de sua falha. |

### Commands e Contracts

| Arquivo | Função |
|---|---|
| `Commands/ComandoPublicarPca.php` | Ordem de criação do PCA com a fotografia completa. |
| `Commands/ComandoInserirItensPca.php` | Ordem de inclusão de Demandas no PCA ativo. |
| `Commands/ComandoExcluirItensPca.php` | Ordem de exclusão de itens com justificativa. |
| `Commands/ComandoExcluirPca.php` | Ordem de exclusão integral do PCA. |
| `Contracts/SessaoPncp.php` | Tipo opaco da sessão autenticada. |
| `Contracts/ProcessadorUnidade.php` | Contrato consumido pelo coordenador global. |
| `Contracts/FinalizadorProcessamentoUnidade.php` | Contrato entre despacho e finalização da unidade. |

### Ports

| Arquivo | Função |
|---|---|
| `Ports/AutenticadorPncp.php` | Autentica uma vez e devolve sessão opaca. |
| `Ports/ContextoUsuarioPort.php` | Resolve Representante e Lotação conjuntamente. |
| `Ports/EscritorPncp.php` | Publica e executa inclusões ou exclusões no PNCP. |
| `Ports/EstadoOperacionalPublicacaoPort.php` | Consulta e persiste a projeção operacional. |
| `Ports/EstrategiaPublicacaoPort.php` | Consulta e adota a estratégia anual. |
| `Ports/EvidenciaCancelamentoHomologadoPort.php` | Obtém a evidência necessária à exclusão integral. |
| `Ports/LeitorPncp.php` | Observa PCA conhecido e localiza candidatos. |
| `Ports/RegistroAuditoriaPort.php` | Persiste uma intenção de auditoria confirmada. |
| `Ports/UnidadeDeTrabalhoPort.php` | Inicia, confirma ou desfaz unidade transacional. |

### Value Objects de orquestração

| Arquivo | Função |
|---|---|
| `ValueObjects/ChaveUnidadeTrabalho.php` | Identifica o alvo da exclusividade transacional: `ESTRATEGIA_ANUAL` para a estratégia de um exercício ou `UNIDADE_EXTERNA` para uma publicação específica. |
| `ValueObjects/ContextoFinalizacaoProcessamentoUnidade.php` | Reúne fatos necessários à observação e persistência finais. |
| `ValueObjects/ContextoUnidadeComDecisao.php` | Transporta contexto exclusivo, PCA observado e decisão. |
| `ValueObjects/ContextoUnidadeSobExclusividade.php` | Transporta Planos e estado relidos sob lock. |
| `ValueObjects/ContextoUsuario.php` | Agrupa Representante e Lotação autenticados. |
| `ValueObjects/CorrelacaoExecucao.php` | Identificador seguro devolvido na Response. |
| `ValueObjects/EstrategiaPublicacao.php` | Representa modo individual ou consolidado. |
| `ValueObjects/FalhaSegura.php` | Código e mensagem que podem atravessar fronteiras. |
| `ValueObjects/IntencaoRegistroHistorico.php` | Conteúdo de auditoria pronto, ainda sem instante Oracle. |
| `ValueObjects/UnidadeExternaPublicacao.php` | Alvo individual ou consolidado processado atomicamente. |

### Results

| Arquivo | Função |
|---|---|
| `Results/PublicarPcaResponse.php` | Resultado global, contagens, resultados unitários e correlação. |
| `Results/DesfechoProcessamentoUnidade.php` | União entre resultado unitário e falha sistêmica. |
| `Results/ResultadoProcessamentoUnidade.php` | Resultado terminal conhecido de uma unidade. |
| `Results/ResultadoVarreduraUnidades.php` | Agrega a visita sequencial das unidades. |
| `Results/ResultadoPreparacaoPublicacaoPca.php` | Resultado da preparação global. |
| `Results/ResultadoResolucaoContextoUsuario.php` | Resultado da resolução do usuário. |
| `Results/ResultadoAutenticacaoPncp.php` | Resultado da autenticação externa. |
| `Results/ResultadoConsultaEstrategia.php` | Estratégia disponível, ausente, inválida ou falha. |
| `Results/ResultadoAdocaoEstrategia.php` | Confirma ou rejeita a adoção anual. |
| `Results/ResultadoInicioUnidadeTrabalho.php` | Iniciada, bloqueada ou falha sistêmica. |
| `Results/ResultadoFinalizacaoUnidadeTrabalho.php` | Resultado de commit ou rollback. |
| `Results/ResultadoConsultaEstadoOperacional.php` | Estado disponível, conflitante ou indisponível. |
| `Results/ResultadoBuscaCandidatosPca.php` | Resultado da busca paginada abstrata de candidatos. |
| `Results/ResultadoObservacaoPca.php` | PCA encontrado, ausência definitiva ou observação insegura. |
| `Results/ResultadoPublicacaoPncp.php` | Resultado da criação com possível sequencial. |
| `Results/ResultadoEscritaPncp.php` | Resultado de inclusão ou exclusão. |
| `Results/ResultadoConsultaEvidenciaCancelamento.php` | Resultado da busca da evidência homologada. |
| `Results/ResultadoPersistenciaLocal.php` | Resultado de estado local ou auditoria. |

### Enums da Application

| Arquivo | Função |
|---|---|
| `Enums/EscopoUnidadeTrabalho.php` | Distingue estratégia anual e unidade externa. |
| `Enums/ModoPublicacao.php` | Distingue individual e consolidado. |
| `Enums/SituacaoAutenticacaoPncp.php` | Estados da autenticação. |
| `Enums/SituacaoBuscaCandidatosPca.php` | Estados da busca de candidatos. |
| `Enums/SituacaoConsultaEstadoOperacional.php` | Estados da consulta local. |
| `Enums/SituacaoConsultaEstrategia.php` | Estados da estratégia anual. |
| `Enums/SituacaoConsultaEvidenciaCancelamento.php` | Estados da evidência de cancelamento. |
| `Enums/SituacaoDesfechoProcessamentoUnidade.php` | Tipo do desfecho unitário. |
| `Enums/SituacaoEscritaPncp.php` | Aceita, rejeitada, incerta ou sistêmica. |
| `Enums/SituacaoFinalizacaoUnidadeTrabalho.php` | Resultado de finalização transacional. |
| `Enums/SituacaoInicioUnidadeTrabalho.php` | Resultado da aquisição da unidade. |
| `Enums/SituacaoObservacaoPca.php` | Estados da observação externa. |
| `Enums/SituacaoPersistenciaLocal.php` | Estados da persistência local. |
| `Enums/SituacaoPreparacaoPublicacaoPca.php` | Preparada, sem unidades ou interrompida. |
| `Enums/SituacaoProcessamentoUnidade.php` | Estados terminais expostos por unidade. |
| `Enums/SituacaoPublicacaoPca.php` | Quatro estados globais da execução. |
| `Enums/SituacaoResolucaoContextoUsuario.php` | Estados da resolução do usuário. |

## 3. Testes

### Domain

| Arquivo | Demonstração principal |
|---|---|
| `Entities/DemandaTest.php` | Invariantes e valores exatos da Demanda. |
| `Entities/PlanoTest.php` | Identidade, fotografia, versão e confirmação do Aggregate. |
| `Entities/UnidadeOrcamentariaTest.php` | Invariantes da UO. |
| `Enums/DecisaoProcessamentoPlanoTest.php` | Catálogo de decisões. |
| `Enums/SituacaoDemandaTest.php` | Participação das situações na fotografia. |
| `Facts/HistoricoProcedimentoTest.php` | Invariantes do fato histórico. |
| `Policies/PoliticaProcessamentoPlanoTest.php` | Matriz de decisão e reconciliação pura. |
| `ValueObjects/AnoExecucaoTest.php` | Faixa válida de exercícios. |
| `ValueObjects/CnpjTest.php` | CNPJ numérico e alfanumérico. |
| `ValueObjects/DecimalExatoTest.php` | Preservação decimal e rejeição de `float`. |
| `ValueObjects/EstadoOperacionalPublicacaoTest.php` | Coerência entre estado e sequencial. |
| `ValueObjects/EvidenciaCancelamentoHomologadoTest.php` | Identidade e justificativa da evidência. |
| `ValueObjects/IdentidadePlanoTest.php` | Igualdade lógica do Plano. |
| `ValueObjects/IdentidadeUnidadeOrcamentariaTest.php` | Validação da identidade da UO. |
| `ValueObjects/LotacaoTest.php` | Validação de Lotação. |
| `ValueObjects/RepresentanteTest.php` | Validação de Representante. |
| `ValueObjects/UasgTest.php` | Validação e igualdade de UASG. |

### Application — contratos e dados

| Arquivo | Demonstração principal |
|---|---|
| `Commands/ComandosEscritaPncpTest.php` | Invariantes de todos os comandos externos. |
| `Contracts/FinalizadorProcessamentoUnidadeTest.php` | Consumo do contrato de finalização. |
| `Ports/ContextoAuditoriaEstadoOperacionalPortsTest.php` | Contratos de contexto, estado e auditoria. |
| `Ports/EvidenciaCancelamentoHomologadoPortTest.php` | Contrato específico da evidência. |
| `Ports/IntegracaoPncpEEstrategiaPortsTest.php` | Contratos PNCP e estratégia. |
| `Ports/UnidadeDeTrabalhoPortTest.php` | Contrato de begin, commit e rollback. |
| `Results/PublicarPcaResponseTest.php` | Quatro estados e invariantes globais. |
| `Results/ResultadoAutenticacaoPncpTest.php` | Desfechos de autenticação. |
| `Results/ResultadoBuscaCandidatosPcaTest.php` | Busca de candidatos e conflitos. |
| `Results/ResultadoConsultaEvidenciaCancelamentoTest.php` | Desfechos da evidência. |
| `Results/ResultadoEstrategiaPublicacaoTest.php` | Consulta e adoção da estratégia. |
| `Results/ResultadoObservacaoPcaTest.php` | Desfechos da observação externa. |
| `Results/ResultadoPreparacaoPublicacaoPcaTest.php` | Invariantes da preparação global. |
| `Results/ResultadoProcessamentoUnidadeTest.php` | Contagens e decisões do resultado unitário. |
| `Results/ResultadoVarreduraUnidadesTest.php` | Contagem de visitas e interrupções. |
| `Results/ResultadosContextoEEstadoOperacionalTest.php` | Resultados de contexto e estado. |
| `Results/ResultadosEscritaPncpTest.php` | Escrita aceita, rejeitada, incerta e sistêmica. |
| `Results/ResultadosUnidadeTrabalhoTest.php` | Resultados transacionais. |
| `ValueObjects/ChaveUnidadeTrabalhoTest.php` | Chaves anuais e unitárias. |
| `ValueObjects/ContextoFinalizacaoProcessamentoUnidadeTest.php` | Compatibilidade entre decisão e escritas. |
| `ValueObjects/ContextoUsuarioTest.php` | Contexto autenticado. |
| `ValueObjects/EstrategiaPublicacaoTest.php` | Modos e UASG consolidadora. |
| `ValueObjects/FalhaSeguraTest.php` | Código e mensagem seguros. |
| `ValueObjects/IntencaoRegistroHistoricoTest.php` | Conteúdo auditável. |
| `ValueObjects/UnidadeExternaPublicacaoTest.php` | Unidade individual e consolidada. |

### Application — fluxo

| Arquivo | Demonstração principal |
|---|---|
| `UseCases/PublicarPca/PublicarPcaRequestTest.php` | Entrada mínima e imutável. |
| `UseCases/PublicarPca/PublicarPcaUseCaseTest.php` | Agregação e estados globais. |
| `UseCases/PublicarPca/PreparadorExecucaoPublicacaoPcaTest.php` | Contexto, estratégia, transação anual e autenticação. |
| `UseCases/PublicarPca/CoordenadorVarreduraUnidadesTest.php` | Continuidade individual e interrupção sistêmica. |
| `UseCases/PublicarPca/PreparadorUnidadeSobExclusividadeTest.php` | Lock, releitura, reconstrução e estado local. |
| `UseCases/PublicarPca/DecisorUnidadeSobExclusividadeTest.php` | Observação e chamada da política. |
| `UseCases/PublicarPca/DespachadorComandosUnidadeTest.php` | Escritas, limite de 1000, ordem e rollback. |
| `UseCases/PublicarPca/FinalizadorProcessamentoUnidadeInternoTest.php` | Observação final, reconciliação, persistência e auditoria. |
| `UseCases/PublicarPca/ProcessadorUnidadeInternoTest.php` | Encadeamento concreto no caminho sem escrita. |
| `UseCases/PublicarPca/FluxoConcretoApplicationTest.php` | Grafo completo para publicação, retificação, exclusão e consolidação. |

## 4. Configuração executável

| Arquivo | Função |
|---|---|
| `composer.json` | Dependências, PSR-4 e comandos ativos. |
| `composer.lock` | Versões resolvidas das dependências. |
| `phpunit.xml` | Bootstrap e descoberta da suíte única atual. |
| `README.md` | Entrada cognitiva e comandos de preparação. |

Para entender o caminho entre esses arquivos, volte ao
[guia de leitura](guia_de_leitura_do_codigo.md).
