# Sprint 8.5 — Falhas, integração da Blank, logs e ajuda ao operador

**Status:** concluída em 21/09/2026 após os dez microciclos autorizados.

## Resumo

Concluir a integração com a `PCA_PUBLICAR_B`, comprovar falhas seguras e validar
o pacote autocontido em homologação. Incluir eventos técnicos pelo
`sc_log_add` e, ao final da sprint, um botão de ajuda ao operador.

Fontes: [roadmap 8.5](../../../docs/vigentes/autoritativos/roadmap.md),
[DI-004, DI-006 e DI-008 a DI-010](../../../docs/vigentes/autoritativos/decisoes_de_integracao.md),
[especificação de logs](../../../docs/vigentes/autoritativos/especificacao_de_logs_estruturados.md),
[fluxo vigente](../../../docs/vigentes/autoritativos/fluxo_de_implementacao.md)
e [regras de negócio](../../../docs/vigentes/autoritativos/regras_de_negocio.md).
O progresso pertence ao [estado operacional](../../../docs/vigentes/operacional/estado_atual.md).

Cada microciclo exige autorização independente e pausa obrigatória ao
terminar. A aprovação de um incremento não autoriza o seguinte. O pedido de
implementação avança somente pelo microciclo autorizado conforme essa divisão.

## Implementação e contratos

### Fachada e eventos

- Preservar `PublicadorPcaScriptcase::executar(...)` e seu retorno atual.
- Acrescentar `executarComEventos(...)`, com os mesmos argumentos, retornando
  um objeto com `resultado`, `eventos` e `avisoRegistroTecnico`. O resultado
  mantém o ViewModel existente; os eventos são envelopes sanitizados, sem
  objetos de Infrastructure; o aviso é seguro ou `null`.
- Compartilhar o caminho de execução, sem executar a publicação duas vezes.
  Preservar `CompositionRootPublicacaoPca::montar(...)` e acrescentar uma
  composição interna que disponibilize fachada, acumulador e estado
  transacional da mesma chamada, sem estado compartilhado entre execuções.
- Exportar eventos ordenados após a execução, preservando correlação e
  `executionTimeMs` somente no último envelope exportado, sem mutar originais.
- Falhas anteriores à criação do logger retornam eventos vazios. Falha de
  exportação preserva o resultado e acrescenta aviso seguro.
- Impedir persistência de logs quando o encerramento da transação Oracle não
  estiver confirmado. Não tentar commit, compensação ou nova publicação para
  registrar logs.
- Atualizar DI-006, DI-010 e fluxo para registrar a integração autorizada,
  mantendo eventos fora de `PublicarPcaResponse`. Contrato aprovado não
  representa implementação ou gate concluído.

### Blank existente

- Versionar a adaptação em `integracao/scriptcase/PCA_PUBLICAR_B/onExecute.php`.
  O código da Blank fornecido pelo mantenedor é a referência para a interface;
  suas chamadas antigas e decisões de integração não substituem as DI vigentes.
- Carregar `backend/vendor/autoload.php` e chamar somente a fachada pública,
  passando conexão emprestada, login, `SERVER_NAME`, ano e
  `HTTP_X_REQUEST_ID`, sem gerar ou substituir a correlação na Blank.
- Retirar Controller antigo, seleção de credenciais e inferência de ambiente
  por hostname. Perfis continuam externos e provisionados pelo servidor.
- Validar JSON e exigir ano inteiro; deixar a validade do exercício no
  domínio. Corrigir os botões invertidos e usar o ano do servidor nos rótulos.
- Remover a espera artificial, impedir submissões simultâneas e restaurar os
  controles ao terminar.
- Responder com `{resultado, avisoRegistroTecnico}`, serializando
  explicitamente os campos públicos do ViewModel e seus resultados unitários.
  Não enviar eventos ao navegador.
- Renderizar situações globais, resultados unitários, contagens e correlação
  com conteúdo escapado. Não mostrar exceções ou respostas brutas.
- Manter o link existente identificado como consulta pública em produção,
  sem usar seu CNPJ na execução.
- Usar mensagens fixas para falhas de transporte e não repetir
  automaticamente a publicação.

### Logs

- Chamar `sc_log_add('execute', $json)` uma vez por evento, após o retorno
  seguro do backend.
- Gravar JSON integral em `SGI.SEGURANCA_LOG.DESCRIPTION`, informado pelo
  mantenedor como `CLOB`; `ACTION` é `VARCHAR2(30 BYTE)`. A gravação pela macro
  ainda depende de gate real. Não cortar em 3.900 caracteres nem converter
  para ISO-8859-1.
- Usar escapes Unicode no JSON para preservar caracteres no ambiente
  Windows-1252 e manter objetos vazios nos campos que representam mapas.
- Se ocorrer falha recuperável de serialização ou gravação, interromper os
  registros restantes, preservar o resultado e mostrar aviso separado.
  Não repetir registros já tentados nem fragmentar envelopes.
