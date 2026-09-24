# Sprint 9.4 — Migração assistida e recuperação

**Status:** concluída em 24/09/2026; Microciclos 1 e 2 concluídos em 23/09/2026 e
Microciclo 3 concluído em 24/09/2026. A estratégia corrente foi normalizada
para `INDIVIDUAL`, o novo baseline foi aprovado e os Microciclos 4 e 5 foram
concluídos em 24/09/2026. O Microciclo 6 foi concluído em 24/09/2026. No
Microciclo 7, 2027 foi reconciliado e confirmado; 2026 permanece bloqueado por
HTTP 403 do PNCP. O Microciclo corretivo 3A foi implementado, validado, empacotado e
implantado em produção em 24/09/2026, sem execução funcional da Blank durante
a implantação. O Microciclo corretivo 7A foi implementado, validado e
empacotado e implantado em produção em 24/09/2026. O Microciclo 8 validou a
recuperação por exercício de mesa e removeu os artefatos temporários. A Sprint
9.5 continua sujeita a planejamento e autorização próprios.

## Objetivo e fontes

Tratar a publicação manual legada conhecida e inventariar os exercícios 2026 e
2027 sem adoção automática. A coleta usa diagnóstico temporário estritamente
somente leitura; qualquer vínculo futuro será materializado por SQL Oracle
específico, transacional e protegido. Depois do vínculo, o fluxo normal fará a
confirmação ou a reconciliação dos itens.

Aplicam-se a Sprint 9.4 do roadmap, `DI-002`, `DI-003`, `DI-004`, `DI-005`,
`DI-007`, `DI-008`, `DI-009`, `DI-010` e o estado operacional vigente. O escopo
original não altera APIs públicas, Domain, Application ou fluxo normal. As
correções operacionais aprovadas preservam essas fronteiras e não alteram as
regras de negócio.

## Microciclos aprovados

1. Preparar localmente o diagnóstico: contrato `Sprint94`, gate temporário
   separado da release, Blank mínima, relatório canônico com SHA-256, guia e
   comandos de construção/verificação. Aceitar somente servidor e usuário
   autorizados, perfil `PRODUCAO`, anos 2026/2027 e etapas `PRECHECK` ou
   `INVENTARIO`; permitir no Oracle apenas `SELECT` e no PNCP apenas autenticação
   e leituras. Validar sem acesso externo e pausar.
2. Construir e verificar o artefato sem configuração ou segredo; instalar o
   gate temporário desarmado em produção e executar apenas o PRECHECK estrutural,
   sem Oracle ou PNCP. Pausar.
3. Inventariar Oracle e PNCP para 2026/2027, com paginação completa e relatório
   sanitizado; confirmar manualmente a procedência; preservar desconhecidos e
   ambiguidades; não repetir nem adotar os sequenciais de homologação 16/17/18.
   Pausar com as evidências.
**Microciclo corretivo 3A.** Corrigir localmente, por TDD, a perda do resultado
conhecido na fronteira Scriptcase e a ausência da decisão nos eventos técnicos.
Reproduzir sem acesso externo o cenário em que a unidade conclui e uma falha
posterior da Blank substitui o resultado por `FALHA_INESPERADA`; preservar o
resultado de negócio já conhecido diante de falha de serialização ou registro
técnico; fornecer somente diagnóstico sanitizado da etapa; e incluir
`context.decisao` com `PUBLICAR`, `RETIFICAR`, `EXCLUIR_PCA` ou
`NENHUMA_ACAO` nos eventos que já possuem o contexto da decisão. Manter
`SEGURANCA_LOG.ACTION = execute`, não transformar observabilidade em auditoria,
não expor exceções ou dados sensíveis e não repetir a escrita já realizada no
`validaSGI`. Validar localmente, preparar a implantação e pausar antes de
qualquer alteração externa.

4. Gerar um SQL exclusivo por legado comprovado, com fotografia anterior,
   bloqueio, pré-condições, `UPDATE` limitado, pós-check e sem `COMMIT`
   automático. Proteger estaticamente e pausar.
5. Repetir o inventário e exigir o hash aprovado; aplicar um vínculo por vez em
   transação Oracle explícita, com `ROLLBACK` diante de qualquer divergência.
   Não chamar PNCP e pausar por exercício/candidato.
6. Avaliar Oracle e PNCP somente por leitura e demonstrar a diferença entre
   `ID_DEMANDA` vigente e `numeroItem`: `NENHUMA_ACAO`, `RETIFICAR` ou
   `EXCLUIR_PCA`. Não comparar atributos item a item. Pausar.
