# Catálogo de Regras de Negócio

**Classificação documental:** Autoritativo  
**Status:** Vigente  
**Autoridade/Escopo:** redação normativa das regras de negócio do backend PCA/PNCP

## 1. Finalidade, autoridade e escopo

Este documento é a fonte autoritativa da redação vigente das regras de negócio
do sistema PCA relacionadas ao planejamento, à homologação, à publicação no
PNCP e à auditoria dos procedimentos concluídos.

Os demais documentos devem referenciar os identificadores deste catálogo em
vez de manter redações normativas concorrentes. Em especial:

- `docs/vigentes/autoritativos/modelo_de_dominio.md` explica a estrutura e as
  relações do modelo;
- `docs/vigentes/autoritativos/decisoes_de_integracao.md` é a fonte das
  decisões e dos contratos de integração;
- `docs/vigentes/apoio/contexto_funcional_do_sistema_pca.md` apresenta
  usuários, etapas e contexto;
- `docs/vigentes/autoritativos/fluxo_de_implementacao.md` descreve a
  colaboração dinâmica entre camadas e portas;
- [`docs/vigentes/autoritativos/roadmap.md`](roadmap.md) organiza a ordem de
  construção;
- os testes constituem evidência executável, mas não substituem este catálogo.

Uma divergência entre este catálogo e outro documento bloqueia a implementação
da regra afetada até que a redação seja reconciliada na fonte apropriada.

Este catálogo não normatiza detalhes arquiteturais, tecnológicos ou físicos,
como ownership de portas, HTTP, Oracle, Scriptcase, tabelas, colunas, locks,
serialização ou transporte.

## 2. Convenções

### 2.1 Linguagem normativa

Os termos `DEVE`, `NÃO DEVE` e `PODE` expressam, respectivamente, obrigação,
proibição e permissão de negócio.

### 2.2 Identificadores

Cada regra usa o formato:

```text
RN-CONTEXTO-NNN
```

Política dos identificadores:

- um ID nunca é reutilizado ou renumerado depois de materializado;
- refinamento textual sem mudança semântica preserva o ID;
- mudança semântica cria novo ID e referencia a regra substituída;
- textos antigos superados não recebem IDs como regras vigentes e são
  rastreados na matriz de conflitos resolvidos;
- regras vigentes descobertas anteriormente recebem IDs neste catálogo.

### 2.3 Status normativo

| Status | Significado |
| --- | --- |
| `Em validação` | Regra candidata ainda não confirmada pela autoridade de negócio |
| `Vigente` | Regra aprovada e aplicável |
| `Substituída` | Regra preservada para rastreabilidade, mas não aplicável |

### 2.4 Evidências de teste

Uma regra pode possuir vários testes, e um teste pode evidenciar várias regras.
Quando houver evidência executável, o teste deve usar:

```text
@regra RN-...
```

Testes estritamente técnicos não precisam de RN. A ausência de teste é
registrada explicitamente e não autoriza inferir comportamento.

## 3. Índice

| ID | Título | Contexto | Status | Decisões relacionadas |
| --- | --- | --- | --- | --- |
| `RN-CAD-001` | CNPJ do órgão | Cadastros | Vigente | DI-009, DI-010 |
| `RN-CAD-002` | Ano de execução permitido | Cadastros | Vigente | DI-010 |
| `RN-CAD-003` | Identificação do Representante | Cadastros | Vigente | DI-006 |
| `RN-CAD-004` | Validade e distinção da Lotação | Cadastros | Vigente | DI-006 |
| `RN-CAD-005` | Composição da Unidade Orçamentária | Cadastros | Vigente | DI-003, DI-008 |
| `RN-CAD-006` | Cadastro ativo e UASG da UO participante | Cadastros | Vigente | DI-003, DI-008 |
| | | | | |
| `RN-FLX-001` | Planejamento ordinário | Fluxo | Vigente | — |
| `RN-FLX-002` | Demanda extraordinária | Fluxo | Vigente | — |
| `RN-FLX-003` | Consolidação e desconsolidação | Fluxo | Vigente | — |
| `RN-FLX-004` | Revisão de Demanda avaliada | Fluxo | Vigente | — |
| | | | | |
| `RN-DEM-001` | Quantidade, categoria e origem | Demandas | Vigente | — |
| `RN-DEM-002` | Identidade global da Demanda | Demandas | Vigente | DI-001, DI-002 |
| `RN-DEM-003` | Imutabilidade após homologação | Demandas | Vigente | DI-001, DI-007 |
| `RN-DEM-004` | Ajuste da data desejada | Demandas | Vigente | DI-002, DI-007 |
| `RN-DEM-005` | Substituição de Demanda homologada | Demandas | Vigente | DI-001 |
| `RN-DEM-006` | Versão gerada por cancelamento | Demandas | Vigente | DI-001 |
| `RN-DEM-007` | Versão gerada por DFD-E | Demandas | Vigente | DI-001 |
| `RN-DEM-008` | Cancelamento da última Demanda ativa | Demandas | Vigente | DI-001 |
| `RN-DEM-009` | Situações participantes da fotografia | Demandas | Vigente | DI-008 |
| | | | | |
| `RN-PLA-001` | Identidade lógica do Plano | Planos | Vigente | DI-001, DI-008 |
| `RN-PLA-002` | Origem e versionamento do Plano | Planos | Vigente | DI-001, DI-008 |
| `RN-PLA-003` | Seleção da versão vigente | Planos | Vigente | DI-008 |
| `RN-PLA-004` | Fotografia completa do Plano | Planos | Vigente | DI-001, DI-008 |
| `RN-PLA-005` | Plano sem Demandas ativas | Planos | Vigente | DI-001, DI-005 |
| `RN-PLA-006` | Unidade e exercício da fotografia | Planos | Vigente | DI-001, DI-008 |
| `RN-PLA-007` | Confirmação versionada da sincronização | Planos | Vigente | DI-005, DI-008 |
| | | | | |
| `RN-PUB-001` | Decisão de publicar | Publicação | Vigente | DI-001, DI-002, DI-005 |
| `RN-PUB-002` | Decisão de retificar | Publicação | Vigente | DI-001, DI-002, DI-005 |
| `RN-PUB-003` | Decisão de excluir o PCA | Publicação | Vigente | DI-001, DI-003, DI-005 |
| `RN-PUB-004` | Decisão de não agir | Publicação | Vigente | DI-002, DI-005 |
| `RN-PUB-005` | Versão não determina a ação | Publicação | Vigente | DI-001, DI-005 |
| `RN-PUB-006` | Correspondência com o conjunto vigente | Publicação | Vigente | DI-001, DI-002, DI-007 |
| `RN-PUB-007` | Nova publicação após exclusão | Publicação | Vigente | DI-001, DI-005 |
| `RN-PUB-008` | Estados da publicação | Publicação | Vigente | DI-005, DI-008 |
| `RN-PUB-009` | Publicação no modo individual | Publicação | Vigente | DI-003, DI-004 |
| `RN-PUB-010` | Publicação no modo consolidado | Publicação | Vigente | DI-003, DI-004 |
| `RN-PUB-011` | UASG por modo de publicação | Publicação | Vigente | DI-003, DI-008 |
| | | | | |
| `RN-AUD-001` | Fatos que geram histórico | Auditoria | Vigente | DI-006 |
| `RN-AUD-002` | Procedimentos de publicação no PNCP | Auditoria | Vigente | DI-006 |
| `RN-AUD-003` | Unidade do histórico | Auditoria | Vigente | DI-006 |
| `RN-AUD-004` | Responsável e Lotação do histórico | Auditoria | Vigente | DI-006, DI-010 |
| `RN-AUD-005` | Conteúdo da mensagem de auditoria | Auditoria | Vigente | DI-006 |

