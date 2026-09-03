# Sprint 8.3 — Suíte PNCP de treinamento

**Status:** Concluída em 03/09/2026 após os seis microciclos autorizados e o
microciclo corretivo de paginação e ausência.

## Objetivo

Criar um gate opt-in que exercite a composição PNCP usada pelo
`CompositionRootPublicacaoPca` contra o ambiente oficial de treinamento, sem
Oracle ou fluxo público integral.

A suíte comprovará autenticação, TLS, paginação real, ausência definitiva,
sequencial, IDs lacunados, decimais exatos, operações de escrita, resultado
incerto deliberado e limpeza best-effort.

O Manual PNCP 2.6 e o OpenAPI vigente serão revalidados especificamente para
PCA. A DI-009 somente será reaberta ou alterada se essa análise ou o gate
revelar incompatibilidade ou contrato novo relevante.

## Contratos aprovados

- APIs públicas, Ports, Commands e Results permanecem inalterados.
- Uma subcomposição interna será a única receita dos três adapters PNCP.
- `PCA_SPRINT83_CONFIG_FILE` conterá somente autorização, servidor esperado e
  massa dedicada; perfil, credenciais, CNPJ, ambiente e limites continuarão no
  registro/perfil final.
- Produção, URL configurável e candidato externo desconhecido serão recusados.
- O gate nominal e o de incerteza serão opt-in, seriais e independentes.
- A incerteza será produzida descartando uma resposta real de sucesso depois
  do envio, sem repetir o POST e sem usar o `Location` descartado na recuperação.
- Relatórios serão sanitizados e identificados por hash SHA-256.

Microciclos com pausa obrigatória
1. Contrato, configuração e preflight
   - Registrar o plano aprovado.
   - Comparar o Manual 2.6 e o OpenAPI vigente com os contratos PCA adotados.
   - Se houver incompatibilidade ou contrato novo relevante, interromper o microciclo e apresentar a reabertura pontual da DI-009.
   - Implementar por TDD a configuração externa e o preflight sem rede, recusando produção, escrita não autorizada, servidor divergente, caminhos internos, estrutura adicional e perfil inválido.
   - Pausar após testes focados e regressão dos carregadores de perfil.
2. Composição PNCP única
   - Extrair a construção de autenticador, leitor e escritor para a subcomposição interna.
   - Refatorar obrigatoriamente o Root final para utilizá-la.
   - Comprovar transporte/logger compartilhados, grafo novo por montagem e ausência de I/O durante a composição.
   - Pausar após regressões de Composition, Presentation e Integration.
3. Runner, incerteza controlada e segurança
   - Implementar o runner nominal, o wrapper que descarta uma resposta real já recebida e a limpeza isolada.
   - Forçar paginação no gate com página de tamanho 1, preservando os demais limites do perfil.
   - Criar testes sem rede para ordem das chamadas, autenticação única, ausência de repetição da escrita incerta, busca posterior, limpeza, resíduos e sanitização.
   - Adicionar configuração PHPUnit e comandos Composer próprios da 8.3.
   - Pausar com suítes locais e contratuais verdes.
4. Gate nominal no treinamento
   - Executar preflight e o ciclo serial: observar estado limpo, autenticar, publicar dois IDs lacunados, observar páginas, incluir o terceiro item, excluir um item, excluir o PCA e comprovar ausência definitiva.
   - Confirmar os lexemas decimais exatos enviados e os mesmos identificadores nas leituras.
   - Em falha posterior à primeira escrita, executar limpeza best-effort; resíduo não confirmado mantém a sprint aberta.
   - Pausar para conferência do relatório sanitizado e do hash.
5. Gate de escrita incerta real
   - Exigir novamente estado inicial sem candidatos.
   - Enviar uma única publicação real e descartar deliberadamente sua resposta HTTP de sucesso.
   - Exigir RESULTADO_INCERTO, não repetir o POST, localizar o efeito por leitura, conferir os identificadores e removê-lo.
   - Comprovar ausência definitiva. Ambiguidade ou leitura inconclusiva será relatada como possível resíduo e interromperá o avanço.
   - Pausar para conferência da evidência sanitizada.
