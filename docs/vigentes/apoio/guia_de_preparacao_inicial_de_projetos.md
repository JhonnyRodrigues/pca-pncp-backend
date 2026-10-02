# Guia de preparação inicial de projetos

**Classificação documental:** Apoio

**Status:** Vigente

**Autoridade/Escopo:** roteiro didático e adaptável para preparar um projeto,
desde a estação de trabalho até a primeira entrega local observável; não define
regras de negócio, arquitetura obrigatória nem versões universais de ferramentas

## 1. Finalidade e limites

Este guia responde à pergunta:

> Como transformar uma ideia inicial em um projeto versionado, compreensível,
> executável e testável sem criar estrutura ou decisões antes da necessidade?

O roteiro possui duas camadas:

- o **princípio geral** explica o resultado que qualquer projeto precisa obter;
- o **exemplo PHP/WSL** mostra uma forma concreta de obter esse resultado com
  Git, PHP, Composer, PSR-4 e PHPUnit.

WSL (*Windows Subsystem for Linux*) oferece um ambiente Linux dentro do
Windows. Composer gerencia dependências PHP, PSR-4 relaciona nomes de classes a
arquivos e PHPUnit executa testes automatizados. Esses conceitos serão
detalhados nas etapas em que passam a ser necessários.

Os exemplos não transformam PHP, WSL, DDD, Clean Architecture ou uma árvore de
diretórios específica em requisitos universais. Outro projeto pode usar outras
ferramentas desde que preserve as evidências esperadas em cada etapa.

O limite deste guia é a **primeira entrega local observável**: o menor
comportamento que uma pessoa consegue instalar, testar e executar na própria
máquina. Integração real, implantação, produção, monitoramento e suporte são
etapas posteriores.

## 2. Como usar o guia

Cada etapa distingue três tipos de orientação:

| Tipo | Significado |
| --- | --- |
| **Requisito** | Resultado mínimo necessário para considerar a etapa concluída |
| **Decisão** | Escolha que depende do produto, da equipe ou do ambiente e deve ser registrada |
| **Opcional** | Recurso útil somente quando já existe um problema ou risco que o justifique |

Os comandos usam sintaxe de shell compatível com o exemplo em WSL. Trechos
como `<nome-do-projeto>` e `X.Y` são **placeholders**: devem ser substituídos e
não são comandos prontos para copiar literalmente.

Use o checklist em ordem na primeira leitura. Em projetos futuros, retorne
somente às etapas afetadas. A preparação está concluída quando suas evidências
existem, não quando todas as ferramentas conhecidas foram instaladas.

## 3. Visão geral do percurso

1. definir a fundação do projeto;
2. preparar e conferir a estação de trabalho;
3. criar e proteger o repositório;
4. escrever a entrada cognitiva no `README.md`;
5. planejar uma documentação proporcional ao projeto;
6. decidir se haverá governança para agentes;
7. escolher a menor estrutura e arquitetura adequadas;
8. inicializar o projeto PHP e seu carregamento automático de classes
   (*autoload*);
9. criar o ambiente de testes;
10. estabelecer o conjunto mínimo de referência (*baseline*) de qualidade;
11. produzir e reproduzir a primeira entrega local.

## 4. Etapa 1 — Definir a fundação

### Por que existe

Ferramentas não esclarecem qual problema será resolvido. Sem um objetivo
mínimo, é fácil escolher arquitetura, dependências e documentos por hábito e
descobrir depois que eles não possuem consumidor.

### Ações

Registre, inicialmente no `README.md` ou em uma nota de descoberta, respostas
curtas para estas perguntas:

| Pergunta | Resultado esperado |
| --- | --- |
| Qual problema será resolvido? | Uma frase observável, sem descrever a solução técnica |
| Quem perceberá o resultado? | Usuário, sistema consumidor ou mantenedor identificado |
| Qual será a primeira entrega? | Menor comportamento útil que pode ser executado localmente |
| Qual é o tipo de produto? | Aplicação web, CLI, biblioteca, worker ou outro formato |
| Quais restrições já são reais? | Runtime, sistema hospedeiro, banco, API, prazo ou segurança |
| Quais fronteiras externas existem? | Serviços, arquivos, bancos ou hosts já confirmados |
| O que ainda não foi decidido? | Lacunas explícitas, sem valores padrão inventados |

CLI (*Command-Line Interface*) é um programa operado por comandos no terminal.
Um *worker* é um processo que recebe e executa trabalho, normalmente sem uma
interface visual direta. Esses formatos exigem pontos de entrada diferentes de
uma aplicação web.

Escolha também um **runtime canônico**: a combinação oficial de sistema,
interpretador e forma de execução usada para instalar, testar e validar o
projeto. WSL pode ser essa referência, mas não precisa ser.

