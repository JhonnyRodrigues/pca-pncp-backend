# Plano definitivo — Sprints 4.11 e 4.12

## 1. Objetivo e fronteira

As duas sprints concluem a Fase 4 — Application sem implementar adapters concretos.

```text
Sprint 4.10
contexto completo + escritas iniciais + unidade local aberta
        ↓
Sprint 4.11
convergência final + persistência atômica + resultado unitário terminal
        ↓
Sprint 4.12
coordenação global + Response + gate final da Application
```

- A Sprint 4.11 encerra uma unidade externa sob exclusividade.
- A Sprint 4.12 recebe resultados unitários já terminais, coordena a execução global e produz a Response.
- Nenhuma responsabilidade transacional da unidade será transferida para a 4.12.
- Nenhuma agregação global será antecipada na 4.11.

Baseline anterior à execução: 276 testes e 827 asserções aprovados.

## 2. Fontes aplicáveis

### Sprint 4.11

- RN-PLA-007;
- RN-PUB-001 a RN-PUB-010;
- RN-AUD-001 a RN-AUD-005;
- DI-002, DI-004, DI-005, DI-006 e DI-007;
- limites abstratos da DI-009 sobre escrita incerta;
- Fluxo de Implementação, especialmente observação final, reconciliação controlada, confirmação, auditoria e resultado unitário.

### Sprint 4.12

- RN-PUB-009 e RN-PUB-010;
- DI-004, DI-007 e DI-010;
- Fluxo de Implementação, especialmente preparação global, varredura, resultados e continuidade;
- Roadmap V2, Fase 4 e gate de entrada na Fase 5.

## 3. Sprint 4.11 — Convergência final, persistência atômica e resultado unitário

### Objetivo

Implementar o finalizador real da unidade deixado como contrato na Sprint 4.10, garantindo que toda unidade iniciada termine em commit ou rollback e produza exatamente um resultado unitário terminal ou uma falha sistêmica global.

**Complexidade:** alta, justificada pela coordenação entre estado externo observado, escrita incerta e atomicidade local.

### Mudanças estruturais

- Implementar `FinalizadorProcessamentoUnidadeInterno`, preservando `FinalizadorProcessamentoUnidade` como contrato interno já consumido.
- Extrair de `DespachadorComandosUnidade` um `ExecutorComandosUnidade` concreto, sem nova interface.
- Reutilizar esse executor:
  - no despacho inicial;
  - na única reconciliação adicional permitida.
- Preservar no executor as proteções já implementadas:
  - limite de 1000 itens;
  - ordem excluir antes de incluir;
  - consulta da evidência homologada para exclusão integral;
  - justificativas vigentes;
  - classificação das escritas;
  - rollback nos desfechos antecipados.
- Consumir `Psr\Log\LoggerInterface` nos novos pontos de orquestração, sem criar Port proprietária nem adapter concreto.

### Correção mínima do contrato de auditoria

Há incompatibilidade entre o contrato atual e a DI-006:

- `HistoricoProcedimento` exige `DateTimeImmutable` no construtor;
- `RegistroAuditoriaPort` recebe o fato já datado;
- a DI-006 determina que confirmação e auditoria recebam o instante do futuro adapter Oracle por `SYSDATE`, na mesma transação.

A correção será:

- criar `IntencaoRegistroHistorico`, DTO imutável da Application contendo:
  - `ProcedimentoPublicacaoPca`;
  - mensagem;
  - Representante;
  - Lotação;
- alterar `RegistroAuditoriaPort` para:

```php
registrar(
    IntencaoRegistroHistorico $intencao
): ResultadoPersistenciaLocal
```

- manter `HistoricoProcedimento` como representação de um fato já persistido ou reconstruído, com instante obrigatório;
- não tornar o instante anulável;
- não introduzir setter;
- não obter horário no PHP;
- deixar o futuro adapter Oracle atribuir `SYSDATE` durante o `INSERT`.

A Application decide se há auditoria e constrói seu conteúdo sem instante. O adapter somente atribui o instante autoritativo e persiste.

### Microciclo 1 — Finalização de `NENHUMA_ACAO`

- Usar a observação conclusiva já obtida sob exclusividade; não realizar nova consulta apenas por não haver escrita.
- Confirmar Planos pendentes somente quando essa observação comprovar:
  - PCA ativo conhecido com fotografia igual à fotografia vigente; ou
  - ausência definitivamente comprovada com fotografia vigente vazia.
- Observação inconclusiva produz `FALHA_RECONCILIAVEL`.
- Conflito produz `BLOQUEADO`.
- Falha sistêmica interrompe a varredura.
- Nos três casos de falha:
  - não confirmar versões;
  - não gerar auditoria;
  - desfazer a unidade local.
