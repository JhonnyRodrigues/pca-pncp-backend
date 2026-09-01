# Estado operacional do projeto PCA/PNCP

**Classificação documental:** Operacional

**Status:** Vigente

**Autoridade/Escopo:** progresso do projeto, última sprint concluída, próxima
sprint na ordem, gates, pendências e restrições operacionais

**Atualizado em:** 01/09/2026

## Limites de autoridade

Este documento centraliza somente o estado corrente do trabalho. Ele não cria
nem altera regras de negócio, modelo de domínio, decisões de integração,
arquitetura, fluxo de implementação, ordem das sprints ou critérios de saída.

Para esses assuntos, prevalecem as fontes autoritativas registradas no
[índice documental](../../README.md). A ordem de construção permanece definida
pelo [Roadmap](../autoritativos/roadmap.md).

## Progresso

- A Fase 4 — Application está concluída.
- A Sprint 5.1 — Gates contratuais Oracle e PNCP está concluída.
- A Sprint 5.2 — Sessão Oracle, unidade de trabalho, estratégia e locks está
  concluída.
- A Sprint 5.3 — Mapeamento e PlanoRepository está concluída.
- A Sprint 5.4 — Estado operacional, contexto, auditoria e eventos técnicos
  está concluída.
- A Sprint 5.5 — Transporte cURL, TLS e autenticação está concluída.
- A Sprint 5.6 — Serialização e payloads exatos está concluída.
- A Sprint 5.7 — Observação, paginação e ausência definitiva está concluída.
- A Sprint 5.8 — Escritas e resultados incertos está concluída.
- A última sprint concluída é a Sprint 7.1.
- A Sprint 5.9 — Suíte de contratos dos adapters está concluída.
- A suíte controlada e os runners opt-in foram implementados. O gate PNCP foi
  aprovado no treinamento, incluindo publicação, operações de itens, exclusão
  integral, ausência definitiva, TLS e eventos sanitizados. O gate Oracle foi
  aprovado no Scriptcase real nos modos `adapters`, `lock_holder`,
  `lock_expect_blocked` e `lock_expect_available`.
- A Sprint 5.10 — Simplificação da justificativa e encerramento da
  Infrastructure está concluída. A investigação Oracle real foi encerrada sem
  execução porque `RN-PUB-003` passou a exigir justificativa institucional fixa.
- Todas as nove Ports vigentes possuem adapter concreto. A DI-008 foi aceita
  após os gates reais e a auditoria final.
- A Fase 5 — Infrastructure concreta está concluída.
- A Sprint 6.1 — Grafo manual sem Presentation está concluída.
- A Sprint 6.2 — Integração controlada dos adapters está concluída.
- A Sprint 6.3 — Gate do núcleo executável está concluída.
- A Fase 6 — Wiring controlado do núcleo está concluída.
- A Sprint 7.1 — Controller e entrada tipada está concluída. A Fase 7 está em
  andamento; a Sprint 7.2 — Presenter e ViewModels é a próxima etapa e depende
  de planejamento e autorização próprios.

## Evidências da Sprint 7.1

- `EntradaPublicarPca` recebe somente CNPJ textual e ano inteiro, enquanto
  `PublicarPcaController` materializa os Value Objects, cria a Request válida e
  delega ao contrato `PublicadorPca`, implementado pelo UseCase existente.
- CNPJ ou ano inválido impedem a chamada ao UseCase e propagam suas exceções
  específicas até a futura fachada. A conversão centralizada para
  `SOLICITACAO_INVALIDA` permanece na Sprint 7.3.
- Presentation aprovou 7 testes e 18 asserções; Application aprovou 237 testes
  e 978 asserções; Composition aprovou 1 teste e 1 asserção; Integration
  aprovou 3 testes e 70 asserções.
- A suíte padrão aprovou 818 testes e 3.017 asserções. O autoload PSR-4 estrito
  gerou 1.746 classes, o lint aprovou 278 arquivos PHP e `git diff --check` foi
  aprovado.
- Domain e Application não dependem de Presentation. Presentation não depende
  de Infrastructure ou Composition e não contém macros, wiring, container,
  Service Locator ou tratamento de exceções.