7. Reconciliar cada exercício em microciclo independente e autorizado, com uma
   única execução da `PCA_PUBLICAR_B` para a ação demonstrada. Resultado
   inesperado ou resposta incerta interrompe o avanço; não repetir
   automaticamente.
8. Finalizar e validar por exercício de mesa o runbook de recuperação, remover
   de produção a Blank, o gate e a configuração temporária, preservar apenas
   fontes e evidências sanitizadas e atualizar roadmap, estado e catálogos.
   Conferir também que o guia de configuração de produção mantém um checklist
   explícito para: pacote autocontido no diretório da Blank, segredos fora do
   webroot com menor privilégio, variável de ambiente persistente no runtime do
   Apache/PHP e aplicação controlada das migrações de configuração e unidades
   orçamentárias, sempre com precheck, post-check e registro da implantação.

## Ajuste operacional de topologia aprovado no Microciclo 3

- O mantenedor confirmou que os candidatos de 2026 nas UASGs 205/sequencial 4,
  207/sequencial 2 e 210/sequencial 3 são publicações oficiais. Eles devem ser
  preservados, vinculados individualmente por SQL específico e reconciliados
  pelo fluxo normal.
- O candidato consolidado de 2027, UASG 930281/sequencial 1, foi confirmado
  como a publicação manual criada pelo mantenedor via Swagger. Diante da decisão
  gerencial de padronizar os dois exercícios em `INDIVIDUAL`, ele não será
  vinculado localmente.
- A retirada dessa publicação consolidada é uma intervenção manual e nominal,
  nunca uma compensação automática. A mudança da configuração corrente para o
  literal exato `INDIVIDUAL` somente ocorre depois da comprovação de ausência
  definitiva, em transação Oracle protegida e sem `COMMIT` automático.
- Depois da mudança de configuração, um novo inventário integral substitui o
  hash anterior antes da preparação dos SQLs de vínculo de 2026. O fluxo normal
  deverá criar as publicações individuais de 2027 somente no Microciclo 7.

## Proteções permanentes da sprint

- Nenhum candidato será adotado, excluído ou compensado automaticamente.
- O inventário não contém DML, controle transacional mutante, escrita PNCP ou
  log persistido.
- Relatórios não incluem credenciais, JWT, SQL, resposta bruta ou CNPJ integral.
- O SQL mutante não será genérico e só existirá depois do inventário real e da
  confirmação de procedência.
- Cada pausa prevista exige autorização explícita antes do próximo microciclo.
- O Microciclo 7 permanece bloqueado até a correção 3A estar implementada e
  validada em ambiente autorizado; a conclusão do inventário somente leitura
  do Microciclo 3 não depende dessa correção.
- Nenhum commit será criado sem solicitação explícita.

## Validação

Aplicar TDD a configuração, guardas Oracle/PNCP, paginação, relatório,
sanitização e empacotamento. No corretivo 3A, executar o caminho real de
adaptação da Blank em teste, demonstrar que falha posterior não apaga um
resultado conhecido, cobrir as quatro decisões nos eventos técnicos e provar
que nenhum diagnóstico expõe dados proibidos. Conforme os microciclos
avançarem, acrescentar testes estáticos dos SQLs, suíte Sprint 9.4, suíte
padrão, contratos relacionados, lint, PSR-4 estrito, validação Composer,
pacotes, busca de segredos e `git diff --check`.

## Critérios de saída

A sprint só termina quando 2026 e 2027 estiverem integralmente inventariados e
classificados; a publicação manual estiver vinculada e reconciliada, tiver sido
retirada de modo assistido com ausência definitiva comprovada ou houver bloqueio
nominal aceito; nenhum candidato tiver sido adotado/excluído
automaticamente; a Blank preservar o resultado de negócio conhecido e os
eventos aplicáveis identificarem explicitamente a decisão; o procedimento de
recuperação estiver validado; e todos os artefatos temporários tiverem sido
removidos de produção.

## Evidências do Microciclo 1

- Contrato Sprint 9.4: 20 testes e 52 asserções verdes.
- Suíte padrão: 957 testes e 4.577 asserções verdes.
- Artefato separado construído e validado com 207 arquivos conferidos,
  entrypoints carregáveis e sem configurações ou segredos.
