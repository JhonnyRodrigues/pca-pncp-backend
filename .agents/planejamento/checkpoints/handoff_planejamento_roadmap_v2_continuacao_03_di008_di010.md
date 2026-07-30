# Handoff do planejamento do Roadmap V2 — Continuação 03

## Metadados do checkpoint

| Campo | Valor |
|---|---|
| Roadmap-alvo | V2 |
| Sequência do checkpoint | 03 |
| Escopo | Fechamento das DI-008, DI-009 e DI-010 |
| Estado do trabalho | Planejamento concluído; consolidação documental ainda não iniciada |
| Próximo ponto | Seção 17 do handoff principal |

### Handoffs predecessores

Este checkpoint complementa, sem substituir:

1. `.agents/planejamento/checkpoints/handoff_planejamento_roadmap_v2.md`;
2. `.agents/planejamento/checkpoints/handoff_planejamento_roadmap_v2_complementar_di006_di008.md`.

### Fontes independentes de conferência

O conteúdo foi reconciliado com as sínteses registradas pelo usuário ao final
de cada decisão:

1. `.agents/planejamento/sinteses/sintese_DI-008.md`;
2. `.agents/planejamento/sinteses/sintese_DI-009.md`;
3. `.agents/planejamento/sinteses/sintese_DI-010.md`.

As sínteses funcionam como evidência independente de completude. Elas não
substituem os handoffs predecessores nem alteram decisões já aprovadas.

---

## 1. Autoridade e finalidade

Este checkpoint:

- registra somente decisões posteriores aos dois handoffs predecessores;
- encerra o planejamento das DI-008, DI-009 e DI-010;
- não reabre DI-001 a DI-007;
- não autoriza implementação de código ou início antecipado da Infrastructure;
- define como próximo ponto a seção 17 do handoff principal.

Em caso de conflito, esta continuação prevalece apenas sobre:

- o estado anteriormente parcial da DI-008;
- a indicação de DI-009 e DI-010 como não iniciadas;
- as pendências efetivamente resolvidas neste checkpoint.

`DI` significa **Decisão de Integração**.

### Estado consolidado

| Decisão | Estado |
|---|---|
| DI-008 — Mecanismo Oracle | Aceita condicionalmente |
| DI-009 — Transporte HTTP | Aceita condicionalmente |
| DI-010 — Composition Root e Scriptcase | Aceita condicionalmente |

Aprovação condicional significa que a decisão está arquiteturalmente fechada,
mas sua implementação depende dos gates contratuais indicados. Falha em um gate
reabre somente a decisão afetada.

---

## 2. Delta da DI-008 — Mecanismo Oracle

As decisões já registradas no handoff complementar permanecem válidas. Esta
seção contém apenas os fechamentos posteriores.

### 2.1 Fotografia vigente das Demandas

Participam da fotografia vigente:

- `2 = HOMOLOGADA`;
- `3 = COMPRADA`;
- `5 = PUBLICADA`.

Não participam:

- `1 = PLANEJADA`;
- `4 = CANCELADA`.

As transições:

```text
HOMOLOGADA -> COMPRADA -> PUBLICADA
```

não removem a Demanda da fotografia e não geram exclusão no PNCP.

A justificativa de cancelamento continua sendo dado explicativo. Ela não
substitui o predicado estruturado de situação.

### 2.2 Versão vigente e reconstrução

Foi confirmado que:

- `PAC_PLANOS` recebe registros somente durante a homologação;
- `DATA_HOMOLOGACAO` é `NOT NULL` e possui `DEFAULT SYSDATE`;
- a versão vigente é a maior `VERSAO` para a combinação:
  - unidade orçamentária;
  - ano de exercício.

`DATA_PUBLICACAO` não participa da escolha da versão vigente.

A consulta autoritativa deverá:

1. partir de `PAC_PLANOS`, preservando Plano sem Demandas ativas;
2. selecionar a maior `VERSAO` da UO/exercício para os metadados vigentes;
3. localizar Demandas ligadas a qualquer versão de origem da mesma identidade;
4. usar o predicado equivalente a `SITUACAO IN (2, 3, 5)`;
5. não limitar Demandas à `FK_PLANO` da versão máxima.

