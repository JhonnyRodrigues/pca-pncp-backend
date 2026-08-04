# Registro de Decisões de Integração

**Classificação documental:** Autoritativo  
**Status:** Vigente  
**Autoridade/Escopo:** decisões DI-001 a DI-010, contratos externos, persistência técnica e operação

## 1. Finalidade e autoridade

Este documento é a fonte autoritativa das decisões de integração do backend
PCA com Oracle, PNCP e Scriptcase. Ele consolida as decisões DI-001 a DI-010 em
formato ADR-lite - Architecture Decision Record (Registro de Decisão de Arquitetura) e registra somente o entendimento vigente depois da
reconciliação incremental dos checkpoints de planejamento.

A autoridade documental é dividida por assunto:

- `docs/vigentes/autoritativos/regras_de_negocio.md` contém a redação
  normativa das regras de negócio;
- este documento contém contratos de integração, escolhas arquiteturais,
  mecanismos técnicos, limitações operacionais e gates contratuais;
- os demais documentos temáticos devem referenciar regras e decisões pelos
  respectivos IDs, sem manter redações concorrentes;
- os checkpoints permanecem como fontes históricas do raciocínio;
- as sínteses das DI são fontes independentes de conferência, não substitutos
  dos checkpoints.

Uma divergência entre este documento e outro texto sobre integração bloqueia a
implementação da decisão afetada até que a fonte apropriada seja reconciliada.

## 2. Precedência dos checkpoints

As fontes de planejamento são incrementais e devem ser lidas nesta ordem:

1. `.agents/planejamento/checkpoints/handoff_planejamento_roadmap_v2.md`;
2. `.agents/planejamento/checkpoints/handoff_planejamento_roadmap_v2_complementar_di006_di008.md`;
3. `.agents/planejamento/checkpoints/handoff_planejamento_roadmap_v2_continuacao_03_di008_di010.md`.

O checkpoint posterior prevalece somente sobre o estado, a hipótese ou a
pendência que ele efetivamente revisa. A redação anterior continua válida nos
demais pontos.

As principais revisões incrementais consolidadas aqui são:

- a fonte física do `sequencialPca` passou a ser exclusivamente `PAC_PLANOS`;
- o baseline detalhado de conteúdo foi retirado da V1;
- PCA conhecido e definitivamente ausente pode ser recriado;
- a estratégia anual é congelada na primeira execução válida;
- DI-008, DI-009 e DI-010 foram encerradas como aceitas condicionalmente.

## 3. Convenção de status

| Status | Significado |
|---|---|
| `Proposta` | Decisão ainda apresentada para análise |
| `Em análise` | Alternativas e consequências ainda estão sendo avaliadas |
| `Aceita` | Decisão aprovada e vigente |
| `Aceita condicionalmente` | Direção aprovada, condicionada a gates contratuais objetivos |
| `Substituída` | Decisão anterior preservada para rastreabilidade, mas não vigente |
| `Bloqueada` | Decisão sem avanço possível até ocorrer fato externo identificado |

`Rejeitada` é classificação de alternativa dentro de uma DI e não constitui,
por si só, status da decisão principal. Hipóteses apoiadas por evidência e
pendências contratuais devem ser registradas nos campos de evidências e gates,
sem serem apresentadas como decisões confirmadas.

## 4. Índice

| ID | Título | Status | Gate principal |
|---|---|---|---|
| `DI-001` | Contrato de publicação e sincronização do PNCP | Aceita | Compatibilidade de `ID_DEMANDA` como `numeroItem` |
| `DI-002` | Autoridade, reconciliação e vínculo externo | Aceita | Contratos de identidade e ausência definitiva |
| `DI-003` | Modos de publicação e unidade externa | Aceita | Persistência e validação da estratégia na DI-008 |
| `DI-004` | Transações, exclusividade e falhas parciais | Aceita | Revisão se volume ou concorrência mudarem |
| `DI-005` | Estado operacional e confirmação versionada | Aceita | Coerência física validada pela DI-008 |
| `DI-006` | Auditoria e procedimentos PNCP | Aceita | Contratos Oracle de auditoria e contexto |
| `DI-007` | Portas necessárias na Application | Aceita | UseCase completo demonstrado com mocks |
| `DI-008` | Mecanismo Oracle | Aceita condicionalmente | ADOdb/OCI8, schema, sessão, datas e decimais |
| `DI-009` | Transporte HTTP | Aceita condicionalmente | Runtime cURL/TLS e suíte no PNCP de treinamento |
| `DI-010` | Composition Root e Scriptcase | Aceita condicionalmente | Fachada, identidade, conexão e pacote em homologação |

## 5. Decisões detalhadas

## DI-001 — Contrato de publicação e sincronização do PNCP

- **Status:** Aceita.
- **Contexto e problema:** o backend precisa representar corretamente a
  diferença entre criação do PCA, sincronização de mudanças homologadas e
  exclusão integral, sem vincular cada versão interna a um novo recurso
  externo.
- **Fontes:** checkpoint principal, seção 10; checkpoint complementar, seção
  4.1; continuação 03, seções 2.4 e 3.15; manual PNCP 2.5 adotado na DI-009.

### Decisão vigente

1. A publicação inicial cria o PCA com seu conjunto inicial de itens.
2. A Retificação sincroniza no PCA ativo:
   - inclusões de Demandas homologadas;
   - exclusões de Demandas canceladas.
3. A Retificação preserva o mesmo `sequencialPca`.
4. `PATCH` não integra o fluxo V1.
5. Fotografia vigente vazia com PCA ativo exige exclusão integral.
6. `ID_DEMANDA` é enviado como `numeroItem`.
7. O mapeamento deve ser comprovado no PNCP de treinamento com IDs reais,
   únicos e contendo lacunas.
8. Não será criado mapeamento alternativo antes desse teste.
9. O limite externo de 1000 itens é aceito sem particionamento na V1.
10. Estados acima do limite bloqueiam antes de escrita externa.
11. `sequencialPca` pertence ao estado operacional da publicação.
12. `PAC_PLANOS` é sua fonte física exclusiva, conforme a revisão da DI-008.
13. Campo equivalente em Demandas:
    - não é lido;
    - não é gravado;
    - não é comparado;
    - não é usado como fallback.
14. `numeroControlePNCP` permanece metadado de integração fora do Aggregate
    enquanto não houver necessidade de negócio documentada.

### Alternativas rejeitadas ou substituídas