- Manter explícita a limitação de falhas fatais anteriores à resposta HTTP,
  que podem impedir a entrega do resultado e a persistência completa dos
  eventos.

## Ajuda ao operador — último incremento funcional

Adicionar o botão **Ajuda**, ao lado de **Publicar**, usando JavaScript e o
SweetAlert já presente. O modal terá título **Como funciona a publicação do
PCA**, conteúdo estático, rolagem em telas pequenas e botão **Entendi**.

O texto explicará, em linguagem de operação:

- **Finalidade:** sincronizar o Plano de Contratações Anual do SGI com o PNCP.
- **Exercício:** escolher o ano corrente ou o seguinte, correspondente à
  execução das compras e contratações (`RN-CAD-002`).
- **Demandas consideradas:** homologadas, compradas e publicadas participam;
  planejadas e canceladas não participam (`RN-DEM-009`).
- **Ação automática:** conforme a comparação entre SGI e PNCP, o sistema pode
  publicar, retificar, excluir ou informar que não há alterações. O número
  da versão não determina sozinho a ação (`RN-PUB-001` a `RN-PUB-005`).
- **Modos:** no individual, cada unidade recebe resultado próprio; no
  consolidado, as unidades compõem um único PCA. Uma unidade vazia não exclui
  o consolidado enquanto outras tiverem demandas (`RN-PUB-009` e `RN-PUB-010`).
- **Exclusão e nova publicação:** o PCA ativo pode ser excluído quando seu
  conjunto ficar vazio; novas demandas após exclusão podem exigir outra
  publicação (`RN-PUB-003` e `RN-PUB-007`).
- **Resultado:** explicar conclusão, conclusão com pendências, solicitação
  inválida e interrupção. Resultados já concluídos não são automaticamente
  desfeitos por uma falha posterior (DI-004 e DI-010).
- **Conduta:** preparar alterações pelo fluxo normal do SGI, aguardar o
  processamento e conferir os resultados. Diante de resposta incerta,
  consultar o responsável com o identificador da execução antes de repetir.
- **Aviso de logs:** uma falha no registro técnico não altera, por si só,
  o resultado apresentado.

A ajuda não fará requisições, não registrará eventos e não alterará dados.
O botão ficará indisponível durante o processamento para não substituir o
modal de andamento. Fechar a ajuda devolverá o foco ao botão. O conteúdo não
mostrará SQL, classes, credenciais ou procedimentos técnicos de recuperação.

## Microciclos e validação

| Microciclo | Entrega e critério de saída |
| --- | --- |
| **1 — Contratos** | Registrar o plano e atualizar os contratos de exportação, logs e tratamento de falhas. |
| **2 — Exportação** | Implementar a extensão compatível da fachada; testar isolamento entre chamadas, sanitização e segurança transacional. |
| **3 — Falhas integradas** | Demonstrar contenção, falha reconciliável e interrupção sistêmica usando adapters reais com ADOdb e transporte controlados. |
| **4 — Blank** | Adaptar o código fornecido; validar entrada, seleção de ano, resultados, submissão única e tratamento dos logs. |
| **5 — Pacote e preparação** | Gerar pacote de homologação isolado com dependências de execução e preparar suporte contratual opt-in, consultas completas e PRECHECK. |
| **6 — Gate real dos logs** | Comprovar ação `execute`, JSON integral, acentos e evento sintético identificado com mais de 4.000 bytes; conferir conteúdo persistido por leitura. |
| **7 — Contenção real** | Reter a âncora consolidada em uma sessão Oracle e executar pela outra. Esperar `BLOQUEADO` e `CONCLUIDA_COM_PENDENCIAS`, sem mutação PNCP ou auditoria de negócio. Liberar por rollback e conferir disponibilidade. |
| **8 — Smoke do pacote** | Executar a Blank com o pacote instalado sobre o consolidado 2026 já excluído. Esperar `CONCLUIDA/SEM_ALTERACAO`, renderização correta e logs persistidos. |
| **9 — Ajuda ao operador** | Implementar o botão e o modal; revisar conteúdo contra as regras e verificar abertura, fechamento, teclado, foco e tela pequena, sem executar publicação. |
| **10 — Encerramento** | Atualizar o artefato com a ajuda, verificar a interface instalada, executar regressões e registrar evidências e estado final. |

Antes de cada gate real, fornecer SQL somente leitura com colunas e
cardinalidades esperadas, conferir resultados, fixar checkpoint e repetir o
PRECHECK automático antes da chamada. Usar o hash canônico ADOdb e a
normalização de charset existente. A conferência manual não substitui o
PRECHECK; o suporte dos gates permanece fora do pacote de execução.

Os testes deverão comprovar:

- Continuidade após falhas específicas no modo individual e interrupção após
  falha sistêmica, preservando resultados conhecidos e sem fabricar resultados
  para unidades não visitadas.
- Resultado conjunto no modo consolidado.
- Observação após escrita incerta, sem repetição cega e com no máximo uma
  reconciliação adicional.
