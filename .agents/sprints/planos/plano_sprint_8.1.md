# Sprint 8.1 — Composition Root, registro externo e perfil Scriptcase

**Status:** Sprint 8.1 concluída em 01/09/2026 após os cinco microciclos
autorizados.

## Objetivo

Entregar o entrypoint `PublicadorPcaScriptcase`, o registro externo allowlisted
e o `CompositionRootPublicacaoPca`, preservando a fachada existente como
fronteira segura da Presentation.

```text
Blank
→ PublicadorPcaScriptcase
→ CompositionRootPublicacaoPca
→ FachadaPublicarPca
→ Controller
→ UseCase
→ Presenter
→ ViewModel
```

## Contratos aprovados

- A Blank fornecerá somente conexão ADOdb emprestada, login autenticado,
  `SERVER_NAME`, ano e `X-Request-ID` já extraído.
- `PublicadorPcaScriptcase::executar(object, string, string, int, ?string)` será
  a única API conhecida pela Blank.
- `CompositionRootPublicacaoPca::montar(object, string, array,
  CorrelacaoExecucao)` criará um grafo novo e devolverá a fachada existente.
- `PCA_PNCP_PROFILE_REGISTRY_FILE`, provisionada no processo sem `.env`,
  apontará para o registro externo `SERVER_NAME => caminho absoluto do perfil`.
- Registro e perfis reais permanecerão fora do repositório e do webroot.
- O CNPJ virá exclusivamente do perfil e será validado antes da montagem.
- O `X-Request-ID` deverá ser UUID v4 e será preservado literalmente; o backend
  não gerará correlação alternativa.
- Request ID ausente ou inválido produzirá `INTERROMPIDA`,
  `REQUEST_ID_INVALIDO`, mensagem fixa e correlação `null`, sem evento técnico.
- Demais falhas de bootstrap produzirão `INTERROMPIDA/FALHA_INESPERADA` com a
  correlação externa válida.

## Microciclos e pausas obrigatórias

1. Correlação, interrupção segura e logging da fachada.
2. Composition Root e materialização tipada do perfil.
3. Registro externo e entrypoint Scriptcase.
4. Prova transversal sem rede real.
5. Gates e encerramento documental.

Cada microciclo exige autorização própria. Não serão criados nova fachada,
interface para o root, factory, container, Service Locator, configuração global
interna, gerador de correlação, integração real ou commit.

## Validação planejada

- testes focados em TDD e regressões diretamente relacionadas;
- suíte padrão, PSR-4 estrito, lint e direção das dependências;
- inspeção de segredos, caminhos, macros, normalização indevida e geração de
  UUID;
- `git diff --check`;
- nenhum preflight, probe, grupo live ou acesso Oracle/PNCP/Scriptcase real.

## Evidências do Microciclo 1

- O Red focado executou 23 testes e 106 asserções, com uma ausência de método e
  duas falhas esperadas: a fachada ainda não oferecia a interrupção para request
  ID inválido, não registrava a falha inesperada e o acumulador ainda não
  reconhecia a ação global.
- O Green focado aprovou 23 testes e 121 asserções.
- A regressão conjunta de Presentation e observabilidade aprovou 44 testes e
  360 asserções.
- A suíte padrão aprovou 843 testes e 3.321 asserções.
- A interrupção `REQUEST_ID_INVALIDO` devolve correlação `null`; as respostas
  normais e a interrupção `FALHA_INESPERADA` preservam a correlação recebida.
- A fachada concentra códigos, mensagens, contagens e resultados das duas
  interrupções públicas. Falhas inesperadas geram somente um evento seguro com
  ação `EXECUTAR_PUBLICACAO_PCA`, e uma falha do logger não atravessa a fachada.
- O acumulador aceita a ação global e preserva byte a byte um UUID v4 com letras
  maiúsculas, sem `trim` ou alteração de caixa da correlação.
- Autoload PSR-4 estrito, lint dos sete arquivos PHP afetados, direção das
  dependências, inspeções de normalização e dados sensíveis e
  `git diff --check` foram aprovados.
