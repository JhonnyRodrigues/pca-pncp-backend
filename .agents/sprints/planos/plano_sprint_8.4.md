# Sprint 8.4 — Fluxos completos dos dois modos

**Status:** Microciclos 1 a 4 concluídos em 08/09/2026. O Microciclo 5 avançou
até a publicação individual real, confirmada em 09/09/2026. O Microciclo 6 foi
concluído em 09/09/2026 com a confirmação real de `NENHUMA_ACAO` individual.
O Microciclo 7 e seu corretivo 8.4.7-C foram concluídos em 16/09/2026, após a
retificação PNCP e a projeção uniforme do estado operacional nas versões
Oracle. Essas execuções não devem ser repetidas. O Microciclo 8 foi concluído
em 16/09/2026 com a exclusão integral individual e o retorno da configuração
externa ao modo somente leitura. O Microciclo 9 foi concluído em 17/09/2026
com o checkpoint real do modo consolidado, sem escrita Oracle ou PNCP. A
publicação consolidada do Microciclo 10 foi concluída em 17/09/2026 e a
configuração externa retornou ao modo somente leitura. O Microciclo 11 foi
concluído em 17/09/2026 com a comprovação real de `NENHUMA_ACAO` consolidada.
O Microciclo 12 foi concluído em 17/09/2026 com a retificação consolidada real
e o retorno da configuração externa ao modo somente leitura. As etapas 13A,
13B e 13C foram concluídas em 17/09/2026, incluindo a exclusão consolidada
real e o retorno final da configuração externa ao modo somente leitura. O
Microciclo 14 foi concluído em 17/09/2026 com a regressão e as inspeções finais.
A Sprint 8.4 está concluída; nenhuma execução real deve ser repetida. A Sprint
8.5 é apenas a próxima na ordem e permanece sem autorização.

## Resumo

Comprovar em homologação a matriz completa dos quatro desfechos nos dois modos:

| Modo | PUBLICAR | NENHUMA_ACAO | RETIFICAR | EXCLUIR_PCA |
| --- | --- | --- | --- | --- |
| Individual, duas UOs e UASGs distintas | Sim | Sim | Sim | Sim |
| Consolidado, duas UOs e uma unidade conjunta | Sim | Sim | Sim | Sim |

Cada modo usará um snapshot Oracle isolado e restaurável. O mantenedor preparará novas versões, homologações e cancelamentos pelo fluxo normal do SGI. O gate não executará DDL nem DML de preparação.

Antes de cada etapa real:

1. fornecerei as consultas Oracle somente leitura;
2. o mantenedor enviará resultados sanitizados;
3. fixaremos as cardinalidades e o hash do checkpoint;
4. a própria chamada do gate repetirá as consultas;
5. qualquer diferença bloqueará a execução antes de escrita PNCP.

Fontes: `RN-PLA-007`, `RN-PUB-001` a `RN-PUB-011`, `RN-AUD-001` a `RN-AUD-005`, `DI-003` a `DI-006`, `DI-008` a `DI-010`, fluxo vigente e Roadmap 8.4.

## Diagnóstico, configuração e interfaces

- Manter inalterada a API de produção `PublicadorPcaScriptcase::executar(...)`.
- Adicionar uma suíte opt-in em `tests/Contract/Sprint84/` e configuração PHPUnit própria.
- Criar o entrypoint da Blank protegida:

```php
executar_fluxos_completos_sprint_84(
    object $db,
    string $login,
    string $serverName,
    string $requestId,
    string $etapa
): array
```

- Etapas aceitas: `PRECHECK`, `PUBLICAR`, `NENHUMA_ACAO`, `RETIFICAR` e `EXCLUIR_PCA`.
- Criar `PCA_SPRINT84_CONFIG_FILE`, externo ao repositório, contendo:
  - consentimentos separados para escritas Oracle e PNCP;
  - servidor, modo, ano e etapa autorizada;
  - exatamente duas UOs;
  - cardinalidades esperadas de versões, fotografia e diferenças;
  - hash SHA-256 do checkpoint Oracle aprovado;
  - estado PNCP esperado;
  - sequenciais autorizados para eventual limpeza emergencial.
- A etapa solicitada deverá coincidir com a etapa autorizada na configuração.
- O PRECHECK será executado novamente dentro da chamada que realizará a operação, antes de invocar `PublicadorPcaScriptcase`.
- Os erros de configuração usarão exceção tipada e relatório detalhado com:
  - código estável;
  - caminho do campo;
  - regra esperada;
  - tipo e valor recebido;
  - classe, mensagem, arquivo, linha e stack da falha;
  - fase exata em que ocorreu.
- Valores de senha, credencial PNCP, JWT e `Authorization` permanecerão mascarados por obrigação da DI-009. Para campo secreto inválido, serão informados caminho, presença, tipo e tamanho, nunca o conteúdo.
- O relatório diagnóstico da Blank protegida exibirá integralmente:
  - configuração não secreta e caminhos resolvidos;
  - CNPJ, ano, UOs, UASGs e sequenciais;
  - ViewModel completo;
  - SQL Oracle, binds, linhas retornadas e contagens;
  - versões, fotografias, estado operacional e auditorias;
  - decisões, inclusões, exclusões, commits e rollbacks;
  - observações PNCP posteriores ao fluxo;
  - erros internos e stack do gate;
  - hash SHA-256 do relatório.
- A guarda exclusiva do contrato Sprint 8.4 capturará requisição e resposta
  HTTP exatas para diagnóstico da Blank protegida, sem alterar o ViewModel nem
  os logs de produção definidos pelas DI-009 e DI-010. Senha, token, JWT e
  `Authorization` continuarão mascarados no relatório final.
- O relatório bruto ficará restrito à Blank protegida. O mantenedor fará a sanitização antes de enviá-lo pelo chat ou registrá-lo como evidência documental.
- A conexão ADOdb será envolvida por uma guarda que registra chamadas e impede `Close()`.

## Consultas manuais e contrato de cardinalidade

Antes de cada etapa, entregarei um arquivo SQL parametrizado somente com `SELECT`, sem `FOR UPDATE`, DML, DDL ou bloco PL/SQL. Os identificadores físicos serão materializados a partir do mesmo perfil usado pelo Composition Root.

| Consulta | Colunas esperadas | Cardinalidade e validação |
| --- | --- | --- |
| Estratégia corrente | `CHAVE_CONFIGURACAO`, `VALOR_CONFIGURACAO`, `DESCRICAO_CONFIGURACAO` | Exatamente 1 linha para `ESTRATEGIA_PUBLICACAO_PLANOS`; no consolidado, exatamente 1 linha adicional para `UASG_CONSOLIDADORA` |
| Representante | `ID_REPRESENTANTE`, `NOME_REPRESENTANTE` | Exatamente 1 representante ativo para o login |
| Lotação do usuário | `ID_LOTACAO`, `NOME_LOTACAO` | Exatamente 1 lotação ativa e vigente |
| Descoberta das UOs | `CHAVE_UO_PLANO`, `IDENTIDADE_UO`, `ID_LOTACAO`, `DESCRICAO_LOTACAO`, `DESCRICAO_UO`, `CODIGO_UASG`, `DESCRICAO_UASG` | Exatamente 2 linhas, uma por UO; no individual, códigos UASG distintos |
| Todas as versões | `ID_PLANO`, `FK_UNIDADE_ORCAMENTARIA`, `ANO_EXERCICIO`, `VERSAO`, `DATA_HOMOLOGACAO`, `DATA_PUBLICACAO`, `ESTRATEGIA_ADOTADA_PUBLICACAO`, `UASG_ADOTADA`, `ESTADO_PUBLICACAO`, `SEQUENCIAL_PUBLICACAO_PNCP` | Quantidade exata fixada no checkpoint; versões positivas e únicas por UO |
| Versões vigentes | As mesmas colunas da consulta anterior | Exatamente 2 linhas: uma versão máxima e homologada por UO |
| Fotografia vigente | `ID_DEMANDA`, `SITUACAO`, `ID_UNIDADE_REQUISITANTE`, `DATA_DESEJADA`, `CATEGORIA`, `DESCRICAO_DEMANDA`, `UNIDADE_MEDIDA`, `QUANTIDADE`, `VALOR_TOTAL`, `VALOR_ORCAMENTO_EXERCICIO`, `CATALOGO`, `CLASSIFICACAO_CATALOGO`, `CODIGO_ITEM`, `GRUPO`, `DESCRICAO_GRUPO`, `ORIGEM`, `FK_UNIDADE_ORCAMENTARIA` | Cardinalidade exata por UO; `CATALOGO = 2`, classificação `1 ou 2`, somente situações`2`,`3` e `5`; IDs globais únicos |
| Ciclo completo das Demandas | `ID_DEMANDA`, `FK_PLANO`, `FK_UNIDADE_ORCAMENTARIA`, `VERSAO_ORIGEM`, `SITUACAO` | Quantidade exata fixada; permite conferir canceladas e origem das Demandas que não integram a fotografia |
| Projeção operacional | `FK_UNIDADE_ORCAMENTARIA`, `VERSAO`, `ESTRATEGIA_ADOTADA_PUBLICACAO`, `UASG_ADOTADA`, `ESTADO_PUBLICACAO`, `SEQUENCIAL_PUBLICACAO_PNCP` | Uma linha por versão; nenhum par parcialmente preenchido |
| Auditoria | `DATA_REGISTRO`, `PROCEDIMENTO`, `MENSAGEM`, `FK_REPRESENTANTE`, `FK_LOTACAO` | Baseline exato, filtrado por ano, autoria e mensagens do fluxo |
| Controles de concorrência | `ESCOPO`, `ANO_EXECUCAO`, `IDENTIFICADOR_ALVO` | Informativa: zero ou uma linha por chave esperada; duplicidade bloqueia |