- Ausência de segredos e detalhes internos nas saídas públicas e nos eventos.
- Preservação do PCA 36 excluído, dos legados 16/17/18, das confirmações
  existentes e da versão intermediária 103 sem confirmação; nenhuma nova
  auditoria de negócio no smoke.
- Funcionamento do pacote sem checkout ou Composer no servidor, com PHP 8.1
  em homologação. Gerar `vendor/` pelo lock em diretório isolado, sem
  dependências de desenvolvimento, testes, probes ou segredos.

Executar suítes afetadas, suíte padrão, contratos locais anteriores e novos
testes da 8.5; conferir lint aplicável, PSR-4, dependências e diff. Validar
macros pela compilação Scriptcase. Para a ajuda estática, usar revisão de
conteúdo e verificação funcional no navegador.

## Limites e condições

- Nenhum commit, acesso à produção ou repetição dos gates concluídos da
  Sprint 8.4.
- Gates desta sprint proíbem mutações PNCP. O smoke admite transações e DML
  Oracle internos autorizados; não é somente leitura.
- Configurações, consentimentos, perfis e segredos permanecem externos ao
  repositório, pacote e webroot. Testes e probes ficam fora do pacote de
  execução.
- Divergência de checkpoint bloqueia o gate; não corrigir dados automaticamente.
- Se 2026 deixar de satisfazer `RN-CAD-002`, interromper o roteiro e planejar
  nova massa.
- Incompatibilidade da macro com CLOB ou tratamento seguro de erros bloqueia
  seu gate, sem substituição silenciosa por SQL direto.

### Resultado bloqueante do Microciclo 6

Em 20/09/2026, depois da habilitação do módulo de logs na Blank de teste, o
PRECHECK real confirmou o checkpoint
`f52668437c192e37eca8757e1250aebb2d87a318961efc03a8e03637536868bd` e zero
ocorrências do identificador. A chamada única de `LOGS` alcançou
`sc_log_add`, mas o Scriptcase emitiu HTML com `ORA-01704: literal de string
extenso demais` antes do JSON seguro. A releitura imediata encontrou zero
linhas identificadas. O gate ficou bloqueado tanto pela ausência de gravação
integral do CLOB quanto pelo vazamento da mensagem técnica na resposta HTTP.
A configuração foi desarmada e a chamada não pode ser repetida ou substituída
por SQL direto dentro do plano vigente.

### Microciclo 6A corretivo — registro próprio de CLOB

Autorizado pelo mantenedor em 20/09/2026 antes do avanço ao Microciclo 7.

O corretivo substituirá `sc_log_add` somente para os eventos técnicos do PCA
por uma fachada pública concreta chamada
`RegistradorEventosTecnicosScriptcase`. Ela ficará diretamente em `src/`, ao
lado do publicador consumido pela Blank. O detalhe Oracle ficará em
`src/Infrastructure/Oracle/Observabilidade/`, e a montagem necessária, em
`src/Composition/`.

O nome da fachada não recebe o sufixo `Port`: ele identifica um ponto de entrada
concreto e específico do Scriptcase. Uma eventual Port deve representar uma
capacidade interna neutra, sem mencionar Scriptcase ou Oracle, e somente será
criada se a implementação demonstrar uma fronteira real.

Antes da escrita de produção, o corretivo deve:

1. confirmar por diagnóstico somente leitura a presença de `UpdateClob` e dos
   métodos transacionais no ADOdb hospedado;
2. inspecionar geração do ID, constraints, triggers e uma amostra sanitizada da
   linha criada pelo módulo nativo;
3. revisar DI-006, DI-010 e fluxo para permitir uma transação técnica nova e
   curta somente depois do encerramento confirmado da transação de negócio;
4. implementar e testar a gravação integral com `EMPTY_CLOB()` e
   `UpdateClob`, sem SQL na Blank e sem segunda conexão;
5. repetir o gate com identificador novo e checkpoint novo antes de concluir o
   Microciclo 6.
- Implantação de produção, rollback de release, monitoramento, retenção e
  suporte permanecem na Fase 9.

## Registro do Microciclo 1

Em 17/09/2026, foram registrados o plano aprovado e os contratos de exportação,
logs e tratamento de falhas. Roadmap, estado operacional e a referência de
apoio foram alinhados para distinguir contrato aprovado de implementação
pendente. Não houve alteração de código PHP, dependências, configuração
externa ou dados reais. O Microciclo 2 exige nova autorização.

## Registro do Microciclo 2

Em 18/09/2026, `PublicadorPcaScriptcase::executarComEventos(...)` foi
implementado preservando integralmente `executar(...)`. A composição da mesma
chamada reúne fachada, acumulador e conexão; exporta envelopes sanitizados
somente após o encerramento transacional e preserva o ViewModel com aviso
seguro diante de transação ativa ou falha de exportação. Não houve nova
execução do fluxo para obter os eventos nem compartilhamento de estado entre
chamadas.

