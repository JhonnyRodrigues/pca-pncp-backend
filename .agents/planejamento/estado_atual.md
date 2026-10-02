# Estado atual do trabalho no projeto PCA/PNCP

**Classificação:** artefato de agentes

**Autoridade/Escopo:** progresso da implementação, última sprint concluída,
próxima sprint na ordem, gates, pendências e continuidade do trabalho

**Atualizado em:** 01/10/2026

## Limites de autoridade

Este documento centraliza o estado corrente do trabalho para agentes. A
condição do produto instalado, as restrições externas e a conduta segura para
manutenção e suporte pertencem à
[situação operacional](../../docs/vigentes/operacional/situacao_operacional.md).

Ele não cria nem altera regras de negócio, modelo de domínio, decisões de
integração, arquitetura, fluxo de implementação, ordem das sprints ou critérios
de saída.

Para esses assuntos, prevalecem as fontes autoritativas registradas no
[índice documental](../../docs/README.md). A ordem de construção permanece
definida pelo [Roadmap](../../docs/vigentes/autoritativos/roadmap.md).

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
- A última sprint concluída é a Sprint 9.4.
- A Sprint 9.2 permanece no roadmap como guia, mas não será executada neste
  ciclo por decisão de escopo do mantenedor. Ela não é pendência nem bloqueio.
- A Sprint 9.3 — Empacotamento e implantação está concluída.
- A Sprint 9.4 — Migração assistida e recuperação está concluída, com o
  bloqueio nominal aceito de 2026 e o corretivo de pré-requisitos Oracle
  registrados abaixo.
- A Sprint 9.5 — Prontidão de produção e suporte está em execução conforme o
  [plano aprovado](../sprints/planos/plano_sprint_9.5.md).
  Microciclos 1 a 7 concluídos; o Microciclo 8 continua interrompido durante
  a investigação do HTTP 403 na inclusão de itens de 2026.
  Oracle e PNCP de 2027 coincidiram por IDs;
  após corrigir a publicação da Blank, uma leitura PNCP expirou duas vezes e
  a execução seguinte concluiu as três unidades em `NENHUMA_ACAO`. Não houve
  `POST` ou `DELETE` nessas duas correlações, e a auditoria de 2027 permaneceu
  inalterada. Após a migration que vinculou 366 demandas legadas, o precheck
  somente leitura aprovou 478 pares desejados e decisões `RETIFICAR` com
  71/3/38 inclusões e zero exclusões. O mantenedor informou três acionamentos
  da Blank; todos pararam no primeiro `POST` com mensagem de inclusão não
  autorizada. Na correlação fornecida, seis chamadas tiveram HTTP 200, o
  `POST` recebeu 403 e não houve `DELETE`. O diagnóstico protegido confirmou a
  mesma identidade do usuário corrente e preservou a mensagem do PNCP de que
  somente o publicador poderia editar o PCA. A renovação experimental não
  resolveu o bloqueio e foi removida; o fluxo voltou a autenticar uma vez por
  execução. O esclarecimento da autoria permanece pendente de chamado ao PNCP,
  sem autorização implícita para repetir a Blank.
- Permanece uma pendência técnica local para o encerramento da Sprint 9.5. A
  unificação das configurações do PHPUnit revelou sete fixtures contratuais das
  Sprints 8.4 e 8.5 que ainda constroem `ResultadoUnidadeViewModel` sem os
  campos `numerosItensIncluidos` e `numerosItensExcluidos`, acrescentados na
  Sprint 9.5. A suíte padrão permanece verde com 984 testes e 4.753 asserções,
  sem evidência de impacto no produto instalado. Na retomada, antes de encerrar
  a sprint, atualizar as fixtures com listas coerentes com cada cenário e
  reexecutar `contract:sprint84:tests`, `contract:sprint85:tests` e a suíte
  padrão. A correção é exclusivamente local, não requer acesso ao Oracle ou ao
  PNCP e permanece adiada até o planejamento posterior à resposta do chamado.
- A Sprint 8.4 comprovou a matriz real 4×2 nos modos individual e consolidado.
  O Microciclo 7 foi concluído após o corretivo 8.4.7-C uniformizar a projeção
  operacional Oracle.
- O corretivo 8.4.7-C/C1 concluiu código, testes e documentação locais. A
  conferência manual de C2 foi aprovada em 16/09/2026, com as 11 consultas e o
  SHA-256 canônico ADOdb
  `97e63e36eba262aef78fe94d056419d90e5ade4111667393ec06a7ef535a9f90`.
  O PRECHECK real de C2 foi aprovado em 16/09/2026, sem escrita Oracle ou PNCP,
  com relatório SHA-256
  `95b025a2cbac00b2723a542f2da513a5b3948ba37008f4cc672b3b81e8d12d8e`.
  A convergência somente Oracle (C3) foi aprovada em 16/09/2026. Após uma
  tentativa recusada ainda sem I/O, a execução efetiva `NENHUMA_ACAO` completou
  somente os quatro pares operacionais pendentes e produziu o SHA-256 Oracle
  `8783234664560b180f75dbd2e9e166db7c0af8d58a36de7ceb3f1744ffc72f92`.
  A execução não deve ser repetida. O arquivo externo foi recolocado em modo
  somente leitura (`PRECHECK`, escritas Oracle/PNCP falsas). A etapa local 8A
  do Microciclo 8 foi concluída em 16/09/2026: o PRECHECK passou a aceitar
  fotografia vazia e o roteiro das onze consultas somente leitura foi criado.
  A preparação SGI, a conferência manual e o PRECHECK real de 8B foram
  concluídos em 16/09/2026. O checkpoint Oracle aprovado é
  `ace6ef705dac4a390cb2857b85648a73fb1341da7283af845f843fc83aac8536`;
  o relatório `PRECHECK_CONFIRMADO` tem SHA-256
  `25afec75e32b761e84163f625db343f2ab33f65792bf202287fb212234004226`.
  Não houve escrita Oracle nem mutação PNCP no PRECHECK. A execução única de
  8C foi concluída com `FLUXO_CONFIRMADO`: sequenciais 1 e 2 excluídos, UASG
  930281/sequencial 4 preservada, onze versões em `EXCLUIDO` e duas auditorias
  de procedimento 9. O SHA-256 Oracle posterior é
  `038ce6eeed00124118272091203f3509d8847b52c89552dbaa328d79ebc19da0` e o
  relatório tem SHA-256
  `96f31765a8c44b6e353884c58cff9864ac3c4a1a3710f1dc248dbb65c15d4b44`.
  Não repetir a execução. O mantenedor confirmou que o arquivo externo voltou
  a `PRECHECK`, com ambas as permissões de escrita falsas, sem nova chamada da
  Blank. O Microciclo 8 está concluído. O Microciclo 9 também foi concluído em
  17/09/2026 com o checkpoint real do modo consolidado para 2026. A tentativa
  anterior de usar 2028 foi recusada antes de I/O, preservando `RN-CAD-002`.
  Após limpeza Oracle controlada e autorizada dos dados antigos de 2026, o SGI
  gerou os planos 99/UO 1 e 100/UO 3 e as Demandas 811/810. As onze consultas
  manuais e o PRECHECK confirmaram o SHA-256 Oracle
  `c877c94cf8a633c977192484cda37455e1443892e5098efbd5f26fc89ba0e5da`.
  O PRECHECK, request ID `29fb8872-e6ea-4c76-8a8e-35beeb10b2de`, comprovou a
  ausência do alvo UASG 930281 sem escrita Oracle ou mutação PNCP. Os PCAs
  legados 16/210, 17/205 e 18/207 permaneceram intocados. O SHA-256 do relatório
  é `ff91c0c86ae7524df608d73acfc77c87c12cfc03fd80d77d6f85644ffae1d223`.
  A publicação consolidada do Microciclo 10 foi concluída em 17/09/2026. A
  execução única, request ID `8c65729b-90b5-4ac5-ada7-ca53b53387f8`, retornou
  `FLUXO_CONFIRMADO`: um único PCA consolidado na UASG 930281, sequencial 36,
  com as Demandas 810 e 811. Os planos 99 e 100 ficaram confirmados e
  uniformemente projetados como `CONSOLIDADO/930281/PUBLICADO/36`; foi criada
  uma auditoria de procedimento 5 e as duas âncoras de concorrência esperadas.
  Os PCAs legados 16, 17 e 18 permaneceram intocados. Houve quatro commits,
  sem rollback ou `Close()`. O SHA-256 Oracle posterior é
  `f0d5b5408d2cbc9c3305168e3e25573fdefc3d67d2b7516afa69f2971a7ead79` e o
  SHA-256 do relatório, recalculado e conferido, é
  `a1f9928182db44f5699fd55ec52275392d2dddfec8622aa30942c09542f581f6`.
  O mantenedor confirmou o retorno do arquivo externo a `PRECHECK`, com ambas
  as permissões de escrita falsas. A execução não deve ser repetida. O
  Microciclo 11 também foi concluído em 17/09/2026. A execução única
  `NENHUMA_ACAO`, request ID `e858fa4a-9230-4a93-a1e0-272e7b1e99be`, retornou
  `FLUXO_CONFIRMADO`, preservando a UASG 930281/sequencial 36 e as Demandas
  810/811. As onze consultas Oracle e o SHA-256
  `f0d5b5408d2cbc9c3305168e3e25573fdefc3d67d2b7516afa69f2971a7ead79`
  permaneceram idênticos; não houve confirmação, auditoria ou mutação PNCP.
  Os únicos dois `Execute` foram garantias idempotentes das âncoras existentes.
  Houve quatro commits, sem rollback ou `Close()`. O SHA-256 do relatório,
  recalculado e conferido, é
  `3fd1ac2635c7d92fe805bc9cc1a708c8f77db2240cef9729cb6678e5643815e8`.
  A configuração externa voltou ao modo somente leitura e a execução não deve
  ser repetida. A etapa local 12A do Microciclo 12 foi concluída em 17/09/2026:
  o roteiro das onze consultas somente leitura para a retificação consolidada
  foi criado e protegido por teste. O Red falhou somente pelo arquivo ausente;
  o Green passou com 7 testes/223 asserções. Os contratos Sprint 8.4 passaram
  com 72/672, a matriz de integração com 10/205, lint e PSR-4 estrito também
  foram aprovados. Não houve acesso real. A preparação SGI, a conferência
  manual e o PRECHECK de 12B foram concluídos em 17/09/2026. Após corrigir a
  lotação ativa de `jsilva` para a lotação 1, o checkpoint aprovou os planos
  101/102, a fotografia 810/812 e o SHA-256 Oracle
  `44944e0472e8d42f526b70dca07710c7322cb50b48cefbc2064a20396a990b23`.
  O PRECHECK real, request ID `214ebb5a-7012-4efa-b01a-902b6e72d280`, repetiu
  esse hash e encontrou o PCA 36 ainda com 810/811, sem escrita Oracle ou PNCP.
  O SHA-256 do relatório, recalculado e conferido, é
  `724db77321c7cc507ec46fa28ebcd0c76a4c26257122d39905ca1d2ea64769df`.
  A etapa 12B foi concluída e a retificação real de 12C também terminou em
  17/09/2026. A execução única, request ID
  `a1c7336d-4b48-44f7-9996-0abf8f056f64`, retornou `FLUXO_CONFIRMADO`: o item
  811 foi excluído antes da inclusão do item 812, preservando o PCA 36 ativo
  com as Demandas 810/812. Os planos 101/102 foram confirmados e as quatro
  versões ficaram uniformemente em `CONSOLIDADO/930281/PUBLICADO/36`. Foi
  acrescentada uma auditoria de procedimento 8. Houve quatro commits, sem
  rollback ou `Close()`. O SHA-256 Oracle posterior é
  `ae8861e51dda76c8dea577c12502636cfecdf06d084364153f4c588078a97326` e o
  SHA-256 do relatório, recalculado e conferido, é
  `0352b60221ce40e80d9c368b5fc7b935b39fa9b4de0426c38a2b8cb8659202f0`.
  O mantenedor confirmou o retorno da configuração externa a `PRECHECK`, com
  ambas as permissões de escrita falsas, sem nova execução. O Microciclo 12
  está concluído. A etapa local 13A foi concluída em 17/09/2026: o roteiro das
  onze consultas somente leitura para a exclusão consolidada foi criado e
  protegido por teste. O Red focado falhou somente pelo arquivo ausente e o
  Green passou com 1 teste/31 asserções. Os contratos Sprint 8.4 passaram com
  73/703, a matriz de integração com 10/205, lint e PSR-4 estrito também foram
  aprovados. Não houve acesso real em 13A. A preparação SGI e o PRECHECK de
  13B foram concluídos em 17/09/2026. Os cancelamentos de 810 e 812 geraram os
  planos 103 e 104; a fotografia ficou vazia e as três Demandas 810/811/812
  ficaram em situação 4. Uma primeira tentativa foi recusada antes de I/O
  mutante porque o hash manual preservava tipos numéricos diferentes dos tipos
  retornados pelo ADOdb. O snapshot foi comprovado idêntico após essa
  normalização. O SHA-256 Oracle canônico é
  `381cbf6ebc6a1d2610bbeaafc7a4aea714b0b6f68ea841a9fd12e3faeca71df4`.
  O PRECHECK efetivo, request ID `f95becf2-a4d7-4f08-8762-45cb6577c1b7`,
  retornou `PRECHECK_CONFIRMADO` e encontrou a UASG 930281/sequencial 36 com
  exatamente 810/812, sem escrita Oracle ou mutação PNCP. O SHA-256 do
  relatório, recalculado e conferido, é
  `6dbc3e8308275936e26770d7eb7e698ce3cfee63cb5664909190112782bd2ba1`.
  A etapa 13B está concluída. A execução única de 13C, request ID
  `5c6798aa-7b80-43ed-93aa-594c915f1031`, também foi confirmada em
  17/09/2026: um único `DELETE` removeu a UASG 930281/sequencial 36, duas
  observações posteriores concluíram ausência definitiva e os PCAs legados
  16, 17 e 18 permaneceram preservados. As seis versões ficaram em
  `CONSOLIDADO/930281/EXCLUIDO/36`; somente o plano vigente 104 recebeu nova
  confirmação e foi criada uma auditoria de procedimento 9. Houve quatro
  commits, sem rollback ou `Close()`. O SHA-256 Oracle posterior é
  `1aee1ee6f67810f56ec0d242c90868090d4b56c0f72ed290f505cbccd37dfd67` e o
  SHA-256 do relatório, recalculado e conferido, é
  `78f74ee35ace7731bdb7383736a81ae6385fb40af8eed1cbeba543b0890e000b`.
  A execução não deve ser repetida. O mantenedor confirmou que a configuração
  externa voltou a `PRECHECK`, com ambas as permissões de escrita falsas, sem
  nova execução da Blank. O Microciclo 13 está concluído. O Microciclo 14 de
  regressão e encerramento aprovou: contratos 8.4 com 73 testes/703 asserções;
  Integration+Composition 29/505; Presentation+Application 270/1.315;
  Infrastructure 511/1.956; contratos anteriores 5.1, 5.9, 8.2 e 8.3 verdes;
  suíte padrão 921/4.214; lint de 355 arquivos; PSR-4 com 1.817 classes;
  direção das dependências, ausência de segredos reais e `git diff --check`.
  O Composer não apresentou erro estrutural e manteve somente o aviso existente
  de licença não declarada. Nenhum teste live ou acesso externo ocorreu. O
  Microciclo 14 e a Sprint 8.4 estão concluídos. O avanço da Sprint 8.5 está
  registrado abaixo, sem autorizar repetição das execuções da Sprint 8.4.
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
- A Sprint 7.1 — Controller e entrada tipada está concluída.
- A Sprint 7.2 — Presenter e ViewModels está concluída.
- A Sprint 7.3 — Fachada e fronteira de exceções está concluída.
- A Sprint 7.4 — Suíte da Presentation está concluída.
- A Fase 7 — Presentation está concluída.
- A Sprint 8.1 — Composition Root e perfil Scriptcase está concluída.
- A Sprint 8.2 — Integração Oracle real está concluída.
- A Sprint 8.3 — Suíte PNCP de treinamento está concluída após os seis
  microciclos autorizados e o corretivo de paginação e ausência.