| Alternativa | Situação | Motivo |
|---|---|---|
| Reenviar sempre o Plano completo | Rejeitada | Não representa as capacidades específicas de sincronização |
| Gerar novo sequencial em toda Retificação | Rejeitada | Mudanças ordinárias preservam o PCA ativo |
| Usar `PATCH` no fluxo normal | Rejeitada | Demanda homologada não sofre alteração material ordinária |
| Manter sequencial em Plano e Demandas | Substituída | Fonte dupla possui cardinalidade inadequada e gera incoerência |
| Criar mapeamento alternativo de item antecipadamente | Rejeitada | O contrato real ainda deve validar o ID existente |

### Consequências e limitações

- Nova versão interna não implica novo PCA.
- Exclusão seguida de nova publicação gera novo sequencial.
- A V1 não divide unidades acima de 1000 itens.
- Rejeição concreta de IDs com lacunas impede a produção até revisão da
  identidade externa.

### Gates e critérios de reabertura

- Executar teste contratual de `ID_DEMANDA` como `numeroItem`.
- Reabrir DI-001 e DI-002 se o PNCP rejeitar IDs únicos com lacunas.
- Reabrir se o contrato oficial alterar criação, inclusão, exclusão ou limite
  de itens de modo incompatível.

### Regras de negócio relacionadas

`RN-DEM-002`, `RN-DEM-003`, `RN-DEM-005`, `RN-DEM-006`, `RN-DEM-007`,
`RN-DEM-008`, `RN-PLA-001`, `RN-PLA-004`, `RN-PLA-005`, `RN-PLA-006`,
`RN-PUB-001`, `RN-PUB-002`, `RN-PUB-003`, `RN-PUB-005` e `RN-PUB-006`.

### Impacto no roadmap

Exige refatorar a decisão do Domain, o modelo do Plano e da Demanda, o contrato
do `PlanoRepository` e os testes antes de criar os adapters PNCP e Oracle.

### Histórico de evolução

- Aceita originalmente com o conceito de sincronização incremental.
- Revisada pela DI-008 para eliminar qualquer leitura ou escrita funcional do
  sequencial em Demandas.

## DI-002 — Autoridade, reconciliação e vínculo externo

- **Status:** Aceita.
- **Contexto e problema:** SGI, Oracle local e PNCP observam fatos diferentes.
  O fluxo precisa convergir sem importar automaticamente estado externo como
  nova verdade de negócio e sem depender de baseline detalhado não necessário
  à V1.
- **Fontes:** checkpoint principal, seção 11; checkpoint complementar, seções
  4.2, 4.3 e 7; continuação 03, seção 3.9.

### Decisão vigente

1. O SGI governa o PCA com vínculo local conhecido e o conjunto desejado de
   Demandas homologadas vigentes.
2. O PNCP representa o estado observado e efetivamente publicado.
3. Para PCA vinculado, a V1 reconcilia somente pertencimento por
   `numeroItem`.
4. Não existe baseline local detalhado de conteúdo na V1.
5. A V1 não compara atributos item a item.
6. Alterações posteriores de `dataDesejada` com o mesmo `ID_DEMANDA` não são
   propagadas automaticamente.
7. PCA conhecido e definitivamente ausente pode ser recriado com novo
   sequencial.
8. Ausência incerta, erro de consulta ou resposta ambígua não autoriza
   recriação.
9. PCA externo sem vínculo local conhecido não é adotado automaticamente.
10. Publicações legadas exigem migração ou reconciliação assistida.
11. O PNCP não altera automaticamente Demandas, homologações ou cancelamentos
    internos.
12. Confirmação externa seguida de falha local produz diagnóstico e
    reconciliação consciente.
13. Não há compensação automática nem adoção automática do recurso externo
    após falha local.

### Alternativas rejeitadas ou substituídas

| Alternativa | Situação | Motivo |
|---|---|---|
| Persistir baseline detalhado | Substituída | Pertencimento satisfaz o escopo V1 |
| Comparar conteúdo automaticamente | Rejeitada na V1 | Não há contrato para conteúdo mutável ou `PATCH` |
| Bloquear toda ausência de PCA conhecido | Substituída | Ausência definitiva permite recriação consciente |
| Dar precedência absoluta ao SGI ou ao PNCP | Rejeitada | Cada sistema governa fatos diferentes |
| Adotar PCA externo desconhecido | Rejeitada | Pode vincular publicação alheia ou legada |
| Importar PNCP para mudar homologações internas | Rejeitada | Estado observado não cria decisão de negócio no SGI |

### Consequências e limitações

- A V1 pode não detectar mudança de conteúdo com identidade preservada.
- `dataDesejada` alterada após publicação permanece limitação conhecida.
- Ausência definitiva depende de duas observações externas, definidas na
  DI-009.
- Migração inicial de publicações legadas permanece procedimento assistido.

### Gates e critérios de reabertura

- Reabrir se conteúdo mutável precisar ser sincronizado.
- Reabrir se `PATCH` entrar no escopo.
- Reabrir se a identidade externa dos itens mudar.
- Reabrir se o contrato real de consulta não permitir comprovar ausência
  definitiva.

### Regras de negócio relacionadas

`RN-DEM-002`, `RN-DEM-004`, `RN-PUB-001`, `RN-PUB-002`, `RN-PUB-004` e
`RN-PUB-006`.

### Impacto no roadmap

Remove da V1 a necessidade de baseline detalhado e orienta as portas de leitura
PNCP, a política pura de reconciliação e os testes do UseCase.

### Histórico de evolução

- A versão inicial exigia baseline e bloqueava toda ausência de PCA conhecido.
- A DI-007 consolidou a substituição por pertencimento e a distinção de
  ausência definitiva.

## DI-003 — Modos de publicação e unidade externa

- **Status:** Aceita.
- **Contexto e problema:** o sistema pode publicar um PCA por UO ou consolidar
  o exercício em uma única UASG. O modo altera a unidade externa, a
  independência das falhas e o escopo da reconciliação.
- **Fontes:** checkpoint principal, seção 12; checkpoint complementar, seções
  4.4 e 8.5; continuação 03, seção 2.4.

### Decisão vigente

1. A configuração corrente vem de:
   - `PUBLICAR_EM_UNICA_UASG`;
   - `UASG_CONSOLIDADORA`, quando aplicável.
2. Configuração ausente, inválida ou incoerente bloqueia antes do PNCP.
3. Não existe default silencioso.
4. No modo individual:
   - existe uma unidade externa por UO;
   - existe um resultado independente por UO;
   - UOs do mesmo exercício não podem compartilhar UASG.
