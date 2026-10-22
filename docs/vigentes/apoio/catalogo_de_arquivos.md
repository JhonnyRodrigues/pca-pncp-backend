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

A coluna **Natureza** antecipa o papel arquitetural ou operacional do arquivo no
formato **família — subtipo**. Ela serve como pista inicial e complementa a
descrição específica da coluna **Função** ou **Demonstração principal**.

## 1. Domain

### Entities, Facts e Policy

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Entities/Demanda.php` | Domínio — Entidade | Representa uma necessidade homologada com identidade, situação e dados exatos. |
| `Entities/Plano.php` | Domínio — Raiz do Agregado | Aggregate Root que representa e protege a fotografia homologada de uma UO e exercício. |
| `Entities/UnidadeOrcamentaria.php` | Domínio — Entidade | Entidade organizacional que relaciona Lotação e UASG. |
| `Facts/HistoricoProcedimento.php` | Domínio — Fato | Fato imutável de procedimento concluído, preservado no modelo. |
| `Policies/PoliticaProcessamentoPlano.php` | Domínio — Política | Compara a fotografia desejada, o vínculo operacional local e o PCA observado para decidir PUBLICAR, RETIFICAR, EXCLUIR_PCA ou NENHUMA_ACAO. Ela é uma política pura porque não sabe que ocorreu uma chamada HTTP, ou seja, ela não consulta Oracle, não chama o PNCP, não abre transação, não registra log, recebe todos os fatos como argumentos. Isso permite testar a matriz sem banco, HTTP ou mocks complexos. |
| `Policies/ResultadoDecisaoProcessamentoPlano.php` | Domínio — Resultado de política | Resultado tipado e imutável da política de Domain que representa uma decisão válida, normaliza inclusões e exclusões e impede combinações contraditórias. Isso faz com que uma instância existente já seja confiável. O executor não precisa validar novamente se a decisão possui alguma diferença. Consequência se a responsabilidade não existisse: Poderiam circular decisões contraditórias, como RETIFICAR sem diferença ou o mesmo item sendo incluído e excluído. |
| `Ports/PlanoRepository.php` | Contrato — Repository | Port do Domain para descobrir identidades de Planos, reconstruir a fotografia vigente de cada Aggregate e confirmar, após a convergência, a sincronização da versão pendente. |

### Value Objects

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `ValueObjects/AnoExecucao.php` | Domínio — Value Object | Valida o exercício corrente ou seguinte. |
| `ValueObjects/Cnpj.php` | Domínio — Value Object | Normaliza e valida CNPJ alfanumérico. |
| `ValueObjects/DecimalExato.php` | Domínio — Value Object | Preserva uma string decimal válida sem `float`. |
| `ValueObjects/EstadoOperacionalPublicacao.php` | Domínio — Value Object | Representa não publicado, publicado ou excluído e seu sequencial. |
| `ValueObjects/IdentidadePlano.php` | Domínio — Value Object | Identidade lógica formada por UO e exercício. |
| `ValueObjects/IdentidadeUnidadeOrcamentaria.php` | Domínio — Value Object | Identificador estável da UO. |
| `ValueObjects/Lotacao.php` | Domínio — Value Object | Identifica e descreve uma lotação administrativa. |
| `ValueObjects/PcaObservado.php` | Domínio — Value Object | Fotografia abstrata do PCA encontrado no PNCP. Representa o fato externo e conclusivo que pode ser comparado pela política do Domain: PCA ausente ou publicação ativa com sequencial e conjunto ordenado e único de identidades observadas. Sem essa responsabilidade, dados físicos do PNCP poderiam atravessar a Application e alcançar diretamente a política do Domain. |
| `ValueObjects/Representante.php` | Domínio — Value Object | Identifica a pessoa responsável pela execução. |
| `ValueObjects/SequencialPca.php` | Domínio — Value Object | Encapsula o sequencial externo do PCA. |
| `ValueObjects/Uasg.php` | Domínio — Value Object | Identifica a unidade externa de publicação. |

### Enums e Exceptions

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Enums/DecisaoProcessamentoPlano.php` | Domínio — Enum | Nomeia intenção ainda não comprovada: PUBLICAR, RETIFICAR, EXCLUIR_PCA e NENHUMA_ACAO. |
| `Enums/EstadoPublicacao.php` | Domínio — Enum | Nomeia os estados operacionais da publicação. |
| `Enums/ProcedimentoPublicacaoPca.php` | Domínio — Enum | Classificação histórica de uma ação comprovada. Cataloga os procedimentos auditáveis. |
| `Enums/SituacaoDemanda.php` | Domínio — Enum | Define situações e participação na fotografia vigente. |
| `Exceptions/AnoExecucaoInvalidoException.php` | Domínio — Exceção | Falha específica de exercício inválido. |
| `Exceptions/ConflitoEstadoPublicacaoException.php` | Domínio — Exceção | Sinaliza estado de publicação semanticamente conflitante. |
| `Exceptions/HistoricoProcedimentoException.php` | Domínio — Exceção | Protege invariantes do fato histórico. |
| `Exceptions/PlanoException.php` | Domínio — Exceção | Exceção-base das invariantes do Plano. |
| `Exceptions/UnidadeOrcamentariaInvalidaException.php` | Domínio — Exceção | Protege invariantes da UO. |

## 2. Application

### Use case e colaboradores

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `UseCases/PublicarPca/PublicarPcaRequest.php` | Aplicação — Request | Entrada tipada com CNPJ e exercício já válidos. |
| `UseCases/PublicarPca/PublicarPcaUseCase.php` | Aplicação — Caso de uso | Implementa o contrato `PublicadorPca`, orquestra o fluxo global de preparação, varredura e classificação do resultado global para produzir a Response final. Consequência caso não existisse: Controller ou outros componentes teriam de conhecer a ordem do fluxo e montar Responses globais, duplicando a orquestração. |
| `UseCases/PublicarPca/PreparadorExecucaoPublicacaoPca.php` | Aplicação — Orquestrador | Prepara os recursos compartilhados por toda a varredura do `CoordenadorVarreduraUnidades`: Resolve contexto, estratégia anual, unidades externas e sessão PNCP. Consequência caso não existisse: A varredura poderia começar sem estratégia confirmada, contexto resolvido ou sessão PNCP; a preparação ficaria duplicada por unidade. |
| `UseCases/PublicarPca/CoordenadorVarreduraUnidades.php` | Aplicação — Orquestrador | Recebe quase tudo pronto do `PreparadorExecucaoPublicacaoPca` para então realizar a iteração das unidades e chamar o contrato `ProcessadorUnidade::processar()`, preservando resultados e interrompendo em falha sistêmica. Consequência caso não existisse: Resultados anteriores poderiam ser perdidos, unidades inválidas poderiam ser processadas ou a execução poderia continuar após uma falha sistêmica. |
| `UseCases/PublicarPca/ProcessadorUnidadeInterno.php` | Aplicação — Orquestrador | É um pequeno pipeline que encadeia preparação, decisão e despacho de uma unidade, retornando imediatamente quando uma etapa já produz bloqueio, falha reconciliável ou falha sistêmica. Consequência se a responsabilidade não existisse: O coordenador precisaria conhecer todas as etapas internas da unidade e repetir suas regras de sequência. |
| `UseCases/PublicarPca/PreparadorUnidadeSobExclusividade.php` | Aplicação — Orquestrador | Obtém a exclusividade transacional da unidade, relê e valida suas identidades, reconstrói a fotografia vigente e consulta o estado operacional local. No caminho válido, devolve um `ContextoUnidadeSobExclusividade` e mantém a unidade de trabalho aberta para decisão, escrita e finalização. Consequência se a responsabilidade não existisse: a decisão poderia usar dados obsoletos, concorrer com outro processamento ou combinar Plano, UASG e estado operacional incoerentes. |
| `UseCases/PublicarPca/DecisorUnidadeSobExclusividade.php` | Aplicação — Orquestrador | Conforme o estado operacional local, observa no PNCP o PCA vinculado pelo sequencial conhecido ou procura candidatos externos. Classifica observações inconclusivas, conflitos e falhas sistêmicas antes de entregar os fatos conclusivos à política do Domain. Consequência se a responsabilidade não existisse: a política pura teria de acessar o PNCP, ou adapters passariam a decidir regras de negócio, classificação de falhas e adoção de vínculos externos. |
| `UseCases/PublicarPca/DespachadorComandosUnidade.php` | Aplicação — Orquestrador | Coordena a passagem entre decisão, escrita e finalização: registra nos logs os itens planejados, aciona o executor, constrói o contexto validado da finalização e contém exceções com rollback. Consequência se a responsabilidade não existisse: o processador precisaria conhecer comandos externos, composição do contexto final, logging e tratamento transacional. |
| `UseCases/PublicarPca/ExecutorComandosUnidade.php` | Aplicação — Orquestrador | Traduz a decisão `PUBLICAR`, `RETIFICAR` e `EXCLUIR_PCA` em comandos externos da Application e preserva a ordem segura das escritas. Na retificação, valida limite, Demandas e sequencial, executa exclusões antes das inclusões e interrompe a sequência quando a escrita anterior não foi aceita. Consequência se a responsabilidade não existisse: ordem, pré-condições e classificação das escritas ficariam duplicadas nos casos de uso ou nos adapters. |
| `UseCases/PublicarPca/FinalizadorProcessamentoUnidadeInterno.php` | Aplicação — Orquestrador | Componente complexo: Após operação de escrita, exige nova observação e só encaminha para persistência quando a política comprova convergência. Procedimento: Observa novamente o PNCP após a escrita, recalcula a política (exemplo: Para uma retificação bem-sucedida, NENHUMA_ACAO aqui significa que a operação produziu convergência), permite no máximo uma reconciliação adicional diante de resultado incerto e somente produz sucesso depois de persistir estado, confirmação e auditoria e confirmar o commit, encerrando a unidade. Somente produz o resultado CONCLUIDO depois da confirmação da transação. Consequência se a responsabilidade não existisse: uma resposta de escrita poderia ser confundida com convergência, gerando confirmação ou auditoria de uma operação ainda não comprovada. |
| `UseCases/PublicarPca/DesfazedorUnidadeProcessamento.php` | Aplicação — Orquestrador | Centraliza rollback e prevalência de sua falha. |