- Lint dos arquivos PHP novos, Composer e PSR-4 estrito aprovados.
- Nenhum acesso Oracle, PNCP, Scriptcase ou produção foi executado.

## Evidências do Microciclo 2

- O artefato separado foi novamente validado com 207 arquivos, entrypoints
  carregáveis e sem configurações ou segredos. O arquivo de transferência teve
  SHA-256
  `1bcc782621f7210334894a63569fc165031dcf650a9d44684067f162c12ba6f3`;
  o manifesto, `d751284707feba3b56e97489deec1f8c2ed9711709309115e60a008cdb1b6164`.
- Uma primeira instalação foi feita inadvertidamente no `validaSGI`. A guarda
  de hostname recusou o PRECHECK com `SERVER_NAME_DIVERGENTE` antes de Oracle
  ou PNCP. A Blank, o gate, a configuração e o arquivo de transferência foram
  removidos desse ambiente antes da retomada.
- No servidor correto `SEMAE-SGIPROD`, a configuração externa desarmada passou
  no lint, manteve o SHA-256
  `905047a9c25094ad5376751bf38c161e20ab3dde9ac26207c1500ae2a9f6d550`
  e ficou legível, mas não gravável, pelo usuário do PHP.
- A Blank temporária foi publicada sem execução prévia; o gate foi instalado
  em sua pasta, carregado como `www-data` e o arquivo de transferência foi
  removido do webroot.
- A chamada única de PRECHECK confirmou perfil `PRODUCAO`, anos 2026/2027,
  todas as dependências disponíveis e escritas Oracle, PNCP e logs desarmadas.
  O SHA-256 canônico do relatório foi
  `190cb1ca388227f41cc889486b1434be36fc07ee93179c81f3aa940972a56eed`.
- O PRECHECK não executou consulta Oracle, autenticação PNCP ou escrita. A
  configuração permanece em `PRECHECK`; o inventário não está autorizado.

## Evidências parciais do Microciclo 3

- O primeiro diagnóstico real foi inconclusivo e motivou códigos sanitizados
  por etapa. A leitura seguinte identificou `ORACLE_ESTRATEGIA_AUSENTE` sem
  escrita Oracle, PNCP ou de logs.
- O mantenedor confirmou que as migrações obrigatórias ainda não haviam sido
  executadas em produção. Depois, aplicou em ordem os scripts de configuração
  da publicação e de unidades orçamentárias, e informou que os checks do banco
  ficaram conformes.
- A ocorrência mostrou que as migrações são pré-requisito de implantação e
  não devem permanecer implícitas. O guia de configuração de produção passa a
  registrá-las junto da topologia do pacote, dos segredos externos e da
  variável persistente do runtime.
- Foi detectado que a versão diagnóstica autenticava no PNCP antes de validar
  o escopo Oracle. Houve somente login de leitura, sem GET de inventário nem
  escrita. A ordem foi corrigida localmente por TDD para bloquear antes de
  qualquer acesso PNCP. Em 24/09/2026, a correção foi incorporada ao artefato
  `pca-pncp-sprint94-inventory-gate-mc3-ordem-segura.tar.gz`, com SHA-256
  `715ede657e9d367c113af621f075dbd250194af1095b85fe782f504366d724fe`.
  O manifesto possui SHA-256
  `1f86906a6ab43f9fceebdd6761368ebdcfcb7b70b38fad8a5a27126ea151e2e4`;
  os 209 arquivos foram verificados tanto no diretório de construção quanto
  após extração isolada, sem configuração ou segredo e com os entrypoints
  carregáveis. A pasta interna permanece no caminho compatível
  `pca-pncp-sprint94-inventory-gate/`.
- Em observação adicional no `validaSGI`, o mantenedor relatou uma execução
  convergente no backend, com publicação aceita e dez eventos técnicos
  persistidos. O último evento foi `Unidade finalizada com convergência
  comprovada.`, emitido somente depois do `COMMIT` local. Apesar disso, o
  SweetAlert respondeu `INTERROMPIDA/FALHA_INESPERADA`, com contagens zeradas e
  a correlação `a8d3df90-7b52-41b4-ac9d-6177dc2301fa`. A mensagem pública
  observada pertence ao `catch` externo da Blank, o que delimita a falha à
  adaptação posterior ao resultado do backend. A escrita não deve ser repetida
  para diagnóstico.
