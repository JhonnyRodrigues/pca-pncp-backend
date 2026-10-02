# Evidências da Sprint 5.9 — contratos dos adapters

**Classificação documental:** Histórico

**Data:** 26/08/2026

**Última atualização:** 28/08/2026

**Autoridade/Escopo:** evidências históricas não normativas da implementação e das
validações controladas da Sprint 5.9

## 1. Resultado atual

A suíte contratual opt-in e os dois runners reais foram implementados sem
alterar regras de negócio, modelo, Ports ou APIs públicas. O Roadmap recebeu
somente a correção nominal de “âncoras” para “controles de concorrência”, sem
mudança de ordem, escopo ou comportamento. A suíte controlada está aprovada,
assim como as regressões diretamente relacionadas.

A Sprint 5.9 está concluída. O gate PNCP foi aprovado no ambiente real de
treinamento após o tratamento estrito da incompatibilidade de `Location`
registrada na seção 3.1, e o gate Oracle foi aprovado no Scriptcase real com
conexão ADOdb emprestada. A Fase 5 permanece aberta pelos bloqueios da seção 6.

## 2. Ambiente e validações controladas

- sistema de execução: workspace local do repositório;
- data: 26/08/2026;
- runtime: PHP 8.4.7;
- suíte de adapters: 468 testes e 1.779 asserções, aprovada;
- contratos controlados da Sprint 5.9: 54 testes e 149 asserções, aprovados;
- contratos legados não reais: 24 testes e 51 asserções, aprovados;
- suíte padrão: 807 testes e 2.924 asserções, aprovada;
- lint: arquivos PHP afetados pela refatoração, aprovados;
- Composer: manifesto válido, com apenas o aviso preexistente de licença;
- PSR-4 estrito: autoload otimizado com 1.734 classes, aprovado;
- `git diff --check`: aprovado.

## 3. Gate PNCP preparado

O runner usa somente classes concretas de produção para transporte cURL,
serialização, autenticação, leitura, escrita e observabilidade. O preflight:

- aceita somente `AmbientePncp::HOMOLOGACAO`, cuja URL pertence ao código de
  produção e não à configuração externa;
- exige autorização explícita de escrita e arquivo de configuração fora do
  repositório;
- recusa opt-in para 1.001 itens e mantém CNPJ alfanumérico fora do gate;
- exige IDs distintos e lacunados, data coerente e CA bundle absoluto e
  legível quando informado;
- aceita apenas ausência inicial, sequencial criado pela execução ou resíduo
  cujo sequencial tenha sido explicitamente autorizado;
- realiza limpeza best-effort e não confirma o gate sem ausência definitiva;
- verifica que credenciais, CNPJ, UASG e token não aparecem nos eventos nem no
  relatório sanitizado.

### 3.1 Incompatibilidade externa observada em 28/08/2026

O gate real recebeu HTTP `201`, criou o PCA de sequencial 24 e retornou um
`Location` absoluto no host oficial de homologação, porém sob o prefixo
`/pncp-api`. O parser esperava a base de serviços documentada `/api/pncp` e,
corretamente para o contrato então vigente, classificou o resultado como
incerto sem extrair o sequencial pelo corpo.

O manual PNCP vigente continua definindo `/api/pncp` como base dos serviços e
os endpoints relativos `/v1/orgaos/{cnpj}/pca/...` para publicação, leitura e
exclusão. A correção mantém essa base no transporte e reconhece também o
prefixo `/pncp-api` somente quando emitido por HTTPS no host fixo de
homologação. Produção, host divergente, URL configurável, query string,
fragmento ou identidade divergente continuam recusados.

O PCA 24 foi submetido à limpeza isolada. A consulta específica respondeu que
o PCA não estava cadastrado, e a listagem independente do mesmo órgão e ano
respondeu HTTP `200` sem candidatos da massa dedicada. A ausência definitiva
foi confirmada sem nova publicação e sem necessidade de `DELETE`.