### Commands e Contracts

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Commands/ComandoPublicarPca.php` | Aplicação — Command | Ordem de criação do PCA com a fotografia completa. |
| `Commands/ComandoInserirItensPca.php` | Aplicação — Command | Representa uma ordem imutável de inclusão em um PCA identificado. Exige ao menos uma Demanda, aceita somente objetos desse tipo e impede identidades duplicadas, sem conhecer JSON ou HTTP. |
| `Commands/ComandoExcluirItensPca.php` | Aplicação — Command | Representa uma ordem imutável de exclusão de itens de um PCA identificado. Exige IDs inteiros, únicos e não vazios e uma justificativa obrigatória; a conversão desses IDs em `listaNumerosItens` pertence ao serializer. |
| `Commands/ComandoExcluirPca.php` | Aplicação — Command | Ordem de exclusão integral do PCA. |
| `Contracts/SessaoPncp.php` | Contrato — Tipo opaco | Tipo opaco da sessão autenticada. |
| `Contracts/PublicadorPca.php` | Contrato — Port de entrada | Contrato executável usado pela Presentation para acionar o UseCase já montado. |
| `Contracts/ProcessadorUnidade.php` | Contrato — Contrato interno | Contrato consumido pelo coordenador global. |
| `Contracts/FinalizadorProcessamentoUnidade.php` | Contrato — Contrato interno | Contrato entre despacho e finalização da unidade. |

### Ports

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Ports/AutenticadorPncpPort.php` | Contrato — Port de saída | Autentica uma vez e devolve sessão opaca. |
| `Ports/ContextoUsuarioPort.php` | Contrato — Port de saída | Resolve Representante e Lotação conjuntamente. |
| `Ports/EscritorPncpPort.php` | Contrato — Port de saída | Define o contrato das quatro escritas PNCP. Recebe sessão e Commands internos e devolve resultados tipados, sem expor HTTP, JSON ou cURL aos casos de uso. |
| `Ports/EstadoOperacionalPublicacaoPort.php` | Contrato — Port de saída | Port de saída da Application que define a consulta e o registro da referência operacional de uma unidade, devolvendo resultados internos tipados sem expor sua persistência física. |
| `Ports/EstrategiaPublicacaoPort.php` | Contrato — Port de saída | Consulta e adota a estratégia anual. |
| `Ports/LeitorPncpPort.php` | Contrato — Port de saída | Port de saída pela qual a Application observa um PCA de sequencial conhecido ou procura candidatos externos, recebendo resultados internos tipados sem conhecer HTTP, JSON, paginação ou retry. |
| `Ports/RegistroAuditoriaPort.php` | Contrato — Port de saída | Port de saída da Application que solicita a persistência de um fato histórico (uma `IntencaoRegistroHistorico`) e devolve um resultado interno tipado, sem expor tabela, SQL ou geração do instante. |
| `Ports/UnidadeDeTrabalhoPort.php` | Contrato — Port de saída | Port de saída da Application que delimita a atomicidade local compartilhada ao iniciar a exclusividade transacional de uma chave e permitir a confirmação ou o desfazimento conjunto das alterações realizadas. |

### Value Objects de orquestração

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `ValueObjects/ChaveUnidadeTrabalho.php` | Aplicação — Value Object | Identifica o alvo da exclusividade transacional: `ESTRATEGIA_ANUAL` para a estratégia de um exercício ou `UNIDADE_EXTERNA` para uma publicação específica. |
| `ValueObjects/ContextoFinalizacaoProcessamentoUnidade.php` | Aplicação — Contexto | Reúne os fatos iniciais, a decisão e os resultados das escritas pertencentes à mesma unidade, rejeitando combinações incompatíveis antes da finalização. Consequência se a responsabilidade não existisse: o finalizador poderia receber Planos ausentes, falhas sistêmicas ou resultados de escrita incompatíveis com a decisão. |
| `ValueObjects/ContextoUnidadeComDecisao.php` | Aplicação — Contexto | Transporta contexto exclusivo, PCA observado e decisão. |
| `ValueObjects/ContextoUnidadeSobExclusividade.php` | Aplicação — Contexto | Transporta Planos e estado relidos sob lock. |
| `ValueObjects/ContextoUsuario.php` | Aplicação — Contexto | Agrupa Representante e Lotação autenticados. |
| `ValueObjects/CorrelacaoExecucao.php` | Aplicação — Value Object | Identificador seguro devolvido na Response. |
| `ValueObjects/EstrategiaPublicacao.php` | Aplicação — Value Object | Representa modo individual ou consolidado. |
| `ValueObjects/FalhaSegura.php` | Aplicação — Value Object | Código e mensagem que podem atravessar fronteiras. |
| `ValueObjects/IntencaoRegistroHistorico.php` | Aplicação — Value Object | Conteúdo de auditoria pronto, ainda sem instante Oracle. |
| `ValueObjects/UnidadeExternaPublicacao.php` | Aplicação — Value Object | Alvo individual ou consolidado processado atomicamente. |

### Results

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Results/PublicarPcaResponse.php` | Aplicação — Response | Contrato global de saída do UseCase. Reúne a situação da solicitação, os resultados por unidade, a eventual falha global segura, as contagens da varredura e a correlação, protegendo a coerência entre esses dados. |
| `Results/DesfechoProcessamentoUnidade.php` | Aplicação — Desfecho | Representa as duas alternativas devolvidas pelo processamento de uma unidade: um resultado unitário conhecido ou uma falha sistêmica que interrompe a varredura. |
| `Results/ResultadoProcessamentoUnidade.php` | Aplicação — Resultado | Representa o resultado terminal conhecido de uma unidade (`CONCLUIDO`, `SEM_ALTERACAO`, `FALHA_RECONCILIAVEL` ou `BLOQUEADO`) e protege invariantes de compatibilidade entre situação, decisão, sequencial, contagens e falha. Na retificação concluída, também preserva as listas de itens incluídos e excluídos apresentadas ao consumidor. Sem essa responsabilidade, poderiam circular resultados contraditórios ou listas incompatíveis com a decisão e suas contagens. |
| `Results/ResultadoVarreduraUnidades.php` | Aplicação — Resultado | Reúne os resultados conhecidos das unidades, a quantidade de unidades visitadas e a eventual falha que interrompeu a varredura. Sem ele, o UseCase perderia a visão da execução sequencial completa. |
| `Results/ResultadoPreparacaoPublicacaoPca.php` | Aplicação — Resultado | Resultado da preparação global. |
| `Results/ResultadoResolucaoContextoUsuario.php` | Aplicação — Resultado | Resultado da resolução do usuário. |
| `Results/ResultadoAutenticacaoPncp.php` | Aplicação — Resultado | Resultado da autenticação externa. |
| `Results/ResultadoConsultaEstrategia.php` | Aplicação — Resultado | Estratégia disponível, ausente, inválida ou falha. |
| `Results/ResultadoAdocaoEstrategia.php` | Aplicação — Resultado | Confirma ou rejeita a adoção anual. |
| `Results/ResultadoInicioUnidadeTrabalho.php` | Aplicação — Resultado | Iniciada, bloqueada ou falha sistêmica. |
| `Results/ResultadoFinalizacaoUnidadeTrabalho.php` | Aplicação — Resultado | Resultado de commit ou rollback. |
| `Results/ResultadoConsultaEstadoOperacional.php` | Aplicação — Resultado | Estado disponível, conflitante ou indisponível. |
| `Results/ResultadoBuscaCandidatosPca.php` | Aplicação — Resultado | Resultado da busca paginada abstrata de candidatos. |
| `Results/ResultadoObservacaoPca.php` | Aplicação — Resultado | Entrega à Application um resultado tipado e coerente, descrevendo os desfechos possíveis de uma tentativa de observação: PCA encontrado, ausência definitiva, observação inconclusiva, conflito ou falha sistêmica. Protege a compatibilidade entre situação, `PcaObservado` e falha segura. |
| `Results/ResultadoPublicacaoPncp.php` | Aplicação — Resultado | Resultado da criação com possível sequencial. |
| `Results/ResultadoEscritaPncp.php` | Aplicação — Resultado | Resultado de inclusão ou exclusão. |
| `Results/ResultadoPersistenciaLocal.php` | Aplicação — Resultado | Resultado de estado local ou auditoria. |

### Enums da Application

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Enums/AcaoExecucaoPublicacao.php` | Aplicação — Enum | Ação técnica global usada pela fachada ao registrar uma falha inesperada segura. |
| `Enums/AcaoProcessamentoUnidade.php` | Aplicação — Enum | Vocabulário operacional estável das ações executadas durante a finalização de uma unidade. |
| `Enums/EscopoUnidadeTrabalho.php` | Aplicação — Enum | Distingue estratégia anual e unidade externa. |
| `Enums/ModoPublicacao.php` | Aplicação — Enum | Distingue individual e consolidado. |
| `Enums/SituacaoAutenticacaoPncp.php` | Aplicação — Enum | Estados da autenticação. |
| `Enums/SituacaoBuscaCandidatosPca.php` | Aplicação — Enum | Estados da busca de candidatos. |
| `Enums/SituacaoConsultaEstadoOperacional.php` | Aplicação — Enum | Estados da consulta local. |
| `Enums/SituacaoConsultaEstrategia.php` | Aplicação — Enum | Estados da estratégia anual. |
| `Enums/SituacaoDesfechoProcessamentoUnidade.php` | Aplicação — Enum | Tipo do desfecho unitário. |
| `Enums/SituacaoEscritaPncp.php` | Aplicação — Enum | Aceita, rejeitada, incerta ou sistêmica. |
| `Enums/SituacaoFinalizacaoUnidadeTrabalho.php` | Aplicação — Enum | Resultado de finalização transacional. |
| `Enums/SituacaoInicioUnidadeTrabalho.php` | Aplicação — Enum | Resultado da aquisição da unidade. |
| `Enums/SituacaoObservacaoPca.php` | Aplicação — Enum | Estados da observação externa. |
| `Enums/SituacaoPersistenciaLocal.php` | Aplicação — Enum | Estados da persistência local. |
| `Enums/SituacaoPreparacaoPublicacaoPca.php` | Aplicação — Enum | Preparada, sem unidades ou interrompida. |
| `Enums/SituacaoProcessamentoUnidade.php` | Aplicação — Enum | Estados terminais expostos por unidade. |
| `Enums/SituacaoPublicacaoPca.php` | Aplicação — Enum | Quatro estados globais da execução. |
| `Enums/SituacaoResolucaoContextoUsuario.php` | Aplicação — Enum | Estados da resolução do usuário. |

## 3. Infrastructure

### Oracle — conexão ADOdb emprestada

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Oracle/Adodb/ConexaoAdodbEmprestada.php` | Infraestrutura — Gateway ADOdb | Encapsula a sessão ADOdb fornecida pelo host, prepara binds e controla seu estado transacional sem fechar a conexão. |
| `Oracle/Adodb/ConversorCharsetAdodbOracle.php` | Infraestrutura — Conversor | Converte leituras Windows-1252 para UTF-8 e binds no sentido inverso, bloqueando perda de caracteres. |
| `Oracle/Adodb/FalhaOracleException.php` | Infraestrutura — Exceção | Transporta somente a mensagem segura e o eventual código `ORA-*`. |

### Oracle — unidade de trabalho e locks

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Oracle/Transacao/MapeamentoControlesConcorrenciaOracle.php` | Infraestrutura — Mapeamento físico | Valida os identificadores físicos e o alvo anual dos controles de concorrência. |
| `Oracle/Transacao/OracleUnidadeDeTrabalhoAdapter.php` | Infraestrutura — Adapter | Implementa a unidade de trabalho no Oracle: provisiona idempotentemente a linha de controle, abre a transação, adquire o lock `FOR UPDATE NOWAIT` e executa o commit ou rollback solicitado pela Application. |