## 4. Regras detalhadas

## 4.1 Cadastros

### RN-CAD-001 — CNPJ do órgão

- **Status normativo:** Vigente.
- **Enunciado normativo:** O órgão responsável pela publicação DEVE ser
  identificado por CNPJ válido com 14 posições no padrão alfanumérico vigente:
  as 12 primeiras posições admitem letras e números, e as duas últimas são
  dígitos verificadores numéricos. Separadores são removidos e letras são
  normalizadas para maiúsculas antes da validação.
- **Origem ou autoridade:** contrato de CNPJ alfanumérico consolidado na DI-009
  e modelo vigente implementado no Domain.
- **Contexto e justificativa:** o CNPJ identifica o órgão perante o PNCP sem
  incorporar formato de banco ou de transporte.
- **Exemplo:** um CNPJ alfanumérico com dígitos verificadores válidos é aceito
  mesmo quando informado com separadores usuais.
- **Contraexemplo:** aceitar 14 caracteres sem validar os dígitos verificadores.
- **Elementos afetados:** CNPJ, órgão responsável e solicitação de publicação.
- **Evidências de teste:** `tests/Domain/ValueObjects/CnpjTest.php`.
- **Decisões e documentos relacionados:** DI-009, DI-010 e
  `docs/vigentes/autoritativos/modelo_de_dominio.md`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-CAD-002 — Ano de execução permitido

- **Status normativo:** Vigente.
- **Enunciado normativo:** O ano de execução DEVE ser o ano corrente ou o ano
  imediatamente seguinte. Anos anteriores ou posteriores a esse intervalo NÃO
  DEVEM ser aceitos.
- **Origem ou autoridade:** `docs/vigentes/autoritativos/modelo_de_dominio.md` e comportamento vigente
  do Value Object.
- **Contexto e justificativa:** o intervalo atende ao planejamento em curso e
  ao planejamento ordinário do exercício seguinte.
- **Exemplo:** durante 2026, os anos 2026 e 2027 são admitidos.
- **Contraexemplo:** durante 2026, aceitar 2025 ou 2028.
- **Elementos afetados:** AnoExecucao, Demanda, Plano e solicitação.
- **Evidências de teste:** `tests/Domain/ValueObjects/AnoExecucaoTest.php`.
- **Decisões e documentos relacionados:** DI-010 e
  `docs/vigentes/autoritativos/modelo_de_dominio.md`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-CAD-003 — Identificação do Representante

- **Status normativo:** Vigente.
- **Enunciado normativo:** O Representante DEVE possuir identificador e nome
  obrigatórios e DEVE representar a pessoa responsável por uma ação de
  negócio.
- **Origem ou autoridade:** `docs/vigentes/autoritativos/modelo_de_dominio.md` e DI-006.
- **Contexto e justificativa:** a identificação sustenta a autoria das ações
  auditáveis sem confundir o Representante com autenticação ou ocorrência
  histórica.
- **Exemplo:** o usuário autenticado é resolvido para um Representante com ID e
  nome antes do registro de um procedimento confirmado.
- **Contraexemplo:** atribuir a autoria ao usuário técnico do banco.
- **Elementos afetados:** Representante e auditoria.
- **Evidências de teste:** `tests/Domain/ValueObjects/RepresentanteTest.php`.
- **Decisões e documentos relacionados:** DI-006 e
  `docs/vigentes/autoritativos/modelo_de_dominio.md`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-CAD-004 — Validade e distinção da Lotação

- **Status normativo:** Vigente.
- **Enunciado normativo:** A Lotação DEVE possuir identificador inteiro
  positivo e descrição obrigatória. Lotação e Unidade Orçamentária NÃO DEVEM
  ser tratadas como o mesmo conceito.
- **Origem ou autoridade:** `docs/vigentes/autoritativos/modelo_de_dominio.md`.
- **Contexto e justificativa:** a Lotação identifica a unidade organizacional
  relacionada à autoria, enquanto a UO organiza o Plano.
- **Exemplo:** a Lotação do Representante pode ser uma coordenadoria que não é
  uma UO.
- **Contraexemplo:** reutilizar uma UnidadeOrcamentaria para representar toda
  Lotação.
- **Elementos afetados:** Lotacao, Representante, UnidadeOrcamentaria e
  auditoria.
- **Evidências de teste:** `tests/Domain/ValueObjects/LotacaoTest.php`.
- **Decisões e documentos relacionados:** DI-006 e
  `docs/vigentes/autoritativos/modelo_de_dominio.md`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-CAD-005 — Composição da Unidade Orçamentária

- **Status normativo:** Vigente.
- **Enunciado normativo:** Uma Unidade Orçamentária válida DEVE possuir
  Lotação, descrição e UASG. A descrição DEVE ser normalizada sem espaços nas
  extremidades, possuir ao menos três caracteres e PODE conter acentos e
  números.
- **Origem ou autoridade:** `docs/vigentes/autoritativos/modelo_de_dominio.md`.
- **Contexto e justificativa:** a UO identifica a unidade responsável por um
  Plano, enquanto a UASG identifica a unidade compradora perante os sistemas
  governamentais.
- **Exemplo:** uma UO com Lotação, descrição “Diretoria 1” e UASG preenchida é
  válida.
- **Contraexemplo:** uma UO sem UASG ou com descrição de dois caracteres.
- **Elementos afetados:** UnidadeOrcamentaria, Lotacao, Uasg e Plano.
- **Evidências de teste:** `tests/Domain/Entities/UnidadeOrcamentariaTest.php` e
  `tests/Domain/ValueObjects/UasgTest.php`.
- **Decisões e documentos relacionados:** DI-003, DI-008 e
  `docs/vigentes/autoritativos/modelo_de_dominio.md`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-CAD-006 — Cadastro ativo e UASG da UO participante

- **Status normativo:** Vigente.
- **Enunciado normativo:** Para cada Unidade Orçamentária participante da
  publicação DEVE existir exatamente um cadastro ativo com UASG preenchida.
  Ausência, duplicidade ou UASG não preenchida DEVE bloquear a unidade antes da
  publicação.
- **Origem ou autoridade:** DI-008.
- **Contexto e justificativa:** uma UO precisa resolver inequivocamente a
  unidade compradora externa que representa.
- **Exemplo:** duas UOs distintas podem participar do exercício quando cada uma
  possui seu único cadastro ativo.
- **Contraexemplo:** escolher arbitrariamente um de dois cadastros ativos para
  a mesma UO.
- **Elementos afetados:** UnidadeOrcamentaria, Uasg e elegibilidade da
  publicação.
- **Evidências de teste:** evidência parcial em
  `tests/Domain/Entities/UnidadeOrcamentariaTest.php` e
  `tests/Domain/ValueObjects/UasgTest.php`; a cardinalidade ativa ainda não
  possui teste.
- **Decisões e documentos relacionados:** DI-003 e DI-008.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

## 4.2 Fluxo de planejamento

### RN-FLX-001 — Planejamento ordinário

- **Status normativo:** Vigente.
- **Enunciado normativo:** O planejamento ordinário DEVE ocorrer no ano
  anterior à execução. Suas Demandas DEVEM percorrer registro, avaliação e
  consolidação, e DEVEM ser homologadas em lote pela Presidência.
- **Origem ou autoridade:** confirmação do usuário consolidada no handoff
  principal, seção 7, e `docs/vigentes/apoio/contexto_funcional_do_sistema_pca.md`.
