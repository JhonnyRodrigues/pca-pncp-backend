# Sprint 5.7 — Plano refinado

## Resultado da revisão externa

Os ajustes 2 e 3 fazem sentido e serão incorporados. O ajuste 1 é parcialmente rejeitado porque conflita com o contrato existente.

- `localizarCandidatos()` não retorna `CandidatoPca`: esse tipo não existe. A Port exige `ResultadoBuscaCandidatosPca`, cujo resultado concluído contém exclusivamente `PcaObservado[]` ativos ([LeitorPncp.php](/C:/Users/jsilva/repositorios-semae/pca-pncp-backend/src/Application/Ports/LeitorPncp.php:14), [ResultadoBuscaCandidatosPca.php](/C:/Users/jsilva/repositorios-semae/pca-pncp-backend/src/Application/Results/ResultadoBuscaCandidatosPca.php:14)).
- `PcaObservado` é uma fotografia que contém sequencial e IDs de Demandas observados. Usar `PcaObservado::ativo($sequencial, [])` sem consultar os itens afirmaria incorretamente que o candidato tem zero itens ([PcaObservado.php](/C:/Users/jsilva/repositorios-semae/pca-pncp-backend/src/Domain/ValueObjects/PcaObservado.php:12)).
- Alterar a Port para introduzir um tipo próprio de candidato violaria os limites desta sprint.
- Decisão aprovada: completar primeiro a listagem por órgão/ano e carregar itens somente quando existir exatamente um candidato da UASG procurada. Zero candidatos não gera consulta de itens; múltiplos candidatos geram conflito sem carregar itens. Isso evita consultas N+1 desnecessárias e preserva fielmente o contrato atual.
- A busca continua estritamente diagnóstica. Os consumidores atuais verificam apenas se há candidatos e bloqueiam qualquer candidato sem vínculo, sem adotá-lo ([DecisorUnidadeSobExclusividade.php](/C:/Users/jsilva/repositorios-semae/pca-pncp-backend/src/Application/UseCases/PublicarPca/DecisorUnidadeSobExclusividade.php:172), [FinalizadorProcessamentoUnidadeInterno.php](/C:/Users/jsilva/repositorios-semae/pca-pncp-backend/src/Application/UseCases/PublicarPca/FinalizadorProcessamentoUnidadeInterno.php:289)).

## Contratos e decisões aplicáveis

Serão implementados em um adapter de Infrastructure os dois métodos existentes de `LeitorPncp`:

- `observarPcaConhecido(...): ResultadoObservacaoPca`;
- `localizarCandidatos(...): ResultadoBuscaCandidatosPca`.

Nenhuma alteração será feita em Domain, Application, Ports, Commands ou APIs públicas.

Consultas oficiais:

- `GET /v1/orgaos/{cnpj}/pca/{ano}/{sequencial}/consolidado`: verifica diretamente a existência e identidade do PCA conhecido.
- `GET /v1/orgaos/{cnpj}/pca/{ano}/{sequencial}/itens?pagina=N&tamanhoPagina=S`: observa completamente o pertencimento por `numeroItem`.
- `GET /v1/orgaos/{cnpj}/pca/{ano}/consolidado/unidades?pagina=N&tamanhoPagina=S`: localiza candidatos do órgão/ano, posteriormente filtrados pela UASG.

