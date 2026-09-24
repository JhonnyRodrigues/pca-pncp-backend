# Catálogo navegável de arquivos ativos

**Classificação documental:** Apoio  
**Status:** Vigente  
**Autoridade/Escopo:** índice do código ativo; não define comportamento

Responde: “Qual é a responsabilidade deste arquivo?”.

Este catálogo cobre código de produção, testes, configuração e a referência ao
estado operacional. `vendor/`, documentação histórica e investigações não
fazem parte do fluxo executável.

Para saber como executar e classificar os scripts, consulte o
[catálogo de comandos Composer](catalogo_de_comandos_composer.md). Esta página
permanece dedicada à função dos arquivos.

Para entender primeiro a função das pastas e depois chegar a um arquivo,
consulte o [catálogo de diretórios](catalogo_de_diretorios.md).

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
| `Oracle/Adodb/ConversorCharsetAdodbOracle.php` | Converte leituras Windows-1252 para UTF-8 e binds no sentido inverso, bloqueando perda de caracteres. |
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

### Oracle — observabilidade técnica

| Arquivo | Função |
| --- | --- |
| `Oracle/Observabilidade/OracleRegistroEventoTecnicoAdapter.php` | Persiste um envelope técnico integral em `SGI.SEGURANCA_LOG`: insere um marcador curto, atualiza o CLOB por ID com `UpdateClob` e encerra uma transação técnica própria. |

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
| `Pncp/Escrita/EscritorPncpAdapter.php` | Executa publicação, inclusão e exclusões em chamada única, classifica o resultado, extrai o sequencial de `Location` estrito por ambiente e registra somente diagnósticos sanitizados. |

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
| `PublicadorPcaScriptcase.php` | Única API conhecida pela Blank; valida o request ID, seleciona e carrega o perfil externo, materializa o CNPJ e aciona um grafo novo por chamada. Preserva a operação que devolve o ViewModel e oferece uma variante que também devolve, separadamente, eventos sanitizados e eventual aviso técnico. |
| `ResultadoPublicacaoPcaScriptcase.php` | Saída imutável da variante com eventos: ViewModel existente, envelopes escalares e aviso técnico seguro. |
| `RegistradorEventosTecnicosScriptcase.php` | Fachada pública que recebe o lote já exportado e solicita seu registro técnico sem expor SQL ou detalhes internos à Blank. |
| `integracao/README.md` | Explica a fronteira com o Scriptcase, a publicação da Blank e os limites de responsabilidade e segurança. |
| `integracao/scriptcase/PCA_PUBLICAR_B/onExecute.php` | Adaptação versionada da Blank: distingue a navegação do menu do POST JSON de publicação, carrega a entrada ao backend (`pca-pncp-backend/vendor/autoload.php`), chama as fachadas públicas, renderiza o resultado seguro e oferece ajuda estática ao operador. |

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
| `CarregadorPerfilPublicacaoPca.php` | Seleciona literalmente o perfil pelo registro externo e valida integralmente seus caminhos antes do carregamento. |
| `ComposicaoOraclePublicacaoPca.php` | Única receita de produção dos seis adapters Oracle sobre uma mesma `ConexaoAdodbEmprestada`. |
| `ComposicaoExecucaoPublicacaoPca.php` | Mantém fachada, acumulador e conexão da mesma chamada; exporta eventos somente após encerramento transacional e preserva o resultado diante de falha técnica. |
| `CompositionRootPublicacaoPca.php` | Materializa o perfil carregado, usa a subcomposição Oracle única e compartilha transporte, logger, conexão e correlação em um grafo novo por chamada. |
| `ExecutorPublicacaoPcaScriptcase.php` | Executa uma vez o fluxo protegido e adapta sua saída para o ViewModel existente ou para o resultado separado com eventos. |
| `ExecutorRegistroEventosTecnicosScriptcase.php` | Carrega o charset do perfil externo, monta o adapter de CLOB sobre a conexão emprestada, preserva eventos já registrados e converte a primeira falha em aviso seguro. |
| `PerfilPublicacaoPca.php` | Trata o array externo como não confiável e materializa ambiente, charset ADOdb, configurações e os onze mapeamentos Oracle sem defaults implícitos. |

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
| `Composition/ComposicaoExecucaoPublicacaoPcaTest.php` | Exportação sanitizada e imutável, bloqueio por transação ativa e preservação do resultado diante de falha de exportação. |
| `Composition/CompositionRootPublicacaoPcaTest.php` | Validação integral do perfil, grafo novo, recursos compartilhados, montagem exportável sem I/O e preservação da conexão emprestada. |