- **Contexto e justificativa:** o fluxo formaliza o conjunto ordinário que
  comporá o PCA do exercício seguinte.
- **Exemplo:** Demandas ordinárias para 2027 são planejadas e homologadas em
  lote em 2026.
- **Contraexemplo:** homologar individualmente uma Demanda ordinária como se
  fosse DFD-E.
- **Elementos afetados:** Demanda ordinária, avaliação, consolidação e
  homologação.
- **Evidências de teste:** ausentes.
- **Decisões e documentos relacionados:** `docs/vigentes/apoio/contexto_funcional_do_sistema_pca.md` e
  `docs/vigentes/apoio/diagramas/fluxograma_PCA.png`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-FLX-002 — Demanda extraordinária

- **Status normativo:** Vigente.
- **Enunciado normativo:** Uma Demanda extraordinária, DFD-E, DEVE percorrer
  fluxo próprio e DEVE ser homologada individualmente pela Presidência.
- **Origem ou autoridade:** confirmação do usuário consolidada no handoff
  principal, seção 7.
- **Contexto e justificativa:** a DFD-E incorpora necessidade posterior ao
  planejamento ordinário já homologado.
- **Exemplo:** uma necessidade extraordinária é avaliada e homologada
  individualmente após a homologação ordinária.
- **Contraexemplo:** incluir uma DFD-E no Plano vigente sem homologação.
- **Elementos afetados:** Demanda extraordinária e homologação.
- **Evidências de teste:** ausentes.
- **Decisões e documentos relacionados:** `docs/vigentes/apoio/contexto_funcional_do_sistema_pca.md`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-FLX-003 — Consolidação e desconsolidação

- **Status normativo:** Vigente.
- **Enunciado normativo:** Somente Demandas autorizadas PODEM ser consolidadas.
  A consolidação DEVE bloquear novas alterações. Antes da homologação, a
  desconsolidação PODE reabrir as Demandas, que DEVEM percorrer novamente as
  etapas aplicáveis de avaliação e consolidação.
- **Origem ou autoridade:** `docs/vigentes/apoio/contexto_funcional_do_sistema_pca.md` e
  `docs/vigentes/apoio/diagramas/fluxograma_PCA.png`.
- **Contexto e justificativa:** consolidação representa o fechamento da análise
  pela UO antes da aprovação presidencial.
- **Exemplo:** uma Demanda revisada após desconsolidação retorna ao fluxo antes
  de ser novamente consolidada.
- **Contraexemplo:** alterar diretamente uma Demanda enquanto o conjunto
  permanece consolidado.
- **Elementos afetados:** Demanda, consolidação, desconsolidação e avaliação.
- **Evidências de teste:** ausentes.
- **Decisões e documentos relacionados:** `docs/vigentes/apoio/contexto_funcional_do_sistema_pca.md` e
  `docs/vigentes/apoio/diagramas/fluxograma_PCA.png`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-FLX-004 — Revisão de Demanda avaliada

- **Status normativo:** Vigente.
- **Enunciado normativo:** Quando uma Demanda avaliada for encaminhada para
  revisão, ela DEVE retornar ao operador para correção e DEVE voltar ao fluxo
  de avaliação.
- **Origem ou autoridade:** `docs/vigentes/apoio/contexto_funcional_do_sistema_pca.md` e
  `docs/vigentes/apoio/diagramas/fluxograma_PCA.png`.
- **Contexto e justificativa:** a revisão não equivale a autorização ou
  homologação e preserva o controle do workflow.
- **Exemplo:** a gerência solicita revisão, o operador corrige e submete
  novamente.
- **Contraexemplo:** considerar autorizada uma Demanda apenas porque a revisão
  foi preenchida.
- **Elementos afetados:** Demanda, operador e avaliação.
- **Evidências de teste:** ausentes.
- **Decisões e documentos relacionados:** `docs/vigentes/apoio/contexto_funcional_do_sistema_pca.md` e
  `docs/vigentes/apoio/diagramas/fluxograma_PCA.png`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

## 4.3 Demandas

### RN-DEM-001 — Quantidade, categoria, classificação e origem

- **Status normativo:** Vigente.
- **Enunciado normativo:** Uma Demanda DEVE possuir quantidade maior que zero.
  Sua categoria DEVE pertencer ao domínio de categorias do item PCA vigente
  (`1` a `8`), sua classificação de catálogo DEVE ser `1 = Material` ou `2 =
  Serviço`, e sua origem DEVE ser Normal ou Extraordinária.
- **Origem ou autoridade:** esclarecimento do mantenedor em 08/09/2026 e
  domínio oficial PNCP.
- **Contexto e justificativa:** esses valores classificam a necessidade e
  impedem Demandas sem quantidade útil.
- **Exemplo:** uma Demanda da categoria Soluções de TIC, classificada como
  Material, origem Extraordinária e quantidade 1 é válida quanto a esses
  atributos.
- **Contraexemplo:** quantidade zero, categoria `9` ou classificação `3`.
- **Elementos afetados:** Demanda.
- **Evidências de teste:** `tests/Domain/Entities/DemandaTest.php`.
- **Decisões e documentos relacionados:** `docs/vigentes/autoritativos/modelo_de_dominio.md`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo; corrigida em
  08/09/2026 para separar categoria do item PCA e classificação
  Material/Serviço após validação do schema SGI e do domínio PNCP.

### RN-DEM-002 — Identidade global da Demanda

- **Status normativo:** Vigente.
- **Enunciado normativo:** Cada Demanda DEVE possuir `ID_DEMANDA` globalmente
  único e estável durante seu ciclo de vida.
- **Origem ou autoridade:** confirmação do usuário consolidada nas DI-001 e
  DI-003.
- **Contexto e justificativa:** a identidade distingue inequivocamente as
  necessidades inclusive entre diferentes UOs.
- **Exemplo:** duas UOs não podem possuir Demandas com o mesmo ID.
- **Contraexemplo:** renumerar uma Demanda porque sua situação mudou.
- **Elementos afetados:** Demanda e identidade.
- **Evidências de teste:** ausentes para unicidade global e estabilidade.
- **Decisões e documentos relacionados:** DI-001 e DI-002. O uso desse ID em
  contrato externo é decisão de integração e não integra o enunciado.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-DEM-003 — Imutabilidade após homologação

- **Status normativo:** Vigente.
- **Enunciado normativo:** Depois de homologada, uma Demanda NÃO DEVE ter seus
  dados de negócio alterados, ressalvado exclusivamente o ajuste de data
  desejada permitido por `RN-DEM-004`.
- **Origem ou autoridade:** confirmação do usuário consolidada no handoff
  principal, seção 7, refinada na DI-007.
- **Contexto e justificativa:** homologação aprova formalmente a necessidade;
  alterações materiais exigem cancelamento e nova aprovação.
- **Exemplo:** alterar descrição ou quantidade exige aplicar `RN-DEM-005`.
- **Contraexemplo:** editar diretamente o objeto de uma Demanda homologada.
- **Elementos afetados:** Demanda e homologação.
- **Evidências de teste:** ausentes.
- **Decisões e documentos relacionados:** DI-001, DI-007, `RN-DEM-004` e
  `RN-DEM-005`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-DEM-004 — Ajuste da data desejada

- **Status normativo:** Vigente.
- **Enunciado normativo:** O Núcleo de Compras PODE ajustar a data desejada de
  uma Demanda homologada durante a elaboração do calendário, preservando o
  mesmo `ID_DEMANDA`.