- Nenhum preflight, grupo `pncp-live`, probe ou acesso externo foi executado.

## Evidências da Sprint 6.3

- Composition aprovou 1 teste e 1 asserção; Integration aprovou 3 testes e 70
  asserções, preservando convergência sem escrita, retificação e falha final
  reconciliável sobre ADOdb e cURL controlados.
- Domain aprovou 94 testes e 146 asserções; Application aprovou 237 testes e
  978 asserções.
- A auditoria encontrou nove Ports e nove implementações concretas. Domain não
  depende das camadas externas; Application não depende de Infrastructure,
  Composition ou Presentation; o montador intermediário depende somente de
  Application, Domain e PSR-3.
- Não existem Presentation, container, Service Locator, configuração global no
  montador ou aliases das antigas Ports PNCP. O núcleo foi construído e
  executado sem a fronteira pública futura.
- No gate completo, a suíte padrão aprovou 811 testes e 2.999 asserções;
  adapters, contratos controlados legados e contratos controlados dos adapters
  aprovaram, respectivamente, 476/1.804, 24/51 e 54/149 testes/asserções.
- O autoload PSR-4 estrito gerou 1.741 classes, o lint aprovou 268 arquivos PHP
  e `git diff --check` foi aprovado. Nenhum preflight, grupo `pncp-live`, probe
  ou acesso externo foi executado.

## Evidências da Sprint 6.2

- `tests/Integration/Composition/` monta os seis adapters Oracle sobre uma
  única `ConexaoAdodbEmprestada` e os três adapters PNCP sobre um único
  `TransporteCurlPncp`, usando o `MontadorNucleoPublicacaoPca`.
- O caminho individual já convergente produz `NENHUMA_ACAO`, autentica uma vez,
  observa identidade e itens pelo transporte concreto, confirma as unidades de
  estratégia e publicação e não escreve no PNCP.
- A retificação exclui o item `1001`, inclui o item `1002`, reutiliza o mesmo
  Bearer, comprova a convergência final, confirma o Plano, registra auditoria e
  somente então confirma a unidade. O estado `PUBLICADO(77)` não é persistido
  novamente porque permaneceu inalterado.
- O caminho inconclusivo esgota duas respostas transitórias sem espera real,
  produz `CONCLUIDA_COM_PENDENCIAS` e `FALHA_RECONCILIAVEL` e desfaz a unidade
  sem confirmação de Plano, estado operacional ou auditoria.
- O ADOdb e o executor cURL controlados exigem filas ordenadas, compartilham um
  registro cronológico sanitizado e impedem Oracle, PNCP ou Scriptcase reais. A
  conexão emprestada não é fechada em nenhum cenário.
- A suíte de integração aprovou 3 testes e 70 asserções; Integration,
  Composition e Application aprovaram 241 testes e 1.049 asserções durante os
  microciclos de implementação.
- No gate final, a suíte padrão aprovou 811 testes e 2.999 asserções; adapters,
  contratos controlados legados e contratos controlados dos adapters aprovaram,
  respectivamente, 476/1.804, 24/51 e 54/149 testes/asserções. O autoload PSR-4
  estrito gerou 1.741 classes, e o lint aprovou 268 arquivos PHP.

## Evidências da Sprint 6.1

- `MontadorNucleoPublicacaoPca` recebe por construtor as nove Ports vigentes e
  o logger PSR-3 e devolve `PublicarPcaUseCase` com sua topologia interna
  composta explicitamente.
- `FluxoConcretoApplicationTest` usa o montador e preserva os cinco cenários de
  publicação, retificação, exclusão e consolidação, sem acessar Infrastructure.
- O wiring intermediário não seleciona perfis, não instancia recursos ou
  adapters concretos e não depende de Presentation, container ou Service
  Locator.
- A suíte padrão aprovou 808 testes e 2.929 asserções; o autoload PSR-4 estrito
  gerou 1.735 classes, e o lint aprovou os 262 arquivos PHP de `src/` e
  `tests/`.

## Evidências da Sprint 5.10