Consequências:

- `FK_PLANO` registra a versão de origem da homologação;
- a fotografia reúne Demandas de todas as versões de origem da UO/exercício;
- o Plano vigente pode possuir zero Demandas ativas;
- a identidade lógica permanece UO + ano de execução.

### 2.3 Resolução da UASG

A associação prevista é:

```text
PAC_PLANOS.FK_UNIDADE_ORCAMENTARIA
    -> PAC_UNIDADES_ORCAMENTARIAS.FK_LOTACAO
```

Para cada UO referenciada deve existir exatamente um cadastro:

- `ATIVA = 'S'`;
- com UASG preenchida e válida.

O exercício pode conter várias UOs distintas. A cardinalidade é uma linha
ativa por UO referenciada, não uma única UO para todo o exercício.

Ausência, duplicidade ou UASG inválida bloqueia antes do PNCP. O join e os nomes
físicos continuam sujeitos ao gate do schema e não vazam para
Domain/Application.

### 2.4 Projeção operacional em `PAC_PLANOS`

A projeção operacional corrente conterá exatamente quatro fatos conceituais:

- modo anual adotado;
- UASG externa adotada;
- estado operacional do PCA;
- `sequencialPca`.

O modo será persistido semanticamente como:

- `INDIVIDUAL`;
- `CONSOLIDADO`.

O estado será persistido semanticamente como:

- `NAO_PUBLICADO`;
- `PUBLICADO`;
- `EXCLUIDO`.

Não serão usadas abreviações físicas como `N`, `P` ou `E`.

`DATA_PUBLICACAO` permanece separada como confirmação versionada da
convergência.

`numeroControlePNCP` não integra a projeção operacional mínima da V1. Permanece
metadado de integração até surgir necessidade de negócio documentada.

`PAC_PLANOS` é a fonte canônica exclusiva do `sequencialPca`. O eventual campo
equivalente em Demandas:

- não é lido;
- não é gravado;
- não é comparado;
- não é usado como fallback.

### 2.5 Chave técnica da exclusividade

A tabela técnica de âncoras permanentes usará chave estruturada por:

```text
ESCOPO + ANO_EXERCICIO + IDENTIFICADOR_ALVO
```

Haverá escopos distintos para:

- adoção da estratégia anual;
- processamento de cada unidade externa.

As demais decisões sobre provisionamento idempotente, transação técnica curta,
`SELECT ... FOR UPDATE NOWAIT`, contenção e liberação permanecem conforme o
handoff complementar.

### 2.6 Publicações legadas

Publicações legadas continuam sujeitas a migração/reconciliação assistida.

Não haverá:

- adoção automática de PCA desconhecido;
- backfill automático da projeção usando dados repetidos de Demandas.

### 2.7 Condições restantes

A DI-008 permanece condicionada a:

- gate ADOdb/OCI8 no Scriptcase real;
- confirmação do schema, joins, nomes físicos e DDL;
- testes de datas, decimais e constraints;
- comprovação de que todos os adapters compartilham a mesma sessão Oracle.

Conexão emprestada, fallback OCI8, transações, mecanismo geral do lock,
congelamento, propagação, datas, decimais e ownership arquitetural permanecem
regidos pelo handoff complementar e não são redefinidos aqui.

---

## 3. DI-009 — Transporte HTTP

Status: **Aceita condicionalmente**

### 3.1 Contrato oficial adotado

Durante a decisão foram adotados:

- manual PNCP vigente na versão 2.5;
- integração REST/HTTP com JSON;
- homologação:
  `https://treina.pncp.gov.br/api/pncp`;
- produção:
  `https://pncp.gov.br/api/pncp`;
- autenticação por login e senha;
- JWT retornado no header `Authorization`;
- validade documentada do token de uma hora;
- CNPJ alfanumérico de 14 posições aceito nos campos das APIs.