### Oracle — estratégia anual

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Oracle/Estrategia/MapeamentoConfiguracaoEstrategiaOracle.php` | Infraestrutura — Mapeamento físico | Declara `CHAVE`, `VALOR`, `DESCRICAO`, chaves e literais da configuração corrente. |
| `Oracle/Estrategia/MapeamentoPlanosEstrategiaOracle.php` | Infraestrutura — Mapeamento físico | Declara a projeção física da estratégia adotada nas linhas de Plano. |
| `Oracle/Estrategia/EstrategiaOracleInvalidaException.php` | Infraestrutura — Exceção | Sinaliza internamente dados físicos ausentes, parciais ou incoerentes. |
| `Oracle/Estrategia/OracleEstrategiaPublicacaoAdapter.php` | Infraestrutura — Adapter | Consulta a configuração, resolve UASGs individuais no cadastro de UOs e adota a estratégia anual na transação compartilhada. |

### Oracle — Planos

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Oracle/Plano/MapeamentoPlanosOracle.php` | Infraestrutura — Mapeamento físico | Declara tabela e colunas físicas necessárias à versão e confirmação dos Planos. |
| `Oracle/Plano/MapeamentoDemandasOracle.php` | Infraestrutura — Mapeamento físico | Declara a relação de origem e os campos físicos necessários à reconstrução das Demandas. |
| `Oracle/Plano/MapeamentoUnidadesOrcamentariasOracle.php` | Infraestrutura — Mapeamento físico | Declara identidade, UASG, vínculo com Plano e condição de cadastro ativo da UO. |
| `Oracle/Plano/MapeamentoLotacoesOracle.php` | Infraestrutura — Mapeamento físico | Declara a tabela e as colunas de Lotação usadas para compor a UO. |
| `Oracle/Plano/MapeadorDadosPlanoOracle.php` | Infraestrutura — Mapeador de dados | Traduz aliases Oracle estritos para tipos de Unidade Orçamentária, Demanda e Plano. |
| `Oracle/Plano/DadosPlanoOracleInvalidosException.php` | Infraestrutura — Exceção | Sinaliza internamente dados físicos ausentes, duplicados ou incoerentes. |
| `Oracle/Plano/OraclePlanoRepository.php` | Infraestrutura — Adapter | Descobre identidades, reconstrói a fotografia vigente e confirma a versão pendente na transação compartilhada. |

### Oracle — estado operacional

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Oracle/EstadoOperacional/MapeamentoEstadoOperacionalOracle.php` | Infraestrutura — Mapeamento físico | Declara tabela, colunas e literais da projeção operacional em Planos. |
| `Oracle/EstadoOperacional/EstadoOperacionalOracleInvalidoException.php` | Infraestrutura — Exceção | Sinaliza internamente projeções físicas ausentes, parciais ou divergentes. |
| `Oracle/EstadoOperacional/OracleEstadoOperacionalPublicacaoAdapter.php` | Infraestrutura — Adapter | Implementa a Port no Oracle: consulta a projeção compartilhada pelos Planos e, ao registrar um estado, propaga-o às versões participantes e relê os dados para confirmar sua consistência dentro da transação ativa. |

### Oracle — contexto do usuário

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Oracle/Contexto/MapeamentoRepresentantesOracle.php` | Infraestrutura — Mapeamento físico | Declara o cadastro físico usado para resolver o Representante ativo pelo login. |
| `Oracle/Contexto/MapeamentoLotacoesUsuarioOracle.php` | Infraestrutura — Mapeamento físico | Declara vínculos e cadastro usados para resolver a Lotação vigente e ativa. |
| `Oracle/Contexto/DadosContextoUsuarioOracleInvalidosException.php` | Infraestrutura — Exceção | Sinaliza internamente dados físicos de identidade ou Lotação inválidos. |
| `Oracle/Contexto/OracleContextoUsuarioAdapter.php` | Infraestrutura — Adapter | Resolve um único Representante e uma única Lotação pelo login confiável da execução. |

### Oracle — auditoria

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Oracle/Auditoria/MapeamentoHistoricosOracle.php` | Infraestrutura — Mapeamento físico | Declara tabela e colunas físicas dos fatos em `PAC_HISTORICOS`. |
| `Oracle/Auditoria/OracleRegistroAuditoriaAdapter.php` | Infraestrutura — Adapter | Implementa o registro de auditoria no Oracle: valida e transforma a intenção de auditoria em registro físico como um fato append-only, atribui o instante por SYSDATE e o insere na transação compartilhada. |

### Oracle — observabilidade técnica

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Oracle/Observabilidade/OracleRegistroEventoTecnicoAdapter.php` | Infraestrutura — Adapter | Persiste um envelope técnico integral em `SGI.SEGURANCA_LOG`: insere um marcador curto, atualiza o CLOB por ID com `UpdateClob` e encerra uma transação técnica própria. |

### PNCP — transporte HTTP

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Pncp/Http/AmbientePncp.php` | Infraestrutura — Configuração técnica | Restringe o destino aos ambientes oficiais de Homologação e Produção. |
| `Pncp/Http/ConfiguracaoTransportePncp.php` | Infraestrutura — Configuração técnica | Valida limites, timeouts e o caminho opcional do CA bundle. |
| `Pncp/Http/RequisicaoHttpPncp.php` | Infraestrutura — Requisição HTTP | Representa e valida método, caminho relativo, headers, corpo e timeout de uma requisição interna. |
| `Pncp/Http/RespostaHttpPncp.php` | Infraestrutura — Resposta HTTP | Representa uma resposta HTTP estruturalmente válida, preservando status, múltiplos valores de headers e corpo. |
| `Pncp/Http/TransporteHttpPncp.php` | Contrato — Contrato técnico | Contrato técnico interno da Infrastructure que recebe uma `RequisicaoHttpPncp` e devolve uma `RespostaHttpPncp`. Permite substituir toda a comunicação HTTP nos testes dos adapters. Sem ele: Autenticador, leitor e escritor ficariam acoplados ao cURL e seriam difíceis de testar sem rede. |
| `Pncp/Http/TransporteCurlPncp.php` | Infraestrutura — Transporte HTTP | Implementa o transporte HTTP seguro do PNCP: aplica HTTPS, verificação TLS e de hostname, timeouts, limites e proibição de redirecionamento; coleta headers e corpo por callbacks, executa uma única chamada pelo executor cURL e registra diagnóstico sem decidir o significado da operação para a Application. |
| `Pncp/Http/ExecutorCurlPncp.php` | Contrato — Contrato técnico | Isola especificamente a execução do cURL para permitir testes determinísticos sem rede (`TransporteCurlPncp` e suas opções). |
| `Pncp/Http/ExecutorCurlNativoPncp.php` | Infraestrutura — Executor técnico | Executa a chamada com a extensão cURL nativa e captura seu resultado técnico. |
| `Pncp/Http/ResultadoExecucaoCurlPncp.php` | Infraestrutura — Resultado técnico | Representa a fotografia técnica devolvida pelo executor cURL — execução, `errno`, status informado, verificação TLS, versão HTTP e diagnóstico de conexão. Não representa, sozinho, uma resposta HTTP válida nem o resultado da operação PNCP. |
| `Pncp/Http/FalhaTransportePncpException.php` | Infraestrutura — Exceção | Representa falhas técnicas do transporte com códigos e mensagens sanitizados e preserva internamente o errno cURL seguro. |

### PNCP — leitura, paginação e retry

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Pncp/Leitura/ConfiguracaoLeituraPncp.php` | Infraestrutura — Configuração técnica | Exige tamanho de página, profundidade JSON, limite defensivo, espera padrão e teto de `Retry-After`. |
| `Pncp/Leitura/LeitorPncpAdapter.php` | Infraestrutura — Adapter | Implementa a observação ao PNCP. Realiza buscas completas do PCA conhecido (nunca entrega fotografia parcial), valida a coerência da sua identidade, reúne todos os números de itens publicados, comprova ausência por duas leituras e classifica conflitos e respostas inconclusivas. |
| `Pncp/Leitura/TemporizadorPncp.php` | Contrato — Contrato técnico | Define relógio UTC e espera injetáveis para retry determinístico. |
| `Pncp/Leitura/TemporizadorSistemaPncp.php` | Infraestrutura — Serviço técnico | Implementa relógio e espera com recursos internos do sistema. |

### PNCP — serialização exata

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Pncp/Serializacao/FalhaSerializacaoPncp.php` | Infraestrutura — Exceção | Expõe uma falha fixa e segura sem incluir valores ou payloads. |
| `Pncp/Serializacao/NumeroJsonExato.php` | Infraestrutura — Value Object técnico | Preserva um lexema numérico JSON previamente validado para emissão sem aspas. |
| `Pncp/Serializacao/CodificadorJsonExato.php` | Infraestrutura — Serializador | Codifica recursivamente tipos permitidos e rejeita `float`, recursos, objetos desconhecidos e UTF-8 inválido. |
| `Pncp/Serializacao/ConversorDecimalPncp.php` | Infraestrutura — Conversor | Converte e divide decimais textualmente com escala quatro, `HALF_UP` e limites de `DECIMAL(17,4)`. |
| `Pncp/Serializacao/SerializadorPayloadPncp.php` | Infraestrutura — Serializador | Projeta os quatro Commands de escrita nos corpos JSON exatos do PNCP. Ordena itens, valida limites físicos, formata datas e converte decimais sem `float`, sem decidir quais operações devem ser realizadas. Sem ele: o contrato físico do payload ficaria espalhado pelos adapters ou atravessaria as camadas internas, contaminando Application e Domain. |

### PNCP — escritas e resultados incertos

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Pncp/Escrita/EscritorPncpAdapter.php` | Infraestrutura — Adapter | Implementa a Port `EscritorPncpPort`: valida a sessão concreta, monta uma única requisição HTTP por operação, usa o serializer e o transporte e classifica aceitação, rejeição, resultado incerto ou falha sistêmica sem retry de escrita. Na publicação inicial, também extrai o sequencial de um `Location` estrito por ambiente. |

### PNCP — autenticação e observabilidade

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Pncp/Autenticacao/AutenticadorPncpAdapter.php` | Infraestrutura — Adapter | Implementa autenticação única, extrai o Bearer da resposta e traduz falhas para o resultado da Application. |
| `Pncp/Autenticacao/SessaoPncpAutenticada.php` | Infraestrutura — Sessão técnica | Mantém o token opaco em uma implementação concreta do contrato de sessão. |
| `Pncp/Observabilidade/AcaoTecnicaPncp.php` | Infraestrutura — Enum | Cataloga autenticação, leituras e as quatro escritas sem ampliar o enum operacional da Application. |

### Observabilidade em memória

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Observabilidade/NivelEventoTecnico.php` | Infraestrutura — Enum | Define o vocabulário técnico exportável dos níveis de evento (`DEBUG`, `INFO`, `WARNING` e `ERROR`), para o qual o acumulador converte os níveis PSR-3. |
| `Observabilidade/RelogioEventosTecnicos.php` | Contrato — Contrato técnico | Contrato mínimo de relógio para timestamp e duração da execução. |
| `Observabilidade/RelogioSistemaEventosTecnicos.php` | Infraestrutura — Serviço técnico | Implementação do relógio baseada no tempo do sistema. |
| `Observabilidade/EventoTecnico.php` | Infraestrutura — DTO técnico | Evento técnico imutável com ação normalizada e envelope estruturado e sanitizado. |
| `Observabilidade/LoteEventosTecnicos.php` | Infraestrutura — DTO técnico | Cópia exportável dos envelopes com duração somente no último metadata. |
| `Observabilidade/AcumuladorEventosTecnicos.php` | Infraestrutura — Acumulador técnico | Logger PSR-3 que valida ações tipadas, sanitiza e acumula eventos ordenados sem I/O. |

