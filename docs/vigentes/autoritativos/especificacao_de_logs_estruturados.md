# Especificação de logs estruturados

**Classificação documental:** Autoritativo  
**Status:** Vigente  
**Autoridade/Escopo:** contrato institucional do envelope, significado dos
campos e proteção de dados dos eventos técnicos

## 1. Finalidade e fontes

Esta especificação consolida localmente o contrato definido pelo POP de
Desenvolvimento, seção 7.6, e pelo Anexo B — Dicionário de Dados dos Logs
Estruturados. Ela deve ser lida com a `DI-006` para distinguir observabilidade
técnica de auditoria de negócio e com o Fluxo de Implementação para compreender
o ciclo de vida dos eventos no backend.

O documento não amplia o dicionário institucional nem define persistência,
campos físicos ou valores próprios da aplicação hospedeira.

## 2. Envelope institucional

Os nomes são emitidos em `camelCase`. Todo envelope possui a estrutura abaixo;
mapas sem conteúdo permanecem vazios e `ipAddress` pode ser `null`.

| Campo | Tipo | Significado |
| --- | --- | --- |
| `timestamp` | string | Instante do evento em ISO 8601. |
| `level` | string | Severidade normalizada: `DEBUG`, `INFO`, `WARNING` ou `ERROR`. |
| `correlationId` | string | UUID v4 fornecido pelo servidor HTTP e preservado literalmente em toda a execução. |
| `environment` | string | Ambiente explícito da execução. |
| `system` | string | Sistema emissor, identificado como `pca-pncp-backend`. |
| `module` | string | Módulo lógico que originou o evento. |
| `action` | string | Operação técnica estável que estava sendo executada. |
| `message` | string | Mensagem técnica sanitizada. |
| `error` | objeto | Erro seguro associado ao evento ou mapa vazio. |
| `context` | objeto | Contexto técnico sanitizado ou mapa vazio. |
| `metadata` | objeto | Medidas e classificações sanitizadas ou mapa vazio. |
| `ipAddress` | string ou `null` | IP confiável quando disponível e válido. |

Depois que o Domain determina uma decisão para a unidade, os eventos técnicos
que carregam seu contexto incluem `context.decisao` com exatamente um dos
valores `PUBLICAR`, `RETIFICAR`, `EXCLUIR_PCA` ou `NENHUMA_ACAO`. Esse campo
explica qual decisão orientou o processamento; ele não substitui `action`, que
continua identificando a operação técnica do evento, nem cria auditoria de
negócio.

## 3. Erro seguro

`action` identifica a operação e nunca é substituída pelo código da falha. Em
evento de erro, `error` preserva:

| Campo | Significado |
| --- | --- |
| `error.code` | Código seguro e estável da falha. |
| `error.message` | Mensagem sanitizada correspondente ao código. |

Código e mensagem não recebem texto externo como autoridade nem detalhes
internos sensíveis. A mensagem principal do envelope também permanece
sanitizada.

## 4. Proteção dos dados

`error`, `context` e `metadata` são mapas associativos sanitizados
recursivamente. Não são registrados credenciais, senhas, segredos, tokens ou
JWT, autorização, SQL ou consultas, dados de conexão, requisições, respostas ou
payloads integrais, headers, exceções, traces, stacks ou valores não
serializáveis.

A correlação necessária ao diagnóstico deve usar os campos próprios do
envelope e dados técnicos seguros, sem transportar conteúdo externo integral.

Quando uma publicação aceita pelo PNCP trouxer `Location` incompatível com o
contrato conhecido, o evento `PUBLICAR_PCA_PNCP` pode incluir apenas em
`metadata`:

- `locationCategoria`: classificação estável como `AUSENTE`, `MULTIPLO`,
  `VAZIO`, `RELATIVO_FORA_DO_CONTRATO`,
  `ABSOLUTO_HOST_OFICIAL_FORA_DO_CONTRATO` ou
  `ABSOLUTO_FORA_DO_CONTRATO`;
- `quantidadeLocations`: quantidade de valores recebidos no header.

Essa classificação auxilia o diagnóstico sem registrar URL, host não
autorizado, CNPJ, query string ou o valor bruto de qualquer header.

O backend não gera, normaliza ou substitui `correlationId`. A Blank encaminha o
`X-Request-ID` já gerado pelo servidor HTTP, e o acumulador aceita somente UUID
v4 válido, preservando sua representação byte a byte. Ausência ou formato
inválido interrompem o entrypoint antes da criação do logger; por isso, essa
falha pública possui correlação `null` e não produz evento técnico com identidade
fabricada.

Uma falha inesperada capturada pela fachada já construída usa a ação
`EXECUTAR_PUBLICACAO_PCA` e erro seguro `FALHA_INESPERADA`. O evento não recebe
o `Throwable`, e eventual falha do próprio logger não atravessa a fronteira
pública. Falhas de bootstrap anteriores à construção do logger também não
produzem evento técnico.

## 5. Imutabilidade e exportação

Os eventos preservam a ordem de emissão e não são alterados depois de criados.
Ao preparar uma cópia para exportação, `executionTimeMs` é acrescentado somente
ao `metadata` do último envelope da cópia. O evento original e seus metadados
permanecem inalterados.

A forma de exposição e persistência pela aplicação hospedeira permanece nas
decisões de integração e no Fluxo de Implementação; ela não modifica este
envelope.