5. No modo consolidado:
   - existe uma unidade externa por exercício e UASG consolidadora;
   - existe uma reconciliação e um resultado conjuntos;
   - o conjunto desejado é a união completa das Demandas vigentes;
   - uma UO vazia apenas deixa de contribuir;
   - o PCA é excluído somente quando a união inteira fica vazia.
6. Os Aggregates Plano permanecem separados por UO.
7. Agrupamento e escolha do modo pertencem à Application.
8. Estratégia e UASG consolidadora são congeladas na primeira execução válida.
9. O congelamento ocorre antes da autenticação e do acesso ao PNCP.
10. Divergência posterior entre configuração corrente e estratégia adotada
    bloqueia e exige migração assistida.

### Alternativas rejeitadas ou substituídas

| Alternativa | Situação | Motivo |
|---|---|---|
| Repository escolher o modo | Rejeitada | Estratégia pertence ao fluxo da Application |
| Domain ler configuração | Rejeitada | Configuração externa não pertence ao Aggregate |
| Aplicar default silencioso | Rejeitada | Pode alterar a topologia externa |
| Consolidar apenas Planos alterados | Rejeitada | O PCA consolidado representa a união vigente completa |
| Congelar após a primeira publicação confirmada | Substituída | Mantinha janela após confirmação externa e falha local |

### Consequências e limitações

- Mudar de modo altera a topologia externa e não é operação automática.
- Exercícios futuros podem adotar estratégia diferente.
- O modo consolidado não possui sucessos independentes por UO.

### Gates e critérios de reabertura

- Validar persistência e coerência da estratégia conforme DI-008.
- Reabrir se surgir requisito de migração automática entre topologias.

### Regras de negócio relacionadas

`RN-CAD-005`, `RN-CAD-006`, `RN-PUB-003`, `RN-PUB-009`, `RN-PUB-010` e
`RN-PUB-011`.

### Impacto no roadmap

Exige porta de estratégia na Application e testes dos dois modos antes da
Infrastructure.

### Histórico de evolução

- Aceita com dois modos de publicação.
- Momento do congelamento revisado de primeira publicação confirmada para
  primeira execução válida.

## DI-004 — Transações, exclusividade e falhas parciais

- **Status:** Aceita.
- **Contexto e problema:** Oracle e PNCP não compartilham transação. O fluxo
  precisa serializar a unidade, observar efeitos externos e classificar falhas
  sem supor rollback distribuído.
- **Fontes:** checkpoint principal, seção 13; checkpoint complementar, seção
  8.2; continuação 03, seções 3.11 e 3.12.

### Decisão vigente

1. Transação Oracle garante apenas atomicidade local.
2. Uma única execução pode processar a mesma unidade externa por vez.
3. A transação da unidade permanece aberta durante:
   - releitura local;
   - observação PNCP;
   - decisão;
   - escrita externa;
   - observação final;
   - confirmação;
   - auditoria.
4. A observação final do PNCP determina convergência.
5. Falha específica e conhecida pode permitir operações independentes.
6. Falha sistêmica ou estado não observável interrompe a unidade.
7. Não há compensação automática.
8. Escrita de resultado incerto nunca é repetida cegamente.
9. Após escrita incerta, o sistema observa, compara e recalcula.
10. É permitida no máximo uma reconciliação controlada adicional na mesma
    execução.
11. Convergência final permite confirmação local mesmo após erro intermediário.
12. Pendência exclusivamente homologada produz `FALHA_RECONCILIAVEL`.
13. Divergência inexplicada produz bloqueio.
14. No modo individual, falha específica de uma UO não impede as demais.
15. Falha sistêmica encerra a execução.
16. No modo consolidado, toda falha pertence à unidade conjunta.
17. Não haverá particionamento acima de 1000 itens na V1.

### Alternativas rejeitadas

| Alternativa | Motivo |
|---|---|
| Tratar Oracle e PNCP como transação distribuída | Não existe rollback externo coordenado |
| Repetir escrita incerta | Pode duplicar ou contradizer efeito já confirmado |
| Compensar automaticamente | Pode destruir estado válido sem certeza suficiente |
| Reconciliar em loop | Cria execução sem limite e retém locks indefinidamente |
| Processar a mesma unidade sem exclusividade | Permite duas escritas concorrentes sobre o mesmo PCA |

### Consequências e limitações

- Locks Oracle permanecem durante chamadas HTTP.
- Falha PNCP confirmada/Oracle falhou exige recuperação consciente.
- O desenho pressupõe baixo volume e baixa concorrência.

### Gates e critérios de reabertura

- Revisar se volume, concorrência ou duração de locks aumentarem.
- Revisar se surgir infraestrutura durável de coordenação ou idempotência.

### Regras de negócio relacionadas

`RN-PUB-009` e `RN-PUB-010`.

### Impacto no roadmap

Define a porta de unidade de trabalho, os cenários de falha do UseCase e o
mecanismo de exclusividade concretizado na DI-008.

### Histórico de evolução

- Aceita a permanência da transação Oracle durante HTTP como trade-off da V1.
- A DI-008 definiu o lock concreto; a DI-009 refinou classificação e ordem das
  escritas.

## DI-005 — Estado operacional e confirmação versionada

- **Status:** Aceita.
- **Contexto e problema:** uma publicação externa pode continuar ativa enquanto
  uma nova versão interna ainda está pendente. Um único campo de data não
  representa todos os fatos necessários.
- **Fontes:** checkpoint principal, seção 14; checkpoint complementar, seções
  4.5 e 8.7.

### Decisão vigente

1. Existência de PCA externo e confirmação da versão vigente são fatos
   distintos.
2. `PAC_PLANOS.DATA_PUBLICACAO` significa o momento em que o SGI confirmou a
   convergência daquela versão homologada.
3. No Domain, o conceito é `dataConfirmacaoSincronizacao`.
4. Nova versão nasce sem confirmação.
5. Confirmações anteriores não são sobrescritas.
6. O Plano carregado permanece fotografia imutável.
7. Ele não é mutado nem recriado apenas para refletir persistência.
8. A Application ordena confirmação por operação orientada à intenção.
9. O Repository persiste confirmação autorizada, sem decidir convergência.
10. Estados operacionais:
    - `NAO_PUBLICADO`;
    - `PUBLICADO`;
    - `EXCLUIDO`.
