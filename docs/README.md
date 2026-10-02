# Arquitetura da documentação

**Classificação documental:** Autoritativo

**Autoridade/Escopo:** organização, classificação, navegação e caminhos
canônicos da documentação mantida em `docs/`

## 1. Finalidade e limites

O [`README.md` da raiz](../README.md) é a entrada cognitiva do repositório. Ele
explica o que o projeto faz, por que ele é útil, como começar, onde obter ajuda,
seu estado geral e sua finalidade didática.

Este índice assume essa visão geral e governa especificamente a documentação
canônica mantida em `docs/`: onde cada assunto deve ser registrado, qual fonte
responde por ele e como localizar o conhecimento vigente.

Instruções permanentes para agentes pertencem a [`AGENTS.md`](../AGENTS.md), e
o planejamento e a continuidade do trabalho pertencem a
[`.agents/`](../.agents/README.md). READMEs de `database/`, `integracao/`,
`tests/` e `tools/` orientam seus próprios contextos e não integram o registro
deste índice.

## 2. Como começar a leitura

A entrada recomendada para uma pessoa que ainda não conhece o projeto é:

1. [README da raiz](../README.md), para compreender o produto e sua finalidade;
2. este índice, para entender onde o conhecimento está e quem responde por ele;
3. [glossário](vigentes/apoio/glossario.md), como referência para a linguagem
   do projeto;
4. uma das trilhas abaixo, conforme a necessidade concreta.

O glossário não precisa ser lido integralmente antes dos demais documentos.
Conheça suas categorias e distinções principais e retorne a ele quando um termo
precisar de definição mais precisa.

### 2.1 Trilhas por objetivo

| Objetivo | Trilha sugerida |
| --- | --- |
| Compreender o negócio | [Contexto funcional](vigentes/apoio/contexto_funcional_do_sistema_pca.md) → [Regras de negócio](vigentes/autoritativos/regras_de_negocio.md) → [Modelo de domínio](vigentes/autoritativos/modelo_de_dominio.md) |
| Compreender arquitetura e integração | [Arquitetura](vigentes/apoio/arquitetura.md) → [Decisões de integração](vigentes/autoritativos/decisoes_de_integracao.md) → [Fluxo de implementação](vigentes/autoritativos/fluxo_de_implementacao.md) |
| Navegar no código | [Guia de leitura do código](vigentes/apoio/guia_de_leitura_do_codigo.md) → [Catálogo de diretórios](vigentes/apoio/catalogo_de_diretorios.md) → [Catálogo de arquivos](vigentes/apoio/catalogo_de_arquivos.md) |
| Operar ou prestar suporte | [Situação operacional](vigentes/operacional/situacao_operacional.md) → [Guia de diagnóstico](vigentes/operacional/guia_operacional_de_diagnostico_da_publicacao_pca.md) |
| Configurar, implantar ou reverter | [Situação operacional](vigentes/operacional/situacao_operacional.md) → [Configuração de produção](vigentes/operacional/guia_de_configuracao_de_producao.md) → [Implantação e rollback](vigentes/operacional/guia_de_implantacao_e_rollback.md) |
| Preparar um novo projeto | [Guia de preparação inicial de projetos](vigentes/apoio/guia_de_preparacao_inicial_de_projetos.md) |
| Acompanhar a construção | [Roadmap](vigentes/autoritativos/roadmap.md) → [Estado do trabalho dos agentes](../.agents/planejamento/estado_atual.md) |
| Atuar como agente | [`AGENTS.md`](../AGENTS.md) → [Governança dos agentes](../.agents/README.md) → [Estado do trabalho](../.agents/planejamento/estado_atual.md) |

## 3. Papéis documentais e autoridade

| Classificação | Papel |
| --- | --- |
| **Autoritativo** | Fonte oficial da verdade para o assunto declarado em seu escopo |
| **Operacional** | Situação corrente ou procedimento ainda aplicável à operação e ao suporte, sem redefinir fontes prescritivas |
| **Apoio** | Explicação, mapa ou referência que facilita a compreensão sem criar norma concorrente |

A situação no ciclo de vida é expressa principalmente pela localização:

- `docs/vigentes/` contém conhecimento atual;
- `docs/historico/` preserva material encerrado ou substituído, sem autoridade
  vigente;
- `docs/investigacoes/` ignorada pelo Git, essa é uma área local temporária que armazena, por exemplo: justificativas, relatórios, ensaios, fotografias de bases de dados, etc