As linhas serão ordenadas deterministicamente e serializadas de forma canônica. O gate calculará `oracle_checkpoint_sha256`; o hash deverá ser idêntico ao aprovado após a conferência manual.

## Estados esperados por etapa

### Individual

- `PUBLICAR`, antes:
  - duas UOs com UASGs distintas;
  - fotografia não vazia em ambas;
  - versões vigentes homologadas e não confirmadas;
  - bloco operacional inicial integralmente nulo;
  - ausência de PCA/candidato nas duas UASGs.
- `PUBLICAR`, depois:
  - dois resultados `CONCLUIDO/PUBLICAR`;
  - dois sequenciais positivos distintos;
  - todas as versões no estado `PUBLICADO`;
  - versões vigentes confirmadas;
  - duas auditorias de procedimento `5`.
- `NENHUMA_ACAO`:
  - mesmo conteúdo local e externo;
  - dois resultados `SEM_ALTERACAO/NENHUMA_ACAO`;
  - sequenciais preservados;
  - zero nova auditoria e nenhuma escrita PNCP.
- `RETIFICAR`, antes:
  - nova versão homologada e não confirmada em cada UO;
  - nova linha com bloco operacional integralmente nulo;
  - versões anteriores coerentes e publicadas;
  - cada UO com pelo menos uma inclusão e uma exclusão por pertencimento.
- `RETIFICAR`, depois:
  - dois resultados `CONCLUIDO/RETIFICAR`;
  - exclusões antes das inclusões em cada unidade;
  - mesmos sequenciais;
  - novas versões confirmadas;
  - duas auditorias de procedimento `8`.
- `EXCLUIR_PCA`, antes:
  - nova versão homologada, vazia e não confirmada em cada UO;
  - bloco operacional novo integralmente nulo;
  - PCA externo ainda ativo.
- `EXCLUIR_PCA`, depois:
  - dois resultados `CONCLUIDO/EXCLUIR_PCA`;
  - ausência externa definitiva nas duas UASGs;
  - estado `EXCLUIDO`, preservando os sequenciais históricos;
  - versões vazias confirmadas;
  - duas auditorias de procedimento `9`.

Auditoria acumulada esperada sobre o baseline individual: `+2`, `+2`, `+4`, `+6`.

### Consolidado

- As mesmas transições serão repetidas com duas UOs e uma única UASG consolidadora.
- `PUBLICAR` produzirá um resultado, um sequencial propagado, duas confirmações e uma auditoria `5`.
- `NENHUMA_ACAO` produzirá um resultado e incremento zero de auditoria.
- `RETIFICAR` exigirá pelo menos uma inclusão e uma exclusão na união completa, preservará o sequencial, confirmará as duas versões vigentes e produzirá uma auditoria `8`.
- `EXCLUIR_PCA` somente será permitido quando a união das duas fotografias estiver vazia; produzirá uma auditoria `9`.

Auditoria acumulada esperada sobre o baseline consolidado: `+1`, `+1`, `+2`, `+3`.

## Correção de convergência já identificada

A preparação normal de uma nova versão pode gerar uma linha com:

```text
ESTRATEGIA_ADOTADA_PUBLICACAO = null
UASG_ADOTADA                  = null
ESTADO_PUBLICACAO             = null
SEQUENCIAL_PUBLICACAO_PNCP    = null
```

Isso é permitido pela DI-008 até a próxima propagação, mas o adapter atual rejeita a mistura entre versões antigas preenchidas e a nova versão integralmente nula.

A Sprint 8.4 corrigirá por TDD esse comportamento:

- aceitar linhas integralmente nulas como pendentes de propagação;
- continuar rejeitando pares parcialmente preenchidos;
- continuar rejeitando modos, UASGs, estados ou sequenciais divergentes;
- conferir que a estratégia já adotada coincide com a corrente;
- preencher somente as linhas pendentes;
- reler e exigir uniformidade de estratégia/UASG antes da autenticação;
- completar estado/sequencial explicitamente na finalização transacional,
  após comprovar a convergência externa, e reler cada linha do escopo;
- preservar as confirmações e versões anteriores.

## Microciclos e pausas obrigatórias

1. **Contrato, configuração detalhada e consultas**
   - Registrar o plano aprovado.
   - Implementar configuração externa, exceções detalhadas, exemplo e preflight sem I/O.
   - Implementar o montador das consultas `SELECT`, snapshot canônico e hash.
   - Testar que nenhuma consulta manual contém escrita ou lock.
   - Pausar. A aprovação do plano autoriza somente este microciclo.

2. **Propagação de novas versões**
   - Criar o Red para versões antigas preenchidas e nova versão integralmente nula.
   - Corrigir estratégia e projeção operacional pelo comportamento descrito acima.
   - Executar regressão Oracle e Application.
   - Pausar.

3. **Matriz controlada com adapters concretos**
   - Cobrir os oito pares modo/decisão com ADOdb e cURL roteirizados.
   - Individual terá duas unidades; consolidado terá uma unidade formada por duas UOs.
   - Validar ordem externa, transação, confirmação, estado, auditoria e resultados.
   - Pausar.

4. **Runner real e diagnóstico completo**
   - Implementar entrypoint, guarda ADOdb, PRECHECK bloqueante, relatório detalhado e verificações pós-fluxo.
   - Implementar limpeza emergencial separada e recusada por padrão.
   - Adicionar PHPUnit e comandos Composer da 8.4.
   - Pausar sem Oracle ou PNCP reais.

5. **Checkpoint e PUBLICAR individual**
   - Entregar as consultas.
   - Analisar o retorno sanitizado e fixar cardinalidades/hash.
   - Executar PRECHECK novamente e, se idêntico, o fluxo público.
   - Pausar com relatório completo local e evidência sanitizada.

6. **Checkpoint e NENHUMA_ACAO individual**
   - Repetir consultas e conferência.
   - Exigir estado idêntico ao término da publicação.
   - Executar e pausar.

7. **Checkpoint e RETIFICAR individual**
   - Aguardar as novas versões preparadas no SGI.
   - Entregar e analisar novamente todas as consultas.
   - Executar somente após PRECHECK idêntico e pausar.

8. **Checkpoint e EXCLUIR_PCA individual**
   - Aguardar versões vazias preparadas no SGI.
   - Repetir conferência e PRECHECK.
   - Executar e pausar somente após ausência externa definitiva.

9. **Ativação e checkpoint consolidado**
   - Restaurar/ativar o snapshot consolidado.
   - Entregar consultas e validar isolamento, duas UOs e estratégia consolidada.
   - Executar somente o PRECHECK de leitura e pausar.

10. **PUBLICAR consolidado**
    - Repetir o PRECHECK dentro do gate.
    - Executar e pausar.

11. **NENHUMA_ACAO consolidado**
    - Entregar novamente as consultas, analisar e executar após igualdade exata.
    - Pausar.

12. **RETIFICAR consolidado**
    - Aguardar versões SGI com inclusão e exclusão na união.
    - Entregar consultas, conferir, executar e pausar.

13. **EXCLUIR_PCA consolidado**
    - Aguardar fotografias vazias nas duas UOs.
    - Entregar consultas, conferir, executar e pausar após ausência definitiva.

14. **Regressão e encerramento**
    - Executar Sprint 8.4, Integration, Composition, Presentation, Application, Infrastructure, contratos anteriores e suíte padrão.
    - Validar lint, Composer, PSR-4, dependências, ausência de segredos versionados e `git diff --check`.
    - Atualizar plano, guia de probes, catálogo, estado operacional e Roadmap apenas com evidências obtidas.
    - Marcar a 8.4 concluída e a 8.5 como próxima somente após a matriz 4×2 completa.

## Premissas e limites

- Consultas e relatórios brutos serão exibidos somente na Blank protegida; apenas versões sanitizadas serão compartilhadas ou versionadas.
- Senha, credencial, JWT e `Authorization` nunca serão exibidos.
- Snapshots, restaurações e transições do SGI são responsabilidade do mantenedor.
- O consolidado não poderá conter UOs além das duas autorizadas.
- Falha de PRECHECK não executará autenticação nem escrita PNCP.
- Falha depois de possível efeito externo interromperá a sprint; limpeza exigirá sequencial configurado e nova autorização.
- Não haverá produção, DDL, DML de preparação, implantação, `sc_log_add`, smoke test ou commit Git.
- A alteração local preexistente em `roadmap.md` será preservada sem normalização ampla de finais de linha.

## Evidências do Microciclo 1

- Red inicial: 13 testes, com 12 erros por classes ainda inexistentes e 1 falha
  de contrato de segurança das consultas.
- Green final: 15 testes e 136 asserções para configuração externa, perfil de
  homologação, erros detalhados, consultas somente leitura, snapshot canônico e
  hash SHA-256.
- Foram materializadas 11 consultas Oracle parametrizadas, cada uma com SQL,
  binds, colunas e cardinalidade esperada. O contrato rejeita comandos de
  escrita, DDL e `FOR UPDATE`.
- O preflight local sem configuração foi recusado como esperado, com código,
  fase, campo, regra esperada, tipo, valor, arquivo, linha e stack, antes de
  qualquer acesso Oracle ou PNCP.
- O diagnóstico mantém CNPJ e login visíveis; somente senha, token, JWT,
  `Authorization` e credenciais secretas são protegidos.
- Validações executadas em PHP 8.4.7: lint dos 11 arquivos PHP, autoload PSR-4
  estrito com 1.801 classes, `composer validate` e suíte padrão com 882 testes e
  3.846 asserções. O Composer informou apenas a advertência preexistente de
  ausência de licença no `composer.json`.
