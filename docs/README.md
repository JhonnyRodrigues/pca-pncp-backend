# Arquitetura da Documentação

**Classificação documental:** Autoritativo  
**Status:** Vigente  
**Autoridade/Escopo:** governança, classificação, navegação e caminhos canônicos da documentação do projeto

## 1. Finalidade

O [`README.md` da raiz](../README.md) é a entrada cognitiva do repositório e
explica o que é o projeto, seu estado geral e sua finalidade didática. Este
índice assume essa visão geral e governa especificamente a arquitetura da
documentação.

Esta página define como a documentação do backend PCA/PNCP é organizada e
qual autoridade cada artefato possui. A classificação documental e o status
são dimensões diferentes:

- **Classificação** informa o papel permanente do documento.
- **Status** informa sua situação atual no ciclo de vida.

Em caso de dúvida sobre classificação documental, caminhos, status ou vigência, este índice prevalece como autoridade de governança documental. Ele não substitui as fontes autoritativas de negócio, domínio ou integração.

## 2. Classificações

| Classificação | Papel |
|---|---|
| **Autoritativo** | Fonte oficial da verdade para o escopo declarado |
| **Apoio** | Explica e contextualiza, sem criar norma concorrente |
| **Investigação** | Preserva pesquisas, experimentações e evidências sem autoridade normativa |
| **Histórico** | Preserva versões substituídas para auditoria e aprendizado |

Os status usados são `Vigente`, `Transitório`, `Histórico` e
`Planejado (ainda não criado)`. Status não criam diretórios.

## 3. Estrutura canônica

```text
docs/
├── README.md
├── diagramas/
├── investigacoes/
├── vigentes/
│   ├── autoritativos/
│   └── apoio/
└── historico/
```

Arquivos planejados não são criados como placeholders. Seu caminho reservado
aparece neste índice sem link até que o documento exista.

## 4. Ordem recomendada de leitura

1. [Arquitetura](vigentes/apoio/arquitetura.md)
2. [Regras de negócio](vigentes/autoritativos/regras_de_negocio.md)
3. [Modelo de domínio](vigentes/autoritativos/modelo_de_dominio.md)
4. [Decisões de integração](vigentes/autoritativos/decisoes_de_integracao.md)
5. [Glossário integrado](vigentes/apoio/glossario.md)
6. [Contexto do sistema PCA](vigentes/apoio/contexto_explicando_o_sistema_PCA.md)
7. `docs/vigentes/autoritativos/fluxo_de_implementacao.md` — Planejado (ainda não criado)
8. `docs/vigentes/autoritativos/roadmap.md` — Planejado (ainda não criado)

O
[contexto do planejamento](vigentes/apoio/contexto_do_planejamento_do_projeto_backend.md)
é transitório e deve ser consultado apenas quando relevante à sua consolidação
documental.

### 4.1 Princípio de autoridade

Quando dois documentos parecerem contraditórios, prevalece:

1. Regras de Negócio;
2. Modelo de Domínio;
3. Decisões de Integração;
4. Fluxo de Implementação;
5. Roadmap;
6. Documentos de Apoio;
7. Investigações;
8. Históricos.

## 5. Registro documental

### 5.1 Autoritativos

| Documento | Status | Autoridade |
|---|---|---|
| [Este índice](README.md) | Vigente | Governança documental |
| [Regras de negócio](vigentes/autoritativos/regras_de_negocio.md) | Vigente | Redação normativa das regras de negócio |
| [Modelo de domínio](vigentes/autoritativos/modelo_de_dominio.md) | Vigente — V2 | Conceitos, identidades, relações e invariantes do modelo-alvo |
| [Decisões de integração](vigentes/autoritativos/decisoes_de_integracao.md) | Vigente | DI-001 a DI-010 e seus gates |
| `docs/vigentes/autoritativos/fluxo_de_implementacao.md` | Planejado (ainda não criado) | Colaboração entre camadas e portas |
| `docs/vigentes/autoritativos/roadmap.md` | Planejado (ainda não criado) | Ordem futura de implementação |

### 5.2 Apoio