### 3.2 Gate PNCP aprovado em 28/08/2026

Após remover o resíduo autorizado da configuração externa, o preflight foi
aprovado e o gate real executou o ciclo completo no PNCP de treinamento. Foram
confirmados autenticação, TLS, sessão opaca, publicação, observação, inclusão,
exclusão de item, exclusão integral e ausência definitiva. O relatório marcou
`confirmado`, `limpeza_confirmada` e `eventos_sanitizados` como verdadeiros.

A execução usou PHP 8.4.24 no WSL e produziu o hash SHA-256
`a2043402655b87762b2c3b7677cdee333e948961c3993bca2f28a3ccb30e7344`.
CNPJ alfanumérico e lote único de 1.001 itens permaneceram não comprovados e
não bloqueantes, conforme o escopo aprovado.

## 4. Gate Oracle preparado

O entrypoint de Scriptcase
`executar_contratos_adapters_oracle_sprint_59($db, $login, $mode)` é exclusivo
da suíte contratual e não constitui fachada nem Composition Root de produção.
Ele oferece os modos:

- `adapters`: valida bind, mesma sessão, leituras concretas, visibilidade de
  confirmação/estado/auditoria dentro da transação e restauração por rollback;
- `lock_holder`: mantém o lock da chave anual durante a janela configurada;
- `lock_expect_blocked`: exige tradução imediata de `ORA-00054` para resultado
  bloqueado;
- `lock_expect_available`: exige nova aquisição após a liberação.

Uma guarda delega a conexão ADOdb emprestada e transforma qualquer tentativa
de `Close()` em falha, sem repassar o fechamento à conexão do Scriptcase.
Relatórios contêm somente estados, códigos e mensagens seguros.

### 4.1 Origem remota da descrição do grupo

Em 27/08/2026, a estrutura física informada pelo mantenedor confirmou que a
descrição do grupo de materiais não pertence a `SGI.PAC_DEMANDA`. Ela deve ser
obtida de `CEBISIS.MT_GRUPOS_MATERIAIS@READ_ORCL`, relacionando
`MGM.CODIGO = D.GRUPO_CODIGO` e projetando `MGM.DESCRICAO` no alias interno
`DESCRICAO_GRUPO`.

O mapeamento controlado passou a validar separadamente schema, tabela, DBLINK e
colunas remotas, permitindo somente o DBLINK `READ_ORCL`. Nenhuma expressão SQL
livre ou conversão de tipo foi incorporada.

Naquela etapa, a atualização foi validada somente por testes locais de montagem
do SQL e do contrato de configuração. O gate Oracle/Scriptcase posterior está
registrado na seção 4.6.

A compatibilidade de tipos foi confirmada no Oracle real em 27/08/2026:
SGI.PAC_DEMANDA.GRUPO_CODIGO é VARCHAR2(5), enquanto
<CEBISIS.MT_GRUPOS_MATERIAIS@READ_ORCL.CODIGO> é VARCHAR2. A consulta de
validação do LEFT JOIN remoto foi executada com sucesso e retornou 10 registros.
Não foi necessária conversão explícita de tipo.

### 4.2 Estrutura física da Unidade Orçamentária

Em 27/08/2026, a estrutura informada pelo mantenedor confirmou que
`SGI.PAC_PLANOS.FK_UNIDADE_ORCAMENTARIA` referencia
`SGI.PAC_UNIDADES_ORCAMENTARIAS.ID_UNIDADE_ORCAMENTARIA`. Esse identificador,
por sua vez, referencia `SGI.RH_LOTACOES.COD_LOTACAO`.

O mapeamento de descoberta passou a representar separadamente o cadastro da UO
e o cadastro de Lotações. `IDENTIDADE_UO` vem de
`PAC_UNIDADES_ORCAMENTARIAS.ID_UNIDADE_ORCAMENTARIA`, enquanto `ID_LOTACAO`
vem de `RH_LOTACOES.COD_LOTACAO`. Os valores físicos são atualmente iguais,
mas os aliases e os conceitos do Domain permanecem distintos.