A versão do manual e do OpenAPI deverá ser registrada e revalidada no teste
contratual anterior à implementação definitiva.

### 3.2 Transporte HTTP

- Usar cURL nativo, sem nova dependência HTTP de runtime.
- Encapsular cURL em adapter da Infrastructure.
- Handles, constantes, códigos e mensagens cURL não chegam à Application.
- Cada chamada do transporte envia uma única requisição.
- Status, headers e corpo são capturados separadamente.
- Falha de transporte é distinta de resposta HTTP.
- O transporte não decide retry, reconciliação ou regra de negócio.
- Redirecionamentos automáticos ficam desabilitados.
- Respostas e payloads possuem limites de tamanho configurados.

O host Scriptcase deverá comprovar `ext-curl`, OpenSSL e CA bundle antes do
adapter definitivo.

### 3.3 Ambientes e URLs

- O ambiente é um tipo interno fechado:
  - `HOMOLOGACAO`;
  - `PRODUCAO`.
- Cada valor mapeia exclusivamente para sua URL oficial.
- Request e usuário não fornecem URL livre.
- Caminhos são montados como segmentos relativos e codificados com segurança.
- Testes unitários usam doubles do transporte sem liberar destinos arbitrários.

### 3.4 TLS

- Usar somente HTTPS.
- Verificar certificado e hostname.
- Usar a cadeia de autoridades certificadoras do ambiente.
- Permitir caminho configurável para o CA bundle.
- Falha de confiança TLS é falha sistêmica.

Não haverá:

- equivalente a `curl -k`;
- opção para desabilitar TLS;
- pinning de chave pública na V1.

### 3.5 Autenticação e token

- Autenticar uma vez por execução.
- Tratar JWT como valor opaco.
- Não decodificar claims para controlar expiração.
- Exigir header `Authorization` válido com esquema `Bearer`.
- Ler headers sem sensibilidade a maiúsculas/minúsculas.
- Não renovar nem realizar segunda autenticação na mesma execução.

O token:

- não é persistido;
- não é compartilhado entre execuções;
- não aparece em logs ou exceções.

Classificação:

- `401`: falha sistêmica de autenticação;
- `403`: autorização negada e falha sistêmica da execução.

A origem das credenciais é definida na DI-010.

### 3.6 Timeouts

Valores iniciais, configuráveis por ambiente:

- conexão: 5 segundos;
- login e leituras: 30 segundos;
- escritas: 60 segundos.

Regras:

- nenhum timeout pode ser infinito;
- configuração ausente ou inválida bloqueia o bootstrap;
- timeout de escrita significa resultado incerto;
- os limites protegem o tempo de retenção do lock Oracle.

### 3.7 Retry de leitura

- Permitido somente para GET.
- Máximo de duas tentativas totais.
- Elegível para:
  - falha transitória de transporte;
  - `408`;
  - `429`;
  - `502`;
  - `503`;
  - `504`.
- Usar espera curta e configurável.
- Respeitar `Retry-After` somente até um teto configurado.
- Registrar cada tentativa na observabilidade.

Login, POST e DELETE não reutilizam essa política. O transporte cURL nunca
possui retry genérico.

### 3.8 Leitura e paginação

- Enviar `pagina` e `tamanhoPagina` explicitamente.
- Paginar até comprovar o fim do conjunto.
- Definir o tamanho da página após teste contratual.
- Nunca decidir convergência com resultado parcial.

Validar entre todas as páginas:

- unicidade de `numeroItem`;
- ausência de páginas repetidas;
- coerência de CNPJ, ano, UASG e sequencial;
- limite total de 1000 itens.

Resposta paginada inconsistente ou acima de 1000 itens é bloqueante.

### 3.9 Ausência definitiva do PCA

A ausência definitiva de um PCA conhecido exige:

1. consulta pelo `sequencialPca` conhecido indicando ausência;
2. listagem paginada por órgão/ano sem PCA da mesma UASG.

Resultados:

- nenhuma ocorrência: ausência definitivamente comprovada;
- outro sequencial para a mesma UASG: conflito/migração assistida;
- respostas divergentes ou inconclusivas: não recriar;
- falha de qualquer consulta: ausência não comprovada.

A forma concreta da resposta de ausência deverá ser validada em homologação,
pois o manual não documenta claramente `404` para esses endpoints.

### 3.10 Leitura tolerante do JSON

- Ignorar campos adicionais desconhecidos.
- Exigir presença e tipo dos campos utilizados pelo sistema.
- Invalidar observações com valores contraditórios, duplicados ou ausentes.
- Rejeitar JSON malformado ou profundo demais.
- Exigir textos em UTF-8 válido.
- Não substituir caracteres inválidos silenciosamente.
- Não permitir que mensagens textuais do PNCP controlem o fluxo.
- Usar textos externos apenas para diagnóstico sanitizado.

### 3.11 Classificação das escritas

- Resposta documentada e semanticamente completa: `ACEITA`.
- `400` ou `422` válidos: `REJEITADA`.
- `401` ou `403`: falha sistêmica.
- Timeout, conexão interrompida após envio, `429`, `5xx`, resposta malformada
  ou sucesso incompleto: `RESULTADO_INCERTO`.

Escrita incerta nunca é repetida diretamente.

Após resultado incerto:

1. observar novamente;
2. comparar desejado e publicado;
3. recalcular;
4. permitir somente a reconciliação adicional aprovada na DI-004.

### 3.12 Ordem da retificação

Quando houver exclusões e inclusões:

1. excluir itens cancelados;
2. incluir itens novos;
3. observar novamente o PCA completo.

Consequências:

- evita ultrapassar temporariamente 1000 itens;
- remove primeiro itens que deixaram de ser vigentes;
- falha parcial pode deixar inclusões pendentes;
- convergência parcial produz `FALHA_RECONCILIAVEL`;
- não haverá compensação automática.

As operações serão em lote. Não haverá particionamento acima de 1000 itens na
V1.

### 3.13 Justificativas

Exclusão de itens com motivos diferentes:

- usa um único DELETE em lote;
- usa mensagem padronizada;
- informa cancelamentos homologados no SGI e a quantidade de itens;
- mantém os motivos completos nas Demandas locais.

Não concatenar nem truncar motivos.

- A Application constrói a justificativa.
- A Infrastructure apenas serializa.
- A exclusão integral do PCA usa o motivo do cancelamento que retirou o último
  item ativo.

### 3.14 Decimais, datas e JSON

- Manter representação decimal exata.
- Nunca converter para `float`.
- Usar marcador interno específico para números JSON exatos.
- Emitir token numérico sem aspas somente após validação lexical.
- Não aceitar fragmentos JSON arbitrários.
- Aplicar `HALF_UP` somente na borda PNCP.
- Preservar `valorTotal` e `valorOrcamentoExercicio` como autoritativos.
- Não recalcular totais a partir do unitário arredondado.
- Serializar datas puras como `YYYY-MM-DD`.

### 3.15 `numeroItem`

Manter:

```text
ID_DEMANDA -> numeroItem
```

- Não criar mapeamento alternativo antecipadamente.
- Testar IDs reais, únicos e com lacunas.
- Bloquear produção até homologação confirmar a aceitação.
- Rejeição concreta reabre DI-001 e DI-002.

### 3.16 Observabilidade e segurança

Registrar por PSR-3:

- ambiente;
- operação lógica;
- método e caminho sanitizado;
- duração;
- tentativa;
- status HTTP;
- categoria do erro;
- resultado da classificação;
- identificador de execução;
- unidade externa.

Nunca registrar:

- login e senha;
- JWT;
- header `Authorization`;
- payload completo;
- resposta completa contendo dados sensíveis;
- handles ou dumps cURL.

### 3.17 Suíte de contrato PNCP

A suíte será:

- PHPUnit opt-in;
- separada da suíte unitária;
- executada somente por comando explícito;
- alimentada por credenciais externas;
- associada a CNPJ, UASG e exercício dedicados;
- serial;
- estruturalmente proibida contra produção.

