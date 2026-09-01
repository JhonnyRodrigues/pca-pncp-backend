# Catálogo navegável de arquivos ativos

**Classificação documental:** Apoio  
**Status:** Vigente  
**Autoridade/Escopo:** índice do código ativo; não define comportamento

Este catálogo cobre código de produção, testes, configuração e a referência ao
estado operacional. `vendor/`, documentação histórica e investigações não
fazem parte do fluxo executável.

## 1. Domain

### Entities, Facts e Policy

| Arquivo | Função |
| --- | --- |
| `Entities/Demanda.php` | Representa uma necessidade homologada com identidade, situação e dados exatos. |
| `Entities/Plano.php` | Aggregate Root da fotografia homologada de uma UO e exercício. |
| `Entities/UnidadeOrcamentaria.php` | Entidade organizacional que relaciona Lotação e UASG. |
| `Facts/HistoricoProcedimento.php` | Fato imutável de procedimento concluído, preservado no modelo. |
| `Policies/PoliticaProcessamentoPlano.php` | Compara fotografia desejada, estado local e PCA observado para decidir a ação. |
| `Policies/ResultadoDecisaoProcessamentoPlano.php` | Resultado tipado da política, com inclusões e exclusões. |
| `Ports/PlanoRepository.php` | Contrato do Aggregate para descoberta, reconstrução e confirmação. |

### Value Objects

| Arquivo | Função |
| --- | --- |
| `ValueObjects/AnoExecucao.php` | Valida o exercício corrente ou seguinte. |
| `ValueObjects/Cnpj.php` | Normaliza e valida CNPJ alfanumérico. |
| `ValueObjects/DecimalExato.php` | Preserva uma string decimal válida sem `float`. |
| `ValueObjects/EstadoOperacionalPublicacao.php` | Representa não publicado, publicado ou excluído e seu sequencial. |
| `ValueObjects/IdentidadePlano.php` | Identidade lógica formada por UO e exercício. |
| `ValueObjects/IdentidadeUnidadeOrcamentaria.php` | Identificador estável da UO. |
| `ValueObjects/Lotacao.php` | Identifica e descreve uma lotação administrativa. |
| `ValueObjects/PcaObservado.php` | Fotografia abstrata do PCA encontrado no PNCP. |
| `ValueObjects/Representante.php` | Identifica a pessoa responsável pela execução. |
| `ValueObjects/SequencialPca.php` | Encapsula o sequencial externo do PCA. |
| `ValueObjects/Uasg.php` | Identifica a unidade externa de publicação. |

### Enums e Exceptions

| Arquivo | Função |
| --- | --- |
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
| --- | --- |
| `UseCases/PublicarPca/PublicarPcaRequest.php` | Entrada tipada com CNPJ e exercício já válidos. |
| `UseCases/PublicarPca/PublicarPcaUseCase.php` | Implementa `PublicadorPca`, orquestra o fluxo global e produz a Response final. |
| `UseCases/PublicarPca/PreparadorExecucaoPublicacaoPca.php` | Resolve contexto, estratégia, unidades e sessão PNCP. |
| `UseCases/PublicarPca/CoordenadorVarreduraUnidades.php` | Visita unidades, preserva resultados e interrompe em falha sistêmica. |
| `UseCases/PublicarPca/ProcessadorUnidadeInterno.php` | Encadeia preparação, decisão e despacho de uma unidade. |
| `UseCases/PublicarPca/PreparadorUnidadeSobExclusividade.php` | Abre lock/transação e reconstrói a fotografia vigente. |
| `UseCases/PublicarPca/DecisorUnidadeSobExclusividade.php` | Observa o PNCP e consulta a política do Domain. |
| `UseCases/PublicarPca/DespachadorComandosUnidade.php` | Liga executor e finalizador, protegendo exceções e rollback. |
| `UseCases/PublicarPca/ExecutorComandosUnidade.php` | Executa os comandos externos correspondentes à decisão. |
| `UseCases/PublicarPca/FinalizadorProcessamentoUnidadeInterno.php` | Observa, reconcilia, persiste, audita, registra operações pelo logger PSR-3 e encerra a unidade. |
| `UseCases/PublicarPca/DesfazedorUnidadeProcessamento.php` | Centraliza rollback e prevalência de sua falha. |

### Commands e Contracts

| Arquivo | Função |
| --- | --- |
| `Commands/ComandoPublicarPca.php` | Ordem de criação do PCA com a fotografia completa. |
| `Commands/ComandoInserirItensPca.php` | Ordem de inclusão de Demandas no PCA ativo. |
| `Commands/ComandoExcluirItensPca.php` | Ordem de exclusão de itens com justificativa. |
| `Commands/ComandoExcluirPca.php` | Ordem de exclusão integral do PCA. |
| `Contracts/SessaoPncp.php` | Tipo opaco da sessão autenticada. |
| `Contracts/PublicadorPca.php` | Contrato executável usado pela Presentation para acionar o UseCase já montado. |
| `Contracts/ProcessadorUnidade.php` | Contrato consumido pelo coordenador global. |
| `Contracts/FinalizadorProcessamentoUnidade.php` | Contrato entre despacho e finalização da unidade. |