Versão documental, conclusão de uma investigação e observações de sprint não
são classificações nem devem ser incorporadas ao nome do status.

### 3.1 Autoridade por assunto

| Assunto | Fonte responsável |
| --- | --- |
| Regras e comportamento normativo do negócio | [Regras de negócio](vigentes/autoritativos/regras_de_negocio.md) |
| Conceitos, identidades, relações e invariantes | [Modelo de domínio](vigentes/autoritativos/modelo_de_dominio.md) |
| Contratos externos, persistência e decisões de integração | [Decisões de integração](vigentes/autoritativos/decisoes_de_integracao.md) |
| Envelope e proteção dos eventos técnicos | [Especificação de logs](vigentes/autoritativos/especificacao_de_logs_estruturados.md) |
| Colaboração dinâmica entre componentes | [Fluxo de implementação](vigentes/autoritativos/fluxo_de_implementacao.md) |
| Ordem de construção e critérios de avanço | [Roadmap](vigentes/autoritativos/roadmap.md) |
| Organização e classificação da documentação em `docs/` | Este índice |
| Condição do produto instalado e conduta segura | [Situação operacional](vigentes/operacional/situacao_operacional.md) |
| Continuidade da implementação e próxima atividade autorizada | [Estado do trabalho dos agentes](../.agents/planejamento/estado_atual.md) |

Não existe uma hierarquia genérica entre fontes autoritativas de assuntos
diferentes. Prevalece a fonte responsável pelo assunto discutido. Se duas
fontes autoritativas forem realmente incompatíveis, então é preciso expôr a divergência para solicitar decisão. Portanto, não combine premissas nem
escolha uma silenciosamente.

## 4. Estrutura canônica

```text
docs/
├── README.md                 # índice e governança da documentação
├── vigentes/                 # conhecimento atual do sistema
│   ├── autoritativos/        # regras, modelo, decisões, fluxo e roadmap
│   ├── apoio/                # arquitetura, explicações, glossário e catálogos
│   │   └── diagramas/        # representações visuais vigentes de apoio
│   └── operacional/          # situação do produto e procedimentos aplicáveis
├── historico/                # material substituído ou encerrado, ainda útil
│   ├── didatico/             # explicações e exercícios de etapas anteriores
│   ├── investigacoes/        # investigações encerradas com valor permanente
│   └── operacional/          # gates, migrações e procedimentos concluídos
└── investigacoes/            # trabalho local temporário, ignorado pelo Git
```

## 5. Registro da documentação vigente

### 5.1 Autoritativos

| Documento | Autoridade |
| --- | --- |
| [Este índice](README.md) | Governança da documentação mantida em `docs/` |
| [Regras de negócio](vigentes/autoritativos/regras_de_negocio.md) | Redação normativa das regras de negócio |
| [Modelo de domínio](vigentes/autoritativos/modelo_de_dominio.md) | Conceitos, identidades, relações e invariantes |
| [Decisões de integração](vigentes/autoritativos/decisoes_de_integracao.md) | Decisões `DI-*`, contratos externos e gates associados |
| [Especificação de logs estruturados](vigentes/autoritativos/especificacao_de_logs_estruturados.md) | Envelope institucional, campos e proteção de dados dos eventos |
| [Fluxo de implementação](vigentes/autoritativos/fluxo_de_implementacao.md) | Colaboração dinâmica entre camadas, componentes e Ports |
| [Roadmap](vigentes/autoritativos/roadmap.md) | Ordem de construção e critérios de avanço |

### 5.2 Apoio