- Com convergência comprovada:
  - confirmar somente versões vigentes ainda pendentes;
  - corrigir o estado operacional local quando a ausência definitiva demonstrar que um estado anteriormente `PUBLICADO` já não está ativo;
  - preservar o último sequencial como referência histórica;
  - não gerar histórico;
  - confirmar a unidade local;
  - retornar `SEM_ALTERACAO`.

### Microciclo 2 — Observação obrigatória depois das escritas

Depois de `PUBLICAR`, `RETIFICAR` ou `EXCLUIR_PCA`:

- observar novamente o estado externo completo;
- não tratar resposta intermediária de escrita como prova de convergência;
- em Publicação aceita, usar o novo sequencial como vínculo provisório para a observação;
- em Publicação incerta sem sequencial:
  - consultar candidatos;
  - ausência permite recalcular;
  - candidato encontrado não é adotado automaticamente e bloqueia a unidade;
- classificar:
  - observação inconclusiva → `FALHA_RECONCILIAVEL`;
  - conflito ou divergência incompatível → `BLOQUEADO`;
  - falha sistêmica → interrupção global;
- desfazer a unidade antes de retornar qualquer resultado não convergente;
- transformar falha no rollback em falha sistêmica prevalente.

### Microciclo 3 — Reconciliação controlada

- Reaplicar a política do Domain usando:
  - os Planos relidos;
  - o estado operacional coerente disponível;
  - a nova observação externa.
- Se a política retornar `NENHUMA_ACAO`, considerar a fotografia convergente.
- Somente um resultado de escrita `RESULTADO_INCERTO` autoriza nova escrita.
- Escrita `REJEITADA` não recebe retry automático.
- Quando autorizada:
  - recalcular apenas as diferenças restantes;
  - reutilizar `ExecutorComandosUnidade`;
  - executar no máximo uma reconciliação adicional;
  - observar novamente depois dela.
- Nunca realizar terceira escrita, loop de convergência ou compensação.
- Depois da reconciliação adicional:
  - convergência permite persistência local;
  - diferenças homologadas ainda pendentes produzem `FALHA_RECONCILIAVEL`;
  - divergência incompatível produz `BLOQUEADO`;
  - nova incerteza não autoriza outra escrita.

### Microciclo 4 — Persistência local da convergência

Quando a convergência estiver comprovada, derivar o estado operacional final:

- Publicação → `PUBLICADO` com o novo sequencial;
- Retificação → `PUBLICADO` preservando o sequencial;
- Exclusão integral → `EXCLUIDO` preservando o último sequencial;
- `NENHUMA_ACAO` → manter ou corrigir coerentemente o estado conforme a observação conclusiva.

Dentro da mesma unidade de trabalho:

1. registrar a alteração necessária do estado operacional;
2. confirmar somente os Planos vigentes pendentes;
3. registrar a intenção de auditoria quando houver fato auditável;
4. confirmar a unidade local.

Regras:

- no modo individual, confirmar a versão vigente pendente da UO;
- no consolidado, confirmar conjuntamente todas as versões vigentes pendentes participantes;
- não mutar nem reconstruir o Aggregate apenas para refletir a confirmação;
- qualquer falha de estado, confirmação, auditoria ou commit:
  - desfaz a unidade local em best-effort;
  - não tenta compensar o PNCP;
  - retorna falha sistêmica;
- criar o resultado de sucesso somente depois do commit.

### Microciclo 5 — Auditoria de negócio

Gerar uma única `IntencaoRegistroHistorico` por PCA externo quando:

- uma escrita `ACEITA` teve seu efeito comprovado; ou
- uma escrita inicialmente incerta teve seu efeito comprovado pela convergência final.

Não gerar histórico para:

- `NENHUMA_ACAO`;
- validação rejeitada;
- sequência composta somente por escritas rejeitadas;
- falha;
- conflito;
- contenção;
- rollback;
- resultado ainda incerto.

Classificação:

- código 5 — Publicação inicial;
- código 8 — Retificação;
- código 9 — Exclusão integral.

Mensagem obrigatória:

- exercício;
- UO ou indicação explícita de consolidado;
- UASG externa;
- `sequencialPca`;
- ação confirmada;
- em Retificação, totais agregados de inclusões e exclusões.

Limites:

- não listar itens ou UOs individualmente;
- não copiar payload ou resposta PNCP;
- não registrar segredos;
- não concatenar motivos individuais;
- usar as diferenças entre a fotografia inicialmente observada e a desejada para as contagens, inclusive quando houver reconciliação adicional.