6. Regressão e encerramento
   - Executar suítes PNCP, Infrastructure, Composition, Presentation, Integration, contratos anteriores e suíte padrão.
   - Validar lint, PHP 8.1 quando disponível, PSR-4 estrito, dependências, ausência de segredos e git diff --check.
   - Se tudo permanecer compatível, manter a DI-009 semanticamente inalterada e registrar a revalidação apenas nas evidências do plano, no estado atual e no roadmap.
   - Atualizar também o guia das probes, catálogo e índice documental conforme necessário.
   - Marcar a 8.3 concluída e a 8.4 como próxima somente após os dois gates reais e suas limpezas serem confirmados.

Cada microciclo seguinte depende de autorização explícita após a pausa do
anterior. Nenhum commit será criado.

## Validação planejada

- TDD focado e regressões relacionadas a perfil e Composition;
- contratos opt-in sem I/O e gates reais executados somente quando autorizados;
- suíte padrão, Infrastructure PNCP, lint, PHP 8.1 quando disponível e PSR-4;
- inspeção de dependências, segredos, URLs, produção e `git diff --check`.

## Limites

Oracle, Scriptcase real, fachada completa, modos individual/consolidado,
persistência local, `sc_log_add`, produção e implantação permanecem fora desta
sprint.

## Evidências do Microciclo 1

- O Red focado executou cinco testes e registrou cinco erros causados
  exclusivamente pela ausência de `Sprint83Config`.
- O Green e a regressão de configuração, carregador de perfil, entrypoint e
  integração anterior ao I/O aprovaram 24 testes e 336 asserções.
- `PCA_SPRINT83_CONFIG_FILE` exige arquivo absoluto fora do repositório,
  consentimento explícito, estrutura exata, massa tipada e servidor literal.
- O perfil final é carregado por `PCA_PNCP_PROFILE_REGISTRY_FILE`; produção,
  CNPJ inválido, servidor divergente, IDs não lacunados e resíduo sem
  sequencial positivo são recusados antes de I/O.
- O preflight sem configuração externa falhou fechado com mensagem genérica e
  não acessou PNCP ou Oracle.
- O Manual PNCP 2.6, de 31/08/2026, e o OpenAPI 3.0.1 com `info.version` 1.0
  foram revalidados em 02/09/2026. Os endpoints, métodos, parâmetros e códigos
  de resposta usados para autenticação e PCA permanecem presentes; as notas da
  versão 2.6 não introduzem mudança relevante para PCA.
- Não foi encontrada incompatibilidade ou contrato novo relevante; a DI-009
  permaneceu semanticamente inalterada.
- Lint dos quatro arquivos PHP novos, `composer validate`, autoload PSR-4
  estrito com 1.784 classes e `git diff --check` foram aprovados no PHP 8.4.7.
- Nenhum endpoint operacional, Oracle, Scriptcase real ou commit foi usado.

## Evidências do Microciclo 2

- O Red estrutural executou um teste e registrou um erro causado exclusivamente
  pela ausência de `ComposicaoPncpPublicacaoPca`.
- O Green focado aprovou seis testes e 168 asserções. A regressão conjunta de
  Composition, Presentation, Integration, entrypoint e configuração 8.3
  aprovou 66 testes e 907 asserções.
- A suíte padrão aprovou 869 testes e 3.820 asserções no PHP 8.4.7.
- `ComposicaoPncpPublicacaoPca` tornou-se a única receita de produção para
  construir `AutenticadorPncpAdapter`, `LeitorPncpAdapter` e
  `EscritorPncpAdapter`; o `CompositionRootPublicacaoPca` a utiliza
  obrigatoriamente.
- Os três adapters compartilham exatamente o transporte e o logger recebidos.
  O leitor também recebe explicitamente a configuração de leitura e o
  temporizador, permitindo que o gate force paginação sem branch de teste.
- A construção da subcomposição e de dois grafos do Root não executou
  transporte, espera, ADOdb ou fechamento da conexão emprestada.