11. Exclusão preserva o último sequencial como histórico sem indicar PCA ativo.
12. Nova publicação após exclusão recebe novo sequencial.
13. No modo individual, confirma-se a versão vigente pendente da UO.
14. No consolidado, confirmam-se conjuntamente as versões vigentes pendentes
    participantes.

### Alternativas rejeitadas

| Alternativa | Motivo |
|---|---|
| Inferir estado apenas por `DATA_PUBLICACAO` | Mistura existência externa e convergência da versão |
| Mutar o Aggregate depois da confirmação | O Plano é fotografia do estado carregado |
| Recriar o Aggregate apenas para persistir | Não agrega comportamento ao fluxo |
| Usar setter genérico | Não expressa a intenção de confirmação |
| Sequencial presente significa PCA ativo | Estado `EXCLUIDO` preserva sequencial histórico |

### Consequências e limitações

- A projeção operacional precisa representar explicitamente o estado.
- Confirmação usa instante Oracle na mesma transação da auditoria.
- `numeroControlePNCP` continua fora do Aggregate na V1.

### Gates e critérios de reabertura

- Validar coerência física e constraints da projeção na DI-008.
- Reabrir se surgir requisito de histórico estruturado de múltiplas instâncias
  externas.

### Regras de negócio relacionadas

`RN-PLA-005`, `RN-PLA-007`, `RN-PUB-001`, `RN-PUB-002`, `RN-PUB-003`,
`RN-PUB-004`, `RN-PUB-005`, `RN-PUB-007` e `RN-PUB-008`.

### Impacto no roadmap

Exige refatorar o estado do Domain e o comando de confirmação antes de
completar o UseCase.

### Histórico de evolução

- Aceita a separação entre publicação externa e confirmação versionada.
- DI-008 definiu sua projeção física corrente em `PAC_PLANOS`.

## DI-006 — Auditoria e procedimentos PNCP

- **Status:** Aceita.
- **Contexto e problema:** `PAC_HISTORICOS` deve registrar fatos de negócio sem
  virar diário de tentativas, falhas técnicas ou decisões ainda não
  confirmadas.
- **Fontes:** checkpoint complementar, seção 5.

### Decisão vigente

1. `PAC_HISTORICOS` contém somente fatos concluídos, observados e confirmados.
2. Não geram histórico:
   - tentativas;
   - validações rejeitadas;
   - falhas técnicas;
   - resultados incertos;
   - conflitos;
   - contenções de lock;
   - rollback;
   - `NENHUMA_ACAO`.
3. Procedimentos:
   - 5: Publicação inicial;
   - 8: Retificação;
   - 9: Exclusão integral.
4. Existe um histórico por PCA externo.
5. No modo individual, um registro por PCA da UO.
6. No consolidado, um único registro para a publicação conjunta.
7. Responsável é o usuário autenticado com sua Lotação.
8. A Application materializa o fato e constrói a mensagem.
9. O histórico é fato imutável, não Entity ou Aggregate Root por causa do ID
   técnico.
10. A porta de persistência pertence à Application.
11. Confirmação, estado operacional e auditoria participam da mesma transação.
12. O instante de confirmação e auditoria vem do Oracle.
13. PSR-3 atende à observabilidade técnica.

### Alternativas rejeitadas

| Alternativa | Motivo |
|---|---|
| Registrar tentativas e falhas no histórico | Mistura fato de negócio e telemetria |
| Registrar `NENHUMA_ACAO` | Não houve transição de negócio |
| Um histórico por UO no consolidado | Multiplica artificialmente uma operação externa |
| Repository decidir procedimento ou mensagem | Transfere decisão para Infrastructure |
| Consultar dinamicamente três códigos conhecidos | Abstração especulativa |
| Auditar fora da transação da confirmação | Permite estado local contraditório |

### Consequências e limitações

- Logs técnicos precisam sobreviver ao rollback do negócio.
- Mensagens não contêm payload completo, segredos ou texto externo como
  autoridade.
- `ID_HISTORICO` permanece chave técnica.

### Gates e critérios de reabertura

- Validar insert de auditoria na mesma sessão e transação da confirmação.
- Reabrir se o catálogo de procedimentos deixar de ser estável ou configurável
  por requisito real.

### Regras de negócio relacionadas

`RN-CAD-003`, `RN-CAD-004`, `RN-AUD-001`, `RN-AUD-002`, `RN-AUD-003`,
`RN-AUD-004` e `RN-AUD-005`.

### Impacto no roadmap

Substitui o Repository provisório do Domain por porta da Application e exige
refatorar o modelo de auditoria antes da Infrastructure.

### Histórico de evolução

- Aceita como primeira decisão do checkpoint complementar.
- Remove a classificação anterior de histórico como Entity apenas por possuir
  ID Oracle.

## DI-007 — Portas necessárias na Application

- **Status:** Aceita.
- **Contexto e problema:** o UseCase completo precisa autenticar, observar,
  decidir, escrever, confirmar, auditar e controlar a unidade sem expor
  tecnologias externas nas camadas internas.
- **Fontes:** checkpoint complementar, seções 6 e 7.

### Decisão vigente

As portas e contratos são definidos por capacidades:

1. autenticação PNCP que retorna sessão opaca reutilizável;
2. leitura PNCP separada de escrita;
3. consulta de PCA conhecido por sequencial;
4. busca de candidatos apenas para diagnóstico ou migração assistida;
5. comandos tipados para:
   - publicar PCA;
   - inserir itens;
   - excluir itens;
   - excluir PCA;
6. estratégia corrente e estratégia adotada;
7. unidade de trabalho e exclusividade sem exposição de Oracle;
8. contexto coeso do usuário, Representante e Lotação;
9. persistência do fato de auditoria;
10. porta própria para estado operacional cross-Plan;
11. `PlanoRepository` no Domain para:
    - listar identidades;
    - reconstruir fotografia vigente;
    - reconstruir Plano vazio;
    - registrar confirmação orientada à intenção;
12. política pura no Domain para decidir ações e IDs envolvidos;
13. PSR-3 como contrato de observabilidade;
14. resultados por unidade externa:
    - `CONCLUIDO`;
    - `SEM_ALTERACAO`;
    - `FALHA_RECONCILIAVEL`;
    - `BLOQUEADO`.

### Alternativas rejeitadas

| Alternativa | Motivo |
|---|---|
| Gateway genérico com HTTP/JSON/JWT | Vaza tecnologia para a Application |
| Porta única “sincronizar” | Esconde capacidades e incerteza das escritas |
| Adoção automática de candidato externo | Pode vincular publicação desconhecida |
| Repositories separados de Representante e Lotação | Fragmenta o contexto da autoria |
| Metadados cross-Plan no `PlanoRepository` | Mistura Aggregate e projeção externa |
| Porta proprietária de logging | PSR-3 já atende ao contrato |