### Testes da Sprint 4.11

Cobrir por TDD:

- `NENHUMA_ACAO` convergente com e sem versões pendentes;
- ausência conclusiva e correção coerente do estado operacional;
- proibição de confirmação após observação inconclusiva ou conflitante;
- observação final obrigatória depois de cada escrita;
- Publicação aceita com novo sequencial;
- Publicação incerta sem adoção automática de candidato;
- Retificação parcial e ordem excluir→incluir;
- Exclusão integral confirmada;
- escrita rejeitada sem retry;
- escrita incerta com uma única reconciliação;
- nova incerteza sem terceira tentativa;
- resultado final reconciliável, bloqueado e sistêmico;
- rollback preservando o desfecho original;
- falha de rollback prevalecendo como sistêmica;
- confirmação apenas de Planos pendentes;
- confirmação conjunta no consolidado;
- estado, confirmação e auditoria na mesma unidade local;
- falha em cada etapa local impedindo commit;
- auditoria individual e consolidada;
- procedimentos 5, 8 e 9;
- conteúdo integral da mensagem;
- ausência de auditoria nos casos proibidos;
- consumo da intenção de histórico sem instante PHP;
- integração do `ProcessadorUnidadeInterno` com o finalizador concreto usando somente doubles.

### Critérios de aceitação da Sprint 4.11

- Toda unidade iniciada termina confirmada ou desfeita.
- Nenhum sucesso é produzido antes do commit local.
- Toda escrita é seguida por observação completa.
- Escrita incerta nunca é repetida antes da observação.
- Há no máximo uma reconciliação adicional.
- Planos pendentes em `NENHUMA_ACAO` só são confirmados por convergência conclusiva sob exclusividade.
- Auditoria respeita RN-AUD-001 a RN-AUD-005 e recebe instante apenas no futuro adapter Oracle.
- Confirmação respeita RN-PLA-007.
- Nenhum adapter, SQL, HTTP, cURL, ADOdb ou OCI8 é implementado.
- Suíte relacionada e regressão permanecem verdes.

## 4. Sprint 4.12 — Coordenação global, Response e gate final

### Objetivo

Conectar o fluxo existente da Application em um UseCase final, agregar resultados unitários já terminais e produzir a Response global sem criar caminho paralelo às Sprints 4.1 a 4.5.

**Complexidade:** média.

### Reutilização obrigatória do fluxo existente

```text
PublicarPcaRequest existente
        ↓
PreparadorExecucaoPublicacaoPca existente
        ↓
CoordenadorVarreduraUnidades existente
        ↓
ResultadoProcessamentoUnidade existente
        ↓
PublicarPcaResponse
```

Reutilização:

- Sprint 4.1: `PublicarPcaRequest` com CNPJ e Ano de Execução;
- Sprint 4.2: validações e invariantes já protegidas pelos tipos da entrada;
- Sprint 4.3: política de decisão do Domain;
- Sprint 4.4: preparação e coordenação evoluídas pelos componentes atuais;
- Sprint 4.5: DTOs tipados de preparação, unidade e varredura.

Não criar:

- outro Request;
- outro Validator;
- validação duplicada de CNPJ ou Ano de Execução;
- outra política de decisão;
- DTOs unitários equivalentes;
- fluxo alternativo de preparação, autenticação ou processamento.

### Tipos globais

Criar `SituacaoPublicacaoPca` com:

- `SOLICITACAO_INVALIDA`;
- `CONCLUIDA`;
- `CONCLUIDA_COM_PENDENCIAS`;
- `INTERROMPIDA`.

Criar `CorrelacaoExecucao` como valor:

- opaco;
- imutável;
- não vazio;
- preservado sem interpretação ou alteração.

Criar `PublicarPcaResponse` contendo:

- situação global;
- resultados unitários conhecidos;
- falha global segura, quando aplicável;
- quantidade de unidades planejadas;
- quantidade de unidades visitadas/processadas;
- quantidade de unidades não processadas;
- correlação da execução.

Criar o ponto mínimo de entrada da correlação:

```php
PublicarPcaUseCase::executar(
    PublicarPcaRequest $request,
    CorrelacaoExecucao $correlacao
): PublicarPcaResponse
```

A Sprint 4.12 não define:

- geração da correlação;
- origem concreta;
- responsabilidade do Composition Root;
- integração concreta com logger ou fachada.

### Construções válidas da Response

A Response deve oferecer construções orientadas aos quatro estados, incluindo:

```php
PublicarPcaResponse::solicitacaoInvalida(
    FalhaSegura $falha,
    CorrelacaoExecucao $correlacao
): PublicarPcaResponse
```

