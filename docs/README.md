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
| --- | --- |
| **Autoritativo** | Fonte oficial da verdade para o escopo declarado |
| **Operacional** | Estado corrente do trabalho, sem autoridade para alterar fontes prescritivas |
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
│   ├── apoio/
│   └── operacional/
└── historico/
```

Arquivos planejados não são criados como placeholders. Seu caminho reservado
aparece neste índice sem link até que o documento exista.

## 4. Ordem recomendada de leitura

1. [Arquitetura](vigentes/apoio/arquitetura.md)
2. [Regras de negócio](vigentes/autoritativos/regras_de_negocio.md)
3. [Modelo de domínio](vigentes/autoritativos/modelo_de_dominio.md)
4. [Decisões de integração](vigentes/autoritativos/decisoes_de_integracao.md)
5. [Especificação de logs estruturados](vigentes/autoritativos/especificacao_de_logs_estruturados.md)
6. [Glossário integrado](vigentes/apoio/glossario.md)
7. [Contexto funcional do sistema PCA](vigentes/apoio/contexto_funcional_do_sistema_pca.md)
8. [Fluxo de implementação](vigentes/autoritativos/fluxo_de_implementacao.md)
9. [Roadmap de implementação](vigentes/autoritativos/roadmap.md)
10. [Estado operacional do projeto](vigentes/operacional/estado_atual.md)
11. [Guia de leitura do código](vigentes/apoio/guia_de_leitura_do_codigo.md)
12. [Guia de configuração de produção](vigentes/apoio/guia_de_configuracao_de_producao.md)
13. [Guia de implantação e rollback](vigentes/apoio/guia_de_implantacao_e_rollback.md)
14. [Guia de execução das probes](vigentes/apoio/guia_de_execucao_das_probes.md)
15. [Catálogo de comandos Composer](vigentes/apoio/catalogo_de_comandos_composer.md)
16. [Catálogo de diretórios](vigentes/apoio/catalogo_de_diretorios.md)
17. [Catálogo navegável de arquivos](vigentes/apoio/catalogo_de_arquivos.md)

### 4.1 Princípio de autoridade

Quando dois documentos parecerem contraditórios, prevalece:

1. Regras de Negócio;
2. Modelo de Domínio;
3. Decisões de Integração;
4. Especificações autoritativas vinculadas às Decisões de Integração;
5. Fluxo de Implementação;
6. Roadmap;
7. Documentos Operacionais, somente quanto ao estado corrente do trabalho;
8. Documentos de Apoio;
9. Investigações;
10. Históricos.

## 5. Registro documental

### 5.1 Autoritativos

| Documento | Status | Autoridade |
| --- | --- | --- |
| [Este índice](README.md) | Vigente | Governança documental |
| [Regras de negócio](vigentes/autoritativos/regras_de_negocio.md) | Vigente | Redação normativa das regras de negócio |
| [Modelo de domínio](vigentes/autoritativos/modelo_de_dominio.md) | Vigente — V2 | Conceitos, identidades, relações e invariantes do modelo-alvo |
| [Decisões de integração](vigentes/autoritativos/decisoes_de_integracao.md) | Vigente | DI-001 a DI-010 e seus gates |
| [Especificação de logs estruturados](vigentes/autoritativos/especificacao_de_logs_estruturados.md) | Vigente | Envelope institucional, dicionário de campos e proteção de dados dos eventos técnicos |
| [Fluxo de implementação](vigentes/autoritativos/fluxo_de_implementacao.md) | Vigente | Colaboração entre camadas e portas |
| [Roadmap de implementação](vigentes/autoritativos/roadmap.md) | Vigente — V2 | Ordem de implementação |

### 5.2 Apoio

| Documento | Status | Papel |
| --- | --- | --- |
| [Arquitetura](vigentes/apoio/arquitetura.md) | Vigente | Limites e direção de dependências |
| [Glossário integrado](vigentes/apoio/glossario.md) | Vigente | Índice semântico da linguagem do projeto |
| [Contexto funcional do sistema PCA](vigentes/apoio/contexto_funcional_do_sistema_pca.md) | Vigente | Usuários, etapas e contexto funcional |
| [Guia de leitura do código](vigentes/apoio/guia_de_leitura_do_codigo.md) | Vigente | Mapas, fluxo, transações e roteiro de estudo |
| [Guia de configuração de produção](vigentes/apoio/guia_de_configuracao_de_producao.md) | Vigente | Preparação e provisionamento seguro do registro, perfil e CA bundle de produção |
| [Guia de implantação e rollback](vigentes/apoio/guia_de_implantacao_e_rollback.md) | Vigente | Construção, transferência SFTP, instalação, atualização e rollback da release |
| [Guia de execução das probes](vigentes/apoio/guia_de_execucao_das_probes.md) | Vigente | Preparação e execução segura das probes e gates 5.1, 5.9, 8.2, 8.3 e corretivo 8.4.7-C |
| [Catálogo de comandos Composer](vigentes/apoio/catalogo_de_comandos_composer.md) | Vigente | Finalidade, classificação, dependências e efeitos externos dos scripts ativos |
| [Catálogo de diretórios](vigentes/apoio/catalogo_de_diretorios.md) | Vigente | Finalidade das pastas e orientação para localizar ou criar artefatos |
| [Catálogo navegável de arquivos](vigentes/apoio/catalogo_de_arquivos.md) | Vigente | Função dos arquivos ativos de código e teste |
| [DER da publicação](diagramas/DER_PUBLICACAO_PCA.png) | Vigente como apoio visual | Diagrama visual |
| [Fluxograma do PCA](diagramas/fluxograma_PCA.png) | Vigente como apoio visual | Diagrama visual |
| [Visão executiva do projeto PCA/PNCP](diagramas/visao_executiva_projeto_pca_pncp.md) | Vigente como apoio visual | Complexidade, progresso e gates até produção |

### 5.3 Operacional

| Documento | Status | Autoridade limitada |
| --- | --- | --- |
| [Estado operacional do projeto](vigentes/operacional/estado_atual.md) | Vigente | Progresso, última sprint concluída, próxima sprint na ordem, gates, pendências e restrições; não altera RN, DI, modelo, fluxo, arquitetura ou Roadmap |

### 5.4 Investigações

Estes arquivos são evidências preservadas e não especificações:

| Documento | Status |
| --- | --- |
| [Código-fonte investigado](investigacoes/codigo-fonte_PCA_PUBLICAR_B.php) | Investigação |
| [Cópia de entry point HTTP](investigacoes/copia_entryPointHTTP.txt) | Investigação |
| [Estrutura observada de ADORecordSet](investigacoes/object_ADORecordSet_array.txt) | Investigação |
| [Auditoria da Fase 4 — Application](investigacoes/auditoria_fase_4_application_2026-08-10.md) | Investigação concluída |
| [Guia de compreensão do backend](investigacoes/guia_de_compreensao_do_backend.md) | Investigação |
| [Evidências da Sprint 5.1 — gates Oracle e PNCP](investigacoes/evidencias_sprint_5_1_gates_oracle_pncp_2026-08-17.md) | Investigação concluída |
| [Evidências da Sprint 5.9 — contratos dos adapters](investigacoes/evidencias_parciais_sprint_5_9_contratos_adapters_2026-08-26.md) | Investigação concluída — gates Oracle e PNCP aprovados |
| [Investigação para encerramento da Fase 5 — Sprint 5.10](investigacoes/investigacao_encerramento_fase_5_sprint_5_10_2026-08-28.md) | Encerrada sem execução Oracle real — superada pela revisão normativa de `RN-PUB-003` |

### 5.5 Históricos

Os documentos abaixo têm status `Histórico`, não possuem autoridade atual e
devem ser consultados apenas para auditoria, evolução e estudo:

- [Índice histórico](historico/README.md);
- [Modelo de domínio V1 legado](historico/modelo_de_dominio_v1_legado.md);
- [Fluxo de implementação V1 legado](historico/fluxo_de_implementacao_v1_legado.md);
- [Contexto histórico do planejamento](historico/contexto_do_planejamento_do_projeto_backend.md);
- [Roadmap V1 legado](historico/roadmap_v1_legado.md).
- [Exemplos didáticos iniciais](historico/didatico/README.md).

## 6. Mapa de nomes antigos

| Nome antigo ou concorrente | Caminho canônico |
| --- | --- |
| `docs/1_arquitetura.md`, `docs/arquitetura.md`, `arquitetura.md` | `docs/vigentes/apoio/arquitetura.md` |
| `docs/2_modelo_dominio.md`, `docs/modelo_dominio.md`, `docs/modelo_de_dominio_v2.md` | `docs/vigentes/autoritativos/modelo_de_dominio.md` |
| `docs/3_fluxo_implementacao.md`, `docs/fluxo_implementacao.md` | `docs/vigentes/autoritativos/fluxo_de_implementacao.md` |
| `docs/4_roadmap.md`, `docs/roadmap.md`, `docs/roadmap_v2.md` | `docs/vigentes/autoritativos/roadmap.md` |
| `docs/4_roadmap_v1.md` | `docs/historico/roadmap_v1_legado.md` |
| `docs/5_contexto_planejamento.md`, `docs/vigentes/apoio/contexto_do_planejamento_do_projeto_backend.md` | `docs/historico/contexto_do_planejamento_do_projeto_backend.md` — histórico |
| `docs/6_explicacao_sistema_PCA.md`, `docs/vigentes/apoio/contexto_explicando_o_sistema_PCA.md` | `docs/vigentes/apoio/contexto_funcional_do_sistema_pca.md` |
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

## 8. Evolução do glossário e distribuição das explicações

### 8.1 Evolução contínua do glossário

O glossário deve evoluir junto com a documentação vigente. Durante a criação
ou revisão de RN, modelo de domínio, DI, fluxo, roadmap e demais documentos,
deve-se avaliar a inclusão de conceitos que sejam:

- específicos do projeto;
- reutilizados por múltiplos documentos;
- relevantes para a compreensão do domínio, arquitetura, integração ou
  operação;
- candidatos a gerar dúvidas recorrentes entre leitores.

Essa avaliação não implica inclusão automática. O glossário não deve funcionar
como:

- catálogo de código;
- dicionário de termos técnicos genéricos;
- inventário de tabelas, campos, APIs ou classes;
- catálogo de códigos, números ou parâmetros operacionais;
- duplicação de RN, DI ou outros documentos autoritativos.

O glossário deve concentrar definições semânticas estáveis e transversais. Cada
verbete deve indicar suas fontes vigentes e não pode criar regras, decisões,
valores padrão (`defaults`) ou significados ainda não consolidados. Quando
faltar uma definição autoritativa necessária, a lacuna deve ser tratada na
fonte responsável antes de ser resumida no glossário.

### 8.2 Critério de distribuição das explicações

Um documento autoritativo deve permanecer suficientemente compreensível para
que seu comportamento seja interpretado sem consulta obrigatória ao glossário.

Ao introduzir um termo:

1. Se sua compreensão alterar comportamento, responsabilidade, ordem de
   execução ou validade da operação, o documento autoritativo deve explicá-lo
   localmente e indicar as fontes aplicáveis.

2. Se o conceito for transversal e reutilizado em diferentes documentos, o
   documento deve apresentar a explicação necessária ao contexto, enquanto o
   glossário concentra sua definição semântica mais estável.

3. Se o conceito já estiver adequadamente definido no glossário e não for
   essencial para interpretar o comportamento descrito, o documento pode
   apenas utilizá-lo ou referenciá-lo.

4. Representações técnicas, nomes de campos, códigos, números, protocolos,
   métodos HTTP, classes concretas, parâmetros operacionais e detalhes de
   implementação podem aparecer como complemento pedagógico quando agregarem
   compreensão, preferencialmente depois do conceito correspondente, mas não
   devem ser promovidos automaticamente a verbetes.

5. Quando uma alteração documental modificar um conceito estável e transversal
   já registrado, o glossário deve ser revisado na mesma mudança para
   permanecer coerente com suas fontes.