### Integration — Composition

| Arquivo | Demonstração principal |
| --- | --- |
| `Integration/Composition/MontadorNucleoPublicacaoPcaIntegrationTest.php` | Exercita os nove adapters concretos pelo montador em convergência, retificação e falha final reconciliável, sem recursos externos reais. |
| `Integration/Composition/Support/CenarioIntegracaoNucleo.php` | Constrói as configurações fictícias, os adapters concretos e os roteiros Oracle e PNCP dos cenários controlados. |
| `Integration/Composition/Support/AdodbControlado.php` | Responde consultas e falhas Oracle por fila estrita e registra transações, consultas, escritas e tentativas de fechamento sem acessar Oracle. |
| `Integration/Composition/Support/ExecutorCurlRoteirizado.php` | Exige método e URL, alimenta os callbacks ou devolve falha cURL controlada ao transporte concreto e impede qualquer chamada de rede. |
| `Integration/Composition/Support/RegistroCronologico.php` | Reúne eventos Oracle e PNCP sanitizados na ordem em que foram observados. |
| `Integration/Composition/Support/TemporizadorControlado.php` | Registra a espera de retry solicitada pelo leitor sem suspender a execução do teste. |
| `Integration/PublicadorPcaScriptcaseIntegrationTest.php` | Percorre entrypoint, perfil, Composition Root, Presentation e Application e comprova que a variante com eventos processa a solicitação uma única vez, sem rede real. |
| `Integration/PackageReleaseToolTest.php` | Protege os aliases da release, a criação segura do `.tar.gz`, a recusa de sobrescrita e a exigência de worktree limpo. |
| `Integration/Scriptcase/PcaPublicarBlankTest.php` | Protege o contrato da Blank para entrada, fachada, logs integrais, resposta pública, submissão única, renderização segura e ajuda estática ao operador. |
| `Integration/Sprint85/FalhasIntegradasTest.php` | Comprova contenção com continuidade, reconciliação única sem repetição da escrita e interrupção sistêmica com preservação dos resultados conhecidos. |
| `PublicadorPcaScriptcaseTest.php` | Contratos públicos compatíveis, request ID, ausência de eventos fabricados, localizador externo, seleção literal, perfil e CNPJ em comportamento fail-closed. |

### Suporte compartilhado dos testes

| Arquivo | Função |
| --- | --- |
| `Support/NormalizadorSql.php` | Remove diferenças de espaçamento das SQLs capturadas para que os testes verifiquem sua estrutura sem impor apresentação em uma única linha. |

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
| `Infrastructure/Pncp/Escrita/PublicacaoPncpTest.php` | Requisição de publicação, prefixos oficiais de `Location` por ambiente, rejeições estritas e diagnóstico sanitizado. |
| `Infrastructure/Pncp/Escrita/EscritaItensPncpTest.php` | Inclusão e exclusão de itens em coleção integral, sem particionamento ou retry. |
| `Infrastructure/Pncp/Escrita/ExclusaoPcaPncpTest.php` | Exclusão integral, justificativa preservada e status aceito exato. |
| `Infrastructure/Pncp/Escrita/MatrizFalhasEscritaPncpTest.php` | Matriz das quatro escritas, falhas antes e depois do despacho e envelope sanitizado. |