O preflight confirma:

- ambiente `HOMOLOGACAO`;
- host `treina.pncp.gov.br`.

Política de massa:

- observar o estado externo antes de qualquer escrita;
- reconciliar conscientemente resíduos anteriores;
- executar limpeza final best-effort;
- registrar diagnóstico quando não for possível limpar.

A suíte deverá comprovar:

- autenticação e header JWT;
- método real do login;
- códigos e formato de ausência;
- paginação e tamanho aceito;
- headers `Location`;
- extração do `sequencialPca`;
- CNPJ alfanumérico;
- `ID_DEMANDA` com lacunas;
- decimal exato como número JSON;
- diferença entre total autoritativo e produto arredondado;
- classificação de falhas de leitura e escrita;
- comportamento após timeout ou resultado incerto.

### 3.18 Parâmetros sem default decidido

Permanecem configuráveis e sujeitos a testes/gates, sem valor inventado neste
checkpoint:

- tamanho máximo de resposta;
- tamanho máximo de payload;
- espera curta entre tentativas;
- teto de `Retry-After`;
- tamanho da página;
- profundidade máxima do JSON.

### 3.19 Gates condicionantes

Antes da Infrastructure definitiva:

- validar `ext-curl`, OpenSSL e CA bundle no Scriptcase;
- corrigir o carregamento duplicado de cURL no WSL;
- alinhar o runtime efetivo com a versão PHP suportada pelo projeto;
- executar a suíte opt-in no PNCP de treinamento;
- registrar versão do manual/OpenAPI, data e resultados observados;
- reabrir somente a decisão cujo contrato real seja incompatível.

---

## 4. DI-010 — Composition Root e integração com Scriptcase

Status: **Aceita condicionalmente**

### 4.1 Princípio

O backend será um produto independente, consumido em processo pela aplicação
Blank.

- O Composition Root pertence ao backend.
- O wiring é manual por construtores.
- Não usar container nem Service Locator.
- O Scriptcase carrega o autoloader e chama uma única fachada pública.
- A integração Scriptcase/backend é PHP em processo, não HTTP.

### 4.2 Fronteira pública

A fachada recebe como contexto externo:

- conexão `$this->Db` emprestada;
- login autenticado confiável;
- `SERVER_NAME`;
- ano selecionado.

O Scriptcase não conhece:

- Repository concreto;
- cliente PNCP;
- token;
- política de transação;
- montagem do Use Case.

### 4.3 Request e contexto

O `PublicarPcaRequest` contém somente:

- `Cnpj`;
- `AnoExecucao`.

Não contém:

- ambiente;
- URL;
- credenciais;
- login;
- senha;
- token.

Na implantação atual, o CNPJ vem do perfil autorizado, mas permanece explícito
no contrato do Use Case. Uma futura implantação multi-CNPJ poderá resolvê-lo
por catálogo autorizado sem alterar esse contrato.

O login é vinculado ao grafo da execução. O adapter de contexto resolve
representante e lotação pela porta já aprovada na DI-006.

### 4.4 Controller e Presenter

Controller:

- recebe entrada tipada;
- constrói o Request;
- chama o Use Case já montado;
- entrega a Response ao Presenter;
- não acessa macros;
- não realiza wiring;
- não contém regra de negócio.

Presenter:

- produz ViewModel estruturado;
- não gera JSON;
- não gera HTML;
- não usa macros Scriptcase.

O Blank apenas renderiza o ViewModel.

### 4.5 Configuração da implantação

`SERVER_NAME` apenas seleciona, por allowlist exata, um perfil conhecido.

O perfil externo declara explicitamente:

- `HOMOLOGACAO` ou `PRODUCAO`;
- CNPJ autorizado;
- credenciais PNCP;
- demais parâmetros da implantação.

Regras:

- não inferir ambiente pelo hostname;
- não concatenar hostname em caminho;
- não usar `HTTP_HOST` livre;
- perfil desconhecido, ausente ou incoerente falha fechado;
- nunca aplicar fallback silencioso para produção;
- usar na V1 um arquivo externo por perfil;
- manter perfis e segredos fora do repositório e do webroot;
- não suportar simultaneamente `.env` e arquivo externo na V1.

