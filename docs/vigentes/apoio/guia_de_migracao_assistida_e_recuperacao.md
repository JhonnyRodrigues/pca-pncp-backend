# Guia de migração assistida e recuperação

**Classificação documental:** Apoio

**Status:** Vigente; procedimento validado na Sprint 9.4

**Autoridade/Escopo:** operação protegida do inventário, vínculo e reconciliação
de publicações PCA legadas

## 1. Finalidade

Este runbook — procedimento operacional passo a passo — orienta a migração
assistida da publicação manual conhecida e o inventário dos exercícios 2026 e
2027. Ele aplica o [plano aprovado](../../../.agents/sprints/planos/plano_sprint_9.4.md)
e não autoriza, isoladamente, nenhuma execução em produção.

O fluxo separa três fatos que não podem ser confundidos:

1. o PCA existe no PNCP;
2. a procedência desse PCA foi comprovada pelo mantenedor;
3. o SGI possui um vínculo local coerente com esse PCA.

A existência externa, sozinha, nunca autoriza adoção ou exclusão.

## 2. Gate temporário

O gate `Sprint94` é empacotado separadamente da release e usa uma configuração
externa indicada por `PCA_SPRINT94_CONFIG_FILE`. A configuração aceita somente:

- servidor `sgi.semaepiracicaba.sp.gov.br` e usuário `jsilva`;
- perfil externo `PRODUCAO`;
- exercícios 2026 e 2027;
- etapa `PRECHECK` ou `INVENTARIO`;
- `allow_oracle_writes`, `allow_pncp_writes` e `allow_log_writes` iguais a
  `false`.

O PRECHECK carrega e valida configuração/perfil e verifica a disponibilidade
das dependências sem consultar Oracle nem PNCP. O INVENTARIO autentica no PNCP,
faz somente `GET`, e permite no ADOdb exclusivamente `GetAll` com `SELECT`.
`Execute`, abertura/fechamento de transação, `Close` e qualquer método HTTP de
negócio são recusados antes do despacho.

O arquivo [`config.example.php`](../../../tests/Contract/Sprint94/config.example.php)
é apenas um modelo desarmado; não contém nem deve receber credenciais, CNPJ ou
caminhos locais de produção.

## 3. Relatório canônico

O relatório ordena recursivamente as chaves e calcula SHA-256 sobre o JSON
canônico antes de acrescentar o próprio hash. Ele contém somente a projeção
Oracle necessária, UASGs, sequenciais, conjuntos ordenados de `numeroItem` e
classificação preliminar.

São recusados campos de credencial, JWT, cabeçalho de autorização, SQL,
respostas brutas e CNPJ integral. O CNPJ do perfil é representado apenas por
SHA-256. A classificação `DESCONHECIDO_PENDENTE_CONFIRMACAO` preserva o
candidato e bloqueia qualquer avanço até a confirmação humana de procedência.

## 4. Construção e validação local

```text
composer contract:sprint94:tests
composer package:sprint94:inventory-gate
composer package:sprint94:inventory-gate:verify
```

O artefato esperado é `build/pca-pncp-sprint94-inventory-gate/`, contendo o
pacote principal validado e apenas o runtime mínimo do gate. Configurações e
segredos permanecem fora dele.

## 5. Pausa obrigatória após o Microciclo 1

Concluir testes, construção e verificação locais não autoriza transferência,
instalação, publicação da Blank nem execução de PRECHECK. O Microciclo 2 só
pode começar após autorização explícita do mantenedor.

As seções operacionais de inventário real, SQL específico, aplicação
transacional, avaliação pós-vínculo, reconciliação e remoção serão completadas
nos respectivos microciclos, a partir de evidências reais e sanitizadas. Não se
antecipa SQL genérico de adoção.

### 5.1 Resultado do Microciclo 2

Em 23/09/2026, o gate foi instalado no servidor de produção
`SEMAE-SGIPROD`, separado da release e sem configurações ou segredos. O arquivo
externo `sprint94.php` permaneceu fora do webroot, com etapa `PRECHECK` e as
três permissões de escrita falsas.

Uma instalação inicial no `validaSGI` foi recusada pela guarda de hostname
antes de qualquer consulta Oracle ou chamada PNCP. Os artefatos desse ambiente
foram removidos antes da instalação correta.

O PRECHECK de produção confirmou o perfil `PRODUCAO`, os anos 2026/2027, as
dependências estruturais e a ausência de consentimento para escritas. O
relatório canônico teve SHA-256
`190cb1ca388227f41cc889486b1434be36fc07ee93179c81f3aa940972a56eed`.
Não houve consulta Oracle nem autenticação PNCP.

O gate e a Blank permanecem instalados para o próximo microciclo, mas a
configuração continua em `PRECHECK`. É proibido chamar `INVENTARIO` sem nova
autorização explícita.

### 5.2 Artefato corretivo 3A

A correção da fronteira Scriptcase precisa ser instalada antes da
reconciliação pelo fluxo normal. Enquanto a sprint permanece sem commit, ela
não pode ser apresentada como release oficial. O artefato nominal e temporário
é produzido por:

```text
composer package:sprint94:correction-3a
composer package:sprint94:correction-3a:verify
composer package:sprint94:correction-3a:archive
```

Ele contém o diretório completo `pca-pncp-backend/`, que deve substituir a
instalação ativa por troca de diretórios com backup, e a fonte
`scriptcase/PCA_PUBLICAR_B/onExecute.php`, que deve ser publicada e compilada
pela interface do Scriptcase. Não se deve copiar somente o arquivo PHP alterado
para dentro do backend ativo, pois isso misturaria versões e invalidaria o
manifesto do pacote.

