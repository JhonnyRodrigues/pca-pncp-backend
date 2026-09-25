# Fluxo de Implementação do Backend PCA/PNCP

**Classificação documental:** Autoritativo

**Status:** Vigente

**Autoridade/Escopo:** colaboração dinâmica entre Presentation, Application,
Domain e Ports no processamento da publicação e sincronização do PCA

## 1. Finalidade, autoridade e limites

Este documento responde à pergunta:

> Como os componentes colaboram, em tempo de execução, para observar, decidir,
> publicar, comprovar a convergência, registrar a confirmação de sincronização
> e apresentar o resultado do processamento do PCA?

Sua autoridade limita-se à sequência dinâmica e às responsabilidades dessa
colaboração. A autoridade dos assuntos relacionados permanece distribuída:

| Assunto | Fonte autoritativa |
| --- | --- |
| Regras de negócio | [Regras de negócio](regras_de_negocio.md) |
| Conceitos, identidades, relações e invariantes | [Modelo de domínio](modelo_de_dominio.md) |
| Contratos externos, persistência técnica e operação | [Decisões de integração](decisoes_de_integracao.md) |
| Ordem de construção | [Roadmap de implementação](roadmap.md) |
| Classificação e precedência documental | [Índice documental](../../README.md) |

A [Arquitetura](../apoio/arquitetura.md) explica os limites estáticos e a
direção das dependências. Este fluxo explica como os participantes colaboram
dentro desses limites.

Quando uma etapa expressar comportamento de negócio, ela referencia uma
`RN-*`. Quando depender de integração, coordenação operacional ou mecanismo
externo, referencia uma `DI-*`. Este documento não reescreve essas fontes.

Neste documento, **V1 da integração** designa a primeira geração operacional
da estratégia de publicação, sincronização e reconciliação definida pelas
decisões de integração vigentes. Ela não se confunde com o **Fluxo de
Implementação V2**, que identifica a segunda versão desta documentação e de
seu modelo de colaboração, nem com a documentação histórica V1 (`DI-001`,
`DI-002`, `DI-004`, `DI-009` e `DI-010`).

Este fluxo não define:

- cronograma, fases ou sprints;
- ordem de construção das classes;
- assinaturas concretas de interfaces;
- estruturas físicas, consultas ou comandos de persistência;
- endereços, operações, corpos de dados ou mecanismos de transporte
  (`endpoints` e `payloads` contratuais);
- implementação de adapters;
- parâmetros ainda condicionados a testes contratuais.

Esses limites impedem que o fluxo funcione como roadmap ou como especificação
concorrente de negócio, domínio ou integração.

## 2. Participantes e responsabilidades

| Participante | Responsabilidade no fluxo |
| --- | --- |
| Aplicação hospedeira | Extrair conexão, login, `SERVER_NAME`, ano e `X-Request-ID` e invocar somente o entrypoint público |
| `PublicadorPcaScriptcase` | Validar a correlação, selecionar e carregar o perfil externo, construir a entrada e acionar o grafo sem expor o bootstrap à Blank |
| Fachada interna | Proteger a fronteira da Presentation e impedir que exceções ou detalhes internos atravessem para o consumidor |
| Controller | Adaptar a entrada tipada, construir o Request e acionar o UseCase já composto |
| UseCase da Application | Validar, coordenar Ports, formar unidades externas, solicitar decisões ao Domain e compor resultados |
| Ports da Application | Fornecer contexto, estratégia, exclusividade, estado operacional, observação e escrita externas e auditoria |
| `PlanoRepository` | Atuar como Port especializada do Domain para descobrir identidades, reconstruir fotografias vigentes e persistir confirmações de sincronização orientadas à intenção do caso de uso |
| Domain | Proteger invariantes e decidir `PUBLICAR`, `RETIFICAR`, `EXCLUIR_PCA` ou `NENHUMA_ACAO` a partir de fatos tipados |
| Adapters da Infrastructure | Traduzir Ports para mecanismos externos sem escolher ação de negócio |
| Presenter | Transformar a Response em ViewModel estruturado e seguro |
| Composition Root | Construir e injetar o grafo concreto sem transferir wiring para o Controller ou para o hospedeiro |

A responsabilidade pelas Ports e a separação entre Aggregate, estado
operacional e estado observado seguem `DI-005`, `DI-006`, `DI-007` e
`DI-010`.

## 3. Visão geral