### Ports

| Arquivo | Função |
| --- | --- |
| `Ports/AutenticadorPncpPort.php` | Autentica uma vez e devolve sessão opaca. |
| `Ports/ContextoUsuarioPort.php` | Resolve Representante e Lotação conjuntamente. |
| `Ports/EscritorPncpPort.php` | Publica e executa inclusões ou exclusões no PNCP. |
| `Ports/EstadoOperacionalPublicacaoPort.php` | Consulta e persiste a projeção operacional. |
| `Ports/EstrategiaPublicacaoPort.php` | Consulta e adota a estratégia anual. |
| `Ports/LeitorPncpPort.php` | Observa PCA conhecido e localiza candidatos. |
| `Ports/RegistroAuditoriaPort.php` | Persiste uma intenção de auditoria confirmada. |
| `Ports/UnidadeDeTrabalhoPort.php` | Inicia, confirma ou desfaz unidade transacional. |

### Value Objects de orquestração

| Arquivo | Função |
| --- | --- |
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
| --- | --- |
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
| `Results/ResultadoPersistenciaLocal.php` | Resultado de estado local ou auditoria. |

### Enums da Application

| Arquivo | Função |
| --- | --- |
| `Enums/AcaoExecucaoPublicacao.php` | Ação técnica global usada pela fachada ao registrar uma falha inesperada segura. |
| `Enums/AcaoProcessamentoUnidade.php` | Vocabulário operacional estável das ações executadas durante a finalização de uma unidade. |
| `Enums/EscopoUnidadeTrabalho.php` | Distingue estratégia anual e unidade externa. |
| `Enums/ModoPublicacao.php` | Distingue individual e consolidado. |
| `Enums/SituacaoAutenticacaoPncp.php` | Estados da autenticação. |
| `Enums/SituacaoBuscaCandidatosPca.php` | Estados da busca de candidatos. |
| `Enums/SituacaoConsultaEstadoOperacional.php` | Estados da consulta local. |
| `Enums/SituacaoConsultaEstrategia.php` | Estados da estratégia anual. |
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

## 3. Infrastructure

### Oracle — conexão ADOdb emprestada

| Arquivo | Função |
| --- | --- |
| `Oracle/Adodb/ConexaoAdodbEmprestada.php` | Encapsula a sessão ADOdb fornecida pelo host, prepara binds e controla seu estado transacional sem fechar a conexão. |
| `Oracle/Adodb/FalhaOracleException.php` | Transporta somente a mensagem segura e o eventual código `ORA-*`. |

### Oracle — unidade de trabalho e locks

| Arquivo | Função |
| --- | --- |
| `Oracle/Transacao/MapeamentoControlesConcorrenciaOracle.php` | Valida os identificadores físicos e o alvo anual dos controles de concorrência. |
| `Oracle/Transacao/OracleUnidadeDeTrabalhoAdapter.php` | Implementa transação local, provisionamento DML idempotente e lock `FOR UPDATE NOWAIT`. |

### Oracle — estratégia anual

| Arquivo | Função |
| --- | --- |
| `Oracle/Estrategia/MapeamentoConfiguracaoEstrategiaOracle.php` | Declara `CHAVE`, `VALOR`, `DESCRICAO`, chaves e literais da configuração corrente. |
| `Oracle/Estrategia/MapeamentoPlanosEstrategiaOracle.php` | Declara a projeção física da estratégia adotada nas linhas de Plano. |
| `Oracle/Estrategia/EstrategiaOracleInvalidaException.php` | Sinaliza internamente dados físicos ausentes, parciais ou incoerentes. |
| `Oracle/Estrategia/OracleEstrategiaPublicacaoAdapter.php` | Consulta a configuração, resolve UASGs individuais no cadastro de UOs e adota a estratégia anual na transação compartilhada. |

### Oracle — Planos

| Arquivo | Função |
| --- | --- |
| `Oracle/Plano/MapeamentoPlanosOracle.php` | Declara tabela e colunas físicas necessárias à versão e confirmação dos Planos. |
| `Oracle/Plano/MapeamentoDemandasOracle.php` | Declara a relação de origem e os campos físicos necessários à reconstrução das Demandas. |
| `Oracle/Plano/MapeamentoUnidadesOrcamentariasOracle.php` | Declara identidade, UASG, vínculo com Plano e condição de cadastro ativo da UO. |
| `Oracle/Plano/MapeamentoLotacoesOracle.php` | Declara a tabela e as colunas de Lotação usadas para compor a UO. |
| `Oracle/Plano/MapeadorDadosPlanoOracle.php` | Traduz aliases Oracle estritos para tipos de Unidade Orçamentária, Demanda e Plano. |
| `Oracle/Plano/DadosPlanoOracleInvalidosException.php` | Sinaliza internamente dados físicos ausentes, duplicados ou incoerentes. |
| `Oracle/Plano/OraclePlanoRepository.php` | Descobre identidades, reconstrói a fotografia vigente e confirma a versão pendente na transação compartilhada. |