- **Origem ou autoridade:** `docs/vigentes/apoio/contexto_funcional_do_sistema_pca.md` e refinamento
  aprovado na DI-007.
- **Contexto e justificativa:** o calendário de contratações pode ajustar a
  oportunidade da compra sem substituir a necessidade homologada.
- **Exemplo:** mover a data desejada de uma Demanda para outro mês do mesmo
  planejamento, mantendo sua identidade.
- **Contraexemplo:** aproveitar o ajuste de data para mudar quantidade ou
  descrição.
- **Elementos afetados:** Demanda, data desejada e calendário.
- **Evidências de teste:** ausentes.
- **Decisões e documentos relacionados:** DI-002 e DI-007. O alcance dessa
  mudança na sincronização V1 é decisão de integração e não integra o
  enunciado.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-DEM-005 — Substituição de Demanda homologada

- **Status normativo:** Vigente.
- **Enunciado normativo:** Para modificar dados de negócio de uma Demanda
  homologada fora da exceção `RN-DEM-004`, a Demanda existente DEVE ser
  cancelada com justificativa estruturada e uma nova Demanda DEVE ser criada e
  submetida novamente a workflow, avaliação e homologação.
- **Origem ou autoridade:** confirmação do usuário consolidada no handoff
  principal, seção 7.
- **Contexto e justificativa:** o cancelamento preserva o fato homologado
  original, e a nova Demanda preserva a aprovação formal das mudanças.
- **Exemplo:** para mudar a quantidade, cancela-se a Demanda original e cria-se
  outra.
- **Contraexemplo:** sobrescrever a quantidade da Demanda homologada.
- **Elementos afetados:** Demanda, cancelamento, justificativa e homologação.
- **Evidências de teste:** ausentes.
- **Decisões e documentos relacionados:** DI-001, `RN-DEM-003` e
  `RN-DEM-004`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-DEM-006 — Versão gerada por cancelamento

- **Status normativo:** Vigente.
- **Enunciado normativo:** O cancelamento efetivo de uma Demanda homologada
  DEVE gerar nova versão do Plano de sua UO e exercício.
- **Origem ou autoridade:** confirmação do usuário consolidada no handoff
  principal, seção 7.
- **Contexto e justificativa:** o cancelamento altera a fotografia homologada
  vigente.
- **Exemplo:** cancelar uma Demanda da versão 2 gera um novo marco de versão.
- **Contraexemplo:** retirar a Demanda da fotografia sem gerar nova versão.
- **Elementos afetados:** Demanda, Plano, cancelamento e versão.
- **Evidências de teste:** ausentes.
- **Decisões e documentos relacionados:** DI-001 e `RN-PLA-002`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-DEM-007 — Versão gerada por DFD-E

- **Status normativo:** Vigente.
- **Enunciado normativo:** A homologação de uma DFD-E DEVE gerar nova versão do
  Plano da UO e exercício correspondentes.
- **Origem ou autoridade:** confirmação do usuário consolidada no handoff
  principal, seção 7.
- **Contexto e justificativa:** a nova necessidade homologada altera a
  fotografia vigente do Plano.
- **Exemplo:** homologar D4 depois da versão que continha D1, D2 e D3 gera nova
  versão cuja fotografia vigente contém D1, D2, D3 e D4.
- **Contraexemplo:** incorporar D4 à fotografia sem um novo marco de
  homologação.
- **Elementos afetados:** DFD-E, Demanda, Plano e versão.
- **Evidências de teste:** ausentes.
- **Decisões e documentos relacionados:** DI-001, `RN-FLX-002` e
  `RN-PLA-002`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-DEM-008 — Cancelamento da última Demanda ativa

- **Status normativo:** Vigente.
- **Enunciado normativo:** O cancelamento da última Demanda ativa de um Plano
  PODE ser realizado. O Plano resultante continua válido com fotografia vazia.
- **Origem ou autoridade:** confirmação do usuário consolidada no handoff
  principal, seção 7.
- **Contexto e justificativa:** a ausência de Demandas ativas representa um
  estado de negócio necessário para excluir uma publicação externa existente.
- **Exemplo:** um Plano com apenas D1 pode ter D1 cancelada.
- **Contraexemplo:** rejeitar o cancelamento apenas porque o Plano ficaria
  vazio.
- **Elementos afetados:** Demanda, Plano e cancelamento.
- **Evidências de teste:** ausentes; o teste atual que proíbe Plano vazio
  evidencia uma premissa substituída.
- **Decisões e documentos relacionados:** DI-001, DI-005, `RN-PLA-005` e
  `RN-PUB-003`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-DEM-009 — Situações participantes da fotografia

- **Status normativo:** Vigente.
- **Enunciado normativo:** Demandas nas situações Homologada, Comprada e
  Publicada DEVEM integrar a fotografia vigente. Demandas Planejadas ou
  Canceladas NÃO DEVEM integrá-la. As transições Homologada → Comprada →
  Publicada NÃO DEVEM, por si mesmas, retirar a Demanda do PCA.
- **Origem ou autoridade:** DI-008.
- **Contexto e justificativa:** compra ou publicação não desfaz a necessidade
  homologada; o cancelamento é o estado que a retira do conjunto vigente.
- **Exemplo:** uma Demanda Comprada continua presente na fotografia.
- **Contraexemplo:** excluir uma Demanda do PCA apenas porque passou de
  Homologada para Publicada.
- **Elementos afetados:** Demanda, situação e fotografia do Plano.
- **Evidências de teste:** `tests/Domain/Enums/SituacaoDemandaTest.php` e
  `tests/Domain/Entities/PlanoTest.php`, nos cenários das cinco situações
  vigentes e de rejeição das não participantes.
- **Decisões e documentos relacionados:** DI-008 e `RN-PLA-004`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

## 4.4 Planos

### RN-PLA-001 — Identidade lógica do Plano

- **Status normativo:** Vigente.
- **Enunciado normativo:** A identidade lógica de um Plano DEVE ser a
  combinação de Unidade Orçamentária e Ano de Execução. Versão, identificador
  técnico, datas e sequencial externo NÃO DEVEM compor essa identidade.
- **Origem ou autoridade:** decisão central do Aggregate Plano consolidada na
  DI-001 e confirmada na DI-008.
- **Contexto e justificativa:** versões sucessivas representam estados da mesma
  identidade de planejamento.
- **Exemplo:** as versões 1 e 2 da mesma UO em 2027 pertencem ao mesmo Plano
  lógico.
- **Contraexemplo:** tratar cada versão como um Plano de negócio independente.
- **Elementos afetados:** Plano, UnidadeOrcamentaria e AnoExecucao.
- **Evidências de teste:** `tests/Domain/ValueObjects/IdentidadePlanoTest.php`,
  `tests/Domain/ValueObjects/IdentidadeUnidadeOrcamentariaTest.php` e
  `tests/Domain/Entities/PlanoTest.php`.
- **Decisões e documentos relacionados:** DI-001 e DI-008.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-PLA-002 — Origem e versionamento do Plano

- **Status normativo:** Vigente.
- **Enunciado normativo:** Um Plano DEVE nascer da homologação. Cada novo marco
  de homologação que altera sua fotografia DEVE produzir versão positiva e
  incremental para a combinação UO + Ano de Execução.
- **Origem ou autoridade:** confirmação do usuário consolidada no handoff
  principal, seção 7, e DI-008.
- **Contexto e justificativa:** a versão registra a evolução homologada sem
  alterar a identidade lógica.