### Infrastructure — Observabilidade

| Arquivo | Demonstração principal |
| --- | --- |
| `Infrastructure/Observabilidade/AcumuladorEventosTecnicosTest.php` | Envelope, catálogos de ações, sanitização, ordem, imutabilidade e lote com duração. |
| `Infrastructure/Oracle/Observabilidade/OracleRegistroEventoTecnicoAdapterTest.php` | JSON integral maior que 4.000 bytes, escapes Unicode, mapas vazios, transação por evento e rollback seguro. |
| `RegistradorEventosTecnicosScriptcaseTest.php` | Fachada pública, ordem dos eventos, isolamento das transações e interrupção no primeiro erro. |

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

### Contratos opt-in — Sprint 8.2

| Arquivo | Demonstração principal |
| --- | --- |
| `Contract/Sprint82/SafetyContractTest.php` | Assinatura do gate, modos autorizados, sanitização, ausência de segredos e separação da suíte padrão. |
| `Contract/Sprint82/Sprint82ConfigTest.php` | Configuração externa exata, servidor literal, consentimento de escrita e perfil exclusivo de homologação. |
| `Contract/Sprint82/OracleCompositionContractTest.php` | Uso da subcomposição Oracle de produção, seis adapters na mesma conexão e montagem do Root sem I/O. |
| `Contract/Sprint82/OracleAdaptersExerciseTest.php` | Sequência transacional do commit controlado, restauração por rollback e preservação da conexão emprestada. |
| `Contract/Sprint82/OracleContractRunnerTest.php` | Relatório seguro do exercício sobre a composição Oracle real. |
| `Contract/Sprint82/OracleFailureReportTest.php` | Diagnóstico opt-in sanitizado e relatório genérico por padrão. |
| `Contract/Sprint82/Support/Sprint82Config.php` | Carrega a configuração própria e o perfil final selecionado pelo registro da implantação. |
| `Contract/Sprint82/Support/OracleContractRunner.php` | Monta o Root sem I/O e executa a mesma subcomposição Oracle nos cinco modos contratuais. |
| `Contract/Sprint82/Support/OracleFailureReport.php` | Produz a falha segura da Sprint 8.2 e limita os detalhes diagnósticos aos modos permitidos. |
| `Contract/Sprint82/scriptcase_probe.php` | Expõe somente o entrypoint Oracle da Sprint 8.2 para uma Blank protegida. |
| `Contract/Sprint82/run-preflight.php` | Valida configuração, registro e perfil de homologação sem acessar Oracle ou PNCP. |
| `Contract/Sprint82/config.example.php` | Modelo sem segredos da configuração específica da massa 8.2. |

### Contratos opt-in — Sprint 8.3

| Arquivo | Demonstração principal |
| --- | --- |
| `Contract/Sprint83/Sprint83ConfigTest.php` | Configuração externa exata, massa dedicada, servidor literal e recusa de produção antes de I/O. |
| `Contract/Sprint83/PncpTrainingTransportTest.php` | Descarte deliberado de uma única resposta real de publicação depois da delegação. |
| `Contract/Sprint83/PncpTrainingContractRunnerTest.php` | Ciclos nominal e incerto em memória, autenticação única, paginação, limpeza e sanitização. |
| `Contract/Sprint83/PncpTrainingContractTest.php` | Grupos opt-in separados para os gates nominal e de escrita incerta no treinamento. |
| `Contract/Sprint83/SafetyContractTest.php` | Separação dos grupos live e proteção dos relatórios contra dados sensíveis. |
| `Contract/Sprint83/Support/Sprint83Config.php` | Carrega autorização, massa e perfil de treinamento pelos dois arquivos externos. |
| `Contract/Sprint83/Support/PncpTrainingTransport.php` | Envolve o transporte compartilhado, registra chamadas e simula a perda controlada da resposta. |
| `Contract/Sprint83/Support/PncpTrainingContractRunner.php` | Exercita a composição PNCP final, observa efeitos e executa limpeza best-effort. |
| `Contract/Sprint83/run-preflight.php` | Valida configuração, registro e perfil sem acessar Oracle ou PNCP. |
| `Contract/Sprint83/run-pncp-cleanup.php` | Remove somente o resíduo com sequencial explicitamente autorizado. |
| `Contract/Sprint83/config.example.php` | Modelo sem segredos da autorização e massa dedicada da Sprint 8.3. |