- A Sprint 8.4 — Fluxos completos dos dois modos está concluída.
- A Sprint 8.5 — Falhas, segurança e smoke test do pacote está concluída,
  conforme o [plano aprovado](../sprints/planos/plano_sprint_8.5.md).
  O Microciclo 1 foi concluído em 17/09/2026 com o registro do plano e a revisão
  contratual de exportação, logs e tratamento de falhas. O Microciclo 2 foi
  concluído em 18/09/2026 com a exportação compatível dos eventos pela API
  pública. O Microciclo 3 foi concluído na mesma data com falhas integradas
  sobre adapters concretos e fronteiras físicas controladas. O Microciclo 4
  versionou e adaptou a Blank. O Microciclo 5 gerou e verificou o pacote autocontido e preparou
  os contratos, as consultas e o PRECHECK da Sprint 8.5. O gate real revelou a
  incompatibilidade da macro com o CLOB maior que 4.000 bytes; o corretivo 6A
  implementou o registrador próprio, e o Microciclo 6 comprovou em homologação
  a gravação integral de 11.179 bytes.
- O Microciclo 7 comprovou a contenção real no validaSGI; o Microciclo 8
  concluiu o smoke e a limpeza segura da massa; o Microciclo 9 entregou e
  validou a ajuda ao operador; e o Microciclo 10 reconstruiu o pacote, executou
  as regressões e encerrou a sprint. A configuração externa permanece
  desarmada em `PRECHECK`. Nenhum gate real deve ser repetido.
- A Sprint 9.1 — Configuração e segredos de produção está concluída conforme o
  [plano aprovado](../sprints/planos/plano_sprint_9.1.md). A pausa
  foi cumprida antes do provisionamento manual autorizado em produção. A Sprint
  9.3 é a próxima na ordem e exige planejamento próprio.

## Evidências da Sprint 9.1

- A Sprint não introduz classe, teste, pasta, dependência, probe, contrato ou
  ferramenta. O carregador e o perfil existentes já recusam configuração
  ausente ou incoerente antes de I/O.
- O perfil de homologação foi consultado de forma sanitizada como referência de
  estrutura e parâmetros. Login, senha, CNPJ integral e valores dos mapeamentos
  físicos não foram expostos.
- A produção usa `registro.php`, `perfil-producao.php` e
  `ca-bundle.crt` em `/var/www/configuracoes/pca-pncp/`, fora da release e do
  webroot. O CA bundle de referência possui SHA-256
  `b7a69ed0ce1e25aa4a278dd9ef1b7d4dc1fee893be19c20cfd7e866679de61cc`.
- O registro contém somente o hostname literal
  `sgi.semaepiracicaba.sp.gov.br`; o perfil declara `PRODUCAO`; e o CA bundle
  contém 152 certificados públicos, sem chave privada.
- A política aprovada aplica o princípio do menor privilégio: diretório `2750`
  com `setgid`, arquivos `640`, propriedade `administrator:www-data`, escrita
  administrativa, leitura pelo PHP e nenhum acesso para os demais usuários.
- O [guia de configuração de produção](../../docs/vigentes/operacional/guia_de_configuracao_de_producao.md)
  documenta também como distinguir `mod_php` de PHP-FPM e provisionar
  `PCA_PNCP_PROFILE_REGISTRY_FILE` de forma persistente. Um `export` de terminal
  não substitui a configuração do processo Apache/PHP.
- O servidor usa Debian 12 e Apache com `mod_php` e `env_module`. O
  `DocumentRoot` é `/var/www/html`. O localizador foi configurado no
  `VirtualHost` HTTPS, passou no `configtest`, foi recarregado e permaneceu
  ativo.
- Os arquivos PHP passaram no lint. A validação sanitizada executada como
  `www-data` confirmou configuração de produção válida e legível, sem exibir
  CNPJ ou credenciais.
- Nenhum Oracle, PNCP, pacote, fluxo da Blank ou smoke de produção foi
  executado. Esses avanços permanecem condicionados às Sprints seguintes.

## Decisão de escopo da Sprint 9.2

- Em 22/09/2026, o mantenedor decidiu não executar a Sprint 9.2 — Observabilidade
  e segurança operacional neste roadmap, priorizando o prazo de entrega. A
  sprint permanece registrada como parte do guia, sem ser considerada concluída,
  pendente ou bloqueante.
- Os eventos técnicos estruturados, a correlação, a sanitização de dados
  sensíveis e a separação de `SGI.SEGURANCA_LOG` e `PAC_HISTORICOS` já
  implementados permanecem vigentes. Nenhum comportamento existente será
  removido ou reaberto por essa decisão.
- Embora exista Grafana institucional, este ciclo não entregará dashboard,
  métricas, alertas, integração com fonte de dados ou política de retenção. O
  monitoramento foi dispensado como critério de saída desta Fase 9.
- A Sprint 9.3 — Empacotamento e implantação possui plano aprovado. A
  implantação inicial em produção foi autorizada pelo mantenedor.

## Evidências da Sprint 9.3

- O plano foi aprovado para uma implantação inicial manual. O backend foi
  instalado em `/var/www/html/PCA_PUBLICAR_B/pca-pncp-backend/` e a Blank
  carrega o autoload dessa pasta.
- A transferência manual foi feita pelo mantenedor por canal SSH com o usuário
  institucional `devteam`. A integração com o Gitea foi adiada como melhoria
  futura não bloqueante devido ao prazo de entrega.
- A aplicação anterior foi removida e não constitui o rollback desta
  implantação inicial. O runbook preserva o procedimento de desativação da
  nova release e o rollback aplicável às atualizações futuras.
- Após a autorização específica, a release `137f933` foi transferida com
  SHA-256 `ca1600244391efbc6acc3150e0868da5e3787cdbac87fa2352df9495691ae97c`,
  idêntico ao valor local, e extraída em
  `/var/www/html/PCA_PUBLICAR_B/pca-pncp-backend/`.
- O ciclo TDD focado passou com 9 testes e 115 asserções. A suíte local passou
  com 954 testes e 4.542 asserções; o lint aprovou 402 arquivos PHP; o autoload
  PSR-4 estrito gerou 1.843 classes; e o Composer permaneceu válido, com apenas
  o aviso anterior de licença não declarada.
- O pacote candidato foi construído e validado com 196 arquivos de conteúdo e
  197 no total, sem dependências de desenvolvimento, configurações ou segredos.
  As duas fachadas públicas são carregáveis. O SHA-256 do manifesto candidato é
  `6c55a1566e795d2765d4048ea38570d923312b163bfd2e47621ab2461bc89b18`.