## 4. Fronteira Scriptcase

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `PublicadorPcaScriptcase.php` | Fronteira pública — Fachada pública | Fachada pública de publicação consumida pela Blank. Expõe os entrypoints `executar()` e `executarComEventos()` e delega a execução à Composition, escondendo do hospedeiro a montagem e as camadas internas. |
| `ResultadoPublicacaoPcaScriptcase.php` | Fronteira pública — DTO público | Saída imutável da variante com eventos para transportar conjuntamente o ViewModel, os eventos e o aviso técnico. Sem ele, a API pública teria de misturar eventos técnicos na Response do caso de uso ou usar retornos paralelos pouco claros. |
| `RegistradorEventosTecnicosScriptcase.php` | Fronteira pública — Fachada pública | Fachada pública que recebe o lote já exportado e solicita seu registro técnico sem expor SQL ou detalhes internos à Blank. |
| `integracao/README.md` | Documentação — Integração | Explica a fronteira com o Scriptcase, a publicação da Blank e os limites de responsabilidade e segurança. |
| `integracao/scriptcase/PCA_PUBLICAR_B/onExecute.php` | Hospedeiro — Front Controller | Adaptação versionada da blank Scriptcase, que exerce o papel de aplicação hospedeira e de Front Controller. Recebe a requisição; diferencia uma simples navegação do menu de uma requisição POST JSON de publicação; coleta conexão, login, servidor e correlationID; carrega o autoloader do backend (`pca-pncp-backend/vendor/autoload.php`); chama as duas fachadas públicas (`PublicadorPcaScriptcase` e `RegistradorEventosTecnicosScriptcase`); serializa as respostas; registra os eventos; renderiza e apresenta o resultado seguro; oferece ajuda estática ao operador. |

