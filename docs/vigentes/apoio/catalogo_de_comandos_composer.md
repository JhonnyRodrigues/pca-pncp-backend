# Catálogo de comandos Composer

**Classificação documental:** Apoio  
**Status:** Vigente  
**Autoridade/Escopo:** referência didática e operacional dos scripts ativos do
`composer.json`; não cria autorização, regra de negócio ou configuração

## 1. Como usar este catálogo

O `composer.json` é a fonte executável dos nomes e comandos. Este catálogo
explica o que cada script faz e, principalmente, se ele pode alcançar ou
alterar um recurso real. A presença de um comando nesta página não autoriza sua
execução.

As classificações usadas são:

- **suíte local padrão:** percorre todos os testes não contratuais, incluindo
  testes de unidade, componentes e integrações controladas por doubles;
- **teste local focado:** restringe a suíte a uma camada ou conjunto técnico;
- **contrato local:** valida um contrato executável sem acessar o recurso real;
- **preflight:** valida configuração e autorização antes do I/O, mas não prova
  conectividade nem executa o gate;
- **gate real opt-in:** acessa deliberadamente uma integração real autorizada;
- **limpeza/recuperação:** pode excluir um efeito real previamente atribuído à
  massa de teste;
- **ferramenta:** auxilia desenvolvimento ou validação, sem representar teste.

## 2. Desenvolvimento e qualidade

| Comando | Natureza e finalidade | I/O externo e efeitos | Dependências, autorização e resultado esperado |
| --- | --- | --- | --- |
| `composer check-psr4` | Ferramenta local. Gera o autoload otimizado e verifica PSR-4 estrito. | Não acessa Oracle, PNCP ou Scriptcase; atualiza arquivos gerados em `vendor/`. | Exige dependências instaladas; não exige configuração externa nem autorização de gate. Espera término sem classe incompatível com PSR-4. |
| `composer run-script "start server"` | Ferramenta local. Inicia o servidor PHP embutido em `localhost:8080`, servindo `public/`. | Abre uma porta local e permanece em execução; não acessa integrações por si só. | Exige PHP e dependências da aplicação. Interrompa com `Ctrl+C`. Não substitui o ambiente Scriptcase. |

## 3. Suítes locais

| Comando | Natureza e finalidade | I/O externo e efeitos | Dependências, autorização e resultado esperado |
| --- | --- | --- | --- |
| `composer tests` | Suíte local padrão. Executa Domain, Application, Infrastructure, Composition, Presentation e Integration, excluindo `tests/Contract`. | Não deve acessar recursos reais nem alterar dados externos. | Exige `composer install`; não exige variáveis externas ou autorização. É a regressão local principal. |
| `composer adapters:tests` | Teste local focado em `tests/Infrastructure`. Exercita adapters com doubles e recursos controlados. | Não acessa Oracle, PNCP ou Scriptcase reais. | Não integra a suíte contratual opt-in. Espera todos os testes de Infrastructure verdes. |

## 4. Contratos locais

| Comando | Natureza e finalidade | I/O externo e efeitos | Dependências, autorização e resultado esperado |
| --- | --- | --- | --- |
| `composer contract:tests` | Contratos locais da Sprint 5.1; exclui o grupo PNCP live. | Sem Oracle ou PNCP real. | Não exige configuração real. Valida proteções e runners controlados da descoberta inicial. Exemplos de descobertas: a conexão de banco de dados do scriptcase pode ser emprestada? Quais são os endpoints indispensáveis do PNCP? |
| `composer contract:adapters:tests` | Contratos locais da Sprint 5.9; exclui o grupo PNCP live. | Sem Oracle, PNCP ou Scriptcase real. | Não exige autorização externa. Valida adapters, segurança, relatórios e exercícios controlados. |
| `composer contract:oracle82:tests` | Contratos locais da Sprint 8.2 para a subcomposição Oracle final. | Não abre conexão Oracle nem chama PNCP. | Não exige configuração externa para os testes locais. Valida composição, runner, falhas e proteções. |
| `composer contract:pncp83:tests` | Contratos locais da Sprint 8.3 para a subcomposição PNCP; exclui os grupos nominal e incerto live. | Não acessa o PNCP e não escreve dados externos. | Não exige arquivos reais. Valida configuração, runner, paginação, incerteza, limpeza e sanitização com transporte em memória. |
| `composer contract:sprint85:tests` | Contratos locais da Sprint 8.5 para Blank, falhas e preparação dos gates; exclui o grupo live. | Não acessa Oracle, PNCP ou Scriptcase real. | Valida consentimentos, massa protegida, consultas somente leitura e integrações controladas. A verificação do pacote possui comando próprio. |
| `composer contract:sprint94:tests` | Contratos locais do gate temporário da Sprint 9.4. | Não acessa Oracle, PNCP ou Scriptcase real. | Valida configuração desarmada, guardas somente leitura, paginação, relatório sanitizado, Blank e empacotamento. |