Pela regra vigente, `DESCRICAO_LOTACAO` e `DESCRICAO_UO` são aliases distintos
projetados de `RH_LOTACOES.NOME_LOTACAO`. Código, descrição e condição ativa da
UASG continuam pertencendo a `PAC_UNIDADES_ORCAMENTARIAS`. A UASG
consolidadora permanece exclusivamente na configuração da estratégia e não foi
incorporada à identidade da UO nem replicada no cadastro individual.

Os dois relacionamentos usam `LEFT JOIN`; assim, ausência de cadastro ativo ou
de Lotação chega ao mapeamento como dado ausente e é rejeitada com falha segura,
em vez de desaparecer da descoberta. Essa estrutura foi comprovada somente por
testes locais de montagem do SQL, aliases, binds, cardinalidade e dados
inválidos. Naquela etapa, o gate Oracle/Scriptcase ainda estava pendente; sua
execução posterior está registrada na seção 4.6.

### 4.3 Configuração corrente e estratégia adotada

Em 27/08/2026, a estrutura informada pelo mantenedor confirmou que
`SGI.PAC_CONFIGURACOES` representa a estratégia corrente por linhas de
`CHAVE`, `VALOR` e `DESCRICAO`. A chave
`ESTRATEGIA_PUBLICACAO_PLANOS` aceita `INDIVIDUAL` ou `CONSOLIDADO`. Na linha
`UASG_CONSOLIDADORA`, `VALOR` fornece o código e `DESCRICAO` fornece a
descrição da UASG; o consolidado não realiza lookup em
`PAC_UNIDADES_ORCAMENTARIAS`.

As UASGs individuais continuam vindo do cadastro ativo de
`PAC_UNIDADES_ORCAMENTARIAS`, relacionado ao Plano por
`ID_UNIDADE_ORCAMENTARIA = FK_UNIDADE_ORCAMENTARIA`. A dependência
`cadastro_uasg` e sua classe exclusiva foram removidas porque não restou outro
consumidor legítimo; o adapter reutiliza o mapeamento físico de UOs já empregado
na descoberta de Planos.

A configuração corrente permanece mutável. A estratégia efetivamente adotada
é lida e escrita exclusivamente em
`PAC_PLANOS.ESTRATEGIA_ADOTADA_PUBLICACAO` e `PAC_PLANOS.UASG_ADOTADA`. Os dois
campos podem permanecer nulos antes da adoção, mas estado parcial é rejeitado.
Como a projeção adotada conserva apenas o código da UASG, a descrição necessária
ao objeto interno consolidado é reutilizada da configuração já consultada na
execução; em consulta isolada, ela somente é aceita quando a linha
`UASG_CONSOLIDADORA` ainda possui o mesmo código. Divergência falha de forma
fechada e não altera a estratégia persistida.

O DDL não foi executado. Caso as colunas ainda não existam, a alteração manual
recomendada é:

```sql
ALTER TABLE SGI.PAC_PLANOS ADD (
    ESTRATEGIA_ADOTADA_PUBLICACAO VARCHAR2(20),
    UASG_ADOTADA VARCHAR2(20)
);

ALTER TABLE SGI.PAC_PLANOS ADD CONSTRAINT CK_PAC_PLANOS_ESTRATEGIA
CHECK (
    ESTRATEGIA_ADOTADA_PUBLICACAO IS NULL
    OR ESTRATEGIA_ADOTADA_PUBLICACAO IN ('INDIVIDUAL', 'CONSOLIDADO')
);

ALTER TABLE SGI.PAC_PLANOS ADD CONSTRAINT CK_PAC_PLANOS_ESTR_UASG
CHECK (
    (ESTRATEGIA_ADOTADA_PUBLICACAO IS NULL AND UASG_ADOTADA IS NULL)
    OR
    (ESTRATEGIA_ADOTADA_PUBLICACAO IS NOT NULL AND UASG_ADOTADA IS NOT NULL)
);
```