- **Exemplo:** após a versão 1, a homologação de DFD-E produz a versão 2.
- **Contraexemplo:** criar versão zero ou reiniciar a sequência para a mesma UO
  e exercício.
- **Elementos afetados:** Plano, homologação e versão.
- **Evidências de teste:** evidência parcial de versão positiva em
  `tests/Domain/Entities/PlanoTest.php`; a semântica vigente completa ainda não
  está testada.
- **Decisões e documentos relacionados:** DI-001, DI-008, `RN-DEM-006` e
  `RN-DEM-007`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-PLA-003 — Seleção da versão vigente

- **Status normativo:** Vigente.
- **Enunciado normativo:** A versão vigente DEVE ser a maior versão existente
  para a combinação UO + Ano de Execução. A confirmação de sincronização NÃO
  DEVE participar dessa seleção.
- **Origem ou autoridade:** DI-008.
- **Contexto e justificativa:** os registros de Plano nascem somente da
  homologação, enquanto a confirmação possui outra semântica.
- **Exemplo:** entre versões 1, 2 e 3, a versão 3 fornece os metadados vigentes,
  ainda que sua confirmação esteja pendente.
- **Contraexemplo:** escolher a versão 2 porque é a última com confirmação
  preenchida.
- **Elementos afetados:** Plano, versão vigente e confirmação.
- **Evidências de teste:** ausentes; a seleção da maior versão pertence à
  reconstrução pelo Repository futuro.
- **Decisões e documentos relacionados:** DI-008 e `RN-PLA-007`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-PLA-004 — Fotografia completa do Plano

- **Status normativo:** Vigente.
- **Enunciado normativo:** O Plano vigente DEVE representar a fotografia
  completa das Demandas vigentes da mesma UO e exercício, independentemente da
  versão em que cada Demanda foi originalmente homologada.
- **Origem ou autoridade:** decisão central do Aggregate Plano consolidada na
  DI-001 e detalhada na DI-008.
- **Contexto e justificativa:** novas versões não copiam nem reassociam
  fisicamente as Demandas anteriores, mas a fotografia de negócio continua
  completa.
- **Exemplo:** após homologar D4 em uma nova versão, a fotografia contém D1,
  D2, D3 e D4 quando todas permanecem vigentes.
- **Contraexemplo:** carregar somente D4 porque ela se originou na versão mais
  recente.
- **Elementos afetados:** Plano, Demanda e versão de homologação.
- **Evidências de teste:** evidência parcial em
  `tests/Domain/Entities/PlanoTest.php`, cobrindo situações participantes,
  unicidade e uniformidade da fotografia. A reconstrução completa entre
  versões permanece responsabilidade do Repository futuro.
- **Decisões e documentos relacionados:** DI-001, DI-008 e `RN-DEM-009`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-PLA-005 — Plano sem Demandas ativas

- **Status normativo:** Vigente.
- **Enunciado normativo:** Um Plano vigente PODE possuir zero Demandas ativas e
  DEVE continuar representável nesse estado.
- **Origem ou autoridade:** DI-001 e DI-005.
- **Contexto e justificativa:** a fotografia vazia é consequência válida do
  cancelamento da última Demanda e permite decidir a exclusão do PCA externo.
- **Exemplo:** após cancelar a única Demanda ativa, o Plano continua existindo
  com fotografia vazia.
- **Contraexemplo:** impedir a reconstrução do Plano por ausência de Demandas.
- **Elementos afetados:** Plano, Demanda e publicação.
- **Evidências de teste:** `testDeveRepresentarPlanoSemDemandasAtivas` em
  `tests/Domain/Entities/PlanoTest.php`.
- **Decisões e documentos relacionados:** DI-001, DI-005, `RN-DEM-008` e
  `RN-PUB-003`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-PLA-006 — Unidade e exercício da fotografia

- **Status normativo:** Vigente.
- **Enunciado normativo:** Todas as Demandas de um Plano DEVEM pertencer à
  mesma Unidade Orçamentária e ao mesmo Ano de Execução que identificam esse
  Plano.
- **Origem ou autoridade:** invariante preservada na DI-001.
- **Contexto e justificativa:** a fotografia completa não mistura identidades
  lógicas distintas.
- **Exemplo:** o Plano da UO A para 2027 contém apenas Demandas dessa UO e desse
  exercício.
- **Contraexemplo:** adicionar ao Plano uma Demanda da UO B ou do exercício
  2028.
- **Elementos afetados:** Plano, Demanda, UnidadeOrcamentaria e AnoExecucao.
- **Evidências de teste:** `tests/Domain/Entities/PlanoTest.php`, nos cenários
  de UO e ano divergentes. O cenário baseado em `idPlano` não é evidência
  vigente.
- **Decisões e documentos relacionados:** DI-001 e DI-008.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-PLA-007 — Confirmação versionada da sincronização

- **Status normativo:** Vigente.
- **Enunciado normativo:** Cada versão homologada DEVE possuir sua própria
  confirmação de sincronização. Uma nova versão DEVE nascer sem confirmação;
  após convergência, somente as versões vigentes pendentes da unidade
  processada DEVEM ser confirmadas. Confirmações de versões anteriores NÃO
  DEVEM ser sobrescritas.
- **Origem ou autoridade:** DI-005.
- **Contexto e justificativa:** existência de publicação externa e convergência
  da versão vigente são fatos diferentes.
- **Exemplo:** a versão 1 permanece confirmada e a versão 2 nasce pendente até
  sua sincronização.
- **Contraexemplo:** copiar automaticamente a confirmação da versão 1 para a
  versão 2.
- **Elementos afetados:** Plano, versão e confirmação de sincronização.
- **Evidências de teste:** ausentes; testes atuais baseados apenas em
  `dataPublicacao` não cobrem a semântica vigente.
- **Decisões e documentos relacionados:** DI-005 e DI-008.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

## 4.5 Publicação e sincronização

### RN-PUB-001 — Decisão de publicar

- **Status normativo:** Vigente.
- **Enunciado normativo:** O sistema DEVE decidir `PUBLICAR` quando a
  fotografia vigente possuir Demandas e não houver PCA externo ativo conhecido
  para a unidade de publicação.
- **Origem ou autoridade:** DI-001, DI-002 e DI-005.
- **Contexto e justificativa:** publicação cria a representação externa inicial
  ou uma nova representação depois de exclusão confirmada.
- **Exemplo:** uma versão 2 nunca publicada, com Demandas vigentes, requer
  Publicação, e não Retificação.
- **Contraexemplo:** decidir Retificação apenas porque a versão é maior que 1.
- **Elementos afetados:** Plano, estado da publicação e decisão.
- **Evidências de teste:** `tests/Domain/Policies/PoliticaProcessamentoPlanoTest.php`,
  incluindo versão maior que 1 e nova Publicação após exclusão.
- **Decisões e documentos relacionados:** DI-001, DI-002, DI-005 e
  `RN-PUB-005`. A identificação de PCA externo sem vínculo é decisão de
  integração.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-PUB-002 — Decisão de retificar

- **Status normativo:** Vigente.
- **Enunciado normativo:** O sistema DEVE decidir `RETIFICAR` quando houver PCA
  externo ativo e mudanças homologadas no conjunto vigente a incorporar ou
  retirar. A Retificação DEVE preservar o mesmo `sequencialPca`.
- **Origem ou autoridade:** DI-001, DI-002 e DI-005.
- **Contexto e justificativa:** nova versão interna não cria automaticamente
  outro PCA; o recurso externo ativo é sincronizado.
- **Exemplo:** incluir nova Demanda homologada e retirar Demanda cancelada do
  PCA ativo, preservando seu sequencial.