- O autoload PSR-4 estrito gerou 1.786 classes. Lint dos três arquivos PHP
  afetados, direção das dependências e `git diff --check` foram aprovados.
- Nenhuma rede, Oracle, Scriptcase real ou commit foi usado.

## Evidências do Microciclo 3

- O Red contratual executou três testes e registrou três erros causados
  exclusivamente pela ausência do transporte e do runner da Sprint 8.3.
- A suíte contratual local da 8.3 aprovou 13 testes e 58 asserções, excluindo
  explicitamente os dois grupos live.
- O runner usa `ComposicaoPncpPublicacaoPca`, força páginas de tamanho 1 e
  preserva os demais limites do perfil. O transporte envoltório delega a
  chamada real antes de descartar uma única resposta de sucesso da publicação.
- O cenário nominal em memória comprovou autenticação única, publicação com
  dois IDs lacunados, paginação, inclusão, exclusão de item, exclusão integral,
  ausência definitiva e decimais JSON exatos.
- O cenário incerto comprovou uma única publicação, classificação
  `RESULTADO_INCERTO`, recuperação somente por leitura, preservação dos IDs e
  limpeza final sem usar o `Location` descartado.
- Falha controlada após publicação confirmou limpeza best-effort. Execução
  normal é recusada enquanto houver resíduo configurado, e a limpeza isolada
  exige sequencial positivo explicitamente autorizado.
- Os grupos `pncp83-nominal-live` e `pncp83-uncertain-live` possuem comandos
  distintos; preflight e limpeza também possuem entrypoints próprios.
- Infrastructure PNCP aprovou 334 testes e 1.084 asserções; Composition aprovou
  11/187; os contratos 5.9 aprovaram 54/149; a suíte padrão aprovou 869/3.820.
- O autoload PSR-4 estrito gerou 1.792 classes. Lint dos arquivos PHP da 8.3,
  validação do Composer, direção das dependências e `git diff --check` foram
  aprovados no PHP 8.4.7.
- Nenhum grupo live, preflight com configuração real, PNCP, Oracle, Scriptcase
  real ou commit foi executado.

## Evidências do microciclo corretivo anterior ao gate nominal

- O ensaio manual orientado no treinamento confirmou autenticação única,
  publicação com dois IDs lacunados, paginação, inclusão do terceiro item,
  exclusão de item, exclusão integral e limpeza operacional. Identidades,
  payloads e respostas não foram registrados nesta evidência.
- A paginação de itens encerrou com `204`. A listagem de unidades apresentou
  páginas `200` completas e respondeu `404` apenas na página posterior.
- Depois da exclusão integral, o consolidado conhecido respondeu `400`; a
  listagem completa não continha a UASG removida. Nenhuma mensagem textual do
  PNCP foi adotada como contrato.
- A incompatibilidade reabriu pontualmente a DI-009. `400` no consolidado é
  apenas sinal provisório e exige listagem completa sem a UASG; `404` termina
  somente a listagem de unidades, em página maior que 1 e após página `200`
  válida. Nos demais contextos, ambos permanecem inconclusivos.
- A regra é uniforme entre ambientes, mas somente o treinamento forneceu
  evidência externa. Não existe novo gate de produção.
- O Red focado registrou 10 falhas esperadas. O Green aprovou 45 testes e 109
  asserções; leitura aprovou 84/213, Infrastructure PNCP 347/1.110, contratos
  locais 8.3 13/58, Composition 11/187 e a suíte padrão 882/3.846.
- Lint dos quatro arquivos PHP afetados, Composer, PSR-4 estrito com 1.792
  classes e preflight foram aprovados no PHP 8.4.7. A configuração externa foi
  somente relida de forma sanitizada, permaneceu com resíduo nulo e não foi
  alterada. Nenhum endpoint ou gate real integrou o microciclo corretivo.
- A execução nominal real seguinte percorreu integralmente o ciclo e confirmou
  a limpeza antes de falhar na emissão do relatório: a UASG textual curta
  coincidiu com um status HTTP numérico e gerou falso positivo na auditoria de
  eventos. Naquela execução não houve relatório nem hash, portanto ela não
  aprovou o gate.