**Decisão:** registre onde os comandos oficiais serão executados. Se Windows,
WSL e contêineres estiverem disponíveis, somente um deles deve ser a referência
inicial; os outros podem servir para diagnóstico ou compatibilidade.

Não detalhe camadas, classes, tabelas ou contratos externos nesta etapa sem
evidência suficiente. Uma hipótese pode ser registrada como hipótese, não como
decisão aprovada.

### Evidência de conclusão

- objetivo, usuário e primeira entrega estão descritos;
- o runtime canônico foi escolhido;
- restrições confirmadas estão separadas de hipóteses e pontos em aberto.

### Erro comum

Confundir preparação com desenho completo da solução. Criar muitas pastas e
interfaces não reduz incerteza quando ainda não se conhece o primeiro
comportamento.

## 5. Etapa 2 — Preparar a estação de trabalho

### Por que existe

Instalar ferramentas é uma responsabilidade da estação de trabalho. Configurar
como o projeto usa essas ferramentas é responsabilidade do repositório. A
separação evita que instruções locais sejam tratadas como arquivos do produto e
torna diferenças de ambiente visíveis.

### Ações gerais

1. consulte a documentação oficial da ferramenta para o sistema escolhido;
2. selecione versões ainda suportadas e mutuamente compatíveis;
3. verifique qual executável o shell realmente encontra;
4. registre no projeto apenas as restrições necessárias, não o caminho pessoal
   da instalação;
5. confira extensões ou capacidades exigidas antes de instalar dependências.

Fontes iniciais:

- [instalação do Git](https://git-scm.com/downloads);
- [instalação do WSL](https://learn.microsoft.com/windows/wsl/install), quando
  ele for o runtime escolhido;
- [versões suportadas do PHP](https://www.php.net/supported-versions.php);
- [instalação do Composer](https://getcomposer.org/download/);
- [documentação do PHPUnit](https://docs.phpunit.de/).

Versões mudam com o tempo. Escolha uma versão do PHP com suporte compatível com
a vida esperada do novo projeto e só depois selecione uma versão do PHPUnit que
declare compatibilidade com ela.

### Exemplo PHP/WSL

```bash
command -v git
git --version

command -v php
php --version
php --ini
php -m

command -v composer
composer --version
composer diagnose
```

`command -v` mostra o executável usado naquele shell. Isso ajuda a detectar o
caso em que o terminal, a IDE e o servidor executam instalações diferentes de
PHP.

**Decisão:** selecione a restrição de PHP somente depois da conferência. Um
registro genérico usa `<restrição-php-suportada>` até a escolha real ser feita.

### Evidência de conclusão

- cada ferramenta responde no runtime canônico;
- caminho e versão do PHP foram conferidos;
- extensões requeridas são conhecidas;
- a compatibilidade entre runtime e ferramentas de desenvolvimento foi
  verificada em fontes oficiais.

### Erro comum

Conferir `php --version` em um terminal e executar Composer ou a IDE com outro
PHP. O número correto em um shell não prova que todos os consumidores usam o
mesmo runtime.

## 6. Etapa 3 — Criar e proteger o repositório

### Por que existe

Git registra fotografias identificáveis da evolução do projeto. Ele permite
comparar decisões, revisar alterações e recuperar estados conhecidos. Instalar
Git na máquina não cria essa história: cada projeto ainda precisa inicializar e
proteger o próprio repositório.

### Ações

Crie a pasta, inicialize o repositório e confira a identidade que assinará os
commits:

```bash
mkdir <nome-do-projeto>
cd <nome-do-projeto>
git init

git config --get user.name
git config --get user.email
git status
```

Se a identidade precisar ser específica para esse projeto, configure-a
localmente:

```bash
git config --local user.name "<nome>"
git config --local user.email "<email>"
```

Use configuração global somente quando a mesma identidade for correta para
todos os repositórios daquela estação.

Crie o `.gitignore` antes da primeira instalação de dependências. Em um projeto
PHP, candidatos comuns são:

```gitignore
/vendor/
/build/
.phpunit.result.cache
.env
.env.*
!.env.example
/config/local/
```

Essa lista é um ponto de partida. Ignore arquivos gerados, caches e segredos;
não ignore silenciosamente código-fonte, migrations ou configurações de
referência que sejam necessárias para reproduzir o projeto.

Defina também, desde o início, uma política curta e compartilhada para arquivos
de texto:

- `.gitattributes`: normalização dos finais de linha e identificação de
  formatos binários;
- `.editorconfig`: charset, final de linha, indentação e quebra final esperados
  pelos editores compatíveis.

Um ponto de partida para projetos que adotam UTF-8, LF e indentação com quatro
espaços é:

```gitattributes
* text=auto eol=lf

*.jpg binary
*.pdf binary
*.png binary
*.zip binary
```

```editorconfig
root = true

[*]
charset = utf-8
end_of_line = lf
indent_style = space
indent_size = 4
insert_final_newline = true
```

`insert_final_newline` garante que o último registro também termine com um
delimitador de linha. Isso evita concatenações acidentais, avisos de ferramentas
e diffs marcados com `No newline at end of file`. Em um repositório existente,
introduza essa política em alteração isolada, com o estado limpo, e use
`git add --renormalize .` somente depois de revisar o alcance da conversão.

Antes da primeira fotografia, inspecione exatamente o que será versionado:

```bash
git status --short
git add README.md .gitignore .gitattributes .editorconfig
git diff --cached
git commit -m "chore: inicia o projeto"
```

**Opcional:** criar e publicar um repositório remoto. O remoto oferece
colaboração e cópia externa, mas não é necessário para provar uma primeira
entrega local.

### Evidência de conclusão

- `git status` reconhece o repositório;
- identidade e política de branch foram conferidas;
- codificação, finais de linha e indentação possuem uma política explícita;
- segredos, dependências instaladas e arquivos gerados não entram no histórico;
- existe uma fotografia inicial revisável.

### Erro comum

Executar `git add .` sem revisar o índice. O primeiro commit pode então guardar
credenciais, caches ou dependências que deveriam ser reproduzidas por comando.

## 7. Etapa 4 — Criar a entrada cognitiva

### Por que existe

O `README.md` é a porta de entrada para quem encontra o repositório sem contexto
oral. Ele reduz conhecimento tribal — informação que existe apenas na memória
de alguém — e permite descobrir rapidamente se o projeto é relevante e como
obter o primeiro resultado.

O README não precisa conter toda a documentação. Sua função é explicar o todo
e encaminhar o leitor às fontes especializadas.

### Conteúdo mínimo

| Seção | Pergunta respondida |
| --- | --- |
| Nome e resumo | O que é este projeto? |
| Problema e utilidade | Por que ele existe? |
| Estado | É experimento, desenvolvimento, produção ou material encerrado? |
| Pré-requisitos | O que precisa existir antes da instalação? |
| Instalação | Como reconstruir as dependências? |
| Execução | Como observar o comportamento local? |
| Testes e verificações | Como comprovar que o ambiente está saudável? |
| Configuração e segredos | Onde ficam e o que nunca deve ser versionado? |
| Documentação e ajuda | Qual fonte responde a cada tipo de dúvida? |

Comandos publicados no README precisam ser executáveis no runtime canônico. Se
um comando exigir credencial, banco ou serviço externo, essa condição deve
aparecer antes do comando.

### Evidência de conclusão

Uma pessoa que conhece a tecnologia, mas não participou das conversas do
projeto, consegue explicar sua finalidade e localizar os comandos iniciais sem
pedir instruções privadas.

### Erro comum

Usar o README como depósito de todas as regras e procedimentos. Isso cria uma
segunda fonte de verdade e torna o arquivo difícil de manter.

## 8. Etapa 5 — Planejar a documentação

### Por que existe

Documentação precisa de papéis claros. Sem eles, regras, hipóteses,
investigações e procedimentos temporários passam a competir entre si. Por outro
lado, copiar uma árvore grande para todo projeto produz arquivos vazios que
parecem importantes, mas não possuem conteúdo nem consumidor.

Chame essa organização de **estrutura documental** ou **esqueleto documental**.
O termo *framework* costuma designar uma base de software com mecanismos de
execução e pode gerar ambiguidade neste contexto.

### Modelo adaptável do ciclo de vida

```text
<raiz>/
├── README.md
├── AGENTS.md                         # somente se houver agentes
├── docs/
│   ├── README.md                     # índice e governança documental
│   ├── vigentes/
│   │   ├── autoritativos/            # decisões e regras oficiais
│   │   ├── apoio/                    # explicações e mapas
│   │   └── operacional/              # situação e procedimentos aplicáveis
│   ├── investigacoes/                # trabalho temporário e hipóteses
│   └── historico/                    # conhecimento substituído ou encerrado
└── .agents/                          # somente se houver continuidade por agentes
```

Essa árvore é uma referência, não uma obrigação. Um projeto pequeno pode usar
menos categorias. A estrutura é suficiente quando deixa claro:

- qual fonte decide cada assunto;
- onde registrar uma hipótese ainda não confirmada;
- onde encontrar o estado atual;
- como preservar ou retirar conteúdo substituído.

### Estados seguros para um artefato

| Estado | Uso |
| --- | --- |
| **Omitido** | O papel não é necessário no projeto atual |
| **Planejado** | A necessidade é conhecida, mas ainda não existe conteúdo confiável; pode constar apenas no índice |
| **Rascunho** | O arquivo existe, identifica lacunas e não se apresenta como fonte aprovada |
| **Vigente** | O conteúdo foi validado e responde oficialmente por seu escopo |
| **Histórico** | O conteúdo foi substituído ou encerrado e permanece apenas para consulta |

Um esqueleto em rascunho deve declarar pelo menos: finalidade, pergunta que
pretende responder, autoridade, estado, fatos conhecidos, lacunas e fontes. Não
preencha regras, valores padrão ou decisões somente para eliminar espaços
vazios.

### Matriz dos principais artefatos

| Artefato ou papel | Pergunta principal | Quando criar ou ativar | Quando omitir ou manter como rascunho |
| --- | --- | --- | --- |
| `README.md` da raiz | O que é, por que existe e como começar? | Sempre, desde a primeira fotografia | Não omitir; mantenha curto enquanto houver poucas informações |
| `docs/README.md` | Onde está cada conhecimento e qual sua autoridade? | Quando houver mais de poucos documentos ou classificações distintas | Omitir se o README da raiz ainda governa tudo sem ambiguidade |
| Contexto funcional | Quem usa o produto e em qual processo? | Quando o código depende de um processo ou vocabulário que não é óbvio | Rascunho enquanto atores e fronteiras ainda estiverem em descoberta |
| Glossário | O que significam termos recorrentes e específicos? | Quando o mesmo termo aparece em várias fontes ou causa dúvida | Omitir se só repetiria vocabulário técnico comum |
| Regras de negócio | Que comportamentos e restrições o produto deve cumprir? | Quando regras precisam ser testadas, rastreadas ou distinguidas de decisões técnicas | Rascunho sem inventar regra quando a descoberta estiver incompleta |
| Modelo de domínio | Quais conceitos, identidades, relações e invariantes existem? | Quando o domínio possui linguagem e comportamento próprios | Omitir em utilitário simples; rascunho enquanto identidades forem hipóteses |
| Arquitetura | Como responsabilidades e dependências são separadas? | Quando há mais de uma fronteira, camada ou mecanismo relevante | Manter mínima; não antecipar classes e pastas sem consumidor |
| Decisões de integração | Como o sistema conversa com recursos externos e quais riscos aceita? | Quando existe banco, API, fila, filesystem ou sistema hospedeiro | Planejado até contratos reais serem conhecidos |
| Fluxo de implementação | Como componentes colaboram durante um caso de uso? | Quando a sequência dinâmica deixou de ser trivial | Omitir quando uma função ou fluxo curto já é autoexplicativo |
| Especificação de observabilidade | Que eventos, campos e proteções são necessários? | Quando operação, auditoria ou correlação exigirem contrato estável | Planejado antes da operação; não inventar campos sem consumidor |
| Roadmap | Em qual ordem construir e que evidência libera o avanço? | Quando houver mais de um incremento ou dependências entre etapas | Um checklist no README pode bastar para trabalho curto |
| Catálogo de diretórios | Onde cada tipo de artefato deve morar? | Quando a árvore cresceu e a escolha deixou de ser evidente | Omitir se apenas repetir nomes de poucas pastas |
| Catálogo de arquivos | Para que serve cada arquivo ativo? | Quando a navegação real já ficou custosa | Não criar antecipadamente para arquivos inexistentes |
| Guia de leitura do código | Por onde estudar o fluxo implementado? | Quando o sistema já possui um caminho relevante entre vários componentes | Planejado até existirem classes reais para citar |
| Situação operacional | O que está instalado e quais ações são seguras agora? | Quando existir ambiente compartilhado ou produto instalado | “Não aplicável” antes da primeira implantação |
| Guia de configuração | Como provisionar configuração e segredos? | Quando a execução depender de parâmetros externos | Rascunho somente com parâmetros confirmados |
| Implantação e rollback | Como instalar, atualizar e voltar com segurança? | Antes da primeira implantação em ambiente compartilhado | Planejado enquanto ainda só houver execução local |
| Diagnóstico e suporte | Como interpretar falhas sem causar novos efeitos? | Quando houver operação, códigos públicos ou suporte recorrente | Planejado antes da operação real |
| Investigações | Onde hipóteses e evidências temporárias são trabalhadas? | Quando uma questão exige pesquisa sem alterar fontes vigentes | Omitir se a investigação cabe em uma issue descartável |
| Histórico | Onde preservar material substituído com valor de consulta? | Quando ocorrer a primeira substituição relevante | Não criar cópias preventivas de arquivos ainda vigentes |
| Estado de agentes | Qual foi o último avanço e qual atividade está autorizada? | Quando agentes precisam retomar trabalho extenso entre sessões | Omitir sem uso de agentes ou continuidade complexa |
| Planos e checkpoints | Como executar e retomar uma mudança complexa aprovada? | Quando uma tarefa exige incrementos, gates ou pausas | Omitir para mudanças pequenas e locais |

### Evidência de conclusão

- cada documento existente possui pergunta e autoridade próprias;
- a navegação diferencia conteúdo vigente, rascunho, investigação e histórico;
- papéis futuros possuem gatilhos, não conteúdo fictício;
- o conjunto foi reduzido ou ampliado conforme o tamanho real do projeto.

### Erro comum

Criar todos os arquivos da matriz e marcá-los como vigentes. Um arquivo vazio
com nome importante pode parecer uma decisão oficial e esconder que o assunto
ainda não foi descoberto.

## 9. Etapa 6 — Decidir sobre `AGENTS.md` e skills

### Por que existe

Agentes precisam saber como agir no repositório, mas instruções permanentes e
procedimentos especializados possuem alcances diferentes. Tratar os dois como
alternativas gera repetição ou regras concorrentes.

### Diferença de papéis

| Artefato | Finalidade | Exemplo |
| --- | --- | --- |
| `README.md` | Explicar o projeto e orientar pessoas | instalar, testar e localizar documentação |
| `AGENTS.md` | Definir instruções permanentes e transversais para agentes no repositório | fontes de autoridade, limites arquiteturais, validações e segurança |
| Skill | Encapsular um procedimento especializado e reutilizável | gerar um tipo de artefato com ferramenta, validação e formato próprios |
| Estado ou plano de agentes | Registrar continuidade temporária de uma tarefa | último incremento, bloqueios e próxima atividade autorizada |

### Regra prática

**Opcional:** só crie `AGENTS.md` se agentes realmente trabalharem no projeto.
Nesse caso, faça-o cedo e registre nele:

- fontes que devem ser lidas;
- limites de escopo e arquitetura;
- convenções de implementação;
- testes e verificações exigidos;
- ações que precisam de autorização;
- forma esperada de comunicar a entrega.

Não copie toda a documentação para `AGENTS.md`. Ele aponta para as fontes
responsáveis e explica como trabalhar com elas.

Uma skill deve surgir depois que houver um procedimento repetido,
especializado, com entrada, saída e validação reconhecíveis. Não crie uma skill
inicial apenas para completar a árvore. Ela também depende da plataforma de
agentes utilizada e não é um padrão universal de repositórios.

### Evidência de conclusão

- projetos com agentes possuem uma única fonte central de instruções
  permanentes;
- procedimentos especializados não competem com essa governança;
- projetos sem agentes não carregam artefatos sem consumidor.

### Erro comum

Distribuir a mesma regra entre `README.md`, `AGENTS.md`, prompts e skills. Quando
a regra mudar, as cópias divergem e ninguém sabe qual prevalece.

## 10. Etapa 7 — Escolher estrutura e arquitetura

### Por que existe

Código, testes, configuração e documentação mudam por razões diferentes. Uma
separação mínima torna essas responsabilidades visíveis. A quantidade de
camadas, porém, deve responder aos riscos atuais e não a um desenho copiado.

### Estrutura mínima de referência

```text
<raiz>/
├── src/                 # código de produção
├── tests/               # verificações automatizadas
├── docs/                # quando o README já não for suficiente
├── config/              # somente referências seguras e necessárias
├── public/              # apenas para aplicação web com document root
├── bin/                 # apenas para comandos executáveis
├── composer.json
├── phpunit.xml
└── README.md
```

Crie somente os diretórios usados pelo tipo de entrega escolhido. Uma
biblioteca pode não ter `public/`; uma aplicação web pode não precisar de
`bin/`; segredos nunca devem ser incluídos apenas porque existe `config/`.

### Arquitetura proporcional ao risco

| Sinal observado | Resposta possível |
| --- | --- |
| Uma regra pequena e sem integração | Classe ou função coesa com teste direto |
| Domínio com linguagem, identidade e invariantes próprias | Modelagem orientada pelo domínio, potencialmente com DDD |
| Banco, API ou framework mudam independentemente das regras | Fronteiras e direção de dependências inspiradas em Clean Architecture |
| Múltiplas implementações reais para a mesma capacidade | Contrato interno e injeção de dependência |
| Código difícil de alterar por responsabilidades misturadas | Aplicar princípios SOLID no ponto do acoplamento observado |

DDD (*Domain-Driven Design*) organiza software em torno do conhecimento do
negócio. Clean Architecture protege regras internas dos mecanismos externos.
SOLID reúne princípios para manter responsabilidades e dependências
controladas. Eles são úteis quando resolvem esses problemas; não exigem uma
classe ou interface para cada passo.

### TDD como ciclo de feedback

TDD (*Test-Driven Development*) é o desenvolvimento orientado por testes:

1. **Red:** escrever uma prova pequena que falha pelo motivo esperado;
2. **Green:** implementar o mínimo para fazê-la passar;
3. **Refactor:** melhorar o desenho mantendo a prova verde.

O primeiro teste também comprova o ambiente, mas testes seguintes devem
proteger comportamento útil, não apenas repetir `assertTrue(true)`.

### Evidência de conclusão

- código e testes possuem lugares claros;
- todo diretório criado tem consumidor atual;
- a primeira regra arquitetural protege um risco identificado;
- o primeiro comportamento pode ser desenvolvido em um ciclo TDD pequeno.

### Erro comum

Usar nomes de camadas como prova de boa arquitetura. Pastas `Domain`,
`Application` e `Infrastructure` não protegem dependências por si mesmas.

## 11. Etapa 8 — Inicializar PHP, Composer e PSR-4

### Por que existe

Composer registra as restrições do projeto, resolve pacotes e gera o
**autoloader**, o carregador que encontra uma classe quando ela é usada. Um
**namespace** organiza e distingue os nomes das classes. PSR-4 define como um
prefixo de namespace corresponde a diretórios e arquivos. Essa convenção
permite que execução, testes e ferramentas de edição localizem a mesma classe
sem `require` manual espalhado pelo código.

### Inicializar o manifesto

Execute o assistente e responda apenas com informações já decididas:

```bash
composer init
```

Depois, configure no `composer.json` a restrição de runtime e os mapeamentos.
O trecho abaixo é **esquemático**: substitua todos os placeholders antes de
validá-lo.

```json
{
    "name": "<fornecedor>/<projeto>",
    "type": "project",
    "require": {
        "php": "<restrição-php-suportada>"
    },
    "autoload": {
        "psr-4": {
            "<Fornecedor>\\<Projeto>\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    }
}
```

No mapeamento PSR-4:

- o prefixo `Fornecedor\Projeto\` corresponde a `src/`;
- os subnamespaces correspondem a subdiretórios;
- o nome e a capitalização da classe correspondem ao arquivo `.php`;
- `autoload-dev` mantém classes de teste fora do autoload de produção.

Exemplo: a classe `Fornecedor\Projeto\Cadastro\CriarUsuario` pertence a
`src/Cadastro/CriarUsuario.php` quando o prefixo acima é usado.

A especificação completa está na
[PSR-4](https://www.php-fig.org/psr/psr-4/), e o mecanismo gerado está descrito
na [documentação de autoload do Composer](https://getcomposer.org/doc/01-basic-usage.md#autoloading).

### Gerar e conferir o autoload

```bash
composer validate --strict
composer install
composer dump-autoload
php -r "require 'vendor/autoload.php'; echo 'autoload ok', PHP_EOL;"
```

Depois de instalar ou atualizar dependências, confira também os requisitos do
runtime:

```bash
composer check-platform-reqs
```

Para validar o mapeamento do próprio projeto de forma estrita:

```bash
composer dump-autoload --optimize --strict-psr
```

O modo otimizado é útil nessa verificação e em produção. Durante o
desenvolvimento diário, não é necessário mantê-lo ativo a cada nova classe.

### Composer e Intelephense

Intelephense é uma ferramenta de análise e navegação de PHP usada pelo editor.
Quando namespaces, arquivos e `composer.json` seguem o mesmo mapeamento PSR-4,
o editor consegue relacionar símbolos ao código real. Por isso, corrija o
autoload do projeto em vez de criar uma configuração paralela apenas para
silenciar o editor.

Se a IDE continuar usando outro PHP ou exibir índices antigos, confira o
workspace aberto, o executável configurado e o cache da extensão antes de
alterar namespaces corretos.

### Dependências e arquivo de lock

- dependências necessárias em produção pertencem a `require`;
- ferramentas de teste e análise pertencem a `require-dev`;
- uma aplicação deve versionar `composer.lock` para reproduzir as versões
  resolvidas;
- uma biblioteca deve decidir e documentar sua política de lock conforme a
  forma de teste e distribuição.

### Evidência de conclusão

- `composer validate --strict` aprova o manifesto;
- `vendor/autoload.php` é gerado por comando, não escrito à mão;
- uma classe em `src/` é carregada pelo namespace esperado;
- classes de teste usam o autoload de desenvolvimento;
- o editor e a linha de comando concordam sobre caminhos e namespaces.

### Erro comum

Adicionar `require` manuais ou caminhos extras à IDE para compensar um
mapeamento PSR-4 incorreto. Isso faz o editor parecer saudável enquanto a
execução real continua quebrada.

## 12. Etapa 9 — Criar o ambiente de testes

### Por que existe

Um runner de testes transforma exemplos esperados em verificações repetíveis.
O bootstrap central garante que a suíte carregue as classes da mesma forma que
a aplicação.

### Escolher e instalar PHPUnit

Consulte a documentação da versão corrente e escolha uma restrição compatível
com o PHP selecionado. No comando abaixo, substitua `X.Y` por uma versão
compatível confirmada:

```bash
composer require --dev phpunit/phpunit:"^X.Y"
./vendor/bin/phpunit --version
```

Não reutilize automaticamente a versão de outro projeto. O runtime suportado e
a versão do PHPUnit evoluem de forma independente.

### Configurar o bootstrap

Um `phpunit.xml` inicial pode conter:

```xml
<?xml version="1.0" encoding="UTF-8"?>

<phpunit
    bootstrap="vendor/autoload.php"
    colors="true"
>
    <testsuites>
        <testsuite name="Projeto">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

O atributo `bootstrap` carrega o autoloader antes dos testes. A suíte aponta
para `tests/`, e o namespace desse diretório deve coincidir com `autoload-dev`.

### Primeiro teste técnico

Crie uma classe mínima em `src/` e um teste que confirme que ela é encontrada
pelo namespace. Esse teste deve falhar quando o arquivo, a capitalização ou o
mapeamento estiver errado; `assertTrue(true)` não comprova o autoload.

Execute:

```bash
./vendor/bin/phpunit
```

Depois que o primeiro comportamento real instanciar essa classe pelo mesmo
autoload, remova o teste puramente técnico se ele não acrescentar uma proteção
distinta. O objetivo é evitar uma coleção permanente de testes de infraestrutura
sem comportamento.

### Evidência de conclusão

- PHPUnit é dependência de desenvolvimento;
- `phpunit.xml` usa `vendor/autoload.php`;
- o teste falha diante de um erro real de PSR-4 e passa após a correção;
- um único comando executa a suíte local.

### Erro comum

Baixar um executável avulso sem registrar versão, comando e compatibilidade. A
suíte funciona em uma máquina, mas não pode ser reconstruída por outra pessoa.

## 13. Etapa 10 — Estabelecer o baseline essencial

### Por que existe

Um **baseline** é o conjunto mínimo de condições usado como referência para
decidir se o projeto está saudável. Antes da primeira entrega, ele precisa de
provas que detectem configuração inválida, sintaxe quebrada, autoload
incoerente e comportamento regressivo. Adicionar todas as ferramentas possíveis
aumenta o custo sem necessariamente reduzir o risco atual.

### Verificações mínimas

| Verificação | Exemplo PHP/WSL | Evidência |
| --- | --- | --- |
| Segredos e gerados protegidos | `git status --short --ignored` | arquivos sensíveis e reproduzíveis não serão commitados |
| Manifesto válido | `composer validate --strict` | estrutura e metadados essenciais estão coerentes |
| Runtime compatível | `composer check-platform-reqs` | PHP e extensões atendem aos pacotes instalados |
| Sintaxe PHP | `find src tests -name '*.php' -print0 \| xargs -0 -r -n1 php -l` | todos os arquivos examinados passam no parser |
| PSR-4 estrito | `composer dump-autoload --optimize --strict-psr` | classes próprias respeitam o mapeamento |
| Comportamento | `./vendor/bin/phpunit` | a suíte termina verde |

O caractere `|` no comando de lint é um pipe do shell. Ele envia cada caminho
encontrado para `php -l`; o exemplo pressupõe `find` e `xargs` disponíveis no
WSL. Em outro runtime, substitua-o por um script equivalente e documentado.

Quando os comandos estabilizarem, dê a eles nomes simples em `scripts` do
Composer. Por exemplo, um script `tests` permite executar `composer tests` sem
memorizar o caminho do runner. Só crie o script depois de existir um comando
real para ele executar.

### Mapa para ferramentas posteriores

| Ferramenta | Problema que resolve | Sinal para adotar | Custo inicial |
| --- | --- | --- | --- |
| Formatador | diferenças mecânicas de estilo e diffs ruidosos | revisões discutem espaços e quebras em vez de comportamento | dependência, configuração e possível reescrita em lote |
| Análise estática | tipos e fluxos suspeitos sem executar o programa | base cresce, contratos ficam complexos ou erros escapam aos testes | configuração de nível, baseline e falsos positivos iniciais |
| Cobertura | identifica trechos não exercitados pela suíte | risco relevante permanece sem evidência de teste | extensão de cobertura, tempo e risco de perseguir porcentagem sem valor |
| Hook de Git | executa verificações antes de commit ou push | equipe esquece comandos locais essenciais | instalação local e tratamento de bypass ou lentidão |
| Integração contínua | repete verificações em ambiente independente | existe remoto e colaboração ou entrega automatizada | configuração do provedor, minutos de execução e manutenção |

Esses itens são decisões, não requisitos da primeira entrega local. Ao adotar
um deles, registre o problema concreto, o comando oficial e a condição de
sucesso.

### Evidência de conclusão

- as cinco classes de risco essenciais possuem uma verificação executável;
- todos os comandos estão documentados no runtime canônico;
- ferramentas adiadas possuem gatilhos compreensíveis, não pendências vagas.

### Erro comum

Instalar formatador, analisador, cobertura, hooks e CI no mesmo incremento sem
conhecer cada falha separadamente. Quando algo quebra, o mantenedor não sabe
qual ferramenta protege qual risco.

## 14. Etapa 11 — Produzir a primeira entrega local

### Por que existe

Autoload e testes provam a infraestrutura, mas não provam que o projeto já
entrega algo observável. A primeira entrega deve atravessar o caminho mínimo
entre uma intenção e um resultado, sem depender de produção.

### Escolher o menor comportamento

| Tipo de projeto | Exemplo de comportamento observável | Execução possível |
| --- | --- | --- |
| CLI | receber uma entrada simples e imprimir um resultado | `php bin/app.php <entrada>` |
| Biblioteca | chamar uma API pública mínima e obter um valor | teste e pequeno script de exemplo |
| Web | responder localmente a uma rota sem recurso externo real | `php -S 127.0.0.1:8000 -t public` |
| Worker | processar uma mensagem local e apresentar o resultado | comando de execução única com entrada controlada |

O servidor embutido iniciado por `php -S` serve apenas para desenvolvimento e
verificação local; ele não é um servidor de produção.

O comportamento deve ser pequeno, mas real. Desenvolva-o com
`Red → Green → Refactor`, começando pelo resultado visível e introduzindo apenas
as abstrações necessárias.

### Reproduzir em uma instalação limpa

Faça a prova em um diretório descartável, usando apenas arquivos versionados e
comandos documentados. Um repositório remoto não é obrigatório; um clone local
usa somente a fotografia commitada:

```bash
diretorio_teste="$(mktemp -d)"
git clone . "$diretorio_teste/projeto"
cd "$diretorio_teste/projeto"

composer install
composer validate --strict
composer check-platform-reqs
composer dump-autoload --optimize --strict-psr
./vendor/bin/phpunit
```

Depois execute o comando específico do comportamento escolhido. O diretório
temporário pode ser descartado após a inspeção; não faça a prova apagando
`vendor/` ou arquivos da área de trabalho principal.

Se o projeto ainda não possui uma fotografia commitada, faça uma cópia
controlada e registre essa limitação. A reprodução definitiva deve usar o
histórico versionado.

### Checklist de saída

- [ ] objetivo, usuário e primeira entrega estão claros;
- [ ] runtime canônico e restrições de versão estão registrados;
- [ ] ferramentas respondem no mesmo ambiente;
- [ ] Git protege segredos, gerados e dependências reproduzíveis;
- [ ] README explica finalidade, instalação, execução, testes e ajuda;
- [ ] cada documento existente possui papel e estado claros;
- [ ] `AGENTS.md` só existe se houver agentes, e skills só possuem procedimento real;
- [ ] estrutura e arquitetura respondem a necessidades observadas;
- [ ] Composer valida, instala e gera o autoload;
- [ ] namespace, diretório, arquivo e Intelephense concordam;
- [ ] PHPUnit carrega `vendor/autoload.php` e executa a suíte;
- [ ] lint, PSR-4 estrito e testes passam;
- [ ] o menor comportamento pode ser observado manualmente;
- [ ] uma instalação limpa reproduz o mesmo resultado.

### Evidência de conclusão

Uma pessoa consegue partir somente da fotografia versionada, executar os
comandos documentados e observar o primeiro comportamento sem conhecimento
privado nem correção manual do ambiente.

### Erro comum

Chamar de “entrega” uma suíte verde que ainda não possui comportamento útil, ou
considerar reproduzível uma execução que depende de arquivos locais não
versionados e não provisionados.

## 15. Resumo dos princípios reutilizáveis

1. prepare primeiro o problema, depois as ferramentas;
2. escolha um runtime canônico e verifique o executável real;
3. diferencie instalação da máquina e configuração do projeto;
4. use o README como entrada e os documentos especializados como fontes;
5. atribua autoridade e estado a cada documento;
6. crie esqueletos e abstrações somente com finalidade e consumidor claros;
7. use `AGENTS.md` para governança transversal e skills para procedimentos
   especializados já reconhecidos;
8. trate DDD, Clean Architecture e SOLID como respostas a riscos, não como
   decoração estrutural;
9. faça Composer, testes e editor obedecerem ao mesmo PSR-4;
10. adote ferramentas de qualidade uma a uma, com problema e evidência claros;
11. encerre a preparação com um comportamento reproduzível, não apenas com
    arquivos configurados.