- Nenhum Oracle ou PNCP real foi acessado, nenhum commit foi criado e a
  alteração local preexistente em `roadmap.md` permaneceu intacta.

## Evidências do Microciclo 2

- Red inicial: 44 testes focados, com 9 falhas que demonstraram a rejeição da
  nova versão pendente e a ausência de propagação pela Application.
- A consulta da estratégia adotada passou a aceitar versões antigas coerentes
  junto de linhas novas cujo par estratégia/UASG esteja integralmente nulo.
  Pares parcialmente preenchidos, modos divergentes e UASGs divergentes
  continuam inválidos.
- A Application confere a estratégia corrente contra a já adotada e solicita
  a adoção idempotente também quando elas coincidem. O adapter atualiza somente
  linhas com estratégia e UASG nulas, tanto no individual quanto no
  consolidado.
- A releitura posterior é estrita: qualquer linha ainda pendente impede a
  confirmação da transação e, consequentemente, a autenticação ou acesso ao
  PNCP.
- O `UPDATE` de propagação não altera versão nem `DATA_PUBLICACAO`; confirmações
  anteriores permanecem preservadas.
- Green final em PHP 8.4.7: Oracle + Application com 372 testes e 1.688
  asserções; Integration + Composition + contrato 8.4 com 31/426; suíte padrão
  com 887/3.874; lint e PSR-4 estrito aprovados.
- Nenhum Oracle ou PNCP real foi acessado e nenhum commit foi criado.

## Evidências do Microciclo 3

- Red inicial: 8 erros, um para cada par modo/decisão, pela ausência do cenário
  controlado que exercitaria o núcleo completo.
- A matriz cobre `PUBLICAR`, `NENHUMA_ACAO`, `RETIFICAR` e `EXCLUIR_PCA` nos
  modos individual e consolidado. O individual usa duas UOs com UASGs e
  sequenciais distintos; o consolidado une as mesmas duas UOs em uma unidade
  externa.
- O cenário monta o núcleo real do Composition Root e seus nove adapters
  concretos. Somente as fronteiras ADOdb e cURL são executores roteirizados e
  determinísticos, sem acesso externo.
- As verificações conferem decisão, situação, sequencial exato, inclusões e
  exclusões, ordem das escritas PNCP, adoção de estratégia, versões confirmadas,
  procedimento de auditoria, estado operacional e binds persistidos, inícios,
  commits, ausência de rollback e consumo integral das filas Oracle/PNCP.
- Green final em PHP 8.4.7: matriz com 8 testes e 173 asserções; Composition +
  Sprint 8.4 com 26/379; suíte padrão com 895/4.047.
- Nenhum arquivo externo de configuração foi lido, nenhum Oracle ou PNCP real
  foi acessado e nenhum commit foi criado.

## Evidências do Microciclo 4

- Red inicial: 7 erros pela ausência da guarda ADOdb, executor do checkpoint e
  runner do gate real.
- O entrypoint protegido `executar_fluxos_completos_sprint_84(...)` confere
  configuração, servidor, UUID v4 e etapa; executa as 11 consultas Oracle e
  exige igualdade do SHA-256 antes de alcançar `PublicadorPcaScriptcase`.
- O relatório integral contém configuração não secreta, caminhos informados e
  resolvidos, perfil com segredos protegidos, SQL, binds, linhas,
  cardinalidades, snapshots, hashes, ViewModel, observações PNCP posteriores,
  itens, sequenciais e todas as chamadas ADOdb, incluindo commits, rollbacks e
  falhas com stack.
- Senha, token, JWT, `Authorization` e credenciais permanecem protegidos. Erros
  de campos secretos informam presença, tipo e tamanho sem mostrar conteúdo;
  erros de configuração e do gate informam código, fase, mensagem, detalhes,
  arquivo, linha, causa e stack.
- A guarda delega a conexão emprestada e registra seu conteúdo integral, mas
  bloqueia qualquer `Close()` antes que alcance o objeto fornecido pelo
  Scriptcase.
- O pós-check PNCP autentica por uma composição separada de leitura, observa
  cada sequencial retornado, mostra UO/UASG/itens e exige identidade, estado e
  cardinalidade iguais ao resultado e à fotografia autorizada.
- A limpeza emergencial é um entrypoint separado, recusa a lista vazia por
  padrão e só exclui um sequencial exato após localizá-lo em uma única unidade.
  Alvo já ausente exige observação de ausência definitiva em todas as unidades;
  exclusão executada também exige ausência definitiva posterior.
- Foram adicionados `phpunit.sprint84.contract.xml`,
  `composer contract:sprint84:tests` e
  `composer contract:sprint84:preflight`. A suíte opt-in exclui o grupo real
  por padrão.
- Green local em PHP 8.4.7: Sprint 8.4 com 37 testes e 383 asserções; suíte
  padrão com 895/4.047; lint dos 22 arquivos contratuais e PSR-4 estrito com
  1.813 classes aprovados.
- Nenhuma configuração externa foi carregada, nenhum Oracle ou PNCP real foi
  acessado e nenhum commit foi criado.

## Evidências do subciclo corretivo 8.4.5-A

- A inspeção manual confirmou que `PAC_DEMANDA.FK_CATEGORIA` referencia as
  oito categorias vigentes do PNCP e que `CLASSIFICACAO_CATALOGO` representa
  exclusivamente `1 = Material` ou `2 = Serviço`.
- O mantenedor estabeleceu a regra global temporária do SGI `catalogo = 2
  (Outros)` enquanto não existir coluna física específica.
- O Domain passou a representar e validar separadamente categoria `1..8`,
  catálogo `1|2` e classificação `1|2`. O adapter Oracle aplica o catálogo
  global e lê `CLASSIFICACAO_CATALOGO` sem derivá-la da categoria.
- O perfil físico passou a exigir `colunaClassificacaoCatalogo`; o serializer
  envia os três campos independentes. A consulta do checkpoint também expõe
  `CATALOGO = 2` e `CLASSIFICACAO_CATALOGO` separadamente.
- Red observado: 30 testes, 72 asserções e 4 erros correspondentes às quatro
  incompatibilidades. Green em PHP 8.4.7: suíte padrão com 898 testes e 4.061
  asserções; contratos 5.9, 8.3 e 8.4 com 54/149, 14/62 e 40/424.
- Nenhum Oracle ou PNCP real foi acessado e nenhum commit foi criado.

## Evidências do subciclo corretivo 8.4.5-C

- O PRECHECK real alcançou as consultas Oracle, mas foi bloqueado antes do
  PNCP porque textos devolvidos pelo ADOdb não formavam UTF-8 válido.
- A consulta somente leitura de `NLS_DATABASE_PARAMETERS` confirmou
  `NLS_CHARACTERSET = WE8MSWIN1252` e
  `NLS_NCHAR_CHARACTERSET = AL16UTF16`; as colunas `VARCHAR2` consumidas usam
  o primeiro charset.
- O perfil passou a exigir `oracle.charset_adodb`, com o valor aprovado
  `Windows-1252`. O Composition Root encaminha esse valor à conexão ADOdb
  emprestada sem depender de configuração global do servidor Scriptcase.
- Leituras são convertidas para UTF-8 antes dos adapters, checkpoint, hash e
  PNCP. Binds textuais são convertidos no sentido inverso e uma verificação de
  ida e volta bloqueia caracteres não representáveis sem substituição.
- O checkpoint e o diagnóstico ADOdb usam a mesma conversão. A serialização
  diagnóstica preserva a causa mesmo diante de byte inválido residual, sem
  permitir que essa tolerância altere o snapshot operacional.
- A execução seguinte revelou `ADODB_FETCH_BOTH`: cada coluna chegou por
  índice numérico e alias. A fronteira remove agora somente os índices
  duplicados das linhas operacionais, preservando a representação crua no
  diagnóstico e sem alterar o modo global da conexão Scriptcase.
- A primeira chegada ao PNCP revelou duas lacunas diagnósticas: o bind textual
  da auditoria ainda não atravessava a conversão de escrita no executor do
  checkpoint, e uma falha na busca descartava o checkpoint concluído e o
  detalhe seguro retornado pelo adapter. O executor converte agora todos os
  binds, e o contexto de falha preserva checkpoint, hash, falha segura e a
  troca HTTP integral da guarda exclusiva da Sprint 8.4.
- Red demonstrado por três falhas de conexão e um erro de hash: acentos eram
  inválidos, a escrita não era convertida, caractere não representável passava
  e o snapshot recusava Windows-1252.
- Green em PHP 8.4.7: suíte padrão com 903 testes e 4.073 asserções; Sprint 8.4
  com 47/455; contratos 5.9, 8.2 e 8.3 com 54/149, 12/43 e 14/62. Lint dos
  arquivos afetados e autoload PSR-4 estrito com 1.815 classes foram aprovados.
- Nenhuma escrita Oracle ou PNCP foi executada e nenhum commit foi criado.

## Evidências do subciclo corretivo 8.4.5-D

- A autenticação PNCP real respondeu `200`, mas a primeira página da listagem
  de PCAs de 2027 respondeu `404` com um `path` interno iniciado por
  `/pncp-api`. A URL externa efetivamente chamada permaneceu na base canônica
  `/api/pncp`.
- Três consultas manuais somente leitura confirmaram que o CNPJ está validado,
  que as UASGs `204` e `207` existem exatamente uma vez, que a listagem de
  2026 responde `200` com três PCAs e que o consolidado anual de 2027 responde
  `404` por não possuir PCA.
