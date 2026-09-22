# Guia de configuração de produção

**Classificação documental:** Apoio

**Status:** Vigente

**Autoridade/Escopo:** procedimento operacional da Sprint 9.1 para preparar e
provisionar o registro, o perfil e o CA bundle de produção sem implantar a
release nem acessar Oracle ou PNCP

## 1. Finalidade e limites

Este guia explica como preparar a configuração externa exigida por `DI-009` e
`DI-010`. Ele não autoriza publicação, acesso Oracle, chamada ao PNCP, instalação
da release ou smoke test de produção.

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

## 8. Critérios de aceite e interrupção

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
- nenhum segredo foi registrado como evidência.

Interrompa sem implantar a release quando qualquer caminho, hostname,
permissão, parâmetro, hash, sintaxe ou topologia permanecer incerto. A
visibilidade efetiva da variável pelo runtime do backend será confirmada na
Sprint de implantação; a Sprint 9.1 não cria probe nem executa a aplicação.

Monitoramento e retenção pertencem à Sprint 9.2; implantação e rollback da
release, à 9.3; reconciliação do legado, à 9.4; smoke e prontidão final, à 9.5.
