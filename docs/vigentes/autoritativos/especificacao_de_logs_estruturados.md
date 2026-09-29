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
| `message` | string | Mensagem técnica sanitizada; em falhas PNCP pode acrescentar o detalhe textual recebido do serviço. |
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

Para `PUBLICAR` e `RETIFICAR`, o evento `REGISTRAR_ITENS_PLANEJADOS` ocorre
antes do primeiro comando mutante e contém em `metadata` os arrays ordenados
`numerosItensAIncluir` e `numerosItensAExcluir`. O evento
`REGISTRAR_ITENS_CONFIRMADOS` contém `numerosItensIncluidos` e
`numerosItensExcluidos` somente após observação de convergência e confirmação da
transação Oracle. A confirmação descreve o estado observado, sem atribuir a
mudança à requisição quando a escrita foi rejeitada. Cada
número corresponde ao `ID_DEMANDA` enviado como `numeroItem` (`DI-001`). Em
`PUBLICAR`, a lista de exclusões é vazia; em `RETIFICAR`, as listas podem
representar somente inclusão ou somente exclusão. `NENHUMA_ACAO` e
`EXCLUIR_PCA` não geram esses eventos de itens.

Cada chamada efetivamente iniciada no transporte cURL gera
`EXECUTAR_REQUISICAO_HTTP_PNCP` com `metadata.metodo` e
`metadata.duracaoTentativaMs` (relógio monotônico, milissegundos). Se cURL
retornar errno não zero, o evento inclui `metadata.curl_errno`. Uma falha
recebe `metadata.categoriaFalha`: `CURL`, `TLS`, `LIMITE_RESPOSTA`,
`RESPOSTA_INVALIDA`, `TRANSPORTE_LOCAL`, `HTTP_3XX`, `HTTP_4XX` ou `HTTP_5XX`.
`metadata.status_http` aparece somente quando o transporte recebeu uma
resposta HTTP com código válido. Um 403 é resposta HTTP; timeout sem resposta
não recebe `status_http`. Falhas anteriores ao início da tentativa não geram
este evento.
As categorias `HTTP_3XX`, `HTTP_4XX` e `HTTP_5XX` classificam apenas a faixa
do status; a interpretação da operação pertence ao adaptador PNCP.

Para distinguir as chamadas sem multiplicar valores de `action`, o mesmo
evento inclui `metadata.ordemHttp`, `metadata.operacao` e
`metadata.ambientePncp`. `operacao` é uma classificação sem identificadores,
como `AUTENTICAR`, `CONSULTAR_CONSOLIDADO_PCA`, `CONSULTAR_ITENS_PCA` ou
`INCLUIR_ITENS_PCA`. Quando aplicável, o evento também contém tamanho e
SHA-256 dos corpos de requisição e resposta, presença de autorização,
SHA-256 do token opaco sem o prefixo `Bearer` e versão HTTP negociada. Esses
hashes permitem comparar bytes e sessões sem registrar o conteúdo original;
não são credenciais reutilizáveis.

Respostas `HTTP_3XX`, `HTTP_4XX` ou `HTTP_5XX`, exceto na operação
`AUTENTICAR`, acrescentam `metadata.diagnosticoHttp`. Esse objeto preserva a
URL efetiva, headers enviados e recebidos, corpos de requisição e resposta,
hashes, tamanhos, diagnóstico não reutilizável da sessão, informações de
conexão e um comando cURL reproduzível. `Authorization`,
`Proxy-Authorization`, `Cookie` e `Set-Cookie` são substituídos por
`[REDACTED]`; o Bearer nunca é persistido e o cURL contém somente um marcador
para preenchimento no terminal protegido. Corpos UTF-8 permanecem textuais;
conteúdo binário usa Base64. Respostas aceitas continuam registrando apenas os
metadados resumidos.

O corpo de erro devolvido pelo PNCP é preservado integralmente como evidência
do serviço externo confiável. Quando esse corpo JSON contém `message` ou
`mensagem`, o adaptador pode acrescentar o texto à mensagem segura apresentada
à Blank como `O PNCP informou: ...`. O código público permanece estável e a
decisão continua baseada no status HTTP e no contrato da operação, nunca na
comparação literal do texto externo.

Os arrays contêm apenas inteiros, sem payloads completos. Um erro `403` pode
preservar o evento planejado, mas não produz evento confirmado. A consulta
operacional por correlação usa `JSON_QUERY ... RETURNING CLOB` para não truncar
listas grandes.

Os arrays planejados pertencem apenas aos eventos técnicos. O resultado
público e a Blank exibem IDs somente para `RETIFICAR/CONCLUIDO`, com as duas
listas confirmadas e contagens correspondentes (`DI-010`). Uma publicação pode
registrar os IDs nos logs sem apresentá-los na Blank.

## 3. Erro seguro

`action` identifica a operação e nunca é substituída pelo código da falha. Em
evento de erro, `error` preserva:

| Campo | Significado |
| --- | --- |
| `error.code` | Código seguro e estável da falha. |
| `error.message` | Mensagem sanitizada correspondente ao código. |

O código não recebe texto externo e permanece estável. A mensagem principal e
`error.message` permanecem sanitizadas pelo acumulador, mas podem incluir o
detalhe textual confiável devolvido pelo PNCP. Esse detalhe explica a falha ao
operador; não se torna regra de negócio nem substitui o código.

## 4. Proteção dos dados

`error`, `context` e os metadados comuns são mapas associativos sanitizados
recursivamente. Não são registrados senhas, segredos, tokens reutilizáveis,
Authorization, cookies, SQL, exceções, traces, stacks ou valores não
serializáveis.

`metadata.diagnosticoHttp` é a exceção tipada destinada ao suporte interno. Ela
pode conter payload e resposta integrais de uma chamada PNCP não autenticadora
que falhou. O transporte redige credenciais e cookies antes de entregar o
objeto, e o acumulador repete a redação dos headers. O diagnóstico de JWT pode
preservar fingerprint, cabeçalho e claims decodificados, mas nunca o token ou
sua assinatura. Chamadas de autenticação e respostas aceitas não recebem esse
objeto. Por conter dados de negócio, `SEGURANCA_LOG` e seus exports devem
permanecer restritos aos desenvolvedores e ao suporte autorizado.

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
fabricada. A saída da fronteira inclui aviso público seguro para verificar a
configuração do servidor responsável por fornecer o `X-Request-ID`.

Uma falha inesperada capturada pela fachada já construída usa a ação
`EXECUTAR_PUBLICACAO_PCA` e erro seguro `FALHA_INESPERADA`. O evento não recebe
o `Throwable`, e eventual falha do próprio logger não atravessa a fronteira
pública. Falhas de bootstrap anteriores à construção do logger também não
produzem evento técnico. Quando já existe uma correlação válida, essas falhas
de inicialização produzem aviso público seguro para verificar a configuração e
os pré-requisitos do servidor, sem expor o detalhe interno.

## 5. Imutabilidade e exportação

Os eventos preservam a ordem de emissão e não são alterados depois de criados.
Ao preparar uma cópia para exportação, `executionTimeMs` é acrescentado somente
ao `metadata` do último envelope da cópia. O evento original e seus metadados
permanecem inalterados.

A forma de exposição e persistência pela aplicação hospedeira permanece nas
decisões de integração e no Fluxo de Implementação; ela não modifica este
envelope.
