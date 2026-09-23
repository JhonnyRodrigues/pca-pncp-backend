# Guia de implantação e rollback

**Classificação documental:** Apoio

**Status:** Vigente

**Escopo:** construção da release, transferência SFTP/SSH, implantação inicial,
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

Esta sprint não autoriza publicação, acesso Oracle ou chamada ao PNCP. Abrir a
interface sem clicar em **Publicar** é apenas uma conferência visual; não é o
smoke test funcional da Sprint 9.5.

## 2. Preparar a release na máquina de desenvolvimento

Depois de criar o commit que identificará a release, execute:

```powershell
composer package:build
```

Intenção: gerar a pasta do pacote em `build/pca-pncp-backend`, com:

* código-fonte em `src/`,
* dependências de produção em `vendor/`
* e o manifesto de integridade em `PACKAGE-MANIFEST.json`.

Execute a verificação:

```powershell
composer package:verify
```

Intenção: conferir hashes, conteúdo permitido e carregamento das fachadas
públicas. Prossiga somente com o retorno `PACOTE_VALIDADO`.

Transforme o pacote validado em um único arquivo pronto para transferência:

```powershell
composer package:archive
```

Intenção: exigir um worktree sem alterações, identificar a release pelo commit
do Git, criar o `.tar.gz`, conferir sua topologia e calcular seu **checksum**.
O comando recusa sobrescrever uma release com o mesmo nome. Registre o caminho
e o SHA-256 exibidos. O nome segue o padrão
`pca-pncp-backend_YYYYMMDD_COMMIT.tar.gz`; a saída `gerado_em` preserva também
a hora e o fuso da geração.

Para executar as três etapas anteriores em sequência, use:

```powershell
composer package:release
```

Intenção: orquestrar `package:build`, `package:verify` e `package:archive` sem
duplicar suas responsabilidades. Esse é o comando preferencial para preparar
uma nova release a partir de um commit limpo.

Não envie o checkout, `composer.json`, testes, ferramentas, perfis ou segredos.
O código da aplicação Blank/evento `onExecute` é instalado e compilado pela interface do
Scriptcase, portanto ele não faz parte do pacote do backend.

## 3. Transferir arquivos

### 3.1 Usando protocolo SFTP

SFTP é o protocolo que transfere arquivos por um canal SSH criptografado. A ferramenta FileZilla é apenas um programa cliente que usa esse protocolo.

### 3.2 Usando o protocolo SSH

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

### 3.3 Destino e validação

Envie o arquivo `.tar.gz` para:

```text
/var/www/html/PCA_PUBLICAR_B/
```

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
sudo tar -xzf pca-pncp-backend_DATA_COMMIT.tar.gz -C /var/www/html/PCA_PUBLICAR_B
```

Intenção: criar a pasta final diretamente a partir do artefato verificado, sem
executar Composer em produção.

Aplique propriedade e permissões:

```bash
sudo chown -R administrator:www-data /var/www/html/PCA_PUBLICAR_B/pca-pncp-backend
sudo find /var/www/html/PCA_PUBLICAR_B/pca-pncp-backend -type d -exec chmod 2750 {} +
sudo find /var/www/html/PCA_PUBLICAR_B/pca-pncp-backend -type f -exec chmod 0640 {} +
```

Intenção: permitir administração somente pelo usuário `administrator`, leitura do grupo `www-data` pelo Apache/PHP, sem conceder escrita, e nenhum acesso aos demais usuários. O `2` de `2750` preserva o grupo nos novos diretórios.

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
entrega manual por SFTP é temporária e não bloqueia esta sprint.

Quando a automação for retomada, o pipeline de CI/CD deverá reproduzir as
mesmas etapas deste guia: testar, construir, verificar, identificar a release,
transferir o artefato e só então promover a versão entre ambientes.