Invariantes de `SOLICITACAO_INVALIDA`:

- falha global obrigatória;
- nenhum resultado unitário;
- contagens planejada, processada e não processada iguais a zero;
- correlação preservada integralmente.

A construção existe como contrato válido da Application. A decisão sobre qual componente da Presentation a utilizará permanece na Fase 7. Não implementar Controller nem entrada bruta.

Invariantes gerais:

- `SOLICITACAO_INVALIDA` e `INTERROMPIDA` exigem falha global segura;
- `CONCLUIDA` e `CONCLUIDA_COM_PENDENCIAS` não carregam falha sistêmica global;
- `CONCLUIDA` aceita zero resultados ou somente `CONCLUIDO` e `SEM_ALTERACAO`;
- `CONCLUIDA_COM_PENDENCIAS` exige ao menos um `FALHA_RECONCILIAVEL` ou `BLOQUEADO`;
- resultados unitários não podem duplicar unidades externas;
- resultados conhecidos ≤ unidades visitadas ≤ unidades planejadas;
- não processadas = planejadas − visitadas;
- a correlação recebida é devolvida sem alteração.

### Ajustes nos resultados intermediários

`ResultadoPreparacaoPublicacaoPca`:

- quando a falha ocorrer depois da formação das unidades, preservar as unidades já conhecidas;
- não fabricar unidades quando a preparação falhar antes dessa formação.

`ResultadoVarreduraUnidades`:

- preservar a quantidade de unidades visitadas;
- contar como visitada a unidade que provocou interrupção sistêmica, ainda que ela não possua resultado unitário seguro;
- não fabricar resultado para essa unidade;
- preservar resultados anteriores;
- não incluir unidades ainda não visitadas.

### Microciclo 1 — Response e invariantes globais

Implementar e testar:

- `CorrelacaoExecucao`;
- as quatro situações globais;
- construções válidas da Response;
- invariantes de cada situação;
- preservação exata da correlação;
- rejeição de combinações incoerentes de falha, resultados e contagens;
- construção válida de `SOLICITACAO_INVALIDA`, sem produtor de Presentation.

### Microciclo 2 — Preparação global

O UseCase final deve:

1. receber `PublicarPcaRequest` existente e correlação opaca;
2. chamar `PreparadorExecucaoPublicacaoPca`;
3. mapear `SEM_UNIDADES` para:
   - `CONCLUIDA`;
   - zero resultados;
   - todas as contagens zeradas;
4. mapear preparação interrompida para:
   - `INTERROMPIDA`;
   - falha global segura;
   - unidades planejadas já conhecidas;
   - zero unidades visitadas;
   - demais unidades como não processadas;
5. não chamar a varredura quando a preparação não estiver concluída;
6. converter exceção inesperada em interrupção segura.

O UseCase tipado não produz `SOLICITACAO_INVALIDA`. A futura Presentation decidirá quando utilizar essa construção.

### Microciclo 3 — Varredura e resultado global

Quando a preparação estiver concluída:

- encaminhar exatamente contexto, sessão, CNPJ e unidades ao coordenador existente;
- não reprocessar ou revalidar internamente as unidades;
- mapear varredura concluída:
  - somente `CONCLUIDO` e `SEM_ALTERACAO` → `CONCLUIDA`;
  - presença de `FALHA_RECONCILIAVEL` ou `BLOQUEADO` → `CONCLUIDA_COM_PENDENCIAS`;
- mapear interrupção sistêmica para `INTERROMPIDA`;
- preservar:
  - resultados anteriores conhecidos;
  - quantidade planejada;
  - quantidade visitada;
  - quantidade não visitada;
  - correlação recebida;
- não fabricar resultados para unidades não visitadas ou para a unidade sistêmica sem resultado seguro.

### Microciclo 4 — Modos de publicação e continuidade

Demonstrar na composição final:

- zero unidades concluindo sem PNCP;
- uma unidade individual;
- várias unidades individuais;
- continuidade após `BLOQUEADO`;
- continuidade após `FALHA_RECONCILIAVEL`;
- interrupção sistêmica preservando resultados anteriores;
- modo consolidado com uma única unidade e um único resultado;
- ausência de sucessos independentes por UO no consolidado.

Esses testes verificam agregação e continuidade. Não repetem os ramos internos de finalização cobertos na 4.11.

### Microciclo 5 — Gate final da Application

Executar:

1. testes focados da Response e do UseCase final;
2. suíte completa da Application;
3. suíte do Domain;
4. regressão completa;
5. verificação da direção das dependências.