- A inspeção local mostrou que `SEGURANCA_LOG.DESCRIPTION` registra ações
  técnicas, ano, UASG e modo, porém não inclui explicitamente a decisão de
  negócio `PUBLICAR`, `RETIFICAR`, `EXCLUIR_PCA` ou `NENHUMA_ACAO`. A correção
  foi delimitada no Microciclo 3A: ela não reabre a Sprint 9.2 inteira, não muda
  o valor físico de `SEGURANCA_LOG.ACTION` e não mistura observabilidade técnica
  com `PAC_HISTORICOS`.
- O primeiro inventário integral aprovado produziu o SHA-256
  `25ace482e92a21d15d671d92c61bb157ffbe3c8d8921140e60ccec46d6f686f1`, sem
  escrita Oracle, PNCP ou de logs. Ele encontrou os três candidatos oficiais de
  2026 e o candidato consolidado de 2027, cujo conjunto de 434 `numeroItem`
  coincidia exatamente com as 434 Demandas vigentes.
- Após a confirmação de procedência e a decisão de padronizar os exercícios no
  modo `INDIVIDUAL`, o mantenedor excluiu manualmente pelo Swagger somente o PCA
  2027/UASG 930281/sequencial 1. O PNCP aceitou a operação com HTTP 200, e a
  consulta nominal posterior retornou HTTP 404. Nenhuma automação repetiu ou
  compensou essa escrita.
- A releitura integral seguinte falhou de modo seguro com
  `PNCP_INVENTARIO_2027_INCONCLUSIVO`, SHA-256
  `03d81481ef4f1e847735ab511fab3da60cb46b2949669447634ac13b5a29dcc2`, porque
  o PNCP usa HTTP 404 já na primeira página quando o exercício não possui PCA.
  O gate não interpretou o corpo e manteve todas as escritas desarmadas.
- O tratamento dessa resposta foi corrigido localmente por TDD: o primeiro
  HTTP 404 somente representa ano vazio quando as UASGs esperadas aparecem
  exatamente uma vez em `/unidades` e a consulta anual também retorna HTTP 404.
  Um 404 isolado, identidade divergente ou PCA anual ainda presente continua
  inconclusivo. A Sprint 9.4 aprovou 32 testes/72 asserções; a suíte padrão,
  959 testes/4.592 asserções; lint e PSR-4 estrito também foram aprovados.
- O artefato corretivo
  `pca-pncp-sprint94-inventory-gate-mc3-ausencia-anual.tar.gz` possui SHA-256
  `e60f8581af4f44bcb25ff698ff7a93ec4344f144d71b35fd99d04520c1ac94c4`; seu
  manifesto possui SHA-256
  `98c54c6dedfe84b78c79f415fba37d3b45e5220d509303daddda698d43b3af54`.
  Os 209 arquivos foram verificados também depois de uma extração isolada, sem
  configurações ou segredos e com os entrypoints carregáveis. A configuração
  de negócio ainda permanecia `CONSOLIDADO` nessa validação.
- O gate corretivo foi instalado e carregado em produção. A releitura integral
  concluiu sem escrita e produziu o SHA-256
  `68468221f162ca970b70ffe750143537043b78d6668f037676d1b10caea5481c`.
  O relatório preservou os três candidatos oficiais de 2026 e apresentou lista
  vazia em 2027 após comprovar estruturalmente a UASG 930281 e a ausência anual.
  Nenhum segredo, JWT, SQL, resposta bruta ou CNPJ integral foi exposto.
- O mantenedor alterou diretamente pelo DBeaver a configuração corrente para o
  literal exato `INDIVIDUAL`. A releitura integral subsequente confirmou UASGs
  esperadas 205, 207 e 210 nos dois exercícios, preservou os três candidatos
  oficiais de 2026 e manteve 2027 sem candidatos. O novo baseline somente
  leitura possui SHA-256
  `7823d46dae06aff72119d2c1d2262afa2c4af82e24e28ae2ce69d1d296345975`, com
  escritas Oracle, PNCP e de logs desarmadas e sem conteúdo sensível.

## Evidências dos Microciclos 4 e 5

- O SQL específico
  `tests/Contract/Sprint94/checkpoints/vincular_legados_individuais_2026.sql`
  foi limitado ao baseline aprovado, aos 112 `ID_PLANO` esperados e aos três
  vínculos oficiais: UO 3/UASG 207/sequencial 2, UO 94/UASG 210/sequencial 3 e
  UO 149/UASG 205/sequencial 4.