- `EXCLUIR_PCA` preserva o gatilho da fotografia vigente vazia com PCA externo
  ativo e envia a justificativa fixa `Exclusão integral do PCA por ausência de
  demandas vigentes no SGI.` nos modos individual e consolidado.
- A Port, o resultado, o enum, o Value Object e a suíte investigativa da fonte
  histórica foram removidos sem alterar contratos PNCP, adapters ou APIs
  públicas.
- A auditoria encontrou nove Ports vigentes e suas nove implementações
  concretas. Nenhuma capacidade de Infrastructure permanece sem adapter.
- A suíte Application aprovou 237 testes e 978 asserções; adapters, contratos
  controlados da Sprint 5.9, contratos legados e suíte padrão aprovaram,
  respectivamente, 476/1.804, 54/149, 24/51 e 807/2.928 testes/asserções.
- O autoload PSR-4 estrito gerou 1.733 classes. Lint, catálogo, referências e
  `git diff --check` foram aprovados em PHP 8.4.7.

## Evidências da Sprint 5.9

- `tests/Contract/Sprint59/` contém uma suíte separada e opt-in. Ela não é
  descoberta por `phpunit.xml` e não altera os contratos legados da Sprint 5.1.
- `PCA_SPRINT59_CONFIG_FILE` aponta para configuração externa ao repositório.
  O preflight falha fechado sem autorização explícita de escrita, recusa URL
  PNCP configurável, valida o ambiente canônico de homologação e instancia os
  mapeamentos Oracle de produção antes de qualquer I/O externo.
- O runner PNCP monta `TransporteCurlPncp`, serializer, autenticador, leitor,
  escritor e acumulador de eventos concretos. Seu fluxo recusa candidato
  desconhecido, publica IDs lacunados e decimais exatos, observa, inclui,
  exclui item, exclui o PCA e só confirma após ausência definitiva. Resíduo
  explicitamente autorizado agora é tratado por limpeza isolada; enquanto a
  configuração o indicar, o fluxo normal recusa nova publicação.
- A base de transporte permanece `https://treina.pncp.gov.br/api/pncp`. O
  parser de `Location` reconhece também o prefixo `/pncp-api` comprovadamente
  emitido pelo mesmo host HTTPS de homologação, sem generalizá-lo para produção
  ou aceitar URL externa configurável.
- O entrypoint exclusivo da suíte
  `executar_contratos_adapters_oracle_sprint_59($db, $login, $mode)` possui os
  modos `adapters`, `lock_holder`, `lock_expect_blocked` e
  `lock_expect_available`. Ele usa uma única `ConexaoAdodbEmprestada` e os
  adapters concretos de unidade de trabalho, estratégia, Plano, estado,
  contexto e auditoria.
- O modo Oracle principal verifica binds, mesma sessão, leituras tipadas,
  visibilidade transacional, confirmação/estado/auditoria atômicos, restauração
  por rollback e reutilização posterior da conexão. Uma guarda impede e detecta
  tentativa de `Close()` antes que alcance a conexão emprestada.
- O estado operacional usa exclusivamente `SGI.PAC_PLANOS`. A estratégia
  (`ESTRATEGIA_ADOTADA_PUBLICACAO` e `UASG_ADOTADA`) é apenas verificada pela
  leitura; a gravação altera somente `ESTADO_PUBLICACAO` e
  `SEQUENCIAL_PUBLICACAO_PNCP`. O sequencial externo positivo é preservado no
  estado `EXCLUIDO`, e `SGI.PAC_CONTROLES_CONCORRENCIA` permanece restrita à
  exclusividade técnica. As duas colunas de estado foram exercitadas no gate
  Oracle/Scriptcase real; seu provisionamento por DDL permanece uma
  responsabilidade operacional externa ao backend.
- A suíte controlada aprovou 54 testes e 149 asserções; a suíte dos adapters
  aprovou 468 testes e 1.779 asserções; os 24 contratos legados e suas 51
  asserções e a suíte padrão com 807 testes e 2.924 asserções permaneceram
  verdes. O autoload PSR-4 estrito gerou 1.734 classes, lint dos arquivos PHP
  afetados e `git diff --check` foram aprovados. As execuções usaram PHP 8.4.7.