A suíte focada aprovou 38 testes/608 asserções; Composition, Integration,
Observabilidade e contrato público aprovaram 70/959; a suíte padrão aprovou
935/4.347, e os contratos 8.4 permaneceram verdes com 73/703. O lint dos nove
arquivos PHP envolvidos, o autoload PSR-4 estrito com 1.820 classes e
`git diff --check` foram aprovados. Nenhum acesso Oracle ou PNCP real,
configuração externa, Blank, pacote ou commit foi executado. O Microciclo 3
exige nova autorização.

## Registro do Microciclo 3

Em 18/09/2026, a suíte integrada passou a comprovar três comportamentos com os
adapters concretos e somente as fronteiras ADOdb e cURL controladas: contenção
`ORA-00054` em uma unidade permite continuar a varredura e termina como
`BLOQUEADO/CONCLUIDA_COM_PENDENCIAS`; escrita incerta executa no máximo uma
reconciliação adicional, sem repetir a escrita; falha sistêmica de transporte
na segunda unidade interrompe a varredura e preserva o resultado concluído da
primeira.

Os cenários também comprovam rollback, ausência de escrita PNCP e auditoria de
negócio indevidas e mensagens públicas sem o detalhe físico da falha. A suíte
focada aprovou 3 testes/44 asserções; as integrações afetadas aprovaram
16/313; a suíte padrão aprovou 938/4.391. Os contratos locais 5.1, 5.9, 8.2,
8.3 e 8.4 aprovaram, respectivamente, 24/51, 54/149, 12/43, 14/62 e 73/703.
O lint dos quatro arquivos PHP do incremento e o autoload PSR-4 estrito com
1.821 classes foram aprovados em PHP 8.4.7. Nenhum recurso externo real,
configuração, Blank, pacote ou commit foi acessado. O Microciclo 4 exige nova
autorização.

## Registro do Microciclo 4

Em 18/09/2026, a adaptação da `PCA_PUBLICAR_B` foi versionada em
`integracao/scriptcase/PCA_PUBLICAR_B/onExecute.php`. A Blank carrega o
autoload do pacote, valida JSON e ano inteiro e chama somente
`PublicadorPcaScriptcase::executarComEventos(...)` com a conexão emprestada e
os dados do hospedeiro. O resultado público é serializado campo a campo; os
eventos não seguem ao navegador e são destinados individualmente a
`sc_log_add('execute', $json)`, sem corte ou conversão de charset.

A interface usa os anos calculados no servidor, impede submissões simultâneas,
remove a espera artificial e apresenta resultados e correlação por nós de
texto. O CNPJ do link de consulta pública não participa da execução. Falhas de
transporte usam mensagem fixa e não provocam repetição automática.

Os testes focados aprovaram 38 testes/558 asserções; a suíte padrão aprovou
944/4.464. Os contratos locais 5.1, 5.9, 8.2, 8.3 e 8.4 aprovaram,
respectivamente, 24/51, 54/149, 12/43, 14/62 e 73/703. O lint PHP, a sintaxe do
JavaScript extraído e o autoload PSR-4 estrito com 1.822 classes também foram
aprovados em PHP 8.4.7 e Node.js 24.16.0. A compilação das macros no Scriptcase,
o comportamento real do CLOB e a verificação em navegador pertencem aos gates
posteriores. Nenhum recurso externo real, pacote instalado ou commit foi
executado. O Microciclo 5 exige nova autorização.

## Registro do Microciclo 5

Em 18/09/2026, foi gerado em `build/pca-pncp-backend` um pacote isolado com
`src/`, `vendor/` de produção e manifesto SHA-256. O construtor usa o
`composer.lock` em diretório isolado, instala com `--no-dev` e remove os
arquivos Composer do artefato após gerar o autoload autoritativo. O verificador
recusa testes, probes, configurações, perfis, segredos e dependências de
desenvolvimento. A fachada foi carregada diretamente do pacote, sem usar o
autoload do checkout.

O suporte opt-in da Sprint 8.5 passou a validar consentimentos por etapa,
ambiente de homologação, modo consolidado, massa protegida e hash bloqueante.
O PRECHECK reutiliza a normalização de charset, o executor ADOdb e o hash
canônico da Sprint 8.4. Às onze consultas completas existentes foi acrescentada
a leitura integral de `SGI.SEGURANCA_LOG.DESCRIPTION`, identificada por valor
único e com colunas e cardinalidade declaradas. Todas as doze consultas são
`SELECT`, sem lock ou escrita.

Após o corretivo de nomenclatura aceito pelo mantenedor, as ferramentas ficaram
em `tools/package/build.php` e `tools/package/verify.php`, o destino passou a
ser `build/pca-pncp-backend` e o teste que inspecionava o texto interno dessas
ferramentas foi removido. Os contratos de configuração, consultas, PRECHECK e
segurança dos gates foram preservados. O catálogo de diretórios passou a
explicar a organização crescente do projeto e a diferença entre ferramenta e
artefato gerado.