Antes da aplicação, é necessário confirmar a ausência das colunas e dos nomes
de constraints no schema alvo. Esta refatoração foi validada somente por testes
locais e não altera o estado dos gates reais.

### 4.4 Controles técnicos de concorrência

Em 27/08/2026, a nomenclatura técnica anterior “âncoras” foi substituída por
“controles de concorrência”. O mapeamento passou a se chamar
`MapeamentoControlesConcorrenciaOracle`, e a configuração contratual exige a
chave `controles_concorrencia`, sem aceitar `ancoras` como alias.

A representação física confirmada é `SGI.PAC_CONTROLES_CONCORRENCIA`, com a
chave composta semanticamente por `ESCOPO`, `ANO_EXECUCAO` e
`IDENTIFICADOR_ALVO`. A tabela não representa entidade de negócio; suas linhas
permanentes apenas permitem serializar unidades de trabalho concorrentes.

A alteração foi exclusivamente semântica. Foram preservados o provisionamento
por `INSERT ... WHERE NOT EXISTS`, a aquisição por `FOR UPDATE NOWAIT`, a ordem
dos binds, a tradução de `ORA-00054`, as transações curtas de provisionamento e
a conexão ADOdb emprestada. Nenhum DDL ou DML real foi executado e o gate
Oracle/Scriptcase permanece pendente.

### 4.5 Estado operacional da publicação em `PAC_PLANOS`

Em 27/08/2026, o mapeamento de estado operacional foi alinhado à fonte física
exclusiva `SGI.PAC_PLANOS`. A estratégia adotada e o estado operacional
permanecem conceitos distintos: `ESTRATEGIA_ADOTADA_PUBLICACAO` contém somente
`INDIVIDUAL` ou `CONSOLIDADO`, `UASG_ADOTADA` identifica o destino escolhido,
e `ESTADO_PUBLICACAO` contém somente `NAO_PUBLICADO`, `PUBLICADO` ou
`EXCLUIDO`.

`SEQUENCIAL_PUBLICACAO_PNCP` é o identificador externo positivo devolvido pelo
PNCP. Ele não representa ID ou versão do Plano, UO ou UASG e permanece
armazenado quando o estado passa a `EXCLUIDO`, como referência histórica da
publicação removida.

A leitura projeta todas as versões do escopo e rejeita estratégia/UASG
parcialmente preenchidas, literais ou sequenciais inválidos e versões
incompatíveis. A gravação altera somente `ESTADO_PUBLICACAO` e
`SEQUENCIAL_PUBLICACAO_PNCP`: estratégia e UASG não são sobrescritas. No modo
individual, leitura e gravação usam ano e UO; no consolidado, usam todas as
linhas do exercício. Esta última representação replica o estado conjunto nas
linhas de `PAC_PLANOS` daquele ano e pressupõe que a leitura transacional tenha
confirmado uma única estratégia consolidada e uma única UASG. Divergências são
tratadas como conflito; o adapter não inventa uma tabela de instâncias externas.

`SGI.PAC_CONTROLES_CONCORRENCIA` continua separado e contém somente controles
de exclusividade técnica. Ele não armazena estado nem sequencial de publicação.

As duas colunas operacionais ainda não existem no schema informado. Nenhum DDL
foi executado. Após conferir a ausência das colunas e dos nomes das constraints,
o DDL manual recomendado é:

```sql
ALTER TABLE SGI.PAC_PLANOS ADD (
    ESTADO_PUBLICACAO VARCHAR2(20),
    SEQUENCIAL_PUBLICACAO_PNCP NUMBER
);

ALTER TABLE SGI.PAC_PLANOS ADD CONSTRAINT CK_PAC_PLANOS_ESTADO_PUB
CHECK (
    ESTADO_PUBLICACAO IS NULL
    OR ESTADO_PUBLICACAO IN ('NAO_PUBLICADO', 'PUBLICADO', 'EXCLUIDO')
);

ALTER TABLE SGI.PAC_PLANOS ADD CONSTRAINT CK_PAC_PLANOS_ESTADO_SEQ
CHECK (
    (ESTADO_PUBLICACAO IS NULL AND SEQUENCIAL_PUBLICACAO_PNCP IS NULL)
    OR (ESTADO_PUBLICACAO = 'NAO_PUBLICADO' AND SEQUENCIAL_PUBLICACAO_PNCP IS NULL)
    OR (
        ESTADO_PUBLICACAO IN ('PUBLICADO', 'EXCLUIDO')
        AND SEQUENCIAL_PUBLICACAO_PNCP > 0
    )
);
```

Antes da aplicação, o DBA ainda deve confirmar tipo e precisão adequados ao
sequencial retornado pelo PNCP. Essa observação registra a situação anterior ao
gate real; a execução consolidada posterior está descrita na seção 4.6.

### 4.6 Gate Oracle aprovado em 28/08/2026

O gate foi executado em aplicação Blank do Scriptcase com conexão ADOdb
emprestada. O modo `adapters` confirmou binds, mesma sessão, leituras tipadas,
visibilidade da escrita dentro da transação, atomicidade entre confirmação,
estado e auditoria, restauração da massa por rollback e reutilização posterior
da conexão. `Close()` não foi invocado e detalhes sensíveis não foram exibidos.

Os modos concorrentes também foram aprovados: `lock_holder` manteve e liberou o
lock, `lock_expect_blocked` recebeu a situação `BLOQUEADA` com tradução de
`ORA-00054`, e `lock_expect_available` adquiriu novamente o lock após a
liberação. Os hashes internos dos relatórios sanitizados foram:

- `adapters`: `70a141ddd6b04e2dbc84f9069a412e5260a49eb4cefaeaec62a6fde24240a2e7`;
- `lock_holder`: `c6d23d297c1ce53113b3da8028edfa9528a8417f9dc9deb8ac59f9b1af168115`;
- `lock_expect_blocked`: `6fdda68cea6f723a2d73ec222a9d7b7fad730bd936d65f75c204f472c36e9e8c`;
- `lock_expect_available`: `b351c6235c9d51957ed9c40858633feccd8871b66f4a2307c52900d3635765c0`.

## 5. Relatórios externos e hashes

| Gate | Execução real | SHA-256 do JSON sanitizado | Estado |
| --- | --- | --- | --- |
| Oracle/Scriptcase | quatro modos aprovados em 28/08/2026 | `6a5f63d50fbd2af121700027dc5a00735bc264cff4dfbe54bbf1cb1fa5c87648` | **APROVADO** |
| PNCP treinamento | ciclo completo e limpeza confirmada em 28/08/2026 | `a2043402655b87762b2c3b7677cdee333e948961c3993bca2f28a3ccb30e7344` | **APROVADO** |

Os JSON integrais permanecem ignorados pelo Git. Os hashes aprovados registram
os relatórios sanitizados dos dois gates e autorizam concluir a Sprint 5.9.

## 6. Limitações e bloqueios

- CNPJ alfanumérico e chamada única com 1.001 itens continuam não comprovados e
  não bloqueantes para esta sprint; não existe opt-in da Sprint 5.9 para
  ampliá-los.
- A Sprint 5.9 está concluída com os dois gates reais aprovados.
- A Fase 5 continua aberta porque
  `EvidenciaCancelamentoHomologadoPort` não possui adapter concreto.
- Outras pendências necessárias ao encerramento da Fase 5 ainda devem ser
  inventariadas em investigação própria, após autorização do mantenedor.
- A Fase 6 e a Sprint 6.1 permanecem bloqueadas até o encerramento explícito da
  Fase 5 e planejamento e autorização próprios.