### Contratos opt-in — Sprint 8.4

| Arquivo | Demonstração principal |
| --- | --- |
| `Contract/Sprint84/Sprint84ConfigTest.php` | Valida consentimentos separados, etapa, modo, cardinalidades e hash do checkpoint. |
| `Contract/Sprint84/Sprint84GateTest.php` | Comprova consultas Oracle somente leitura, cardinalidades e snapshot canônico bloqueante. |
| `Contract/Sprint84/Sprint84RunnerTest.php` | Exercita PRECHECK, execução, pós-check, invariância e propagação operacional permitida. |
| `Contract/Sprint84/Sprint84PncpTransportGuardTest.php` | Comprova bloqueio de mutações antes do transporte e permissão de login/GET no gate somente leitura. |
| `Contract/Sprint84/OracleCheckpointQueriesTest.php` | Protege as onze consultas canônicas e os roteiros manuais contra escrita e lock. |
| `Contract/Sprint84/Support/Sprint84Runner.php` | Coordena o checkpoint Oracle, a observação PNCP, o fluxo público e as verificações posteriores. |
| `Contract/Sprint84/Support/OracleCheckpointQueries.php` | Monta as onze consultas parametrizadas a partir do perfil e da configuração autorizados. |
| `Contract/Sprint84/Support/OracleCheckpointSnapshot.php` | Normaliza resultados e calcula o SHA-256 canônico do estado Oracle. |
| `Contract/Sprint84/Support/Sprint84DiagnosticReport.php` | Protege segredos e calcula o hash do relatório detalhado da Blank. |
| `Contract/Sprint84/scriptcase_probe.php` | Expõe o entrypoint protegido dos fluxos completos da Sprint 8.4. |
| `Contract/Sprint84/run-preflight.php` | Valida localmente configuração, registro e perfil antes de qualquer I/O. |
| `Contract/Sprint84/checkpoints/*.sql` | Preserva os roteiros somente leitura usados nas conferências manuais autorizadas. |
| `Contract/Sprint84/blank_scriptcase/teste_adapters_contracts_sprint84.php` | Referência da aplicação Blank usada para executar o gate no Scriptcase. |
| `Contract/Sprint84/config.example.php` | Modelo sem segredos da configuração externa da Sprint 8.4. |

### Contratos opt-in — Sprint 8.5