- Nenhum Composition Root, entrypoint Scriptcase, acesso externo ou commit foi
  criado neste microciclo.

## Evidências do Microciclo 2

- O Red focado executou 5 testes e 2 asserções, com três erros e duas falhas
  causados exclusivamente pela ausência do `CompositionRootPublicacaoPca`.
- O Green focado aprovou 5 testes e 152 asserções.
- A regressão de Composition, Presentation, Application e integração de
  composição aprovou 277 testes e 1.520 asserções.
- A suíte padrão aprovou 848 testes e 3.473 asserções.
- O perfil tipado exige estrutura exata, todos os limites, timeouts, parâmetros
  de leitura e os onze mapeamentos Oracle. Ambiente desconhecido, URL
  configurável, credencial vazia, tipo inválido, retry incoerente, timezone ou
  mapeamento inválido e CA bundle inseguro falham antes da conexão emprestada.
- A chave `ca_bundle` é obrigatória e aceita `null` explícito conforme o contrato
  vigente; quando contém caminho, ele deve ser absoluto, existente e legível.
- O CNPJ é exigido estruturalmente como texto no perfil, mas sua validação de
  domínio e a construção da entrada permanecem no entrypoint do Microciclo 3,
  antes da chamada ao root, conforme a separação aprovada.
- Cada montagem cria uma conexão ADOdb compartilhada entre os seis adapters
  Oracle, um transporte compartilhado entre os três adapters PNCP e um logger
  compartilhado entre PNCP, núcleo e fachada, sempre com a correlação recebida.
- A construção de duas fachadas não executou nenhum método ADOdb, não chamou
  `Close()` e não realizou rede. O root não conhece ambiente de processo,
  `SERVER_NAME`, arquivos de perfil, Scriptcase ou renderização.
- Autoload PSR-4 estrito, lint dos dez arquivos PHP acumulados na sprint,
  direção das dependências, inspeções de conteúdo proibido, whitespace e
  `git diff --check` foram aprovados.
- Nenhum registro externo, entrypoint Scriptcase, acesso externo ou commit foi
  criado neste microciclo.

## Evidências do Microciclo 3

- O Red focado executou 14 testes, sem asserções concluídas, e registrou 14
  erros causados exclusivamente pela ausência do `PublicadorPcaScriptcase`.
- O Green focado aprovou 14 testes e 280 asserções.
- A regressão conjunta do entrypoint, Composition, Presentation e
  observabilidade aprovou 64 testes e 793 asserções.
- A suíte padrão aprovou 862 testes e 3.753 asserções.
- A API pública não possui construtor configurável e recebe somente conexão
  emprestada, login autenticado, `SERVER_NAME`, ano e request ID. CNPJ, perfil,
  registro e caminhos não integram sua assinatura.
- Request ID ausente, vazio, cercado por espaços, com quebra de linha, formato,
  versão ou variante inválidos produz `REQUEST_ID_INVALIDO`, correlação `null`
  e não carrega o registro externo.
- O único `getenv` de produção lê `PCA_PNCP_PROFILE_REGISTRY_FILE`. Valor
  ausente, vazio, relativo, inexistente, não arquivo, ilegível ou localizado
  dentro da release falha fechado, sem `.env`, busca, concatenação ou default.
- O registro aceita somente índice `string => string`, valida todos os caminhos
  antes da seleção e exige arquivos absolutos, existentes, legíveis e externos
  à release. Estrutura adicional, entrada inválida ou perfil interno à release
  é recusado.
- A seleção usa diretamente `array_key_exists` e o valor indexado; diferenças
  de caixa ou espaços em `SERVER_NAME` não carregam o perfil e nenhum hostname
  participa da construção de caminho ou da escolha do ambiente.
- Perfil não-array, sem CNPJ, com tipo ou CNPJ inválido e perfil incoerente
  produzem `FALHA_INESPERADA` com a correlação externa original. O `Cnpj` de
  domínio é materializado antes do root e seu valor validado compõe a entrada.