| Documento | Status | Papel |
|---|---|---|
| [Arquitetura](vigentes/apoio/arquitetura.md) | Vigente | Limites e direção de dependências |
| [Glossário integrado](vigentes/apoio/glossario.md) | Vigente | Índice semântico da linguagem do projeto |
| [Contexto do sistema PCA](vigentes/apoio/contexto_explicando_o_sistema_PCA.md) | Vigente | Usuários, etapas e contexto funcional |
| [Contexto do planejamento](vigentes/apoio/contexto_do_planejamento_do_projeto_backend.md) | Transitório | Exportação a consolidar no passo 5 da seção 17 |
| [DER da publicação](diagramas/DER_PUBLICACAO_PCA.png) | Vigente como apoio visual | Diagrama visual |
| [Fluxograma do PCA](diagramas/fluxograma_PCA.png) | Vigente como apoio visual | Diagrama visual |

### 5.3 Investigações

Estes arquivos são evidências preservadas e não especificações:

| Documento | Status |
|---|---|
| [Código-fonte investigado](investigacoes/codigo-fonte_PCA_PUBLICAR_B.php) | Investigação |
| [Cópia de entry point HTTP](investigacoes/copia_entryPointHTTP.txt) | Investigação |
| [Estrutura observada de ADORecordSet](investigacoes/object_ADORecordSet_array.txt) | Investigação |

### 5.4 Históricos

Os documentos abaixo têm status `Histórico`, não possuem autoridade atual e
devem ser consultados apenas para auditoria, evolução e estudo:

- [Índice histórico](historico/README.md);
- [Modelo de domínio V1 legado](historico/modelo_de_dominio_v1_legado.md);
- [Fluxo de implementação V1 legado](historico/fluxo_de_implementacao_v1_legado.md);
- [Roadmap V1 legado](historico/roadmap_v1_legado.md).

## 6. Mapa de nomes antigos

| Nome antigo ou concorrente | Caminho canônico |
|---|---|
| `docs/1_arquitetura.md`, `docs/arquitetura.md`, `arquitetura.md` | `docs/vigentes/apoio/arquitetura.md` |
| `docs/2_modelo_dominio.md`, `docs/modelo_dominio.md`, `docs/modelo_de_dominio_v2.md` | `docs/vigentes/autoritativos/modelo_de_dominio.md` |
| `docs/3_fluxo_implementacao.md`, `docs/fluxo_implementacao.md` | `docs/vigentes/autoritativos/fluxo_de_implementacao.md` — planejado |
| `docs/4_roadmap.md`, `docs/roadmap.md`, `docs/roadmap_v2.md` | `docs/vigentes/autoritativos/roadmap.md` — planejado |
| `docs/4_roadmap_v1.md` | `docs/historico/roadmap_v1_legado.md` |
| `docs/5_contexto_planejamento.md` | `docs/vigentes/apoio/contexto_do_planejamento_do_projeto_backend.md` |
| `docs/6_explicacao_sistema_PCA.md` | `docs/vigentes/apoio/contexto_explicando_o_sistema_PCA.md` |
| `docs/7_decisoes_integracao.md` | `docs/vigentes/autoritativos/decisoes_de_integracao.md` |
| `docs/regras_negocio.md` | `docs/vigentes/autoritativos/regras_de_negocio.md` |
| `docs/glossario.md`, `docs/glossario_tecnico.md`, `docs/vigentes/apoio/glossario_tecnico.md` | `docs/vigentes/apoio/glossario.md` |
| `docs/img/DER_PUBLICACAO_PCA.png` | `docs/diagramas/DER_PUBLICACAO_PCA.png` |
| `docs/img/fluxograma_PCA.png` | `docs/diagramas/fluxograma_PCA.png` |

Os aliases desta tabela servem para migração e não devem ser usados em novas
referências.

## 7. Política de referências e preservação

- Documentos vigentes devem usar os caminhos canônicos.
- Documentos planejados devem ser citados como texto, com seu status, sem link.
- Conteúdo normativo deve referenciar IDs `RN-*`; escolhas externas ou
  operacionais devem referenciar IDs `DI-*`.
- Documentos de apoio, investigações e históricos não prevalecem sobre fontes
  autoritativas.
- O corpo de um documento histórico permanece como evidência de sua época,
  inclusive quando contém nomes antigos. Apenas seu cabeçalho de navegação
  pode ser atualizado.
- Movimentações futuras devem atualizar este índice e todas as referências
  vigentes na mesma alteração.
- Agentes e colaboradores automatizados devem utilizar preferencialmente os documentos classificados como Autoritativos e considerar documentos de Apoio apenas para contextualização.