### 4.6 Ciclo de vida

Cada chamada do Blank:

- cria um grafo novo;
- cria uma autenticação PNCP;
- cria uma sessão PNCP opaca;
- compartilha uma conexão Oracle entre os adapters;
- descarta token e recursos HTTP ao terminar.

A conexão do Scriptcase:

- é emprestada;
- nunca é fechada pelo backend.

Se o gate ADOdb falhar:

- usar o fallback OCI8 próprio já aprovado;
- o backend passa a possuir e encerrar essa conexão.

### 4.7 Execução síncrona e concorrência

- A V1 é síncrona.
- Não criar fila, worker, polling ou persistência de progresso.
- O Blank aguarda o ViewModel final.
- A tela bloqueia nova submissão enquanto aguarda.
- Concorrência residual usa locks unitários e reconciliação.
- Não criar lock global de execução.
- Não criar chave de idempotência persistida na V1.

### 4.8 Autorização

- O módulo de segurança do Scriptcase autoriza acesso à aplicação Blank.
- O backend exige identidade autenticada.
- Representante e lotação devem ser resolvíveis.
- Não criar papéis ou porta adicional de autorização sem regra documentada.

### 4.9 Resposta global

Estados:

- `SOLICITACAO_INVALIDA`;
- `CONCLUIDA`;
- `CONCLUIDA_COM_PENDENCIAS`;
- `INTERROMPIDA`.

Regras determinísticas:

- entrada inválida produz `SOLICITACAO_INVALIDA`;
- conclusão integral apenas com `CONCLUIDO` ou `SEM_ALTERACAO` produz
  `CONCLUIDA`;
- varredura concluída com `FALHA_RECONCILIAVEL` ou `BLOQUEADO` produz
  `CONCLUIDA_COM_PENDENCIAS`;
- falha sistêmica que encerra o fluxo antes da conclusão produz
  `INTERROMPIDA`.

### 4.10 Resultado por unidade externa

Estados já aprovados:

- `CONCLUIDO`;
- `SEM_ALTERACAO`;
- `FALHA_RECONCILIAVEL`;
- `BLOQUEADO`.

Cada resultado preserva:

- identidade externa;
- modo;
- UASG;
- ação;
- `sequencialPca`, quando conhecido;
- contagens relevantes;
- código e mensagem seguros;
- correlação de diagnóstico.

No modo individual há normalmente um resultado por UO/PCA. No modo consolidado
há um único resultado para o PCA conjunto, conforme a DI-007.

### 4.11 Interrupção e exceções

Em interrupção sistêmica:

- preservar resultados já conhecidos;
- informar quantidades planejada, processada e não processada;
- não fabricar resultados para unidades não visitadas.

Nenhuma exceção atravessa a fachada.

Falhas inesperadas:

- recebem correlação;
- são registradas com proteção de segredos;
- são convertidas em ViewModel seguro.

O ViewModel nunca expõe:

- stack trace;
- payload;
- SQL;
- login ou senha;
- JWT;
- header `Authorization`;
- detalhes internos de cURL, ADOdb ou OCI8.

### 4.12 Distribuição

- Distribuir release externa autocontida com código e `vendor/`.
- Não executar Composer em produção.
- Manter perfis e segredos externos ao artefato.
- O Scriptcase conhece somente autoloader e fachada pública.
- Preservar independência da estrutura interna do Scriptcase.
- Permitir reutilização futura por outras autarquias.

### 4.13 Testes e critérios de aceitação

Testar:

1. seleção allowlisted do perfil e falha fechada;
2. ausência de inferência do ambiente pelo hostname;
3. construção do Request com CNPJ configurado e ano informado;
4. resolução de representante e lotação pelo login vinculado ao bootstrap;
5. wiring manual e compartilhamento da mesma conexão;
6. conexão emprestada nunca fechada;
7. uma autenticação por execução;
8. todos os estados globais e unitários;
9. sanitização de exceções e segredos;
10. preservação de resultados após interrupção sistêmica;
11. integração real com a aplicação Blank;
12. pacote autocontido em homologação antes da produção.