```text
contexto externo aprovado
        ↓
PublicadorPcaScriptcase → Composition Root → Fachada → Controller → Request
        ↓
validar solicitação e contexto
        ↓
descobrir identidades e validar estratégia
        ↓
adotar ou conferir estratégia anual
        ↓
formar unidades externas
        ↓
autenticar para as leituras no PNCP
        ↓
para cada unidade:
    obter exclusividade
        ↓
    reler fotografia e estado operacional
        ↓
    observar estado externo
        ↓
    Domain decide a intenção
        ↓
    se houver escrita, autenticar novamente
        ↓
    executar, quando necessário
        ↓
    observar novamente
        ↓
    registrar a confirmação de sincronização ou desfazer localmente
        ↓
compor resultado global
        ↓
Presenter → ViewModel → Fachada
```

O fluxo normal segue o ciclo aprovado em `DI-002` e `DI-004`:

```text
observar → decidir → executar → observar novamente → registrar confirmação de sincronização
```

A confirmação de sincronização depende da observação final. Um resultado
intermediário de escrita não substitui a comprovação da convergência.

## 4. Preparação global da execução

### 4.1 Entrada pública e composição

A aplicação hospedeira extrai somente conexão ADOdb emprestada, login
autenticado, `SERVER_NAME`, ano e `X-Request-ID` e invoca
`PublicadorPcaScriptcase`. Antes de qualquer outro passo, o entrypoint exige que
a correlação recebida seja um UUID v4 completo e preserva literalmente o texto
fornecido pelo servidor HTTP (`DI-010`).

Com correlação válida, o entrypoint lê exclusivamente de
`PCA_PNCP_PROFILE_REGISTRY_FILE` o caminho absoluto do registro externo. O
registro funciona somente como índice allowlisted de `SERVER_NAME` exato para
caminho absoluto de perfil; não contém configuração e não deriva caminhos ou
ambiente do hostname. Registro, chave, caminho ou perfil inválido falham
fechado.

O perfil PHP externo é tratado como entrada não confiável. Dele vêm ambiente,
CNPJ institucional, credenciais e parâmetros. O entrypoint materializa o CNPJ
e constrói `EntradaPublicarPca` com o ano recebido; a Blank não fornece CNPJ,
perfil, allowlist ou caminhos.

O `CompositionRootPublicacaoPca` recebe os recursos já extraídos, o perfil
carregado e a correlação validada. Ele materializa a configuração restante,
constrói um novo grafo para a chamada, compartilha conexão, transporte, logger
e correlação e cria Presenter, Controller e `FachadaPublicarPca`. O Controller
recebe a entrada tipada e constrói o Request com CNPJ e Ano de Execução
(`DI-010`).

O Request não recebe ambiente, endereço externo, credenciais, identidade do
usuário ou detalhes dos mecanismos integrados. O hospedeiro não monta o
UseCase nem conhece adapters concretos.

O contrato aprovado para a Sprint 8.5 preserva `executar(...)` e acrescenta
`executarComEventos(...)` com os mesmos argumentos. Os dois métodos
compartilham o caminho de execução; a nova saída reúne `resultado` (ViewModel),
`eventos` (envelopes sanitizados) e `avisoRegistroTecnico`. A composição mantém
a fachada, o acumulador e o estado transacional da mesma chamada disponíveis
para essa exportação, sem duplicar o processamento (`DI-010`).

Request ID ausente ou inválido produz `INTERROMPIDA/REQUEST_ID_INVALIDO`, com
correlação `null`, sem consultar o registro e sem evento técnico. Qualquer outra
falha na inicialização e composição global (`bootstrap`) produz
`INTERROMPIDA/FALHA_INESPERADA`, preserva a correlação externa válida e não
expõe exceção, caminho, perfil, CNPJ ou segredo (`DI-010`).

### 4.2 Validação da solicitação e do contexto

A fronteira de entrada materializa CNPJ e Ano de Execução pelos Value Objects
do Domain antes de invocar o UseCase tipado (`RN-CAD-001`, `RN-CAD-002` e
`DI-010`). Se a entrada bruta for inválida, ela usa o contrato
`PublicarPcaResponse::solicitacaoInvalida`; o UseCase não recebe um Request
inválido e nenhum processamento externo é iniciado.

Depois da validação, a Application resolve o usuário autenticado, seu
Representante e sua Lotação por uma Port coesa de contexto. Identidade ou
contexto não resolvíveis impedem o avanço (`RN-CAD-003`, `RN-CAD-004`,
`RN-AUD-004`, `DI-006` e `DI-010`).

### 4.3 Descoberta preliminar

