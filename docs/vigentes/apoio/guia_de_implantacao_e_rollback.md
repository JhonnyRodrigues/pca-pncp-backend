# Guia de implantação e rollback

**Classificação documental:** Apoio

**Status:** Vigente

**Escopo:** construção da release, transferência SFTP, implantação inicial,
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

Execute depois de criar o commit que identificará a release:

```powershell
composer package:build
```

Intenção: gerar `build/pca-pncp-backend` com `src/`, dependências de produção em
`vendor/` e o manifesto de integridade.

```powershell
composer package:verify
```

Intenção: conferir hashes, conteúdo permitido e carregamento das fachadas
públicas. Prossiga somente com o retorno `PACOTE_VALIDADO`.

```powershell
$releaseId = git rev-parse --short HEAD
tar.exe -czf "build/pca-pncp-backend-$releaseId.tar.gz" -C build pca-pncp-backend
Get-FileHash "build/pca-pncp-backend-$releaseId.tar.gz" -Algorithm SHA256
```

Intenção: identificar a release pelo commit do Git, criar um único arquivo para
transferência e calcular seu **checksum**, o resumo usado para detectar qualquer
alteração durante o envio. Registre o nome e o SHA-256 exibido.

Não envie o checkout, `composer.json`, testes, ferramentas, perfis ou segredos.
O código do evento `onExecute` é instalado e compilado pela interface do
Scriptcase; ele não faz parte do pacote do backend.

## 3. Transferir por SFTP

No FileZilla, use o protocolo SFTP e envie o `.tar.gz` para:

```text
/var/www/html/PCA_PUBLICAR_B/
```

SFTP é o protocolo que transfere arquivos por um canal SSH criptografado. O
FileZilla é apenas o programa cliente que usa esse protocolo.

Depois do envio, no servidor:

```bash
cd /var/www/html/PCA_PUBLICAR_B
sha256sum pca-pncp-backend-RELEASE_ID.tar.gz
```

Intenção: entrar no diretório exato e confirmar que o SHA-256 é idêntico ao
calculado na máquina de desenvolvimento. Substitua `RELEASE_ID` pelo commit.

Pare se o nome, o caminho ou o hash forem diferentes.

## 4. Implantação inicial

Confirme primeiro que o destino ainda não existe:

```bash
test ! -e /var/www/html/PCA_PUBLICAR_B/pca-pncp-backend
```

Intenção: evitar a mistura acidental da nova release com arquivos existentes.
O comando termina sem saída quando o destino está livre.

Extraia o pacote:

```bash
sudo tar -xzf pca-pncp-backend-RELEASE_ID.tar.gz -C /var/www/html/PCA_PUBLICAR_B
```

Intenção: criar a pasta final diretamente a partir do artefato verificado, sem
executar Composer em produção.

Aplique propriedade e permissões:

```bash
sudo chown -R administrator:www-data /var/www/html/PCA_PUBLICAR_B/pca-pncp-backend
sudo find /var/www/html/PCA_PUBLICAR_B/pca-pncp-backend -type d -exec chmod 2750 {} +
sudo find /var/www/html/PCA_PUBLICAR_B/pca-pncp-backend -type f -exec chmod 0640 {} +
```

Intenção: permitir administração por `administrator`, leitura pelo Apache/PHP
do grupo `www-data` e nenhum acesso aos demais usuários. O `2` de `2750`
preserva o grupo nos novos diretórios.

Confira a topologia e o autoload como o usuário do PHP:

```bash
sudo find /var/www/html/PCA_PUBLICAR_B/pca-pncp-backend -mindepth 1 -maxdepth 1 -printf '%f\n'
sudo -u www-data php -r '
require "/var/www/html/PCA_PUBLICAR_B/pca-pncp-backend/vendor/autoload.php";
$ok = class_exists("Semae\\Pca\\PublicadorPcaScriptcase")
    && class_exists("Semae\\Pca\\RegistradorEventosTecnicosScriptcase");
echo $ok ? "Backend carregável\n" : "ERRO: backend inválido\n";
exit($ok ? 0 : 1);
'
```

Intenção: conferir somente `PACKAGE-MANIFEST.json`, `src` e `vendor` no topo e
comprovar que o mesmo usuário do Apache consegue carregar as duas fachadas.
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
sudo tar -xzf pca-pncp-backend-RELEASE_ID.tar.gz \
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