### Oracle — estado operacional

| Arquivo | Função |
| --- | --- |
| `Oracle/EstadoOperacional/MapeamentoEstadoOperacionalOracle.php` | Declara tabela, colunas e literais da projeção operacional em Planos. |
| `Oracle/EstadoOperacional/EstadoOperacionalOracleInvalidoException.php` | Sinaliza internamente projeções físicas ausentes, parciais ou divergentes. |
| `Oracle/EstadoOperacional/OracleEstadoOperacionalPublicacaoAdapter.php` | Consulta, propaga e relê o estado operacional na transação compartilhada. |

### Oracle — contexto do usuário

| Arquivo | Função |
| --- | --- |
| `Oracle/Contexto/MapeamentoRepresentantesOracle.php` | Declara o cadastro físico usado para resolver o Representante ativo pelo login. |
| `Oracle/Contexto/MapeamentoLotacoesUsuarioOracle.php` | Declara vínculos e cadastro usados para resolver a Lotação vigente e ativa. |
| `Oracle/Contexto/DadosContextoUsuarioOracleInvalidosException.php` | Sinaliza internamente dados físicos de identidade ou Lotação inválidos. |
| `Oracle/Contexto/OracleContextoUsuarioAdapter.php` | Resolve um único Representante e uma única Lotação pelo login confiável da execução. |

### Oracle — auditoria

| Arquivo | Função |
| --- | --- |
| `Oracle/Auditoria/MapeamentoHistoricosOracle.php` | Declara tabela e colunas físicas dos fatos em `PAC_HISTORICOS`. |
| `Oracle/Auditoria/OracleRegistroAuditoriaAdapter.php` | Insere fatos append-only com instante Oracle na transação compartilhada. |

### PNCP — transporte HTTP

| Arquivo | Função |
| --- | --- |
| `Pncp/Http/AmbientePncp.php` | Restringe o destino aos ambientes oficiais de Homologação e Produção. |
| `Pncp/Http/ConfiguracaoTransportePncp.php` | Valida limites, timeouts e o caminho opcional do CA bundle. |
| `Pncp/Http/RequisicaoHttpPncp.php` | Representa método, caminho relativo, headers, corpo e timeout de uma requisição interna. |
| `Pncp/Http/RespostaHttpPncp.php` | Mantém status, headers e corpo da resposta separados e validados. |
| `Pncp/Http/TransporteHttpPncp.php` | Define a fronteira interna usada pelos adapters PNCP para enviar uma requisição. |
| `Pncp/Http/TransporteCurlPncp.php` | Aplica HTTPS, TLS, hostname, limites e timeouts e executa uma única chamada pelo executor cURL. |
| `Pncp/Http/ExecutorCurlPncp.php` | Isola a execução cURL para permitir testes determinísticos sem rede. |
| `Pncp/Http/ExecutorCurlNativoPncp.php` | Executa a chamada com a extensão cURL nativa e captura seu resultado técnico. |
| `Pncp/Http/ResultadoExecucaoCurlPncp.php` | Transporta sucesso técnico, errno, status HTTP e resultado da verificação TLS. |
| `Pncp/Http/FalhaTransportePncpException.php` | Representa falhas técnicas do transporte com códigos e mensagens sanitizados e preserva internamente o errno cURL seguro. |

### PNCP — leitura, paginação e retry

| Arquivo | Função |
| --- | --- |
| `Pncp/Leitura/ConfiguracaoLeituraPncp.php` | Exige tamanho de página, profundidade JSON, limite defensivo, espera padrão e teto de `Retry-After`. |
| `Pncp/Leitura/LeitorPncpAdapter.php` | Implementa observação e busca completas, valida coerência, comprova ausência por duas leituras e classifica conflitos e respostas inconclusivas. |
| `Pncp/Leitura/TemporizadorPncp.php` | Define relógio UTC e espera injetáveis para retry determinístico. |
| `Pncp/Leitura/TemporizadorSistemaPncp.php` | Implementa relógio e espera com recursos internos do sistema. |

### PNCP — serialização exata