A Application solicita ao `PlanoRepository` somente a descoberta das
identidades lógicas existentes no exercício. Essa etapa não monta a fotografia
das Demandas e não substitui a releitura posterior sob exclusividade
(`DI-007` e `DI-008`).

A descoberta permite:

- comprovar que existe contexto de trabalho;
- identificar as UOs participantes;
- validar a resolução única de UASG de cada UO;
- preparar a adoção ou conferência da estratégia anual;
- determinar as futuras unidades externas.

Cada UO participante deve possuir cadastro ativo único com UASG preenchida.
Ausência, duplicidade ou valor inválido bloqueia antes do PNCP
(`RN-CAD-005`, `RN-CAD-006` e `DI-008`).

### 4.4 Estratégia corrente e estratégia adotada

A Application obtém por Port a estratégia corrente e a estratégia já adotada
para o exercício. Configuração ausente, inválida ou incoerente não recebe
valor padrão implícito (`default`) e impede qualquer acesso ao PNCP (`DI-003`).

Uma estratégia pode ser adotada somente quando:

- o Request é válido;
- a configuração está completa e coerente;
- as UOs e UASGs atendem às restrições aplicáveis;
- existe ao menos uma identidade lógica no exercício.

A adoção ou conferência ocorre em uma unidade exclusiva e curta, anterior à
autenticação externa:

1. solicitar exclusividade para a estratégia do exercício;
2. reler configuração corrente e estratégia adotada;
3. adotar a estratégia corrente se ainda não houver estratégia adotada;
4. concluir a transação local que registra essa escolha antes de qualquer
   acesso ao PNCP;
5. bloquear, sem sobrescrever, quando a estratégia corrente divergir da
   adotada.

O congelamento na primeira execução válida e a proibição de migração
automática seguem `DI-003` e `DI-008`.

### 4.5 Formação das unidades externas

Com a adoção da estratégia concluída e registrada localmente, a Application
forma as unidades externas sem fundir os Aggregates `Plano` (`DI-003` e
`DI-007`).

| Modo | Formação da unidade | Resultado esperado |
| --- | --- | --- |
| Individual | Uma unidade para cada UO e sua UASG | Um resultado independente por unidade |
| Consolidado | Uma unidade para a união completa das UOs do exercício na UASG consolidadora | Um único resultado conjunto |

No modo individual, UOs do mesmo exercício não podem governar a mesma UASG
(`RN-PUB-009` e `RN-PUB-011`). No consolidado, todas as UOs contribuem para a
mesma unidade, e uma UO vazia apenas deixa de contribuir (`RN-PUB-010`).

### 4.6 Autenticação externa

Somente depois da preparação válida, a Application autentica para as leituras
iniciais da execução. Após a decisão de uma unidade que exige escrita, autentica
novamente antes do primeiro comando mutante e reutiliza essa sessão nas escritas
e observações finais da unidade. `NENHUMA_ACAO` preserva a sessão inicial
(`DI-007`, `DI-009` e `DI-010`).

Falha de autenticação ou autorização é sistêmica e encerra a varredura. A
sessão não é persistida, exposta ou compartilhada entre execuções.

## 5. Processamento de uma unidade externa

Cada unidade externa é processada pela seguinte colaboração.

### 5.1 Exclusividade e unidade de trabalho

A Application inicia a unidade de trabalho e solicita exclusividade para a
chave lógica da unidade externa. Ela não conhece o mecanismo concreto usado
para obter essa exclusividade (`DI-004`, `DI-007` e `DI-008`).

Se a unidade já estiver sendo processada:

- não aguardar indefinidamente;
- não realizar leitura decisória nem escrita externa;
- desfazer a unidade local;
- produzir resultado unitário `BLOQUEADO`.

No modo individual, o fluxo pode continuar nas demais unidades. No modo
consolidado, existe somente a unidade conjunta.

### 5.2 Releitura autoritativa sob exclusividade

Depois da exclusividade, a Application solicita:

1. a releitura das identidades que compõem a unidade;
2. a reconstrução da fotografia homologada vigente de cada Plano;
3. a leitura do estado operacional da publicação externa.

A fotografia deve:

- usar a identidade UO + Ano de Execução (`RN-PLA-001`);
- adotar a maior versão existente como marco vigente (`RN-PLA-002` e
  `RN-PLA-003`);
- reunir Demandas vigentes de todas as versões de origem
  (`RN-PLA-004`);