- **Contraexemplo:** reenviar sempre o Plano completo e gerar novo sequencial.
- **Elementos afetados:** Plano, Demandas vigentes, Retificação e
  sequencialPca.
- **Evidências de teste:** `tests/Domain/Policies/PoliticaProcessamentoPlanoTest.php`,
  nos cenários de diferenças por pertencimento e invariantes do resultado.
- **Decisões e documentos relacionados:** DI-001, DI-002, DI-005 e
  `RN-PUB-006`. Métodos externos e ordem de operações são decisões de
  integração.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-PUB-003 — Decisão de excluir o PCA

- **Status normativo:** Vigente.
- **Enunciado normativo:** O sistema DEVE decidir `EXCLUIR_PCA` quando a
  fotografia vigente estiver vazia e houver PCA externo ativo. A exclusão
  integral DEVE usar a justificativa institucional fixa `Exclusão integral do
  PCA por ausência de demandas vigentes no SGI.`.
- **Origem ou autoridade:** DI-001, DI-003 e DI-005.
- **Contexto e justificativa:** um PCA externo não deve permanecer ativo sem
  Demandas homologadas vigentes. A justificativa descreve a transição
  institucional executada pelo backend e NÃO depende da causa histórica de uma
  Demanda específica. No modo consolidado, a unidade somente está vazia quando
  a união completa das UOs participantes não possui Demandas vigentes.
- **Exemplo:** cancelar a única Demanda ativa de um PCA publicado leva à
  exclusão integral.
- **Contraexemplo:** manter o PCA ativo vazio ou impedir o cancelamento.
- **Elementos afetados:** Plano vazio, justificativa institucional e
  publicação.
- **Evidências de teste:** evidência parcial em
  `tests/Domain/Policies/PoliticaProcessamentoPlanoTest.php`, cobrindo decisão
  com Plano vazio e rejeição da ausência de Planos. O envio do texto fixo
  pertence à orquestração da Application.
- **Decisões e documentos relacionados:** DI-001, DI-003, DI-005, DI-007,
  `RN-DEM-008` e `RN-PLA-005`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo; revisada em
  31/08/2026 para substituir a evidência do último cancelamento por
  justificativa institucional fixa.

### RN-PUB-004 — Decisão de não agir

- **Status normativo:** Vigente.
- **Enunciado normativo:** O sistema DEVE decidir `NENHUMA_ACAO` quando o PCA
  externo ativo já corresponder ao conjunto homologado vigente ou quando não
  houver Demandas vigentes nem PCA externo ativo.
- **Origem ou autoridade:** DI-002 e DI-005.
- **Contexto e justificativa:** não se executa procedimento externo quando não
  há transição de negócio necessária.
- **Exemplo:** PCA ativo convergente com a fotografia vigente não requer nova
  escrita.
- **Contraexemplo:** retificar um PCA sem qualquer mudança homologada.
- **Elementos afetados:** Plano, publicação e decisão.
- **Evidências de teste:** `tests/Domain/Policies/PoliticaProcessamentoPlanoTest.php`,
  nos cenários de PCA ativo convergente e de ambos os lados inativos.
- **Decisões e documentos relacionados:** DI-002, DI-005 e `RN-AUD-001`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-PUB-005 — Versão não determina a ação

- **Status normativo:** Vigente.
- **Enunciado normativo:** O número da versão interna, isoladamente, NÃO DEVE
  determinar se a ação é Publicação, Retificação, Exclusão ou Nenhuma Ação.
- **Origem ou autoridade:** DI-001 e DI-005.
- **Contexto e justificativa:** pode existir versão maior que 1 antes da
  primeira publicação, e o PCA externo pode continuar ativo entre versões
  internas.
- **Exemplo:** uma versão 2 sem publicação anterior decide Publicação quando
  sua fotografia possui Demandas.
- **Contraexemplo:** mapear versão 1 para Publicação e toda versão posterior
  para Retificação.
- **Elementos afetados:** Plano, versão e decisão de processamento.
- **Evidências de teste:** `testVersaoEConfirmacaoNaoDeterminamDecisao` em
  `tests/Domain/Policies/PoliticaProcessamentoPlanoTest.php`.
- **Decisões e documentos relacionados:** DI-001, DI-005 e
  `RN-PUB-001` a `RN-PUB-004`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-PUB-006 — Correspondência com o conjunto vigente

- **Status normativo:** Vigente.
- **Enunciado normativo:** O PCA publicado DEVE corresponder ao conjunto
  homologado vigente: novas Demandas homologadas DEVEM ser incorporadas e
  Demandas canceladas DEVEM deixar de integrá-lo.
- **Origem ou autoridade:** DI-001, DI-002 e DI-007.
- **Contexto e justificativa:** a publicação externa representa o estado de
  negócio vigente, sem transformar o PNCP em fonte das homologações e
  cancelamentos internos.
- **Exemplo:** D4 homologada passa a integrar o PCA; D2 cancelada deixa de
  integrá-lo.
- **Contraexemplo:** manter no PCA uma Demanda cancelada ou ignorar uma nova
  Demanda homologada.
- **Elementos afetados:** Plano, Demanda, Publicação e Retificação.
- **Evidências de teste:** `tests/Domain/Policies/PoliticaProcessamentoPlanoTest.php`,
  cobrindo diferenças por pertencimento, múltiplos Planos e unicidade global
  das Demandas.
- **Decisões e documentos relacionados:** DI-001, DI-002 e DI-007. Mapeamento
  de identificadores, algoritmo de comparação, limites de conteúdo e alcance
  da sincronização V1 pertencem às decisões de integração e não a este
  enunciado.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-PUB-007 — Nova publicação após exclusão

- **Status normativo:** Vigente.
- **Enunciado normativo:** Se uma nova Demanda for homologada depois da
  exclusão integral do PCA, o sistema DEVE realizar nova Publicação e DEVE
  adotar o novo `sequencialPca` recebido, preservando o anterior apenas como
  histórico.
- **Origem ou autoridade:** DI-001 e DI-005.
- **Contexto e justificativa:** um PCA excluído não volta a ser ativo por
  Retificação; a nova fotografia requer outro recurso externo.
- **Exemplo:** `EXCLUIDO` seguido de nova DFD-E resulta em `PUBLICAR` e novo
  sequencial.
- **Contraexemplo:** reutilizar como ativo o sequencial do PCA excluído.
- **Elementos afetados:** Plano, estado da publicação e sequencialPca.
- **Evidências de teste:** `testDevePublicarNovamenteDepoisDeExclusao` em
  `tests/Domain/Policies/PoliticaProcessamentoPlanoTest.php`.
- **Decisões e documentos relacionados:** DI-001, DI-005, `RN-PUB-001` e
  `RN-PUB-008`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-PUB-008 — Estados da publicação

- **Status normativo:** Vigente.
- **Enunciado normativo:** O estado da publicação DEVE ser
  `NAO_PUBLICADO`, `PUBLICADO` ou `EXCLUIDO`. `NAO_PUBLICADO` DEVE possuir
  sequencial nulo; `PUBLICADO` DEVE possuir sequencial positivo; `EXCLUIDO`
  DEVE preservar como histórico o último sequencial positivo sem tratá-lo como
  publicação ativa.
- **Origem ou autoridade:** DI-005 e DI-008.
- **Contexto e justificativa:** presença de sequencial e confirmação de versão
  não descrevem sozinhas a existência atual do PCA externo.
- **Exemplo:** após exclusão, o estado é `EXCLUIDO` e o sequencial antigo não
  indica PCA ativo.