Os contratos 8.5 aprovaram 19 testes/190 asserções; a suíte padrão permaneceu
verde com 944/4.464. Os contratos 5.1, 5.9, 8.2, 8.3 e 8.4 aprovaram 24/51,
54/149, 12/43, 14/62 e 73/703. Lint, Composer, PSR-4 estrito com 1.827 classes,
construção, verificação de 193 arquivos de conteúdo mais o manifesto e
carregamento isolado da fachada foram
aprovados em PHP 8.4.7. A instalação isolada das dependências também simula a
plataforma PHP 8.1 exigida pela homologação. Nenhum Oracle, PNCP ou Scriptcase real foi
acessado e nenhum commit foi criado. O Microciclo 6 exige nova autorização.

## Adaptação autorizada para o validaSGI

Depois de identificado que o Scriptcase de desenvolvimento usa o Oracle DEV e
o validaSGI usa o Oracle de homologação, os gates reais dos Microciclos 6 a 8
foram destinados ao validaSGI. O DEV permanece disponível para compilação e
ensaio, sem substituir a evidência do ambiente instalado.

A fotografia de homologação mostrou planos de 2026 aptos a uma publicação.
Como o Microciclo 6 não pode acessar nem alterar o PNCP, seu checkpoint passou
a ser independente da massa de PCA: ele confere somente a estrutura física de
`SGI.SEGURANCA_LOG` e a ausência do identificador sintético. A etapa `LOGS`
repete esse PRECHECK e compara o hash canônico antes de realizar uma única
gravação pelo adapter Oracle de CLOB aprovado no corretivo 6A.

Esse gate técnico não carrega o perfil PNCP. Sua configuração mínima fixa o
host do validaSGI e o `charset_adodb` aprovado, evitando depender de
credenciais e mapeamentos que não participam da prova do CLOB.

O JSON sintético usa escapes Unicode, ultrapassa 4.000 bytes e preserva mapas
vazios. Depois do registrador, a Blank relê o CLOB integral e exige uma única linha,
conteúdo idêntico e acentos decodificáveis. Falha ou resposta incerta não
autoriza repetição. Os Microciclos 7 e 8 deverão receber checkpoints próprios
da massa de homologação antes de suas execuções.

Em 18/09/2026, a conferência manual do Microciclo 6 confirmou no Oracle de
homologação `ACTION` como `VARCHAR2(30 BYTE)`, `DESCRIPTION` como `CLOB` e zero
linhas para `SPRINT85-LOGS-HML-20260918-JSILVA-01`. A configuração permaneceu
em `PRECHECK`, sem consentimento de logs. O próximo checkpoint é a execução
somente leitura pela conexão ADOdb do validaSGI para calcular o hash canônico.

Para essa execução, o checkout completo não será instalado no validaSGI. O
artefato temporário `build/pca-pncp-log-gate` reúne uma cópia validada do pacote
principal e uma lista fechada do runtime da probe. Seu manifesto cobre 205
arquivos de conteúdo; configuração e segredos permanecem externos.

Em 19/09/2026, o PRECHECK ADOdb do validaSGI confirmou as duas consultas e
fixou o checkpoint
`f52668437c192e37eca8757e1250aebb2d87a318961efc03a8e03637536868bd`.
A configuração local candidata foi armada para `LOGS`, mantendo escritas
Oracle e PNCP falsas. A próxima operação é uma única execução do evento
sintético, seguida pela releitura automática e pelo pós-check manual.

A primeira tentativa foi recusada com `CORRELACAO_INVALIDA` antes de
`sc_log_add`, confirmando zero escrita. O resultado não distingue ausência,
nome físico diferente ou formato incompatível do header. A Blank de teste
passou a expor, somente no PRECHECK, presença, valor, tamanho e validade UUID v4
de `HTTP_X_REQUEST_ID` e `HTTP_X_CORRELATION_ID`. Nenhuma correlação será
fabricada, e a Blank real permanece inalterada.

Depois da configuração do UUID v4 no validaSGI, a tentativa única alcançou
`sc_log_add`, mas a releitura automática encontrou cardinalidade diferente de
um. O resultado é incerto e não autoriza repetição. A configuração local foi
desarmada; o diagnóstico seguirá exclusivamente pelo `logs_postcheck.sql`, que
lê o identificador, o CLOB integral, o tamanho, os escapes, os mapas vazios e a
validade JSON.

## Implementação local do corretivo 6A

Em 20/09/2026, a confirmação do trigger `TR_SEGURANCA_LOG` demonstrou que o
Oracle preenche `ID` com `ID_LOG.NEXTVAL` quando a chave chega nula. O corretivo
passou a inserir um marcador curto sem informar o ID, localizar a única chave
gerada e gravar o JSON integral por `UpdateClob`, na mesma conexão ADOdb e em
uma transação técnica nova para cada evento.

A Blank chama `RegistradorEventosTecnicosScriptcase`, uma fachada pública
concreta. A composição carrega o charset do perfil externo e monta
`OracleRegistroEventoTecnicoAdapter`; nenhum SQL ou detalhe da tabela foi
levado para a Blank. O primeiro erro interrompe os eventos restantes, preserva
os eventos já confirmados e devolve somente o aviso técnico seguro.

