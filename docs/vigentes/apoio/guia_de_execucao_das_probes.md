# Guia de execução das probes e gates das Sprints 5.1, 5.9, 8.2 e 8.3

**Classificação documental:** Apoio

**Status:** Vigente

**Autoridade/Escopo:** roteiro operacional didático; não cria autorização,
regra de negócio ou configuração nova

## 1. Antes de começar

Uma **probe** é uma verificação executada de propósito contra um recurso real.
Ela não faz parte da suíte comum e pode abrir transações ou realizar escritas
controladas. O **preflight** valida a configuração antes desse acesso.

Use a probe ou o gate correspondente ao que precisa comprovar:

| Sprint | Finalidade | Forma de execução real |
| --- | --- | --- |
| 5.1 | Descobrir e validar os contratos básicos do Oracle e do PNCP. | Scriptcase para Oracle; Composer para PNCP. |
| 5.9 | Exercitar diretamente os adapters Oracle e PNCP de produção. | Scriptcase para Oracle; Composer para PNCP. |
| 8.2 | Exercitar a composição Oracle compartilhada pelo Composition Root final. | Scriptcase para Oracle. |
| 8.3 | Exercitar a composição PNCP usada pelo Composition Root final. | Composer, exclusivamente no treinamento. |

Não troque entrypoints entre sprints. Em especial, o relatório Oracle da 8.2
possui `gate: ORACLE_COMPOSITION_SCRIPTCASE`; `ORACLE_SCRIPTCASE` identifica a
probe antiga da 5.1.

Antes de qualquer execução real:

1. obtenha autorização para o ambiente e para a massa dedicada;
2. confirme que está em homologação ou treinamento, nunca em produção;
3. instale as dependências com `composer install` e implante a mesma revisão de
   código que será testada;
4. copie o `config.example.php` da sprint para um arquivo externo e preencha-o
   sem alterar o modelo de chaves;
5. mantenha credenciais, CNPJ, nomes físicos, caminhos e dados da massa fora do
   repositório, da Blank e dos relatórios compartilhados;
6. execute primeiro o preflight e pare se ele for recusado;
7. salve somente o JSON sanitizado retornado pela probe.

Em produção, configurações e perfis devem ficar fora da release e do webroot,
com leitura restrita ao processo PHP. Um ambiente local controlado pode exigir
uma disposição temporária diferente, mas essa exceção não deve ser copiada para
produção.

## 2. Como informar as variáveis ao PHP

Os nomes abaixo guardam apenas caminhos; os segredos continuam dentro dos
arquivos protegidos:

| Sprint | Variável obrigatória |
| --- | --- |
| 5.1 | `PCA_SPRINT51_CONFIG_FILE` |
| 5.9 | `PCA_SPRINT59_CONFIG_FILE` |
| 8.2 | `PCA_SPRINT82_CONFIG_FILE` e `PCA_PNCP_PROFILE_REGISTRY_FILE` |
| 8.3 | `PCA_SPRINT83_CONFIG_FILE` e `PCA_PNCP_PROFILE_REGISTRY_FILE` |

O servidor deve preferencialmente provisionar essas variáveis no processo PHP.
No FastCGI, uma variável definida no terminal não chega necessariamente ao
worker que atende o Scriptcase.

Somente em desenvolvimento controlado, é possível simplificar a Blank com
`putenv()` antes de carregar a probe:

```php
putenv('PCA_SPRINT82_CONFIG_FILE=/CAMINHO/EXTERNO/sprint82.php');
putenv('PCA_PNCP_PROFILE_REGISTRY_FILE=/CAMINHO/EXTERNO/registro.php');
```

Repita essas linhas em toda requisição: cada acesso pode cair em outro worker
FastCGI. Não coloque senha, token ou conteúdo do perfil em `putenv()` e remova
esse arranjo ao implantar em produção.

## 3. Estrutura de uma Blank protegida

No evento `onExecute` da Blank, carregue o autoloader e o arquivo da sprint,
chame somente uma função e serialize o retorno:

```php
header('Content-Type: application/json; charset=utf-8');

putenv('VARIAVEL_DE_AMBIENTE=caminho/do/arquivo/arquivo.php');

$backendRoot = 'exemplo/opt/www/pca-pncp-backend';

require '/CAMINHO/DA/RELEASE/vendor/autoload.php';
require '/CAMINHO/DA/RELEASE/tests/Contract/Sprint82/scriptcase_probe.php';

$login = (string) [usr_login]; // variável global de sessão do Scriptcase
$servidor = (string) ($_SERVER['SERVER_NAME'] ?? '');

$modosPermitidos = [
    'preflight',
    'commit',
    'adapters',
	'lock_holder',
	'lock_expect_blocked',
	'lock_expect_available',
    // altere manualmente somente para um modo permitido
];
$modo = isset($_GET['mode']) ? (string) $_GET['mode'] : '';
if (!in_array($modo, $modosPermitidos, true)) {
    echo json_encode(
        [
            'sprint' => '8.2',
            'gate' => 'ORACLE_SCRIPTCASE',
            'confirmado' => false,
            'falha' => [
                'codigo' => 'MODO_RECUSADO',
                'mensagem' => 'Modo recusado.',
            ],
            'close_invocado' => false,
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    return;
}

// $registro = (string) getenv('PCA_PNCP_PROFILE_REGISTRY_FILE');
// $resultado = [
//     'registro_configurado' => $registro,
//     'registro_existe' => is_file($registro),
//     'registro_legivel' => is_readable($registro),
// ];
// if (is_file($registro) && is_readable($registro)) {
//     $indice = require $registro;
//     $perfil = is_array($indice) ? ($indice[$serverName] ?? null) : null;

//     $resultado['servidor_encontrado'] = is_string($perfil);
//     $resultado['perfil_configurado'] = $perfil;
//     $resultado['perfil_existe'] = is_string($perfil) && is_file($perfil);
//     $resultado['perfil_legivel'] = is_string($perfil) && is_readable($perfil);
// }
// echo json_encode($resultado,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$relatorio = executar_integracao_oracle_sprint_82(
    $this->Db,
    $login,
    $servidor,
    $modo
);

try {
    $relatorio = executar_integracao_oracle_sprint_82(
        $this->Db,
        $loginAutenticado,
        $serverName,
        $modo
    );
} catch (\Throwable $e) {
    $relatorio = [
        'sprint' => '8.2',
        'gate' => 'ORACLE_SCRIPTCASE',
        'confirmado' => false,
        'falha' => [
            'codigo' => 'EXECUCAO_FALHOU',
            'mensagem' => 'Falha durante a execução do gate.',
        ],
        'close_invocado' => false,
    ];
}

// Serialização sanitizada da evidência.
$json = json_encode($relatorio,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($json === false) {
    echo '{"sprint":"8.2","gate":"ORACLE_SCRIPTCASE","confirmado":false,"falha":{"codigo":"SERIALIZACAO_FALHOU","mensagem":"Falha ao serializar relatorio sanitizado."},"close_invocado":false}';
    return;
}

echo $json;
```

Esse exemplo é da 8.2. Nas seções seguintes estão as assinaturas das outras
sprints. `$this->Db` é a conexão ADOdb emprestada pelo Scriptcase; a probe não
pode fechá-la. Configure `[usr_login]` como variável global de sessão. Login
vazio deve ser corrigido no Scriptcase, não inventado pelo backend.

Para evitar que qualquer pessoa escolha uma operação pela URL, não leia o modo
diretamente de `GET` ou `POST`. Durante o diagnóstico, altere a string
hardcoded, publique e execute a Blank protegida. Para a prova de contenção,
use dois navegadores ou duas sessões independentes; duas abas podem ser
serializadas pela mesma sessão PHP.

## 4. Sprint 5.1 — descoberta do ambiente

Comece pelo modelo
[`tests/Contract/Sprint51/config.example.php`](../../../tests/Contract/Sprint51/config.example.php).
Ele contém duas seções independentes: `oracle` e `pncp`.

Execute os testes sem I/O real e o preflight desejado:

```bash
composer contract:tests
composer contract:preflight:oracle
composer contract:preflight:pncp
```

`composer contract:preflight` valida as duas seções de uma vez. Para o PNCP,
habilite escritas somente quando a massa de treinamento e a limpeza estiverem
autorizadas. A execução real é explícita:

```bash
composer contract:pncp
```

No Scriptcase, carregue `tests/Contract/Sprint51/scriptcase_probe.php` e use:

```php
$relatorio = executar_probe_oracle_sprint_51($this->Db, 'discovery');
```

Os modos Oracle permitidos são:

- `discovery`: runtime, schema, binds, transação, restauração da fixture e
  consultas agregadas configuradas;