- **Contraexemplo:** inferir `PUBLICADO` somente porque existe um sequencial
  armazenado.
- **Elementos afetados:** estado da publicação e sequencialPca.
- **Evidências de teste:** `tests/Domain/ValueObjects/EstadoOperacionalPublicacaoTest.php`
  e cenários de coerência em
  `tests/Domain/Policies/PoliticaProcessamentoPlanoTest.php`.
- **Decisões e documentos relacionados:** DI-005, DI-008 e `RN-PUB-007`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-PUB-009 — Publicação no modo individual

- **Status normativo:** Vigente.
- **Enunciado normativo:** No modo individual, cada Unidade Orçamentária DEVE
  possuir seu próprio PCA externo e DEVE produzir resultado independente. Uma
  falha específica de uma UO NÃO DEVE impedir o processamento das demais
  unidades.
- **Origem ou autoridade:** DI-003 e DI-004.
- **Contexto e justificativa:** cada UO governa uma publicação externa distinta
  nesse modo.
- **Exemplo:** falha específica da UO A não impede o sucesso da UO B.
- **Contraexemplo:** transformar toda a execução em falha por erro específico
  de uma única UO.
- **Elementos afetados:** UnidadeOrcamentaria, PCA externo e resultado.
- **Evidências de teste:** ausentes; testes atuais apenas processam múltiplos
  Planos sem o fluxo externo completo.
- **Decisões e documentos relacionados:** DI-003, DI-004 e `RN-PUB-011`.
  Transações e classificação de falhas sistêmicas são decisões de integração.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-PUB-010 — Publicação no modo consolidado

- **Status normativo:** Vigente.
- **Enunciado normativo:** No modo consolidado, todas as UOs do exercício DEVEM
  contribuir para um único PCA externo na UASG consolidadora. O conjunto
  publicado DEVE ser a união completa de suas Demandas vigentes. Uma UO vazia
  apenas deixa de contribuir; o PCA consolidado DEVE ser excluído somente
  quando a união inteira ficar vazia. O processamento DEVE produzir um único
  resultado conjunto.
- **Origem ou autoridade:** DI-003 e DI-004.
- **Contexto e justificativa:** o modo consolidado possui uma única unidade
  externa, embora os Planos internos permaneçam distintos por UO.
- **Exemplo:** se UO A ficar vazia e UO B mantiver Demandas, o PCA consolidado
  permanece com as Demandas de B.
- **Contraexemplo:** excluir o PCA consolidado porque uma única UO ficou vazia.
- **Elementos afetados:** Planos, UOs, UASG consolidadora e PCA externo.
- **Evidências de teste:** ausentes.
- **Decisões e documentos relacionados:** DI-003, DI-004, `RN-PUB-003` e
  `RN-PUB-011`. Agrupamento técnico e controle transacional pertencem às
  decisões de integração.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-PUB-011 — UASG por modo de publicação

- **Status normativo:** Vigente.
- **Enunciado normativo:** No modo individual, duas UOs do mesmo exercício NÃO
  DEVEM governar a mesma UASG. No modo consolidado, todas as UOs DEVEM publicar
  por meio da UASG consolidadora adotada para o exercício.
- **Origem ou autoridade:** DI-003 e DI-008.
- **Contexto e justificativa:** duas unidades internas independentes não podem
  governar o mesmo PCA externo, enquanto o consolidado possui uma unidade
  externa compartilhada.
- **Exemplo:** UO A e UO B usam UASGs distintas no modo individual.
- **Contraexemplo:** publicar dois PCA individuais para a mesma UASG e
  exercício.
- **Elementos afetados:** UnidadeOrcamentaria, Uasg e modo de publicação.
- **Evidências de teste:** ausentes.
- **Decisões e documentos relacionados:** DI-003 e DI-008. Fonte da
  configuração, congelamento anual e persistência da estratégia são decisões
  de integração.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

## 4.6 Auditoria

### RN-AUD-001 — Fatos que geram histórico

- **Status normativo:** Vigente.
- **Enunciado normativo:** Somente Publicação inicial, Retificação e Exclusão
  integral concluídas, observadas e confirmadas DEVEM gerar histórico de
  negócio. Tentativa, validação rejeitada, falha técnica, resultado incerto,
  conflito, contenção, rollback e `NENHUMA_ACAO` NÃO DEVEM gerar esse
  histórico.
- **Origem ou autoridade:** DI-006.
- **Contexto e justificativa:** o histórico representa fatos de negócio
  ocorridos, e não telemetria ou intenção.
- **Exemplo:** Retificação comprovadamente convergente gera histórico.
- **Contraexemplo:** registrar Publicação antes de comprovar o resultado
  externo.
- **Elementos afetados:** auditoria, publicação e confirmação.
- **Evidências de teste:** ausentes; testes atuais não distinguem confirmação,
  tentativa e falha.
- **Decisões e documentos relacionados:** DI-006 e `RN-PUB-004`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-AUD-002 — Procedimentos de publicação no PNCP

- **Status normativo:** Vigente.
- **Enunciado normativo:** Os fatos confirmados DEVEM ser classificados pelos
  procedimentos de negócio: código 5 para Publicação inicial do PCA, código 8
  para Retificação do PCA ativo e código 9 para Exclusão integral do PCA.
- **Origem ou autoridade:** DI-006.
- **Contexto e justificativa:** cada fato possui significado próprio no
  catálogo de procedimentos.
- **Exemplo:** uma Retificação confirmada usa o procedimento 8.
- **Contraexemplo:** registrar toda operação PNCP como procedimento 5.
- **Elementos afetados:** Procedimento e auditoria.
- **Evidências de teste:** `tests/Domain/Facts/HistoricoProcedimentoTest.php`,
  cobrindo as classificações e os códigos 5, 8 e 9.
- **Decisões e documentos relacionados:** DI-006 e
  `docs/vigentes/autoritativos/modelo_de_dominio.md`, cuja redação anterior é substituída por esta
  regra.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-AUD-003 — Unidade do histórico

- **Status normativo:** Vigente.
- **Enunciado normativo:** Cada operação externa confirmada DEVE gerar um
  histórico por PCA externo: um registro para o PCA da UO no modo individual e
  um único registro para todo o PCA no modo consolidado.
- **Origem ou autoridade:** DI-006.
- **Contexto e justificativa:** a unidade da auditoria acompanha o fato externo
  efetivamente ocorrido.
- **Exemplo:** uma Retificação consolidada de três UOs gera um único histórico.
- **Contraexemplo:** registrar três Retificações quando houve uma única
  operação consolidada.
- **Elementos afetados:** auditoria, PCA externo e modo de publicação.
- **Evidências de teste:** ausentes.
- **Decisões e documentos relacionados:** DI-006, `RN-PUB-009` e
  `RN-PUB-010`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-AUD-004 — Responsável e Lotação do histórico

- **Status normativo:** Vigente.
- **Enunciado normativo:** O responsável pelo fato auditado DEVE ser o usuário
  autenticado que iniciou a execução, representado por seu Representante e por
  sua Lotação no momento da ação.
- **Origem ou autoridade:** DI-006 e DI-010.
- **Contexto e justificativa:** a autoria pertence à pessoa que acionou a
  operação, não a usuários técnicos ou à UO publicada.
- **Exemplo:** uma publicação acionada por servidor lotado na Coordenadoria X
  registra esse Representante e essa Lotação.