### Consequências e limitações

- Interfaces internas não usam URL, JSON, HTTP, SQL, ADOdb, OCI8 ou macros.
- Contratos devem ser demonstrados por testes do consumidor com doubles.
- Falhas sistêmicas permanecem no resultado global, não em resultado unitário
  fabricado.

### Gates e critérios de reabertura

- Demonstrar os contratos pelo UseCase completo com mocks.
- Reabrir somente se uma capacidade real não puder ser expressa pelos contratos
  aprovados.

### Regras de negócio relacionadas

`RN-DEM-003`, `RN-DEM-004` e `RN-PUB-006`.

### Impacto no roadmap

Define o gate para completar a Application antes de iniciar adapters concretos.

### Histórico de evolução

- Aceita depois da definição de auditoria.
- Incorporou a revisão da DI-002 para reconciliação sem baseline.

## DI-008 — Mecanismo Oracle

- **Status:** Aceita condicionalmente.
- **Contexto e problema:** é necessário compartilhar conexão, transação, lock,
  reconstrução, confirmação e auditoria no ambiente Scriptcase sem contaminar
  Domain ou Application com ADOdb, OCI8 ou schema físico.
- **Fontes:** checkpoint complementar, seção 8; continuação 03, seção 2.

### Decisão vigente

#### Conexão e transação

1. Estratégia principal: reutilizar `$this->Db`, ADOdb fornecido pelo
   Scriptcase.
2. A conexão é emprestada.
3. O backend nunca chama `Close()` nessa conexão.
4. Repository, unidade de trabalho, lock, estado, confirmação e auditoria
   compartilham a mesma sessão Oracle.
5. Scriptcase e ADOdb ficam encapsulados na Infrastructure.
6. Fallback oficial: conexão OCI8 própria.
7. PDO_OCI não é fallback.
8. Quando usar fallback próprio, o backend controla seu ciclo de vida.

#### Exclusividade

1. Uma tabela técnica contém âncoras permanentes.
2. O lock real pertence à transação Oracle.
3. Aquisição usa `SELECT ... FOR UPDATE NOWAIT`.
4. Contenção retorna `BLOQUEADO` imediatamente.
5. A chave é estruturada por escopo, exercício e identificador-alvo.
6. Há âncoras distintas para estratégia anual e unidade externa.
7. Linhas são provisionadas idempotentemente em transação técnica curta.

#### Datas e decimais

1. Datas de calendário não sofrem conversão de timezone.
2. Instantes usam fuso operacional explícito.
3. Máscaras Oracle são explícitas.
4. Confirmação e auditoria usam `SYSDATE`.
5. Decimais são representados exatamente, sem `float`.
6. `HALF_UP` ocorre somente na borda PNCP.
7. Totais do SGI não são recalculados a partir do unitário arredondado.

#### Projeção operacional

1. `PAC_PLANOS` é a projeção operacional corrente.
2. A projeção mínima contém:
   - modo adotado;
   - UASG externa;
   - estado;
   - `sequencialPca`.
3. `PAC_PLANOS` é a fonte física exclusiva do sequencial.
4. Não serão criadas agora tabelas de cabeçalho anual ou instâncias externas.
5. `DATA_PUBLICACAO` permanece confirmação versionada.
6. Valores não nulos divergentes bloqueiam.
7. Não há autorreparo silencioso.

#### Reconstrução

1. Primeira etapa: descoberta preliminar de identidades.
2. Segunda etapa: releitura autoritativa depois do lock correto.
3. Versão vigente é a maior versão da UO e exercício.
4. A fotografia reúne Demandas de todas as versões de origem.
5. Situações participantes:
   - Homologada;
   - Comprada;
   - Publicada.
6. Planejada e Cancelada não participam.
7. Plano vazio deve ser reconstruível.
8. Cada UO participante exige cadastro ativo único e UASG preenchida.

### Alternativas rejeitadas

| Alternativa | Motivo |
|---|---|
| OCI8 próprio como primeira escolha | A sessão real do host pode ser reutilizada |
| PDO_OCI como fallback | Foi escolhido um único fallback explícito |
| Usar `_connectionID` diretamente | Acopla a detalhe interno do ADOdb |
| Usar somente macros `sc_*` | Contrato transacional é insuficiente |
| `DBMS_LOCK` | Exige privilégios e complexidade desnecessários |
| Lock por `INSERT`/`DELETE` | Introduz limpeza e corridas de registros |
| Lock global | Bloqueia unidades externas independentes |
| Linha de Plano como lock | Não cobre consolidado ou Plano vazio |
| Tabela própria de instâncias externas | Não há requisito atual para o ciclo rico |
| Fonte dupla do sequencial | Cria inconsistência e cardinalidade errada |
| Reconstruir tudo antes do lock | A fotografia pode envelhecer antes da exclusividade |

### Consequências e limitações

- `PAC_PLANOS` acumula fisicamente versionamento e projeção, mas os contratos
  internos permanecem separados.
- Nomes de colunas, DDL, constraints e índices não são inventados nesta
  decisão.
- A aceitação arquitetural não substitui testes no ambiente real.

### Gates e critérios de reabertura

#### Gate ADOdb/OCI8

- início explícito de transação;
- commit e rollback;
- bind de parâmetros;
- tradução de erros;
- mesma sessão em todos os adapters;
- rollback real de alteração controlada;
- versão do ADOdb embarcado.

Falha em requisito obrigatório ativa OCI8 próprio sem reabrir as demais
decisões.

#### Gate do schema

- DDL completo e nomes físicos;
- tipos, tamanhos e nulabilidade;
- constraints, chaves e índices;
- joins de UO e UASG;
- contrato de `DATA_HOMOLOGACAO`;
- coerência da projeção;
- datas e decimais.

### Regras de negócio relacionadas

`RN-CAD-005`, `RN-CAD-006`, `RN-DEM-009`, `RN-PLA-001`, `RN-PLA-002`,
`RN-PLA-003`, `RN-PLA-004`, `RN-PLA-006`, `RN-PLA-007`, `RN-PUB-008` e
`RN-PUB-011`.

### Impacto no roadmap

Libera a implementação Oracle somente depois do gate da Application e dos
testes contratuais aplicáveis.

### Histórico de evolução