## 5. Preflights sem acesso externo

| Comando | Natureza e finalidade | I/O externo e efeitos | Dependências, autorização e resultado esperado |
| --- | --- | --- | --- |
| `composer contract:preflight` | Preflight completo da Sprint 5.1 para as seções Oracle e PNCP. | Lê a configuração externa; não conecta nem escreve. | Sua execução exige previamente a variável de ambiente `PCA_SPRINT51_CONFIG_FILE`. Deve preceder as probes correspondentes e retornar confirmação sanitizada. |
| `composer contract:preflight:oracle` | Preflight Oracle da Sprint 5.1. | Sem conexão Oracle e sem PNCP. | Exige a configuração externa da sprint 5.1 com a seção Oracle válida. Não autoriza a execução real. |
| `composer contract:preflight:pncp` | Preflight PNCP da Sprint 5.1. | Sem rede e sem escrita PNCP. | Exige a configuração externa da sprint 5.1 e autorização configurada para a massa quando aplicável. |
| `composer contract:adapters:preflight` | Preflight completo da Sprint 5.9 para adapters Oracle e PNCP. | Lê configuração; não acessa integrações. | Exige `PCA_SPRINT59_CONFIG_FILE`. Deve ser refeito após qualquer ajuste de massa ou resíduo. |
| `composer contract:adapters:preflight:oracle` | Preflight Oracle da Sprint 5.9. | Sem conexão Oracle e sem PNCP. | Exige a seção Oracle completa da configuração 5.9. O gate Oracle continua sendo executado pela Blank protegida. |
| `composer contract:adapters:preflight:pncp` | Preflight PNCP da Sprint 5.9. | Sem rede e sem escrita. | Exige a seção PNCP da configuração 5.9. Deve preceder gate ou limpeza dessa sprint. |
| `composer contract:oracle82:preflight` | Preflight da Sprint 8.2 para configuração, registro e perfil da composição Oracle. | Não acessa Oracle nem PNCP. | Exige `PCA_SPRINT82_CONFIG_FILE` e `PCA_PNCP_PROFILE_REGISTRY_FILE`; valida ambiente, servidor e autorização antes da Blank. |
| `composer contract:pncp83:preflight` | Preflight da Sprint 8.3 para configuração, registro, perfil e massa PNCP dedicada. | Não abre rede, não autentica e não modifica o PNCP. | Exige `PCA_SPRINT83_CONFIG_FILE` e `PCA_PNCP_PROFILE_REGISTRY_FILE`. Deve ser executado antes de qualquer modo real da 8.3. |
| `composer contract:sprint85:preflight` | Preflight local da Sprint 8.5 para configuração, perfil e contrato das doze consultas. | Lê arquivos externos; não acessa Oracle, PNCP ou Scriptcase. | Exige `PCA_SPRINT85_CONFIG_FILE` e `PCA_PNCP_PROFILE_REGISTRY_FILE`. Confirma somente a preparação local. |
| `composer contract:sprint85:queries -- LOGIN_SGI` | Renderiza SQL, binds, colunas e cardinalidades para conferência manual. | Lê configuração e perfil externos; não executa as consultas. | Deve ser usado antes de cada gate real, com o login do operador autorizado. |

Preflight aprovado significa apenas que a configuração passou pelas proteções
locais. Ele não confirma credenciais, disponibilidade, contrato remoto ou
ausência de resíduos.

## 6. Gates reais opt-in