- preservar UO e exercício uniformes (`RN-PLA-006`);
- incluir Demandas Homologadas, Compradas e Publicadas e excluir Planejadas e
  Canceladas (`RN-DEM-009`);
- representar Plano com zero Demandas ativas (`RN-PLA-005`).

A fotografia vigente não transporta a justificativa histórica de uma Demanda
cancelada. Quando esse fato for necessário para excluir integralmente o PCA, a
Application usa a capacidade específica definida em `DI-007`, sem ampliar o
`PlanoRepository`.

No modo consolidado, a releitura ocorre para todas as UOs depois da
exclusividade da unidade conjunta. A união desejada é calculada pela
Application, sem criar outro Aggregate (`RN-PUB-010` e `DI-003`).

### 5.3 Observação do estado externo

A Application observa o PNCP pela Port de leitura usando a sessão opaca
(`DI-007` e `DI-009`). A observação devolve fatos tipados e deve distinguir:

- PCA conhecido encontrado;
- ausência definitivamente comprovada;
- consulta inconclusiva;
- conflito ou resposta inválida;
- falha sistêmica.

Quando existir vínculo local conhecido, a consulta usa essa referência. A
busca de candidatos sem vínculo serve somente para diagnóstico ou migração
assistida e nunca autoriza adoção automática (`DI-002` e `DI-007`).

A ausência definitiva de um PCA conhecido precisa atender ao conjunto de
observações aprovado na `DI-009`. Resposta parcial, ambígua ou falha não
autoriza recriação.

Para PCA conhecido e vinculado, a V1 da integração aplica reconciliação por
pertencimento: compara a identidade de cada Demanda desejada à identidade
correspondente do item observado. O contrato vigente representa essas
identidades por `ID_DEMANDA`, no estado desejado, e `numeroItem`, no estado
observado (`DI-001` e `DI-002`).

Como a V1 da integração não mantém uma fotografia detalhada do conteúdo
externo anteriormente confirmado (`baseline de conteúdo`), ela não compara
automaticamente atributos item a item nem executa atualização parcial de
atributos (`PATCH` contratual). A observação precisa estar completa antes da
decisão (`DI-001` e `DI-002`).

### 5.4 Decisão no Domain

A Application fornece à política pura do Domain:

- a fotografia desejada da unidade;
- o estado operacional tipado;
- o estado externo observado;
- as diferenças de pertencimento comprovadas.

O Domain devolve uma intenção e, quando aplicável, as identidades das Demandas
envolvidas. Ele não conhece mecanismos de persistência, transporte,
configuração ou observabilidade (`DI-007`).

| Condição | Decisão | Autoridade |
| --- | --- | --- |
| Há Demandas vigentes e não há PCA ativo conhecido | `PUBLICAR` | `RN-PUB-001` |
| Há PCA ativo e inclusões ou exclusões homologadas | `RETIFICAR` | `RN-PUB-002`, `RN-PUB-006` |
| A unidade ficou vazia e há PCA ativo | `EXCLUIR_PCA` | `RN-PUB-003` |
| O estado está convergente ou ambos os lados estão inativos | `NENHUMA_ACAO` | `RN-PUB-004` |

Versão e presença isolada de sequencial não determinam a ação
(`RN-PUB-005`, `RN-PUB-008`). Depois de uma exclusão, uma nova fotografia não
vazia exige nova Publicação (`RN-PUB-007`).

## 6. Execução das decisões

### 6.1 `PUBLICAR`

A Application ordena a criação do PCA com o conjunto vigente completo da
unidade (`RN-PUB-001` e `DI-001`). Antes da escrita, valida o limite contratual
vigente de itens, atualmente fixado em 1000 itens. Uma unidade acima desse
limite é bloqueada integralmente; não há publicação parcial ou particionamento
na V1 da integração (`DI-001` e `DI-004`).

Uma resposta de escrita não encerra o fluxo. O estado externo deve ser
observado novamente. Somente a convergência comprovada autoriza:

- registrar o estado operacional `PUBLICADO` com o novo `sequencialPca`;
- registrar a confirmação de sincronização das versões vigentes pendentes;
- materializar a auditoria de Publicação inicial.

O fato de auditoria é classificado pelo procedimento de negócio Publicação
inicial (código 5 no catálogo vigente de procedimentos) e segue `RN-AUD-001` a
`RN-AUD-005` e `DI-006`.

### 6.2 `RETIFICAR`

A Application ordena somente as diferenças de pertencimento:

1. excluir os itens que deixaram de integrar o conjunto vigente;
2. inserir os novos itens homologados;
3. observar novamente o PCA completo.