- O relatório investigativo sanitizado está em
  [Evidências da Sprint 5.9](../../investigacoes/evidencias_parciais_sprint_5_9_contratos_adapters_2026-08-26.md).

O resíduo PCA 24 teve ausência definitiva confirmada sem nova publicação. Em
seguida, o gate PNCP executou o ciclo real completo e aprovou limpeza e eventos
sanitizados. O relatório externo produziu o hash SHA-256
`a2043402655b87762b2c3b7677cdee333e948961c3993bca2f28a3ccb30e7344`.
O gate Oracle foi aprovado nos quatro modos contratuais. O arquivo externo
agregado produziu o hash SHA-256
`6a5f63d50fbd2af121700027dc5a00735bc264cff4dfbe54bbf1cb1fa5c87648` e não
expôs credenciais, CNPJ, URL, token ou `Authorization`.

## Evidências da Sprint 5.8

- `EscritorPncpAdapter` implementa as quatro operações da Port existente sem
  alterar Domain, Application, Ports, Commands, Results ou APIs públicas.
- Publicação e inclusão enviam `POST`; exclusão de itens e exclusão integral
  enviam `DELETE`. Cada método usa o Bearer da sessão opaca, JSON UTF-8,
  timeout de escrita e exatamente uma execução do transporte.
- Publicação aceita somente `200` ou `201` e exige um único `Location`
  canônico, coerente com ambiente, CNPJ e ano e com sequencial positivo, sem
  fallback pelo corpo. Inclusão aceita somente `200` ou `201`; exclusões,
  somente `200`.
- Respostas `400` e `422` são rejeitadas; `401` e `403` são falhas sistêmicas;
  status transitórios, `5xx` e qualquer status não aprovado produzem resultado
  incerto.
- Falhas locais de preparação, sessão, payload ou TLS são sistêmicas. Timeout,
  interrupção de execução, resposta excessiva ou inválida e falha inesperada
  depois do despacho produzem resultado incerto, sem retry.
- Inclusões e exclusões enviam a coleção integral em uma única requisição, sem
  particionamento. A Application continua responsável pelo limite de 1.000
  itens e pela ordem excluir antes de incluir.
- Eventos usam ações técnicas próprias, preservam `FalhaSegura` em
  `error.code` e `error.message` e registram somente metadados técnicos seguros.
  Token, Authorization, headers, corpos, URLs, CNPJ, UASG, sequencial e IDs não
  são registrados.
- O contrato institucional do envelope foi consolidado na
  [Especificação de logs estruturados](../autoritativos/especificacao_de_logs_estruturados.md),
  com referências enxutas na DI-006 e no Fluxo de Implementação.
- A suíte focada de escrita aprovou 131 testes e 575 asserções.
- A regressão de `Infrastructure/Pncp` aprovou 326 testes e 1.059 asserções.
- A regressão de serialização PNCP aprovou 88 testes e 149 asserções.
- A suíte contratual não real aprovou 24 testes e 51 asserções.
- A suíte padrão aprovou 799 testes e 2.865 asserções.
- Lint dos 6 arquivos PHP envolvidos, autoload otimizado com PSR-4 estrito
  contendo 1.727 classes e `git diff --check` foram aprovados.
- As execuções usaram PHP 8.4.7. A implementação foi inspecionada para manter
  sintaxe e APIs compatíveis com PHP 8.1, mas não havia runtime PHP 8.1
  disponível para execução.

Nenhum teste `pncp-live`, probe Oracle/PNCP, acesso real à rede, escrita
externa ou integração Scriptcase foi executado durante a Sprint 5.8.

## Evidências da Sprint 5.7

- `LeitorPncpAdapter` implementa os dois métodos existentes de `LeitorPncpPort`,
  usando a sessão autenticada opaca somente para compor o Bearer interno e sem
  alterar Domain, Application, Ports, Commands ou APIs públicas.