- **Contraexemplo:** atribuir o histórico ao usuário do banco ou à UO do Plano.
- **Elementos afetados:** Representante, Lotacao e auditoria.
- **Evidências de teste:** evidência parcial em
  `tests/Domain/ValueObjects/RepresentanteTest.php`,
  `tests/Domain/ValueObjects/LotacaoTest.php` e
  `tests/Domain/Facts/HistoricoProcedimentoTest.php`; a resolução pelo
  usuário autenticado ainda não está coberta.
- **Decisões e documentos relacionados:** DI-006, DI-010, `RN-CAD-003` e
  `RN-CAD-004`.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

### RN-AUD-005 — Conteúdo da mensagem de auditoria

- **Status normativo:** Vigente.
- **Enunciado normativo:** A mensagem de auditoria DEVE informar exercício, UO
  ou indicação explícita de consolidado, UASG externa, `sequencialPca` e ação
  confirmada. Em Retificação, DEVE também informar as quantidades agregadas de
  itens incluídos e excluídos, sem listar itens ou UOs individualmente.
- **Origem ou autoridade:** DI-006.
- **Contexto e justificativa:** a mensagem precisa identificar o fato de forma
  segura e útil sem reproduzir payloads ou detalhes excessivos.
- **Exemplo:** mensagem de Retificação consolidada com exercício, UASG,
  sequencial e totais de inclusões e exclusões.
- **Contraexemplo:** copiar o texto do PNCP ou o payload completo para a
  auditoria.
- **Elementos afetados:** mensagem de auditoria e fato confirmado.
- **Evidências de teste:** evidência parcial da obrigatoriedade da mensagem em
  `tests/Domain/Facts/HistoricoProcedimentoTest.php`; o conteúdo vigente
  completo ainda não está coberto.
- **Decisões e documentos relacionados:** DI-006.
- **Histórico:** criada em 30/07/2026 na consolidação do catálogo.

## 5. Decisões deliberadamente mantidas fora do catálogo

Os temas abaixo pertencem às DI e ao futuro
`docs/vigentes/autoritativos/decisoes_de_integracao.md`:

- mapeamento `ID_DEMANDA` → `numeroItem`;
- reconciliação V1 exclusivamente por presença de `numeroItem`;
- ausência de comparação de conteúdo na V1;
- não propagação automática de alterações de `dataDesejada` na V1;
- congelamento da estratégia na primeira execução válida;
- persistência e lock utilizados nesse congelamento;
- adoção ou migração assistida de PCA externo sem vínculo local;
- endpoints, métodos HTTP, JSON, JWT, TLS, retry, paginação e timeouts;
- limite contratual de 1000 itens;
- ordem física de requisições e serialização de datas ou decimais;
- Oracle, ADOdb, OCI8, transações, locks, tabelas, colunas, joins e DDL;
- ownership de portas e camadas, Composition Root e Scriptcase;
- estados dos DTOs de resposta e detalhes de observabilidade.

As referências às DI registradas nas regras não incorporam esses mecanismos aos
enunciados normativos.

## 6. Matriz de conflitos resolvidos

Textos antigos superados permanecem rastreados nesta matriz e NÃO DEVEM ser
tratados como regras vigentes.

| Texto anterior | Resolução vigente |
| --- | --- |
| A identidade do Plano inclui versão ou `ID_PLANO` | `RN-PLA-001` |
| Plano não pode existir sem Demandas | `RN-PLA-005` |
| `FK_PLANO` define pertencimento exclusivo ao Plano vigente | `RN-PLA-004` e `RN-PLA-006` |
| Retificação reenvia o Plano completo e cria novo sequencial | `RN-PUB-002` |
| Versão e data de publicação determinam sozinhas a ação | `RN-PUB-001` a `RN-PUB-005` |
| Cada UO é sempre processada isoladamente | `RN-PUB-009` e `RN-PUB-010` |
| CNPJ deve conter somente dígitos | `RN-CAD-001` |
| Demanda homologada nunca pode mudar | `RN-DEM-003` e exceção `RN-DEM-004` |
| Somente a situação Homologada integra o PCA | `RN-DEM-009` |
| Cancelamento de Demanda homologada é apenas evolução futura | `RN-DEM-005`, `RN-DEM-006` e `RN-DEM-008` |
| Publicação e Retificação usam o mesmo procedimento | `RN-AUD-002` |
| Tentativas e falhas pertencem ao histórico de negócio | `RN-AUD-001` |
| Sequencial externo pertence à Demanda | `RN-PUB-008`; a persistência física permanece na DI-008 |
| Há precedência absoluta do SGI ou do PNCP | O SGI governa o PCA com vínculo local conhecido e o conjunto desejado; o PNCP fornece o estado observado. Diferenças por pertencimento e PCA sem vínculo são tratados conforme DI-002 e DI-007, sem baseline detalhado ou adoção automática |

## 7. Lacunas conhecidas

As lacunas abaixo não bloqueiam a criação deste catálogo, mas NÃO DEVEM ser
preenchidas por inferência no Domain, na Application ou na Infrastructure:

1. formato lexical completo da UASG além da obrigatoriedade de preenchimento;
2. etapas e autoridades intermediárias exatas do workflow próprio da DFD-E;
3. autoridade e momento exatos em que o cancelamento homologado se torna
   efetivo;
4. justificativa aplicável quando cancelamentos simultâneos esvaziarem uma
   unidade consolidada sem um evento inequivocamente último;
5. testes ainda ausentes para regras introduzidas pelas DI;
6. referências `@regra RN-...` ainda ausentes nos testes existentes.

## 8. Política de manutenção

1. Uma descoberta deve registrar sua origem e permanecer `Em validação` até ser
   confirmada pela autoridade de negócio.
2. O catálogo deve ser atualizado antes de modelo, fluxo, roadmap, código e
   testes afetados.
3. Documentos temáticos devem referenciar o ID, sem reescrever a regra de modo
   concorrente.
4. Mudanças semânticas devem criar novo ID e marcar a regra anterior como
   `Substituída`.
5. Evidências de teste devem usar `@regra RN-...` quando forem criadas ou
   revisadas.
6. Não existe relação obrigatória de um para um entre regras e testes.
7. Conflitos documentais devem ser resolvidos antes da implementação.
8. A validação automatizada de IDs e referências permanece adiada até o formato
   amadurecer com uso real.

## 9. Referências de autoridade

### Checkpoints de planejamento

- `.agents/planejamento/checkpoints/handoff_planejamento_roadmap_v2.md`;
- `.agents/planejamento/checkpoints/handoff_planejamento_roadmap_v2_complementar_di006_di008.md`;
- `.agents/planejamento/checkpoints/handoff_planejamento_roadmap_v2_continuacao_03_di008_di010.md`.

Os checkpoints são incrementais. O checkpoint posterior prevalece somente
sobre o estado ou a pendência que ele efetivamente revisa.

### Documentos temáticos atuais

- `docs/vigentes/apoio/arquitetura.md`;
- `docs/vigentes/autoritativos/modelo_de_dominio.md`;
- `docs/vigentes/apoio/contexto_funcional_do_sistema_pca.md`;
- `docs/vigentes/apoio/glossario.md`;
- `docs/vigentes/apoio/diagramas/DER_PUBLICACAO_PCA.png`;
- `docs/vigentes/apoio/diagramas/fluxograma_PCA.png`.

- `docs/vigentes/autoritativos/fluxo_de_implementacao.md`;
- [Roadmap de implementação](roadmap.md).

### Fontes independentes de conferência

As sínteses `.agents/planejamento/sinteses/sintese_DI-*.md` foram usadas para
conferir integridade, sem substituir os handoffs.
