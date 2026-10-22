# Guia de implantação e rollback

**Classificação documental:** Operacional

**Autoridade/Escopo:** construção da release, transferência SFTP/SSH, implantação inicial,
atualização e rollback do backend PCA/PNCP

## 1. Limites e topologia

Este runbook instala o pacote autocontido em:

```text
/var/www/html/PCA_PUBLICAR_B/pca-pncp-backend/
```

A Blank `PCA_PUBLICAR_B` carrega:

```text
/var/www/html/PCA_PUBLICAR_B/pca-pncp-backend/vendor/autoload.php
```

O arquivo registro, o arquivo perfil de produção e o arquivo `ca-bundle.crt` permanecem externos em
`/var/www/configuracoes/pca-pncp/`. Nunca os inclua no pacote de release.

Este guia não autoriza publicação, acesso Oracle ou chamada ao PNCP. Abrir a
interface sem clicar em **Publicar** é apenas uma conferência visual; não é um
smoke test funcional.

## 2. Preparar a release na máquina de desenvolvimento

O preparo separa dois controles:

- **gate de qualidade:** conjunto de verificações que procura problemas no
  checkout antes da release;
- **empacotamento:** transformação de um commit aprovado em um artefato
  autocontido, verificado e pronto para transferência.

Executar esses controles manualmente não constitui, por si só, um pipeline de
CI/CD. Eles passam a integrar um pipeline quando uma ferramenta de integração
ou entrega contínua os automatiza.

### 2.1 Preparar o ambiente quando necessário

Em uma máquina nova, depois de clonar o projeto, quando `vendor/` estiver
ausente ou depois de receber alterações em `composer.json` ou `composer.lock`,
execute:

```bash
composer install
```

Esse comando instala no checkout as versões registradas no `composer.lock`,
inclui as ferramentas de desenvolvimento e gera o autoload local. Não é
necessário repeti-lo antes de toda release quando as dependências já estiverem
coerentes. Não o execute no servidor de produção; a release é autocontida.

### 2.2 Executar os gates locais de qualidade

Antes de criar o commit da release, execute:

```bash
composer validate
composer check-psr4
composer tests
```

Esses comandos, respectivamente, conferem os arquivos do Composer, validam o
carregamento PSR-4 das classes e executam a regressão local principal. Prossiga
somente se todos terminarem sem erro e avalie os avisos exibidos pelo Composer.
A variante `composer validate --strict` transforma também os avisos em código
de saída de erro; ela só deve se tornar obrigatória depois que o projeto tiver
uma decisão explícita para todos eles, inclusive a licença declarada no
`composer.json`.

Execute também as suítes contratuais locais afetadas pela mudança. Identifique
os comandos aplicáveis e confirme que não possuem I/O real no
[catálogo de comandos Composer](../apoio/catalogo_de_comandos_composer.md).
Gates reais, preflights e comandos de limpeza não fazem parte dessa regressão
de rotina e exigem contexto e autorização próprios.

### 2.3 Identificar a release por um commit limpo

Depois que os gates aplicáveis passarem, conclua as alterações e crie o commit
que identificará a release. Em seguida, execute:

```bash
git status --short
```

Prossiga somente se o comando não exibir saída. Isso significa que o
**worktree** — os arquivos da cópia de trabalho do Git — não possui alterações
ou arquivos novos pendentes. O arquivamento recusa uma release cujo conteúdo
não possa ser associado integralmente ao commit.

### 2.4 Construir, verificar e arquivar

Com o commit criado e o worktree limpo, execute:

```bash
composer package:build
```

Intenção: gerar a pasta do pacote em `build/pca-pncp-backend`, com:

* código-fonte em `src/`,
* dependências de produção em `vendor/`
* e o manifesto de integridade em `PACKAGE-MANIFEST.json`.

Execute a verificação:

```bash
composer package:verify
```

Intenção: conferir hashes, conteúdo permitido e carregamento das fachadas
públicas. Prossiga somente com o retorno `PACOTE_VALIDADO`.

Transforme o pacote validado em um único arquivo pronto para transferência:

```bash
composer package:archive
```

Intenção: exigir um worktree sem alterações, identificar a release pelo commit
do Git, criar o `.tar.gz`, conferir sua topologia e calcular seu **checksum**.
O comando recusa sobrescrever uma release com o mesmo nome. Registre o caminho
e o SHA-256 exibidos. O nome segue o padrão
`pca-pncp-backend_YYYYMMDD_COMMIT.tar.gz`; a saída `gerado_em` preserva também
a hora e o fuso da geração.