| Comando | Natureza e finalidade | I/O externo e efeitos | Dependências, autorização e resultado esperado |
| --- | --- | --- | --- |
| `composer contract:pncp` | Gate PNCP legado da Sprint 5.1. Exercita o contrato descoberto no treinamento. | Autentica, consulta, publica e remove massa real; o lote de limite possui opt-in próprio. | Exige configuração 5.1, preflight aprovado, massa dedicada e autorização explícita. Falha sem limpeza confirmada pode deixar resíduo. |
| `composer contract:adapters:pncp` | Gate real da Sprint 5.9 para os adapters PNCP de produção. | Autentica e percorre um ciclo real de leitura, publicação e exclusão no treinamento. | Exige configuração 5.9, preflight PNCP aprovado, nenhum resíduo configurado, massa dedicada e autorização explícita. Espera relatório sanitizado e limpeza confirmada. |
| `composer contract:pncp83:nominal` | Gate nominal real da Sprint 8.3 para a composição PNCP usada pelo Root. | No treinamento, autentica uma vez, publica dois itens, consulta páginas, inclui outro item, exclui um item, exclui o PCA e comprova ausência. | Exige as duas variáveis da 8.3, preflight aprovado, execução serial, estado inicial livre, resíduo nulo e autorização explícita. O relatório inclui o sequencial recebido/excluído e hash; falha posterior à escrita aciona limpeza best-effort. |
| `composer contract:pncp83:uncertain` | Gate real de escrita incerta da Sprint 8.3. Descarta deliberadamente uma resposta de publicação já recebida. (header: Location) | Executa exatamente um POST real, recupera o efeito por leitura e o exclui; não repete a publicação. | Exige nova autorização específica, preflight aprovado, estado inicial livre e resíduo nulo. Ambiguidade ou limpeza inconclusiva interrompe o avanço e pode exigir recuperação isolada. |

Esses comandos ficam fora da suíte padrão. Não os execute como regressão de
rotina, em paralelo ou apenas para verificar se “continua tudo verde”.

## 7. Construção do pacote