- Iniciada parcialmente no checkpoint complementar.
- Encerrada condicionalmente na continuação 03 com fotografia, versão vigente,
  UASG, projeção e chave de exclusividade definidas.

## DI-009 — Transporte HTTP

- **Status:** Aceita condicionalmente.
- **Contexto e problema:** o adapter PNCP precisa tratar autenticação, leitura,
  escrita incerta, TLS, paginação e números exatos sem vazar HTTP ou cURL para
  as camadas internas.
- **Fontes:** continuação 03, seção 3; manual PNCP 2.5 e OpenAPI adotados
  durante a decisão.

### Decisão vigente

#### Contrato e transporte

1. O contrato adotado é o manual PNCP 2.5 e seu OpenAPI, sujeitos a
   revalidação no gate.
2. Ambientes oficiais adotados:
   - homologação: `https://treina.pncp.gov.br/api/pncp`;
   - produção: `https://pncp.gov.br/api/pncp`.
3. O transporte usa cURL nativo, encapsulado na Infrastructure.
4. Não há dependência HTTP adicional de runtime.
5. Cada chamada envia uma única requisição.
6. Status, headers e corpo são capturados separadamente.
7. Falha de transporte é distinta de resposta HTTP.
8. Transporte não decide retry, reconciliação ou regra.

#### Ambientes e TLS

1. Ambientes internos são `HOMOLOGACAO` e `PRODUCAO`.
2. Request e usuário não fornecem URL livre.
3. Somente HTTPS é permitido.
4. Certificado e hostname são verificados.
5. Redirecionamentos automáticos ficam desabilitados.
6. Não existe opção equivalente a `curl -k`.
7. O CA bundle pode ter caminho configurável.

#### Autenticação

1. A autenticação ocorre uma vez por execução.
2. JWT é valor opaco.
3. Claims não são decodificados para controlar expiração.
4. O header `Authorization` deve usar esquema `Bearer`.
5. Token não é persistido nem compartilhado entre execuções.
6. Token, credenciais e header não aparecem em logs ou exceções.
7. Não há renovação ou segunda autenticação na mesma execução.
8. `401` e `403` são falhas sistêmicas.

#### Timeouts e retry

1. Valores iniciais configuráveis:
   - conexão: 5 segundos;
   - login e leitura: 30 segundos;
   - escrita: 60 segundos.
2. Nenhum timeout pode ser infinito.
3. Timeout de escrita significa resultado incerto.
4. Retry é permitido somente para GET.
5. Existem no máximo duas tentativas totais.
6. Elegíveis:
   - falha transitória de transporte;
   - `408`;
   - `429`;
   - `502`;
   - `503`;
   - `504`.
7. `Retry-After` é respeitado somente até teto configurado.
8. Login, POST e DELETE não usam essa política.

#### Leitura

1. `pagina` e `tamanhoPagina` são explícitos.
2. A leitura pagina até comprovar o fim.
3. Convergência nunca é decidida com resultado parcial.
4. Validar entre páginas:
   - unicidade de `numeroItem`;
   - ausência de páginas repetidas;
   - coerência de CNPJ, ano, UASG e sequencial;
   - limite total de 1000 itens.
5. Campos JSON desconhecidos são ignorados.
6. Campos utilizados são obrigatórios e tipados.
7. JSON malformado, profundo demais ou com UTF-8 inválido é rejeitado.
8. Texto do PNCP não controla o fluxo.

#### Ausência definitiva

1. Consulta pelo sequencial conhecido deve indicar ausência.
2. Listagem paginada por órgão e ano não deve conter PCA da mesma UASG.
3. Nenhuma ocorrência comprova ausência definitiva.
4. Outro sequencial para a mesma UASG gera conflito ou migração assistida.
5. Respostas divergentes, inconclusivas ou falha de consulta não autorizam
   recriação.

#### Escritas

1. Classificações:
   - `ACEITA`;
   - `REJEITADA`;
   - `RESULTADO_INCERTO`;
   - falha sistêmica.
2. `400` e `422` válidos são rejeições conhecidas.
3. Timeout, interrupção após envio, `429`, `5xx`, resposta malformada ou
   sucesso incompleto são resultados incertos.
4. Escrita incerta nunca é repetida diretamente.
5. Após incerteza, observar, comparar e recalcular.
6. Quando houver exclusões e inclusões:
   - excluir primeiro;
   - incluir depois;
   - observar o PCA completo ao final.
7. Não há compensação automática.
8. Operações usam lotes, sem particionamento acima de 1000 na V1.

#### Justificativas, decimais e segurança

1. Exclusão em lote com motivos diferentes usa mensagem padronizada e mantém
   os motivos completos localmente.
2. Motivos não são concatenados nem truncados.
3. Exclusão integral usa o motivo que retirou o último item.
4. Decimais permanecem exatos, sem `float`.
5. Número JSON exato usa marcador interno e validação lexical.
6. Não são aceitos fragmentos JSON arbitrários.
7. `HALF_UP` ocorre somente na borda.
8. Totais autoritativos não são recalculados.
9. Datas puras usam `YYYY-MM-DD`.
10. Logs não contêm credenciais, JWT, payload completo, resposta completa ou
    dumps cURL.

#### Suíte contratual

1. PHPUnit opt-in e separado da suíte unitária.
2. Execução serial e por comando explícito.
3. Credenciais externas e massa dedicada.
4. Preflight exige homologação e host `treina.pncp.gov.br`.
5. A suíte é estruturalmente proibida contra produção.
6. Estado externo é observado antes de escrita.
7. Limpeza final é best-effort, com diagnóstico de resíduos.

### Alternativas rejeitadas

| Alternativa | Motivo |
|---|---|
| Biblioteca HTTP adicional | Não há necessidade de nova dependência |
| URL livre fornecida por Request | Permite destino arbitrário |
| Desabilitar TLS | Viola segurança do contrato |
| Seguir redirects automaticamente | Pode expor o JWT a outro host |
| Decodificar JWT | Claims não são necessários para o fluxo |
| Renovar token na execução | A V1 usa uma autenticação por chamada |
| Retry genérico | Pode repetir escrita incerta |
| Decidir convergência por página parcial | Pode omitir itens |
| Usar `float` | Perde exatidão decimal |
| Fragmento JSON arbitrário | Rompe segurança da serialização |

### Consequências e limitações

- Alguns parâmetros permanecem configuráveis sem default aprovado:
  - tamanho máximo de resposta;
  - tamanho máximo de payload;
  - espera entre tentativas;
  - teto de `Retry-After`;
  - tamanho da página;
  - profundidade máxima do JSON.