A ordem e os lotes seguem `DI-001` e `DI-009`. A atualização parcial de
atributos do item (`PATCH` contratual) não integra o fluxo normal. O
`sequencialPca` ativo é preservado (`RN-PUB-002`).

Uma falha intermediária não define sozinha o resultado. A observação final
determina se houve convergência, se restam mudanças homologadas pendentes ou
se apareceu divergência inexplicada (`DI-004`).

Convergência comprovada autoriza o registro da confirmação de sincronização e
um único fato de auditoria de Retificação para o PCA externo. A mensagem
informa as quantidades agregadas de itens incluídos e excluídos. O fato é
classificado pelo procedimento de negócio Retificação (código 8 no catálogo
vigente de procedimentos), conforme `RN-AUD-002`, `RN-AUD-003`, `RN-AUD-005`
e `DI-006`.

### 6.3 `EXCLUIR_PCA`

A Application ordena a exclusão integral quando toda a unidade externa fica
sem Demandas vigentes (`RN-PUB-003`).

No modo individual, isso depende da fotografia da UO. No consolidado, depende
da união completa de todas as UOs (`RN-PUB-009` e `RN-PUB-010`).

A Application constrói o comando de exclusão com a justificativa institucional
fixa `Exclusão integral do PCA por ausência de demandas vigentes no SGI.`, nos
termos de `RN-PUB-003` e `DI-009`. Não há consulta histórica nem dependência da
justificativa de uma Demanda cancelada.

Essa simplificação não altera o gatilho da decisão, a exigência de sequencial
conhecido ou a classificação dos resultados da escrita externa.

Depois da observação final comprovar a exclusão, a Application ordena:

- estado operacional `EXCLUIDO`;
- preservação histórica do último sequencial sem tratá-lo como ativo;
- registro da confirmação de sincronização das versões vigentes pendentes;
- um fato de auditoria classificado pelo procedimento de negócio Exclusão
  integral (código 9 no catálogo vigente de procedimentos).

Uma publicação posterior adotará novo sequencial (`RN-PUB-007`,
`RN-PUB-008`, `DI-005` e `DI-006`).

### 6.4 `NENHUMA_ACAO`

`NENHUMA_ACAO` não executa escrita externa e não gera histórico de negócio
(`RN-PUB-004`, `RN-AUD-001` e `DI-006`).

Se a observação já comprovar a convergência de uma versão vigente ainda
pendente, a Application registra a confirmação de sincronização dessa versão
conforme `RN-PLA-007` e `DI-005`. Esse registro não transforma a ausência de
ação externa em Publicação ou Retificação.

A unidade termina como `SEM_ALTERACAO` depois da conclusão local necessária.

## 7. Observação final e reconciliação controlada

Depois de qualquer escrita, a Application observa novamente o estado externo
completo. A classificação final segue `DI-002`, `DI-004` e `DI-009`:

| Estado final observado | Consequência |
| --- | --- |
| Convergente | Registrar a confirmação local da sincronização e concluir a unidade |
| Restam somente mudanças homologadas pendentes | Desfazer localmente e retornar `FALHA_RECONCILIAVEL` |
| Divergência inesperada | Desfazer localmente, diagnosticar e bloquear |
| Não observável | Desfazer localmente e classificar conforme a falha |

Escrita de resultado incerto nunca é repetida diretamente. O fluxo deve:

1. observar novamente;
2. comparar estado desejado e observado;
3. recalcular as operações ainda necessárias;
4. permitir no máximo uma reconciliação controlada adicional na mesma
   execução.

Nova incerteza encerra a unidade sem loop e sem compensação automática
(`DI-004` e `DI-009`).

## 8. Confirmação, estado operacional e auditoria

Quando a convergência estiver comprovada, a Application ordena as alterações
locais aplicáveis dentro da mesma unidade de trabalho:

- atualizar o estado operacional da publicação;
- registrar ou preservar o `sequencialPca` conforme o estado;
- registrar a confirmação de sincronização somente para as versões vigentes
  pendentes;
- persistir o fato de auditoria quando ocorreu operação externa auditável.

Os registros de confirmação de sincronização, estado operacional e auditoria
devem ser persistidos ou desfeitos em conjunto na mesma unidade de trabalho
(`DI-005`, `DI-006` e `DI-008`). O `PlanoRepository` persiste a confirmação de
sincronização orientada à intenção. As demais Ports recebem as intenções de
estado operacional e auditoria, e seus adapters realizam a persistência
concreta. Nenhum desses participantes decide convergência, procedimento ou
mensagem.