- O Red local reproduziu o erro. A UASG passou a ser procurada como string JSON
  exata, sem enfraquecer a busca por fragmentos aplicada a credenciais e demais
  valores. Runner 6/29, contratos locais 8.3 14/60 e suíte padrão 882/3.846
  ficaram verdes antes da nova execução autorizada descrita a seguir.

## Evidências do Microciclo 4

- O gate nominal real foi aprovado no treinamento em 03/09/2026, depois de
  preflight sem rede confirmado.
- Uma única autenticação sustentou publicação, leituras paginadas, inclusão do
  terceiro item, exclusão de um item, exclusão integral e comprovação de
  ausência definitiva. IDs lacunados, sequencial externo e lexemas decimais
  exatos foram preservados.
- O relatório registrou 32 eventos sanitizados, paginação múltipla, limpeza
  confirmada e nenhuma identidade, credencial, token, payload ou resposta.
- SHA-256 da evidência sanitizada:
  `0688185978654e374ddf959f377d8cf619314bc906f81e19cafbdff1388aae2c`.
- O grupo nominal aprovou 1 teste e 3 asserções no PHP 8.4.7. O gate incerto
  não foi executado e depende de autorização própria.
- Por solicitação do mantenedor, o contrato das próximas evidências nominais
  passou a incluir `sequencial_pca_recebido_excluido`: o inteiro positivo
  recebido na publicação e usado na exclusão integral já confirmada. A mudança
  foi validada somente com o PNCP em memória; o gate real não foi repetido.

## Evidências do Microciclo 5

- O preflight sem rede foi confirmado novamente antes do gate real, com perfil
  de treinamento, escritas autorizadas e sem acesso a Oracle ou PNCP.
- O gate de escrita incerta foi aprovado no treinamento em 03/09/2026. Uma
  única autenticação sustentou exatamente um POST de publicação cuja resposta
  de sucesso foi deliberadamente descartada.
- O resultado foi classificado como incerto sem repetição da escrita. O efeito
  foi localizado somente por leitura, os identificadores foram preservados e
  o PCA atribuível à execução foi removido com limpeza confirmada.
- O relatório registrou 23 eventos sanitizados, sem credenciais, token,
  payload, resposta ou caminho. SHA-256 da evidência:
  `f68ee8ee61db4ea11598f88a52100bc9e1cb5ca924eb6bcbe16930d458a149ba`.
- O grupo incerto aprovou 1 teste e 3 asserções no PHP 8.4.7. Nenhuma nova
  incompatibilidade ou contrato relevante foi observado; a DI-009 permanece
  semanticamente inalterada por este gate.

## Evidências do Microciclo 6

- Infrastructure PNCP aprovou 347 testes e 1.110 asserções; Composition,
  11/187; Presentation, 31/319; Integration, 5/103.
- Os contratos locais das Sprints 5.1, 5.9, 8.2 e 8.3 aprovaram,
  respectivamente, 24/51, 54/149, 12/43 e 14/62. Todos os grupos live
  permaneceram excluídos. A suíte padrão aprovou 882/3.846.
- O lint aprovou os 325 arquivos PHP de `src/` e `tests/`; o autoload PSR-4
  estrito gerou 1.792 classes. Composer, requisitos instalados, catálogo de 22
  comandos, referências documentais e `git diff --check` foram validados.
- O runtime disponível foi PHP 8.4.7; não havia binário PHP 8.1 instalado. O
  requisito Composer `^8.1` foi satisfeito, mas não houve segunda execução no
  runtime mínimo.
- Nenhum valor externo conhecido da massa foi encontrado no repositório.
  Nenhum preflight real, grupo live, Oracle, PNCP ou Scriptcase foi acessado no
  encerramento.
- Manual 2.6, OpenAPI e os dois gates reais permaneceram compatíveis. A DI-009
  não recebeu alteração semântica adicional. A Sprint 8.3 está concluída e a
  Sprint 8.4 é a próxima na ordem, ainda sem autorização de implementação.