| Arquivo | Demonstração principal |
| --- | --- |
| `Contract/Sprint85/Sprint85ConfigTest.php` | Consentimentos por etapa, modo consolidado, hash e preservação da massa conhecida. |
| `Contract/Sprint85/Sprint85PreparationTest.php` | Doze consultas completas somente leitura, leitura integral do CLOB e recusa antes de I/O. |
| `Contract/Sprint85/Sprint85LogGateTest.php` | Protege a configuração, as duas consultas independentes da massa e o evento sintético maior que 4.000 bytes. |
| `Contract/Sprint85/Sprint85ContainmentGateTest.php` | Comprova ordem do lock, resultado bloqueado, preservação do snapshot, acesso PNCP restrito e registro posterior dos eventos. |
| `Contract/Sprint85/Sprint85OracleLockProbeTest.php` | Distingue a contenção real por `ORA-00054` de uma âncora livre e verifica o rollback da sonda. |
| `Contract/Sprint85/Sprint85ContainmentArtifactsTest.php` | Protege as doze consultas materializadas, a Blank restrita, a configuração desarmada e o empacotamento próprio. |
| `Contract/Sprint85/Support/Sprint85Config.php` | Carrega a configuração externa exata e restringe perfil, etapa, consentimentos e massa. |
| `Contract/Sprint85/Support/Sprint85OracleQueries.php` | Reutiliza as onze consultas canônicas e acrescenta a consulta identificada dos logs técnicos. |
| `Contract/Sprint85/Support/Sprint85Precheck.php` | Executa o checkpoint somente leitura, compara o hash fixado e impede SQL mutante ou lock. |
| `Contract/Sprint85/Support/Sprint85LogGateConfig.php` | Restringe o gate de logs a PRECHECK ou LOGS no host validaSGI, fixa o charset ADOdb e exige o hash antes da escrita. |
| `Contract/Sprint85/Support/Sprint85LogGateQueries.php` | Define o PRECHECK reduzido da estrutura do log e a releitura integral do CLOB. |
| `Contract/Sprint85/Support/Sprint85SyntheticLog.php` | Produz o envelope identificado, com escapes Unicode, mapas vazios e mais de 4.000 bytes. |
| `Contract/Sprint85/Support/Sprint85LogGate.php` | Coordena PRECHECK, preparação e verificação do evento sem chamar o publicador. |
| `Contract/Sprint85/Support/Sprint85ContainmentGate.php` | Coordena checkpoint, prova do lock externo, fluxo público protegido, comparação posterior e logs técnicos. |
| `Contract/Sprint85/Support/Sprint85OracleLockProbe.php` | Testa a âncora consolidada com `FOR UPDATE NOWAIT` e sempre desfaz sua transação local. |
| `Contract/Sprint85/scriptcase_probe.php` | Expõe o PRECHECK geral preparado originalmente para os gates dependentes da massa. |
| `Contract/Sprint85/scriptcase_log_gate_probe.php` | Expõe somente os três entrypoints necessários ao artefato temporário do Microciclo 6. |
| `Contract/Sprint85/scriptcase_containment_probe.php` | Expõe os entrypoints seguros de PRECHECK, contenção e disponibilidade do Microciclo 7. |
| `Contract/Sprint85/run-preflight.php` | Valida configuração, perfil e contrato das consultas sem acessar recursos externos. |
| `Contract/Sprint85/render-manual-queries.php` | Renderiza SQL, binds, colunas e cardinalidades para conferência manual. |
| `Contract/Sprint85/config.example.php` | Modelo sem segredos da configuração externa dos quatro gates. |
| `Contract/Sprint85/log-gate-config.example.php` | Modelo sem segredos e independente da massa de PCA para o Microciclo 6 no validaSGI. |
| `Contract/Sprint85/containment-config.example.php` | Configuração desarmada e sem segredos da fotografia de homologação usada no Microciclo 7. |
| `Contract/Sprint85/smoke-config.example.php` | Configuração desarmada do PRECHECK do smoke, com as três UOs e fotografia vazia. |
| `Contract/Sprint85/checkpoints/smoke_precheck.sql` | Materializa as doze consultas somente leitura da massa excluída usada no smoke. |
| `Contract/Sprint85/checkpoints/smoke_postcheck.sql` | Confere projeção, confirmações, plano 103, demandas, auditoria, controles e logs após a chamada única do smoke. |
| `Contract/Sprint85/checkpoints/smoke_resposta_incerta_postcheck.sql` | Reconstitui por consultas somente leitura o efeito de um smoke cuja resposta não trouxe correlação, incluindo eventos recentes por aplicação e operador. |
| `Contract/Sprint85/checkpoints/logs_precheck.sql` | Dois SELECTs materializados para conferir estrutura e ausência do identificador antes do gate. |
| `Contract/Sprint85/checkpoints/logs_postcheck.sql` | Releitura manual integral do CLOB depois da chamada única da macro. |
| `Contract/Sprint85/checkpoints/logs_diagnostico_persistencia.sql` | Consultas somente leitura para investigar um pós-check vazio sem repetir a macro. |
| `Contract/Sprint85/blank_scriptcase/teste_contracts_sprint85.php` | Blank protegida que libera somente PRECHECK e LOGS, sem publicador ou acesso PNCP. |
| `Contract/Sprint85/checkpoints/contencao_precheck.sql` | Doze SELECTs concretos, com colunas e cardinalidades, para a fotografia manual anterior ao gate de contenção. |
| `Contract/Sprint85/checkpoints/contencao_reter_ancora.sql` | Comando operacional da sessão A para reter a âncora e liberá-la por rollback depois da chamada. |
| `Contract/Sprint85/blank_scriptcase/teste_contencao_sprint85.php` | Blank separada que limita o Microciclo 7 a PRECHECK, CONTENCAO e DISPONIBILIDADE. |