- O diretório instalado contém somente `PACKAGE-MANIFEST.json`, `src` e
  `vendor`. O PHP CLI executado como `www-data` carregou as duas fachadas
  públicas. Houve warning de inicialização do OCI8 por ausência de
  `libmql1.so`; essa evidência não valida o runtime Apache nem a conexão Oracle.
- A ampliação autorizada acrescentou `package:archive` e `package:release`. O
  teste focado passou com 3 testes e 35 asserções; a suíte completa, com 957
  testes e 4.577 asserções; o lint, com 404 arquivos; e o PSR-4 estrito, com
  1.844 classes.
- O `.tar.gz` foi removido do webroot. A nova versão da Blank foi compilada e
  publicada, e o mantenedor confirmou que a tela `PCA_PUBLICAR_B` abriu
  normalmente pelo menu do SGI.
- A abertura da tela conclui a verificação de implantação, mas não constitui
  smoke funcional. O botão **Publicar** não foi acionado; nenhuma publicação,
  conexão Oracle ou chamada PNCP foi executada nesta sprint.
- A Sprint 9.3 foi concluída em 22/09/2026. A Sprint 9.4 foi posteriormente
  concluída conforme as evidências seguintes.

## Evidências da Sprint 9.4

- O gate temporário inventariou 2026 e 2027 somente por leitura, com escrita de
  logs, Oracle e PNCP desabilitada. Candidatos desconhecidos foram preservados
  até a confirmação humana de procedência.
- Os PCAs oficiais de 2026 foram vinculados em modo `INDIVIDUAL`: UASG
  205/sequencial 4, UASG 207/sequencial 2 e UASG 210/sequencial 3. A execução
  única de reconciliação foi interrompida no primeiro `DELETE` de itens com
  HTTP 403 e código `PNCP_EXCLUSAO_ITENS_NAO_AUTORIZADA`. Não houve inclusão,
  o Oracle sofreu rollback e a fotografia posterior permaneceu idêntica. O
  mantenedor aceitou nominalmente esse bloqueio. Uma exclusão manual posterior
  com a mesma credencial funcionou depois de renovar o token, invalidando a
  hipótese de falta permanente de permissão. A execução de 2026 não deve ser
  repetida sem novo diagnóstico, fotografia atual e autorização específica.
- O PCA consolidado manual de 2027 foi removido conscientemente pelo mantenedor
  antes da adoção definitiva da estratégia `INDIVIDUAL`. A publicação seguinte
  criou três candidatos próprios, mas as respostas HTTP 201 ficaram incertas
  porque o prefixo absoluto do `Location` não era reconhecido.
- O inventário de SHA-256
  `66fe4bfc01865dd2967b1801eab44abe9c66c36d4be3b4246b4116f27b709d69`
  comprovou igualdade exata: UASG 205/sequencial 2 com 260 itens, UASG
  207/sequencial 3 com 9 itens e UASG 210/sequencial 4 com 165 itens. Após o
  vínculo assistido, a correlação `eda907a5-a7d6-45ca-9e46-60f1e9cd73e7`
  concluiu as três unidades como `NENHUMA_ACAO`, sem escrita PNCP, e confirmou
  localmente os planos.
- A correção 3A preserva o resultado conhecido na Blank e inclui
  `context.decisao` nos eventos finais. A execução real demonstrou a mensagem
  segura e as decisões. A correção 7A reconhece em produção somente o prefixo
  oficial estrito `https://pncp.gov.br/pncp-api`; formatos incompatíveis geram
  apenas categoria sanitizada, sem registrar o header bruto.
- O pacote 7A possui SHA-256
  `2d1760221bd5cd0d080827c7483c41d1de8215ad3f83663ced504caf4c8b88a3`
  e manifesto SHA-256
  `6ff0b8d4118d86d24f340a745a499277a6de7ae61cb09387b81cafb1a3e192ba`.
  Foi validado em diretório candidato, promovido por troca integral e carregado
  como `www-data`; o backend anterior permaneceu disponível para rollback.
- A regressão local aprovou 960 testes/4.600 asserções; os contratos Sprint 9.4
  aprovaram 36 testes/116 asserções. Lint, Composer, PSR-4 estrito e pacote
  autocontido também foram validados.
- O exercício de mesa aprovou a matriz de recuperação sem induzir falha em
  produção. A Blank e o gate temporários, `sprint94.php`, os arquivos de
  transferência e a quarentena foram removidos. Os perfis permanentes e os
  backends de rollback não foram alterados.
- Depois do encerramento, uma retificação real de 2027 recompôs no PNCP os itens
  949, 950 e 951 da UASG 205/sequencial 2. A leitura posterior confirmou a
  quantidade novamente em 260. A escrita externa foi concluída, mas o Oracle
  recusou o histórico porque `PAC_PROCEDIMENTOS` não possuía o procedimento 8;
  o procedimento 9 também estava ausente. A falta desse registro histórico foi
  aceita pelo mantenedor e não será reparada retroativamente.
- O corretivo criou migrations posteriores para assegurar os procedimentos 8 e
  9 e formalizar a estratégia `INDIVIDUAL`, preservando a migration consolidada
  já aplicada. Também adicionou uma consulta somente leitura dos eventos por
  correlação. Em 24/09/2026, ambas as migrations foram aplicadas em produção
  com postchecks conformes: dois procedimentos presentes e uma configuração
  individual conforme. Não houve chamada à Blank nem ao PNCP.
- A Sprint 9.5 criou o catálogo operacional de códigos e acrescentou aos
  eventos de transporte `curl_errno`, categoria sanitizada e duração da
  tentativa. `status_http` permanece ausente quando não houve resposta HTTP.
  A nova autenticação antes da escrita foi implementada. Em 25/09/2026, a
  correlação `74936623-5300-4392-b67c-6383d1e70cd8` comprovou novo login
  HTTP 200 após a decisão da UASG 205, seguido de HTTP 403 no primeiro `DELETE`
  de 208 itens. Os 71 itens a incluir e 208 a excluir foram registrados como
  planejados; não houve itens confirmados. A renovação do token foi insuficiente
  para este bloqueio e foi posteriormente removida, restaurando a sessão única
  por execução. A execução foi interrompida; não repetir a escrita. O
  mantenedor confirmou que as contagens PNCP permanecem em 5, 153 e 208 para as
  UASGs 207, 210 e 205. Não solicitar
  nova leitura apenas para reconfirmar essas contagens. A projeção e auditoria
  Oracle não foram reconferidas após a tentativa; a causa da primeira falha de
  autenticação também permanece sem diagnóstico.
  O precheck de 2026 também foi invalidado: o mantenedor conferiu no SGI que
  os itens `411`, `412`, `413`, `418` e `419` da UASG 207 continuam homologados
  e desejados, mas nenhum deles consta no export Oracle que embasou a decisão
  de excluí-los. Para a UASG 205, informou que os 208 itens normais publicados
  devem permanecer e receber mais 71 itens extraordinários, totalizando 279;
  o evento planejado incorretamente propôs excluir os 208. A consulta direta no
  banco `sgi` mostrou `SITUACAO = 2` e `FK_PLANO = NULL` nas amostras normais;
  as extraordinárias amostradas possuem vínculo com Planos de 2026. O `JOIN`
  obrigatório por `FK_PLANO` na reconstrução omite as demandas normais. Pela
  `RN-PLA-004`, elas devem integrar a fotografia quando vigentes. É preciso
  comprovar como o SGI associa essas demandas a exercício e UO antes de corrigir
  o acesso a dados ou permitir qualquer nova escrita.
  A diferença entre o
  tempo percebido pela Blank e o medido no backend ainda carece do tempo do
  navegador para a mesma correlação. Esse escopo não reabre dashboards,
  alertas ou retenção da Sprint 9.2.
- Em 25/09/2026, o mantenedor informou ter aplicado a migration excepcional
  `V202609251823` para vincular os 366 IDs normais legados de 2026 aos planos
  de versão 1 e ter conferido os dados no banco, com resultado conforme. Em
  28/09/2026, confirmou que o arquivo executado em produção é exatamente o do
  repositório, com SHA-256
  `47309C11DEA3E017B7AC129ACBC13CFFFBC9E53CAE2A3E245428B5BE153F2F9F`.
  A fotografia Oracle anterior permanece inválida. O precheck somente leitura
  pós-migration foi preparado para reconstruir o conjunto desejado e as
  diferenças por UASG antes de nova execução da Blank. O HTTP 403 anterior
  continua sem causa comprovada.
- Em 28/09/2026, o precheck pós-migration foi executado em produção e aprovado.
  Os 366 vínculos normais e as quantidades desejadas por UASG ficaram
  `CONFORME`. A comparação dos 478 pares distintos `UASG + numeroItem` com o
  inventário PNCP comprovou 71, 3 e 38 inclusões para as UASGs 205, 207 e 210,
  respectivamente, sem qualquer exclusão. A consulta de auditoria dos
  procedimentos 5, 8 e 9 permaneceu vazia.
- Em 28/09/2026, o mantenedor autorizou uma única execução funcional da Blank
  para 2026 após o precheck aprovado. Esperam-se três retificações somente com
  inclusões: 71, 3 e 38 itens nas UASGs 205, 207 e 210. Qualquer exclusão,
  `DELETE`, falha de autenticação, 401/403 ou resultado incerto exige parada sem
  nova tentativa.
- O mantenedor informou três acionamentos posteriores, todos interrompidos com
  `PNCP_INCLUSAO_ITENS_NAO_AUTORIZADA`. A resposta preservada tem correlação
  `2b433428-88ab-4cd5-99db-39bccefcf966`; seus 16 eventos mostram seis
  respostas HTTP 200 e um `POST` de 73 inclusões recusado com 403 em 109 ms,
  sem `DELETE`. A captura autorizada preservou a resposta exata do PNCP:
  `Operação não autorizada. Somente o usuário que publicou a informação pode
  editá-la.` O JWT usado pelo backend corresponde ao usuário de produção
  consultado no PNCP (`idBaseDados` 397 e o mesmo `sub/login`), portanto o PNCP
  considera que o PCA 2026/sequencial 4 pertence a outro publicador interno.
  Isso identifica a causa da recusa, mas não comprova uma correção funcional.
- Após a investigação, o mantenedor revisou a política de observabilidade. O
  arquivo diagnóstico opt-in foi descontinuado. Respostas HTTP `3xx/4xx/5xx`
  não autenticadoras passam a registrar em `SEGURANCA_LOG` requisição,
  resposta, conexão e cURL reproduzível em `metadata.diagnosticoHttp`, sempre
  sem Bearer ou cookies. O código público permanece estável e a mensagem da
  Blank acrescenta o detalhe textual devolvido pelo PNCP. Essa alteração não
  autoriza nova chamada real.
- A Sprint 9.4 foi concluída em 24/09/2026. A Sprint 9.5 recebeu plano e
  autorizações incrementais; o smoke de 2027 do Microciclo 7 foi concluído,
  e o precheck pós-migration de 2026 foi aprovado. O Microciclo 8 encontra-se
  interrompido pelo 403 no `POST`; a mensagem externa atribui a recusa à autoria
  do PCA, mas a confirmação pelo PNCP e a correção funcional continuam
  pendentes. A segunda autenticação, comprovadamente insuficiente, foi retirada.