### 4.14 Gates condicionantes

A DI-010 fica condicionada a:

- teste contratual da fachada no Scriptcase real;
- gate da conexão emprestada;
- validação da identidade autenticada e de seu contexto;
- smoke test do pacote autocontido em homologação.

---

## 5. Ponto exato de retomada

O planejamento das DI-001 a DI-010 está encerrado nos termos dos três
checkpoints.

Nenhum código ou documento canônico deve ser implementado a partir deste
checkpoint isoladamente.

O próximo passo autorizado é executar a seção 17 do handoff principal:

## 17. Refatorações documentais e arquiteturais antes da Infrastructure

Esta ordem foi revisada posteriormente no handoff principal para respeitar a
dependência natural entre regras, modelo, fluxo e roadmap. A seção 17 do
handoff principal contém a justificativa e os critérios de saída completos.

Ordem vigente:

1. Criar e preencher
   `docs/vigentes/autoritativos/regras_de_negocio.md`. **Concluído.**
2. Criar e preencher
   `docs/vigentes/autoritativos/decisoes_de_integracao.md`. **Concluído.**
3. Atualizar
   `docs/vigentes/autoritativos/modelo_de_dominio.md`. **Concluído.**

Como refinamento documental descoberto após a consolidação do modelo e das DI,
foi incorporado o seguinte gate de coerência, sem renumerar as etapas
originais:

**Gate 3A.** Revisar
`docs/vigentes/apoio/arquitetura.md`. **Concluído.**

- alinhar responsabilidades e direção das dependências ao modelo e às DI;
- corrigir ownership das portas;
- explicitar fachada, Composition Root e fronteira Scriptcase;
- distinguir arquitetura-alvo do código atual;
- manter o documento como apoio, sem criar regras ou decisões concorrentes.

Como segundo refinamento documental anterior ao fluxo, foi incorporado o gate:

**Gate 3B.** Revisar e integrar
`docs/vigentes/apoio/glossario.md`. **Concluído.**

- consolidar linguagem de negócio, domínio, integração e arquitetura;
- remover termos antigos e definições concorrentes;
- referenciar as fontes autoritativas;
- preservar o caráter exclusivamente de apoio.

Como terceiro refinamento documental anterior ao fluxo, foi incorporado o
seguinte gate de governança, sem renumerar as etapas originais:

**Gate 3C.** Consolidar a governança operacional dos agentes. **Concluído.**

- arquivar o prompt inicial reutilizável como registro histórico;
- manter `AGENTS.md` como única instrução operacional permanente;
- alinhar suas orientações à arquitetura e à governança documental vigentes;
- preservar a finalidade didática por meio de feedback adaptativo e
  proporcional à tarefa;
- distinguir instruções, checkpoints, sínteses e prompts históricos.

4. Criar `docs/vigentes/autoritativos/fluxo_de_implementacao.md`
   (planejado, ainda não criado).
5. Consolidar
   `docs/vigentes/apoio/contexto_do_planejamento_do_projeto_backend.md` e
   arquivá-lo.
6. Preservar a V1 em `docs/historico/roadmap_v1_legado.md`.
7. Criar a V2 ativa em `docs/vigentes/autoritativos/roadmap.md`
   (planejado, ainda não criado).
8. Executar a sprint de refatoração arquitetural com TDD.
9. Completar a Application com mocks.
10. Somente então iniciar Infrastructure.

### Restrições de retomada

- Executar a seção 17 na ordem definida.
- Não iniciar Infrastructure antes dos passos documentais, da refatoração e do
  gate da Application.
- Não transformar parâmetros ainda sujeitos a teste em defaults inventados.
- Reabrir somente decisões afetadas por evidência contratual incompatível.

Este checkpoint não implementa código, não altera a documentação canônica e não
inicia Infrastructure. Sua única materialização é este arquivo de handoff.