Os testes focados aprovaram 25 testes/142 asserções; a suíte padrão aprovou
951/4.500; os contratos 8.5 aprovaram 27/250. Os contratos locais 5.1, 5.9,
8.2, 8.3 e 8.4 aprovaram 24/51, 54/149, 12/43, 14/62 e 73/703. Lint e PSR-4
estrito com 1.838 classes também passaram. O pacote principal foi verificado
com 197 arquivos e o artefato do gate com 209, ambos sem configurações ou
segredos.

O novo gate usa o identificador
`SPRINT85-LOGS-HML-20260921-JSILVA-02`. Sua configuração deve permanecer em
`PRECHECK`, com hash nulo e todas as escritas falsas, até a nova fotografia
somente leitura no validaSGI. A prova real do CLOB ainda é necessária para
encerrar o Microciclo 6; o Microciclo 7 continua bloqueado até a pausa
obrigatória.

Em 21/09/2026, o PRECHECK manual e o PRECHECK ADOdb do validaSGI confirmaram
estrutura 2, cardinalidade 0, identificador novo, correlação UUID v4 e os quatro
métodos ADOdb. O snapshot produziu novamente o hash
`f52668437c192e37eca8757e1250aebb2d87a318961efc03a8e03637536868bd`. A
configuração externa local foi armada e validada para uma única chamada de
`LOGS`, mantendo somente `allow_log_writes = true`; Oracle de negócio e PNCP
continuam proibidos.

## Registro de conclusão do Microciclo 6

Em 21/09/2026, a chamada única do novo registrador retornou
`LOGS_CONFIRMADOS`. A releitura automática encontrou exatamente uma linha com
`ACTION = execute`, 11.179 bytes e SHA-256
`d48fcf1daf915e5064d2b3df4b00b3805e9085c5b35393cfdcd494a429c3f326`, idêntico
ao conteúdo enviado. Acentos e mapas vazios foram preservados.

O pós-check manual, executado por uma leitura independente, confirmou a mesma
cardinalidade, o identificador do gate, o tamanho integral, os escapes Unicode,
os mapas vazios e a validade JSON. Não houve acesso ou escrita PNCP nem escrita
Oracle de negócio. A configuração externa local foi desarmada para `PRECHECK`,
hash nulo e todos os consentimentos falsos. A chamada `LOGS` não deve ser
repetida.

O Microciclo 6 e seu corretivo 6A estão concluídos. Aplica-se a pausa
obrigatória prevista no plano; o Microciclo 7 exige autorização independente,
checkpoint próprio e novo PRECHECK.

## Preparação autorizada do Microciclo 7

Em 21/09/2026, o mantenedor autorizou o Microciclo 7 no validaSGI. O suporte
local separa o gate de contenção do gate de logs e exige, nesta ordem: o
checkpoint Oracle integral, a comprovação de que outra sessão reteve a âncora
`UNIDADE_EXTERNA/2026/930281`, a execução única do fluxo e a igualdade do
snapshot antes e depois.

Como o fluxo autentica antes de tentar a âncora, o transporte do gate permite
somente o POST de login e bloqueia qualquer mutação PNCP antes do envio. O
resultado aceito é uma unidade `BLOQUEADO`, situação global
`CONCLUIDA_COM_PENDENCIAS`, nenhum DML Oracle de negócio e nenhuma nova
auditoria. A sessão que retém a âncora deverá liberá-la por `ROLLBACK`; uma
etapa separada comprovará sua disponibilidade.

O roteiro materializado possui doze SELECTs e usa a fotografia de homologação
com 112 planos e 112 demandas ativas nas UOs 3, 94 e 149, sem auditoria de
negócio. A configuração modelo começa em `PRECHECK`, com hash nulo
e todos os consentimentos falsos. O artefato isolado
`build/pca-pncp-containment-gate` foi gerado e validado com 212 arquivos, sem
configuração ou segredo. A execução real continua condicionada à conferência
manual e ao novo PRECHECK da Blank.

Após confirmação do mantenedor, `perfil-homologacao.php` recebeu o mesmo CNPJ,
credenciais PNCP de treinamento e mapeamentos Oracle do perfil DEV. O CA bundle
público foi copiado para a mesma pasta e referenciado por `__DIR__`, evitando um
caminho absoluto do Windows no validaSGI. O preflight local confirmou o perfil
de homologação e as doze consultas sem acessar Oracle ou PNCP.

A replicação semanal de produção substituiu a massa inicialmente preparada. O
mantenedor autorizou aproveitar a fotografia nova e manipular homologação. Um
seed protegido por ambiente projetou `CONSOLIDADO/930281` nos 112 planos e
criou os dois controles de concorrência, preservando as demandas. O post-check
do seed e a conferência manual das doze consultas foram aprovados com as
cardinalidades `2, 1, 1, 3, 112, 3, 112, 112, 112, 0, 2, 0`. As consultas 10 e
12 vazias confirmam ausência de auditoria de negócio e do identificador técnico
do gate. Falta obter e fixar o hash canônico pelo PRECHECK da Blank antes de
qualquer contenção real.