- O leitor passou a tratar o `404` da primeira página somente como sinal
  provisório. Em homologação e produção, a ausência exige confirmação
  independente do cadastro exato da UASG/CNPJ e `404` no consolidado anual,
  sem interpretar mensagens textuais ou exigir o campo não contratual
  `statusAtivo`.
- A mesma comprovação atende à busca inicial e à observação posterior à
  exclusão do último PCA do ano. Divergência cadastral conflita, evidência
  incompleta permanece inconclusiva e falha de transporte permanece sistêmica.
- Red demonstrado por 10 falhas focadas. Green em PHP 8.4.7: contratos de
  paginação e ausência definitiva com 39/107; Infrastructure PNCP com
  356/1.141; suíte padrão com 912/4.104; contratos 5.9, 8.3 e 8.4 com 54/149,
  14/62 e 47/455; autoload PSR-4 estrito com 1.815 classes.
- Nenhuma escrita Oracle ou PNCP foi executada e nenhum commit foi criado.

## Evidências do subciclo 8.4.5-E

- A conferência manual repetiu as 11 consultas somente leitura e confirmou o
  cenário individual inicial: planos `61` e `62`, UOs `1` e `3`, UASGs `204`
  e `207`, versões `1` homologadas, fotografias com `2` e `3` Demandas,
  projeção operacional nula, auditoria vazia e nenhuma âncora previamente
  provisionada.
- O gate interno repetiu Oracle e PNCP imediatamente antes das escritas. O
  snapshot Oracle permaneceu no SHA-256
  `cf1e96def5604bbd1680a4e8e3359c01dbf73cd3ed5cb8c596dfd6ed6ffb3709`,
  e a ausência externa foi comprovada para ambas as UASGs.
- A fachada concluiu os dois resultados `PUBLICAR`: UO `1`/UASG `204` recebeu
  o sequencial `1` e publicou as Demandas `800` e `801`; UO `3`/UASG `207`
  recebeu o sequencial `2` e publicou as Demandas `802`, `803` e `804`.
- O pós-check PNCP encontrou ambos os PCAs ativos, com os mesmos sequenciais e
  os cinco identificadores integrais. O pós-check Oracle confirmou as duas
  versões com `DATA_PUBLICACAO`, estratégia `INDIVIDUAL`, respectivas UASGs,
  estado `PUBLICADO` e sequenciais correspondentes.
- Foram registradas exatamente duas auditorias do procedimento `5`, atribuídas
  ao representante `709` e à lotação `1`. A conexão emprestada registrou seis
  inícios e seis commits, sem rollback e sem fechamento pelo backend.
- As três linhas observadas em `PAC_CONTROLES_CONCORRENCIA` são as âncoras
  permanentes previstas pela DI-008 — estratégia anual e unidades `204` e
  `207`. Elas não representam locks ativos nem resíduo; os locks pertenciam às
  transações já confirmadas.
- Resultado final `FLUXO_CONFIRMADO`, SHA-256 do relatório
  `13c164e4d2bf20948bc4afbbd2631a2320b31c0c2309394ccdd98c582f40357f`.
  Nenhuma limpeza foi necessária e a execução não deve ser repetida.
- Limitação diagnóstica observada: a guarda HTTP do runner registrou as
  autenticações e leituras dos verificadores, mas não os dois `POST /pca`
  executados pelo transporte próprio da fachada. Sequenciais, ViewModel e
  releitura integral comprovam as publicações, porém o compartilhamento do
  diagnóstico de escrita deverá ser corrigido antes de um próximo cenário que
  altere o PNCP.

## Evidências do subciclo corretivo 8.4.5-F

- O Composition Root passou a aceitar opcionalmente um `TransporteHttpPncp`
  já construído; sem injeção, continua criando o transporte cURL nativo e
  preserva o comportamento de produção.
- A API `PublicadorPcaScriptcase::executar(...)` e a ausência de construtor
  público foram preservadas. A execução comum foi extraída para um colaborador
  interno da Composition, que permite ao runner usar o mesmo fluxo com o
  Composition Root instrumentado.
- O runner agora cria uma única `Sprint84PncpTransportGuard` e a compartilha
  entre precheck, fachada pública, pós-check e limpeza. O histórico resultante
  preserva a ordem cronológica, os payloads e respostas de negócio; senha e
  autorização continuam protegidas no relatório final.
- Red observado: dois erros por parâmetros de injeção ainda inexistentes; a
  regressão intermediária também protegeu a API pública ao rejeitar a primeira
  tentativa de adicionar construtor ao publicador.
- Green focado: runner/guarda/verificador com 19 testes e 92 asserções;
  Composition, fachada e matriz com 33/655. Green amplo: Sprint 8.4 com
  50/470, Infrastructure PNCP com 356/1.141, Composition + Integration com
  28/480 e suíte padrão com 914/4.114.
- Nenhum Oracle ou PNCP real foi acessado, nenhuma configuração externa foi
  alterada e nenhuma publicação foi repetida.

## Preparação do microciclo 8.4.6

- O runner passou a exigir dois resultados `SEM_ALTERACAO/NENHUMA_ACAO`,
  igualdade exata dos snapshots Oracle anterior e posterior e igualdade das
  observações PNCP por UASG, sequencial e conjunto de Demandas.
- O histórico HTTP compartilhado é inspecionado pelo gate: somente o `POST`
  de autenticação é admitido; qualquer outro `POST` ou qualquer `DELETE`
  bloqueia a confirmação de `NENHUMA_ACAO`.
- O roteiro `nenhuma_acao_individual_precheck.sql` reutiliza exatamente os 11
  `SELECT`s auditados e fixa as novas expectativas: duas auditorias do
  procedimento `5` e três âncoras permanentes de concorrência.
- A conferência manual revelou que o `LIKE` da auditoria dependia da caixa do
  literal em relação aos dados Oracle. O filtro gerado e o roteiro manual
  passaram a usar `UPPER(MENSAGEM) LIKE UPPER(...)`, preservando as duas linhas
  esperadas e o conteúdo usado no hash do checkpoint.
- Red observado para escrita PNCP indevida. Os testes adicionais comprovam
  rejeição de situação unitária incorreta, mudança de itens PNCP e mudança do
  snapshot Oracle.
- Green em PHP 8.4.7: Sprint 8.4 com 56 testes e 511 asserções;
  Infrastructure PNCP com 356/1.141; Composition + Integration com 28/480 e
  suíte padrão com 914/4.114.
- Nenhum Oracle ou PNCP real foi acessado e nenhuma configuração externa foi
  alterada. A execução aguarda sincronização remota, conferência manual e hash
  idêntico ao checkpoint aprovado.

## Evidência real do microciclo 8.4.6

- A execução única `NENHUMA_ACAO`, correlacionada pelo request ID
  `6bb6b396-fb7f-467c-ac34-e53ad9814961`, terminou com
  `FLUXO_CONFIRMADO` em homologação.
- As UOs 1/UASG 204 e 3/UASG 207 retornaram, respectivamente, os sequenciais
  1 e 2, ambas como `SEM_ALTERACAO/NENHUMA_ACAO`, sem inclusões nem exclusões.
- As observações PNCP anteriores e posteriores permaneceram `ENCONTRADO` e
  ativas, com os mesmos sequenciais e os conjuntos 800/801 e 802/803/804.
- Os snapshots Oracle anterior e posterior produziram o mesmo SHA-256
  `f9cfb8abb04aed362b9624637cbe0e9eab7cfb4bd8e68c1cec0126184b06ec5f`.
  As duas auditorias do procedimento `5` e as três âncoras permanentes de
  concorrência permaneceram inalteradas.
- O diagnóstico HTTP registrou 15 chamadas: três autenticações `POST` e doze
  leituras `GET`, todas com HTTP 200. Não houve `POST` de PCA ou item nem
  qualquer `DELETE`.
- O diagnóstico ADOdb registrou seis transações iniciadas e confirmadas, sem
  rollback e sem fechamento da conexão emprestada. Os únicos três `Execute`
  foram tentativas idempotentes de assegurar as âncoras de concorrência já
  existentes, todas com zero linhas inseridas; não houve mutação persistida,
  confirmação adicional ou nova auditoria.
- O relatório integral produziu o SHA-256
  `bca81d982dfa8224040ee431dc0567d6989ec67095d399de713349a9bd4d5ea8`.

## Preparação e PRECHECK do microciclo 8.4.7

- O mantenedor preparou pelo SGI os cancelamentos 801/802 e as novas Demandas
  807/806, respectivamente nas UOs 1/3. As versões vigentes de 2027 são os
  planos 91/90, ambos versão 3, homologados e ainda não confirmados. A Demanda
  805 pertence ao plano 89 de 2026 e não participa deste fluxo.
- Uma incompatibilidade concreta de configuração impedia `PRECHECK` com PCA
  existente: o contrato exigia sempre `AUSENTE`. A correção pontual autorizada
  permite `AUSENTE` ou `ENCONTRADO` no PRECHECK, preservando `AUSENTE` para
  PUBLICAR e `ENCONTRADO` para as demais etapas reais. A comparação com a
  observação PNCP continua obrigatória.
- Red: o novo teste recusou `ENCONTRADO` no PRECHECK. Green em PHP 8.4.7:
  configuração 11/58, Sprint 8.4 59/527, suíte padrão 914/4.114, lint dos dois
  arquivos e PSR-4 estrito com 1.816 classes.
- A execução de leitura, request ID
  `117be27f-3860-4601-9028-5b9103d7acae`, retornou `PRECHECK_CONFIRMADO`.
  As 11 consultas coincidem integralmente com a fotografia manual corrigida,
  desconsiderando apenas a representação de números como texto pelo ADOdb e
  a ordem de exportação das linhas.