### Massa descartável de homologação — Sprint 8.5

| Arquivo | Função |
| --- | --- |
| `database/seeds/sprint85_contencao_hml/README.md` | Define a preparação em etapas e impede que o seed de HML seja aplicado sobre dados replicados ou em produção. |
| `database/seeds/sprint85_contencao_hml/01_diagnostico_pre_seed.sql` | Diagnóstico somente leitura do ambiente, UOs candidatas, colisões, estrutura e dependências necessárias para gerar o seed de contenção. |
| `database/seeds/sprint85_contencao_hml/02_precheck_seed.sql` | Confere ambiente, cardinalidades, projeção vazia e ausência de controles antes do DML. |
| `database/seeds/sprint85_contencao_hml/03_aplicar_seed.sql` | Prepara de forma bloqueante a estratégia consolidada dos 112 planos e os dois controles técnicos. |
| `database/seeds/sprint85_contencao_hml/04_postcheck_seed.sql` | Confirma a projeção, os controles e a preservação da massa replicada. |
| `database/seeds/sprint85_contencao_hml/05_limpar_seed.sql` | Reverte somente o estado exato criado pelo seed e recusa qualquer fotografia divergente. |
| `database/seeds/sprint85_contencao_hml/06_postcheck_limpeza.sql` | Confirma por seis SELECTs que a preparação da contenção foi removida e a massa replicada foi preservada. |
| `database/seeds/sprint85_smoke_hml/README.md` | Explica a preparação reversível da fotografia vazia com PCA excluído para o smoke. |
| `database/seeds/sprint85_smoke_hml/01_precheck_seed.sql` | Confere planos, situações originais das demandas, versões vigentes, controles, auditoria e plano 103 antes do seed. |
| `database/seeds/sprint85_smoke_hml/02_aplicar_seed.sql` | Marca as 112 demandas como canceladas e projeta os 112 planos como `EXCLUIDO/36`, somente em SGIHML. |
| `database/seeds/sprint85_smoke_hml/03_postcheck_seed.sql` | Confirma fotografia vazia, projeção excluída, controles e preservação do plano 103. |
| `database/seeds/sprint85_smoke_hml/04_limpar_seed.sql` | Restaura situações, confirmações e projeção anteriores, recusando qualquer divergência. |

### Suporte contratual compartilhado

| Arquivo | Demonstração principal |
| --- | --- |
| `Contract/Support/OracleAdaptersExercise.php` | Reúne o exercício transacional compartilhado pelas Sprints 5.9 e 8.2 sem alterar seus entrypoints. |
| `Contract/Support/OracleAdaptersSet.php` | Transporta explicitamente os seis adapters Oracle da subcomposição. |
| `Contract/Support/BorrowedAdodbGuard.php` | Registra operações ADOdb e impede `Close()` sobre a conexão emprestada. |
| `Contract/Support/OracleContractFailure.php` | Preserva internamente a causa e identifica a etapa segura da falha. |
| `Contract/Support/OracleTechnicalMessageSanitizer.php` | Remove valores sensíveis conhecidos da mensagem diagnóstica opt-in. |
| `Contract/Support/SafeContractReport.php` | Valida, serializa e calcula o hash de relatórios sanitizados. |