- `lock_holder`: mantém o lock pelo tempo configurado;
- `lock_expect_blocked`: deve encontrar contenção enquanto o holder está ativo;
- `lock_expect_available`: deve adquirir o lock após sua liberação.

Essa probe é um diagnóstico legado do ambiente. Ela não prova o Composition
Root final nem deve ser usada como substituta da 5.9 ou da 8.2.

## 5. Sprint 5.9 — contratos dos adapters

Copie e preencha
[`tests/Contract/Sprint59/config.example.php`](../../../tests/Contract/Sprint59/config.example.php).
Use massa Oracle dedicada e o ambiente PNCP canônico de homologação.

Valide primeiro:

```bash
composer adapters:tests
composer contract:adapters:tests
composer contract:adapters:preflight:oracle
composer contract:adapters:preflight:pncp
```

No Scriptcase, carregue `tests/Contract/Sprint59/scriptcase_probe.php` e chame:

```php
$relatorio = executar_contratos_adapters_oracle_sprint_59(
    $this->Db,
    (string) [usr_login],
    'adapters'
);
```

Os modos são `adapters`, `lock_holder`, `lock_expect_blocked` e
`lock_expect_available`. O modo `adapters` altera temporariamente confirmação,
estado e auditoria dentro de uma transação e precisa confirmar que o rollback
restaurou a massa. Um resultado falso ou uma restauração não comprovada exige
interrupção e inspeção da massa antes de repetir.

Se `adapters` falhar, somente em desenvolvimento controlado habilite
temporariamente `PCA_SPRINT59_DEBUG_ORACLE=true`. A etapa e a mensagem
sanitizada ajudam no diagnóstico. Remova a variável ao terminar; os modos de
lock não expõem detalhes técnicos adicionais.

### Resíduo PNCP conhecido

Se uma execução PNCP anterior deixou um sequencial pertencente à massa de
teste, preencha somente `owned_residue_sequence` e execute nesta ordem:

```bash
composer contract:adapters:preflight:pncp
composer contract:adapters:pncp:cleanup
```

A limpeza não publica e só pode remover o alvo coerente com a massa dedicada.
Depois de `confirmado: true`, `ausencia_definitiva_confirmada: true` e
`publicacao_executada: false`, apague o sequencial da configuração, repita o
preflight e só então execute:

```bash
composer contract:adapters:pncp
```

Nunca tente “adivinhar” um sequencial para limpar.

## 6. Sprint 8.2 — composição Oracle final

A 8.2 usa três arquivos externos com responsabilidades diferentes:

```text
registro.php
└── SERVER_NAME exato -> caminho absoluto de perfil.php

perfil.php
├── ambiente e CNPJ
├── configuração PNCP
└── mapeamentos Oracle de produção

sprint82.php
├── autorização explícita de escrita
├── SERVER_NAME esperado
└── ano, UO, sequencial, marcador e duração da massa de prova
```

O nome do perfil é livre; não precisa conter `homologacao`. O campo
`ambiente` dentro dele é que define o ambiente, e o registro seleciona um
perfil pelo `SERVER_NAME` exato. Cada perfil representa um ambiente completo;
não coloque configurações de homologação e produção no mesmo array.

Crie `sprint82.php` a partir de
[`tests/Contract/Sprint82/config.example.php`](../../../tests/Contract/Sprint82/config.example.php).
O perfil deve ter exatamente as chaves aceitas por
`PerfilPublicacaoPca`; use como referência um perfil já validado da implantação.
Se a implantação ainda possui apenas a configuração da 5.9, a reorganização é:

| Destino no perfil | Origem equivalente na configuração 5.9 |
| --- | --- |
| `ambiente` | `pncp.ambiente` |
| `cnpj` | `pncp.cnpj` |
| `pncp.login`, `pncp.senha`, `pncp.ca_bundle` | Campos de mesmo nome em `pncp` |
| limites de payload e resposta | Campos de mesmo nome em `pncp` |
| `timeout_*_segundos` | Campos `timeout_*` correspondentes em `pncp` |
| `pncp.tamanho_pagina` | `pncp.page_size` |
| `pncp.limite_maximo_paginas` | `pncp.max_pages` |
| `oracle.timezone` | `oracle.timezone` |
| `oracle.mapeamentos` | `oracle.mappings` completo |

