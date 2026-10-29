# Guia de configuração de produção

**Classificação documental:** Operacional

**Autoridade/Escopo:** preparação e recuperação do registro, do perfil, do CA bundle e dos
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
[seção 7 da arquitetura](../apoio/arquitetura.md#7-fachada-composition-root-scriptcase-e-configuração-externa).

Um **runbook** é um procedimento operacional passo a passo. Este documento é o
runbook da configuração de produção. Ele usa somente comandos locais do
servidor e deve ser executado por pessoa autorizada dentro do processo de
mudança aplicável ao ambiente.

O caminho da configuração e o nome do CA bundle podem ser versionados: eles não
são segredos e já integram o contrato operacional conhecido. Nunca registre no
Git, em chat, relatório ou saída de comando:

- login ou senha do PNCP;
- conteúdo integral do perfil de produção;
- tokens, headers de autenticação ou strings de conexão;
- valores físicos que não sejam necessários como evidência sanitizada.

Os modelos sem credenciais podem versionar os parâmetros operacionais e
mapeamentos físicos aprovados. Isso não autoriza versionar ou exibir o perfil
real completo. A [seção 10](#10-recuperar-a-configuração-externa) descreve a
recuperação e o conteúdo do backup protegido.

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

O mesmo formato está em
[`config/registro.example.php`](../../../config/registro.example.php). Em
homologação, substitua também o nome do arquivo por `perfil-homologacao.php`,
usando somente o hostname literal daquele ambiente.

## 3. Referência autorizada de homologação

O perfil de homologação foi lido de forma sanitizada durante o provisionamento
inicial. Ele possui a estrutura exata esperada por
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

O modelo [`config/perfil.example.php`](../../../config/perfil.example.php)
preserva esses parâmetros e os onze grupos de mapeamento. O mantenedor
confirmou os mapeamentos em HML e PROD em 28/10/2026; essa confirmação não é uma
inspeção independente dos bancos pelos testes locais. O modelo permite
reconstrução sem depender de outro perfil de homologação, mas exige conferência
antes da ativação. Ele não contém DDL e não recria objetos nem dados do Oracle.

## 4. Preparar o perfil de produção

Copie o modelo `config/perfil.example.php` compatível com a versão instalada
para `perfil-producao.php` no local protegido. O modelo conserva `HOMOLOGACAO`
e marca CNPJ, login e senha com `[PREENCHER]`: não é uma configuração pronta
para execução.
Confira campo a campo:

1. `ambiente` deve ser exatamente `PRODUCAO`;
2. o CNPJ institucional deve ser confirmado pela fonte responsável;
3. login e senha devem ser credenciais próprias do PNCP de produção;
4. `ca_bundle` deve apontar por caminho absoluto, ou por `__DIR__`, para
   `/var/www/configuracoes/pca-pncp/ca-bundle.crt`;
5. limites, timeouts, charset, timezone e mapeamentos partem do modelo
   versionado conferido; diferenças em relação ao ambiente devem ser
   esclarecidas com a fonte responsável, sem inferir valores;
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
certificados. Essas verificações locais não comprovam, sozinhas, a comunicação
TLS com o PNCP; qualquer smoke real depende de autorização específica.

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

O comando usa o autoload da release e os mesmos validadores do backend, como
`www-data`, sem exibir credenciais ou acessar Oracle/PNCP. Substitua o hostname
pelo valor literal de produção. O localizador é informado explicitamente ao
processo CLI; isso não configura nem comprova a variável no Apache/PHP.

```bash
sudo -u www-data env \
  PCA_PNCP_PROFILE_REGISTRY_FILE=/var/www/configuracoes/pca-pncp/registro.php \
  php -d display_errors=0 -d log_errors=0 -r '
ob_start();
set_error_handler(static function (int $nivel, string $mensagem): void {
    throw new ErrorException("Falha na validação local.", 0, $nivel);
});

try {
    if ($argc !== 4 || !in_array($argv[3], ["HOMOLOGACAO", "PRODUCAO"], true)) {
        throw new RuntimeException("Argumentos inválidos.");
    }
    require $argv[1];
    $perfil = (new Semae\Pca\Composition\CarregadorPerfilPublicacaoPca())->carregar($argv[2]);
    $tipado = new Semae\Pca\Composition\PerfilPublicacaoPca($perfil);
    new Semae\Pca\Domain\ValueObjects\Cnpj($perfil["cnpj"]);
    $valido = $tipado->getAmbiente()->value === $argv[3]
        && is_string($perfil["pncp"]["ca_bundle"])
        && is_readable($perfil["pncp"]["ca_bundle"]);
} catch (Throwable) {
    $valido = false;
} finally {
    restore_error_handler();
    ob_end_clean();
}

echo $valido
    ? "Configuração local válida para o ambiente informado.\n"
    : "ERRO: validação local da configuração falhou.\n";

exit($valido ? 0 : 1);
' -- "/var/www/html/PCA_PUBLICAR_B/pca-pncp-backend/vendor/autoload.php" \
  "SERVER_NAME_EXATO_DE_PRODUCAO" "PRODUCAO"
```

O código de saída é `0` no sucesso e `1` na falha. Em homologação, use o
autoload da release daquele ambiente, seu registro, hostname e `HOMOLOGACAO` no
último argumento. Não passe credenciais pela linha de comando. Em caso de
falha, confira os arquivos em sessão administrativa protegida, sem imprimir o
perfil ou a cadeia de exceções.

Essa validação cobre carregamento, formato, tipos, CNPJ e legibilidade do
bundle, não sua autenticidade. Complete a seção 6 e o checklist operacional.
Ela não comprova existência dos objetos Oracle, permissões ou acesso pelo DB
link, autenticação no PNCP, comunicação TLS ou visibilidade da variável no
runtime web. O registro de produção deve continuar contendo somente uma
entrada, conforme a seção 2.

### 7.4 Diagnóstico persistente de erros HTTP do PNCP

Não existe configuração adicional nem arquivo diagnóstico externo. Respostas
HTTP `3xx`, `4xx` ou `5xx` de operações diferentes de autenticação registram em
`SEGURANCA_LOG`, na mesma correlação, `metadata.diagnosticoHttp` com requisição,
resposta, conexão e cURL reproduzível. Bearer, cookies e `Set-Cookie` aparecem
como `[REDACTED]`.

Use `database/queries/seguranca_log_por_correlacao.sql` e examine a coluna
`DIAGNOSTICO_HTTP`. Como o evento pode conter o payload e a resposta integrais,
seu acesso e eventual exportação devem permanecer restritos aos
desenvolvedores e ao suporte autorizado. A presença do diagnóstico não
autoriza repetir uma escrita; confirme antes o estado PNCP e Oracle.

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

O provisionamento é aceito somente quando houver evidência sanitizada de que:

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
visibilidade efetiva da variável deve ser confirmada no runtime usado pela
aplicação, sem inferi-la a partir de um terminal isolado.

A implantação e o rollback da release seguem o
[guia de implantação e rollback](guia_de_implantacao_e_rollback.md).
Consulte a [situação operacional](situacao_operacional.md) antes de qualquer
smoke ou intervenção real.

## 10. Recuperar a configuração externa

### 10.1 O que preservar no backup protegido

Mantenha cópias recuperáveis do registro, dos perfis reais de cada ambiente e
do CA bundle aprovado, além dos trechos efetivos do `VirtualHost` ou pool
PHP-FPM que definem `PCA_PNCP_PROFILE_REGISTRY_FILE`. Registre também caminhos,
proprietário, grupo, permissões, versão da aplicação e identificação do bundle
aprovado. Configurações de servidor podem conter outras informações sensíveis;
trate o conjunto como material restrito.

O Git preserva os modelos, não as credenciais nem a cópia exata instalada.
Mantenha o backup criptografado fora do servidor de aplicação, com acesso
restrito, atualização após mudanças e teste periódico de restauração. A
definição institucional de ferramenta, destino e retenção não faz parte deste
guia. Não armazene o backup no repositório ou no webroot.

### 10.2 Sequência de recuperação

1. **Identifique a versão instalada.** Consulte a identificação e as evidências
   da release no processo de implantação. Obtenha os dois modelos da revisão
   Git correspondente, a partir de uma estação de manutenção; não presuma que
   os modelos da revisão mais recente sejam compatíveis. Se a versão não puder
   ser identificada, interrompa a reconstrução.
2. **Escolha a fonte.** Priorize um backup válido e compatível. Sem ele,
   transfira apenas os modelos para o local protegido e reconstrua conforme as
   seções 2 a 4. Prepare o diretório conforme a seção 5; copie os modelos para
   `registro.php` e `perfil-producao.php`, preenchendo hostname literal,
   `PRODUCAO`, CNPJ e credenciais próprias do ambiente obtidas da fonte
   institucional protegida. Em HML, use `perfil-homologacao.php` e
   `HOMOLOGACAO`. Não sobrescreva uma configuração válida apenas para copiar o
   modelo e não mantenha os perfis preenchidos na pasta `config/` do projeto.
3. **Confira mapeamentos e parâmetros.** Preserve os onze grupos, tipos e
   literais; confirme sua correspondência com o ambiente antes da ativação.
   Os testes locais não consultam o banco. Resolva diferenças com o responsável,
   sem usar valores dos testes como configuração operacional.
4. **Recupere o bundle.** Restaure o arquivo aprovado do backup ou obtenha-o da
   fonte institucional responsável. Confira integridade e certificados pela
   seção 6. O SHA-256 documentado identifica o conteúdo, mas não permite
   reconstruí-lo. Não elimine a verificação TLS para contornar sua ausência.
5. **Restabeleça o acesso.** Aplique e confira proprietário, grupo e permissões
   da seção 5. Reponha a configuração persistente do localizador no Apache/PHP
   pela seção 7, com validação da sintaxe e recarga controlada. Um resultado no
   terminal não comprova o runtime web.
6. **Valide sem publicar.** Execute o lint e as verificações da seção 6, depois
   o comando sanitizado da seção 7.3 com a release escolhida. Exija código de
   saída `0`; qualquer falha mantém a recuperação pendente. Complete o
   checklist da seção 8 sem reaplicar migrations já conformes.
7. **Registre e encaminhe a reativação.** Anote ambiente, versão/revisão dos
   modelos, fonte utilizada (backup ou reconstrução), data, responsável,
   identificação do bundle e verificações com seus resultados, sem valores
   secretos. A reativação segue a situação operacional e o processo de mudança
   vigente; recuperar arquivos não autoriza publicar PCA nem executar smoke
   real automaticamente.

Esse procedimento recupera a configuração da aplicação. Ele não restaura
objetos ou dados do Oracle: `database/` contém migrations específicas e ainda
não possui a baseline completa do DDL legado, conforme seu
[README](../../../database/README.md).

### 10.3 Manter a referência recuperável

Ao alterar o contrato do perfil, atualize o modelo e execute seus testes de
composição na mesma mudança. Quando mudar um mapeamento ou parâmetro
operacional aprovado, atualize a referência e sua origem; quando mudar a
configuração real, atualize também o backup protegido. Preserve a referência
Git utilizada por cada implantação. Os modelos não entram no pacote
autocontido e não substituem a configuração externa selecionada em execução.
