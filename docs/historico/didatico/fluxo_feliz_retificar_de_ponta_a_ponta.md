# Mapa sequencial de chamadas — fluxo feliz `RETIFICAR`

**Classificação documental:** Histórico

**Autoridade/Escopo:** material didático sem autoridade normativa

**Fotografia da implementação:** 20/10/2026

Este material acompanha uma execução concreta da publicação desde a Blank do
Scriptcase até a resposta JSON devolvida por ela. O cenário foi escolhido para
mostrar o caminho feliz da decisão `RETIFICAR`, incluindo a nova observação que
comprova a convergência.

> Este documento explica uma fotografia do código e pode ficar desatualizado.
> Para orientar manutenção, deve ser confrontado com a documentação vigente e
> com a implementação atual.

## Cenário acompanhado

- a requisição HTTP é válida e possui um `CorrelationID` válido;
- o perfil externo e o autoload estão disponíveis;
- a estratégia de publicação é individual e existe uma UO a processar;
- o estado operacional aponta para um PCA ativo com sequencial conhecido;
- a fotografia observada possui diferenças em relação à fotografia desejada;
- há pelo menos um item a excluir e um item a incluir;
- as duas escritas são aceitas pelo PNCP;
- a primeira observação posterior às escritas encontra a fotografia desejada;
- persistência local, auditoria, commit e registro técnico são concluídos.

Não aparecem caminhos alternativos de bloqueio, falha, rollback ou
reconciliação adicional. Paginação, SQLs internas e chamadas repetitivas de
observabilidade também foram resumidas para preservar a leitura do fluxo
principal.

## Como ler o mapa

```text
→          chamada ou delegação
↳          implementação concreta usada para atender um contrato
[cria]     criação de um objeto
[extrai]   obtenção de um dado contido no objeto recebido
←          retorno da chamada
#          explicação didática ou significado da etapa
```

A **descida** acompanha as chamadas `→`: a Blank delega a execução até chegar
às operações internas e às integrações. A **subida** acompanha os retornos `←`:
os resultados tipados são transformados até voltarem à Blank. Esses termos não
indicam herança nem a direção arquitetural das dependências.

Uma Port é o contrato pelo qual a camada interna faz a chamada. Ela não recebe
a operação para depois chamar o Adapter. O Adapter injetado já é o objeto que
atende à chamada; por isso ele aparece abaixo da Port com `↳`.

## Fluxo completo