Construir e validar esse artefato não autoriza transferência, instalação,
compilação da Blank ou execução funcional. A instalação deve preservar o
backend anterior até a verificação do autoload e permitir rollback por troca de
diretórios. O rollback dos arquivos não desfaz eventual efeito Oracle ou PNCP.

Em 24/09/2026, após autorização específica, o artefato foi transferido com o
mesmo SHA-256, extraído e verificado. O backend foi promovido a partir de um
diretório candidato, mantendo a instalação anterior para rollback. A Blank
corrigida foi publicada e carregou sem erro, mas o botão de publicação não foi
acionado. A reconciliação permanece sujeita à autorização separada de cada
exercício.

### 5.3 Recuperação de publicação com HTTP 201 e `Location` incompatível

Em 24/09/2026, a publicação de 2027 criou no PNCP os três PCAs individuais e
respondeu HTTP 201, mas o backend classificou os resultados como incertos por
não reconhecer o prefixo absoluto retornado. O inventário posterior comprovou
os candidatos exatos: UASG 205/sequencial 2, UASG 207/sequencial 3 e UASG
210/sequencial 4. Depois do vínculo assistido, o fluxo normal concluiu as três
unidades como `NENHUMA_ACAO`, sem nova escrita PNCP.

Esse caso demonstra por que um resultado incerto não pode ser repetido: a
escrita já havia surtido efeito. A recuperação correta é observar, comprovar a
procedência, vincular de modo protegido e só então reconciliar pelo fluxo
normal.

A correção 7A aceita o prefixo oficial estrito
`https://pncp.gov.br/pncp-api` para extrair o sequencial em produção. Ela não
aceita URL livre: HTTPS, host, caminho, CNPJ, exercício, sequencial positivo e
ausência de query ou fragmento continuam obrigatórios. Se surgir outro formato,
o log registra somente uma categoria sanitizada, nunca o `Location` bruto.

A correção evita que futuras publicações válidas com esse formato caiam na
mesma recuperação assistida. Ela não altera os vínculos já recuperados, não
executa chamada externa e não autoriza repetir as publicações de 2027.

O pacote candidato local é
`build/pca-pncp-sprint94-correcao-7a.tar.gz`, SHA-256
`2d1760221bd5cd0d080827c7483c41d1de8215ad3f83663ced504caf4c8b88a3`.
Ele contém somente o backend autocontido `pca-pncp-backend/`; a Blank não mudou
nesse microciclo. Construção e verificação local não autorizam transferência,
instalação nem nova execução funcional.

Após autorização específica em 24/09/2026, o checksum transferido foi
confirmado, o pacote foi validado como candidato e promovido por troca integral
do diretório. O backend 7A ativo permaneceu carregável pelo usuário do PHP, e a
versão anterior foi preservada para rollback. Não houve republicação da Blank
nem execução funcional para provocar nova escrita PNCP.

## 6. Matriz de recuperação validada no encerramento

| Situação observada | Conduta protegida |
| --- | --- |
| Sem efeito PNCP e sem commit Oracle | Novo diagnóstico antes de qualquer nova execução. |
| Efeito PNCP convergente e vínculo local ausente | Recuperar pelo mesmo SQL assistido e específico. |
| Efeito parcial próprio | Vincular somente com procedência comprovada e reconciliar pelo fluxo normal. |
| Candidato desconhecido ou observação inconclusiva | Preservar e bloquear. |
| Vínculo local cujo PCA esteja ausente | Exigir ausência definitiva antes do fluxo normal. |

Nunca compensar, excluir, adotar ou repetir uma escrita automaticamente.

### 6.1 Exercício de mesa

Em 24/09/2026, a matriz foi percorrida sem induzir nova falha em produção:

- **sem efeito PNCP e sem commit Oracle:** o HTTP 403 de 2026 interrompeu antes
  da inclusão, desfez a unidade local e exigiu nova fotografia antes da decisão;
- **efeito PNCP convergente e vínculo ausente:** os três HTTP 201 de 2027 foram
  observados, atribuídos pela correlação e pelos conjuntos exatos, vinculados e
  confirmados pelo fluxo normal sem repetir publicação;
- **efeito parcial próprio:** a procedência deve ser comprovada com correlação,
  intervalo temporal e conteúdo antes de qualquer vínculo; a parte desconhecida
  permanece preservada;
- **candidato desconhecido ou observação inconclusiva:** o fluxo bloqueia e não
  adota, exclui nem tenta novamente;
- **vínculo local com PCA ausente:** duas observações completas devem comprovar
  ausência definitiva antes do fluxo normal;
- **falha depois de escrita externa:** não existe compensação automática nem
  repetição cega; primeiro observar, depois recalcular a ação.

O exercício foi aprovado porque cada cenário possui condição de parada,
evidência exigida e retomada consciente, sem transformar rollback Oracle em
suposição de rollback PNCP.

## 7. Encerramento operacional

O gate e a Blank de inventário foram retirados de
`/var/www/html/teste_inventario_sprint94`; a configuração temporária
`/var/www/configuracoes/pca-pncp/sprint94.php` e os arquivos de transferência
3A/7A também foram removidos. A quarentena protegida usada durante a retirada
foi eliminada depois da conferência. O backend 7A permaneceu carregável e os
dois diretórios anteriores foram preservados exclusivamente para rollback.

O bloqueio HTTP 403 de 2026 permanece nominalmente aceito e não impede o
encerramento da sprint, mas proíbe nova reconciliação daquele exercício sem
correção da permissão, nova fotografia e autorização explícita. O exercício
2027 está reconciliado. A próxima etapa do roadmap é a Sprint 9.5.