- O script altera somente `ESTRATEGIA_ADOTADA_PUBLICACAO`, `UASG_ADOTADA`,
  `ESTADO_PUBLICACAO` e `SEQUENCIAL_PUBLICACAO_PNCP`; exige estratégia corrente
  `INDIVIDUAL`, campos anteriores nulos, bloqueio das linhas, cardinalidades
  3/38/71 e pós-check de 112 linhas. Não contém `COMMIT` automático e executa
  `ROLLBACK` diante de falha.
- Os testes estáticos do SQL e a suíte Sprint 9.4 aprovaram 35 testes/103
  asserções. A justificativa e o procedimento para conciliações futuras foram
  registrados em `tests/Contract/Sprint94/README.md`.
- O mantenedor executou o script pelo DBeaver com verificação própria e informou
  sucesso no vínculo dos planos de 2026. Nenhuma chamada ao PNCP pertenceu a
  essa aplicação. A fotografia Oracle e PNCP posterior ainda será obtida no
  Microciclo 6 antes de qualquer execução da aplicação normal.

## Evidências do Microciclo 6

- O inventário pós-vínculo concluiu sem escrita com SHA-256
  `1a3d164bfa7df2c1c92f3f79dc2872f66acd1f69f25a0d839594398b0f528c60`.
  As projeções Oracle ficaram uniformes em `INDIVIDUAL/207/PUBLICADO/2` para
  3 linhas da UO 3, `INDIVIDUAL/210/PUBLICADO/3` para 38 linhas da UO 94 e
  `INDIVIDUAL/205/PUBLICADO/4` para 71 linhas da UO 149. Os três candidatos de
  2026 foram classificados como `VINCULO_LOCAL_EXISTENTE`; 2027 permaneceu sem
  candidato.
- A diferença exata de pertencimento foi registrada em
  `tests/Contract/Sprint94/avaliacao_pos_vinculo_2026_2027.json`, com SHA-256
  `59d0b91bcd26c94b88f8ea436cc24b19ac413a3650f5f112f5b6a8f7544e2fe3`, sem
  CNPJ integral, credencial, SQL ou resposta bruta.
- Para 2026, a ação demonstrada é `RETIFICAR` nas três unidades: UASG 207 inclui
  3 e exclui 5 itens; UASG 210 inclui 38 e exclui 153; UASG 205 inclui 71 e
  exclui 208. Não existe item mantido em nenhuma unidade.
- Para 2027, a ação demonstrada é `PUBLICAR` nas três unidades ainda sem PCA:
  UASG 207 com 9 itens, UASG 210 com 165 e UASG 205 com 260.
- Nenhuma comparação de atributos item a item foi realizada, conforme a
  limitação vigente da V1. Nenhuma execução da aplicação normal foi feita.

## Evidências do Microciclo corretivo 3A

- O teste vermelho reproduziu a dependência da Blank de um callback identificado
  apenas pelo texto `pca_publicar_serializar_resultado_unidade`. A evidência do
  ambiente é compatível com a renomeação de funções feita pelo compilador
  Scriptcase, hipótese que ainda precisa ser confirmada na compilação externa.
- A serialização unitária passou a usar `foreach` e chamada direta. O resultado
  funcional é preparado antes do registro técnico; uma exceção inesperada do
  registrador se torna aviso sanitizado e não substitui o resultado conhecido.
- Os eventos da finalização que possuem contexto da unidade agora incluem
  `context.decisao`. Testes cobrem `PUBLICAR`, `RETIFICAR`, `EXCLUIR_PCA` e
  `NENHUMA_ACAO`; `action`, `SEGURANCA_LOG.ACTION` e `PAC_HISTORICOS`
  permanecem com suas responsabilidades anteriores.
- Os testes focados aprovaram 11 testes/126 asserções da Blank e 24 testes/159
  asserções do finalizador. A suíte padrão aprovou 959 testes/4.592 asserções, e
  o contrato Sprint 9.4 aprovou 29 testes/68 asserções.
- O lint dos quatro arquivos PHP afetados, o autoload PSR-4 estrito com 1.862
  classes, a validação Composer comum e a construção/verificação do pacote
  foram aprovados. A validação Composer estrita recusou somente o aviso
  preexistente de ausência de `license`. O pacote contém 197 arquivos, 196
  verificados, sem dependências de desenvolvimento, configuração ou segredo,
  com fachada e registrador carregáveis.
- O arquivador de release recusou corretamente gerar o arquivo de transferência
  porque o worktree possui alterações sem commit. A proteção não foi contornada,
  nenhum commit foi criado e nenhuma release oficial foi produzida.