| Arquivo | Função |
| --- | --- |
| `Pncp/Serializacao/FalhaSerializacaoPncp.php` | Expõe uma falha fixa e segura sem incluir valores ou payloads. |
| `Pncp/Serializacao/NumeroJsonExato.php` | Preserva um lexema numérico JSON previamente validado para emissão sem aspas. |
| `Pncp/Serializacao/CodificadorJsonExato.php` | Codifica recursivamente tipos permitidos e rejeita `float`, recursos, objetos desconhecidos e UTF-8 inválido. |
| `Pncp/Serializacao/ConversorDecimalPncp.php` | Converte e divide decimais textualmente com escala quatro, `HALF_UP` e limites de `DECIMAL(17,4)`. |
| `Pncp/Serializacao/SerializadorPayloadPncp.php` | Projeta os quatro Commands de escrita nos corpos JSON exatos do PNCP. |

### PNCP — escritas e resultados incertos

| Arquivo | Função |
| --- | --- |
| `Pncp/Escrita/EscritorPncpAdapter.php` | Executa publicação, inclusão e exclusões em chamada única, classifica o resultado e registra eventos seguros. |

### PNCP — autenticação e observabilidade

| Arquivo | Função |
| --- | --- |
| `Pncp/Autenticacao/AutenticadorPncpAdapter.php` | Implementa autenticação única, extrai o Bearer da resposta e traduz falhas para o resultado da Application. |
| `Pncp/Autenticacao/SessaoPncpAutenticada.php` | Mantém o token opaco em uma implementação concreta do contrato de sessão. |
| `Pncp/Observabilidade/AcaoTecnicaPncp.php` | Cataloga autenticação, leituras e as quatro escritas sem ampliar o enum operacional da Application. |

### Observabilidade em memória

| Arquivo | Função |
| --- | --- |
| `Observabilidade/NivelEventoTecnico.php` | Normaliza os níveis PSR-3 para o vocabulário técnico exportável. |
| `Observabilidade/RelogioEventosTecnicos.php` | Contrato mínimo de relógio para timestamp e duração da execução. |
| `Observabilidade/RelogioSistemaEventosTecnicos.php` | Implementação do relógio baseada no tempo do sistema. |
| `Observabilidade/EventoTecnico.php` | Evento técnico imutável com ação normalizada e envelope estruturado e sanitizado. |
| `Observabilidade/LoteEventosTecnicos.php` | Cópia exportável dos envelopes com duração somente no último metadata. |
| `Observabilidade/AcumuladorEventosTecnicos.php` | Logger PSR-3 que valida ações tipadas, sanitiza e acumula eventos ordenados sem I/O. |

## 4. Fronteira Scriptcase

| Arquivo | Função |
| --- | --- |
| `PublicadorPcaScriptcase.php` | Única API conhecida pela Blank; valida o request ID, seleciona e carrega o perfil pelo registro externo, materializa o CNPJ, solicita o grafo e devolve ViewModel seguro. |

## 5. Presentation

| Arquivo | Função |
| --- | --- |
| `Inputs/EntradaPublicarPca.php` | Entrada imutável com CNPJ textual e ano inteiro, sem materializar o Domain. |
| `Controllers/PublicarPcaController.php` | Materializa CNPJ e Ano de Execução, sinaliza internamente entradas inválidas, delega a `PublicadorPca` e entrega a Response ao Presenter. |
| `Exceptions/EntradaPublicarPcaInvalidaException.php` | Transporta uma falha de entrada segura exclusivamente entre Controller e fachada, preservando internamente a causa original para diagnóstico. |
| `Facades/FachadaPublicarPca.php` | Fronteira interna da Presentation que converte entradas inválidas e falhas inesperadas em ViewModels seguros e registra a captura inesperada pelo logger compartilhado. |
| `Presenters/PublicarPcaPresenter.php` | Projeta a Response global e seus resultados unitários em dados escalares seguros. |
| `ViewModels/PublicarPcaViewModel.php` | Estrutura imutável do resultado global apresentado ao consumidor. |
| `ViewModels/ResultadoUnidadeViewModel.php` | Estrutura imutável do resultado apresentado por unidade externa. |

## 6. Composition

| Arquivo | Função |
| --- | --- |
| `MontadorNucleoPublicacaoPca.php` | Recebe as nove Ports e o logger e monta explicitamente o grafo interno de `PublicarPcaUseCase`, sem Presentation ou seleção de adapters concretos. |
| `CompositionRootPublicacaoPca.php` | Materializa o perfil carregado, cria os adapters concretos e compartilha conexão, transporte, logger e correlação em um grafo novo por chamada. |
| `PerfilPublicacaoPca.php` | Trata o array externo como não confiável e materializa ambiente, configurações e os onze mapeamentos Oracle sem defaults implícitos. |

## 7. Testes

### Domain

| Arquivo | Demonstração principal |
| --- | --- |
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
| `ValueObjects/IdentidadePlanoTest.php` | Igualdade lógica do Plano. |
| `ValueObjects/IdentidadeUnidadeOrcamentariaTest.php` | Validação da identidade da UO. |
| `ValueObjects/LotacaoTest.php` | Validação de Lotação. |
| `ValueObjects/RepresentanteTest.php` | Validação de Representante. |
| `ValueObjects/UasgTest.php` | Validação e igualdade de UASG. |

