# Guia de configuração de produção

**Classificação documental:** Apoio

**Status:** Vigente

**Autoridade/Escopo:** preparação do registro, do perfil, do CA bundle e dos
pré-requisitos de configuração necessários à implantação em produção

## 1. Finalidade e limites

Este guia explica como preparar a configuração externa exigida por `DI-009` e
`DI-010` e como conferir os demais pré-requisitos da implantação. Ele não
autoriza, por si só, publicação, chamada ao PNCP, DML Oracle, instalação da
release ou smoke test de produção. Cada operação depende do processo de mudança
e da autorização do ambiente.

Este runbook responde **como provisionar** os arquivos e as permissões. Para
entender **por que** existem variável de ambiente, registro, perfil, validação,
segredos e mapeamentos Oracle, consulte a
[seção 7 da arquitetura](arquitetura.md#7-fachada-composition-root-scriptcase-e-configuração-externa).

Um **runbook** é um procedimento operacional passo a passo. Este documento é o
runbook da configuração de produção da V1. Ele usa somente comandos locais do
servidor e deve ser executado por pessoa autorizada após a pausa prevista no
plano da Sprint 9.1.

O caminho da configuração e o nome do CA bundle podem ser versionados: eles não
são segredos e já integram o contrato operacional conhecido. Nunca registre no
Git, em chat, relatório ou saída de comando:

- login ou senha do PNCP;
- conteúdo integral do perfil de produção;
- tokens, headers de autenticação ou strings de conexão;
- valores físicos que não sejam necessários como evidência sanitizada.

## 2. Topologia externa aprovada

Produção usa a seguinte estrutura, externa à release e ao webroot:

```text
/var/www/configuracoes/pca-pncp/
├── registro.php
├── perfil-producao.php
└── ca-bundle.crt
```

O registro de produção contém somente o hostname literal desse ambiente. Não
copie a entrada de homologação.

```php
<?php

return [
    'SERVER_NAME_EXATO_DE_PRODUCAO' => __DIR__ . '/perfil-producao.php',
];
```

Substitua `SERVER_NAME_EXATO_DE_PRODUCAO` pelo valor efetivamente recebido em produção, sem mudar caixa, remover espaços, criar apelidos ou inferir o ambiente.

## 3. Referência autorizada de homologação

O perfil de homologação foi lido de forma sanitizada durante o planejamento da
Sprint 9.1. Ele possui a estrutura exata esperada por
`PerfilPublicacaoPca`, credenciais não vazias, os onze grupos de mapeamento e os
seguintes parâmetros não secretos:

| Parâmetro | Valor validado |
| --- | ---: |
| Limite de payload | 8.388.608 bytes |
| Limite de resposta | 8.388.608 bytes |
| Timeout de conexão | 5 segundos |
| Timeout de login | 30 segundos |
| Timeout de leitura | 30 segundos |
| Timeout de escrita | 60 segundos |
| Tamanho da página | 100 |
| Profundidade máxima do JSON | 64 |
| Limite máximo de páginas | 100 |
| Espera padrão de retry | 250 ms |
| Teto de `Retry-After` | 5.000 ms |
| Charset ADOdb | `Windows-1252` |
| Fuso operacional | `America/Sao_Paulo` |

O `ca-bundle.crt` de referência estava existente e legível. Seu **SHA-256** —
resumo criptográfico usado para identificar exatamente o conteúdo do arquivo — é
`b7a69ed0ce1e25aa4a278dd9ef1b7d4dc1fee893be19c20cfd7e866679de61cc`.
Esse hash identifica o arquivo aprovado; ele não revela chaves privadas porque
o bundle contém certificados públicos.

## 4. Preparar o perfil de produção

Use o perfil validado de homologação apenas como referência estrutural. Prepare
o arquivo de produção no local protegido e confira campo a campo:

1. `ambiente` deve ser exatamente `PRODUCAO`;
2. o CNPJ institucional deve ser confirmado pela fonte responsável;
3. login e senha devem ser credenciais próprias do PNCP de produção;
4. `ca_bundle` deve apontar por caminho absoluto, ou por `__DIR__`, para
   `/var/www/configuracoes/pca-pncp/ca-bundle.crt`;
5. limites, timeouts, charset, timezone e mapeamentos partem do perfil já
   validado e só mudam mediante decisão explícita da fonte responsável;
6. o arquivo não recebe campos de massa, consentimento ou etapa pertencentes às
   antigas suítes contratuais.

Não copie credenciais de homologação para produção, nem mesmo
temporariamente. Não exiba o array para conferir sua estrutura.

## 5. Aplicar o princípio do menor privilégio

O **princípio do menor privilégio** significa conceder somente o acesso
necessário. O
administrador escreve; a conta ou o grupo do PHP apenas lê; demais usuários não
acessam.

No ambiente de produção confirmado, o usuário `administrator` administra os arquivos e
o Apache/PHP executa como usuário `www-data`. O diretório usa `setgid`, bit que faz novos
arquivos herdarem o grupo `www-data`, sem conceder escrita ao grupo:

```bash
sudo install -d -o administrator -g www-data -m 2750 /var/www/configuracoes/pca-pncp

sudo chown administrator:www-data /var/www/configuracoes/pca-pncp/registro.php
sudo chown administrator:www-data /var/www/configuracoes/pca-pncp/perfil-producao.php
sudo chown administrator:www-data /var/www/configuracoes/pca-pncp/ca-bundle.crt

sudo chmod 0640 /var/www/configuracoes/pca-pncp/registro.php
sudo chmod 0640 /var/www/configuracoes/pca-pncp/perfil-producao.php
sudo chmod 0640 /var/www/configuracoes/pca-pncp/ca-bundle.crt
```

Depois, confirme sem mostrar conteúdo:

```bash
namei -l /var/www/configuracoes/pca-pncp/registro.php
stat -c '%U %G %a %n' /var/www/configuracoes/pca-pncp/registro.php
stat -c '%U %G %a %n' /var/www/configuracoes/pca-pncp/perfil-producao.php
stat -c '%U %G %a %n' /var/www/configuracoes/pca-pncp/ca-bundle.crt
```

As permissões corretas são:

- diretorio 2750: administrador controla, www-data pode entrar/ler e outros não acessam;
- arquivos 640: administrador escreve, www-data apenas lê e outros não leem;

A conta efetiva do PHP deve conseguir ler os três arquivos e não deve conseguir
escrevê-los. Se o ambiente usar **ACL**, lista adicional de permissões por
usuário, ou **SELinux**, controle de segurança obrigatório do Linux, essas
políticas também precisam permitir somente essa leitura.

## 6. Validar arquivos sem acessar sistemas externos

As verificações abaixo não acessam Oracle nem PNCP:

```bash
php -l /var/www/configuracoes/pca-pncp/registro.php
php -l /var/www/configuracoes/pca-pncp/perfil-producao.php
test -s /var/www/configuracoes/pca-pncp/ca-bundle.crt
sha256sum /var/www/configuracoes/pca-pncp/ca-bundle.crt
openssl crl2pkcs7 -nocrl -certfile /var/www/configuracoes/pca-pncp/ca-bundle.crt | openssl pkcs7 -print_certs -noout
```

Os dois arquivos PHP devem passar no **lint**, a verificação de sintaxe sem
executar a aplicação. O bundle não pode estar vazio, o
SHA-256 deve corresponder ao arquivo aprovado e o OpenSSL deve conseguir ler os
certificados. A validade TLS contra o PNCP será comprovada somente no smoke
autorizado de produção, em Sprint posterior.

## 7. Tornar o localizador persistente

`PCA_PNCP_PROFILE_REGISTRY_FILE` contém somente o caminho absoluto do registro: `/var/www/configuracoes/pca-pncp/registro.php`

Um `export` feito no terminal vale apenas para aquele processo e seus filhos;
ele não altera um Apache já iniciado. Primeiro descubra como o PHP é executado:

```bash
sudo apachectl -M | grep -E 'php|proxy_fcgi'
```

- `php_module` indica **mod_php**, módulo que executa PHP dentro do Apache;
- `proxy_fcgi_module`, junto de um serviço PHP-FPM, indica **PHP-FPM**, processo
  separado ao qual o Apache encaminha os arquivos PHP;
- se nenhum modelo puder ser confirmado, pare e solicite a topologia à
  infraestrutura.

### 7.1 Apache com mod_php

Abra o arquivo do `VirtualHost` — bloco do Apache que configura esse site específico —, fora do webroot, adicione a variável de ambiente:

```bash
sudoedit /etc/apache2/sites-available/default-ssl.conf
```

Edite o arquivo do `VirtualHost`:

```apache
# Cria variavel de ambiente
SetEnv PCA_PNCP_PROFILE_REGISTRY_FILE "/var/www/configuracoes/pca-pncp/registro.php"
```

Valide antes de recarregar:

```bash
sudo apachectl configtest
```

Somente com `Syntax OK`, recarregue o Apache pelo mecanismo institucional:

```bash
sudo apache2ctl graceful
```

e confirme que o serviço permaneceu saudável:

```bash
sudo systemctl is-active apache2
```

### 7.2 Apache com PHP-FPM

No pool PHP-FPM usado pelo Scriptcase, fora do webroot, adicione:

```ini
env[PCA_PNCP_PROFILE_REGISTRY_FILE] = /var/www/configuracoes/pca-pncp/registro.php
```

Um **pool** é o conjunto de processos PHP-FPM que atende uma aplicação. Use o
pool real do Scriptcase; não altere todos os pools sem necessidade. Valide a
configuração com o binário instalado, por exemplo:

```bash
sudo php-fpm -t
sudo apachectl configtest
```

Somente depois das duas validações, recarregue primeiro o PHP-FPM e depois o
Apache pelo mecanismo institucional. Não crie página pública com `phpinfo()` ou
com o conteúdo da variável para testar a configuração.

### 7.3 Validação sanitizada no servidor

Ela carrega registro e perfil como www-data, mas não exibe credenciais nem acessa Oracle/PNCP:

```bash
sudo -u www-data php -r '
$registro = require "/var/www/configuracoes/pca-pncp/registro.php";
$caminho = $registro["SERVER_NAME_EXATO_DE_PRODUCAO"] ?? null;
$perfil = is_string($caminho) && is_file($caminho)
    ? require $caminho
    : null;

$valido =
    count($registro) === 1
    && is_array($perfil)
    && ($perfil["ambiente"] ?? null) === "PRODUCAO"
    && trim((string) ($perfil["cnpj"] ?? "")) !== ""
    && trim((string) ($perfil["pncp"]["login"] ?? "")) !== ""
    && trim((string) ($perfil["pncp"]["senha"] ?? "")) !== ""
    && is_readable((string) ($perfil["pncp"]["ca_bundle"] ?? ""));

echo $valido
    ? "Configuração de produção válida e legível\n"
    : "ERRO: configuração inválida\n";

exit($valido ? 0 : 1);
'
```

## 8. Checklist integrado antes da ativação

A configuração de produção não termina no arquivo de credenciais. Antes de
ativar a Blank ou permitir o botão **Publicar**, confira os quatro blocos a
seguir. O [guia de implantação e rollback](guia_de_implantacao_e_rollback.md)
continua sendo a fonte do procedimento detalhado de instalação e troca da
release.

### 8.1 Pacote autocontido da aplicação

Instale somente o pacote construído e validado, incluindo suas dependências de
produção, em:

```text
/var/www/html/PCA_PUBLICAR_B/pca-pncp-backend/
```

A Blank `PCA_PUBLICAR_B` deve carregar o `vendor/autoload.php` desse diretório.
Não execute `composer install` no servidor e não misture arquivos de releases
diferentes. Confira o SHA-256 do arquivo transferido, a topologia, o manifesto,
o autoload e as permissões conforme o guia de implantação.

### 8.2 Segredos e certificados fora do webroot

Mantenha `registro.php`, `perfil-producao.php` e `ca-bundle.crt` exclusivamente
em:

```text
/var/www/configuracoes/pca-pncp/
```

O administrador pode alterar esses arquivos; o usuário efetivo do Apache/PHP
deve apenas lê-los. Outros usuários não devem ter acesso. Aplique e verifique o
princípio do menor privilégio conforme as seções 5 e 6, sem imprimir o conteúdo
do perfil ou qualquer credencial.

### 8.3 Localizador persistente no runtime web

Configure `PCA_PNCP_PROFILE_REGISTRY_FILE` no runtime que realmente atende o
Scriptcase. Para `mod_php`, use o `VirtualHost` do Apache; para PHP-FPM, use o
pool correspondente. Siga a seção 7, valide a sintaxe e recarregue os serviços
de forma controlada. Um `export` no terminal não configura um Apache já em
execução.

### 8.4 Migrações obrigatórias do Oracle

O pacote autocontido não leva nem executa automaticamente scripts de banco. A
implantação deve tratar, em ordem, os requisitos versionados atuais:

1. `V202609181815__configuracao_publicacao_pca.sql`, que assegura a estratégia
   consolidada original e a UASG consolidadora;
2. `V202609181816__unidades_orcamentarias_pca.sql`, que assegura as unidades
   orçamentárias participantes e suas UASGs;
3. `V202609241700__procedimentos_publicacao_pca.sql`, que assegura os
   procedimentos `8 — RETIFICAÇÃO DE PLANO` e
   `9 — EXCLUSÃO DE PLANO NO PNCP` exigidos pela auditoria de negócio;
4. `V202609241701__estrategia_individual_publicacao_pca.sql`, que registra a
   transição posterior da estratégia para `INDIVIDUAL` sem reescrever a
   migration de baseline já aplicada.

Para cada versão, siga [`database/README.md`](../../../database/README.md):

1. calcule e registre o SHA-256 do script;
2. execute o `precheck` correspondente em `database/checks/`;
3. interrompa diante de qualquer linha divergente;
4. se o requisito já estiver integralmente conforme, não execute DML;
5. caso existam somente dados ausentes, obtenha a autorização da mudança e
   execute a migração como script completo;
6. execute o `postcheck` e exija todas as linhas conformes;
7. registre ambiente, data, executor, versão, hash, resultado e se houve DML em
   `database/deployments/` e no procedimento institucional aplicável.

Não habilite o botão **Publicar** enquanto configuração, unidades,
procedimentos de auditoria ou checks permanecerem ausentes, divergentes ou sem
evidência. A ausência dessas migrations pode ser apresentada ao usuário apenas
como falha segura da preparação; ela não substitui o preflight operacional da
implantação.

## 9. Critérios de aceite e interrupção

A etapa externa da Sprint 9.1 termina somente quando houver evidência
sanitizada de que:

- o `DocumentRoot` foi identificado e a configuração está fora dele e da
  release;
- o registro contém apenas o hostname literal de produção;
- o perfil usa `PRODUCAO` e parâmetros aprovados;
- o CA bundle é legível, válido e possui o hash esperado;
- diretório e arquivos usam `2750/640`, com leitura sem escrita pelo PHP;
- a configuração persistente passou na validação do Apache e, quando aplicável,
  do PHP-FPM;
- o pacote autocontido foi instalado no caminho esperado e teve integridade,
  topologia, autoload e permissões verificados;
- os prechecks e post-checks das migrations de configuração, unidades
  orçamentárias, procedimentos de auditoria e estratégia individual ficaram
  integralmente conformes, com registro da implantação;
- nenhum segredo foi registrado como evidência.

Interrompa sem implantar a release quando qualquer caminho, hostname,
permissão, parâmetro, hash, sintaxe ou topologia permanecer incerto. A
visibilidade efetiva da variável pelo runtime do backend será confirmada na
Sprint de implantação; a Sprint 9.1 não cria probe nem executa a aplicação.

A implantação e o rollback da release seguem o
[guia de implantação e rollback](guia_de_implantacao_e_rollback.md).
Reconciliação do legado pertence à Sprint 9.4; smoke e prontidão final, à 9.5.