- Cardinalidades observadas: estratégia 1, representante 1, lotação 1, UOs 2,
  todas as versões 6, versões vigentes 2, fotografia 5, ciclo de Demandas 7,
  projeção operacional 6, auditoria 2 e controles permanentes 3.
- PNCP: UASG 204/sequencial 1 com 800/801 e UASG 207/sequencial 2 com
  802/803/804. Oracle desejado: 800/807 e 803/804/806. As diferenças são uma
  inclusão e uma exclusão por UO, preservando os sequenciais existentes.
- O diagnóstico registrou 11 `GetAll` Oracle, sem DML ou transações, e cinco
  chamadas HTTP 200: uma autenticação e quatro leituras. Não houve escrita
  PNCP, limpeza nem invocação do fluxo público.
- A listagem anual também contém UASG 930281/sequencial 4, fora das duas
  unidades autorizadas. Este PCA não é alvo de retificação nem de limpeza.
- SHA-256 Oracle do checkpoint:
  `6b72e8337f532142bd6767c19ef422021dab4c5b6e071bf010622f861747a7f0`.
  SHA-256 do relatório:
  `9cdd5c9efec35fe6d16eddf3dc0c7e3804a4a9b69a39b86d81c4925520f65206`.
  A etapa permanece PRECHECK; este registro não autoriza armar RETIFICAR.

## Evidência real do microciclo 8.4.7

- Após aprovação explícita do checkpoint, a execução única RETIFICAR, request
  ID `2dff65c1-b6b0-482c-865c-f5b78713044c`, terminou com `FLUXO_CONFIRMADO`.
  O PRECHECK interno repetiu o SHA-256 Oracle aprovado
  `6b72e8337f532142bd6767c19ef422021dab4c5b6e071bf010622f861747a7f0`.
- UO 1/UASG 204: DELETE de 801 com HTTP 200, seguido de POST de 807 com HTTP
  201, ambos no sequencial 1. UO 3/UASG 207: DELETE de 802 com HTTP 200,
  seguido de POST de 806 com HTTP 201, ambos no sequencial 2.
- Ambos os resultados são `CONCLUIDO/RETIFICAR`, com uma inclusão e uma
  exclusão. As releituras integrais PNCP encontram os PCAs ativos, com os
  conjuntos exatos 800/807 e 803/804/806, preservando os sequenciais 1/2.
- O diagnóstico compartilhado capturou as quatro escritas reais, seus
  payloads e respostas, comprovando em execução real a observabilidade
  corrigida em 8.4.5-F. Não houve POST de PCA, exclusão integral ou comando de
  escrita para o sequencial 4 da UASG 930281.
- Os planos vigentes 91/90 receberam DATA_PUBLICACAO em
  2026-09-15 18:03:52 e 18:03:53. As confirmações anteriores de 61/62 foram
  preservadas; as versões intermediárias 87/88 continuam não confirmadas,
  pois a confirmação individual incide na versão vigente pendente (DI-005).
- Estratégia/UASG foram propagadas às quatro linhas pendentes. Estado e
  sequencial continuam PUBLICADO/1 e PUBLICADO/2 nas versões 1; os pares
  estado/sequencial das versões 2/3 permanecem integralmente nulos. Não houve
  UPDATE desses campos na retificação, que preservou o vínculo operacional
  existente. Confirmação versionada e estado operacional permanecem distintos.
  Interpretação corretiva: a preservação do estado lógico não justifica deixar
  as novas linhas pendentes. Essa evidência comprova a retificação PNCP, mas
  também identifica a persistência incompleta tratada em 8.4.7-C.
- Há exatamente quatro auditorias: as duas anteriores de procedimento 5 e
  duas novas de procedimento 8, ambas com uma inclusão e uma exclusão,
  representante 709 e lotação 1.
- Estratégia corrente, contexto, UOs, fotografia, ciclo de Demandas e as três
  âncoras permanentes de concorrência permaneceram iguais entre precheck e
  pós-check Oracle. Não houve mutação de preparação pelo gate.
- ADOdb: seis BeginTrans e seis CommitTrans, sem RollbackTrans nem Close.
  Os nove Execute compreendem três garantias idempotentes de âncoras, dois
  UPDATE de estratégia/UASG, duas confirmações e dois INSERT de auditoria.
- SHA-256 Oracle posterior:
  `97e63e36eba262aef78fe94d056419d90e5ade4111667393ec06a7ef535a9f90`.
  SHA-256 do relatório:
  `e5ac295a65e4d5314b7fc4423f9617780c16072020098f6e6fc27ed5cb722251`.
  Nenhuma limpeza foi necessária. Pausa obrigatória antes de EXCLUIR_PCA.

## Corretivo aprovado 8.4.7-C — Projeção operacional após convergência

### C1 — Implementação local

Implementação local concluída em 15/09/2026. C2/C3 continuam não autorizadas.

- A finalização solicita o registro explícito também quando o estado lógico
  não muda. O adapter consulta e valida o escopo antes de gravar, completa
  somente pares integralmente nulos nesse caso e evita UPDATE se a projeção
  já estiver completa. Nas transições legítimas, projeta todas as versões.
- A releitura exige cada par correspondente ao estado final e a mesma
  cardinalidade. Falhas impedem sucesso e seguem o rollback da unidade, sem
  commit autônomo nem Close. Consultas permanecem somente leitura.
- O gate exige projeção uniforme após os fluxos concluídos. Em NENHUMA_ACAO,
  compara o pós-check com o snapshot anterior transformado exclusivamente
  pela propagação dos pares nulos nas três consultas que os representam;
  confirmações, auditoria e demais dados devem permanecer iguais.
- As chaves externas não mudam: PRECHECK permite ambas as escritas falsas;
  NENHUMA_ACAO permite escrita somente Oracle. A guarda compartilhada recusa
  mutações PNCP antes do despacho nessas duas etapas, permitindo login/GET.
- Não houve alteração de Ports, API pública, modelo de domínio, dependências
  ou roadmap. Relatórios históricos não foram reescritos.
- Red observado: três testes do adapter e três da finalização reproduziram
  ausência de propagação, UPDATE sem filtro ou releitura incompleta; os testes
  novos de configuração/guarda e gate também recusaram o comportamento antigo.
- Green em PHP 8.4.7: suíte padrão 921 testes/4.214 asserções; Application
  239/996; Oracle 142/774; Infrastructure PNCP 356/1.141; Integration 15/308;
  matriz 8.4 10/205; contratos locais 5.9 54/149, 8.3 14/62 e 8.4 67/580.
- Lint dos 17 arquivos PHP afetados e PSR-4 estrito com 1.817 classes
  aprovados. Inspeção do diff/documentação e referências novas executada;
  diff --check aprovado considerando CRLF como fim de linha Windows.
  A execução usou PHP 8.4.7, não um runtime 8.1. Nenhum gate live, acesso
  Oracle/PNCP real, sincronização, escrita externa ou commit foi executado.

### C2 — Conferência manual e PRECHECK real aprovados

- A conferência manual foi autorizada e aprovada em 16/09/2026. Os 11 SELECTs
  completos foram materializados em
  `tests/Contract/Sprint84/checkpoints/corretivo_projecao_individual_precheck.sql`,
  com colunas e
  cardinalidades exatas. Esperar estratégia 1, representante 1, lotação 1,
  UOs 2, versões 6, vigentes 2, fotografia 5, ciclo 7, projeção 6,
  auditoria 4 e âncoras permanentes 3. A fotografia recebida apresentou todas
  essas cardinalidades e conteúdos sem divergência.
- Esperar planos 61/87/91 e 62/88/90, vigentes 91/90 confirmados;
  fotografia 800/807 e 803/804/806. A demanda 805 de 2026 fica fora do escopo.
- Conferir manualmente os resultados, depois executar somente PRECHECK com
  escritas falsas. Exigir releitura Oracle exata e PNCP ativo nas UASGs
  204/207, sequenciais 1/2, com os mesmos conjuntos de itens.
- A reprodução da serialização canônica com os tipos escalares retornados pelo
  ADOdb confirmou o SHA-256
  `97e63e36eba262aef78fe94d056419d90e5ade4111667393ec06a7ef535a9f90`;
  o PRECHECK remoto ainda deverá recalculá-lo e compará-lo antes de qualquer
  avanço. A exportação manual representa colunas numéricas como números,
  enquanto o ADOdb as materializa como texto; essa diferença de representação
  não constitui mudança do estado Oracle. Nenhuma sincronização ou execução
  remota foi realizada nesta conferência. Pausa obrigatória após análise do
  relatório PRECHECK.
- O PRECHECK real único, request ID
  `b317de23-6aae-4045-9a68-b544d9e09b18`, terminou em 16/09/2026 com
  `PRECHECK_CONFIRMADO`. O gate repetiu as 11 leituras Oracle e confirmou o
  SHA-256 aprovado sem iniciar transação ou executar DML.
- O PNCP confirmou UASG 204/sequencial 1 com itens 800/807 e UASG
  207/sequencial 2 com itens 803/804/806. O diagnóstico HTTP registrou uma
  autenticação POST e quatro GETs com HTTP 200, sem POST de PCA/item ou DELETE.
  O diagnóstico ADOdb contém somente 40 chamadas Param e 11 GetAll, sem
  StartTrans, Execute, commit, rollback ou Close.
- SHA-256 do relatório:
  `95b025a2cbac00b2723a542f2da513a5b3948ba37008f4cc672b3b81e8d12d8e`,
  recalculado e conferido. C2 está concluída; essa evidência não autoriza C3.

### C3 — Convergência real concluída