- Para permitir a implantação corretiva sem misturar arquivos, foi criado um
  artefato nominal separado, declarado explicitamente como não oficial. Ele
  contém o backend completo validado e a fonte da Blank, mas nenhuma
  configuração ou segredo. Seus 198 arquivos foram verificados também depois de
  extração isolada.
- O arquivo `pca-pncp-sprint94-correcao-3a.tar.gz` possui SHA-256
  `f933dc5f95e0db66d6d71f44988322ebd0d042b02753fc1ee87efb3ccee3be93`; o
  manifesto interno possui SHA-256
  `a772b28c75b182f1aef5ef7254d9a5c6f7f420128472e4a5db9e2c2575117a99`.
- Após o empacotamento, o contrato Sprint 9.4 aprovou 36 testes/116 asserções,
  os testes focados da Blank e do finalizador aprovaram 35 testes/285 asserções
  e a suíte padrão aprovou 959 testes/4.592 asserções. Lint, Composer, PSR-4
  estrito e `git diff --check` dos arquivos deste escopo também foram aprovados;
  a verificação global de whitespace continua acusando apenas alterações locais
  preexistentes e fora deste escopo em `AGENTS.md` e `arquitetura.md`.
- Nenhum Oracle, PNCP, Scriptcase real, ambiente externo ou commit foi acessado.
  A transferência, implantação e compilação da Blank continuavam exigindo
  autorização própria nessa validação local.
- Após autorização explícita, o arquivo corretivo chegou à produção com o mesmo
  SHA-256, foi extraído em área temporária e teve estrutura e manifesto
  conferidos. O backend completo foi preparado em diretório candidato, validado
  como `www-data` e promovido somente depois dessa verificação; a versão anterior
  permaneceu preservada para rollback.
- O backend ativo apresentou o SHA-256 de manifesto
  `17bf236a6be2d332329c361d9750f38408086b3e859abeabbda05db40965ee4a` e as
  fachadas públicas permaneceram carregáveis. A fonte corrigida da Blank, com
  SHA-256 `dffa636ca266c15fce0d66ffba616f8420071a5bf64b5288894196e7532ca700`,
  foi publicada e a aplicação carregou sem erro.
- Nenhum botão de publicação foi acionado durante a implantação. O Microciclo 7
  permanece pausado antes da execução única de 2026.

## Evidências parciais do Microciclo 7

- Após autorização explícita, o mantenedor executou uma única vez a Blank para
  2026. A execução foi interrompida na primeira das três unidades, sem resultado
  unitário concluído, e devolveu a correlação
  `7902bab6-ded4-4e12-8771-5590dee3f59c` e o código seguro
  `PNCP_EXCLUSAO_ITENS_NAO_AUTORIZADA`.
- Os eventos técnicos dessa correlação comprovaram autenticação HTTP 200 e
  leituras anteriores concluídas. A primeira escrita, ação
  `EXCLUIR_ITENS_PCA_PNCP`, recebeu HTTP 403 do PNCP. A retificação executa a
  exclusão antes da inclusão; portanto, nenhuma inclusão foi tentada. O backend
  classificou a resposta como falha sistêmica definitiva e desfez a unidade de
  trabalho Oracle.
- A resposta preservada pela Blank confirmou 3 unidades planejadas, 1 visitada
  e 2 não processadas, demonstrando o funcionamento da correção 3A. Nenhuma
  repetição foi realizada. Antes de nova decisão, é obrigatório comprovar por
  leitura o estado externo e resolver ou aceitar nominalmente o bloqueio de
  autorização da credencial no PNCP.
- A primeira releitura do gate após a falha foi inconclusiva, com o código
  `PNCP_INVENTARIO_2026_INCONCLUSIVO` e SHA-256
  `ea1ee290f3181bee18d2a59fdd37c0a6eba7ded720a14ecfe85ca639fa01b69d`;
  todas as permissões de escrita permaneceram falsas. Uma única repetição
  somente leitura concluiu e reproduziu exatamente o SHA-256 anterior ao
  acionamento, `1a3d164bfa7df2c1c92f3f79dc2872f66acd1f69f25a0d839594398b0f528c60`.
- A fotografia idêntica comprova que as projeções Oracle e os três PCA de 2026
  permaneceram inalterados. O exercício 2026 fica nominalmente bloqueado pela
  autorização HTTP 403 do PNCP e não pode ser repetido enquanto a permissão da
  credencial não for resolvida e houver nova autorização explícita. O exercício
  2027 continua independente e ainda não foi executado.