## Evidências do Microciclo 5 da Sprint 8.5

- O construtor em `tools/package/build.php` usa o lock em
  diretório isolado, instala somente dependências de produção, gera autoload
  autoritativo e registra hashes no manifesto. O artefato gerado e ignorado
  pelo Git fica em `build/pca-pncp-backend` e é conferido por
  `tools/package/verify.php`.
- O corretivo do Microciclo 5 retirou a sprint dos nomes das ferramentas,
  removeu o teste que apenas inspecionava seu texto e preservou os contratos
  comportamentais de configuração, consultas, PRECHECK e segurança. O
  [catálogo de diretórios](../../docs/vigentes/apoio/catalogo_de_diretorios.md) registra a
  organização atual e a diferença entre ferramenta mantida e artefato gerado.
- O pacote validado possui 194 arquivos no total: 193 arquivos de conteúdo
  protegidos por hash e o próprio `PACKAGE-MANIFEST.json`. Contém apenas
  `src/`, `vendor/` e o manifesto, sem testes, probes, configuração, perfil,
  segredo, Composer executável ou dependências de desenvolvimento. A fachada
  pública foi carregada pelo autoload do próprio pacote fora do diretório raiz
  do checkout.
- A configuração opt-in separa `PRECHECK`, `LOGS`, `CONTENCAO` e `SMOKE`,
  mantém mutações PNCP proibidas e protege os sequenciais legados 16/17/18, o
  PCA 36 excluído e o plano 103 sem confirmação.
- A preparação original reaproveitava as onze consultas e o hash canônico da
  Sprint 8.4 e acrescentava a consulta integral do CLOB. Esse desenho continua
  disponível para os gates que dependem da massa, mas foi substituído no
  Microciclo 6 pelo checkpoint reduzido descrito no estado corrente abaixo.
- Os contratos 8.5 aprovaram 19 testes/190 asserções; a suíte padrão,
  944/4.464. Os contratos 5.1, 5.9, 8.2, 8.3 e 8.4 aprovaram 24/51, 54/149,
  12/43, 14/62 e 73/703. Lint, Composer e PSR-4 estrito com 1.827 classes
  também passaram em PHP 8.4.7.
- A construção simula a plataforma PHP 8.1 ao instalar as dependências: o
  projeto exige `^8.1` e a única dependência de produção exige PHP `>=8.0.0`.
  A execução no PHP 8.1 de homologação será comprovada pelo smoke do pacote.
- Nenhum Oracle, PNCP ou Scriptcase real foi acessado e nenhum commit foi
  criado. O gate de logs do Microciclo 6 depende de autorização própria.

## Evidências do Microciclo 4 da Sprint 8.5

- `integracao/scriptcase/PCA_PUBLICAR_B/onExecute.php` carrega o autoload e
  chama somente a fachada pública com conexão emprestada, login, nome do
  servidor, ano inteiro e request ID recebido do hospedeiro.
- A resposta ao navegador contém somente o ViewModel serializado campo a campo
  e eventual aviso técnico. Os eventos são gravados individualmente por
  `sc_log_add('execute', $json)` após o retorno seguro; a primeira falha
  recuperável interrompe os registros restantes e preserva o resultado.
- A interface usa os anos do servidor, corrige a associação dos botões, impede
  submissões simultâneas, não espera artificialmente nem repete a publicação.
  Dados dinâmicos são inseridos como texto, incluindo resultados, contagens e
  correlação.
- O CNPJ exibido no link de consulta pública em produção não é argumento da
  execução. Controller antigo, credenciais locais e inferência de ambiente por
  hostname foram removidos da integração.
- Os testes focados aprovaram 38 testes/558 asserções; a suíte padrão,
  944/4.464. Os contratos locais 5.1, 5.9, 8.2, 8.3 e 8.4 aprovaram 24/51,
  54/149, 12/43, 14/62 e 73/703. Lint PHP, sintaxe do JavaScript extraído e
  PSR-4 estrito com 1.822 classes também passaram em PHP 8.4.7 e Node.js
  24.16.0.
- A compilação no Scriptcase, a gravação real no CLOB e a verificação funcional
  no navegador permanecem para os gates previstos. Nenhum Oracle, PNCP,
  Scriptcase real, pacote instalado ou commit foi acessado. O Microciclo 5
  depende de autorização própria.

## Evidências do Microciclo 3 da Sprint 8.5

- Uma contenção Oracle `ORA-00054` na primeira unidade é traduzida para
  `BLOQUEADO`; a unidade seguinte é processada e o conjunto termina como
  `CONCLUIDA_COM_PENDENCIAS`, sem escrita PNCP ou auditoria de negócio.
- A escrita incerta mantém uma única chamada para cada operação remota e faz
  somente uma reconciliação adicional. Sem convergência observável, desfaz a
  transação e retorna `FALHA_RECONCILIAVEL`.
- Uma falha sistêmica do transporte na segunda unidade desfaz sua transação,
  interrompe a varredura e preserva o resultado conhecido da primeira, sem
  fabricar resultado para a unidade interrompida.
- Os cenários percorrem os adapters Oracle e PNCP concretos. Somente ADOdb e a
  execução cURL são roteirizados; não há Oracle, rede ou PNCP reais.
- A suíte focada aprovou 3 testes/44 asserções; as integrações afetadas,
  16/313; a suíte padrão, 938/4.391. Os contratos locais 5.1, 5.9, 8.2, 8.3 e
  8.4 aprovaram 24/51, 54/149, 12/43, 14/62 e 73/703. O lint dos quatro
  arquivos PHP do incremento e o autoload PSR-4 estrito com 1.821 classes
  também foram aprovados em PHP 8.4.7.
- Nenhum recurso externo real, configuração, Blank, pacote ou commit foi
  acessado. O Microciclo 4 depende de autorização própria.

## Evidências do Microciclo 2 da Sprint 8.5

- `PublicadorPcaScriptcase::executar(...)` preserva a assinatura e o ViewModel
  existente. `executarComEventos(...)` recebe os mesmos cinco argumentos e
  devolve `ResultadoPublicacaoPcaScriptcase`, com resultado, envelopes e aviso
  técnico separados.
- O Composition Root preserva `montar(...)` e oferece a composição interna da
  mesma chamada com fachada, acumulador e conexão. A obtenção dos eventos não
  repete o processamento e cada chamada cria um grafo isolado.
- A exportação preserva ordem e sanitização e acrescenta `executionTimeMs`
  somente ao último envelope da cópia, sem alterar os eventos originais.
- Transação Oracle ainda ativa impede a disponibilização dos eventos. Falha na
  exportação também devolve eventos vazios; nos dois casos, o ViewModel é
  preservado com aviso público fixo, sem tentar commit, rollback ou nova
  publicação.
- A suíte focada aprovou 38 testes/608 asserções; Composition, Integration,
  Observabilidade e contrato público aprovaram 70/959; a suíte padrão aprovou
  935/4.347, e os contratos 8.4 permaneceram verdes com 73/703. O lint dos nove
  arquivos PHP envolvidos, o autoload PSR-4 estrito com 1.820 classes e
  `git diff --check` foram aprovados em PHP 8.4.7.
- Nenhum acesso Oracle ou PNCP real, configuração externa, Blank, pacote ou
  commit foi executado. O Microciclo 3 depende de autorização própria.

## Evidências do Microciclo 1 da Sprint 8.5

- O mantenedor aprovou dez microciclos com pausas independentes, incluindo
  eventos separados do ViewModel, logs pela Blank e ajuda ao operador.
- DI-006, DI-010 e fluxo registram o contrato de `executarComEventos(...)`,
  preservando `executar(...)`, sem incluir eventos em `PublicarPcaResponse`
  ou na resposta ao navegador.
- O resultado de consulta de metadados fornecido pelo mantenedor informa
  `SGI.SEGURANCA_LOG.ACTION` como `VARCHAR2(30 BYTE)` e `DESCRIPTION` como
  `CLOB`. Isso não comprova a gravação pela macro: o gate de logs deverá
  verificar JSON integral, acentos e conteúdo maior que 4.000 bytes.
- Os novos gates ficam restritos à homologação: logs, contenção e smoke sobre
  o consolidado 2026 já excluído, sem mutação PNCP e sem repetir gates da 8.4.
  Cada execução depende de autorização e PRECHECK próprios.
- A validação deste incremento é documental: consistência dos contratos,
  12 referências locais adicionadas sem alvo ausente, escopo do diff e
  `git diff --check` aprovados. Testes de código não se aplicam a este
  incremento. Nenhum código PHP, teste, configuração externa, dado Oracle ou
  recurso PNCP foi alterado; nenhum commit foi criado.

## Evidências do microciclo corretivo da Sprint 8.3

- O ensaio manual no treinamento percorreu publicação, leitura paginada,
  inclusão, exclusão de item e exclusão integral, com limpeza operacional
  confirmada e sem registrar identidades da massa neste documento.
- O ambiente retornou `400` ao consultar o consolidado conhecido depois da
  exclusão. Esse status passou a ser somente um sinal provisório: ausência
  definitiva ainda exige listagem completa sem a mesma UASG.
- A listagem de unidades retornou `404` após páginas `200` válidas; a paginação
  de itens encerrou com `204`. O `404` só encerra unidades em página maior que
  1 e depois de ao menos uma página válida.
- A classificação é uniforme entre ambientes, mas a evidência externa alcança
  somente o treinamento. Nenhum gate de produção foi criado ou executado.
- Mensagens textuais do PNCP permanecem não autoritativas. Fora das condições
  estritas acima, `400` e `404` produzem leitura inconclusiva.
- O Red focado registrou 10 falhas esperadas. O Green aprovou 45 testes e 109
  asserções; a suíte de leitura aprovou 84/213, Infrastructure PNCP 347/1.110,
  contratos locais 8.3 13/58, Composition 11/187 e a suíte padrão 882/3.846.
- Lint dos quatro arquivos PHP afetados, Composer, PSR-4 estrito com 1.792
  classes e preflight foram aprovados no PHP 8.4.7. A configuração externa foi
  apenas lida de forma sanitizada e permaneceu com resíduo nulo. Nenhum gate
  real ou endpoint PNCP foi executado durante o corretivo.
- A primeira execução automatizada do gate nominal após o corretivo completou
  publicação, observações, operações de itens, exclusão integral e ausência
  definitiva, mas falhou antes de emitir relatório e hash: a auditoria do
  runner confundiu a UASG textual curta com um status HTTP numérico igual.
  O resíduo já estava removido quando ocorreu a falha.
- Um novo teste reproduziu o falso positivo. A auditoria passou a comparar a
  UASG como string JSON exata, mantendo credenciais e demais valores sob busca
  por fragmento. O teste do runner aprovou 6/29, os contratos locais 8.3
  aprovaram 14/60 e a suíte padrão permaneceu verde em 882/3.846, preparando a
  nova execução autorizada que produziu a evidência formal abaixo.

## Evidências do Microciclo 4 da Sprint 8.3

- O gate nominal real foi aprovado no treinamento em 03/09/2026. Uma única
  autenticação sustentou o ciclo completo de publicação, observações
  paginadas, inclusão, exclusão de item, exclusão integral e ausência
  definitiva.