O perfil final também exige `profundidade_maxima_json`,
`espera_retry_padrao_ms` e `teto_retry_after_ms`. Esses campos não existem no
modelo da 5.9: obtenha os valores aprovados para a implantação, sem inventar
defaults. Campos exclusivos da massa de teste — ano, UO, sequencial e mensagem
de auditoria — ficam em `sprint82.php`, não no perfil.

O registro tem formato simples:

```php
<?php

return [
    'servidor.exemplo.interno' => '/CAMINHO/EXTERNO/perfil.php',
];
```

Todos os caminhos devem ser absolutos e legíveis pelo processo PHP. O
carregador valida todas as entradas do registro, então qualquer perfil citado
nele precisa existir e estar legível, mesmo que outro servidor seja o alvo da
execução. 

Se `ca_bundle` estiver preenchido, seu arquivo também precisa existir
no servidor.

Valide os contratos e o preflight, que não acessa Oracle nem PNCP:

```bash
composer contract:oracle82:tests
composer contract:oracle82:preflight
```

Depois carregue `tests/Contract/Sprint82/scriptcase_probe.php` na Blank e use a
assinatura mostrada na seção 3. O `serverName` deve vir de
`$_SERVER['SERVER_NAME']`, e o login, da sessão autenticada.

Execute primeiro `commit` e depois `adapters`:

- `commit` separa o commit técnico do provisionamento da transação controlada
  principal. Deve confirmar lock, commit principal, liberação e readquisição do
  lock, conexão reutilizável, nenhuma alteração de negócio e nenhum `Close()`;
- `adapters` prova binds, leituras tipadas, mesma sessão, confirmação temporária,
  estado e auditoria atômicos, rollback integral e conexão reutilizável.

O modo `adapters` exige que a massa comece com confirmação pendente. Ano e UO
devem apontar para a massa dedicada já validada na 5.9; o sequencial temporário
e o marcador de auditoria da 8.2 devem ser novos.

Em uma falha de `commit` ou `adapters`, somente no ambiente controlado, use
temporariamente `PCA_SPRINT82_DEBUG_ORACLE=true` para receber tipo, mensagem
sanitizada e etapa. Remova-o depois da investigação.

### Prova de contenção em duas sessões

1. Na Sessão A, execute `lock_holder` e mantenha a página aguardando.
2. Enquanto A aguarda, execute `lock_expect_blocked` na Sessão B.
3. B deve confirmar `situacao_inicio: BLOQUEADA` e a tradução de `ORA-00054`.
4. Aguarde A terminar e confirmar a liberação do lock.
5. Na Sessão B, execute `lock_expect_available`.
6. B deve confirmar `situacao_inicio: INICIADA` e liberar o lock ao final.

Os três relatórios precisam indicar `close_invocado: false`. 

A 8.2 monta o
Composition Root real sem I/O e exercita a mesma subcomposição Oracle usada por
ele. Ela não percorre a fachada e o UseCase completos, pois isso alcançaria a
autenticação PNCP; essa integração pertence às sprints posteriores.

## 7. Sprint 8.3 — composição PNCP no treinamento

A 8.3 usa um arquivo de autorização e massa dedicado, além do mesmo registro de
perfis da composição final:

```text
registro.php
└── SERVER_NAME exato -> caminho absoluto de perfil.php

perfil.php
├── ambiente, CNPJ e credenciais PNCP
├── TLS, timeouts e limites defensivos
└── demais configurações da composição final

sprint83.php
├── autorização explícita e SERVER_NAME esperado
├── ano, data, UASG e três IDs lacunados da massa
├── classificação e valores do item de treinamento
└── sequencial de resíduo autorizado, normalmente null
```

Crie a configuração a partir de
[`tests/Contract/Sprint83/config.example.php`](../../../tests/Contract/Sprint83/config.example.php).
Ela e o registro devem permanecer fora da release e do webroot. No Git Bash,
informe caminhos absolutos com barras normais:

```bash
export PCA_SPRINT83_CONFIG_FILE='C:/caminho/externo/sprint83.php'
export PCA_PNCP_PROFILE_REGISTRY_FILE='C:/caminho/externo/registro.php'
```

Antes de qualquer rede, execute:

```bash
composer contract:pncp83:tests
composer contract:pncp83:preflight
```

O primeiro comando é inteiramente local. O segundo lê e valida a configuração,
mas deve declarar `oracle_acessado: false` e `pncp_acessado: false`. Preflight
aprovado não garante autenticação nem autoriza um gate real.

### Ciclo nominal