- Após autorização separada, o mantenedor executou uma única vez a Blank para
  2027, correlação `93e4ece5-2123-4600-83f0-2e3f80ddb416`. As três unidades
  foram visitadas e todas retornaram `BLOQUEADO/PUBLICAR`, com o código
  `CANDIDATO_APOS_ESCRITA_SEM_VINCULO`; nenhuma unidade ficou não processada.
- O resultado indica que cada `POST` pode ter produzido efeito externo, mas o
  backend não obteve um sequencial confiável da resposta e recusou corretamente
  adotar o candidato encontrado pela leitura posterior. As projeções locais não
  foram confirmadas. É proibido repetir a execução ou remover candidatos antes
  do inventário e da comprovação de procedência.
- Os eventos da correlação confirmaram três respostas HTTP 201 classificadas
  como `PNCP_PUBLICACAO_RESULTADO_INCERTO`, uma para cada UASG 205, 207 e 210.
  Os eventos posteriores preservaram `context.decisao=PUBLICAR`, ano 2027 e a
  UASG correspondente, comprovando também a correção de observabilidade 3A.
- O inventário posterior concluiu com SHA-256
  `66fe4bfc01865dd2967b1801eab44abe9c66c36d4be3b4246b4116f27b709d69`.
  Comparado ao baseline imediatamente anterior, ele encontrou exatamente três
  candidatos novos: UO 149/UASG 205/sequencial 2 com 260 itens, UO 3/UASG
  207/sequencial 3 com 9 itens e UO 94/UASG 210/sequencial 4 com 165 itens.
  Cada conjunto coincide exatamente com as Demandas vigentes da respectiva
  unidade, sem falta ou sobra.
- A estratégia anual foi intencionalmente adotada e confirmada antes do acesso
  ao PNCP: os planos 201, 202 e 203 possuem `INDIVIDUAL` e as UASGs 205, 207 e
  210, respectivamente. Estado, sequencial e confirmação continuam nulos. A
  combinação de baseline vazio, correlação da execução, HTTP 201 e igualdade
  exata dos conjuntos comprova a procedência própria dos três candidatos.
- A recuperação prevista é um novo SQL específico e sem `COMMIT` automático,
  limitado aos planos 201/202/203 e aos sequenciais comprovados. Ele deverá
  preservar estratégia e UASG já adotadas, preencher somente estado e
  sequencial e ser seguido por inventário antes de qualquer nova execução da
  Blank. A preparação desse SQL exige nova autorização de microciclo.
- Antes da preparação autorizada desse SQL, o mantenedor informou ter preenchido
  diretamente pelo DBeaver os vínculos exatos comprovados: plano 201/UASG
  205/sequencial 2, plano 202/UASG 207/sequencial 3 e plano 203/UASG
  210/sequencial 4, todos em `INDIVIDUAL/PUBLICADO`, preservando
  `DATA_PUBLICACAO` nula naquele momento.
- O mantenedor executou novamente a Blank para 2027 antes da pausa prevista. A
  resposta, correlação `eda907a5-a7d6-45ca-9e46-60f1e9cd73e7`, concluiu as três
  unidades como `SEM_ALTERACAO/NENHUMA_ACAO`, com os sequenciais 2, 3 e 4,
  zero inclusões, zero exclusões e nenhuma unidade não processada. Essa decisão
  não realiza nova escrita PNCP e deve ter confirmado apenas as versões locais.
- O desvio operacional não autoriza nova repetição. A conclusão de 2027 ainda
  depende de fotografia somente leitura que confirme vínculos, datas de
  confirmação, candidatos e eventos da correlação.
- O inventário final tentou novamente a leitura, mas terminou inconclusivo em
  2027 com todas as escritas falsas. Em vez de insistir no gate demorado, o
  pós-check Oracle confirmou os três vínculos e as três `DATA_PUBLICACAO`:
  plano 201/UASG 205/sequencial 2 às 16:50:18 UTC e planos 202/UASG
  207/sequencial 3 e 203/UASG 210/sequencial 4 às 16:50:49 UTC.