Para executar as três etapas anteriores em sequência, use:

```bash
composer package:release
```

Intenção: orquestrar `package:build`, `package:verify` e `package:archive` sem
duplicar suas responsabilidades. Esse é o comando preferencial para preparar
uma nova release a partir de um commit limpo.

Não envie o checkout, `composer.json`, testes, ferramentas, perfis ou segredos.
O código da aplicação Blank/evento `onExecute` é instalado e compilado pela interface do
Scriptcase, portanto ele não faz parte do pacote do backend.

## 3. Transferir arquivos

### 3.1 Destino

Antes da primeira transferência, conceda ao usuário de implantação acesso
direto ao diretório, sem alterar seu proprietário e grupo:

```bash
sudo setfacl -m u:devteam:rwx /var/www/html/PCA_PUBLICAR_B
```

Intenção: autorizar especificamente o usuário `devteam` a criar, renomear e
remover entradas nesse diretório, mantendo o proprietário e grupo atual. Não conceda escrita irrestrita com `chmod 777` ou
`chmod o+w`.

Confira a permissão na sessão do usuário `devteam`:

```bash
if test -w /var/www/html/PCA_PUBLICAR_B; then
  echo "devteam pode escrever"
else
  echo "ERRO: devteam sem permissão de escrita"
fi
```

Prossiga somente quando o comando confirmar a permissão. Envie então o
arquivo `.tar.gz` diretamente para: `/var/www/html/PCA_PUBLICAR_B/`

### 3.2a Opção de upload usando protocolo SFTP

SFTP é o protocolo que transfere arquivos por um canal SSH criptografado. A ferramenta FileZilla é um bom programa cliente que usa esse protocolo.

### 3.2b Opção de upload usando o protocolo SSH

Instale o pacote da ferramenta para conexão remota via protocolo SSH (secure shell)

```bash
apt install openssh-client
```

O comando scp (Secure Copy) permite transferir arquivos entre sua máquina local e um servidor remoto de forma segura, via SSH, e vice-versa:

```bash
scp /caminho/do/arquivo_local.txt usuario@ip_do_servidor:/caminho/do/destino/
```

Para transferir pastas inteiras com todos os arquivos internos, adicionar a tag -r (recursivo):

```bash
scp -r /caminho/da/pasta_local usuario@ip_do_servidor:/caminho/do/destino/
```

### 3.3 Validação

Depois do envio, verifique no servidor se houve alterações no checksum:

```bash
cd /var/www/html/PCA_PUBLICAR_B
sha256sum pca-pncp-backend_DATA_COMMIT.tar.gz
```

Intenção: entrar no diretório exato e confirmar que o SHA-256 é idêntico ao
calculado na máquina de desenvolvimento. Substitua `RELEASE_ID` pelo commit.

Pare se o nome, o caminho ou o hash forem diferentes.

## 4. Implantação inicial

Confirme primeiro que o destino ainda não existe:

```bash
if [ -e /var/www/html/PCA_PUBLICAR_B/pca-pncp-backend ]; then
  echo "ERRO: destino já existe"
else
  echo "Destino livre"
fi
```

Intenção: evitar a mistura acidental da nova release com arquivos existentes.
Prossiga somente quando o comando informar `Destino livre`.

Extraia o pacote:

```bash
tar -xzf pca-pncp-backend_DATA_COMMIT.tar.gz -C /var/www/html/PCA_PUBLICAR_B
```

Intenção: criar a pasta final diretamente a partir do artefato verificado, sem
executar Composer em produção.

Aplique propriedade e permissões:

```bash
sudo chown -R devteam:www-data /var/www/html/PCA_PUBLICAR_B/pca-pncp-backend
find /var/www/html/PCA_PUBLICAR_B/pca-pncp-backend -type d -exec chmod 2750 {} +
find /var/www/html/PCA_PUBLICAR_B/pca-pncp-backend -type f -exec chmod 0640 {} +
```

Intenção: permitir administração somente pelo usuário `devteam`, leitura do grupo `www-data` pelo Apache/PHP, sem conceder escrita, e nenhum acesso aos demais usuários. O `2` de `2750` preserva o grupo nos novos diretórios.