Confirmar:

- Domain e Application não importam Infrastructure;
- contratos não expõem HTTP, JSON, cURL, ADOdb, OCI8, SQL ou Scriptcase;
- nenhuma Port genérica foi criada;
- nenhum adapter concreto foi necessário;
- todas as capacidades da DI-007 são demonstradas por consumidores com doubles;
- o contrato global está pronto para a futura Presentation.

### Critérios de aceitação da Sprint 4.12

- O UseCase final reutiliza Request, preparação, política e DTOs existentes.
- Não existe fluxo paralelo às Sprints 4.1 a 4.5.
- Os quatro estados globais possuem construção contratualmente válida.
- `SOLICITACAO_INVALIDA` possui invariantes testadas sem Controller ou entrada bruta.
- Zero Planos resulta em `CONCLUIDA` com contagens zeradas.
- Resultados unitários conhecidos são preservados após interrupção.
- Contagens distinguem unidades planejadas, visitadas e não processadas.
- Os modos individual e consolidado produzem a cardinalidade correta.
- A correlação opaca entra por `executar`, atravessa a composição e chega inalterada à Response.
- A geração e a origem concreta da correlação permanecem adiadas.
- Todas as suítes permanecem verdes.
- Somente a conclusão desse gate autoriza a Fase 5, respeitados os gates das DI-008 a DI-010.

## 5. Justificativa da divisão de responsabilidades

| Responsabilidade | Sprint | Motivo |
|---|---|---|
| Observação posterior à escrita | 4.11 | Ainda ocorre sob lock e determina a convergência da unidade |
| Reconciliação adicional | 4.11 | Reutiliza os fatos, sessão e transação da unidade |
| Estado operacional, confirmação e auditoria | 4.11 | Devem ser persistidos atomicamente antes do resultado unitário |
| Commit, rollback e resultado unitário | 4.11 | Encerram a unidade iniciada na Sprint 4.10 |
| Procedimento e mensagem de auditoria | 4.11 | Dependem da ação e da convergência daquela publicação externa |
| Preparação + varredura | 4.12 | Coordenam unidades já independentes e terminalmente finalizadas |
| Response e estado global | 4.12 | Dependem da composição dos resultados unitários |
| Preservação de resultados após interrupção | 4.12 | É propriedade da varredura e da execução global |
| Contagens planejadas/processadas | 4.12 | Exigem conhecimento do conjunto completo de unidades |
| Correlação global | 4.12 | Pertence ao contrato da execução, não à transação de uma unidade |
| Gate da Application | 4.12 | Somente o fluxo completo prova a Application antes da Infrastructure |

## 6. Itens deliberadamente adiados

### Fase 5 — Infrastructure

- adapter Oracle de `RegistroAuditoriaPort` com `SYSDATE`;
- adapters Oracle de estado, confirmação e unidade de trabalho;
- logger concreto;
- SQL, schema, constraints, ADOdb e OCI8;
- cURL, TLS, autenticação e transporte PNCP;
- paginação, serialização e parâmetros condicionados;
- classificação concreta de respostas HTTP;
- testes contratuais Oracle e PNCP.

### Fase 7 — Presentation

- Controller;
- conversão de entrada bruta;
- decisão sobre qual componente produzirá `SOLICITACAO_INVALIDA`;
- Presenter;
- ViewModel;
- fachada e fronteira pública de exceções.

### Fase 8 — Integração real e composição final

- geração concreta da correlação;
- responsabilidade concreta por fornecê-la;
- Composition Root final;
- perfil Scriptcase;
- integração com a aplicação Blank;
- conexão e identidade reais;
- smoke test do pacote.

### Fora da V1

- Domain Events;
- barramento;
- filas ou execução assíncrona;
- idempotência persistida;
- compensação automática;
- transação distribuída;
- loop de reconciliação;
- baseline detalhado;
- comparação de conteúdo;
- `PATCH`;
- adoção automática de PCA sem vínculo;
- particionamento acima de 1000 itens.

## 7. Limites de execução futura

- Preservar alterações locais preexistentes no workspace.
- Implementar cada microciclo em Red → Green → Refactor.
- Usar `@regra RN-*` quando o teste evidenciar comportamento de negócio.
- Não criar adapter para facilitar testes da Application; usar doubles.
- Não transformar resultado intermediário de escrita em prova de sucesso.
- Não preencher lacunas com defaults, mensagens ou decisões inferidas.
- Não iniciar a Sprint 4.12 antes de a 4.11 produzir resultados unitários terminais.
- Não iniciar a Fase 5 antes da aceitação integral da Sprint 4.12.