- A auditoria não encontrou geração interna de UUID, normalização de request ID
  ou `SERVER_NAME`, acesso a `$_SERVER`, URL configurável, hostname ou caminho
  real no código de produção.
- Autoload PSR-4 estrito, lint dos doze arquivos PHP acumulados na sprint,
  direção das dependências, whitespace e `git diff --check` foram aprovados.
- Os testes usaram somente registro e perfis temporários fora do repositório,
  restauraram a variável de processo e não acessaram Oracle, PNCP ou Scriptcase.
  A prova transversal do caminho montado permanece no Microciclo 4.
- Nenhum commit foi criado.

## Evidências do Microciclo 4

- A primeira execução dos dois cenários transversais falhou porque a massa de
  teste fornecia `valorAtivo` inteiro a um mapeamento que exige texto. A
  correção ficou restrita à massa, sem mudança no código de produção.
- A prova transversal focada aprovou 2 testes e 33 asserções.
- A regressão de Integration, entrypoint, Composition, Presentation e
  observabilidade aprovou 69 testes e 896 asserções.
- A suíte padrão aprovou 864 testes e 3.786 asserções.
- Com ano inválido, registro e perfil externos são carregados e o grafo é
  montado, mas o Controller devolve `ANO_EXECUCAO_INVALIDO` antes de qualquer
  bind, consulta, transação ou escrita ADOdb, preservando literalmente a
  correlação externa.
- Com ano válido, duas chamadas públicas distintas alcançam a preparação da
  Application e encerram na resolução local do Representante. Foram observadas
  exatamente duas consultas ADOdb, sem transação, escrita ou `Close()`, e cada
  ViewModel devolveu o UUID fornecido em sua própria chamada.
- A saída local ocorre antes da autenticação pela ordem vigente da Application.
  Como proteção adicional do teste, a credencial PNCP não vazia é
  propositalmente inválida para JSON: se a ordem regredir, a autenticação falha
  antes de entregar qualquer requisição ao transporte.
- O teste usa registro e perfil PHP temporários fora do repositório, restaura a
  variável de processo e não adiciona seam, executor configurável, interface,
  factory, container ou Service Locator.
- Nenhuma rede, Oracle, PNCP ou Scriptcase real foi acessado e nenhum arquivo de
  produção foi alterado neste microciclo.
- Autoload PSR-4 estrito, lint, whitespace e `git diff --check` foram aprovados.
- Nenhum commit foi criado.

## Evidências do Microciclo 5

- A regressão conjunta de Presentation, Composition, Integration, Application
  e entrypoint aprovou 293 testes e 1.833 asserções.
- A suíte padrão aprovou 864 testes e 3.786 asserções no PHP 8.4.7.
- O autoload PSR-4 estrito gerou 1.761 classes, e o lint aprovou os 293 arquivos
  PHP do repositório.
- As auditorias confirmaram a direção das dependências e a ausência, no escopo
  novo, de geração ou normalização de UUID, acesso a `$_SERVER`, `.env`, macros
  Scriptcase, factory, container, Service Locator, URL, hostname, CNPJ, segredo
  ou caminho real versionado.
- `git diff --check` e a inspeção de whitespace dos arquivos novos foram
  aprovados; os avisos exibidos limitaram-se à conversão futura de LF para CRLF
  já configurada pelo Git no workspace.
- DI-010, especificação de logs, fluxo, Roadmap, estado operacional,
  arquitetura, catálogo, glossário e guia de leitura foram atualizados com o
  contrato e as evidências da Sprint 8.1.
- O estado operacional registra a Sprint 8.1 como concluída e a Sprint 8.2 —
  Integração Oracle real como próxima etapa, dependente de planejamento e
  autorização próprios.
- Nenhum preflight, probe, grupo `pncp-live`, Oracle, PNCP ou Scriptcase real
  foi executado. Nenhum commit foi criado.