### Application — contratos e dados

| Arquivo | Demonstração principal |
| --- | --- |
| `Commands/ComandosEscritaPncpTest.php` | Invariantes de todos os comandos externos. |
| `Contracts/FinalizadorProcessamentoUnidadeTest.php` | Consumo do contrato de finalização. |
| `Ports/ContextoAuditoriaEstadoOperacionalPortsTest.php` | Contratos de contexto, estado e auditoria. |
| `Ports/IntegracaoPncpEEstrategiaPortsTest.php` | Contratos PNCP e estratégia. |
| `Ports/UnidadeDeTrabalhoPortTest.php` | Contrato de begin, commit e rollback. |
| `Results/PublicarPcaResponseTest.php` | Quatro estados e invariantes globais. |
| `Results/ResultadoAutenticacaoPncpTest.php` | Desfechos de autenticação. |
| `Results/ResultadoBuscaCandidatosPcaTest.php` | Busca de candidatos e conflitos. |
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
| --- | --- |
| `UseCases/PublicarPca/PublicarPcaRequestTest.php` | Entrada mínima e imutável. |
| `UseCases/PublicarPca/PublicarPcaUseCaseTest.php` | Agregação e estados globais. |
| `UseCases/PublicarPca/PreparadorExecucaoPublicacaoPcaTest.php` | Contexto, estratégia, transação anual e autenticação. |
| `UseCases/PublicarPca/CoordenadorVarreduraUnidadesTest.php` | Continuidade individual e interrupção sistêmica. |
| `UseCases/PublicarPca/PreparadorUnidadeSobExclusividadeTest.php` | Lock, releitura, reconstrução e estado local. |
| `UseCases/PublicarPca/DecisorUnidadeSobExclusividadeTest.php` | Observação e chamada da política. |
| `UseCases/PublicarPca/DespachadorComandosUnidadeTest.php` | Escritas, limite de 1000, ordem e rollback. |
| `UseCases/PublicarPca/FinalizadorProcessamentoUnidadeInternoTest.php` | Observação final, reconciliação, persistência e auditoria. |
| `UseCases/PublicarPca/ProcessadorUnidadeInternoTest.php` | Encadeamento concreto no caminho sem escrita. |
| `UseCases/PublicarPca/FluxoConcretoApplicationTest.php` | Executa pelo montador o grafo completo para publicação, retificação, exclusão e consolidação. |

### Presentation

| Arquivo | Demonstração principal |
| --- | --- |
| `Presentation/PublicacaoPcaPresentationTest.php` | Fluxo transversal da entrada aos ViewModels, saída renderizável, preservação de resultados e sanitização de falhas inesperadas. |
| `Presentation/Inputs/EntradaPublicarPcaTest.php` | Preservação dos primitivos tipados e imutabilidade da entrada. |
| `Presentation/Controllers/PublicarPcaControllerTest.php` | Classificação localizada da entrada, materialização dos Value Objects, delegação, apresentação e distinção de falhas posteriores. |
| `Presentation/Facades/FachadaPublicarPcaTest.php` | Caminhos válidos, solicitações inválidas, fronteira de `Throwable`, ausência de vazamento e preservação de Responses seguras. |
| `Presentation/Presenters/PublicarPcaPresenterTest.php` | Estados globais e unitários, modos, decisões, campos opcionais, ordem, contagens, correlação e falhas seguras. |

### Composition

| Arquivo | Demonstração principal |
| --- | --- |
| `Composition/MontadorNucleoPublicacaoPcaTest.php` | Contrato de montagem do UseCase a partir das nove Ports vigentes e do logger PSR-3. |
| `Composition/CompositionRootPublicacaoPcaTest.php` | Validação integral do perfil, grafo novo, recursos compartilhados, ausência de I/O na montagem e preservação da conexão emprestada. |

### Integration — Composition

| Arquivo | Demonstração principal |
| --- | --- |
| `Integration/Composition/MontadorNucleoPublicacaoPcaIntegrationTest.php` | Exercita os nove adapters concretos pelo montador em convergência, retificação e falha final reconciliável, sem recursos externos reais. |
| `Integration/Composition/Support/CenarioIntegracaoNucleo.php` | Constrói as configurações fictícias, os adapters concretos e os roteiros Oracle e PNCP dos cenários controlados. |
| `Integration/Composition/Support/AdodbControlado.php` | Responde consultas por fila estrita e registra transações, consultas, escritas e tentativas de fechamento sem acessar Oracle. |
| `Integration/Composition/Support/ExecutorCurlRoteirizado.php` | Exige método e URL, alimenta os callbacks do transporte concreto e impede qualquer chamada de rede. |
| `Integration/Composition/Support/RegistroCronologico.php` | Reúne eventos Oracle e PNCP sanitizados na ordem em que foram observados. |
| `Integration/Composition/Support/TemporizadorControlado.php` | Registra a espera de retry solicitada pelo leitor sem suspender a execução do teste. |
| `Integration/PublicadorPcaScriptcaseIntegrationTest.php` | Percorre o entrypoint, perfil, Composition Root, Presentation e Application até uma saída local controlada anterior ao PNCP, preservando duas correlações externas sem rede real. |
| `PublicadorPcaScriptcaseTest.php` | Contrato público mínimo, request ID, localizador externo, seleção literal, perfil e CNPJ em comportamento fail-closed. |