## 8. Configuração executável

| Arquivo | Função |
| --- | --- |
| `composer.json` | Dependências, PSR-4 e comandos ativos. |
| `composer.lock` | Versões resolvidas das dependências. |
| `tools/README.md` | Distingue ferramentas versionadas de resultados gerados, documenta o fluxo normal de release e limita a repetição de gates encerrados. |
| `tests/README.md` | Orienta a suíte padrão, contratos opt-in e cuidados antes de executar qualquer gate com recurso real. |
| `tools/package/build.php` | Constrói em `build/` o pacote isolado com código, dependências de produção e manifesto de integridade. |
| `tools/package/verify.php` | Verifica hashes, topologia, conteúdo permitido e autoload do pacote gerado. |
| `tools/package/archive.php` | Exige commit limpo, compacta o pacote validado com data de geração e commit no nome, confere sua topologia e calcula o SHA-256. |
| `tools/package/build-log-gate.php` | Monta o artefato temporário do gate de logs a partir do pacote principal já validado e de uma lista fechada de arquivos da probe. |
| `tools/package/verify-log-gate.php` | Confere o manifesto, a topologia e os entrypoints carregáveis do artefato temporário. |
| `tools/package/build-containment-gate.php` | Monta o artefato temporário do gate de contenção a partir do pacote principal validado e de uma lista fechada de suporte. |
| `tools/package/verify-containment-gate.php` | Confere manifesto, topologia, hashes e entrypoints do artefato de contenção. |
| `phpunit.xml` | Bootstrap da suíte padrão, com exclusão explícita dos contratos opt-in. |
| `phpunit.contract.xml` | Suíte contratual legada da Sprint 5.1. |
| `phpunit.adapters.contract.xml` | Suíte separada dos contratos de adapters da Sprint 5.9. |
| `phpunit.oracle82.contract.xml` | Suíte separada da integração Oracle da Sprint 8.2. |
| `phpunit.pncp83.contract.xml` | Suíte separada dos contratos locais e gates PNCP da Sprint 8.3. |
| `phpunit.sprint84.contract.xml` | Suíte separada dos contratos e do runner da Sprint 8.4. |
| `phpunit.sprint85.contract.xml` | Suíte separada dos contratos locais e futuros gates opt-in da Sprint 8.5. |
| `README.md` | Entrada cognitiva e comandos de preparação. |

### Migrações do banco de dados