O PRECHECK da Blank confirmou as doze consultas pela conexão ADOdb e fixou o
hash canônico
`1a64c049431e093f784095dd66c31aef757415aaed7309f053adc0cab691a77e`, sem
qualquer escrita ou acesso PNCP. A configuração externa local foi armada para
`CONTENCAO` com somente `allow_log_writes = true`; Oracle de negócio e PNCP
permanecem bloqueados. Antes da chamada única, essa configuração deve ser
copiada para o validaSGI e outra sessão Oracle deve reter a âncora prevista.

## Registro de conclusão do Microciclo 7

Em 21/09/2026, uma sessão Oracle externa reteve a âncora
`UNIDADE_EXTERNA/2026/930281` e a chamada única da Blank retornou
`CONTENCAO_CONFIRMADA`. O resultado foi `BLOQUEADO` e
`CONCLUIDA_COM_PENDENCIAS`; os snapshots antes e depois conservaram o hash
canônico, a auditoria de negócio foi preservada e nenhuma escrita PNCP ocorreu.
O único acesso remoto foi o login permitido, com HTTP 200, e um evento técnico
foi registrado.

Após o `ROLLBACK` da sessão que retinha a linha, a etapa `DISPONIBILIDADE`
retornou `ANCORA_DISPONIVEL`, sem escrita Oracle, acesso PNCP ou log. A
configuração externa local foi desarmada para `PRECHECK`, hash nulo e todos os
consentimentos falsos. O Microciclo 7 está concluído e aplica-se a pausa
obrigatória; o Microciclo 8 exige autorização independente.

## Preparação autorizada do Microciclo 8

O mantenedor autorizou o Microciclo 8 em 21/09/2026. A fotografia posterior à
contenção ainda possui 112 demandas participantes e, por isso, produziria uma
publicação em vez do smoke sem alteração. A evidência já aprovada permite uma
preparação reversível: 106 demandas estavam na situação 2, seis na situação 3,
todas as confirmações estavam nulas e as versões vigentes são os planos 143,
124 e 224.

O seed exclusivo de SGIHML altera as demandas para situação 4 e projeta os 112
planos como `CONSOLIDADO/930281/EXCLUIDO/36`. Ele preserva os dois controles,
a ausência de auditoria e o plano intermediário 103 sem confirmação. A limpeza
restaura os valores anteriores e aceita somente as confirmações das três
versões vigentes que o fluxo sem alteração pode produzir. A configuração e as
doze consultas do smoke começam desarmadas; nenhum DML deste microciclo foi
executado ainda.

Os contratos 8.5 aprovaram 43 testes/379 asserções; a suíte padrão,
951/4.500; e o PSR-4 estrito, 1.843 classes. O pacote principal foi reconstruído
e verificado com 197 arquivos, sem dependências de desenvolvimento,
configurações ou segredos. O preflight local confirmou as doze consultas,
perfil `HOMOLOGACAO` e ausência de I/O externo.

O precheck manual do seed foi aprovado no SGIHML com os 112 planos sem
confirmação, a distribuição original 106/6 das situações 2/3, os seis IDs
esperados, as versões vigentes 143/124/224, os dois controles, auditoria zero e
o plano 103 ainda sem confirmação. O seed do smoke está liberado pelas guardas
para execução como script completo.

O seed foi executado com sucesso e o post-check confirmou 112 planos
`CONSOLIDADO/930281/EXCLUIDO/36`, zero confirmações, 112 demandas canceladas,
nenhuma participante, dois controles e o plano 103 preservado. A massa está
pronta para a conferência manual das doze consultas do smoke.

A conferência manual das doze consultas foi aprovada com as cardinalidades
`2, 1, 1, 3, 112, 3, 0, 112, 112, 0, 2, 0`. Fotografia, auditoria e log novo
estão vazios; o ciclo contém 112 demandas canceladas; e a projeção mantém os
112 planos em `CONSOLIDADO/930281/EXCLUIDO/36`. O próximo passo é obter o hash
canônico pelo PRECHECK ADOdb da Blank de teste.

O PRECHECK ADOdb confirmou a fotografia e fixou o hash
`f0c17e5e43f8aa5fc89cff3c211838ed5e64f8d0d39c2487afe2ec6d3fd6013c`, sem
I/O externo ou escrita. A configuração local passou no preflight armada para
`SMOKE`, com somente DML Oracle interno e logs permitidos. O pós-check de sete
consultas somente leitura também foi materializado antes da chamada real.