Confira a topologia do nível principal do diretório:

```bash
sudo find /var/www/html/PCA_PUBLICAR_B/pca-pncp-backend -mindepth 1 -maxdepth 1 -printf '%f\n'
```

e conferir o autoload como o usuário do PHP:

```bash
sudo -u www-data php -r '
require "/var/www/html/PCA_PUBLICAR_B/pca-pncp-backend/vendor/autoload.php";
$ok = class_exists("Semae\\Pca\\PublicadorPcaScriptcase")
    && class_exists("Semae\\Pca\\RegistradorEventosTecnicosScriptcase");
echo $ok ? "Backend carregável\n" : "ERRO: backend inválido\n";
exit($ok ? 0 : 1);
'
```

Intenção: conferir somente arquivo `PACKAGE-MANIFEST.json`, diretórios `src` e `vendor` no topo e comprovar que o mesmo usuário do Apache consegue carregar as duas fachadas.
Pare se o primeiro comando listar qualquer outro item.

Por fim, atualize o evento `onExecute` da Blank com o arquivo versionado,
compile a aplicação no Scriptcase e abra a interface sem clicar em
**Publicar**. A ativação no menu deve ocorrer somente depois dessas conferências.

## 5. Falha e rollback da implantação inicial

Antes da ativação, uma falha apenas reprova a candidata: não compile nem
publique a Blank e corrija a causa antes de tentar novamente.

Depois da ativação, retire primeiro a Blank do menu e desative a nova release:

```bash
cd /var/www/html/PCA_PUBLICAR_B
sudo mv pca-pncp-backend pca-pncp-backend-rejeitada-RELEASE_ID
```

Intenção: interromper o carregamento da release sem apagá-la, preservando seus
arquivos para diagnóstico. A aplicação anterior não é o alvo deste rollback e
pode ser removida administrativamente; manter duas aplicações ativas criaria
ambiguidade operacional.

## 6. Atualizações futuras

Uma implantação inicial instala onde nada existia. Uma atualização troca uma
release já ativa e, por isso, precisa manter a release anterior até a nova ser
verificada.

Para atualizar, extraia primeiro em uma pasta candidata separada:

```bash
cd /var/www/html/PCA_PUBLICAR_B
sudo mkdir pca-pncp-backend-candidata-RELEASE_ID
sudo tar -xzf pca-pncp-backend_DATA_COMMIT.tar.gz \
  -C pca-pncp-backend-candidata-RELEASE_ID --strip-components=1
```

Intenção: remover somente o diretório externo gravado no `.tar.gz` e colocar
seu conteúdo na pasta candidata, sem tocar na release ativa. Aplique nela as
mesmas permissões e execute a mesma verificação de topologia e autoload,
substituindo o caminho nos comandos.

Depois, em uma janela controlada:

```bash
cd /var/www/html/PCA_PUBLICAR_B
sudo mv pca-pncp-backend pca-pncp-backend-anterior-RELEASE_ANTERIOR
sudo mv pca-pncp-backend-candidata-RELEASE_ID pca-pncp-backend
```

Intenção: trocar diretórios completos, evitando misturar arquivos de releases
diferentes. Isso reduz o intervalo de indisponibilidade, embora os dois `mv`
não formem uma única operação atômica conjunta.

Se a atualização falhar, desative a candidata e restaure a anterior:

```bash
cd /var/www/html/PCA_PUBLICAR_B
sudo mv pca-pncp-backend pca-pncp-backend-rejeitada-RELEASE_ID
sudo mv pca-pncp-backend-anterior-RELEASE_ANTERIOR pca-pncp-backend
```

Intenção: executar o rollback de release, isto é, voltar aos arquivos
executáveis anteriores. Esse rollback não desfaz transações já confirmadas no
Oracle nem efeitos já produzidos no PNCP.

## 7. Automação futura

O Gitea institucional associa `develop` à homologação e `main` à produção, mas
sua integração com este repositório foi adiada por decisão do mantenedor. A
entrega manual por SFTP permanece como o procedimento disponível enquanto essa
automação não for adotada.

Quando a automação for retomada, o pipeline de CI/CD deverá reproduzir as
mesmas etapas deste guia: testar, construir, verificar, identificar a release,
transferir o artefato e só então promover a versão entre ambientes.