### Infrastructure — Oracle

| Arquivo | Demonstração principal |
| --- | --- |
| `Infrastructure/Oracle/Adodb/ConexaoAdodbEmprestadaTest.php` | Binds, leituras, comandos, transação, falhas seguras e ausência de fechamento. |
| `Infrastructure/Oracle/Transacao/MapeamentoControlesConcorrenciaOracleTest.php` | Validação allowlisted do plano físico dos controles de concorrência. |
| `Infrastructure/Oracle/Transacao/OracleUnidadeDeTrabalhoAdapterTest.php` | Provisionamento, chaves, contenção, commit, rollback e ausência de DDL. |
| `Infrastructure/Oracle/Estrategia/MapeamentosFisicosEstrategiaOracleTest.php` | Obrigatoriedade e allowlist dos mapeamentos de configuração e estratégia adotada nos Planos. |
| `Infrastructure/Oracle/Estrategia/OracleEstrategiaPublicacaoAdapterTest.php` | Configuração corrente, UASGs individuais, estratégia adotada, binds, adoção e sessão compartilhada. |
| `Infrastructure/Oracle/Plano/MapeamentosPlanoOracleTest.php` | Obrigatoriedade e allowlist dos mapeamentos físicos de Planos, Demandas e UOs. |
| `Infrastructure/Oracle/Plano/MapeadorDadosPlanoOracleTest.php` | Tradução estrita de aliases, datas, situações e decimais para o Domain. |
| `Infrastructure/Oracle/Plano/OraclePlanoRepositoryTest.php` | Descoberta, reconstrução entre versões, Plano vazio e confirmação orientada à intenção. |
| `Infrastructure/Oracle/EstadoOperacional/MapeamentoEstadoOperacionalOracleTest.php` | Obrigatoriedade e allowlist da projeção operacional em Planos. |
| `Infrastructure/Oracle/EstadoOperacional/OracleEstadoOperacionalPublicacaoAdapterTest.php` | Escopos, versões, conflitos, propagação e releitura na mesma transação. |
| `Infrastructure/Oracle/Contexto/MapeamentosContextoUsuarioOracleTest.php` | Obrigatoriedade e allowlist dos cadastros de Representantes e Lotações. |
| `Infrastructure/Oracle/Contexto/OracleContextoUsuarioAdapterTest.php` | Login, cardinalidade, vigência, dados inválidos e falhas sistêmicas. |
| `Infrastructure/Oracle/Auditoria/MapeamentoHistoricosOracleTest.php` | Obrigatoriedade e allowlist do histórico de procedimentos. |
| `Infrastructure/Oracle/Auditoria/OracleRegistroAuditoriaAdapterTest.php` | Append-only, procedimentos, autoria, transação e sessão compartilhada. |

### Infrastructure — PNCP