Esses caminhos e suas respostas `200`/`204` constam no [Manual PNCP 2.5](https://pncp.gov.br/manual/pt-br/latest/singlehtml/) e no [OpenAPI oficial](https://pncp.gov.br/pncp-api/v3/api-docs). O desenho também segue a exigência de duas observações para ausência definitiva em [DI-002](/C:/Users/jsilva/repositorios-semae/pca-pncp-backend/docs/vigentes/autoritativos/decisoes_de_integracao.md:159) e [DI-009](/C:/Users/jsilva/repositorios-semae/pca-pncp-backend/docs/vigentes/autoritativos/decisoes_de_integracao.md:765).

## Semântica das leituras

### Paginação

- O índice inicial é `1`; `pagina` e `tamanhoPagina` serão sempre explícitos.
- O tamanho da página, a profundidade JSON e o limite defensivo de páginas serão configurações obrigatórias, positivas e sem defaults inventados.
- As respostas oficiais são arrays sem `totalPages`, página atual ou indicador `last`.
- Uma página `200` com menos de `S` elementos, inclusive `[]`, encerra a leitura.
- Uma resposta `204` encerra aquela sequência paginada.
- Uma página `200` com exatamente `S` elementos exige a página seguinte.
- O adapter gerará apenas `1, 2, 3...`, impedindo salto do cliente.
- Como o servidor não informa o índice retornado, um salto interno do servidor não pode ser observado diretamente. A proteção possível será feita por fingerprint de página, identidades duplicadas, metadados contraditórios, limite de páginas e limite total de 1.000 itens.
- Lacunas entre valores de `numeroItem` são válidas e não representam salto de página.

### Coerência e conflito

Serão conflitos:

- CNPJ, ano, UASG ou sequencial válidos, mas divergentes do contexto consultado;
- mudança desses metadados entre páginas;
- página repetida, inclusive com registros já vistos;
- `numeroItem` duplicado;
- mais de 1.000 itens;
- candidato duplicado;
- mais de um sequencial para a mesma UASG;
- consulta direta indicando ausência enquanto a listagem completa contém a mesma UASG, com o mesmo ou outro sequencial.

Campos ausentes, com tipo inválido ou JSON inválido não serão convertidos em conflito de identidade: produzirão leitura inconclusiva.

### Identidade

- CNPJ identifica o órgão.
- Ano identifica o exercício do PCA.
- UASG, representada por `codigoUnidade`, identifica a unidade externa.
- `sequencialPca` identifica o recurso PNCP dentro do órgão/ano.
- `numeroItem` corresponde exclusivamente ao `ID_DEMANDA` e identifica pertencimento observado.
- `ID_DEMANDA` não identifica o PCA e suas lacunas serão preservadas.
- `numeroControlePNCP` não criará nem substituirá vínculo local.

### Significado contextual de HTTP 204

`204` não terá uma interpretação global:

- no endpoint consolidado de um sequencial, é a primeira evidência de que o PCA conhecido está ausente;
- na paginação de itens, indica fim ou ausência de itens naquela página; após um consolidado `200`, representa PCA ativo sem mais itens, não PCA ausente;
- na listagem por órgão/ano, encerra a listagem; somente o percurso completo e coerente permite concluir que não há candidato da UASG.

Essas interpretações são inferências conservadoras derivadas do contexto de cada endpoint e da exigência de duas observações da DI-009; nenhuma resposta vazia isolada comprovará ausência.

### Classificações

Ausência definitiva exige simultaneamente:

1. endpoint consolidado do sequencial conhecido respondendo `204`;
2. listagem completa e coerente por órgão/ano sem PCA da mesma UASG.

Serão inconclusivas:

- paginação interrompida;
- limite defensivo de páginas atingido;
- duas tentativas transitórias sem sucesso;
- resposta `200` vazia quando o endpoint exige objeto;
- JSON malformado, profundo demais ou com UTF-8 inválido;
- campo utilizado ausente ou com tipo inválido;
- impossibilidade de comprovar o encerramento da paginação.

Serão falhas sistêmicas:

- sessão concreta incompatível;
- `401` ou `403`;
- falha de configuração, inicialização, TLS ou limite de transporte;
- status permanente ou contratualmente inesperado, como `400`, `404`, `422` ou `500`.

## Sessão, retry e sanitização

A sessão da Application permanece opaca. O adapter aceitará `SessaoPncp`, exigirá internamente `SessaoPncpAutenticada` e obterá o token somente para montar `Authorization: Bearer ...`. O token não será persistido, retornado, registrado ou incluído em falhas ([SessaoPncpAutenticada.php](/C:/Users/jsilva/repositorios-semae/pca-pncp-backend/src/Infrastructure/Pncp/Autenticacao/SessaoPncpAutenticada.php:10)).

Retry de GET:

- serão no máximo duas tentativas totais: uma inicial e exatamente uma segunda;
- serão elegíveis `408`, `429`, `502`, `503`, `504` e somente `CURLE_OPERATION_TIMEDOUT` (`28`) entre as falhas cURL;
- os demais erros cURL serão permanentes/estruturais e não terão retry;
- se a segunda tentativa também terminar em condição transitória, o resultado será `INCONCLUSIVA`;
- se a segunda tentativa produzir outra condição, ela receberá sua classificação normal;
- `FalhaTransportePncpException` preservará internamente o errno seguro para que o adapter, e não o transporte, decida a política.

A escolha conservadora de apenas `CURLE_OPERATION_TIMEDOUT` segue o comportamento padrão documentado do retry do curl, que considera timeout transitório e exige opção adicional até para conexão recusada. O significado dos códigos permanece baseado na [documentação oficial da libcurl](https://curl.se/libcurl/c/libcurl-errors.html) e na [política oficial de retry do curl](https://curl.se/docs/manpage.html).

Será criada no Microciclo 5 uma única fronteira interna mínima, `TemporizadorPncp`, com capacidades para:

- obter o instante UTC atual;
- aguardar uma quantidade inteira de milissegundos.

Sua implementação de sistema poderá usar relógio real e `usleep`, mas a lógica do adapter receberá a abstração injetada. Os testes usarão um fake sem espera real.

`Retry-After`:

- aceitará `delay-seconds` inteiro não negativo ou `HTTP-date`, conforme a [RFC 9110](https://www.rfc-editor.org/rfc/rfc9110.html#section-10.2.3);
- data passada resultará em espera zero;
- valor válido será limitado ao teto configurado;
- header ausente, duplicado ou inválido usará a espera configurada;
- nenhum valor de resposta controlará espera acima do teto.

Permanecerão fora de logs e falhas:

- token, credenciais e `Authorization`;
- headers completos;
- corpo ou resposta completa;
- URL/caminho completo;
- CNPJ, UASG, sequencial e IDs de itens;
- dumps cURL ou texto devolvido pelo PNCP.

Só poderão ser registrados ação técnica, tentativa, página, quantidade, status HTTP e códigos seguros fixos.

## Microciclos Red → Green → Refactor

### Microciclo 1 — Adapter e caminhos seguros de uma página

Antes do Red será registrado, em linguagem simples:

- `observarPcaConhecido` consulta primeiro o consolidado do sequencial e, quando ele existe, a primeira página de itens;
- `localizarCandidatos` consulta a primeira página da listagem por órgão/ano;
- o Bearer é extraído apenas da implementação concreta da sessão e nunca exposto;
- `204` depende do endpoint e não prova sozinho ausência definitiva;
- os campos mínimos são `cnpj`, `anoPca`, `codigoUnidade`, `sequencialPca` e, apenas nos itens, `numeroItem`;
- candidatos não são adotados porque a busca apenas diagnostica e os consumidores existentes bloqueiam qualquer vínculo desconhecido;
- múltiplas páginas, retry, `Retry-After`, ausência definitiva completa e itens de candidatos continuarão deliberadamente incompletos.

Red:

- sessão concreta válida e sessão incompatível;
- Bearer correto, GET, timeout de leitura e caminhos exatos;
- consolidado `200` coerente + primeira página curta de itens;
- consolidado `200` + itens `204`, produzindo PCA ativo com zero itens;
- listagem de candidatos `204` ou página curta vazia;
- página cheia, candidato presente ou consolidado ausente falhando de modo fechado como `INCONCLUSIVA`;
- JSON/campos básicos inválidos e sanitização do token.

Green:

- criar configuração de leitura inicialmente apenas com tamanho de página e profundidade JSON;
- criar `LeitorPncpAdapter`;
- implementar apenas os caminhos conclusivos de uma página;
- manter deliberadamente inconclusivos os ramos que dependem de microciclos futuros.

Refactor:

- extrair somente métodos privados evidentes para GET autenticado, decodificação JSON e validação de identidade;
- não criar abstrações de paginação ou tempo ainda.

### Microciclo 2 — Paginação completa do PCA conhecido

Red:

- página curta, página cheia seguida de `204` e várias páginas;
- página repetida ou reordenada;
- `numeroItem` duplicado;
- CNPJ, ano, UASG ou sequencial divergente entre páginas;
- lacunas válidas em `numeroItem`;
- limite de 1.000 itens e limite configurado de páginas;
- interrupção sem fabricação de `PcaObservado` parcial.

Green:

- paginação sequencial completa dos itens;
- fingerprints e conjuntos de identidades;
- construção de `PcaObservado` somente depois do encerramento comprovado.

Refactor:

- consolidar o mecanismo privado de paginação necessário aos itens, sem gateway genérico.

### Microciclo 3 — Candidatos e fotografia do único candidato

Red:

- paginação completa por órgão/ano;
- registros de outras UASGs;
- identidade inválida ou divergente;
- página e candidato duplicados;
- nenhum candidato;
- exatamente um candidato da UASG;
- mais de um candidato da UASG;
- item inválido ou paginação incompleta do candidato único.

Green:

- validar todas as páginas e filtrar a UASG;
- retornar `CONCLUIDA([])` quando não houver candidato;
- retornar `CONFLITO` quando houver múltiplos candidatos;
- somente para candidato único, carregar completamente seus itens e produzir o `PcaObservado` exigido pelo contrato;
- nunca persistir, selecionar ou adotar o sequencial.

Refactor:

- reutilizar apenas a paginação de itens já comprovada no Microciclo 2.

### Microciclo 4 — Ausência definitiva e conflitos de vínculo

Red:

- consolidado `204` + listagem completa sem mesma UASG;
- consolidado `204` + mesma UASG/mesmo sequencial;
- consolidado `204` + mesma UASG/outro sequencial;
- falha ou interrupção em qualquer uma das duas consultas;
- consolidado `200` com zero itens;
- resposta vazia ou ambígua.

Green:

- implementar a prova de duas consultas;
- retornar `AUSENCIA_DEFINITIVA` somente na combinação autorizada;
- classificar divergências como conflito e falhas parciais como inconclusivas.

Refactor:

- tornar explícita a decisão de ausência sem criar política nas camadas internas.

### Microciclo 5 — Retry, tempo e observabilidade

Red:

- uma falha elegível seguida de sucesso;
- duas falhas elegíveis, sem terceira tentativa;
- `408`, `429`, `502`, `503`, `504` elegíveis;
- somente errno cURL `28` elegível;
- erros cURL permanentes sem retry;
- `Retry-After` em segundos, data futura, data passada, inválido, duplicado e acima do teto;
- espera e relógio determinísticos;
- `401`, `403` e falhas estruturais;
- eventos sanitizados por tentativa e página.

Green:

- adicionar configuração explícita de espera e teto;
- adicionar `TemporizadorPncp` e implementação de sistema;
- expor o errno seguro na exceção de transporte;
- implementar exatamente duas tentativas totais e a classificação final.

Refactor:

- remover duplicações reais da classificação de GET;
- revisar ações técnicas e garantir que permaneçam exclusivamente em Infrastructure.

## Validações

Ao final de cada microciclo serão executados, conforme aplicáveis:

- testes focados de `tests/Infrastructure/Pncp/Leitura`;
- `vendor\bin\phpunit tests\Infrastructure\Pncp`;
- `composer contract:tests`, que não executa `pncp-live`;
- `composer tests`;
- `php -l` nos arquivos afetados;
- validação PSR-4 estrita;
- `git diff --check`, inspeção integral do diff e `git status`;
- revisão de dependências, sanitização e compatibilidade sintática com PHP 8.1.

Não serão executados PNCP real, `pncp-live`, probes Oracle/PNCP, escrita externa ou integração Scriptcase. Não serão adicionadas dependências nem criados commits.

As alterações locais não rastreadas `.agents/sprints/planos/plano_sprint_5.7.md` e `.agents/sprints/prompts/prompt_sprint_5.7.md` permanecerão preservadas.

Ao final de cada microciclo serão apresentados o diff e somente as evidências efetivamente executadas, seguidos de pausa obrigatória. A aprovação explícita deste plano substitutivo liberará exclusivamente o Microciclo 1.