- O relatório sanitizado confirmou paginação múltipla, IDs preservados,
  sequencial externo positivo, decimais JSON exatos, 32 eventos seguros e
  limpeza final, sem registrar identidades ou conteúdo de transporte.
- SHA-256:
  `0688185978654e374ddf959f377d8cf619314bc906f81e19cafbdff1388aae2c`.
- O grupo nominal aprovou 1 teste e 3 asserções no PHP 8.4.7. O gate real de
  escrita incerta não foi executado.
- As próximas execuções de `contract:pncp83:nominal` informarão também
  `sequencial_pca_recebido_excluido`, limitado ao sequencial transitório
  recebido e efetivamente removido pelo próprio ciclo. Essa ampliação do
  relatório foi validada localmente, sem nova chamada ao PNCP.

## Evidências do Microciclo 5 da Sprint 8.3

- O preflight sem rede foi confirmado novamente antes da execução real.
- O gate de escrita incerta foi aprovado no treinamento em 03/09/2026 com uma
  autenticação e exatamente um POST. A resposta de sucesso foi descartada de
  propósito, a escrita não foi repetida e o efeito foi localizado por leitura.
- Os identificadores foram preservados e a exclusão terminou com ausência e
  limpeza confirmadas. O relatório registrou 23 eventos sanitizados.
- SHA-256:
  `f68ee8ee61db4ea11598f88a52100bc9e1cb5ca924eb6bcbe16930d458a149ba`.
- O grupo incerto aprovou 1 teste e 3 asserções no PHP 8.4.7. O gate não revelou
  incompatibilidade nem contrato novo relevante; a DI-009 não recebeu nova
  alteração semântica.

## Encerramento da Sprint 8.3

- Infrastructure PNCP aprovou 347 testes e 1.110 asserções; Composition,
  11/187; Presentation, 31/319; Integration, 5/103.
- Os contratos locais 5.1, 5.9, 8.2 e 8.3 aprovaram 24/51, 54/149, 12/43 e
  14/62, respectivamente. A suíte padrão aprovou 882 testes e 3.846 asserções.
- O lint aprovou 325 arquivos PHP; o autoload PSR-4 estrito gerou 1.792 classes.
  Composer, requisitos instalados, catálogo dos 22 comandos, referências,
  ausência dos valores externos conhecidos e `git diff --check` foram
  validados.
- As verificações usaram PHP 8.4.7 porque não havia PHP 8.1 instalado. O
  requisito Composer `^8.1` foi satisfeito, sem execução adicional no runtime
  mínimo.
- Nenhum grupo live ou integração real foi executado no encerramento. Manual
  2.6, OpenAPI e os gates já aprovados permaneceram compatíveis, portanto a
  DI-009 foi revalidada sem nova alteração semântica.

## Evidências da Sprint 8.2

- `ComposicaoOraclePublicacaoPca` tornou-se a única receita de produção dos
  seis adapters Oracle. O `CompositionRootPublicacaoPca` e o gate 8.2 usam
  obrigatoriamente essa mesma subcomposição, sem branch ou seam de teste.
- Testes estruturais comprovaram uma única `ConexaoAdodbEmprestada` nos seis
  adapters. A montagem do Root real não executou ADOdb nem rede PNCP.
- O preflight foi confirmado no PHP 8.1 `cgi-fcgi` de desenvolvimento, com
  perfil de homologação e sem acesso Oracle ou PNCP.
- O modo `commit` foi aprovado com hash sanitizado
  `4d0111f16fa205ecca028a3dc8f1cf2c0ea5b567432f379e453e03a19d3bd6d7`.
  A evidência separou o commit técnico de provisionamento da transação
  controlada principal e confirmou início explícito, lock real na mesma sessão,
  commit concluído, liberação e readquisição do lock, conexão reutilizável,
  nenhuma alteração de negócio e nenhum `Close()`.
- O modo `adapters` foi aprovado com hash sanitizado
  `c21808c0d15d79095dcc79b78f82d1a3e1ac7e2ce612839170d3f2018d36c707`.
  Binds, leituras tipadas, contexto, confirmação temporária, estado e auditoria
  foram exercitados na mesma sessão; o rollback restaurou integralmente a
  confirmação pendente, o estado e a contagem de auditoria.
- A contenção real foi comprovada em duas sessões independentes. Os modos
  `lock_expect_blocked`, `lock_holder` e `lock_expect_available` produziram,
  respectivamente, os hashes sanitizados
  `6037ce8054aedf777f41425b0f422a854137eaedcc07ec8d58767e32d4d26c84`,
  `a0b5a315104cdf2af366c3ced1cc89d6d456ec7271f66d01ba8b61f1d72345bd` e
  `cea92f6a4a93ab0588f0405aad4e76dccd3d7d93ef55041629cce551ce20e028`.
  Houve tradução imediata de `ORA-00054`, liberação posterior e ausência de
  `Close()` nos três relatórios.
- O guia operacional das probes 5.1, 5.9 e 8.2 foi consolidado em
  [guia histórico de execução das probes](../../docs/historico/operacional/guia_de_execucao_das_probes.md); o
  README passou a apenas encaminhar para essa fonte de apoio.
- Por decisão do mantenedor, os arquivos externos permaneceram sob o webroot
  somente no desenvolvimento controlado. Produção continua exigindo registro,
  perfis e configurações fora da release e do webroot.
- A evidência alcança a montagem real do Root e sua subcomposição Oracle; não
  alcança o fluxo público integral até o UseCase, que envolveria autenticação
  PNCP e permanece para as sprints posteriores.

## Evidências da Sprint 8.1

- `PublicadorPcaScriptcase` é a única API conhecida pela Blank e recebe somente
  conexão ADOdb emprestada, login autenticado, `SERVER_NAME`, ano e request ID.
  CNPJ, perfil, registro, allowlist e caminhos não integram o contrato público.
- `PCA_PNCP_PROFILE_REGISTRY_FILE` é o único localizador do registro externo.
  O índice aceita somente `SERVER_NAME => caminho absoluto do perfil`, valida
  todos os caminhos e rejeita registro ou perfil dentro da release.
- A seleção é literal, sem normalizar hostname, concatenar caminho ou inferir
  ambiente. Registro, servidor, caminho, perfil e CNPJ inválidos falham
  fechado, sem default ou fallback silencioso.
- O CNPJ vem exclusivamente do perfil PHP externo, tratado como entrada não
  confiável, e é validado antes de compor `EntradaPublicarPca` com o ano
  recebido da Blank.
- O `X-Request-ID` deve ser UUID v4 e permanece byte a byte idêntico na
  correlação, logger e ViewModel. Ausência ou formato inválido retorna
  `INTERROMPIDA/REQUEST_ID_INVALIDO`, correlação `null` e não acessa registro ou
  serviço; o backend não gera correlação alternativa.
- `CompositionRootPublicacaoPca` valida e materializa o perfil, cria um grafo
  novo por chamada, reutiliza `MontadorNucleoPublicacaoPca` e compartilha uma
  conexão entre seis adapters Oracle, um transporte entre três adapters PNCP e
  um logger com a correlação recebida.
- A fachada centraliza as interrupções públicas e registra sua captura
  inesperada com `EXECUTAR_PUBLICACAO_PCA` e erro seguro, sem transportar a
  exceção. Falha do logger não atravessa a fronteira.
- A prova transversal executou duas chamadas públicas até a preparação da
  Application e as encerrou por uma saída ADOdb local anterior à autenticação.
  Cada ViewModel preservou seu UUID externo; não houve transação, escrita,
  `Close()` ou rede.
- A regressão de Presentation, Composition, Integration, Application e
  entrypoint aprovou 293 testes e 1.833 asserções. A suíte padrão aprovou 864
  testes e 3.786 asserções.
- O autoload PSR-4 estrito gerou 1.761 classes, e o lint aprovou 293 arquivos
  PHP. Direção das dependências, conteúdo proibido, segredos e caminhos,
  correlação e whitespace foram auditados.
- Nenhum preflight, probe, grupo `pncp-live`, Oracle, PNCP ou Scriptcase real
  foi executado. Nenhum commit foi criado.

## Evidências da Sprint 7.4

- A suíte transversal percorre entrada, fachada, Controller, Presenter e
  ViewModels e aprovou 6 testes e 152 asserções.
- O caminho válido preserva chamada única ao UseCase, ordem dos resultados,
  contagens e correlação textual. A saída consumível contém somente ViewModels,
  escalares e valores nulos.
- Entradas inválidas não alcançam o UseCase. Falhas seguras globais e unitárias
  preservam exclusivamente seus códigos e mensagens, e resultados conhecidos
  não são descartados nem fabricados.
- `RuntimeException` e `Error` com SQL, payload, senha, token e stack simulados
  produzem a falha pública fixa sem expor esses detalhes no ViewModel.
- Presentation aprovou 28 testes e 300 asserções; Application aprovou 237
  testes e 978 asserções; Composition aprovou 1 teste e 1 asserção; Integration
  aprovou 3 testes e 70 asserções.
- A suíte padrão aprovou 839 testes e 3.299 asserções. O autoload PSR-4 estrito
  gerou 1.754 classes, e o lint aprovou 286 arquivos PHP.
- As auditorias de direção das dependências, ViewModels, conteúdo proibido,
  sanitização da fachada e whitespace foram aprovadas.
- Nenhum arquivo de produção foi alterado, e nenhum preflight, grupo
  `pncp-live`, probe ou acesso externo foi executado.
- A suíte comprova que a fronteira entrega ViewModels renderizáveis. A prova da
  renderização pela Blank real permanece para a Fase 8.
- Na conclusão da Sprint 7.4, o registro PSR-3 da captura inesperada permanecia
  pendente; essa pendência foi resolvida pela Sprint 8.1 com o logger e a
  correlação compartilhados no grafo final.

## Evidências da Sprint 7.3

- `EntradaPublicarPcaInvalidaException` transporta internamente uma
  `FalhaSegura`; o Controller classifica somente os erros originados ao
  materializar CNPJ e ano, preservando a precedência do CNPJ e impedindo a
  chamada ao UseCase.
- `FachadaPublicarPca` preserva ViewModels e Responses seguras já produzidas e
  converte entradas inválidas em `SOLICITACAO_INVALIDA`.
- Qualquer outro `Throwable` produz `INTERROMPIDA`, código
  `FALHA_INESPERADA`, mensagem pública fixa, contagens zeradas e correlação
  textual exata, sem fabricar resultados ou transportar detalhes da exceção.
- A fachada é a fronteira tipada da Presentation. Conexão emprestada, login,
  `SERVER_NAME`, perfil, geração da correlação e assinatura final do Scriptcase
  permanecem na Fase 8.
- Presentation aprovou 22 testes e 148 asserções; Application aprovou 237
  testes e 978 asserções; Composition aprovou 1 teste e 1 asserção; Integration
  aprovou 3 testes e 70 asserções.
- A suíte padrão aprovou 833 testes e 3.147 asserções. O autoload PSR-4 estrito
  gerou 1.753 classes, e o lint aprovou 285 arquivos PHP.
- Domain e Application não dependem de Presentation. Presentation não depende
  de Infrastructure, Composition ou Scriptcase, e a fachada não realiza
  wiring, serialização ou logging.
- Nenhum preflight, grupo `pncp-live`, probe ou acesso externo foi executado.

## Evidências da Sprint 7.2