- C3 foi autorizada explicitamente em 16/09/2026. Orientar a configuração
  externa para uma
  execução única NENHUMA_ACAO individual/2027, allow_oracle_writes=true,
  allow_pncp_writes=false, baseline 4, ENCONTRADO, hash confirmado,
  limpeza vazia e novo UUID v4. O runner repetirá o precheck bloqueante.
- A primeira tentativa de armamento, request ID
  `b520137b-daac-42cf-965e-e9189699a1db`, foi recusada ainda na fase
  `CONFIGURACAO`: a Blank solicitou `NENHUMA_ACAO`, mas o arquivo externo
  permaneceu com `etapa_autorizada = PRECHECK`. O relatório íntegro, SHA-256
  `b1079c37da7261d109817104d7abbcc41cf75f85109216d9cbb3cdcb023b53f6`,
  contém `precheck_oracle = null` e diagnósticos HTTP/ADOdb vazios; nenhuma
  leitura ou escrita ocorreu. Uma nova tentativa exige correção explícita da
  etapa, novo UUID e autorização do plano de recuperação abaixo.
- A execução efetiva única, request ID
  `f09ce718-f13e-4757-b2d9-dd089e42fa24`, terminou em 16/09/2026 com
  `FLUXO_CONFIRMADO`. O precheck interno repetiu o SHA-256 Oracle aprovado
  `97e63e36eba262aef78fe94d056419d90e5ade4111667393ec06a7ef535a9f90`.
- Os dois resultados foram `SEM_ALTERACAO/NENHUMA_ACAO`, sem inclusão ou
  exclusão PNCP. Planos 87/91 receberam `PUBLICADO/1` e planos 88/90 receberam
  `PUBLICADO/2`; a projeção posterior ficou uniforme nas seis versões. O novo
  SHA-256 Oracle é
  `8783234664560b180f75dbd2e9e166db7c0af8d58a36de7ceb3f1744ffc72f92`.
- Estratégia, contexto, UOs, fotografia, ciclo de Demandas, confirmações,
  quatro auditorias e três âncoras permaneceram idênticos. O diagnóstico ADOdb
  mostra somente os dois UPDATEs de preenchimento da projeção e três INSERTs
  idempotentes das âncoras já existentes; todas as seis transações terminaram
  em commit, sem rollback ou Close.
- O diagnóstico PNCP registrou três autenticações e doze GETs, todos HTTP 200,
  sem mutação. A observação final preservou UASG 204/sequencial 1 com itens
  800/807 e UASG 207/sequencial 2 com itens 803/804/806.
- SHA-256 do relatório, recalculado e conferido:
  `b8f060898a14429ae760fffa00e1302479f8c8e5ed566a02366a5bce7f61d003`.
  A execução não deve ser repetida.
- O mantenedor confirmou em 16/09/2026 que o arquivo externo voltou para
  `PRECHECK`, com `allow_oracle_writes=false` e `allow_pncp_writes=false`.
  Nenhuma nova execução foi feita. O corretivo 8.4.7-C e o Microciclo 7 estão
  concluídos; não avançar para o Microciclo 8 sem planejamento e autorização
  próprios.

## Microciclo 8.4.8 — Exclusão integral individual

### 8A — Compatibilidade local concluída

- O plano específico foi aprovado em 16/09/2026, autorizando somente 8A.
- `Sprint84Config` passou a aceitar `quantidade_fotografia = 0` em `PRECHECK`.
  Essa etapa continua exigindo as duas permissões de escrita falsas para o
  cenário real planejado e aceita `estado_pncp_esperado = ENCONTRADO`.
- `PUBLICAR` e `NENHUMA_ACAO` continuam exigindo fotografia positiva;
  `EXCLUIR_PCA` continua exigindo fotografia exatamente zero e as duas
  permissões de escrita verdadeiras.
- O roteiro somente leitura
  `tests/Contract/Sprint84/checkpoints/excluir_pca_individual_precheck.sql`
  materializa as onze consultas canônicas, suas colunas e cardinalidades para
  a preparação futura. Ele não contém DML, DDL, PL/SQL ou `FOR UPDATE`.
- Red observado: o teste de configuração recebeu
  `CONFIG_FOTOGRAFIA_VAZIA` e o teste do roteiro encontrou o arquivo ausente.
  Green observado em PHP 8.4.7: Sprint 8.4 com 70 testes/613 asserções, matriz
  com 10/205, Application com 239/996, Oracle com 142/774, PNCP com 356/1141,
  Integration com 15/308, contratos 5.9 com 54/149, contratos 8.3 com 14/62 e
  suíte padrão com 921/4214. O lint aprovou 355 arquivos PHP e o PSR-4 estrito
  gerou o autoload otimizado com 1817 classes.
- Nenhum acesso ao SGI, Oracle ou PNCP real foi realizado em 8A. A preparação
  de demandas, conferência manual e execução de PRECHECK pertencem a 8B e não
  estão autorizadas por esta conclusão local.

### 8B — Preparação, conferência e PRECHECK concluídos

- O mantenedor cancelou e homologou pelo fluxo normal do SGI as Demandas
  800/807 da UO 1 e 803/804/806 da UO 3. Foram criados os planos 92/v4 e
  93/v5 na UO 1 e 94/v4, 95/v5 e 96/v6 na UO 3.
- A conferência manual das onze consultas confirmou: estratégia `INDIVIDUAL`,
  UO 1/UASG 204, UO 3/UASG 207, onze versões, vigentes 93/v5 e 96/v6,
  fotografia vazia, sete Demandas em situação 4, quatro auditorias e três
  âncoras permanentes. As versões 1 a 3 preservavam o estado publicado; as
  cinco versões novas apresentavam o bloco operacional integralmente nulo.
- O SHA-256 Oracle canônico aprovado foi
  `ace6ef705dac4a390cb2857b85648a73fb1341da7283af845f843fc83aac8536`.
- O PRECHECK real, request ID `c5bdd078-06d0-4153-a91d-466a04501e7f`,
  retornou `PRECHECK_CONFIRMADO` e repetiu exatamente esse hash. O PNCP ainda
  apresentava UASG 204/sequencial 1 com itens 800/807 e UASG 207/sequencial 2
  com itens 803/804/806.
- O diagnóstico registrou onze `GetAll`, sem DML, e cinco chamadas HTTP:
  somente um login e quatro GETs. O SHA-256 do relatório, recalculado e
  conferido, é
  `25afec75e32b761e84163f625db343f2ab33f65792bf202287fb212234004226`.
- A etapa 8B está concluída. Nenhuma exclusão foi executada. A etapa 8C exige
  autorização explícita própria e uma única execução de `EXCLUIR_PCA`.

### 8C — Exclusão real executada

- A execução única autorizada, request ID
  `9cf931c0-ca89-463f-af54-ac8a7f156ded`, retornou `FLUXO_CONFIRMADO` com dois
  resultados `CONCLUIDO/EXCLUIR_PCA`, sequenciais 1 e 2 e zero inclusões ou
  exclusões unitárias de itens.
- Foram enviados exatamente dois DELETEs, um para cada sequencial, ambos HTTP
  200 e com a justificativa institucional fixa. As observações posteriores
  comprovaram `AUSENCIA_DEFINITIVA` nas UASGs 204 e 207. A UASG
  930281/sequencial 4 permaneceu presente com 434 itens antes e depois.
- O Oracle terminou com as onze versões em estratégia `INDIVIDUAL`, respectivas
  UASGs, estado `EXCLUIDO` e sequenciais preservados. Somente os planos vigentes
  93/v5 e 96/v6 receberam a nova confirmação; as confirmações anteriores foram
  preservadas. A fotografia continuou vazia e as sete Demandas permaneceram em
  situação 4.
- Duas auditorias de procedimento 9 foram acrescentadas, totalizando seis. As
  três âncoras permanentes foram preservadas. Houve seis commits, sem rollback
  ou `Close()`.
- SHA-256 Oracle posterior:
  `038ce6eeed00124118272091203f3509d8847b52c89552dbaa328d79ebc19da0`.
  SHA-256 do relatório, recalculado e conferido:
  `96f31765a8c44b6e353884c58cff9864ac3c4a1a3710f1dc248dbb65c15d4b44`.
- A execução não deve ser repetida. O mantenedor confirmou que o arquivo
  externo voltou a `PRECHECK`, com ambas as permissões de escrita falsas, sem
  nova execução da Blank. O Microciclo 8 está concluído; o Microciclo 9
  consolidado permanece sem autorização.

## Microciclo 8.4.9 — Ativação e checkpoint consolidado

- A tentativa inicial de usar o exercício 2028 foi recusada na configuração
  por `RN-CAD-002`, antes de qualquer acesso Oracle ou PNCP. A regra foi
  preservada e o cenário consolidado passou a usar o exercício 2026.
- Em ambiente não produtivo e mediante autorização específica, o mantenedor
  executou no DBeaver a limpeza controlada dos dados Oracle antigos de 2026,
  após inventário e backup lógico. Foram removidos cinco planos, cinco
  demandas, oito assinaturas, três avaliações extraordinárias, sete históricos
  e três controles de concorrência. A Demanda 14 foi preservada e apenas seu
  vínculo com a Demanda 1 foi removido. Os dados de 2027 e 2028 permaneceram
  intactos. Os PCAs PNCP legados 16, 17 e 18 não foram alterados.
- Pelo fluxo normal do SGI, foram criados e homologados os planos 99/UO 1 e
  100/UO 3, ambos versão 1, com as Demandas 811 e 810, respectivamente.
- A conferência manual das onze consultas aprovou o snapshot consolidado de
  2026 e o SHA-256 Oracle canônico
  `c877c94cf8a633c977192484cda37455e1443892e5098efbd5f26fc89ba0e5da`.