- A observação do PCA conhecido consulta o consolidado e percorre integralmente
  os itens com páginas iniciadas em 1 e parâmetros explícitos. A fotografia só
  é produzida depois de encerramento comprovado por página curta ou `204`.
- A paginação valida identidade e coerência entre páginas, detecta páginas e
  itens repetidos, rejeita mais de 1.000 itens e termina como inconclusiva
  quando o limite defensivo de páginas impede comprovar o encerramento.
- A busca por órgão e ano percorre toda a listagem antes de filtrar a UASG.
  Zero candidatos não consulta itens; múltiplos candidatos conflitam; somente
  o candidato único tem seus itens carregados para satisfazer o contrato de
  `PcaObservado`. Nenhum vínculo é criado, selecionado ou adotado.
- Ausência definitiva exige duas evidências coerentes: `204` no consolidado do
  sequencial conhecido e listagem completa por órgão e ano sem PCA da mesma
  UASG. A mesma UASG com sequencial igual ou diferente produz conflito; leitura
  parcial, inválida ou interrompida não fabrica ausência.
- O significado de `204` permanece contextual: após consolidado encontrado,
  `204` nos itens representa PCA ativo sem itens; na listagem, encerra somente
  aquela sequência paginada.
- GET elegível realiza no máximo duas tentativas. Somente `408`, `429`, `502`,
  `503`, `504` e errno cURL `28` permitem uma segunda tentativa; duas falhas
  transitórias produzem resultado inconclusivo, sem terceira chamada.
- `Retry-After` aceita segundos ou data HTTP, usa relógio e espera injetáveis,
  limita a espera ao teto configurado e usa a espera configurada quando o
  header está ausente, duplicado ou inválido.
- Eventos registram somente ação técnica, tentativa, página, quantidade,
  status HTTP e códigos seguros. Token, credenciais, headers, corpos, caminhos
  e identidades observadas permanecem fora de eventos e falhas.
- A suíte focada de leitura aprovou 71 testes e 187 asserções.
- A regressão de `Infrastructure/Pncp` aprovou 195 testes e 484 asserções.
- A suíte padrão aprovou 668 testes e 2.290 asserções.
- A suíte contratual não real aprovou 24 testes e 51 asserções.
- A regressão de serialização PNCP aprovou 88 testes e 149 asserções.
- Lint dos 11 arquivos PHP envolvidos, autoload otimizado com PSR-4 estrito
  contendo 1.722 classes, sanitização e `git diff --check` foram aprovados.
- As execuções usaram PHP 8.4.7. A implementação foi inspecionada para manter
  sintaxe e APIs compatíveis com PHP 8.1, mas não havia runtime PHP 8.1
  disponível para execução.

Nenhum teste `pncp-live`, probe Oracle/PNCP, acesso real à rede, escrita
externa ou integração Scriptcase foi executado durante a Sprint 5.7.

## Evidências da Sprint 5.6

- O marcador numérico valida a gramática JSON e preserva o lexema exato; o
  codificador recursivo aceita somente arrays, strings, inteiros, booleanos,
  `null` e números exatos, sem converter números para `float`.
- `float`, recursos, objetos desconhecidos, lexemas inválidos e UTF-8 inválido
  são recusados com uma única mensagem segura, sem valores ou payloads.
- Quantidade, totais e valor unitário são produzidos por operações textuais com
  quatro casas e `HALF_UP`; negativos, divisor zero e resultados fora de
  `DECIMAL(17,4)` são rejeitados, inclusive carry que provoca overflow.
- O valor unitário usa o valor total interno original dividido pela quantidade;
  valor total e orçamento do exercício são convertidos independentemente e não
  são recalculados pelo unitário arredondado.
- Os quatro corpos preservam e ordenam numericamente `ID_DEMANDA` como
  `numeroItem`, sem renumeração, e emitem categoria do PCA inteira,
  classificação Material/Serviço textual, catálogo independente, datas
  `YYYY-MM-DD` e números JSON exatos.
- Textos são validados por caracteres Unicode e nunca truncados. Campos sem
  fonte semanticamente compatível são omitidos, sem envio de `null`.