- O manual não documenta claramente todos os formatos de ausência.
- A decisão permanece condicionada ao ambiente real.

### Gates e critérios de reabertura

- validar `ext-curl`, OpenSSL e CA bundle no Scriptcase;
- corrigir carregamento duplicado de cURL no WSL;
- alinhar o runtime PHP efetivo;
- executar suíte no PNCP de treinamento;
- registrar versão do manual/OpenAPI, data e resultados;
- comprovar autenticação, ausência, paginação, `Location`, sequencial, CNPJ
  alfanumérico, IDs com lacunas e decimais exatos;
- reabrir somente o contrato incompatível com a evidência real.

### Regras de negócio relacionadas

`RN-CAD-001`.

### Impacto no roadmap

Libera os adapters HTTP e PNCP somente depois da Application completa com mocks
e da execução dos gates de ambiente e contrato.

### Histórico de evolução

- Iniciada e encerrada condicionalmente na continuação 03.
- Incorpora os refinamentos de escrita incerta e ausência definitiva das
  decisões anteriores.

## DI-010 — Composition Root e Scriptcase

- **Status:** Aceita condicionalmente.
- **Contexto e problema:** o backend deve ser consumido pelo Scriptcase sem
  transferir wiring, credenciais, conexão ou estrutura interna para a aplicação
  Blank.
- **Fontes:** continuação 03, seção 4.

### Decisão vigente

#### Fronteira e composição

1. O backend é produto independente consumido em processo pela aplicação
   Blank.
2. O Composition Root pertence ao backend.
3. Wiring é manual por construtores.
4. Não há container ou Service Locator.
5. Scriptcase carrega o autoloader e chama uma única fachada pública.
6. A fachada recebe:
   - conexão `$this->Db` emprestada;
   - login autenticado confiável;
   - `SERVER_NAME`;
   - ano selecionado.
7. O Scriptcase não conhece Repository concreto, cliente PNCP, token ou
   política transacional.

#### Request, Controller e Presenter

1. `PublicarPcaRequest` contém somente:
   - CNPJ;
   - AnoExecucao.
2. Ambiente, URL, credenciais, login, senha e token não pertencem ao Request.
3. O CNPJ vem do perfil autorizado na implantação atual.
4. Controller recebe entrada tipada, cria Request, chama UseCase e entrega
   Response ao Presenter.
5. Controller não usa macros, não faz wiring e não contém regra.
6. Presenter produz ViewModel estruturado.
7. Presenter não gera JSON, HTML ou macros.
8. A aplicação Blank apenas renderiza o ViewModel.

#### Configuração

1. `SERVER_NAME` seleciona por allowlist exata um perfil conhecido.
2. O perfil declara explicitamente:
   - `HOMOLOGACAO` ou `PRODUCAO`;
   - CNPJ autorizado;
   - credenciais PNCP;
   - parâmetros da implantação.
3. Ambiente não é inferido pelo hostname.
4. Perfil desconhecido, ausente ou incoerente falha fechado.
5. Não existe fallback silencioso para produção.
6. A V1 usa um arquivo externo por perfil.
7. Perfis e segredos ficam fora do repositório e do webroot.
8. `.env` e arquivo externo não são suportados simultaneamente na V1.

#### Ciclo de vida e execução

1. Cada chamada cria novo grafo.
2. Cada chamada realiza uma autenticação PNCP.
3. A sessão PNCP é opaca e descartada no fim.
4. A mesma conexão Oracle é compartilhada entre adapters.
5. Conexão emprestada nunca é fechada.
6. No fallback OCI8, o backend possui e encerra a conexão.
7. A V1 é síncrona.
8. Não há fila, worker, polling ou progresso persistido.
9. A tela bloqueia nova submissão enquanto aguarda.
10. Não há lock global nem chave de idempotência persistida na V1.
11. Autorização de acesso à Blank permanece no Scriptcase.
12. Backend exige identidade e contexto resolvíveis, sem inventar novos papéis.

#### Respostas e falhas

1. Estados globais:
   - `SOLICITACAO_INVALIDA`;
   - `CONCLUIDA`;
   - `CONCLUIDA_COM_PENDENCIAS`;
   - `INTERROMPIDA`.
2. Entrada inválida produz `SOLICITACAO_INVALIDA`.
3. Somente resultados `CONCLUIDO` ou `SEM_ALTERACAO` produzem `CONCLUIDA`.
4. Presença de `FALHA_RECONCILIAVEL` ou `BLOQUEADO` depois da varredura produz
   `CONCLUIDA_COM_PENDENCIAS`.
5. Falha sistêmica que interrompe a varredura produz `INTERROMPIDA`.
6. Resultados já conhecidos são preservados.
7. Unidades não visitadas não recebem resultados fabricados.
8. Nenhuma exceção atravessa a fachada.
9. ViewModel não expõe stack trace, payload, SQL, credenciais, JWT ou detalhes
   de cURL, ADOdb e OCI8.

#### Distribuição

1. A release é autocontida com código e `vendor/`.
2. Composer não executa em produção.
3. Perfis e segredos permanecem externos ao artefato.
4. Scriptcase conhece somente autoloader e fachada pública.

### Alternativas rejeitadas

| Alternativa | Motivo |
|---|---|
| Composition Root no Scriptcase | Expõe estrutura interna e wiring ao host |
| Container ou Service Locator | Desnecessário para o grafo atual |
| Integração HTTP entre Blank e backend | O consumo aprovado é em processo |
| Ambiente inferido do hostname | Pode selecionar perfil incorreto |
| URL livre ou `HTTP_HOST` | Permite destino não allowlisted |
| `.env` e arquivo externo simultâneos | Cria fontes concorrentes |
| Execução assíncrona | Não há requisito de fila ou progresso na V1 |
| Lock global | Bloqueia unidades independentes |
| Idempotência persistida | Não justificada na V1 |
| Presenter gerar HTML ou JSON | Mistura ViewModel com renderização |
| Composer em produção | Release deve ser autocontida |

### Consequências e limitações

- A aplicação Blank aguarda o resultado final.
- A fronteira pública permanece pequena e estável.
- Perfis precisam ser provisionados externamente ao release.
- A decisão depende de comportamento real do Scriptcase.

### Gates e critérios de reabertura