| Arquivo | Demonstração principal |
| --- | --- |
| `Infrastructure/Pncp/Http/ContratosHttpPncpTest.php` | Ambientes fechados, configuração, requisição, resposta e falhas sanitizadas. |
| `Infrastructure/Pncp/Http/TransporteCurlPncpTest.php` | TLS, hostname, limites, callbacks, uma execução por chamada e ausência de rede nos testes. |
| `Infrastructure/Pncp/Autenticacao/AutenticadorPncpAdapterTest.php` | Autenticação única, sessão opaca, falhas sistêmicas e ausência de segredos nos eventos. |
| `Infrastructure/Pncp/Serializacao/NumeroJsonExatoTest.php` | Gramática e preservação de lexemas numéricos JSON exatos. |
| `Infrastructure/Pncp/Serializacao/CodificadorJsonExatoTest.php` | Emissão determinística, escapes, Unicode e rejeição segura de tipos proibidos. |
| `Infrastructure/Pncp/Serializacao/ConversorDecimalPncpTest.php` | Escala, `HALF_UP`, divisão textual, limites, negativos e overflow. |
| `Infrastructure/Pncp/Serializacao/SerializadorPayloadPncpTest.php` | Projeção integral de itens, ordenação, datas, omissões e limites textuais. |
| `Infrastructure/Pncp/Serializacao/CorposPayloadPncpTest.php` | Corpos integrais dos quatro Commands e ownership do limite operacional. |
| `Infrastructure/Pncp/Leitura/LeitorPncpAdapterTest.php` | Sessão opaca, caminhos, respostas de uma página, identidade e sanitização básica. |
| `Infrastructure/Pncp/Leitura/PaginacaoItensPncpTest.php` | Paginação completa de itens, coerência, duplicidades, limites e ausência de fotografia parcial. |
| `Infrastructure/Pncp/Leitura/PaginacaoCandidatosPncpTest.php` | Listagem completa, filtro por UASG, candidato único com itens e proibição de adoção automática. |
| `Infrastructure/Pncp/Leitura/AusenciaDefinitivaPncpTest.php` | Prova em duas consultas, conflitos de vínculo e falhas que impedem concluir ausência. |
| `Infrastructure/Pncp/Leitura/RetryLeituraPncpTest.php` | Duas tentativas máximas, `Retry-After`, tempo determinístico, classificação e eventos sanitizados. |
| `Infrastructure/Pncp/Escrita/PublicacaoPncpTest.php` | Requisição de publicação e extração estrita do sequencial pelo `Location`. |
| `Infrastructure/Pncp/Escrita/EscritaItensPncpTest.php` | Inclusão e exclusão de itens em coleção integral, sem particionamento ou retry. |
| `Infrastructure/Pncp/Escrita/ExclusaoPcaPncpTest.php` | Exclusão integral, justificativa preservada e status aceito exato. |
| `Infrastructure/Pncp/Escrita/MatrizFalhasEscritaPncpTest.php` | Matriz das quatro escritas, falhas antes e depois do despacho e envelope sanitizado. |

### Infrastructure — Observabilidade

| Arquivo | Demonstração principal |
| --- | --- |
| `Infrastructure/Observabilidade/AcumuladorEventosTecnicosTest.php` | Envelope, catálogos de ações, sanitização, ordem, imutabilidade e lote com duração. |

### Contratos opt-in — Sprint 5.1

| Arquivo | Demonstração principal |
| --- | --- |
| `Contract/Sprint51/SafetyContractTest.php` | Fail-closed, destino exclusivo de treinamento, JSON exato, opt-in do lote de 1.001 itens, resíduos conhecidos, binds Oracle e ausência de fechamento da conexão. |
| `Contract/Sprint51/PncpTrainingContractTest.php` | Gate real opt-in do fluxo completo no PNCP de treinamento. |
| `Contract/Sprint51/Support/ExactJsonEncoder.php` | Codifica os tipos permitidos pelo probe preservando números JSON exatos e recusando `float`. |
| `Contract/Sprint51/Support/ExactJsonNumber.php` | Mantém o lexema numérico exato usado exclusivamente pelo probe contratual. |
| `Contract/Sprint51/Support/OracleScriptcaseProbe.php` | Investiga runtime, schema, consultas, transação, binds e contenção em uma conexão ADOdb emprestada. |
| `Contract/Sprint51/Support/PncpContractRunner.php` | Executa autenticação, publicação, observação, alteração, exclusão e limpeza no PNCP de treinamento. |
| `Contract/Sprint51/Support/PncpHttpClient.php` | Cliente cURL exclusivo do probe, restrito ao destino de treinamento e às opções TLS aprovadas. |
| `Contract/Sprint51/Support/PncpHttpResponse.php` | Separa status, headers e corpo e produz resumos sanitizados para o probe. |
| `Contract/Sprint51/Support/Sprint51Config.php` | Carrega a configuração externa e valida os preflights Oracle e PNCP em modo fail-closed. |
| `Contract/Sprint51/scriptcase_probe.php` | Expõe somente o entrypoint contratual Oracle da Sprint 5.1 para o Scriptcase. |
| `Contract/Sprint51/run-preflight.php` | Executa o preflight conjunto ou específico para Oracle e PNCP. |
| `Contract/Sprint51/config.example.php` | Modelo sem segredos da configuração externa da suíte. |

### Contratos opt-in — Sprint 5.9