No modo individual, registra-se a confirmação de sincronização da versão
vigente da UO processada. No modo consolidado, registram-se conjuntamente as
confirmações das versões vigentes pendentes que participam da unidade
(`RN-PLA-007` e `DI-005`).

A auditoria:

- registra somente Publicação, Retificação ou Exclusão integral cuja
  convergência foi confirmada;
- possui um fato por PCA externo;
- usa o Representante e a Lotação resolvidos para o usuário da execução;
- não recebe tentativas, falhas, conflitos, reversão da transação local
  (`rollback`), contenção ou `NENHUMA_ACAO`.

Esses comportamentos pertencem a `RN-AUD-001` a `RN-AUD-005` e `DI-006`.
Tentativas e falhas permanecem na observabilidade técnica.

Se o estado externo convergir, mas o registro local da confirmação de
sincronização falhar, o fluxo não tenta desfazer automaticamente o efeito
externo. Ele desfaz o que for local, registra diagnóstico seguro e exige
reconciliação consciente (`DI-002` e `DI-004`).

## 9. Resultados e continuidade

### 9.1 Resultado por unidade externa

| Estado | Significado |
| --- | --- |
| `CONCLUIDO` | A convergência da operação necessária foi comprovada e seus efeitos locais foram registrados |
| `SEM_ALTERACAO` | Não foi necessária escrita externa e a unidade terminou coerente |
| `FALHA_RECONCILIAVEL` | Restou pendência que admite nova observação ou atuação consciente |
| `BLOQUEADO` | A unidade não pôde prosseguir por contenção ou conflito bloqueante |

Cada resultado preserva a identidade externa e somente os dados seguros
aprovados na `DI-010`. Unidades não visitadas não recebem resultados
fabricados.

No modo individual, falha específica ou bloqueio de uma unidade não impede as
demais. Falha sistêmica encerra a varredura. No modo consolidado, há uma única
unidade e um único resultado (`RN-PUB-009`, `RN-PUB-010`, `DI-004` e
`DI-010`).

### 9.2 Resultado global

Depois da varredura, a Application compõe a Response de forma determinística:

| Condição | Estado global |
| --- | --- |
| Entrada inválida | `SOLICITACAO_INVALIDA` |
| Todas as unidades terminaram em `CONCLUIDO` ou `SEM_ALTERACAO` | `CONCLUIDA` |
| A varredura terminou com `FALHA_RECONCILIAVEL` ou `BLOQUEADO` | `CONCLUIDA_COM_PENDENCIAS` |
| Falha sistêmica interrompeu a varredura | `INTERROMPIDA` |

Em uma interrupção, resultados já conhecidos são preservados, junto das
quantidades processadas e não processadas. Nenhuma exceção atravessa a fachada
(`DI-010`).

O Presenter converte a Response em ViewModel estruturado e seguro. A aplicação
hospedeira apenas renderiza esse ViewModel.

## 10. Falhas e observabilidade

Falhas são classificadas pelo significado que possuem para o fluxo, sem
exposição do mecanismo externo:

- falha específica de unidade;
- falha reconciliável;
- bloqueio ou conflito;
- falha sistêmica;
- resultado incerto de escrita.

A observabilidade técnica acompanha preparação, autenticação, unidades,
decisões, escritas, reconciliação, contenção e falhas. Seu envelope,
dicionário de campos, erro seguro, sanitização e imutabilidade obedecem à
[Especificação de logs estruturados](especificacao_de_logs_estruturados.md)
(`DI-006`, `DI-009` e `DI-010`).

Nos eventos emitidos depois da decisão, `context.decisao` identifica
explicitamente `PUBLICAR`, `RETIFICAR`, `EXCLUIR_PCA` ou `NENHUMA_ACAO`.
`action` continua descrevendo a etapa técnica, e somente `PAC_HISTORICOS`
afirma fatos de negócio auditáveis.

O backend produz os eventos por PSR-3 e os mantém ordenados somente em memória.
O lote para exportação é preparado sem modificar os eventos originais.

O logger usa exatamente a correlação válida fornecida pelo servidor HTTP. Uma
falha inesperada capturada pela fachada gera a ação
`EXECUTAR_PUBLICACAO_PCA` com erro seguro, sem transportar a exceção. A falha
anterior à correlação válida ou à construção do logger não fabrica evento
técnico, e uma falha do logger não altera o ViewModel seguro.