- O PRECHECK real, request ID `29fb8872-e6ea-4c76-8a8e-35beeb10b2de`, retornou
  `PRECHECK_CONFIRMADO`, repetiu exatamente o hash aprovado e comprovou a
  ausência de PCA da UASG consolidadora 930281 em 2026. A listagem anual
  retornou os PCAs legados 16/210, 17/205 e 18/207, que permaneceram fora do
  alvo e sem alteração.
- O diagnóstico registrou 41 parametrizações e onze leituras ADOdb, sem
  `Execute`, início ou término de transação e sem `Close()`. No PNCP ocorreram
  somente o login e uma leitura da listagem consolidada, ambos HTTP 200; não
  houve mutação. O SHA-256 do relatório, recalculado e conferido, é
  `ff91c0c86ae7524df608d73acfc77c87c12cfc03fd80d77d6f85644ffae1d223`.
- O Microciclo 9 está concluído. Esta evidência não autoriza `PUBLICAR`; o
  Microciclo 10 depende de planejamento e autorização próprios.

## Microciclo 8.4.10 — PUBLICAR consolidado

- A execução única, request ID `8c65729b-90b5-4ac5-ada7-ca53b53387f8`,
  retornou `FLUXO_CONFIRMADO`. O PRECHECK interno repetiu exatamente o
  SHA-256 Oracle aprovado
  `c877c94cf8a633c977192484cda37455e1443892e5098efbd5f26fc89ba0e5da`
  e comprovou novamente a ausência do alvo UASG 930281.
- O único resultado foi `CONCLUIDO/PUBLICAR`, modo `CONSOLIDADO`, sem
  identidade individual de UO, com duas inclusões, zero exclusões e sequencial
  PNCP 36. Houve exatamente um `POST /v1/orgaos/{cnpj}/pca`, HTTP 201, com as
  Demandas 810 e 811. A releitura encontrou o PCA 36 ativo na UASG 930281 com
  exatamente esses dois itens.
- Os PCAs legados 16/210, 17/205 e 18/207 permaneceram fora do alvo. O
  diagnóstico HTTP contém dez trocas: três autenticações, duas listagens
  anuais, o único POST de PCA e duas duplas de releitura de identidade/itens.
  Não houve DELETE nem outra mutação PNCP.
- Os planos 99 e 100 foram confirmados em `2026-09-17 11:26:10` e ficaram
  uniformemente com estratégia `CONSOLIDADO`, UASG 930281, estado `PUBLICADO`
  e sequencial 36. Fotografia, ciclo das Demandas, estratégia corrente,
  contexto e unidades permaneceram inalterados.
- Foi acrescentada uma auditoria de procedimento 5 para a publicação
  consolidada. Foram materializadas as duas âncoras esperadas:
  `ESTRATEGIA_ANUAL/2026/ESTRATEGIA_ANUAL` e
  `UNIDADE_EXTERNA/2026/930281`.
- O diagnóstico ADOdb registrou sete `Execute`: duas garantias de âncora, uma
  adoção de estratégia/UASG, uma projeção de estado/sequencial, duas
  confirmações e uma auditoria. As quatro transações terminaram em commit,
  sem rollback ou `Close()`.
- SHA-256 Oracle posterior:
  `f0d5b5408d2cbc9c3305168e3e25573fdefc3d67d2b7516afa69f2971a7ead79`.
  SHA-256 do relatório, recalculado e conferido:
  `a1f9928182db44f5699fd55ec52275392d2dddfec8622aa30942c09542f581f6`.
- A execução não deve ser repetida. O mantenedor confirmou que o arquivo
  externo voltou a `PRECHECK`, com `allow_oracle_writes=false` e
  `allow_pncp_writes=false`, sem nova execução da Blank. O Microciclo 10 está
  concluído; o Microciclo 11 depende de planejamento e autorização próprios.

## Microciclo 8.4.11 — NENHUMA_ACAO consolidada

- A execução única, request ID `e858fa4a-9230-4a93-a1e0-272e7b1e99be`,
  retornou `FLUXO_CONFIRMADO`. O PRECHECK interno repetiu exatamente o
  SHA-256 Oracle posterior à publicação
  `f0d5b5408d2cbc9c3305168e3e25573fdefc3d67d2b7516afa69f2971a7ead79`
  e encontrou a UASG 930281/sequencial 36 com as Demandas 810 e 811.
- O único resultado foi `SEM_ALTERACAO/NENHUMA_ACAO`, modo `CONSOLIDADO`, com
  sequencial 36, zero inclusões e zero exclusões. A observação posterior PNCP
  permaneceu idêntica, ativa e com os mesmos dois itens.
- As nove trocas HTTP foram três autenticações e seis leituras. Não houve POST
  de PCA/item, DELETE ou qualquer outra mutação PNCP.
- Todas as onze consultas Oracle permaneceram exatamente iguais antes e
  depois. O SHA-256 posterior continuou
  `f0d5b5408d2cbc9c3305168e3e25573fdefc3d67d2b7516afa69f2971a7ead79`.
  Não houve nova confirmação, auditoria, atualização de estado ou propagação
  de projeção operacional.
- Os dois únicos `Execute` foram garantias idempotentes das âncoras já
  existentes `ESTRATEGIA_ANUAL/2026/ESTRATEGIA_ANUAL` e
  `UNIDADE_EXTERNA/2026/930281`; o snapshot comprova que nenhuma linha nova foi
  acrescentada. As quatro transações terminaram em commit, sem rollback ou
  `Close()`.
- O PRECHECK manual separado e o roteiro local planejados não foram executados
  nesta ocasião. Isso não invalida a evidência obtida: antes do fluxo, o gate
  interno comparou o estado com o hash exato aprovado e, depois, comprovou a
  invariância integral. A execução não deve ser repetida.
- SHA-256 do relatório, recalculado e conferido:
  `3fd1ac2635c7d92fe805bc9cc1a708c8f77db2240cef9729cb6678e5643815e8`.
- O mantenedor confirmou que o arquivo externo voltou a `PRECHECK`, com ambas
  as permissões de escrita falsas, sem nova execução da Blank. O Microciclo 11
  está concluído; o Microciclo 12 depende de planejamento e autorização
  próprios.

## Microciclo 8.4.12 — RETIFICAR consolidado

### 12A — Roteiro local concluído

- Foi criado o roteiro somente leitura
  `tests/Contract/Sprint84/checkpoints/retificar_consolidado_precheck.sql` com
  as onze consultas canônicas para o exercício 2026, duas UOs e a UASG
  consolidadora 930281.
- O checkpoint esperado materializa quatro versões, vigentes versão 2,
  fotografia consolidada com a Demanda 810 e uma nova Demanda extraordinária,
  ciclo com a Demanda 811 cancelada, projeção antiga em `PUBLICADO/36` e novas
  linhas integralmente nulas, uma auditoria e duas âncoras.
- O teste contratual exige exatamente onze `SELECT`s e recusa DML, DDL, PL/SQL
  e `FOR UPDATE`. Red observado: 7 testes/194 asserções, com a única falha pelo
  roteiro ausente. Green: 7 testes/223 asserções.
- Validação local em PHP 8.4.7: contratos Sprint 8.4 com 72 testes/672
  asserções; matriz de integração com 10/205; lint aprovado e PSR-4 estrito com
  1.817 classes. O Composer apenas informou que prosseguiu sem o cache local,
  por falta de permissão de escrita nesse diretório externo ao repositório.
- Nenhum acesso ao SGI, Oracle ou PNCP real foi realizado. A etapa 12A está
  concluída; 12B permanece pausada e exige autorização própria.

### 12B — Preparação, conferência e PRECHECK concluídos

- Pelo fluxo normal do SGI, o cancelamento da Demanda 811 gerou o plano
  101/UO 1/versão 2. A nova Demanda extraordinária 812 gerou o plano
  102/UO 3/versão 2. A Demanda 810 permaneceu ativa.
- A primeira fotografia manual encontrou `jsilva` na lotação 3 e foi bloqueada.
  Após o mantenedor retornar à lotação 1, a consulta foi repetida e a única
  divergência desapareceu. As outras dez consultas permaneceram iguais.
- O checkpoint aprovado contém quatro versões; vigentes 101/102 ainda não
  confirmadas; fotografia 810/812; ciclo com 811 cancelada; versões 1 em
  `CONSOLIDADO/930281/PUBLICADO/36`; versões 2 com bloco operacional nulo; uma
  auditoria de procedimento 5 e duas âncoras. O SHA-256 Oracle canônico é
  `44944e0472e8d42f526b70dca07710c7322cb50b48cefbc2064a20396a990b23`.
- O PRECHECK real, request ID `214ebb5a-7012-4efa-b01a-902b6e72d280`, retornou
  `PRECHECK_CONFIRMADO` e repetiu exatamente o hash aprovado. O PNCP apresentou
  UASG 930281/sequencial 36 ativa com as Demandas 810 e 811, formando uma
  exclusão e uma inclusão para a futura retificação consolidada.
- O diagnóstico ADOdb contém somente 41 chamadas `Param` e onze `GetAll`, sem
  transação, DML ou `Close()`. O diagnóstico HTTP contém um login, uma listagem
  anual e uma leitura de itens, todos HTTP 200, sem mutação.
- SHA-256 do relatório, recalculado e conferido:
  `724db77321c7cc507ec46fa28ebcd0c76a4c26257122d39905ca1d2ea64769df`.
- A etapa 12B está concluída. Esta evidência não autoriza `RETIFICAR`; 12C
  permanece pausada e exige autorização própria.

### 12C — Retificação real concluída