| Comando | Natureza e finalidade | I/O externo e efeitos | Dependências, autorização e resultado esperado |
| --- | --- | --- | --- |
| `composer package:build` | Gera `build/pca-pncp-backend` a partir do lock, com código, dependências de produção e manifesto. | Pode baixar `psr/log`; escreve somente em `build/`. Não acessa Oracle, PNCP ou Scriptcase. | É o comando vigente para construir a release. Exige Composer apenas na máquina de construção; o artefato final não contém Composer, testes, probes ou configuração. |
| `composer package:verify` | Confere topologia, hashes, ausência de conteúdo proibido e autoload das fachadas públicas. | Lê somente o pacote local e o lock do checkout. | É o comando vigente de verificação e espera `PACOTE_VALIDADO`; qualquer divergência exige nova construção. |
| `composer package:archive` | Compacta o pacote já validado como `pca-pncp-backend_DATA_COMMIT.tar.gz`, confere a topologia e calcula seu SHA-256. | Lê o pacote e o Git; escreve somente o arquivo em `build/`. Não acessa sistemas externos. | Usa a data local `YYYYMMDD`, exige worktree limpo, pacote existente e `tar`; recusa sobrescrever arquivo existente e espera `RELEASE_ARQUIVADA`. |
| `composer package:release` | Orquestra, nessa ordem, `package:build`, `package:verify` e `package:archive`. | Reúne os efeitos locais dos três comandos; pode baixar dependências durante a construção. | É o comando preferencial para formar uma release completa e pronta para transferência a partir de um commit limpo. |
| `composer package:sprint85` | Alias histórico compatível de `package:build`. | Possui os mesmos efeitos de `package:build`. | Preservado para reproduzir os procedimentos registrados na Sprint 8.5; novas releases usam o nome vigente. |
| `composer package:sprint85:verify` | Alias histórico compatível de `package:verify`. | Possui os mesmos efeitos de `package:verify`. | Preservado para reproduzir os procedimentos registrados na Sprint 8.5. |
| `composer package:sprint85:log-gate` | Monta `build/pca-pncp-log-gate` para o Microciclo 6, reutilizando o pacote principal e acrescentando somente o runtime da probe. | Escreve somente em `build/`; não acessa Oracle, PNCP ou Scriptcase. | Exige que `build/pca-pncp-backend` esteja presente e validado. Não inclui configuração ou segredo. |
| `composer package:sprint85:log-gate:verify` | Confere hashes, topologia, pacote principal incorporado e carregamento dos três entrypoints do gate. | Lê somente os artefatos locais e o lock. | Espera `ARTEFATO_GATE_VALIDADO` antes do envio por FileZilla. |
| `composer package:sprint85:containment-gate` | Monta `build/pca-pncp-containment-gate` para o Microciclo 7, com o pacote principal e o runtime mínimo da contenção. | Escreve somente em `build/`; não acessa Oracle, PNCP ou Scriptcase. | Exige o pacote principal validado e não inclui configuração nem segredo. |
| `composer package:sprint85:containment-gate:verify` | Confere hashes, topologia e os três entrypoints de PRECHECK, contenção e disponibilidade. | Lê somente os artefatos locais e o lock. | Espera `ARTEFATO_CONTENCAO_VALIDADO` antes do envio por FileZilla. |
| `composer package:sprint94:inventory-gate` | Constrói o gate temporário e separado de inventário da Sprint 9.4. | Pode baixar dependências durante a construção do backend e escreve somente em `build/`; não acessa Oracle, PNCP ou Scriptcase. | Inclui apenas o backend validado e o runtime do gate; não inclui configuração nem segredo. |
| `composer package:sprint94:inventory-gate:verify` | Confere topologia, hashes e entrypoints do gate da Sprint 9.4. | Lê somente artefatos locais e o lock. | Espera `ARTEFATO_SPRINT94_VALIDADO`; não autoriza instalação ou execução em produção. |
| `composer package:sprint94:correction-3a` | Monta o artefato nominal da correção 3A, contendo o backend completo validado e a fonte da Blank. | Pode baixar dependências durante a construção e escreve somente em `build/`; não acessa Oracle, PNCP ou Scriptcase. | O manifesto declara `release_oficial=false`; o artefato não substitui o processo normal de release e não inclui configuração nem segredo. |
| `composer package:sprint94:correction-3a:verify` | Confere o pacote interno, a topologia, todos os hashes e as duas correções funcionais da 3A. | Lê somente o artefato local, o lock e os verificadores do checkout. | Espera `CORRECAO_SPRINT94_3A_VALIDADA`; qualquer divergência exige reconstrução. |
| `composer package:sprint94:correction-3a:archive` | Compacta o artefato corretivo verificado e calcula seu SHA-256. | Escreve somente `build/pca-pncp-sprint94-correcao-3a.tar.gz`; não acessa sistemas externos. | Recusa sobrescrever o arquivo existente, confere a topologia e não identifica o resultado como release oficial. |

## 8. Limpeza e recuperação

| Comando | Natureza e finalidade | I/O externo e efeitos | Dependências, autorização e resultado esperado |
| --- | --- | --- | --- |
| `composer contract:adapters:pncp:cleanup` | Recuperação PNCP da Sprint 5.9. | Pode consultar e excluir um PCA real da massa dedicada; não publica. | Exige preflight PNCP e `owned_residue_sequence` positivo, conhecido e autorizado. Só conclui com ausência confirmada; nunca adivinhe o sequencial. |
| `composer contract:pncp83:cleanup` | Recuperação PNCP da Sprint 8.3. | Pode consultar e excluir integralmente o PCA real indicado; não cria publicação. | Exige preflight e `residuo_sequencial_autorizado` positivo, previamente atribuído à massa e explicitamente autorizado. Após sucesso, remova o valor da configuração e repita o preflight. |

## 9. Relação com as pastas de testes

As pastas:

- `tests/Domain`, `tests/Application`, `tests/Presentation` e parte de `tests/Infrastructure` exercitam unidades ou componentes.
- `tests/Integration` e `tests/Composition` verificam colaboração entre componentes usando ambientes controlados.
- `tests/Contract` contém tanto verificações locais quanto gates opt-in; por isso o nome da pasta, sozinho, não informa se existe I/O real.

Para recuperar a preparação original das configurações externas, Blanks
protegidas, diagnóstico e tratamento de resíduos dos gates encerrados, consulte
o [guia histórico de execução das probes](../../historico/operacional/guia_de_execucao_das_probes.md).