Os eventos não integram `PublicarPcaResponse`. O contrato aprovado para a
Sprint 8.5 exige sua devolução separada ao hospedeiro por
`executarComEventos(...)`, após a execução. A cópia preserva a ordem e a
correlação e acrescenta `executionTimeMs` apenas ao último envelope. Falhas
anteriores à criação do logger não fabricam eventos; falha de exportação
preserva o resultado conhecido e devolve aviso seguro.

Antes de permitir a persistência, a fronteira verifica se o encerramento da
transação Oracle está confirmado. Sem essa confirmação, impede a gravação
e devolve aviso separado; não confirma uma transação de negócio pendente nem
tenta compensação ou nova publicação para registrar eventos.

Depois do retorno seguro, a Blank entrega o lote ordenado a
`RegistradorEventosTecnicosScriptcase`. Para cada evento, o registrador abre
uma transação técnica curta na mesma conexão emprestada, insere uma linha com
marcador provisório, obtém o ID preenchido pelo trigger e grava o envelope
integral no CLOB `DESCRIPTION` por `UpdateClob`. Depois confirma somente essa
transação. A ação detalhada permanece no JSON, e
`SEGURANCA_LOG.ACTION` recebe `execute`. A serialização usa escapes Unicode,
preserva mapas vazios como objetos JSON e não trunca, fragmenta ou converte o
texto para ISO-8859-1.

A Blank serializa explicitamente o resultado conhecido antes de iniciar o
registro técnico. A adaptação dos resultados unitários usa chamada direta,
sem callback identificado apenas por texto, porque o compilador do hospedeiro
pode renomear funções. Uma falha inesperada do registrador é contida e se torna
aviso seguro, sem substituir o resultado funcional já preparado.

Falha recuperável de serialização ou gravação interrompe os registros
restantes, preserva o resultado e gera aviso separado. Registros já tentados
não são repetidos; a transação técnica corrente é desfeita quando possível.
Ao navegador segue
somente `{resultado, avisoRegistroTecnico}`, sem eventos técnicos, exceções ou
respostas brutas. Falha de transporte não provoca nova publicação automática.

Esse contrato foi aprovado no Microciclo 1 da Sprint 8.5, e a exportação local
foi implementada no Microciclo 2. O Microciclo 3 comprovou localmente a
contenção, a reconciliação limitada e a interrupção sistêmica sobre adapters
concretos com fronteiras físicas controladas. O Microciclo 4 versionou a
adaptação da Blank, incluindo validação da entrada, serialização explícita da
saída e a primeira integração de logs. O gate do Microciclo 6 demonstrou que
`sc_log_add` não suporta o CLOB exigido nesta instalação; o Microciclo 6A
substitui a macro pelo registrador próprio descrito acima. O Microciclo 5 gerou o pacote
autocontido e preparou o suporte opt-in, as consultas completas e o PRECHECK
bloqueante. A instalação e os gates reais permanecem pendentes; seu progresso pertence ao
[estado operacional](../operacional/estado_atual.md).

Auditoria e observabilidade não são intercambiáveis: a primeira afirma fatos
de negócio confirmados; a segunda registra o funcionamento e os problemas da
execução (`RN-AUD-001` e `DI-006`).

Por isso, `SEGURANCA_LOG` permanece separada de `PAC_HISTORICOS`, e o backend
não abre uma segunda conexão Oracle para logging. Uma falha fatal anterior à
resposta HTTP pode impedir a entrega do resultado e a persistência completa
dos eventos. Essa limitação é aceita conscientemente.

## 11. Gates e limitações vigentes

O fluxo descreve a colaboração aprovada, mas não libera antecipadamente
adapters nem transforma decisões condicionais em contratos comprovados.

Antes das respectivas implementações concretas, permanecem os gates:

- contrato do mecanismo de persistência e compartilhamento da unidade local;
- estrutura física dos dados (`schema`), cardinalidades, datas, decimais e coerência da projeção;
- ambiente efetivo de execução do transporte seguro (`runtime`) e suíte contratual ativada explicitamente (`opt-in`) no ambiente de treinamento;
- identidade externa dos itens e ausência definitiva;
- entrypoint, provisionamento do registro, contexto do usuário e pacote no
  ambiente hospedeiro real.

Os critérios completos e as condições de reabertura pertencem a `DI-008`,
`DI-009` e `DI-010`.

### Limitações deliberadas da V1 da integração

As explicações e os exemplos abaixo apenas ilustram as consequências práticas
das limitações definidas nas decisões de integração vigentes.