A primeira abertura da aplicação real por POST de navegação revelou que a
Blank ainda classificava qualquer POST como publicação. A solicitação foi
recusada antes da fachada, sem I/O de negócio. A adaptação passou a reconhecer
como publicação somente POST com `Content-Type: application/json`, preservando
POSTs usados pelo menu para abrir a interface. As mensagens fixas também foram
tornadas independentes do charset do editor Scriptcase. Antes do smoke, resta
recompilar essa versão e provisionar o registro de perfis no processo do
servidor HTTP; um `export` em terminal separado não altera o Apache já iniciado.
Depois da correção, passaram 47 testes/432 asserções da Sprint 8.5, 953
testes/4.513 asserções da suíte padrão e o carregamento PSR-4 estrito das 1.843
classes.

A chamada única do smoke retornou HTTP 500 com a resposta pública
`INTERROMPIDA/FALHA_INESPERADA`, sem correlação. O POST continha o JSON esperado
e a mensagem foi entregue em UTF-8. O pós-check resolveu a incerteza: três
versões vigentes foram confirmadas, a projeção e o plano 103 foram preservados,
não houve auditoria e quatro eventos com a correlação
`ad5d9e3f-3ed2-45da-a3e6-aedf5ef83641` encerram em `PERSISTIR_CONVERGENCIA`.
Logo, o backend concluiu o smoke e a falha ocorreu depois, na saída da Blank. A
chamada não será repetida. A Blank passou a processar JSON público válido mesmo
em HTTP não 2xx e a preservar uma correlação válida no fallback. A instalação
foi posteriormente conferida pelos hashes do pacote.

O conteúdo integral do pacote foi instalado diretamente em
`/var/www/html/PCA_PUBLICAR_B/backend`. Os seis arquivos críticos conferidos
possuem os mesmos SHA-256 da build local. A causa técnica do HTTP 500 anterior
não foi atribuída a cache; o resultado funcional foi comprovado pelo estado e
pelos eventos, e a Blank recebeu o tratamento seguro correspondente.

As duas camadas temporárias da massa foram removidas na ordem prevista. O
pós-check final confirmou 112 planos sem estratégia, UASG, estado, sequencial
ou confirmação; 106 demandas na situação 2 e seis na situação 3; nenhum
controle de 2026; plano 103 sem confirmação; e auditoria de negócio vazia. A
configuração externa permanece desarmada em `PRECHECK`, sem permissões de
escrita. O Microciclo 8 está concluído e aplica-se a pausa obrigatória antes do
Microciclo 9.

## Registro do Microciclo 9

O botão `Ajuda` foi acrescentado ao lado de `Publicar`. O modal estático usa o
SweetAlert já carregado, contém as nove seções operacionais previstas, oferece
rolagem em telas pequenas, fecha por teclado ou pelo botão `Entendi` e devolve o
foco ao botão de origem. A ajuda não chama `fetch`, não registra eventos e não
altera dados. Durante seleção ou processamento, os dois botões permanecem
desabilitados.

O mantenedor atualizou a Blank e confirmou no navegador que o botão funciona.
O lint PHP e o teste focado foram aprovados novamente com 9 testes/115
asserções. Os contratos 8.5 passaram novamente com 48 testes/461 asserções; a
suíte padrão anteriormente aprovada permanece com 954 testes/4.540 asserções.
O Microciclo 9 está concluído e aplica-se a pausa obrigatória antes do
Microciclo 10.

## Registro do Microciclo 10

O pacote autocontido final foi reconstruído em `build/pca-pncp-backend` e
validado com 196 arquivos de conteúdo mais o manifesto. Ele contém somente
`src/`, `vendor/` e `PACKAGE-MANIFEST.json`, sem dependências de
desenvolvimento, configurações ou segredos. As fachadas públicas de publicação
e registro técnico são carregáveis pelo autoload do próprio pacote. O SHA-256
do manifesto final é
`4de4c0bd063d8388e42b85a2746caed3d5d315113dd85fa34d368bd5a773e187`.

O `onExecute` permanece como componente separado da aplicação Scriptcase. Sua
versão final tem SHA-256
`7a78f42ccc198407b3b4549c677ccfcd3fe0850cc1b4ab5542bd265724f171ee`.
O mantenedor confirmou o funcionamento do botão de ajuda na Blank instalada.

A regressão final aprovou a suíte padrão com 954 testes/4.542 asserções e os
contratos locais 5.1, 5.9, 8.2, 8.3, 8.4 e 8.5, respectivamente com 24/51,
54/149, 12/43, 14/62, 73/703 e 48/461. O lint aprovou 404 arquivos PHP, o
autoload PSR-4 estrito gerou 1.843 classes e os requisitos da plataforma foram
atendidos no PHP 8.4.7. O `composer.json` permaneceu válido, com apenas o aviso
já conhecido de licença não declarada.

Nenhum gate real foi repetido, nenhum recurso de produção foi acessado e nenhum
commit foi criado. O `git diff --check` continua apontando espaços finais que
já pertenciam às alterações locais preservadas, inclusive na `README.md` e na
formatação SQL mantida a pedido do mantenedor. A Sprint 8.5 está concluída. O
próximo trabalho pertence à Fase 9 e exige planejamento e autorização próprios.