- testar a fachada no Scriptcase real;
- concluir o gate da conexão emprestada;
- validar login, Representante e Lotação;
- validar seleção allowlisted do perfil;
- executar smoke test do pacote autocontido em homologação;
- reabrir apenas o contrato incompatível com o ambiente real.

### Regras de negócio relacionadas

`RN-CAD-001`, `RN-CAD-002` e `RN-AUD-004`.

### Impacto no roadmap

Orienta Presentation, Composition Root final, integração com a Blank e
distribuição, sem antecipar essas etapas durante a criação deste documento.

### Histórico de evolução

- Iniciada e encerrada condicionalmente na continuação 03.
- Confirma o Composition Root no backend e remove ambiente, credenciais e
  usuário do Request.

## 6. Matriz de decisões substituídas

As premissas abaixo são históricas e NÃO DEVEM ser implementadas como decisões
vigentes.

| Premissa anterior | Decisão vigente | Referência |
|---|---|---|
| Retificação reenvia o Plano completo | Sincroniza inclusões e exclusões no PCA ativo | DI-001 |
| Baseline detalhado é obrigatório | V1 reconcilia somente pertencimento | DI-002 |
| PCA conhecido ausente sempre bloqueia | Recriação somente após ausência definitiva | DI-002 |
| Congelamento ocorre após primeira publicação | Congelamento ocorre na primeira execução válida | DI-003 |
| Sequencial é lido ou gravado em Demandas | Fonte exclusiva na projeção de `PAC_PLANOS` | DI-001, DI-008 |
| OCI8 próprio é a primeira escolha | ADOdb emprestado, condicionado ao contrato | DI-008 |
| É necessária tabela própria de instâncias | Estado corrente é projetado em `PAC_PLANOS` | DI-008 |
| Fotografia é montada antes do lock | Descoberta preliminar e releitura sob lock | DI-008 |
| Histórico registra tentativas e falhas | Histórico registra somente fatos confirmados | DI-006 |
| Consolidado gera um histórico por UO | Gera um histórico por PCA externo | DI-006 |
| Transporte possui retry genérico | Retry restrito a GET; escrita incerta exige observação | DI-009 |
| Composition Root pertence ao Scriptcase | Composition Root pertence ao backend | DI-010 |

## 7. Gates consolidados e critérios de reabertura

### Gates antes da Infrastructure Oracle

- contrato ADOdb/OCI8 no Scriptcase real;
- mesma sessão entre todos os adapters;
- DDL, nomes físicos, tipos, constraints, chaves e índices;
- joins e cardinalidade de UO/UASG;
- datas, fusos, decimais e coerência da projeção;
- mecanismo de lock com contenção observável.

### Gates antes da Infrastructure PNCP definitiva

- runtime com cURL, OpenSSL e CA bundle;
- manual e OpenAPI revalidados;
- suíte opt-in no PNCP de treinamento;
- autenticação, paginação, ausência, `Location` e sequencial;
- CNPJ alfanumérico;
- `ID_DEMANDA` com lacunas;
- decimal exato como número JSON;
- classificação de escrita incerta.

### Gates antes da integração Scriptcase

- fachada no ambiente real;
- contexto de identidade, Representante e Lotação;
- conexão emprestada ou fallback;
- seleção allowlisted do perfil;
- pacote autocontido em homologação.

### Política de reabertura

1. Falha em gate reabre somente a decisão diretamente afetada.
2. Parâmetro ainda não validado não recebe default inventado.
3. Mudança de volume, concorrência ou topologia reabre as decisões que dependem
   dessas premissas.
4. Evidência incompatível deve ser registrada antes de alterar o status.
5. Reabertura deve atualizar este documento antes de roadmap, código e testes.

## 8. Política de manutenção

1. Cada nova decisão recebe ID `DI-NNN`.
2. IDs não são reutilizados.
3. Mudança semântica relevante deve registrar evolução e, quando necessário,
   substituir a decisão anterior.
4. Alternativas descartadas permanecem registradas para evitar reintrodução
   acidental.
5. Gates devem indicar evidência objetiva e critério de reabertura.
6. Regras de negócio são referenciadas por `RN-*`, sem serem redefinidas aqui.
7. O roadmap deve conter apenas ID, título, resumo, status e gate.
8. Conflito documental bloqueia implementação até reconciliação.
9. Este documento deve ser atualizado antes de implementar decisão revisada.

## 9. Referências

### Checkpoints

- `.agents/planejamento/checkpoints/handoff_planejamento_roadmap_v2.md`;
- `.agents/planejamento/checkpoints/handoff_planejamento_roadmap_v2_complementar_di006_di008.md`;
- `.agents/planejamento/checkpoints/handoff_planejamento_roadmap_v2_continuacao_03_di008_di010.md`.

### Sínteses independentes

- `.agents/planejamento/sinteses/sintese_DI-001.md`;
- `.agents/planejamento/sinteses/sintese_DI-003.md`;
- `.agents/planejamento/sinteses/sintese_DI-004.md`;
- `.agents/planejamento/sinteses/sintese_DI-005.md`;
- `.agents/planejamento/sinteses/sintese_DI-006.md`;
- `.agents/planejamento/sinteses/sintese_DI-007.md`;
- `.agents/planejamento/sinteses/sintese_DI-008.md`;
- `.agents/planejamento/sinteses/sintese_DI-009.md`;
- `.agents/planejamento/sinteses/sintese_DI-010.md`.

Não existe síntese DI-002 separada no workspace. Sua decisão vigente é
reconstruída pela precedência dos checkpoints principal e complementar e pela
revisão registrada na DI-007.

### Documentos relacionados

- `docs/vigentes/autoritativos/regras_de_negocio.md`;
- `docs/vigentes/apoio/arquitetura.md`;
- `docs/vigentes/autoritativos/modelo_de_dominio.md`;
- `docs/vigentes/apoio/contexto_funcional_do_sistema_pca.md`.

- `docs/vigentes/autoritativos/fluxo_de_implementacao.md`;
- [Roadmap de implementação](roadmap.md).

### Fontes oficiais adotadas durante o planejamento

- Manual PNCP 2.5:
  `https://pncp.gov.br/manual/pt-br/latest/singlehtml/`;
- OpenAPI PNCP:
  `https://pncp.gov.br/pncp-api/v3/api-docs`;
- ambiente de treinamento:
  `https://treina.pncp.gov.br/api/pncp`;
- ambiente de produção:
  `https://pncp.gov.br/api/pncp`.

Essas fontes devem ser revalidadas e registradas pelos gates da DI-009 antes da
implementação definitiva.