- `PublicarPcaPresenter` converte a Response em `PublicarPcaViewModel` e
  `ResultadoUnidadeViewModel`, expondo somente escalares, valores nulos e a
  coleção ordenada de resultados unitários.
- Situações, decisões, identidade individual, modo, ano, UASG, sequencial,
  contagens, correlação e falhas seguras são projetados sem normalização,
  tradução ou criação de mensagens. No modo consolidado, o ID da UO é `null`.
- `PublicarPcaController` entrega a Response ao Presenter e devolve o ViewModel.
  Exceções de entrada e falhas inesperadas continuam reservadas à fronteira
  global da Sprint 7.3.
- Presentation aprovou 14 testes e 80 asserções; Application aprovou 237 testes
  e 978 asserções; Composition aprovou 1 teste e 1 asserção; Integration
  aprovou 3 testes e 70 asserções.
- A suíte padrão aprovou 825 testes e 3.079 asserções. O autoload PSR-4 estrito
  gerou 1.750 classes, e o lint aprovou 282 arquivos PHP.
- Domain e Application não dependem de Presentation. Presentation não depende
  de Infrastructure, Composition ou Scriptcase; seus ViewModels não dependem
  de Domain ou Application.
- Nenhum preflight, grupo `pncp-live`, probe ou acesso externo foi executado.

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
  [Evidências da Sprint 5.9](../../docs/historico/investigacoes/evidencias_parciais_sprint_5_9_contratos_adapters_2026-08-26.md).

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
  [Especificação de logs estruturados](../../docs/vigentes/autoritativos/especificacao_de_logs_estruturados.md),
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
[Evidências dos gates Oracle e PNCP](../../docs/historico/investigacoes/evidencias_sprint_5_1_gates_oracle_pncp_2026-08-17.md).

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
- A conclusão da Sprint 8.2 não autoriza automaticamente a suíte PNCP real da
  Sprint 8.3 nem qualquer novo acesso externo.
- O registro e os perfis reais devem permanecer fora da release e do webroot;
  o servidor deve provisionar `PCA_PNCP_PROFILE_REGISTRY_FILE` com o caminho
  absoluto do índice antes da implantação.
- A exposição dos eventos pela fachada e sua persistência pela Blank por meio
  do `RegistradorEventosTecnicosScriptcase` têm contrato e implementação
  aprovados. A correção 3A foi comprovada no fluxo real da Sprint 9.4; a 7A foi
  instalada e deve ser conferida sem induzir nova escrita no smoke da 9.5.
- Uma falha fatal anterior ao retorno à Blank pode impedir a persistência dos
  eventos acumulados em memória; essa limitação foi aceita conscientemente.

## Preparação corrente do Microciclo 6 da Sprint 8.5

- Os gates reais dos Microciclos 6 a 8 foram destinados ao validaSGI, conectado
  ao Oracle de homologação. O DEV permanece somente para desenvolvimento,
  compilação e ensaio.
- A fotografia de homologação contém planos de 2026 candidatos a publicação.
  Por segurança, o gate de logs não usa essa massa, não chama
  `PublicadorPcaScriptcase` e não acessa o PNCP.
- O PRECHECK do gate de logs possui duas consultas somente leitura: estrutura
  física de `SGI.SEGURANCA_LOG` e ausência do identificador sintético. A
  configuração externa foi criada em `PRECHECK`, com todas as permissões de
  escrita falsas.
- A Blank de teste aceita somente `PRECHECK` e `LOGS`. A segunda etapa continua
  bloqueada até que um novo identificador e o hash canônico do PRECHECK real
  sejam fixados para a execução do registrador próprio.
- A conferência manual no Oracle de homologação foi aprovada em 18/09/2026:
  `ACTION` foi confirmado como `VARCHAR2(30 BYTE)`, `DESCRIPTION` como `CLOB`
  e nenhuma linha possuía o identificador
  `SPRINT85-LOGS-HML-20260918-JSILVA-01`. Essa leitura não substitui o
  PRECHECK ADOdb do validaSGI, que ainda deve produzir o hash canônico.
- A validação local desta adaptação aprovou 26 testes e 240 asserções, além do
  lint dos arquivos PHP e da inspeção de whitespace. O Microciclo 6 permanece
  em andamento até a comprovação real do CLOB e a pausa obrigatória.
- Para evitar o envio do checkout ao validaSGI, foi gerado e verificado o
  artefato temporário `build/pca-pncp-log-gate`. Ele contém o pacote principal
  validado e somente o runtime necessário à probe, totalizando 206 arquivos
  com manifesto. Não contém configuração, segredo, documentação, ferramentas,
  `.git`, PHPUnit ou outras suítes de teste. A Blank passou a carregar esse
  artefato pelo caminho relativo da própria aplicação.
- Em 19/09/2026, o PRECHECK real executado pela conexão ADOdb do validaSGI
  retornou `PRECHECK_LOGS_CONFIRMADO`. Repetiu as duas cardinalidades manuais,
  confirmou zero ocorrências do identificador e calculou o SHA-256 canônico
  `f52668437c192e37eca8757e1250aebb2d87a318961efc03a8e03637536868bd`,
  sem escrita Oracle, acesso PNCP ou escrita de log.
- A configuração externa local foi validada para a etapa `LOGS`, com somente
  `allow_log_writes = true` e o hash fixado. Ela ainda precisa ser copiada para
  o validaSGI antes da chamada única. O gate real e o pós-check permanecem
  pendentes; não repetir diante de resposta incerta.
- A primeira tentativa de `LOGS` foi interrompida antes da macro com
  `CORRELACAO_INVALIDA`. O relatório confirmou `log_writes = false`, portanto
  não houve resposta incerta nem registro a repetir. Esse código demonstra
  somente que o valor lido em `HTTP_X_REQUEST_ID` não satisfez o UUID v4; não
  permite concluir que o servidor deixou de enviar correlação. A Blank de
  teste ganhou diagnóstico somente leitura de `HTTP_X_REQUEST_ID` e
  `HTTP_X_CORRELATION_ID`, sem fabricar identidade. A Blank real não mudou.
- Após o servidor passar a fornecer o UUID v4 esperado, a chamada única de
  `LOGS` alcançou `sc_log_add` e a verificação posterior falhou com
  `PRECHECK_CARDINALIDADE_DIVERGENTE`. `log_writes = true` registra tentativa,
  não confirmação de persistência. O estado passou a ser incerto: é proibido
  repetir a URL e somente o pós-check identificado pode determinar se existem
  zero, uma ou várias linhas.
- A configuração externa local foi imediatamente desarmada para `PRECHECK`,
  com todas as permissões de escrita falsas. A cópia remota ainda deve ser
  substituída antes de qualquer outra interação com a Blank.
- O pós-check manual executado depois dessa tentativa retornou zero linhas.
  Portanto, não existe persistência identificável na
  `SGI.SEGURANCA_LOG` consultada, e a etapa `LOGS` continua proibida de ser
  repetida. O primeiro diagnóstico somente leitura confirmou o banco `SGIHML`,
  serviço `sgihml`, schema e usuário `SGI`, uma única tabela válida com esse
  nome e nenhum sinônimo. A busca do identificador sem restringir a ação e a
  busca histórica por `execute` também retornaram vazias. A estrutura completa
  possui nove colunas, incluindo `INSERTED_DATE`, `USERNAME`, `APPLICATION`,
  `DESCRIPTION` como CLOB e `DESCRIPTION2` como BLOB. Esse diagnóstico levou à
  conferência do esquema de log vinculado à Blank no Scriptcase.
- A causa do pós-check vazio foi confirmada fora do backend: a nova aplicação
  `teste_contracts_sprint85` ainda não estava vinculada ao módulo de logs do
  Scriptcase. Depois da habilitação, uma verificação do hospedeiro registrou
  linha em `SGI.SEGURANCA_LOG`. A chamada seguinte de `PRECHECK` foi recusada
  antes de I/O com `CONFIG_HASH_INVALIDO`, pois o checksum do arquivo havia
  sido colocado no campo reservado ao checkpoint Oracle. A configuração
  externa local voltou ao estado seguro: etapa `PRECHECK`, hash `null` e todos
  os consentimentos de escrita falsos; ela deve substituir a cópia remota.
- Depois da correção e do novo deploy, o PRECHECK real voltou a confirmar as
  cardinalidades 2 e 0, o `X-Request-ID` como UUID v4 e o checkpoint canônico
  `f52668437c192e37eca8757e1250aebb2d87a318961efc03a8e03637536868bd`,
  sem escrita Oracle, acesso PNCP ou escrita de log. A configuração externa
  local foi validada para a chamada única de `LOGS`, mantendo somente
  `allow_log_writes = true`.
- A chamada única de `LOGS` após a habilitação do módulo alcançou a macro,
  mas o Scriptcase emitiu uma página HTML com `ORA-01704: literal de string
  extenso demais` antes do relatório JSON
  `PRECHECK_CARDINALIDADE_DIVERGENTE`. Assim, esta instalação não comprovou a
  gravação integral do evento maior que 4.000 bytes e também não conteve a
  mensagem técnica na fronteira HTTP. Conforme a condição de bloqueio do
  plano, o Microciclo 6 está bloqueado e a macro não pode ser substituída
  silenciosamente por SQL direto. A configuração externa local foi desarmada
  novamente para `PRECHECK`, hash `null` e todos os consentimentos falsos; a
  cópia remota deve ser substituída e a etapa `LOGS` não deve ser repetida.
- O mantenedor autorizou o Microciclo 6A corretivo antes do Microciclo 7. A
  direção aceita substitui `sc_log_add` somente para os eventos técnicos do
  PCA por uma fachada pública concreta e um adapter Oracle de CLOB, mantendo a
  mesma conexão emprestada e separando a nova transação técnica da transação
  de negócio já encerrada. A implementação depende primeiro da confirmação
  somente leitura de `UpdateClob`, da geração do ID e das regras físicas da
  tabela.
- O diagnóstico do validaSGI confirmou `UpdateClob`, `BeginTrans`,
  `CommitTrans` e `RollbackTrans` na conexão ADOdb. `SEGURANCA_LOG.ID` é chave
  primária obrigatória, sem default ou identity, e existe o trigger habilitado
  `TR_SEGURANCA_LOG` antes de cada insert. A fonte confirmou que, quando
  `:NEW.ID` é nulo, o trigger atribui `ID_LOG.NEXTVAL`; por isso o registrador
  omite o ID no insert inicial e usa a chave gerada para atualizar o CLOB. A
  amostra também revelou quatro registros
  automáticos `access`, sem descrição, criados ao abrir a Blank; esse evento
  automático deve ser desabilitado porque não pertence ao contrato técnico do
  PCA e deixa de ser necessário com o registrador próprio.
- O corretivo 6A foi concluído localmente. A fachada pública
  `RegistradorEventosTecnicosScriptcase` delega à composição, e o adapter
  Oracle insere um marcador curto, recupera a única chave gerada pelo trigger e
  usa `UpdateClob` para gravar o JSON integral. Cada evento usa uma transação
  técnica própria, iniciada somente depois do retorno seguro da publicação.
- A regressão local aprovou 25 testes/142 asserções no recorte afetado,
  951/4.500 na suíte padrão e 27/250 nos contratos 8.5. Os contratos 5.1, 5.9,
  8.2, 8.3 e 8.4 também permaneceram verdes com 24/51, 54/149, 12/43, 14/62 e
  73/703. O PSR-4 estrito carregou 1.838 classes e o lint dos arquivos afetados
  passou.