- Os eventos da correlação final contêm três
  `PERSISTIR_CONVERGENCIA/NENHUMA_ACAO`, um por UASG, e nenhuma ação técnica de
  publicação, inclusão ou exclusão no PNCP. Uma falha transitória de transporte
  durante a leitura da UASG 207 foi sucedida por leitura concluída e convergência
  comprovada. A fotografia PNCP imediatamente anterior já demonstrava igualdade
  exata dos três conjuntos, e a execução `NENHUMA_ACAO` não realizou escrita
  externa.
- O exercício 2027 está reconciliado e confirmado. Permanecem como pendências
  independentes o bloqueio HTTP 403 para exclusão de itens de 2026 e a
  incompatibilidade observada na extração do sequencial de respostas HTTP 201
  de publicação em produção.
- A revalidação do OpenAPI oficial de produção em 24/09/2026 mostrou contrato
  declarado como OpenAPI 3.0.1, versão 1.0: a operação `incluirPlano` documenta
  respostas 200/204, entre outras falhas, mas não declara HTTP 201 nem o header
  `Location`. A produção real devolveu HTTP 201 e criou os três recursos. Essa
  divergência externa concreta reabre apenas o ponto correspondente da DI-009;
  qualquer correção do parser exige microciclo próprio e testes, sem nova
  publicação real para diagnóstico.

## Resultado do Microciclo corretivo 7A

- O teste de regressão reproduziu a recusa do `Location` absoluto oficial de
  produção sob `https://pncp.gov.br/pncp-api` e a ausência de diagnóstico
  sanitizado. A implementação mínima tornou os dois cenários verdes.
- O adapter aceita esse segundo prefixo somente em `PRODUCAO`. Permanecem
  obrigatórios HTTPS, host oficial exato, caminho do recurso, CNPJ, exercício,
  sequencial positivo e ausência de query string, fragmento ou sufixo.
- Um formato ainda incompatível produz somente `locationCategoria` e
  `quantidadeLocations` no evento técnico; o `Location` bruto, CNPJ e query
  string não são registrados.
- A suíte focada aprovou 128 testes/550 asserções, a suíte padrão aprovou 960
  testes/4.600 asserções e o contrato Sprint 9.4 aprovou 36 testes/116
  asserções. Lint, Composer e PSR-4 estrito também foram aprovados.
- O pacote autocontido foi construído e verificado com 197 arquivos, sem
  dependências de desenvolvimento, configurações ou segredos. Seu manifesto
  possui SHA-256
  `6ff0b8d4118d86d24f340a745a499277a6de7ae61cb09387b81cafb1a3e192ba`.
- O arquivo candidato
  `pca-pncp-sprint94-correcao-7a.tar.gz` possui SHA-256
  `2d1760221bd5cd0d080827c7483c41d1de8215ad3f83663ced504caf4c8b88a3`;
  ele foi extraído localmente e o pacote extraído foi verificado novamente.
- Nenhum Oracle, PNCP, Scriptcase real ou commit foi acessado. A implantação
  está obrigatoriamente pausada e exige autorização explícita própria.
- Após autorização explícita, o arquivo chegou ao servidor com o mesmo SHA-256.
  O pacote foi extraído em diretório candidato, teve topologia, manifesto,
  permissões e autoload conferidos e foi promovido por troca de diretórios. O
  mantenedor confirmou que o backend 7A ativo permaneceu carregável, e a versão
  anterior ficou preservada em `pca-pncp-backend-anterior-7a` para rollback.
- A Blank não mudou e nenhuma publicação foi executada para testar a correção:
  2027 já está convergente, e uma escrita real de diagnóstico violaria a regra
  de não repetir operação externa sem necessidade.

## Resultado do Microciclo 8

- A matriz de recuperação foi validada por exercício de mesa, usando os casos
  reais de 2026 e 2027 e cenários controlados para ausência, efeito parcial e
  observação inconclusiva. Nenhuma falha foi provocada em produção.
- O diretório público `teste_inventario_sprint94`, a configuração externa
  temporária `sprint94.php` e os arquivos de transferência 3A/7A foram movidos
  para quarentena protegida após conferência dos alvos. O backend ativo e os
  dois diretórios anteriores de rollback permaneceram fora do escopo.
- Depois da verificação do backend ativo, a quarentena foi removida mediante
  validação do caminho absoluto. O backend final permaneceu carregável.
- 2027 está reconciliado e confirmado. O HTTP 403 de 2026 permanece como
  bloqueio nominal explicitamente aceito, sem autorizar repetição.
- Os critérios de saída da Sprint 9.4 foram atendidos com essa exceção nominal.
  A Sprint 9.5 é a próxima e não foi iniciada.