| Arquivo | Função |
| --- | --- |
| `database/README.md` | Define a organização e o procedimento manual provisório das migrações Oracle. |
| `database/migrations/V202609181815__configuracao_publicacao_pca.sql` | Insere os dois dados de referência da publicação consolidada, aceita estado já conforme e recusa divergência com rollback. |
| `database/checks/V202609181815__configuracao_publicacao_pca_precheck.sql` | Classifica cada configuração como ausente, conforme ou divergente antes da migração. |
| `database/checks/V202609181815__configuracao_publicacao_pca_postcheck.sql` | Exige duas configurações conformes depois da migração. |
| `database/migrations/V202609181816__unidades_orcamentarias_pca.sql` | Insere quatro unidades orçamentárias, aceita estado já conforme e recusa conflitos de identidade, UASG ou lotação. |
| `database/checks/V202609181816__unidades_orcamentarias_pca_precheck.sql` | Classifica as quatro unidades como ausentes, conformes, divergentes ou sem lotação correspondente. |
| `database/checks/V202609181816__unidades_orcamentarias_pca_postcheck.sql` | Exige quatro unidades e lotações conformes depois da migração. |
| `database/migrations/V202609241700__procedimentos_publicacao_pca.sql` | Assegura os procedimentos 8 e 9 da auditoria de negócio, recusando colisões de identificador ou nome. |
| `database/checks/V202609241700__procedimentos_publicacao_pca_precheck.sql` | Classifica os procedimentos 8 e 9 como ausentes, conformes ou divergentes antes da migration. |
| `database/checks/V202609241700__procedimentos_publicacao_pca_postcheck.sql` | Exige os procedimentos 8 e 9 com nomes exatos depois da migration. |
| `database/migrations/V202609241701__estrategia_individual_publicacao_pca.sql` | Registra a transição da estratégia consolidada original para `INDIVIDUAL`, preservando a baseline aplicada. |
| `database/checks/V202609241701__estrategia_individual_publicacao_pca_precheck.sql` | Distingue estratégia já conforme, transição necessária, ausência e divergência. |
| `database/checks/V202609241701__estrategia_individual_publicacao_pca_postcheck.sql` | Exige a configuração final da estratégia individual com descrição atualizada. |
| `database/queries/seguranca_log_por_correlacao.sql` | Localiza por bind os eventos técnicos de uma correlação e apresenta campos operacionais sanitizados. |
| `database/seeds/README.md` | Delimita seeds descartáveis de DEV/HML e impede seu uso para configuração obrigatória, produção ou segredos. |
| `database/deployments/README.md` | Define o conteúdo seguro do registro manual provisório de implantações. |
| `database/deployments/homologacao.md` | Registra migrações aplicadas ou requisitos já satisfeitos no banco de homologação. |
| `database/deployments/producao.md` | Registra migrations aplicadas ou requisitos já satisfeitos no banco de produção. |

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
| `docs/vigentes/apoio/arquitetura.md` | Explica limites, responsabilidades, direção das dependências e fronteiras entre configuração, segredos e mapeamentos físicos. |
| `docs/vigentes/apoio/glossario.md` | Mantém o índice semântico da linguagem de negócio, integração e arquitetura. |
| `docs/vigentes/apoio/contexto_funcional_do_sistema_pca.md` | Contextualiza usuários, etapas e funcionamento do sistema PCA. |
| `docs/vigentes/apoio/guia_de_leitura_do_codigo.md` | Orienta a leitura do código, do fluxo e das fronteiras transacionais. |
| `docs/vigentes/apoio/guia_de_configuracao_de_producao.md` | Orienta o provisionamento externo do registro, perfil e CA bundle de produção, inclusive permissões e configuração persistente no Apache/PHP. |
| `docs/vigentes/apoio/guia_de_implantacao_e_rollback.md` | Orienta construção, transferência SFTP, implantação inicial, atualização e rollback da release autocontida. |
| `docs/vigentes/apoio/catalogo_de_diretorios.md` | Explica a finalidade das pastas e orienta onde localizar ou criar cada tipo de artefato. |
| `docs/vigentes/apoio/guia_de_execucao_das_probes.md` | Ensina a preparar, executar, diagnosticar e encerrar as probes e gates 5.1, 5.9, 8.2, 8.3 e 8.4. |
| `docs/vigentes/apoio/catalogo_de_comandos_composer.md` | Classifica os scripts Composer e informa finalidade, dependências, efeitos externos e autorização necessária. |
| `docs/vigentes/apoio/catalogo_de_arquivos.md` | Cataloga a função dos arquivos ativos de código, testes, configuração e documentação vigente. |
| `docs/diagramas/DER_PUBLICACAO_PCA.png` | Apoia visualmente a compreensão do modelo de dados da publicação do PCA. |
| `docs/diagramas/fluxograma_PCA.png` | Apoia visualmente a compreensão do fluxo funcional do PCA. |
| `docs/vigentes/operacional/estado_atual.md` | Centraliza progresso, última sprint concluída, próxima sprint na ordem, gates, pendências e restrições, sem criar regra ou decisão. |

Para entender o caminho entre esses arquivos, volte ao
[guia de leitura](guia_de_leitura_do_codigo.md).