- O pacote principal reconstruído contém 197 arquivos e carrega as duas
  fachadas públicas. O artefato `build/pca-pncp-log-gate` contém 209 arquivos,
  carrega os quatro entrypoints e não contém configurações nem segredos.
- A nova prova real foi isolada pelo identificador
  `SPRINT85-LOGS-HML-20260921-JSILVA-02`. Antes de qualquer escrita, a
  configuração externa deve ficar em `PRECHECK`, com hash nulo e todos os
  consentimentos falsos, e o SQL manual deve confirmar zero ocorrências. O
  Microciclo 7 permanece bloqueado até a conclusão e a pausa do Microciclo 6.
- A configuração externa local foi atualizada para esse novo identificador,
  permaneceu desarmada e passou no lint PHP. A cópia remota ainda precisa ser
  substituída antes do novo PRECHECK.
- O novo PRECHECK manual e o PRECHECK pela conexão ADOdb do validaSGI foram
  aprovados em 21/09/2026. Ambos confirmaram as duas colunas físicas e zero
  ocorrências de `SPRINT85-LOGS-HML-20260921-JSILVA-02`; a Blank também
  confirmou `X-Request-ID` UUID v4 e os quatro métodos ADOdb necessários. O
  hash canônico permaneceu
  `f52668437c192e37eca8757e1250aebb2d87a318961efc03a8e03637536868bd`.
- A configuração externa local foi validada para a chamada única de `LOGS`,
  com somente `allow_log_writes = true`; escritas Oracle de negócio e PNCP
  permanecem falsas. Ela deve substituir a cópia remota antes da chamada. Uma
  resposta incerta proíbe repetição e exige o pós-check identificado.
- O Microciclo 6 foi concluído em 21/09/2026. A chamada única retornou
  `LOGS_CONFIRMADOS` e encontrou exatamente uma linha `execute`, com 11.179
  bytes. O SHA-256 enviado e o persistido coincidiram em
  `d48fcf1daf915e5064d2b3df4b00b3805e9085c5b35393cfdcd494a429c3f326`.
  A releitura automática confirmou acentos e mapas vazios.
- O pós-check independente confirmou a mesma linha, o identificador correto,
  11.179 caracteres ASCII, escapes de acentuação, mapas vazios e JSON válido.
  Não houve acesso nem escrita PNCP e não houve escrita Oracle de negócio.
- A configuração externa local foi desarmada após a prova: `PRECHECK`, hash
  nulo e todos os consentimentos de escrita falsos. A cópia remota deve ser
  substituída por esse estado seguro; a URL `LOGS` não deve ser repetida.
- A pausa obrigatória do Microciclo 6 foi cumprida. O Microciclo 7 recebeu
  autorização independente e conserva checkpoint próprio e novo PRECHECK.

## Preparação corrente do Microciclo 7 da Sprint 8.5

- O mantenedor autorizou o Microciclo 7 em 21/09/2026. Nenhuma chamada real de
  contenção foi executada nesta preparação.
- A Blank `teste_contencao_sprint85` aceita somente `PRECHECK`, `CONTENCAO` e
  `DISPONIBILIDADE`. Seu runtime fica no artefato temporário
  `build/pca-pncp-containment-gate`, separado do gate encerrado de logs.
- O roteiro `contencao_precheck.sql` contém doze SELECTs materializados para a
  fotografia de homologação atual: 112 planos e 112 demandas ativas nas UOs
  3, 94 e 149, nenhuma auditoria de negócio e duas âncoras. O identificador técnico novo é
  `SPRINT85-CONTENCAO-HML-20260921-JSILVA-01` e deve estar ausente.
- O gate exige que uma sessão Oracle externa retenha exatamente a âncora
  `UNIDADE_EXTERNA/2026/930281`. Uma sonda `FOR UPDATE NOWAIT` na conexão da
  Blank precisa receber `ORA-00054` antes de chamar o fluxo público.
- Como a autenticação PNCP antecede o lock no fluxo vigente, o transporte de
  teste permite somente `POST /v1/usuarios/login`; qualquer outra operação
  mutante é bloqueada antes do envio. O snapshot Oracle completo deve ser
  idêntico antes e depois, inclusive a ausência de auditoria de negócio.
- O pacote principal foi reconstruído e validado com 197 arquivos. O artefato
  de contenção foi validado com 212 arquivos, entrypoints carregáveis e sem
  configuração ou segredo. A configuração externa modelo permanece em
  `PRECHECK`, hash nulo e todos os consentimentos falsos.
- Após confirmação do mantenedor, o perfil de homologação recebeu o mesmo
  CNPJ, credenciais PNCP de treinamento e mapeamentos Oracle do perfil DEV. O
  CA bundle público foi colocado ao lado do perfil e passou a usar caminho
  relativo a `__DIR__`, compatível com a cópia do conjunto para o Linux. O
  preflight local confirmou ambiente `HOMOLOGACAO`, charset `Windows-1252`, 11
  grupos de mapeamento e as 12 consultas somente leitura, sem I/O externo.
- As configurações externas foram centralizadas em
  `/var/www/configuracoes/pca-pncp`, fora da DocumentRoot `/var/www/html`. A
  pasta deve conter somente `sprint85.php`, `registro.php`,
  `perfil-homologacao.php` e `ca-bundle.crt`, ser legível pelo usuário do
  PHP/Apache e permanecer inacessível por HTTP. O `registro.php` autoriza
  somente o host validaSGI e referencia o perfil por `__DIR__`.
- Em 21/09/2026, o mantenedor confirmou a instalação dos quatro arquivos em
  `/var/www/configuracoes/pca-pncp` no validaSGI. A tentativa de acesso por URL
  retornou “The requested URL was not found on this server”, confirmando que a
  pasta externa não é publicada pela DocumentRoot.
- A conferência manual seguinte recusou a fotografia preparada. As consultas
  4 a 11 divergiram do cenário fixado: foram encontradas as UOs 3, 94 e 149;
  apenas três versões da UO 3; uma versão vigente; três demandas ativas; zero
  auditorias correspondentes e zero controles de concorrência. Somente a
  ausência do identificador técnico na consulta 12 permaneceu conforme.
- O checkpoint não será recalculado para aceitar silenciosamente esse estado.
  A Blank, o lock e a etapa `CONTENCAO` permanecem proibidos até a escolha e a
  preparação explícita de uma nova massa de homologação, seguida por nova
  conferência manual das doze consultas.
- O mantenedor confirmou que a divergência ocorreu após a replicação semanal
  de produção para homologação e autorizou preparar uma nova massa. O primeiro
  passo versionado é o diagnóstico somente leitura em
  `database/seeds/sprint85_contencao_hml/01_diagnostico_pre_seed.sql`: ele
  confirma o ambiente, procura duas UOs sem planos de 2026, mede colisões e
  inventaria colunas, constraints, dependências e triggers. O DML do seed será
  produzido somente após essa evidência, sem alterar dados replicados.
- O diagnóstico confirmou `SGIHML`, exercício 2026 válido, configuração
  consolidada e uma única UO livre (1). A fotografia replicada possui planos e
  demandas ativas nas UOs 3, 94 e 149; os identificadores antigos também estão
  ocupados por dados atuais. Para a contenção, o mantenedor autorizou manipular
  HML e sugeriu aproveitar as UOs 3 e 94. Como o modo consolidado descobre todas
  as UOs do exercício, o checkpoint seguro deverá incluir também a UO 149. Uma
  consulta adicional verificará a projeção operacional antes de preparar o
  seed mínimo dos controles. Essa escolha vale para o Microciclo 7 e não atende
  ainda à massa vazia e excluída exigida pelo smoke do Microciclo 8.
- A consulta adicional confirmou 112 planos de 2026, todos sem estratégia,
  UASG, estado ou sequencial. Foi preparado um seed mínimo e reversível,
  exclusivo de `SGIHML`: ele exige as cardinalidades 3/38/71 das UOs 3/94/149,
  preserva as 112 demandas ativas, projeta somente
  `CONSOLIDADO/930281` nos planos e cria os controles `ESTRATEGIA_ANUAL` e
  `UNIDADE_EXTERNA/930281`. Precheck, post-check e limpeza bloqueante ficam na
  mesma pasta. Nenhum desses DMLs foi executado.
- O precheck bloqueante do seed foi aprovado manualmente no `SGIHML`: UO 3
  com 3 planos/demandas ativas, UO 94 com 38 e UO 149 com 71; os 112 planos
  permanecem sem estratégia, UASG, estado ou sequencial, e não existe controle
  de concorrência para 2026. A fotografia corresponde exatamente às guardas de
  `03_aplicar_seed.sql`; o DML ainda não foi executado.
- O mantenedor executou `03_aplicar_seed.sql`; o cliente informou
  `Updated Rows = -1`, comportamento esperado para um bloco PL/SQL sem uma
  contagem agregada de linhas. A leitura independente já encontrou exatamente
  os controles `ESTRATEGIA_ANUAL/2026/ESTRATEGIA_ANUAL` e
  `UNIDADE_EXTERNA/2026/930281`. A aplicação permanece pendente da conferência
  das três consultas de `04_postcheck_seed.sql`, especialmente a projeção dos
  112 planos e a preservação das demandas.
- O post-check confirmou integralmente o seed: 112 planos conformes e zero
  divergentes; exatamente os controles `ESTRATEGIA_ANUAL` e
  `UNIDADE_EXTERNA/930281`; e a massa preservada nas cardinalidades 3/3/3,
  38/38/38 e 71/71/71 para as UOs 3, 94 e 149. A limpeza permanece disponível,
  mas não deve ser executada antes do encerramento do gate de contenção. O
  próximo passo é conferir as doze consultas atualizadas e fixar seu novo hash
  pelo PRECHECK da Blank.
- A conferência manual das doze consultas atualizadas foi aprovada a partir de
  `docs/investigacoes/sprint85_fotografia_oracle_microciclo7.json`. As
  cardinalidades foram `2, 1, 1, 3, 112, 3, 112, 112, 112, 0, 2, 0`; as
  consultas 10 e 12 vazias confirmam, respectivamente, ausência de auditoria de
  negócio para o exercício e ausência do identificador técnico novo. A
  projeção contém somente `CONSOLIDADO/930281`, sem estado ou sequencial, e os
  dois controles são exatamente os esperados. O próximo passo continua sendo o
  PRECHECK da Blank para obter o hash canônico pela conexão ADOdb; a etapa
  `CONTENCAO` ainda não está autorizada para execução.
- O PRECHECK da Blank no validaSGI confirmou novamente as doze cardinalidades
  e produziu o hash canônico ADOdb
  `1a64c049431e093f784095dd66c31aef757415aaed7309f053adc0cab691a77e`.
  O relatório confirmou ausência de escrita Oracle, acesso ou escrita PNCP e
  escrita de log. A configuração externa local foi preparada para
  `CONTENCAO`, com esse hash e somente `allow_log_writes = true`; ela ainda
  precisa substituir a cópia remota antes da retenção manual da âncora.