Com autorização explícita, massa dedicada **sem candidato preexistente**,
`residuo_sequencial_autorizado = null` e execução serial, use:

```bash
composer contract:pncp83:nominal
```

Esse gate:

1. autentica uma vez, 
2. publica dois IDs lacunados, 
3. força paginação com página de tamanho 1, 
4. inclui o terceiro item, 
5. exclui um item, 
6. exclui o PCA e
7. confirma ausência definitiva.

Portanto, ele **cria, altera e exclui dados reais
no treinamento**. O relatório aprovado informa o sequencial recebido e
excluído em `sequencial_pca_recebido_excluido`, confirma a limpeza e apresenta
o hash SHA-256 do JSON sanitizado.

Se houver falha depois da primeira escrita, o runner tenta limpeza best-effort.
Não repita imediatamente o gate quando a mensagem indicar possível resíduo:
primeiro localize o efeito por leitura e confirme que pertence à execução.

### Escrita incerta deliberada

Este modo exige autorização própria, mesmo depois de o nominal ter sido
aprovado:

```bash
composer contract:pncp83:uncertain
```

O runner envia uma única publicação real e descarta deliberadamente sua
resposta de sucesso (na header: location devolve o `sequencialPca`). Ele deve produzir `RESULTADO_INCERTO`:

1. realizar POST e não repeti-lo,
2. localizar o efeito somente por leitura,
3. excluir o PCA atribuído à execução e
4. confirmar ausência.

Ambiguidade, leitura inconclusiva ou limpeza não confirmada interrompe o avanço.

### Limpeza isolada

Somente depois de identificar com segurança um resíduo da massa, preencha seu
sequencial positivo em: arquivo `sprint83.php`, chave `residuo_sequencial_autorizado`, obtenha autorização e execute novamente o preflight antes de:

```bash
composer contract:pncp83:cleanup
```

A limpeza não publica e não aceita alvo inferido. Depois de confirmar ausência,
volte o campo para `null` e repita o preflight antes de qualquer novo gate.
Nunca altere candidatos preexistentes desconhecidos.

As classificações resumidas e os riscos de todos os scripts estão no
[catálogo de comandos Composer](catalogo_de_comandos_composer.md).

## 8. Como interpretar e compartilhar o resultado

Um gate só está aprovado quando o retorno do teste é `confirmado=true` e todas as verificações esperadas para o modo também são verdadeiras. O hash SHA-256 identifica o
conteúdo sanitizado recebido, mas não transforma um relatório recusado em aprovação.

Antes de compartilhar o JSON, confirme que ele não contém:

- senha, token ou cabeçalho `Authorization`;
- caminho de arquivo, hostname, login ou CNPJ;
- SQL, nome físico de tabela ou dados da massa;
- corpo completo de requisição ou resposta externa.

Se houver qualquer um desses dados, não envie o relatório. Corrija a origem da
exposição e gere uma nova evidência sanitizada.

## 9. Diagnóstico rápido

| Sintoma | Verificação mais provável |
| --- | --- |
| Variável “não foi informada” | No FastCGI, configure-a no processo PHP ou repita o `putenv()` na requisição controlada. |
| “Arquivo externo não está legível” | Confirme caminho absoluto, permissões, todos os perfis do registro e o `ca_bundle`. |
| `allow_writes=true` obrigatório | Altere somente após autorização e somente para a massa de homologação. |
| Servidor divergente ou não encontrado | Compare literalmente o `SERVER_NAME` recebido com a configuração e a chave do registro. |
| Perfil de produção recusado | Use um perfil cujo campo `ambiente` seja `HOMOLOGACAO`; não contorne a proteção. |
| Login autenticado obrigatório | Configure a variável global de sessão do Scriptcase e confirme que ela chega preenchida. |
| Massa deve possuir confirmação pendente | Corrija ano/UO para a massa dedicada ou restaure-a antes de repetir `adapters`. |
| Modo recusado | Confira a lista de modos da sprint e se a Blank carregou o arquivo correto. |
| `lock_expect_blocked` encontra lock disponível | Garanta que a Sessão A ainda aguarda e que A e B são sessões PHP independentes. |
| Falha na preparação | A operação Oracle ainda pode não ter começado; corrija configuração, perfil, login ou entrada antes de investigar locks. |

Ao terminar, remova variáveis de debug, reverta autorizações temporárias que não
devem permanecer, confirme a restauração da massa e arquive somente os
relatórios sanitizados aprovados.