| Documento | Papel |
| --- | --- |
| [Arquitetura](vigentes/apoio/arquitetura.md) | Limites, responsabilidades e direção das dependências |
| [Glossário](vigentes/apoio/glossario.md) | Índice semântico da linguagem do projeto |
| [Contexto funcional](vigentes/apoio/contexto_funcional_do_sistema_pca.md) | Contexto do sistema PCA, usuários e etapas |
| [Guia de preparação inicial de projetos](vigentes/apoio/guia_de_preparacao_inicial_de_projetos.md) | Roteiro adaptável da estação de trabalho até a primeira entrega local observável |
| [Guia de leitura do código](vigentes/apoio/guia_de_leitura_do_codigo.md) | Roteiro de estudo com explicação do fluxo da aplicação pelas classes reais, transações. |
| [Catálogo de comandos Composer](vigentes/apoio/catalogo_de_comandos_composer.md) | Finalidade, dependências e efeitos dos comandos ativos |
| [Catálogo de diretórios](vigentes/apoio/catalogo_de_diretorios.md) | Finalidade das pastas e orientação para organização de novos artefatos |
| [Catálogo de arquivos](vigentes/apoio/catalogo_de_arquivos.md) | Função dos arquivos ativos de código, teste e documentação |
| [DER da publicação](vigentes/apoio/diagramas/DER_PUBLICACAO_PCA.png) | Representação visual das estruturas de dados |
| [Fluxograma do PCA](vigentes/apoio/diagramas/fluxograma_PCA.png) | Representação visual do processo de negócio |
| [Visão executiva](vigentes/apoio/diagramas/visao_executiva_projeto_pca_pncp.md) | Diagramas para explicar o problema, a solução, a convergência e os principais controles do sistema |

### 5.3 Operacionais

| Documento | Papel |
| --- | --- |
| [Situação operacional](vigentes/operacional/situacao_operacional.md) | Condição do produto instalado, restrições correntes e ações seguras |
| [Guia operacional de diagnóstico](vigentes/operacional/guia_operacional_de_diagnostico_da_publicacao_pca.md) | Interpretação das respostas do sistema, evidências e conduta de suporte |
| [Guia de configuração de produção](vigentes/operacional/guia_de_configuracao_de_producao.md) | Provisionamento dos arquivos de configuração: registro, perfil, CA bundle e permissões |
| [Guia de implantação e rollback](vigentes/operacional/guia_de_implantacao_e_rollback.md) | Construção, instalação, atualização e reversão da release |

### 5.4 Históricos

A documentação substituída ou encerrada é preservada no
[índice histórico](historico/README.md) para auditoria e aprendizado. Ela não
possui autoridade vigente e não integra as trilhas normais de leitura.

## 6. Investigações, referências e preservação

### 6.1 Ciclo de vida de uma investigação

- `docs/investigacoes/` é uma área de trabalho local e temporária. Contém
  investigações abertas, ensaios, evidências brutas e hipóteses. Seu conteúdo
  é ignorado pelo Git e não possui autoridade documental.
- `docs/historico/investigacoes/` é o acervo versionado de investigações
  encerradas que ainda possuem valor para auditoria, rastreabilidade ou
  aprendizado. Esses documentos não possuem autoridade vigente, mas são
  preservados no repositório.

O fluxo esperado é:

```text
investigação aberta
docs/investigacoes/
        │
        ├── conclusão estável
        │       └── incorporar à fonte vigente responsável pelo assunto
        │
        └── encerramento da investigação
                ├── possui valor permanente
                │       └── preservar em docs/historico/investigacoes/
                │
                └── possui apenas valor temporário
                        └── manter local enquanto útil e depois descartar
```

### 6.2 Referências, preservação e autoridade

- Documentos históricos não prevalecem sobre fontes vigentes. Seu corpo pode
  preservar nomes e premissas da época; cabeçalhos e avisos de navegação podem
  ser atualizados para evitar uso indevido.
- Documentos vigentes devem usar caminhos canônicos. Movimentações devem
  atualizar este índice, os catálogos e as referências vigentes na mesma
  alteração.
- Conteúdo normativo deve referenciar IDs `RN-*`; decisões de integração e
  operação externa devem referenciar IDs `DI-*` quando aplicável.
- Código existente, investigações, históricos, prompts e planos antigos não se
  tornam fontes normativas por descreverem uma implementação.

## 7. Evolução do glossário e distribuição das explicações

O glossário deve evoluir junto com a documentação vigente. Durante a criação
ou revisão de regras, modelo, decisões de integração, fluxo, roadmap e guias,
avalie a inclusão de conceitos que sejam:

- específicos do projeto;
- reutilizados por múltiplos documentos;
- relevantes para compreender domínio, arquitetura, integração ou operação;
- candidatos a gerar dúvidas recorrentes.

Essa avaliação não implica inclusão automática. O glossário não deve funcionar
como catálogo de código, dicionário de termos técnicos genéricos, inventário de
tabelas, campos, APIs ou classes, nem duplicação de fontes autoritativas.

Cada verbete deve apontar para suas fontes vigentes e não pode criar regras,
decisões, valores padrão ou significados ainda não consolidados. Quando faltar
uma definição necessária, corrija primeiro a fonte responsável pelo assunto.