## 5. Presentation

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Inputs/EntradaPublicarPca.php` | Presentation — Input | Entrada imutável com CNPJ textual e ano inteiro, sem materializar o Domain. |
| `Controllers/PublicarPcaController.php` | Presentation — Controller | Transforma a entrada da Presentation em Value Objects e Request da Application, sinaliza internamente entradas inválidas, delega a `PublicadorPca` e entrega a Response ao Presenter. Consequência caso não existisse: A Application receberia primitivos não validados ou essa adaptação ficaria espalhada em outras classes. |
| `Exceptions/EntradaPublicarPcaInvalidaException.php` | Presentation — Exceção | Transporta uma falha de entrada segura exclusivamente entre Controller e fachada, preservando internamente a causa original para diagnóstico. |
| `Facades/FachadaPublicarPca.php` | Presentation — Fachada | Protege a fronteira interna da Presentation: converte entradas inválidas e falhas inesperadas em ViewModels seguros e registra a captura inesperada pelo logger compartilhado. Consequência caso não existisse: Entradas inválidas poderiam virar falhas inesperadas; exceções e detalhes internos poderiam escapar; o evento técnico seguro poderia não ser registrado. |
| `Presenters/PublicarPcaPresenter.php` | Presentation — Presenter | Projeta objetos internos (Response global com seus resultados unitários) em dados escalares seguros. Sem ele, a Blank precisaria conhecer enums, Value Objects e estruturas da Application. |
| `ViewModels/PublicarPcaViewModel.php` | Presentation — ViewModel | Estrutura imutável de transporte de dados já preparados para a apresentação ao consumidor: situação da solicitação, resultados por unidade, falha global segura, contagens e correlação. Sem ele, a Presentation não teria um modelo estável e independente da renderização. |
| `ViewModels/ResultadoUnidadeViewModel.php` | Presentation — ViewModel | Estrutura imutável de transporte de dados já preparados para uma unidade externa: identificação, situação, decisão, sequencial, contagens, listas da retificação e eventual falha segura. |

## 6. Composition

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `MontadorNucleoPublicacaoPca.php` | Composição — Montador | Monta a topologia da Application a partir de Ports já fornecidas. |
| `CarregadorPerfilPublicacaoPca.php` | Composição — Carregador de configuração | Prepara a configuração necessária para a montagem: Carrega o registro externo, **valida os caminhos** de todos os perfis registrados e seleciona literalmente pelo `SERVER_NAME` o array bruto do perfil. Porém, não valida semanticamente todas as configurações do perfil, pois essa é a responsabilidade de `PerfilPublicacaoPca`. |
| `ComposicaoOraclePublicacaoPca.php` | Composição — Subcomposição | Monta o subgrafo dos seis adapters Oracle que compartilham a mesma `ConexaoAdodbEmprestada`. |
| `ComposicaoPncpPublicacaoPca.php` | Composição — Subcomposição | Monta o subgrafo dos adapters PNCP de autenticação, leitura e escrita, compartilhando transporte HTTP, configurações e logger. |
| `ComposicaoExecucaoPublicacaoPca.php` | Composição — Contexto de execução | Mantém fachada, acumulador de eventos e conexão pertencentes à mesma chamada; exporta eventos somente após encerramento transacional e preserva o resultado diante de falha técnica. |
| `CompositionRootPublicacaoPca.php` | Composição — Composition Root | Coordena a montagem completa da publicação. Instancia os adapters concretos e conecta Presentation, Application e Infrastructure em um grafo novo por chamada. Não executa a publicação nem decide o negócio. |
| `ExecutorPublicacaoPcaScriptcase.php` | Composição — Executor de fronteira | Coordena a preparação: valida correlação, carrega perfil, cria CNPJ e entrada, solicita o grafo e adapta a saída para o ViewModel existente ou para o resultado separado com eventos. |
| `ExecutorRegistroEventosTecnicosScriptcase.php` | Composição — Executor de fronteira | Carrega o charset do perfil externo, monta o adapter de CLOB sobre a conexão emprestada, preserva eventos já registrados e converte a primeira falha em aviso seguro. |
| `PerfilPublicacaoPca.php` | Composição — Perfil validado | Prepara a configuração necessária para a montagem: Converte o array externo não confiável (`CarregadorPerfilPublicacaoPca`) em configurações e mapeamentos tipados e validados. |

## 7. Testes

### Domain

| Arquivo | Natureza | Demonstração principal |
| --- | --- | --- |
| `Entities/DemandaTest.php` | Teste — Unidade | Invariantes e valores exatos da Demanda. |
| `Entities/PlanoTest.php` | Teste — Unidade | Identidade, fotografia, versão e confirmação do Aggregate. |
| `Entities/UnidadeOrcamentariaTest.php` | Teste — Unidade | Invariantes da UO. |
| `Enums/DecisaoProcessamentoPlanoTest.php` | Teste — Unidade | Catálogo de decisões. |
| `Enums/SituacaoDemandaTest.php` | Teste — Unidade | Participação das situações na fotografia. |
| `Facts/HistoricoProcedimentoTest.php` | Teste — Unidade | Invariantes do fato histórico. |
| `Policies/PoliticaProcessamentoPlanoTest.php` | Teste — Unidade | Matriz de decisão e reconciliação pura. |
| `ValueObjects/AnoExecucaoTest.php` | Teste — Unidade | Faixa válida de exercícios. |
| `ValueObjects/CnpjTest.php` | Teste — Unidade | CNPJ numérico e alfanumérico. |
| `ValueObjects/DecimalExatoTest.php` | Teste — Unidade | Preservação decimal e rejeição de `float`. |
| `ValueObjects/EstadoOperacionalPublicacaoTest.php` | Teste — Unidade | Coerência entre estado e sequencial. |
| `ValueObjects/IdentidadePlanoTest.php` | Teste — Unidade | Igualdade lógica do Plano. |
| `ValueObjects/IdentidadeUnidadeOrcamentariaTest.php` | Teste — Unidade | Validação da identidade da UO. |
| `ValueObjects/LotacaoTest.php` | Teste — Unidade | Validação de Lotação. |
| `ValueObjects/RepresentanteTest.php` | Teste — Unidade | Validação de Representante. |
| `ValueObjects/UasgTest.php` | Teste — Unidade | Validação e igualdade de UASG. |

### Application — contratos e dados

| Arquivo | Natureza | Demonstração principal |
| --- | --- | --- |
| `Commands/ComandosEscritaPncpTest.php` | Teste — Unidade | Invariantes de todos os comandos externos. |
| `Contracts/FinalizadorProcessamentoUnidadeTest.php` | Teste — Unidade | Consumo do contrato de finalização. |
| `Ports/ContextoAuditoriaEstadoOperacionalPortsTest.php` | Teste — Unidade | Contratos de contexto, estado e auditoria. |
| `Ports/IntegracaoPncpEEstrategiaPortsTest.php` | Teste — Unidade | Contratos PNCP e estratégia. |
| `Ports/UnidadeDeTrabalhoPortTest.php` | Teste — Unidade | Contrato de begin, commit e rollback. |
| `Results/PublicarPcaResponseTest.php` | Teste — Unidade | Quatro estados e invariantes globais. |
| `Results/ResultadoAutenticacaoPncpTest.php` | Teste — Unidade | Desfechos de autenticação. |
| `Results/ResultadoBuscaCandidatosPcaTest.php` | Teste — Unidade | Busca de candidatos e conflitos. |
| `Results/ResultadoEstrategiaPublicacaoTest.php` | Teste — Unidade | Consulta e adoção da estratégia. |
| `Results/ResultadoObservacaoPcaTest.php` | Teste — Unidade | Desfechos da observação externa. |
| `Results/ResultadoPreparacaoPublicacaoPcaTest.php` | Teste — Unidade | Invariantes da preparação global. |
| `Results/ResultadoProcessamentoUnidadeTest.php` | Teste — Unidade | Contagens e decisões do resultado unitário. |
| `Results/ResultadoVarreduraUnidadesTest.php` | Teste — Unidade | Contagem de visitas e interrupções. |
| `Results/ResultadosContextoEEstadoOperacionalTest.php` | Teste — Unidade | Resultados de contexto e estado. |
| `Results/ResultadosEscritaPncpTest.php` | Teste — Unidade | Escrita aceita, rejeitada, incerta e sistêmica. |
| `Results/ResultadosUnidadeTrabalhoTest.php` | Teste — Unidade | Resultados transacionais. |
| `ValueObjects/ChaveUnidadeTrabalhoTest.php` | Teste — Unidade | Chaves anuais e unitárias. |
| `ValueObjects/ContextoFinalizacaoProcessamentoUnidadeTest.php` | Teste — Unidade | Compatibilidade entre decisão e escritas. |
| `ValueObjects/ContextoUsuarioTest.php` | Teste — Unidade | Contexto autenticado. |
| `ValueObjects/EstrategiaPublicacaoTest.php` | Teste — Unidade | Modos e UASG consolidadora. |
| `ValueObjects/FalhaSeguraTest.php` | Teste — Unidade | Código e mensagem seguros. |
| `ValueObjects/IntencaoRegistroHistoricoTest.php` | Teste — Unidade | Conteúdo auditável. |
| `ValueObjects/UnidadeExternaPublicacaoTest.php` | Teste — Unidade | Unidade individual e consolidada. |

### Application — fluxo

| Arquivo | Natureza | Demonstração principal |
| --- | --- | --- |
| `UseCases/PublicarPca/PublicarPcaRequestTest.php` | Teste — Unidade | Entrada mínima e imutável. |
| `UseCases/PublicarPca/PublicarPcaUseCaseTest.php` | Teste — Unidade | Agregação e estados globais. |
| `UseCases/PublicarPca/PreparadorExecucaoPublicacaoPcaTest.php` | Teste — Unidade | Prepara os recursos compartilhados pela varredura: resolve o contexto do usuário, lista as identidades dos planos, valida e adota transacionalmente a estratégia anual, forma as unidades externas e autentica no PNCP. No fluxo individual, forma uma unidade externa para cada identidade de plano. No consolidado, forma uma única unidade externa para todo o exercício. |
| `UseCases/PublicarPca/CoordenadorVarreduraUnidadesTest.php` | Teste — Unidade | Valida e percorre as unidades, preservando resultados e interrompendo somente diante de falha sistêmica. |
| `UseCases/PublicarPca/PreparadorUnidadeSobExclusividadeTest.php` | Teste — Unidade | Lock, releitura, reconstrução e estado local. |
| `UseCases/PublicarPca/DecisorUnidadeSobExclusividadeTest.php` | Teste — Unidade | Observação e chamada da política. |
| `UseCases/PublicarPca/DespachadorComandosUnidadeTest.php` | Teste — Unidade | Escritas, limite de 1000, ordem e rollback. |
| `UseCases/PublicarPca/FinalizadorProcessamentoUnidadeInternoTest.php` | Teste — Unidade | Observação final, reconciliação, persistência e auditoria. |
| `UseCases/PublicarPca/ProcessadorUnidadeInternoTest.php` | Teste — Unidade | Encadeamento concreto no caminho sem escrita. |
| `UseCases/PublicarPca/FluxoConcretoApplicationTest.php` | Teste — Integração interna | Executa pelo montador o grafo completo para publicação, retificação, exclusão e consolidação. |

### Presentation

| Arquivo | Natureza | Demonstração principal |
| --- | --- | --- |
| `Presentation/PublicacaoPcaPresentationTest.php` | Teste — Integração interna | Fluxo transversal da entrada aos ViewModels, saída renderizável, preservação de resultados e sanitização de falhas inesperadas. |
| `Presentation/Inputs/EntradaPublicarPcaTest.php` | Teste — Unidade | Preservação dos primitivos tipados e imutabilidade da entrada. |
| `Presentation/Controllers/PublicarPcaControllerTest.php` | Teste — Unidade | Classificação localizada da entrada, materialização dos Value Objects, delegação, apresentação e distinção de falhas posteriores. |
| `Presentation/Facades/FachadaPublicarPcaTest.php` | Teste — Unidade | Caminhos válidos, solicitações inválidas, fronteira de `Throwable`, ausência de vazamento e preservação de Responses seguras. |
| `Presentation/Presenters/PublicarPcaPresenterTest.php` | Teste — Unidade | Estados globais e unitários, modos, decisões, campos opcionais, ordem, contagens, correlação e falhas seguras. |

### Composition

| Arquivo | Natureza | Demonstração principal |
| --- | --- | --- |
| `Composition/MontadorNucleoPublicacaoPcaTest.php` | Teste — Unidade | Contrato de montagem do UseCase a partir das nove Ports vigentes e do logger PSR-3. |
| `Composition/ComposicaoExecucaoPublicacaoPcaTest.php` | Teste — Unidade | Exportação sanitizada e imutável, bloqueio por transação ativa e preservação do resultado diante de falha de exportação. |
| `Composition/CompositionRootPublicacaoPcaTest.php` | Teste — Unidade | Validação integral do perfil, grafo novo, recursos compartilhados, montagem exportável sem I/O e preservação da conexão emprestada. |

### Integration — Composition

| Arquivo | Natureza | Demonstração principal |
| --- | --- | --- |
| `Integration/Composition/MontadorNucleoPublicacaoPcaIntegrationTest.php` | Teste — Integração interna | Exercita os nove adapters concretos pelo montador em convergência, retificação e falha final reconciliável, sem recursos externos reais. |
| `Integration/Composition/Support/CenarioIntegracaoNucleo.php` | Teste — Suporte de integração | Constrói as configurações fictícias, os adapters concretos e os roteiros Oracle e PNCP dos cenários controlados. |
| `Integration/Composition/Support/AdodbControlado.php` | Teste — Suporte de integração | Responde consultas e falhas Oracle por fila estrita e registra transações, consultas, escritas e tentativas de fechamento sem acessar Oracle. |
| `Integration/Composition/Support/ExecutorCurlRoteirizado.php` | Teste — Suporte de integração | Exige método e URL, alimenta os callbacks ou devolve falha cURL controlada ao transporte concreto e impede qualquer chamada de rede. |
| `Integration/Composition/Support/RegistroCronologico.php` | Teste — Suporte de integração | Reúne eventos Oracle e PNCP sanitizados na ordem em que foram observados. |
| `Integration/Composition/Support/TemporizadorControlado.php` | Teste — Suporte de integração | Registra a espera de retry solicitada pelo leitor sem suspender a execução do teste. |
| `Integration/PublicadorPcaScriptcaseIntegrationTest.php` | Teste — Integração interna | Percorre entrypoint, perfil, Composition Root, Presentation e Application e comprova que a variante com eventos processa a solicitação uma única vez, sem rede real. |
| `Integration/PackageReleaseToolTest.php` | Teste — Integração interna | Protege os aliases da release, a criação segura do `.tar.gz`, a recusa de sobrescrita e a exigência de worktree limpo. |
| `Integration/Scriptcase/PcaPublicarBlankTest.php` | Teste — Integração interna | Protege o contrato da Blank para entrada, fachada, logs integrais, resposta pública, submissão única, renderização segura e ajuda estática ao operador. |
| `Integration/Sprint85/FalhasIntegradasTest.php` | Teste — Integração interna | Comprova contenção com continuidade, reconciliação única sem repetição da escrita e interrupção sistêmica com preservação dos resultados conhecidos. |
| `PublicadorPcaScriptcaseTest.php` | Teste — Integração interna | Contratos públicos compatíveis, request ID, ausência de eventos fabricados, localizador externo, seleção literal, perfil e CNPJ em comportamento fail-closed. |

### Suporte compartilhado dos testes

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `Support/NormalizadorSql.php` | Teste — Suporte | Remove diferenças de espaçamento das SQLs capturadas para que os testes verifiquem sua estrutura sem impor apresentação em uma única linha. |

### Infrastructure — Oracle

| Arquivo | Natureza | Demonstração principal |
| --- | --- | --- |
| `Infrastructure/Oracle/Adodb/ConexaoAdodbEmprestadaTest.php` | Teste — Unidade | Binds, leituras, comandos, transação, falhas seguras e ausência de fechamento. |
| `Infrastructure/Oracle/Transacao/MapeamentoControlesConcorrenciaOracleTest.php` | Teste — Unidade | Validação allowlisted do plano físico dos controles de concorrência. |
| `Infrastructure/Oracle/Transacao/OracleUnidadeDeTrabalhoAdapterTest.php` | Teste — Unidade | Provisionamento, chaves, contenção, commit, rollback e ausência de DDL. |
| `Infrastructure/Oracle/Estrategia/MapeamentosFisicosEstrategiaOracleTest.php` | Teste — Unidade | Obrigatoriedade e allowlist dos mapeamentos de configuração e estratégia adotada nos Planos. |
| `Infrastructure/Oracle/Estrategia/OracleEstrategiaPublicacaoAdapterTest.php` | Teste — Unidade | Configuração corrente, UASGs individuais, estratégia adotada, binds, adoção e sessão compartilhada. |
| `Infrastructure/Oracle/Plano/MapeamentosPlanoOracleTest.php` | Teste — Unidade | Obrigatoriedade e allowlist dos mapeamentos físicos de Planos, Demandas e UOs. |
| `Infrastructure/Oracle/Plano/MapeadorDadosPlanoOracleTest.php` | Teste — Unidade | Tradução estrita de aliases, datas, situações e decimais para o Domain. |
| `Infrastructure/Oracle/Plano/OraclePlanoRepositoryTest.php` | Teste — Unidade | Descoberta, reconstrução entre versões, Plano vazio e confirmação orientada à intenção. |
| `Infrastructure/Oracle/EstadoOperacional/MapeamentoEstadoOperacionalOracleTest.php` | Teste — Unidade | Obrigatoriedade e allowlist da projeção operacional em Planos. |
| `Infrastructure/Oracle/EstadoOperacional/OracleEstadoOperacionalPublicacaoAdapterTest.php` | Teste — Unidade | Escopos, versões, conflitos, propagação e releitura na mesma transação. |
| `Infrastructure/Oracle/Contexto/MapeamentosContextoUsuarioOracleTest.php` | Teste — Unidade | Obrigatoriedade e allowlist dos cadastros de Representantes e Lotações. |
| `Infrastructure/Oracle/Contexto/OracleContextoUsuarioAdapterTest.php` | Teste — Unidade | Login, cardinalidade, vigência, dados inválidos e falhas sistêmicas. |
| `Infrastructure/Oracle/Auditoria/MapeamentoHistoricosOracleTest.php` | Teste — Unidade | Obrigatoriedade e allowlist do histórico de procedimentos. |
| `Infrastructure/Oracle/Auditoria/OracleRegistroAuditoriaAdapterTest.php` | Teste — Unidade | Append-only, procedimentos, autoria, transação e sessão compartilhada. |

### Infrastructure — PNCP

| Arquivo | Natureza | Demonstração principal |
| --- | --- | --- |
| `Infrastructure/Pncp/Http/ContratosHttpPncpTest.php` | Teste — Unidade | Ambientes fechados, configuração, requisição, resposta e falhas sanitizadas. |
| `Infrastructure/Pncp/Http/TransporteCurlPncpTest.php` | Teste — Unidade | TLS, hostname, limites, callbacks, uma execução por chamada e ausência de rede nos testes. |
| `Infrastructure/Pncp/Autenticacao/AutenticadorPncpAdapterTest.php` | Teste — Unidade | Autenticação única, sessão opaca, falhas sistêmicas e ausência de segredos nos eventos. |
| `Infrastructure/Pncp/Serializacao/NumeroJsonExatoTest.php` | Teste — Unidade | Gramática e preservação de lexemas numéricos JSON exatos. |
| `Infrastructure/Pncp/Serializacao/CodificadorJsonExatoTest.php` | Teste — Unidade | Emissão determinística, escapes, Unicode e rejeição segura de tipos proibidos. |
| `Infrastructure/Pncp/Serializacao/ConversorDecimalPncpTest.php` | Teste — Unidade | Escala, `HALF_UP`, divisão textual, limites, negativos e overflow. |
| `Infrastructure/Pncp/Serializacao/SerializadorPayloadPncpTest.php` | Teste — Unidade | Projeção integral de itens, ordenação, datas, omissões e limites textuais. |
| `Infrastructure/Pncp/Serializacao/CorposPayloadPncpTest.php` | Teste — Unidade | Corpos integrais dos quatro Commands e ownership do limite operacional. |
| `Infrastructure/Pncp/Leitura/LeitorPncpAdapterTest.php` | Teste — Unidade | Sessão opaca, caminhos, respostas de uma página, identidade e sanitização básica. |
| `Infrastructure/Pncp/Leitura/PaginacaoItensPncpTest.php` | Teste — Unidade | Paginação completa de itens, coerência, duplicidades, limites e ausência de fotografia parcial. |
| `Infrastructure/Pncp/Leitura/PaginacaoCandidatosPncpTest.php` | Teste — Unidade | Listagem completa, filtro por UASG, candidato único com itens e proibição de adoção automática. |
| `Infrastructure/Pncp/Leitura/AusenciaDefinitivaPncpTest.php` | Teste — Unidade | Prova em duas consultas, conflitos de vínculo e falhas que impedem concluir ausência. |
| `Infrastructure/Pncp/Leitura/RetryLeituraPncpTest.php` | Teste — Unidade | Duas tentativas máximas, `Retry-After`, tempo determinístico, classificação e eventos sanitizados. |
| `Infrastructure/Pncp/Escrita/PublicacaoPncpTest.php` | Teste — Unidade | Requisição de publicação, prefixos oficiais de `Location` por ambiente, rejeições estritas e diagnóstico sanitizado. |
| `Infrastructure/Pncp/Escrita/EscritaItensPncpTest.php` | Teste — Unidade | Inclusão e exclusão de itens em coleção integral, sem particionamento ou retry. |
| `Infrastructure/Pncp/Escrita/ExclusaoPcaPncpTest.php` | Teste — Unidade | Exclusão integral, justificativa preservada e status aceito exato. |
| `Infrastructure/Pncp/Escrita/MatrizFalhasEscritaPncpTest.php` | Teste — Unidade | Matriz das quatro escritas, falhas antes e depois do despacho e envelope sanitizado. |

### Infrastructure — Observabilidade

| Arquivo | Natureza | Demonstração principal |
| --- | --- | --- |
| `Infrastructure/Observabilidade/AcumuladorEventosTecnicosTest.php` | Teste — Unidade | Envelope, catálogos de ações, sanitização, ordem, imutabilidade e lote com duração. |
| `Infrastructure/Oracle/Observabilidade/OracleRegistroEventoTecnicoAdapterTest.php` | Teste — Unidade | JSON integral maior que 4.000 bytes, escapes Unicode, mapas vazios, transação por evento e rollback seguro. |
| `RegistradorEventosTecnicosScriptcaseTest.php` | Teste — Unidade | Fachada pública, ordem dos eventos, isolamento das transações e interrupção no primeiro erro. |

### Contratos opt-in — Sprint 5.1

| Arquivo | Natureza | Demonstração principal |
| --- | --- | --- |
| `Contract/Sprint51/SafetyContractTest.php` | Teste — Contrato opt-in | Fail-closed, destino exclusivo de treinamento, JSON exato, opt-in do lote de 1.001 itens, resíduos conhecidos, binds Oracle e ausência de fechamento da conexão. |
| `Contract/Sprint51/PncpTrainingContractTest.php` | Teste — Contrato opt-in | Gate real opt-in do fluxo completo no PNCP de treinamento. |
| `Contract/Sprint51/Support/ExactJsonEncoder.php` | Teste — Suporte contratual | Codifica os tipos permitidos pelo probe preservando números JSON exatos e recusando `float`. |
| `Contract/Sprint51/Support/ExactJsonNumber.php` | Teste — Suporte contratual | Mantém o lexema numérico exato usado exclusivamente pelo probe contratual. |
| `Contract/Sprint51/Support/OracleScriptcaseProbe.php` | Teste — Suporte contratual | Investiga runtime, schema, consultas, transação, binds e contenção em uma conexão ADOdb emprestada. |
| `Contract/Sprint51/Support/PncpContractRunner.php` | Teste — Suporte contratual | Executa autenticação, publicação, observação, alteração, exclusão e limpeza no PNCP de treinamento. |
| `Contract/Sprint51/Support/PncpHttpClient.php` | Teste — Suporte contratual | Cliente cURL exclusivo do probe, restrito ao destino de treinamento e às opções TLS aprovadas. |
| `Contract/Sprint51/Support/PncpHttpResponse.php` | Teste — Suporte contratual | Separa status, headers e corpo e produz resumos sanitizados para o probe. |
| `Contract/Sprint51/Support/Sprint51Config.php` | Teste — Suporte contratual | Carrega a configuração externa e valida os preflights Oracle e PNCP em modo fail-closed. |
| `Contract/Sprint51/scriptcase_probe.php` | Teste — Ferramenta contratual | Expõe somente o entrypoint contratual Oracle da Sprint 5.1 para o Scriptcase. |
| `Contract/Sprint51/run-preflight.php` | Teste — Ferramenta contratual | Executa o preflight conjunto ou específico para Oracle e PNCP. |
| `Contract/Sprint51/config.example.php` | Configuração — Exemplo contratual | Modelo sem segredos da configuração externa da suíte. |

### Contratos opt-in — Sprint 5.9

| Arquivo | Natureza | Demonstração principal |
| --- | --- | --- |
| `Contract/Sprint59/SafetyContractTest.php` | Teste — Contrato opt-in | Fail-closed, destino PNCP canônico, ausência de lote 1.001, mapeamentos Oracle e sanitização de relatórios. |
| `Contract/Sprint59/OracleAdaptersContractRunnerTest.php` | Teste — Contrato opt-in | Compatibilidade da leitura escalar com aliases associativos e índices numéricos duplicados pelo ADOdb. |
| `Contract/Sprint59/OracleAdaptersFailureReportTest.php` | Teste — Contrato opt-in | Diagnóstico opt-in de falhas Oracle por etapa, com mensagem genérica por padrão e causa técnica sanitizada em desenvolvimento. |
| `Contract/Sprint59/PncpAdaptersContractRunnerTest.php` | Teste — Contrato opt-in | Diagnóstico seguro da publicação PNCP, status disponível, encadeamento da falha e resultado da limpeza best-effort. |
| `Contract/Sprint59/PncpAdaptersTrainingContractTest.php` | Teste — Contrato opt-in | Gate real opt-in do ciclo completo no PNCP de treinamento. |
| `Contract/Sprint59/Support/PncpAdaptersContractRunner.php` | Teste — Suporte contratual | Monta e exercita somente transporte, serializer, autenticação, leitura, escrita e eventos de produção. |
| `Contract/Sprint59/Support/PncpDiagnosticTransport.php` | Teste — Suporte contratual | Delega ao transporte PNCP real e retém temporariamente requisição, resposta ou falha para diagnóstico contratual. |
| `Contract/Sprint59/Support/OracleAdaptersContractRunner.php` | Teste — Suporte contratual | Exercita os adapters Oracle reais em uma conexão emprestada e nos quatro modos contratuais. |
| `Contract/Sprint59/Support/OracleAdaptersContractFailure.php` | Teste — Suporte contratual | Preserva a causa original e identifica a etapa segura em que o fluxo `adapters` falhou. |
| `Contract/Sprint59/Support/OracleAdaptersFailureReport.php` | Teste — Suporte contratual | Mantém o erro genérico por padrão e sanitiza a causa técnica habilitada por opt-in. |
| `Contract/Sprint59/Support/BorrowedAdodbGuard.php` | Teste — Suporte contratual | Delega ADOdb e impede que `Close()` alcance a conexão do Scriptcase. |
| `Contract/Sprint59/Support/Sprint59Config.php` | Teste — Suporte contratual | Carrega configuração externa e valida os dois gates antes de I/O. |
| `Contract/Sprint59/Support/SafeContractReport.php` | Teste — Suporte contratual | Codifica e verifica relatórios sanitizados e calcula hashes SHA-256. |
| `Contract/Sprint59/scriptcase_probe.php` | Teste — Ferramenta contratual | Expõe somente o entrypoint contratual de Scriptcase da Sprint 5.9. |
| `Contract/Sprint59/run-preflight.php` | Teste — Ferramenta contratual | Preflight conjunto ou específico para Oracle e PNCP. |
| `Contract/Sprint59/run-pncp-cleanup.php` | Teste — Ferramenta contratual | Remove somente o resíduo PNCP explicitamente autorizado e exige ausência definitiva sem publicar. |
| `Contract/Sprint59/config.example.php` | Configuração — Exemplo contratual | Modelo sem segredos da configuração externa. |

### Contratos opt-in — Sprint 8.2

| Arquivo | Natureza | Demonstração principal |
| --- | --- | --- |
| `Contract/Sprint82/SafetyContractTest.php` | Teste — Contrato opt-in | Assinatura do gate, modos autorizados, sanitização, ausência de segredos e separação da suíte padrão. |
| `Contract/Sprint82/Sprint82ConfigTest.php` | Teste — Contrato opt-in | Configuração externa exata, servidor literal, consentimento de escrita e perfil exclusivo de homologação. |
| `Contract/Sprint82/OracleCompositionContractTest.php` | Teste — Contrato opt-in | Uso da subcomposição Oracle de produção, seis adapters na mesma conexão e montagem do Root sem I/O. |
| `Contract/Sprint82/OracleAdaptersExerciseTest.php` | Teste — Contrato opt-in | Sequência transacional do commit controlado, restauração por rollback e preservação da conexão emprestada. |
| `Contract/Sprint82/OracleContractRunnerTest.php` | Teste — Contrato opt-in | Relatório seguro do exercício sobre a composição Oracle real. |
| `Contract/Sprint82/OracleFailureReportTest.php` | Teste — Contrato opt-in | Diagnóstico opt-in sanitizado e relatório genérico por padrão. |
| `Contract/Sprint82/Support/Sprint82Config.php` | Teste — Suporte contratual | Carrega a configuração própria e o perfil final selecionado pelo registro da implantação. |
| `Contract/Sprint82/Support/OracleContractRunner.php` | Teste — Suporte contratual | Monta o Root sem I/O e executa a mesma subcomposição Oracle nos cinco modos contratuais. |
| `Contract/Sprint82/Support/OracleFailureReport.php` | Teste — Suporte contratual | Produz a falha segura da Sprint 8.2 e limita os detalhes diagnósticos aos modos permitidos. |
| `Contract/Sprint82/scriptcase_probe.php` | Teste — Ferramenta contratual | Expõe somente o entrypoint Oracle da Sprint 8.2 para uma Blank protegida. |
| `Contract/Sprint82/run-preflight.php` | Teste — Ferramenta contratual | Valida configuração, registro e perfil de homologação sem acessar Oracle ou PNCP. |
| `Contract/Sprint82/config.example.php` | Configuração — Exemplo contratual | Modelo sem segredos da configuração específica da massa 8.2. |

### Contratos opt-in — Sprint 8.3

| Arquivo | Natureza | Demonstração principal |
| --- | --- | --- |
| `Contract/Sprint83/Sprint83ConfigTest.php` | Teste — Contrato opt-in | Configuração externa exata, massa dedicada, servidor literal e recusa de produção antes de I/O. |
| `Contract/Sprint83/PncpTrainingTransportTest.php` | Teste — Contrato opt-in | Descarte deliberado de uma única resposta real de publicação depois da delegação. |
| `Contract/Sprint83/PncpTrainingContractRunnerTest.php` | Teste — Contrato opt-in | Ciclos nominal e incerto em memória, autenticação única, paginação, limpeza e sanitização. |
| `Contract/Sprint83/PncpTrainingContractTest.php` | Teste — Contrato opt-in | Grupos opt-in separados para os gates nominal e de escrita incerta no treinamento. |
| `Contract/Sprint83/SafetyContractTest.php` | Teste — Contrato opt-in | Separação dos grupos live e proteção dos relatórios contra dados sensíveis. |
| `Contract/Sprint83/Support/Sprint83Config.php` | Teste — Suporte contratual | Carrega autorização, massa e perfil de treinamento pelos dois arquivos externos. |
| `Contract/Sprint83/Support/PncpTrainingTransport.php` | Teste — Suporte contratual | Envolve o transporte compartilhado, registra chamadas e simula a perda controlada da resposta. |
| `Contract/Sprint83/Support/PncpTrainingContractRunner.php` | Teste — Suporte contratual | Exercita a composição PNCP final, observa efeitos e executa limpeza best-effort. |
| `Contract/Sprint83/run-preflight.php` | Teste — Ferramenta contratual | Valida configuração, registro e perfil sem acessar Oracle ou PNCP. |
| `Contract/Sprint83/run-pncp-cleanup.php` | Teste — Ferramenta contratual | Remove somente o resíduo com sequencial explicitamente autorizado. |
| `Contract/Sprint83/config.example.php` | Configuração — Exemplo contratual | Modelo sem segredos da autorização e massa dedicada da Sprint 8.3. |

### Contratos opt-in — Sprint 8.4

| Arquivo | Natureza | Demonstração principal |
| --- | --- | --- |
| `Contract/Sprint84/Sprint84ConfigTest.php` | Teste — Contrato opt-in | Valida consentimentos separados, etapa, modo, cardinalidades e hash do checkpoint. |
| `Contract/Sprint84/Sprint84GateTest.php` | Teste — Contrato opt-in | Comprova consultas Oracle somente leitura, cardinalidades e snapshot canônico bloqueante. |
| `Contract/Sprint84/Sprint84RunnerTest.php` | Teste — Contrato opt-in | Exercita PRECHECK, execução, pós-check, invariância e propagação operacional permitida. |
| `Contract/Sprint84/Sprint84PncpTransportGuardTest.php` | Teste — Contrato opt-in | Comprova bloqueio de mutações antes do transporte e permissão de login/GET no gate somente leitura. |
| `Contract/Sprint84/OracleCheckpointQueriesTest.php` | Teste — Contrato opt-in | Protege as onze consultas canônicas e os roteiros manuais contra escrita e lock. |
| `Contract/Sprint84/Support/Sprint84Runner.php` | Teste — Suporte contratual | Coordena o checkpoint Oracle, a observação PNCP, o fluxo público e as verificações posteriores. |
| `Contract/Sprint84/Support/OracleCheckpointQueries.php` | Teste — Suporte contratual | Monta as onze consultas parametrizadas a partir do perfil e da configuração autorizados. |
| `Contract/Sprint84/Support/OracleCheckpointSnapshot.php` | Teste — Suporte contratual | Normaliza resultados e calcula o SHA-256 canônico do estado Oracle. |
| `Contract/Sprint84/Support/Sprint84DiagnosticReport.php` | Teste — Suporte contratual | Protege segredos e calcula o hash do relatório detalhado da Blank. |
| `Contract/Sprint84/scriptcase_probe.php` | Teste — Ferramenta contratual | Expõe o entrypoint protegido dos fluxos completos da Sprint 8.4. |
| `Contract/Sprint84/run-preflight.php` | Teste — Ferramenta contratual | Valida localmente configuração, registro e perfil antes de qualquer I/O. |
| `Contract/Sprint84/checkpoints/*.sql` | Teste — Checkpoint SQL | Preserva os roteiros somente leitura usados nas conferências manuais autorizadas. |
| `Contract/Sprint84/blank_scriptcase/teste_adapters_contracts_sprint84.php` | Teste — Probe Scriptcase | Referência da aplicação Blank usada para executar o gate no Scriptcase. |
| `Contract/Sprint84/config.example.php` | Configuração — Exemplo contratual | Modelo sem segredos da configuração externa da Sprint 8.4. |

### Contratos opt-in — Sprint 8.5

| Arquivo | Natureza | Demonstração principal |
| --- | --- | --- |
| `Contract/Sprint85/Sprint85ConfigTest.php` | Teste — Contrato opt-in | Consentimentos por etapa, modo consolidado, hash e preservação da massa conhecida. |
| `Contract/Sprint85/Sprint85PreparationTest.php` | Teste — Contrato opt-in | Doze consultas completas somente leitura, leitura integral do CLOB e recusa antes de I/O. |
| `Contract/Sprint85/Sprint85LogGateTest.php` | Teste — Contrato opt-in | Protege a configuração, as duas consultas independentes da massa e o evento sintético maior que 4.000 bytes. |
| `Contract/Sprint85/Sprint85ContainmentGateTest.php` | Teste — Contrato opt-in | Comprova ordem do lock, resultado bloqueado, preservação do snapshot, acesso PNCP restrito e registro posterior dos eventos. |
| `Contract/Sprint85/Sprint85OracleLockProbeTest.php` | Teste — Contrato opt-in | Distingue a contenção real por `ORA-00054` de uma âncora livre e verifica o rollback da sonda. |
| `Contract/Sprint85/Sprint85ContainmentArtifactsTest.php` | Teste — Contrato opt-in | Protege as doze consultas materializadas, a Blank restrita, a configuração desarmada e o empacotamento próprio. |
| `Contract/Sprint85/Support/Sprint85Config.php` | Teste — Suporte contratual | Carrega a configuração externa exata e restringe perfil, etapa, consentimentos e massa. |
| `Contract/Sprint85/Support/Sprint85OracleQueries.php` | Teste — Suporte contratual | Reutiliza as onze consultas canônicas e acrescenta a consulta identificada dos logs técnicos. |
| `Contract/Sprint85/Support/Sprint85Precheck.php` | Teste — Suporte contratual | Executa o checkpoint somente leitura, compara o hash fixado e impede SQL mutante ou lock. |
| `Contract/Sprint85/Support/Sprint85LogGateConfig.php` | Teste — Suporte contratual | Restringe o gate de logs a PRECHECK ou LOGS no host validaSGI, fixa o charset ADOdb e exige o hash antes da escrita. |
| `Contract/Sprint85/Support/Sprint85LogGateQueries.php` | Teste — Suporte contratual | Define o PRECHECK reduzido da estrutura do log e a releitura integral do CLOB. |
| `Contract/Sprint85/Support/Sprint85SyntheticLog.php` | Teste — Suporte contratual | Produz o envelope identificado, com escapes Unicode, mapas vazios e mais de 4.000 bytes. |
| `Contract/Sprint85/Support/Sprint85LogGate.php` | Teste — Suporte contratual | Coordena PRECHECK, preparação e verificação do evento sem chamar o publicador. |
| `Contract/Sprint85/Support/Sprint85ContainmentGate.php` | Teste — Suporte contratual | Coordena checkpoint, prova do lock externo, fluxo público protegido, comparação posterior e logs técnicos. |
| `Contract/Sprint85/Support/Sprint85OracleLockProbe.php` | Teste — Suporte contratual | Testa a âncora consolidada com `FOR UPDATE NOWAIT` e sempre desfaz sua transação local. |
| `Contract/Sprint85/scriptcase_probe.php` | Teste — Ferramenta contratual | Expõe o PRECHECK geral preparado originalmente para os gates dependentes da massa. |
| `Contract/Sprint85/scriptcase_log_gate_probe.php` | Teste — Artefato contratual | Expõe somente os três entrypoints necessários ao artefato temporário do Microciclo 6. |
| `Contract/Sprint85/scriptcase_containment_probe.php` | Teste — Artefato contratual | Expõe os entrypoints seguros de PRECHECK, contenção e disponibilidade do Microciclo 7. |
| `Contract/Sprint85/run-preflight.php` | Teste — Ferramenta contratual | Valida configuração, perfil e contrato das consultas sem acessar recursos externos. |
| `Contract/Sprint85/render-manual-queries.php` | Teste — Ferramenta contratual | Renderiza SQL, binds, colunas e cardinalidades para conferência manual. |
| `Contract/Sprint85/config.example.php` | Configuração — Exemplo contratual | Modelo sem segredos da configuração externa dos quatro gates. |
| `Contract/Sprint85/log-gate-config.example.php` | Configuração — Exemplo contratual | Modelo sem segredos e independente da massa de PCA para o Microciclo 6 no validaSGI. |
| `Contract/Sprint85/containment-config.example.php` | Configuração — Exemplo contratual | Configuração desarmada e sem segredos da fotografia de homologação usada no Microciclo 7. |
| `Contract/Sprint85/smoke-config.example.php` | Configuração — Exemplo contratual | Configuração desarmada do PRECHECK do smoke, com as três UOs e fotografia vazia. |
| `Contract/Sprint85/checkpoints/smoke_precheck.sql` | Teste — Checkpoint SQL | Materializa as doze consultas somente leitura da massa excluída usada no smoke. |
| `Contract/Sprint85/checkpoints/smoke_postcheck.sql` | Teste — Checkpoint SQL | Confere projeção, confirmações, plano 103, demandas, auditoria, controles e logs após a chamada única do smoke. |
| `Contract/Sprint85/checkpoints/smoke_resposta_incerta_postcheck.sql` | Teste — Checkpoint SQL | Reconstitui por consultas somente leitura o efeito de um smoke cuja resposta não trouxe correlação, incluindo eventos recentes por aplicação e operador. |
| `Contract/Sprint85/checkpoints/logs_precheck.sql` | Teste — Checkpoint SQL | Dois SELECTs materializados para conferir estrutura e ausência do identificador antes do gate. |
| `Contract/Sprint85/checkpoints/logs_postcheck.sql` | Teste — Checkpoint SQL | Releitura manual integral do CLOB depois da chamada única da macro. |
| `Contract/Sprint85/checkpoints/logs_diagnostico_persistencia.sql` | Teste — Checkpoint SQL | Consultas somente leitura para investigar um pós-check vazio sem repetir a macro. |
| `Contract/Sprint85/blank_scriptcase/teste_contracts_sprint85.php` | Teste — Probe Scriptcase | Blank protegida que libera somente PRECHECK e LOGS, sem publicador ou acesso PNCP. |
| `Contract/Sprint85/checkpoints/contencao_precheck.sql` | Teste — Checkpoint SQL | Doze SELECTs concretos, com colunas e cardinalidades, para a fotografia manual anterior ao gate de contenção. |
| `Contract/Sprint85/checkpoints/contencao_reter_ancora.sql` | Teste — Checkpoint SQL | Comando operacional da sessão A para reter a âncora e liberá-la por rollback depois da chamada. |
| `Contract/Sprint85/blank_scriptcase/teste_contencao_sprint85.php` | Teste — Probe Scriptcase | Blank separada que limita o Microciclo 7 a PRECHECK, CONTENCAO e DISPONIBILIDADE. |

### Massa descartável de homologação — Sprint 8.5

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `database/seeds/sprint85_contencao_hml/README.md` | Documentação — Massa de homologação | Define a preparação em etapas e impede que o seed de HML seja aplicado sobre dados replicados ou em produção. |
| `database/seeds/sprint85_contencao_hml/01_diagnostico_pre_seed.sql` | Operacional — Seed de homologação | Diagnóstico somente leitura do ambiente, UOs candidatas, colisões, estrutura e dependências necessárias para gerar o seed de contenção. |
| `database/seeds/sprint85_contencao_hml/02_precheck_seed.sql` | Operacional — Seed de homologação | Confere ambiente, cardinalidades, projeção vazia e ausência de controles antes do DML. |
| `database/seeds/sprint85_contencao_hml/03_aplicar_seed.sql` | Operacional — Seed de homologação | Prepara de forma bloqueante a estratégia consolidada dos 112 planos e os dois controles técnicos. |
| `database/seeds/sprint85_contencao_hml/04_postcheck_seed.sql` | Operacional — Seed de homologação | Confirma a projeção, os controles e a preservação da massa replicada. |
| `database/seeds/sprint85_contencao_hml/05_limpar_seed.sql` | Operacional — Seed de homologação | Reverte somente o estado exato criado pelo seed e recusa qualquer fotografia divergente. |
| `database/seeds/sprint85_contencao_hml/06_postcheck_limpeza.sql` | Operacional — Seed de homologação | Confirma por seis SELECTs que a preparação da contenção foi removida e a massa replicada foi preservada. |
| `database/seeds/sprint85_smoke_hml/README.md` | Documentação — Massa de homologação | Explica a preparação reversível da fotografia vazia com PCA excluído para o smoke. |
| `database/seeds/sprint85_smoke_hml/01_precheck_seed.sql` | Operacional — Seed de homologação | Confere planos, situações originais das demandas, versões vigentes, controles, auditoria e plano 103 antes do seed. |
| `database/seeds/sprint85_smoke_hml/02_aplicar_seed.sql` | Operacional — Seed de homologação | Marca as 112 demandas como canceladas e projeta os 112 planos como `EXCLUIDO/36`, somente em SGIHML. |
| `database/seeds/sprint85_smoke_hml/03_postcheck_seed.sql` | Operacional — Seed de homologação | Confirma fotografia vazia, projeção excluída, controles e preservação do plano 103. |
| `database/seeds/sprint85_smoke_hml/04_limpar_seed.sql` | Operacional — Seed de homologação | Restaura situações, confirmações e projeção anteriores, recusando qualquer divergência. |

### Suporte contratual compartilhado

| Arquivo | Natureza | Demonstração principal |
| --- | --- | --- |
| `Contract/Support/OracleAdaptersExercise.php` | Teste — Suporte contratual | Reúne o exercício transacional compartilhado pelas Sprints 5.9 e 8.2 sem alterar seus entrypoints. |
| `Contract/Support/OracleAdaptersSet.php` | Teste — Suporte contratual | Transporta explicitamente os seis adapters Oracle da subcomposição. |
| `Contract/Support/BorrowedAdodbGuard.php` | Teste — Suporte contratual | Registra operações ADOdb e impede `Close()` sobre a conexão emprestada. |
| `Contract/Support/OracleContractFailure.php` | Teste — Suporte contratual | Preserva internamente a causa e identifica a etapa segura da falha. |
| `Contract/Support/OracleTechnicalMessageSanitizer.php` | Teste — Suporte contratual | Remove valores sensíveis conhecidos da mensagem diagnóstica opt-in. |
| `Contract/Support/SafeContractReport.php` | Teste — Suporte contratual | Valida, serializa e calcula o hash de relatórios sanitizados. |

## 8. Configuração executável

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `.editorconfig` | Configuração — Projeto | Orienta editores compatíveis a usar o charset UTF-8, finais de linha LF, indentação com quatro espaços e quebra final nos arquivos do repositório. |
| `.gitattributes` | Configuração — Projeto | Normaliza arquivos de texto com finais de linha LF e identifica formatos binários para impedir conversão de conteúdo e diffs textuais inadequados. |
| `.gitignore` | Configuração — Projeto | Exclui do versionamento dependências, configurações locais, segredos, caches, relatórios e artefatos gerados, preservando somente as exceções intencionalmente rastreadas. |
| `composer.json` | Configuração — Dependências e automação | Dependências, PSR-4 e comandos ativos. |
| `composer.lock` | Configuração — Lock de dependências | Versões resolvidas das dependências. |
| `build/README.md` | Documentação — Guia técnico | Explica a finalidade da pasta de artefatos gerados, seu conteúdo comum e os limites da limpeza local. |
| `tools/README.md` | Documentação — Guia técnico | Distingue ferramentas versionadas de resultados gerados, documenta o fluxo normal de release e limita a repetição de gates encerrados. |
| `tests/README.md` | Documentação — Guia técnico | Orienta a suíte padrão, contratos opt-in e cuidados antes de executar qualquer gate com recurso real. |
| `tools/package/build.php` | Automação — Empacotamento | Constrói em `build/` o pacote isolado com código, dependências de produção e manifesto de integridade. |
| `tools/package/verify.php` | Automação — Empacotamento | Verifica hashes, topologia, conteúdo permitido e autoload do pacote gerado. |
| `tools/package/archive.php` | Automação — Empacotamento | Exige commit limpo, compacta o pacote validado com data de geração e commit no nome, confere sua topologia e calcula o SHA-256. |
| `tools/package/build-log-gate.php` | Automação — Empacotamento | Monta o artefato temporário do gate de logs a partir do pacote principal já validado e de uma lista fechada de arquivos da probe. |
| `tools/package/verify-log-gate.php` | Automação — Empacotamento | Confere o manifesto, a topologia e os entrypoints carregáveis do artefato temporário. |
| `tools/package/build-containment-gate.php` | Automação — Empacotamento | Monta o artefato temporário do gate de contenção a partir do pacote principal validado e de uma lista fechada de suporte. |
| `tools/package/verify-containment-gate.php` | Automação — Empacotamento | Confere manifesto, topologia, hashes e entrypoints do artefato de contenção. |
| `phpunit.xml` | Configuração — Projeto | Configuração unificada do PHPUnit: mantém `PCA` como suíte padrão segura e reúne suítes contratuais opt-in, selecionadas pelos comandos Composer. |
| `README.md` | Documentação — Introdução | Entrada cognitiva que explica o que o projeto faz, por que ele é útil, como começar e onde obter ajuda. |

### Migrações do banco de dados

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `database/README.md` | Documentação — Banco de dados | Define a organização e o procedimento manual provisório das migrações Oracle. |
| `database/migrations/V202609181815__configuracao_publicacao_pca.sql` | Operacional — Migration | Insere os dois dados de referência da publicação consolidada, aceita estado já conforme e recusa divergência com rollback. |
| `database/checks/V202609181815__configuracao_publicacao_pca_precheck.sql` | Operacional — Check SQL | Classifica cada configuração como ausente, conforme ou divergente antes da migração. |
| `database/checks/V202609181815__configuracao_publicacao_pca_postcheck.sql` | Operacional — Check SQL | Exige duas configurações conformes depois da migração. |
| `database/migrations/V202609181816__unidades_orcamentarias_pca.sql` | Operacional — Migration | Insere quatro unidades orçamentárias, aceita estado já conforme e recusa conflitos de identidade, UASG ou lotação. |
| `database/checks/V202609181816__unidades_orcamentarias_pca_precheck.sql` | Operacional — Check SQL | Classifica as quatro unidades como ausentes, conformes, divergentes ou sem lotação correspondente. |
| `database/checks/V202609181816__unidades_orcamentarias_pca_postcheck.sql` | Operacional — Check SQL | Exige quatro unidades e lotações conformes depois da migração. |
| `database/migrations/V202609241700__procedimentos_publicacao_pca.sql` | Operacional — Migration | Assegura os procedimentos 8 e 9 da auditoria de negócio, recusando colisões de identificador ou nome. |
| `database/checks/V202609241700__procedimentos_publicacao_pca_precheck.sql` | Operacional — Check SQL | Classifica os procedimentos 8 e 9 como ausentes, conformes ou divergentes antes da migration. |
| `database/checks/V202609241700__procedimentos_publicacao_pca_postcheck.sql` | Operacional — Check SQL | Exige os procedimentos 8 e 9 com nomes exatos depois da migration. |
| `database/migrations/V202609241701__estrategia_individual_publicacao_pca.sql` | Operacional — Migration | Registra a transição da estratégia consolidada original para `INDIVIDUAL`, preservando a baseline aplicada. |
| `database/checks/V202609241701__estrategia_individual_publicacao_pca_precheck.sql` | Operacional — Check SQL | Distingue estratégia já conforme, transição necessária, ausência e divergência. |
| `database/checks/V202609241701__estrategia_individual_publicacao_pca_postcheck.sql` | Operacional — Check SQL | Exige a configuração final da estratégia individual com descrição atualizada. |
| `database/queries/seguranca_log_por_correlacao.sql` | Operacional — Consulta SQL | Localiza por bind os eventos técnicos de uma correlação e apresenta campos operacionais sanitizados. |
| `database/seeds/README.md` | Documentação — Seeds | Delimita seeds descartáveis de DEV/HML e impede seu uso para configuração obrigatória, produção ou segredos. |
| `database/deployments/README.md` | Documentação — Implantação | Define o conteúdo seguro do registro manual provisório de implantações. |
| `database/deployments/homologacao.md` | Operacional — Registro de implantação | Registra migrações aplicadas ou requisitos já satisfeitos no banco de homologação. |
| `database/deployments/producao.md` | Operacional — Registro de implantação | Registra migrations aplicadas ou requisitos já satisfeitos no banco de produção. |

## 9. Documentação vigente

| Arquivo | Natureza | Função |
| --- | --- | --- |
| `docs/README.md` | Documentação — Governança | Governa classificação, autoridade, navegação e caminhos canônicos da documentação mantida em `docs/`. |
| `docs/vigentes/autoritativos/regras_de_negocio.md` | Documentação — Autoritativa | Define as regras de negócio vigentes identificadas por `RN-*`. |
| `docs/vigentes/autoritativos/modelo_de_dominio.md` | Documentação — Autoritativa | Define conceitos, identidades, relações e invariantes do modelo de domínio. |
| `docs/vigentes/autoritativos/decisoes_de_integracao.md` | Documentação — Autoritativa | Registra as decisões de integração vigentes e seus gates. |
| `docs/vigentes/autoritativos/especificacao_de_logs_estruturados.md` | Documentação — Autoritativa | Consolida o envelope institucional, o significado dos campos e a proteção de dados dos eventos técnicos. |
| `docs/vigentes/autoritativos/fluxo_de_implementacao.md` | Documentação — Autoritativa | Descreve a colaboração vigente entre camadas, casos de uso e Ports. |
| `docs/vigentes/autoritativos/roadmap.md` | Documentação — Autoritativa | Organiza a ordem de implementação, dependências e condições de avanço. |
| `docs/vigentes/apoio/arquitetura.md` | Documentação — Apoio | Explica limites, responsabilidades, direção das dependências e fronteiras entre configuração, segredos e mapeamentos físicos. |
| `docs/vigentes/apoio/glossario.md` | Documentação — Apoio | Mantém o índice semântico da linguagem de negócio, integração e arquitetura. |
| `docs/vigentes/apoio/contexto_funcional_do_sistema_pca.md` | Documentação — Apoio | Contextualiza usuários, etapas e funcionamento do sistema PCA. |
| `docs/vigentes/apoio/guia_de_leitura_do_codigo.md` | Documentação — Apoio | Orienta a leitura do código, do fluxo e das fronteiras transacionais. |
| `docs/vigentes/apoio/catalogo_de_diretorios.md` | Documentação — Apoio | Explica a finalidade das pastas e orienta onde localizar ou criar cada tipo de artefato. |
| `docs/vigentes/apoio/catalogo_de_comandos_composer.md` | Documentação — Apoio | Classifica os scripts Composer e informa finalidade, dependências, efeitos externos e autorização necessária. |
| `docs/vigentes/apoio/catalogo_de_arquivos.md` | Documentação — Apoio | Cataloga a função dos arquivos ativos de código, testes, configuração e documentação vigente. |
| `docs/vigentes/operacional/situacao_operacional.md` | Documentação — Operacional | Resume a condição do produto instalado, a restrição corrente e as ações seguras para manutenção e suporte. |
| `docs/vigentes/operacional/guia_operacional_de_diagnostico_da_publicacao_pca.md` | Documentação — Operacional | Orienta a interpretação das respostas, a correlação dos eventos e a conduta segura de suporte. |
| `docs/vigentes/operacional/guia_de_configuracao_de_producao.md` | Documentação — Operacional | Orienta o provisionamento externo do registro, perfil e CA bundle de produção, inclusive permissões e configuração persistente no Apache/PHP. |
| `docs/vigentes/operacional/guia_de_implantacao_e_rollback.md` | Documentação — Operacional | Orienta construção, transferência SFTP, implantação inicial, atualização e rollback da release autocontida. |
| `docs/vigentes/apoio/diagramas/DER_PUBLICACAO_PCA.png` | Documentação — Diagrama | Apoia visualmente a compreensão do modelo de dados da publicação do PCA. |
| `docs/vigentes/apoio/diagramas/fluxograma_PCA.png` | Documentação — Diagrama | Apoia visualmente a compreensão do fluxo funcional do PCA. |
| `docs/vigentes/apoio/diagramas/visao_executiva_projeto_pca_pncp.md` | Documentação — Diagrama | Explica visualmente o problema, a convergência e os principais controles do sistema. |
| `.agents/planejamento/estado_atual.md` | Documentação — Planejamento | Centraliza progresso da implementação, gates, pendências e continuidade do trabalho para agentes. |

Para entender o caminho entre esses arquivos, volte ao
[guia de leitura](guia_de_leitura_do_codigo.md).