| Arquivo | Demonstração principal |
| --- | --- |
| `Contract/Sprint59/SafetyContractTest.php` | Fail-closed, destino PNCP canônico, ausência de lote 1.001, mapeamentos Oracle e sanitização de relatórios. |
| `Contract/Sprint59/OracleAdaptersContractRunnerTest.php` | Compatibilidade da leitura escalar com aliases associativos e índices numéricos duplicados pelo ADOdb. |
| `Contract/Sprint59/OracleAdaptersFailureReportTest.php` | Diagnóstico opt-in de falhas Oracle por etapa, com mensagem genérica por padrão e causa técnica sanitizada em desenvolvimento. |
| `Contract/Sprint59/PncpAdaptersContractRunnerTest.php` | Diagnóstico seguro da publicação PNCP, status disponível, encadeamento da falha e resultado da limpeza best-effort. |
| `Contract/Sprint59/PncpAdaptersTrainingContractTest.php` | Gate real opt-in do ciclo completo no PNCP de treinamento. |
| `Contract/Sprint59/Support/PncpAdaptersContractRunner.php` | Monta e exercita somente transporte, serializer, autenticação, leitura, escrita e eventos de produção. |
| `Contract/Sprint59/Support/PncpDiagnosticTransport.php` | Delega ao transporte PNCP real e retém temporariamente requisição, resposta ou falha para diagnóstico contratual. |
| `Contract/Sprint59/Support/OracleAdaptersContractRunner.php` | Exercita os adapters Oracle reais em uma conexão emprestada e nos quatro modos contratuais. |
| `Contract/Sprint59/Support/OracleAdaptersContractFailure.php` | Preserva a causa original e identifica a etapa segura em que o fluxo `adapters` falhou. |
| `Contract/Sprint59/Support/OracleAdaptersFailureReport.php` | Mantém o erro genérico por padrão e sanitiza a causa técnica habilitada por opt-in. |
| `Contract/Sprint59/Support/BorrowedAdodbGuard.php` | Delega ADOdb e impede que `Close()` alcance a conexão do Scriptcase. |
| `Contract/Sprint59/Support/Sprint59Config.php` | Carrega configuração externa e valida os dois gates antes de I/O. |
| `Contract/Sprint59/Support/SafeContractReport.php` | Codifica e verifica relatórios sanitizados e calcula hashes SHA-256. |
| `Contract/Sprint59/scriptcase_probe.php` | Expõe somente o entrypoint contratual de Scriptcase da Sprint 5.9. |
| `Contract/Sprint59/run-preflight.php` | Preflight conjunto ou específico para Oracle e PNCP. |
| `Contract/Sprint59/run-pncp-cleanup.php` | Remove somente o resíduo PNCP explicitamente autorizado e exige ausência definitiva sem publicar. |
| `Contract/Sprint59/config.example.php` | Modelo sem segredos da configuração externa. |

## 8. Configuração executável

| Arquivo | Função |
| --- | --- |
| `composer.json` | Dependências, PSR-4 e comandos ativos. |
| `composer.lock` | Versões resolvidas das dependências. |
| `phpunit.xml` | Bootstrap da suíte padrão, com exclusão explícita dos contratos opt-in. |
| `phpunit.contract.xml` | Suíte contratual legada da Sprint 5.1. |
| `phpunit.adapters.contract.xml` | Suíte separada dos contratos de adapters da Sprint 5.9. |
| `README.md` | Entrada cognitiva e comandos de preparação. |

## 9. Documentação vigente

| Arquivo | Função |
| --- | --- |
| `docs/README.md` | Governa classificação, status, precedência, navegação e caminhos canônicos da documentação. |
| `docs/vigentes/autoritativos/regras_de_negocio.md` | Define as regras de negócio vigentes identificadas por `RN-*`. |
| `docs/vigentes/autoritativos/modelo_de_dominio.md` | Define conceitos, identidades, relações e invariantes do modelo de domínio. |
| `docs/vigentes/autoritativos/decisoes_de_integracao.md` | Registra as decisões de integração vigentes e seus gates. |
| `docs/vigentes/autoritativos/especificacao_de_logs_estruturados.md` | Consolida o envelope institucional, o significado dos campos e a proteção de dados dos eventos técnicos. |
| `docs/vigentes/autoritativos/fluxo_de_implementacao.md` | Descreve a colaboração vigente entre camadas, casos de uso e Ports. |
| `docs/vigentes/autoritativos/roadmap.md` | Organiza a ordem de implementação, dependências e condições de avanço. |
| `docs/vigentes/apoio/arquitetura.md` | Explica limites, responsabilidades e direção das dependências arquiteturais. |
| `docs/vigentes/apoio/glossario.md` | Mantém o índice semântico da linguagem de negócio, integração e arquitetura. |
| `docs/vigentes/apoio/contexto_funcional_do_sistema_pca.md` | Contextualiza usuários, etapas e funcionamento do sistema PCA. |
| `docs/vigentes/apoio/guia_de_leitura_do_codigo.md` | Orienta a leitura do código, do fluxo e das fronteiras transacionais. |
| `docs/vigentes/apoio/catalogo_de_arquivos.md` | Cataloga a função dos arquivos ativos de código, testes, configuração e documentação vigente. |
| `docs/diagramas/DER_PUBLICACAO_PCA.png` | Apoia visualmente a compreensão do modelo de dados da publicação do PCA. |
| `docs/diagramas/fluxograma_PCA.png` | Apoia visualmente a compreensão do fluxo funcional do PCA. |
| `docs/vigentes/operacional/estado_atual.md` | Centraliza progresso, última sprint concluída, próxima sprint na ordem, gates, pendências e restrições, sem criar regra ou decisão. |

Para entender o caminho entre esses arquivos, volte ao
[guia de leitura](guia_de_leitura_do_codigo.md).