Um documento autoritativo deve permanecer compreensível sem exigir consulta
obrigatória ao glossário.

Ao introduzir um termo:

1. explique localmente aquilo que altera comportamento, responsabilidade,
   ordem de execução ou validade da operação;
2. use o glossário para a definição semântica estável de conceitos transversais;
3. apenas referencie termos já conhecidos quando sua definição não for
   necessária para interpretar o comportamento;
4. mantenha campos, códigos, métodos HTTP, classes e parâmetros como detalhes
   técnicos, salvo quando representarem um conceito reutilizável;
5. revise o glossário na mesma alteração quando uma fonte modificar um conceito
   estável já registrado.

## 8. Mapa de caminhos antigos

Como alguns caminhos foram atualizados, eles agora servem apenas para migração de referências e não devem ser usados em novos documentos.

| Caminho antigo ou concorrente | Caminho canônico |
| --- | --- |
| `docs/1_arquitetura.md`, `docs/arquitetura.md`, `arquitetura.md` | `docs/vigentes/apoio/arquitetura.md` |
| `docs/2_modelo_dominio.md`, `docs/modelo_dominio.md`, `docs/modelo_de_dominio_v2.md` | `docs/vigentes/autoritativos/modelo_de_dominio.md` |
| `docs/3_fluxo_implementacao.md`, `docs/fluxo_implementacao.md` | `docs/vigentes/autoritativos/fluxo_de_implementacao.md` |
| `docs/4_roadmap.md`, `docs/roadmap.md`, `docs/roadmap_v2.md` | `docs/vigentes/autoritativos/roadmap.md` |
| `docs/4_roadmap_v1.md` | `docs/historico/roadmap_v1_legado.md` |
| `docs/5_contexto_planejamento.md`, `docs/vigentes/apoio/contexto_do_planejamento_do_projeto_backend.md` | `docs/historico/contexto_do_planejamento_do_projeto_backend.md` |
| `docs/6_explicacao_sistema_PCA.md`, `docs/vigentes/apoio/contexto_explicando_o_sistema_PCA.md` | `docs/vigentes/apoio/contexto_funcional_do_sistema_pca.md` |
| `docs/7_decisoes_integracao.md` | `docs/vigentes/autoritativos/decisoes_de_integracao.md` |
| `docs/regras_negocio.md` | `docs/vigentes/autoritativos/regras_de_negocio.md` |
| `docs/glossario.md`, `docs/glossario_tecnico.md` | `docs/vigentes/apoio/glossario.md` |
| `docs/vigentes/operacional/estado_atual.md` | `.agents/planejamento/estado_atual.md` |
| `docs/vigentes/operacional/catalogo_codigos_publicos.md` | `docs/vigentes/operacional/guia_operacional_de_diagnostico_da_publicacao_pca.md` |
| `docs/vigentes/apoio/guia_de_configuracao_de_producao.md` | `docs/vigentes/operacional/guia_de_configuracao_de_producao.md` |
| `docs/vigentes/apoio/guia_de_implantacao_e_rollback.md` | `docs/vigentes/operacional/guia_de_implantacao_e_rollback.md` |
| `docs/vigentes/apoio/guia_de_execucao_das_probes.md` | `docs/historico/operacional/guia_de_execucao_das_probes.md` |
| `docs/vigentes/apoio/guia_de_migracao_assistida_e_recuperacao.md` | `docs/historico/operacional/guia_de_migracao_assistida_e_recuperacao.md` |
| `docs/investigacoes/guia_de_compreensao_do_backend.md` | `docs/historico/didatico/guia_de_compreensao_do_backend.md` |
| `docs/img/DER_PUBLICACAO_PCA.png` | `docs/vigentes/apoio/diagramas/DER_PUBLICACAO_PCA.png` |
| `docs/img/fluxograma_PCA.png` | `docs/vigentes/apoio/diagramas/fluxograma_PCA.png` |
| `docs/diagramas/DER_PUBLICACAO_PCA.png` | `docs/vigentes/apoio/diagramas/DER_PUBLICACAO_PCA.png` |
| `docs/diagramas/fluxograma_PCA.png` | `docs/vigentes/apoio/diagramas/fluxograma_PCA.png` |
| `docs/diagramas/visao_executiva_projeto_pca_pncp.md` | `docs/vigentes/apoio/diagramas/visao_executiva_projeto_pca_pncp.md` |