- A sessão Oracle A reteve exatamente a âncora
  `UNIDADE_EXTERNA/2026/930281`. A chamada única da etapa `CONTENCAO` retornou
  `CONTENCAO_CONFIRMADA`, situação global `CONCLUIDA_COM_PENDENCIAS` e unidade
  consolidada `BLOQUEADO/UNIDADE_TRABALHO_BLOQUEADA`. Os hashes antes e depois
  permaneceram iguais a
  `1a64c049431e093f784095dd66c31aef757415aaed7309f053adc0cab691a77e`,
  confirmando preservação do snapshot e da auditoria de negócio. O transporte
  realizou somente o login PNCP com HTTP 200, sem escrita PNCP, e registrou um
  evento técnico.
- Depois do `ROLLBACK` na mesma sessão Oracle A, a etapa `DISPONIBILIDADE`
  retornou `ANCORA_DISPONIVEL`, sem escrita Oracle, acesso PNCP ou log. O
  Microciclo 7 está concluído. A configuração externa local foi desarmada para
  `PRECHECK`, hash nulo e todos os consentimentos falsos; essa cópia segura deve
  substituir a configuração remota. A limpeza da massa e qualquer preparação
  do smoke ficam adiadas até a autorização independente do Microciclo 8.

## Preparação corrente do Microciclo 8 da Sprint 8.5

- O mantenedor autorizou o Microciclo 8 em 21/09/2026. Nenhum DML do smoke e
  nenhuma chamada da Blank real foram executados nesta preparação.
- A fotografia aprovada possui 112 planos sem confirmação e 112 demandas
  participantes: 106 em situação 2 e seis em situação 3, nos IDs 795, 799,
  815, 824, 857 e 867. As versões vigentes são os planos 143, 124 e 224; o
  plano intermediário 103 permanece sem confirmação.
- A camada `database/seeds/sprint85_smoke_hml` prepara, somente em SGIHML, uma
  fotografia vazia com os 112 planos em
  `CONSOLIDADO/930281/EXCLUIDO/36`. Precheck, aplicação, post-check e limpeza
  possuem cardinalidades bloqueantes; a limpeza restaura as situações e aceita
  confirmação somente nas três versões vigentes.
- `smoke-config.example.php` e `checkpoints/smoke_precheck.sql` registram as
  três UOs, fotografia zero, ciclo de 112 demandas, projeção de 112 planos,
  auditoria zero, dois controles e identificador técnico novo. A configuração
  externa local permanece em `PRECHECK`, hash nulo e todos os consentimentos
  falsos. O próximo passo é a conferência manual de `01_precheck_seed.sql`.
- A preparação local aprovou 43 testes e 379 asserções dos contratos 8.5. A
  suíte padrão permaneceu verde com 951 testes e 4.500 asserções, e o PSR-4
  estrito carregou 1.843 classes. O pacote principal foi reconstruído e
  verificado com 197 arquivos, sem dependências de desenvolvimento,
  configurações ou segredos, e com as duas fachadas públicas carregáveis.
- O preflight da configuração externa do smoke confirmou etapa `PRECHECK`,
  ambiente `HOMOLOGACAO` e doze consultas somente leitura sem acessar Oracle
  ou PNCP. A configuração local está pronta, mas não deve substituir a cópia
  remota antes da aprovação do precheck manual do seed.
- O precheck manual do seed do smoke foi aprovado no `SGIHML`: 112 planos
  conformes e sem confirmação; 112 demandas, sendo 106 em situação 2 e seis
  em situação 3 nos IDs 795, 799, 815, 824, 857 e 867; versões vigentes 143,
  124 e 224 sem confirmação; dois controles; nenhuma auditoria de negócio; e
  plano intermediário 103 sem confirmação. A massa satisfaz integralmente as
  guardas de `02_aplicar_seed.sql`.
- O mantenedor executou `02_aplicar_seed.sql` com sucesso. O post-check
  confirmou 112 planos em `CONSOLIDADO/930281/EXCLUIDO/36`, zero divergentes e
  zero confirmações; 112 demandas canceladas e nenhuma participante; os dois
  controles esperados; e o plano 103 ainda sem confirmação. A limpeza
  reversível permanece disponível, mas não deve ser executada antes das
  evidências do smoke. O próximo passo é conferir as doze consultas de
  `checkpoints/smoke_precheck.sql`.
- A conferência manual das doze consultas do smoke foi aprovada com as
  cardinalidades `2, 1, 1, 3, 112, 3, 0, 112, 112, 0, 2, 0`. As consultas 7,
  10 e 12 vazias confirmam fotografia vigente vazia, ausência de auditoria de
  negócio e ausência do identificador técnico novo. As 112 linhas do ciclo
  estão na situação 4, as 112 projeções permanecem em
  `CONSOLIDADO/930281/EXCLUIDO/36` e os controles são exatamente os dois
  esperados. Falta repetir o PRECHECK pela conexão ADOdb da Blank de teste.
- O PRECHECK ADOdb confirmou as mesmas doze cardinalidades e produziu o hash
  canônico `f0c17e5e43f8aa5fc89cff3c211838ed5e64f8d0d39c2487afe2ec6d3fd6013c`,
  sem escrita Oracle, acesso PNCP ou log. A configuração externa local foi
  armada para `SMOKE`, com escrita Oracle interna e logs permitidos, escrita
  PNCP falsa e o hash fixado; o preflight desse estado foi aprovado.
- O pós-check somente leitura foi materializado em `smoke_postcheck.sql` para
  conferir projeção, as três confirmações vigentes, plano 103, demandas,
  auditoria, controles e os eventos técnicos localizados pela correlação da
  resposta. Seus sete SELECTs passaram na validação local.
- A aplicação real e o pacote foram instalados no validaSGI. Uma abertura
  manual por POST de navegação foi recusada com `ENTRADA_HTTP_INVALIDA`, antes
  de chamar a fachada. A Blank foi corrigida para tratar como publicação
  somente POST com `Content-Type: application/json`; POSTs de menu agora
  renderizam a interface. As mensagens fixas da resposta também passaram a ser
  construídas em UTF-8 a partir de fonte ASCII, evitando caracteres de
  substituição após a compilação Scriptcase. O teste focado aprovou 7 testes e
  81 asserções, e o lint PHP passou.
- O `export PCA_PNCP_PROFILE_REGISTRY_FILE=...` executado em um terminal não é
  herdado pelo Apache já iniciado. Antes da chamada única do smoke, o caminho
  `/var/www/configuracoes/pca-pncp/registro.php` ainda precisa ser provisionado
  de forma persistente ao processo PHP e a Blank corrigida precisa ser
  recompilada.
- Após a correção, os contratos da Sprint 8.5 aprovaram 47 testes e 432
  asserções; a suíte padrão aprovou 953 testes e 4.513 asserções; e o autoload
  PSR-4 estrito carregou 1.843 classes.
- O mantenedor recompilou a Blank, confirmou que o POST do menu renderiza a
  interface e fez a chamada única do smoke para 2026. O servidor respondeu HTTP
  500 com `INTERROMPIDA/FALHA_INESPERADA` e sem correlação na resposta. O
  pós-check eliminou a incerteza funcional: os 112 planos e as 112 demandas
  foram preservados; os planos vigentes 143, 124 e 224 foram confirmados; o
  plano 103 continuou sem confirmação; não houve auditoria; e os dois controles
  permaneceram. Quatro eventos integrais com a correlação
  `ad5d9e3f-3ed2-45da-a3e6-aedf5ef83641` comprovam autenticação, busca com zero
  candidatos e persistência da convergência. O backend concluiu o smoke; a
  falha ocorreu depois, na fronteira de resposta da Blank, e a chamada não deve
  ser repetida.
- A Blank foi corrigida para interpretar qualquer corpo JSON público válido,
  inclusive quando o HTTP não for 2xx, e para preservar um UUID de correlação
  válido no fallback externo. Resta comparar os arquivos implantados e eliminar
  eventual pacote ou bytecode antigo antes de validar a interface sem repetir o
  smoke.
- O mantenedor instalou o conteúdo integral de `build/pca-pncp-backend`
  diretamente em `/var/www/html/PCA_PUBLICAR_B/backend`. Manifesto, `src/` e
  `vendor/` estão presentes, e os SHA-256 das duas fachadas públicas, do DTO de
  saída, dos dois ViewModels e do executor coincidem com o pacote local. A causa
  técnica do HTTP 500 anterior não foi atribuída a cache; o estado funcional e
  os eventos são as evidências aceitas do smoke.
- A configuração externa foi desarmada e seu preflight confirmou `PRECHECK`,
  todas as escritas falsas e ausência de I/O. A limpeza da camada do smoke foi
  executada com sucesso: 112 planos sem estado, sequencial ou confirmação; 106
  demandas em situação 2 e seis em situação 3; versões vigentes 143, 124 e 224
  sem confirmação; plano 103 preservado; auditoria vazia; e os dois controles
  da contenção ainda presentes. A última etapa é remover a preparação da
  contenção e conferir o novo pós-check somente leitura.
- A limpeza da contenção foi executada e o pós-check final confirmou 112 planos
  sem estratégia, UASG, estado, sequencial ou confirmação; 106 demandas em
  situação 2 e seis em situação 3; nenhum controle para 2026; plano 103 sem
  confirmação; e auditoria de negócio vazia. O Microciclo 8 está concluído, a
  configuração externa continua desarmada e a chamada do smoke não deve ser
  repetida.
- A pausa obrigatória ao fim do Microciclo 8 foi cumprida antes da autorização
  independente do Microciclo 9.
- O mantenedor autorizou o Microciclo 9. A implementação adicionou o
  botão `Ajuda` ao lado de `Publicar` e um modal SweetAlert estático com as nove
  seções operacionais previstas. A ajuda possui rolagem responsiva, fechamento
  por teclado, devolução explícita do foco e bloqueio durante processamento;
  não faz requisições, não registra eventos e não altera dados. O mantenedor
  atualizou a Blank e confirmou no navegador que o botão funciona. O lint PHP e
  o teste focado passaram novamente com 9 testes/115 asserções; os contratos
  8.5, com 48/461. A suíte padrão anteriormente aprovada permanece em
  954/4.540. O Microciclo 9 está concluído e aplica-se a pausa obrigatória antes
  do Microciclo 10.
- O mantenedor autorizou o Microciclo 10. O pacote autocontido final foi
  reconstruído e validado com 196 arquivos de conteúdo mais o manifesto, sem
  dependências de desenvolvimento, configurações ou segredos. As duas fachadas
  públicas são carregáveis. O SHA-256 do manifesto é
  `4de4c0bd063d8388e42b85a2746caed3d5d315113dd85fa34d368bd5a773e187`.
  A versão final separada do `onExecute` tem SHA-256
  `7a78f42ccc198407b3b4549c677ccfcd3fe0850cc1b4ab5542bd265724f171ee`;
  o mantenedor confirmou o funcionamento do botão de ajuda na Blank instalada.
  A regressão final aprovou a suíte padrão com 954 testes/4.542 asserções e os
  contratos 5.1, 5.9, 8.2, 8.3, 8.4 e 8.5 com 24/51, 54/149, 12/43, 14/62,
  73/703 e 48/461. O lint aprovou 404 arquivos PHP; o PSR-4 estrito gerou 1.843
  classes; e os requisitos da plataforma foram atendidos no PHP 8.4.7. Nenhum
  gate real foi repetido, nenhum recurso de produção foi acessado e nenhum
  commit foi criado. A Sprint 8.5 está concluída; o próximo trabalho pertence à
  Fase 9 e exige planejamento e autorização próprios.