- A execução única, request ID `a1c7336d-4b48-44f7-9996-0abf8f056f64`,
  retornou `FLUXO_CONFIRMADO`. O PRECHECK interno repetiu exatamente o
  SHA-256 Oracle aprovado
  `44944e0472e8d42f526b70dca07710c7322cb50b48cefbc2064a20396a990b23`.
- O único resultado foi `CONCLUIDO/RETIFICAR`, modo `CONSOLIDADO`, sequencial
  36, com uma exclusão e uma inclusão. O DELETE do item 811 ocorreu primeiro e
  retornou HTTP 200; o POST posterior do item 812 retornou HTTP 201.
- As releituras encontraram o PCA 36 ativo na UASG 930281 com exatamente as
  Demandas 810 e 812. Não houve POST de PCA, exclusão integral ou mutação em
  outro sequencial.
- Os planos 101 e 102 foram confirmados em `2026-09-17 12:15:12`. As quatro
  versões terminaram uniformemente em `CONSOLIDADO/930281/PUBLICADO/36`; as
  confirmações anteriores dos planos 99 e 100 foram preservadas.
- Foi acrescentada uma auditoria de procedimento 8, com uma inclusão e uma
  exclusão, totalizando duas auditorias. Fotografia, ciclo das Demandas,
  estratégia corrente, contexto, UOs e as duas âncoras permaneceram iguais.
- Os sete `Execute` foram duas garantias idempotentes de âncora, uma adoção de
  estratégia/UASG nas linhas pendentes, uma propagação de estado/sequencial,
  duas confirmações e uma auditoria. As quatro transações terminaram em commit,
  sem rollback ou `Close()`.
- SHA-256 Oracle posterior:
  `ae8861e51dda76c8dea577c12502636cfecdf06d084364153f4c588078a97326`.
  SHA-256 do relatório, recalculado e conferido:
  `0352b60221ce40e80d9c368b5fc7b935b39fa9b4de0426c38a2b8cb8659202f0`.
- O mantenedor confirmou que o arquivo externo voltou a `PRECHECK`, com ambas
  as permissões de escrita falsas, sem nova execução da Blank. A execução não
  deve ser repetida. O Microciclo 12 está concluído; o Microciclo 13 depende de
  planejamento e autorização próprios.

## Microciclo 8.4.13 — EXCLUIR_PCA consolidado

### 13A — Roteiro local concluído

- Foi criado o roteiro somente leitura
  `tests/Contract/Sprint84/checkpoints/excluir_pca_consolidado_precheck.sql`
  com as onze consultas canônicas para o exercício 2026, as UOs 1 e 3 e a
  UASG consolidadora 930281.
- O checkpoint esperado materializa seis versões: UO 1 com versões 1 e 2 e
  UO 3 com versões 1 a 4. As quatro versões existentes permanecem em
  `CONSOLIDADO/930281/PUBLICADO/36`; as novas versões 3 e 4 da UO 3 devem
  apresentar bloco operacional integralmente nulo.
- O roteiro exige fotografia vazia, as Demandas 810, 811 e 812 em situação 4,
  duas auditorias existentes e as duas âncoras de concorrência já observadas.
  Os IDs físicos dos novos planos serão aceitos somente após a conferência
  manual de 13B.
- O teste contratual exige exatamente onze `SELECT`s e recusa DML, DDL, PL/SQL
  e `FOR UPDATE`. O Red focado falhou exclusivamente pela ausência do roteiro;
  o Green passou com 1 teste/31 asserções.
- Validação local em PHP 8.4.7: contratos Sprint 8.4 com 73 testes/703
  asserções; matriz de integração com 10/205; lint aprovado e PSR-4 estrito
  com 1.817 classes.
- Nenhum acesso ao SGI, Oracle ou PNCP real foi realizado. A etapa 13A está
  concluída; 13B permanece pausada e exige autorização própria.

### 13B — Preparação, conferência e PRECHECK concluídos

- Pelo fluxo normal do SGI, os cancelamentos das Demandas 810 e 812 geraram os
  planos 103/UO 3/versão 3 e 104/UO 3/versão 4. A Demanda 811 permaneceu
  cancelada no plano 101/UO 1/versão 2.
- As onze consultas manuais confirmaram seis versões, fotografia vazia, as
  três Demandas em situação 4, quatro versões anteriores em
  `CONSOLIDADO/930281/PUBLICADO/36`, duas novas versões com bloco operacional
  nulo, duas auditorias e duas âncoras.
- O primeiro PRECHECK foi recusado antes de qualquer acesso PNCP ou escrita
  porque o hash calculado a partir do JSON do DBeaver preservava números como
  tipos numéricos, enquanto o ADOdb os retorna como strings. A comparação
  programática comprovou que os snapshots eram idênticos após aplicar os tipos
  reais do ADOdb. O SHA-256 Oracle canônico aprovado é
  `381cbf6ebc6a1d2610bbeaafc7a4aea714b0b6f68ea841a9fd12e3faeca71df4`.
- O PRECHECK real efetivo, request ID
  `f95becf2-a4d7-4f08-8762-45cb6577c1b7`, retornou
  `PRECHECK_CONFIRMADO`, repetiu o hash aprovado e encontrou a UASG
  930281/sequencial 36 ativa com exatamente as Demandas 810 e 812.
- O diagnóstico ADOdb registrou somente 41 chamadas `Param` e onze `GetAll`.
  No PNCP ocorreram somente login e duas leituras HTTP 200; não houve mutação.
  O SHA-256 do relatório, recalculado e conferido, é
  `6dbc3e8308275936e26770d7eb7e698ce3cfee63cb5664909190112782bd2ba1`.
- A etapa 13B está concluída. Esta evidência não autoriza `EXCLUIR_PCA`; 13C
  permanece pausada e exige autorização própria.

### 13C — Exclusão real confirmada

- A execução única, request ID `5c6798aa-7b80-43ed-93aa-594c915f1031`,
  retornou `FLUXO_CONFIRMADO`. O PRECHECK interno repetiu exatamente o
  SHA-256 Oracle aprovado
  `381cbf6ebc6a1d2610bbeaafc7a4aea714b0b6f68ea841a9fd12e3faeca71df4`.
- O único resultado foi `CONCLUIDO/EXCLUIR_PCA`, modo `CONSOLIDADO`, UASG
  930281 e sequencial 36, com zero inclusões e zero exclusões de itens. Houve
  exatamente um `DELETE /v1/orgaos/{cnpj}/pca/2026/36`, HTTP 200, com a
  justificativa institucional fixa.
- Duas verificações posteriores independentes concluíram
  `AUSENCIA_DEFINITIVA`. As listagens anuais posteriores preservaram os PCAs
  legados 16/210, 17/205 e 18/207 e não apresentaram o sequencial 36.
- As seis versões terminaram uniformemente em
  `CONSOLIDADO/930281/EXCLUIDO/36`. Somente o plano vigente 104 recebeu nova
  confirmação, em `2026-09-17 15:29:20`; o plano intermediário 103 permaneceu
  sem confirmação e as confirmações anteriores foram preservadas.
- Foi acrescentada uma auditoria de procedimento 9, totalizando três
  auditorias. Fotografia vazia, ciclo das Demandas canceladas e as duas
  âncoras permaneceram preservados.
- O diagnóstico ADOdb registrou seis `Execute`, quatro transações concluídas em
  commit, nenhum rollback e nenhum `Close()`. O diagnóstico HTTP contém doze
  trocas: três logins, oito leituras e o único DELETE integral.
- SHA-256 Oracle posterior:
  `1aee1ee6f67810f56ec0d242c90868090d4b56c0f72ed290f505cbccd37dfd67`.
  SHA-256 do relatório, recalculado e conferido:
  `78f74ee35ace7731bdb7383736a81ae6385fb40af8eed1cbeba543b0890e000b`.
- A execução não deve ser repetida. O mantenedor confirmou que o arquivo
  externo voltou a `PRECHECK`, com ambas as permissões de escrita falsas, sem
  nova execução da Blank. O Microciclo 13 está concluído; a Sprint 8.4 depende
  do Microciclo 14 de regressão e encerramento.

## Microciclo 8.4.14 — Regressão e encerramento

- Regressão final aprovada em PHP 8.4.7:
  - contratos Sprint 8.4: 73 testes/703 asserções;
  - Integration e Composition: 29/505;
  - Presentation e Application: 270/1.315;
  - Infrastructure: 511/1.956;
  - contratos base 5.1: 24/51;
  - contratos de adapters 5.9: 54/149;
  - contratos Oracle 8.2: 12/43;
  - contratos PNCP 8.3, sem grupos live: 14/62;
  - suíte padrão: 921/4.214.
- O lint aprovou os 355 arquivos PHP de `src/` e `tests/`. O autoload PSR-4
  estrito foi gerado com 1.817 classes. A inspeção das quatro camadas não
  encontrou dependência em direção proibida.
- A busca por material sensível encontrou somente literais declarados em
  fixtures e testes de segurança; nenhum segredo real foi identificado. O
  `git diff --check` passou após normalização mecânica de finais de linha e
  espaços finais em três documentos já alterados.
- O `composer.json` foi validado sem erro estrutural. O modo estrito conserva
  somente o aviso já existente de licença ausente; nenhuma licença foi
  inventada porque essa decisão pertence ao mantenedor.
- O plano, o guia de probes, o catálogo, o estado operacional e o Roadmap foram
  atualizados somente com evidências observadas. Nenhum teste live, acesso
  Oracle/PNCP ou nova execução da Blank ocorreu neste microciclo.
- A matriz real 4×2 está completa nos modos individual e consolidado. A Sprint
  8.4 está concluída. A Sprint 8.5 é a próxima na ordem, mas permanece sem
  planejamento ou autorização de implementação.