- O serializer não redefine o limite operacional de 1.000 itens protegido pela
  Application; um teste direto confirmou a ausência dessa duplicação com 1.001
  itens.
- A suíte focada de serialização aprovou 88 testes e 149 asserções.
- A regressão de `Infrastructure/Pncp` aprovou 124 testes e 296 asserções.
- A suíte padrão aprovou 597 testes e 2.102 asserções.
- A suíte contratual não real aprovou 24 testes e 51 asserções.
- Lint dos 10 arquivos PHP envolvidos, autoload otimizado com PSR-4 estrito,
  direção das dependências, ausência de HTTP na serialização, ausência de
  `float` e divisão nativa no conversor e `git diff --check` foram aprovados.
- As execuções usaram PHP 8.4.7. A implementação foi inspecionada para manter
  sintaxe e APIs compatíveis com PHP 8.1, mas não havia runtime PHP 8.1
  disponível para execução.

Nenhum teste `pncp-live`, probe Oracle/PNCP, endpoint, paginação, escrita externa
ou integração Scriptcase foi executado durante a Sprint 5.6.

## Evidências da Sprint 5.5

- Os ambientes PNCP são fechados em Homologação e Produção, associados somente
  aos endpoints canônicos HTTPS; Request e usuário não fornecem URL livre.
- A configuração exige limites positivos de payload e resposta, não admite
  timeout infinito e aplica os valores iniciais de 5 segundos para conexão,
  30 para login e leitura e 60 para escrita, com CA bundle opcional.
- O transporte cURL nativo executa exatamente uma requisição por chamada,
  verifica certificado e hostname, restringe o protocolo a HTTPS, desabilita
  redirects e captura status, headers e corpo separadamente.
- Payload acima do limite é recusado antes da execução; resposta acima do
  limite é interrompida; falha de transporte permanece distinta de resposta
  HTTP e não decide retry ou reconciliação.
- A autenticação envia login e senha somente no corpo interno da requisição,
  ocorre uma vez por execução e exige `Authorization: Bearer` em resposta 200.
- O token permanece opaco em sessão sem contrato de serialização, sem leitura
  de claims, persistência, renovação ou exposição em eventos e falhas.
- `401`, `403`, falha de transporte e resposta de autenticação inválida geram
  falha sistêmica sanitizada; a ação técnica `AUTENTICAR_PNCP` não amplia o
  catálogo de ações da Application.
- A suíte focada aprovou 48 testes e 185 asserções.
- A regressão relacionada aprovou 288 testes e 1.171 asserções.
- A suíte padrão aprovou 509 testes e 1.953 asserções.
- A suíte contratual não real aprovou 24 testes e 51 asserções.
- Lint dos 19 arquivos PHP envolvidos, autoload PSR-4 estrito e inspeções de
  segurança, escopo e direção das dependências foram aprovados.

Nenhum probe real Oracle ou PNCP e nenhuma integração Scriptcase foram
executados durante a Sprint 5.5.

## Evidências da Sprint 5.4

- O estado operacional em `PAC_PLANOS` exige transação ativa, projeta todas as
  versões do escopo, rejeita divergências e confirma a atualização por
  releitura na mesma sessão.
- O contexto do usuário resolve exatamente um Representante ativo e uma
  Lotação vigente e ativa pelo login confiável, sem inferência por UO ou UASG.
- A auditoria insere fatos append-only em `PAC_HISTORICOS` com `SYSDATE`, binds
  e procedimentos tipados 5, 8 ou 9 na mesma transação da confirmação e do
  estado operacional.
- O backend produz eventos técnicos imutáveis por PSR-3, com ações operacionais
  da Application, sanitização recursiva e acumulação ordenada apenas em memória.
- O lote para exportação futura calcula `executionTimeMs` sem alterar os
  eventos acumulados nem seu metadata original.
- Os eventos não integram `PublicarPcaResponse`, não escrevem em Oracle ou
  Scriptcase e não possuem adaptação para `sc_log_add` nesta sprint.