- **Limite de itens sem divisão automática:** cada unidade externa admite o
  limite contratual vigente, atualmente 1000 itens, e não é dividida em
  publicações menores (`particionamento`). Exemplo: uma unidade com 1001 itens
  é bloqueada integralmente, em vez de publicar os primeiros 1000.

- **Reconciliação somente por pertencimento:** o fluxo compara quais
  identidades estão presentes nos conjuntos desejado e observado, sem comparar
  o conteúdo de cada item. Exemplo: se um item mantiver a mesma identidade,
  mas um atributo mudar, essa diferença não será detectada por essa
  reconciliação.

- **Sem atualização parcial de atributos:** a V1 não envia alterações isoladas
  dos atributos de um item (`PATCH` contratual) no fluxo normal. Exemplo: se a
  data desejada mudar sem mudança de identidade, a V1 não enviará uma operação
  para alterar apenas essa data.

- **Sem adoção automática de PCA sem vínculo:** uma publicação encontrada no
  PNCP sem relação local conhecida não é assumida como pertencente ao SGI
  apenas por parecer correspondente. Exemplo: encontrar um PCA com o mesmo ano
  e a mesma UASG permite diagnóstico, mas exige análise controlada antes de
  qualquer vínculo.

- **Sem compensação automática:** o fluxo não executa uma ação inversa apenas
  para tentar desfazer um efeito externo já produzido antes de uma falha.
  Exemplo: se a exclusão de um item for aceita antes de uma falha posterior, o
  sistema não o reinsere automaticamente para retornar ao estado inicial.

- **Somente uma reconciliação adicional após escrita incerta:** depois de um
  resultado incerto, o fluxo observa novamente o estado, recalcula o que ainda
  falta e permite uma única reconciliação controlada adicional na mesma
  execução. Exemplo: se essa nova tentativa também terminar de forma incerta,
  a unidade é encerrada em vez de repetir o ciclo indefinidamente.

- **Execução síncrona:** quem inicia a operação aguarda seu resultado final,
  sem processamento em fila ou em segundo plano. Exemplo: o consumidor da
  fachada somente recebe a resposta quando a execução termina ou é
  interrompida.

- **Sem atomicidade conjunta entre os sistemas:** o estado local e o PNCP não
  podem ser confirmados ou desfeitos como uma única operação indivisível
  (`transação distribuída`). Exemplo: o PNCP pode aceitar uma operação e o
  registro local falhar, exigindo reconciliação consciente.

Parâmetros ainda sujeitos a gates não recebem valores padrão inventados neste
documento.

## 12. Matriz de rastreabilidade

| Parte do fluxo | Regras de negócio | Decisões de integração |
| --- | --- | --- |
| Entrada e contexto | `RN-CAD-001` a `RN-CAD-004` | `DI-006`, `DI-010` |
| UO, UASG e estratégia | `RN-CAD-005`, `RN-CAD-006`, `RN-PUB-009` a `RN-PUB-011` | `DI-003`, `DI-008` |
| Descoberta e reconstrução | `RN-DEM-009`, `RN-PLA-001` a `RN-PLA-006` | `DI-007`, `DI-008` |
| Observação e reconciliação | `RN-PUB-004`, `RN-PUB-006` | `DI-002`, `DI-004`, `DI-007`, `DI-009` |
| Decisão de processamento | `RN-PUB-001` a `RN-PUB-008` | `DI-001`, `DI-002`, `DI-005`, `DI-007` |
| Confirmação versionada | `RN-PLA-007` | `DI-005`, `DI-008` |
| Auditoria | `RN-AUD-001` a `RN-AUD-005` | `DI-006` |
| Resultados e fronteira pública | `RN-PUB-009`, `RN-PUB-010` | `DI-004`, `DI-007`, `DI-010` |

## 13. Critérios de coerência do fluxo

Este documento permanece coerente enquanto:

- comportamento de negócio continuar referenciado pelas `RN-*` vigentes;
- escolhas externas e operacionais continuarem referenciadas pelas `DI-*`;
- Domain decidir intenções sem conhecer mecanismos externos;
- Application coordenar unidades e Ports sem decidir mecanismos concretos;
- Infrastructure traduzir contratos sem escolher regras de negócio;
- Presentation adaptar entrada e saída sem acessar adapters;
- nenhuma etapa futura do roadmap for apresentada como já implementada.

Uma alteração de regra ou decisão deve ser consolidada primeiro em sua fonte
autoritativa e depois refletida neste fluxo.