```text
PCA_PUBLICAR_B/onExecute.php    |   Blank do Scriptcase que atua como Hospedeiro — Front Controller

  # 1. Requisição e bootstrap
  → valida POST, Content-Type e o campo ano do JSON recebido
  → require_once vendor/autoload.php
  [extrai] conexão ADOdb, login, SERVER_NAME, ano e CorrelationID

  → PublicadorPcaScriptcase::executarComEventos(...)    |   Fronteira pública — Fachada pública
    → ExecutorPublicacaoPcaScriptcase::executarComEventos(...)    |   Composição — Executor de fronteira
      → ExecutorPublicacaoPcaScriptcase::executarInternamente(..., true)
        [cria] CorrelacaoExecucao a partir do CorrelationID

        # 2. Perfil externo e composição do grafo (montagem do sistema)
        → CarregadorPerfilPublicacaoPca::carregar(SERVER_NAME)
        ← perfil externo selecionado pela allowlist

        [cria] Cnpj a partir do perfil
        [cria] EntradaPublicarPca com CNPJ e ano

        → CompositionRootPublicacaoPca::montarExecucao(...)   |   Composição — Composition Root
          [cria] PerfilPublicacaoPca

          [cria] ConexaoAdodbEmprestada

          [cria] ComposicaoOraclePublicacaoPca
            [cria] OracleContextoUsuarioAdapter
            [cria] OraclePlanoRepository
            [cria] OracleEstrategiaPublicacaoAdapter
            [cria] OracleUnidadeDeTrabalhoAdapter
            [cria] OracleEstadoOperacionalPublicacaoAdapter
            [cria] OracleRegistroAuditoriaAdapter

          [cria] RelogioSistemaEventosTecnicos
          [cria] AcumuladorEventosTecnicos
                  recebe RelogioSistemaEventosTecnicos

          [cria] ExecutorCurlNativoPncp
          [cria] TransporteCurlPncp
                  recebe ExecutorCurlNativoPncp
                  recebe AcumuladorEventosTecnicos

          [cria] TemporizadorSistemaPncp
          [cria] ComposicaoPncpPublicacaoPca
                  recebe TemporizadorSistemaPncp
            [cria] AutenticadorPncpAdapter
            [cria] LeitorPncpAdapter
            [cria] SerializadorPayloadPncp
            [cria] EscritorPncpAdapter
                    recebe SerializadorPayloadPncp

          [cria] MontadorNucleoPublicacaoPca
                  recebe os Adapters Oracle
                  recebe os Adapters PNCP
                  recebe o logger AcumuladorEventosTecnicos

          # Antes do Controller ser construído, seus 2 argumentos precisam existir: Presenter e Use case
          [cria] PublicarPcaPresenter

          # A montagem do núcleo constrói o subgrafo da Application e, por fim, o Use Case
          → MontadorNucleoPublicacaoPca::montar()
            [cria] PoliticaProcessamentoPlano
            [cria] ExecutorComandosUnidade
            [cria] FinalizadorProcessamentoUnidadeInterno
                    recebe a Política
                    recebe o ExecutorComandosUnidade

            # Antes do ProcessadorUnidadeInterno poder ser construído, seus 3 colaboradores precisam existir: Preparador, Decisor e Despachador
            [cria] PreparadorUnidadeSobExclusividade
            [cria] DecisorUnidadeSobExclusividade
            [cria] DespachadorComandosUnidade
            [cria] ProcessadorUnidadeInterno
                  recebe Preparador, Decisor e Despachador

            # Com o Processador pronto, podem ser criados os dois colaboradores diretos do Use Case:
            [cria] PreparadorExecucaoPublicacaoPca
            [cria] CoordenadorVarreduraUnidades
            [cria] PublicarPcaUseCase
                  recebe Preparador e Coordenador
          ← PublicarPcaUseCase

          [cria] PublicarPcaController
                  recebe PublicarPcaUseCase como tipo PublicadorPca
                  recebe PublicarPcaPresenter

          [cria] FachadaPublicarPca
                  recebe PublicarPcaController
                  recebe o mesmo PublicarPcaPresenter
                  recebe AcumuladorEventosTecnicos como LoggerInterface

          [cria] ComposicaoExecucaoPublicacaoPca
                  recebe FachadaPublicarPca
                  recebe AcumuladorEventosTecnicos
                  recebe ConexaoAdodbEmprestada
        ← ComposicaoExecucaoPublicacaoPca

        → ComposicaoExecucaoPublicacaoPca::getFachada()
        ← FachadaPublicarPca

        # 3. Presentation e preparação global da Application
        → FachadaPublicarPca::(EntradaPublicarPca, CorrelacaoExecucao)
          → PublicarPcaController::executar(...)

            # A entrada da fachada transporta valores simples; o Controller os transforma novamente em objetos validados da Application/Domain.
            [cria] Cnpj a partir do valor recebido
            [cria] AnoExecucao a partir do valor recebido
            [cria] PublicarPcaRequest
                  recebe Cnpj e AnoExecucao

            → PublicarPcaUseCase::executar(...)
              # O Preparador reúne os recursos compartilhados por toda a varredura.
              → PreparadorExecucaoPublicacaoPca::preparar(PublicarPcaRequest)

                → ContextoUsuarioPort::resolver()
                  ↳ OracleContextoUsuarioAdapter
                    → consulta Representante e Lotação no Oracle
                  ← ResultadoResolucaoContextoUsuario::resolvido(...)
                [extrai] ContextoUsuario

                → PlanoRepository::listarIdentidades(AnoExecucao)
                  ↳ OraclePlanoRepository
                    → consulta as identidades participantes do exercício
                  ← IdentidadePlano[]

                # Uma transação curta congela a estratégia anual antes do PNCP
                [cria] ChaveUnidadeTrabalho::estrategiaAnual(...)
                → UnidadeDeTrabalhoPort::iniciar(chave anual)
                  ↳ OracleUnidadeDeTrabalhoAdapter
                    → inicia a transação e protege a estratégia do exercício
                  ← ResultadoInicioUnidadeTrabalho::iniciada()

                → EstrategiaPublicacaoPort::consultarCorrente(AnoExecucao)
                  ↳ OracleEstrategiaPublicacaoAdapter
                  ← ResultadoConsultaEstrategia::disponivel(EstrategiaPublicacao::individual())

                [extrai] EstrategiaPublicacao corrente

                # Confirma exercício, unicidade das UOs e, no modo individual, que UOs diferentes não compartilham a mesma UASG.
                → PreparadorExecucaoPublicacaoPca::validarIdentidadesParaEstrategia(...)
                ← null: identidades compatíveis com a estratégia

                → EstrategiaPublicacaoPort::consultarAdotada(AnoExecucao)
                  ↳ OracleEstrategiaPublicacaoAdapter
                  ← ResultadoConsultaEstrategia::disponivel(estratégia individual já adotada)

                # Neste cenário, a estratégia adotada é igual à corrente. adotar() confirma essa situação de forma idempotente.
                → EstrategiaPublicacaoPort::adotar(AnoExecucao, estratégia)
                  ↳ OracleEstrategiaPublicacaoAdapter
                  ← ResultadoAdocaoEstrategia::adotada()

                → UnidadeDeTrabalhoPort::confirmar()
                  ↳ OracleUnidadeDeTrabalhoAdapter
                    → commit da transação da estratégia
                  ← ResultadoFinalizacaoUnidadeTrabalho::concluida()

                [cria] UnidadeExternaPublicacao::individual(IdentidadePlano)

                → AutenticadorPncpPort::autenticar()
                  ↳ AutenticadorPncpAdapter
                    [cria] RequisicaoHttpPncp para login
                    → TransporteHttpPncp::enviar(requisição)
                      ↳ TransporteCurlPncp
                        → ExecutorCurlPncp::executar(URL, opções)
                          ↳ ExecutorCurlNativoPncp
                            → curl_exec()
                          ← ResultadoExecucaoCurlPncp
                        [cria] RespostaHttpPncp validada
                      ← RespostaHttpPncp
                      [extrai] token Bearer do header Authorization
                    [cria] SessaoPncpAutenticada com token opaco
                  ← ResultadoAutenticacaoPncp::autenticada(SessaoPncpAutenticada)

                [extrai] SessaoPncpAutenticada

                [cria] ResultadoPreparacaoPublicacaoPca::preparada(...)
              ← contexto do usuário, estratégia, UO externa e sessão PNCP

              → CoordenadorVarreduraUnidades::processar(...)
                → ProcessadorUnidadeInterno::processar(UO individual)

                  # 4. UO sob exclusividade e fotografia local vigente
                  → PreparadorUnidadeSobExclusividade::preparar(UO)
                    [cria] ChaveUnidadeTrabalho::unidadeExterna(UO)

                    → UnidadeDeTrabalhoPort::iniciar(chave da UO)
                      ↳ OracleUnidadeDeTrabalhoAdapter
                        → inicia a transação da unidade
                        → adquire exclusividade com FOR UPDATE NOWAIT
                      ← ResultadoInicioUnidadeTrabalho::iniciada()

                    → PlanoRepository::listarIdentidades(AnoExecucao)
                      ↳ OraclePlanoRepository
                        → relê as identidades dentro da exclusividade
                      ← IdentidadePlano[]

                    → PlanoRepository::reconstruirFotografiaVigente(identidade)
                      ↳ OraclePlanoRepository
                        → reconstrói o Plano e suas Demandas homologadas vigentes
                      ← Plano

                    → EstadoOperacionalPublicacaoPort::consultar(UO)
                      ↳ OracleEstadoOperacionalPublicacaoAdapter
                        → consulta a referência operacional local
                      ← ResultadoConsultaEstadoOperacional::disponivel(...)

                    [cria] ContextoUnidadeSobExclusividade
                  ← ContextoUnidadeSobExclusividade

                  # 5. Observação inicial e decisão RETIFICAR
                  → DecisorUnidadeSobExclusividade::decidir(...)
                    → LeitorPncpPort::observarPcaConhecido(sessão, CNPJ, UO, sequencial)
                      ↳ LeitorPncpAdapter
                        [cria] requisições HTTP para o consolidado e seus itens
                        → TransporteHttpPncp::enviar(requisições de leitura)
                          ↳ TransporteCurlPncp
                            → ExecutorCurlPncp::executar(URL, opções)
                              ↳ ExecutorCurlNativoPncp
                                → curl_exec()
                              ← ResultadoExecucaoCurlPncp
                            [cria] RespostaHttpPncp validada a cada envio
                          ← respostas do consolidado e dos itens
                        [cria] ResultadoObservacaoPca::encontrado(...)
                      ← ResultadoObservacaoPca

                    [extrai] PcaObservado com a fotografia inicial

                    → PoliticaProcessamentoPlano::decidir(Planos, estado local, PcaObservado)
                    ← ResultadoDecisaoProcessamentoPlano::retificar(inclusões, exclusões)

                    [cria] ContextoUnidadeComDecisao
                  ← ContextoUnidadeComDecisao com decisão RETIFICAR

                  # Os eventos técnicos são acumulados em memória durante o fluxo.
                  # As chamadas repetitivas a LoggerInterface foram omitidas.

                  → DespachadorComandosUnidade::despachar(...)
                    → ExecutorComandosUnidade::executar(contexto)

                      # 6. RETIFICAR preserva a ordem segura: excluir antes de incluir
                      [cria] ComandoExcluirItensPca
                      → EscritorPncpPort::excluirItens(sessão, comando)
                        ↳ EscritorPncpAdapter
                          → SerializadorPayloadPncp::serializarExclusaoItens(comando)
                          ← JSON exato da exclusão
                          [cria] RequisicaoHttpPncp DELETE
                          → TransporteHttpPncp::enviar(requisição)
                            ↳ TransporteCurlPncp
                              → ExecutorCurlPncp::executar(URL, opções)
                                ↳ ExecutorCurlNativoPncp
                                  → curl_exec()
                                ← ResultadoExecucaoCurlPncp
                              [cria] RespostaHttpPncp validada
                            ← RespostaHttpPncp com status aceito
                          [cria] ResultadoEscritaPncp::aceita()
                        ← ResultadoEscritaPncp ACEITA

                      # A inclusão só é tentada porque a exclusão foi aceita
                      [cria] ComandoInserirItensPca
                      → EscritorPncpPort::inserirItens(sessão, comando)
                        ↳ EscritorPncpAdapter
                          → SerializadorPayloadPncp::serializarInclusaoItens(comando)
                          ← JSON exato da inclusão
                          [cria] RequisicaoHttpPncp POST
                          → TransporteHttpPncp::enviar(requisição)
                            ↳ TransporteCurlPncp
                              → ExecutorCurlPncp::executar(URL, opções)
                                ↳ ExecutorCurlNativoPncp
                                  → curl_exec()
                                ← ResultadoExecucaoCurlPncp
                              [cria] RespostaHttpPncp validada
                            ← RespostaHttpPncp com status aceito
                          [cria] ResultadoEscritaPncp::aceita()
                        ← ResultadoEscritaPncp ACEITA

                    ← resultados das duas escritas aceitas

                    [cria] ContextoFinalizacaoProcessamentoUnidade
                    → FinalizadorProcessamentoUnidadeInterno::finalizar(contexto)

                      # 7. Uma resposta aceita não comprova, sozinha, a convergência
                      → LeitorPncpPort::observarPcaConhecido(sessão, CNPJ, UO, sequencial)
                        ↳ LeitorPncpAdapter
                          [cria] novas requisições HTTP para o consolidado e seus itens
                          → TransporteHttpPncp::enviar(requisições de leitura)
                            ↳ TransporteCurlPncp
                              → ExecutorCurlPncp::executar(URL, opções)
                                ↳ ExecutorCurlNativoPncp
                                  → curl_exec()
                                ← ResultadoExecucaoCurlPncp
                              [cria] RespostaHttpPncp validada a cada envio
                            ← respostas do consolidado e dos itens
                          [cria] ResultadoObservacaoPca::encontrado(...)
                        ← ResultadoObservacaoPca

                      [extrai] PcaObservado com a fotografia final

                      → PoliticaProcessamentoPlano::decidir(Planos, estado local, PcaObservado)
                      ← ResultadoDecisaoProcessamentoPlano::nenhumaAcao()

                      # NENHUMA_ACAO é a prova de que não restou diferença.
                      # A operação concluída continua sendo RETIFICAR.

                      # 8. Persistência local atômica depois da convergência
                      [cria] EstadoOperacionalPublicacao::publicado(sequencial)
                      → EstadoOperacionalPublicacaoPort::registrar(UO, estado final)
                        ↳ OracleEstadoOperacionalPublicacaoAdapter
                          → registra e relê a projeção operacional PUBLICADO
                        ← ResultadoPersistenciaLocal::concluida()

                      → PlanoRepository::confirmarSincronizacao(Plano)
                        ↳ OraclePlanoRepository
                          → confirma a versão vigente ainda pendente
                        ← void, com a confirmação persistida

                      [cria] IntencaoRegistroHistorico da RETIFICAÇÃO
                      → RegistroAuditoriaPort::registrar(intenção)
                        ↳ OracleRegistroAuditoriaAdapter
                          → acrescenta o histórico de negócio no Oracle
                        ← ResultadoPersistenciaLocal::concluida()

                      → UnidadeDeTrabalhoPort::confirmar()
                        ↳ OracleUnidadeDeTrabalhoAdapter
                          → commit da transação da UO
                        ← ResultadoFinalizacaoUnidadeTrabalho::concluida()

                      # Somente depois do commit os itens são registrados como confirmados
                      → LoggerInterface::info(itens confirmados)
                        ↳ AcumuladorEventosTecnicos
                          → acumula o evento técnico em memória

                      → LoggerInterface::info(unidade finalizada)
                        ↳ AcumuladorEventosTecnicos
                          → acumula o evento técnico em memória

                      [cria] ResultadoProcessamentoUnidade::concluido(...)
                              decisão apresentada: RETIFICAR
                              situação: CONCLUIDO
                              itens incluídos e excluídos preservados
                      [cria] DesfechoProcessamentoUnidade::resultadoUnitario(...)

                    # 9. Subida dos Results até o ViewModel
                    # O Processador devolve ao Coordenador o desfecho produzido pelo Finalizador.
                    ← DesfechoProcessamentoUnidade        (Finalizador → Despachador)
                  ← o mesmo DesfechoProcessamentoUnidade  (Despachador → Processador)
                ← o mesmo DesfechoProcessamentoUnidade    (Processador → Coordenador)
              → DesfechoProcessamentoUnidade::getResultadoUnitario()
              ← ResultadoProcessamentoUnidade
                    situação: CONCLUIDO
                    decisão apresentada: RETIFICAR
              [cria] ResultadoVarreduraUnidades::concluida(
                ResultadoProcessamentoUnidade[]
              )
            ← ResultadoVarreduraUnidades

            [extrai] resultados unitários
            [extrai] quantidade de unidades visitadas

            [cria] PublicarPcaResponse::concluida(
              resultados unitários,
              quantidade planejada,
              quantidade visitada,
              correlação
            )
          ← PublicarPcaResponse

          # O Controller entrega a Response da Application ao Presenter.
          → PublicarPcaPresenter::apresentar(PublicarPcaResponse)

            # Cada resultado rico da Application é convertido para dados de apresentação.
            [cria] ResultadoUnidadeViewModel
                    situação: CONCLUIDO
                    decisão: RETIFICAR
                    sequencial e itens confirmados

            [cria] PublicarPcaViewModel
                    situação global: CONCLUIDA
                    resultados unitários seguros
                    contagens globais
                    correlação

          ← PublicarPcaViewModel          (Presenter → Controller)
        ← o mesmo PublicarPcaViewModel    (Controller → FachadaPublicarPca)
      ← o mesmo PublicarPcaViewModel      (FachadaPublicarPca → ExecutorPublicacaoPcaScriptcase)

      # O mesmo ViewModel é seguro para apresentação porque expõe somente
      # os dados previstos pelo contrato, sem exceções ou detalhes internos.
      [guarda] $resultado: PublicarPcaViewModel

        # 10. Eventos são exportados somente após o encerramento transacional
        → ComposicaoExecucaoPublicacaoPca::exportar(PublicarPcaViewModel)
          → ConexaoAdodbEmprestada::estaEmTransacao()
          ← false
          → AcumuladorEventosTecnicos::criarLoteParaExportacao()
          ← LoteEventosTecnicos
          [extrai] envelopes técnicos exportáveis
          [cria] ResultadoPublicacaoPcaScriptcase
                  resultado: PublicarPcaViewModel
                  eventos: envelopes exportados
                  aviso técnico: null
        ← ResultadoPublicacaoPcaScriptcase          (ComposicaoExecucaoPublicacaoPca::exportar → ExecutorPublicacaoPcaScriptcase::executarInternamente)
      ← o mesmo ResultadoPublicacaoPcaScriptcase    (ExecutorPublicacaoPcaScriptcase::executarInternamente → ExecutorPublicacaoPcaScriptcase::executarComEventos)
    ← o mesmo ResultadoPublicacaoPcaScriptcase      (ExecutorPublicacaoPcaScriptcase::executarComEventos → PublicadorPcaScriptcase::executarComEventos)
  ← o mesmo ResultadoPublicacaoPcaScriptcase        (PublicadorPcaScriptcase::executarComEventos → Blank PCA_PUBLICAR_B)

  # 11. Registro técnico posterior e resposta ao navegador

  → ResultadoPublicacaoPcaScriptcase::getResultado()
  ← PublicarPcaViewModel

  → pca_publicar_serializar_resultado(PublicarPcaViewModel)
  ← array seguro do resultado funcional

  [extrai] eventos técnicos e aviso técnico

  → RegistradorEventosTecnicosScriptcase::registrar(...)
    → ExecutorRegistroEventosTecnicosScriptcase::registrar(...)
      → CarregadorPerfilPublicacaoPca::carregar(SERVER_NAME)
      ← perfil externo
      [cria] PerfilPublicacaoPca
      [cria] ConexaoAdodbEmprestada
      [cria] OracleRegistroEventoTecnicoAdapter

      # Para cada envelope exportado, usa uma transação técnica própria
      → OracleRegistroEventoTecnicoAdapter::registrar(evento, login, aplicação)
        → insere o marcador do evento em SGI.SEGURANCA_LOG
        → atualiza o CLOB integral por ID
        → commit da transação técnica do evento
      ← evento técnico persistido
    ← null: todos os eventos foram registrados
  ← null: nenhum aviso técnico

  [cria] resposta HTTP com resultado e avisoRegistroTecnico = null
  → pca_publicar_responder_json(200, resposta)
  ← JSON apresentado ao navegador com RETIFICAR / CONCLUIDO
```

## O ponto central do cenário

A primeira decisão e a decisão posterior respondem a perguntas diferentes:

- `RETIFICAR`: qual operação é necessária diante da fotografia inicial?
- `NENHUMA_ACAO`: depois das escritas, ainda existe alguma diferença?

Por isso, `NENHUMA_ACAO` comprova a convergência, enquanto o resultado unitário
continua informando que a operação executada foi `RETIFICAR`.

## Leituras vigentes relacionadas

- [Guia de leitura do código](../../vigentes/apoio/guia_de_leitura_do_codigo.md)
- [Catálogo de arquivos](../../vigentes/apoio/catalogo_de_arquivos.md)
- [Glossário](../../vigentes/apoio/glossario.md)