- A suíte focada aprovou 67 testes e 432 asserções.
- A suíte padrão aprovou 472 testes e 1.805 asserções.
- A suíte contratual não real aprovou 24 testes e 51 asserções.
- Lint dos arquivos PHP alterados, autoload PSR-4 estrito e inspeções de
  fronteira foram aprovados.

Nenhum probe real Oracle ou PNCP, DDL ou integração Scriptcase foi executado
durante a Sprint 5.4.

## Evidências da Sprint 5.3

- Os mapeamentos de Planos, Demandas e Unidades Orçamentárias validam os
  identificadores Oracle e não possuem nomes físicos implícitos.
- A tradução física usa aliases canônicos, datas com máscaras explícitas,
  fuso operacional injetado e decimais exatos sem `float`.
- A descoberta retorna identidades distintas e ordenadas, exige cadastro ativo
  único de UO e rejeita ausência ou incoerência de Lotação e UASG.
- A reconstrução exige transação ativa, seleciona a maior versão sem usar a
  confirmação e reúne Demandas Homologadas, Compradas e Publicadas de todas as
  versões de origem.
- Plano vazio permanece reconstruível; cardinalidade, duplicidade e conteúdo
  físico inválido são rejeitados antes de atravessar a Infrastructure.
- A confirmação confere a identidade e versão exatas, atualiza somente a linha
  pendente com `SYSDATE` e relê a intenção, sem mutar o Plano carregado.
- O Repository reutiliza a conexão e a transação emprestadas, sem `Close()`,
  commit ou rollback autônomo.
- A suíte padrão aprovou 428 testes e 1.511 asserções.
- A suíte contratual não real aprovou 24 testes e 51 asserções.
- A suíte focada da Infrastructure Oracle aprovou 89 testes e 372 asserções.

Nenhum probe real Oracle ou PNCP e nenhum DDL foram executados durante a
Sprint 5.3.

## Gates confirmados

- O gate Oracle/Scriptcase confirmou ADOdb emprestado, `Param()`, mesma sessão,
  `BeginTrans`, `CommitTrans`, `RollbackTrans`, ausência de `Close()`,
  `FOR UPDATE NOWAIT` e contenção traduzível a partir de `ORA-00054`.
- O gate PNCP confirmou os contratos autorizados de runtime, TLS, autenticação,
  leitura e escrita no ambiente de treinamento.
- O gate dos adapters confirmou contexto, estado operacional e auditoria sobre
  a mesma sessão Oracle real, com restauração integral por rollback.

As evidências não autoritativas da Sprint 5.1 permanecem em
[Evidências dos gates Oracle e PNCP](../../investigacoes/evidencias_sprint_5_1_gates_oracle_pncp_2026-08-17.md).

## Pendências não bloqueantes

- A compatibilidade com CNPJ alfanumérico integra o contrato aceito da DI-009.
  Eventual incompatibilidade externa concreta reabrirá somente essa decisão.
- Uma chamada única com 1.001 itens permanece **AINDA NÃO COMPROVADA**.
- O limite operacional continua em 1.000 itens por chamada.

## Restrições para o próximo avanço

- O provisionamento de controles de concorrência depende de tabela e colunas preexistentes,
  fornecidas pelo mapeamento físico; o backend não cria DDL nem aplica
  fallback se a tabela estiver ausente.
- Nomes físicos, credenciais, tokens e strings de conexão não possuem default
  no código e não devem ser registrados neste documento.
- A conexão ADOdb fornecida pelo Scriptcase permanece emprestada e nunca pode
  ser fechada pelo backend.
- Os contratos autorizados dos adapters e os gates reais Oracle/Scriptcase e
  PNCP de treinamento da Sprint 5.9 estão aprovados.
- A conclusão da Sprint 7.1 não autoriza automaticamente Presenter, ViewModels,
  fachada, tratamento global de exceções ou Composition Root final. A Sprint
  7.2 exige planejamento e autorização próprios.
- A exposição dos eventos pela fachada e sua persistência pela Blank com
  `sc_log_add` permanecem adiadas para a futura fronteira Scriptcase.
- Uma falha fatal anterior ao retorno à Blank pode impedir a persistência dos
  eventos acumulados em memória; essa limitação foi aceita conscientemente.
