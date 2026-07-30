# Governança dos Artefatos de Agentes

**Classificação:** índice de governança  
**Status:** vigente  
**Autoridade/Escopo:** classificação, precedência e uso seguro dos artefatos de
`.agents/`

Este índice explica como interpretar os arquivos destinados a agentes. Ele não
substitui as instruções operacionais, os checkpoints nem a documentação do
sistema.

## 1. Ordem inicial de leitura

Um agente deve iniciar pelo contexto geral e avançar para o estado específico
da tarefa:

1. [`README.md`](../README.md) — visão, finalidade e estado geral do projeto;
2. [`AGENTS.md`](AGENTS.md) — única instrução operacional permanente;
3. este índice — governança dos artefatos de agentes;
4. [`docs/README.md`](../docs/README.md) — taxonomia e ordem da documentação;
5. checkpoints aplicáveis à tarefa, respeitando sua precedência;
6. documentos vigentes indicados pelo índice documental.

Documentos históricos, investigações, sínteses e prompts antigos não
representam o estado atual por si mesmos.

## 2. Classificação

| Artefato | Classificação e autoridade |
|---|---|
| [`AGENTS.md`](AGENTS.md) | Instrução operacional vigente e permanente para agentes neste repositório |
| [`planejamento/checkpoints/`](planejamento/checkpoints/) | Registros históricos incrementais com autoridade de precedência e continuidade sobre o planejamento |
| [`planejamento/sinteses/`](planejamento/sinteses/) | Fontes históricas independentes de conferência; não substituem os checkpoints |
| [`sprints/prompts/`](sprints/prompts/) | Prompts históricos, inclusive o antigo prompt inicial reutilizável; preservados para auditoria e aprendizado |
| [`arquivado/AGENTS_v1_legado.md`](arquivado/AGENTS_v1_legado.md) | Versão histórica das instruções operacionais, disponível somente para auditoria e aprendizado |
| [`arquivado/sugestao_enum_fase4.md`](arquivado/sugestao_enum_fase4.md) | Proposta histórica não aprovada, sem autoridade normativa ou operacional |

Não existe prompt inicial estático vigente. Cada novo chat deve receber um
prompt específico, derivado da tarefa, dos checkpoints aplicáveis e da
documentação vigente naquele momento.

## 3. Precedência dos checkpoints

Os checkpoints do Roadmap V2 devem ser lidos nesta ordem:

1. [handoff principal](planejamento/checkpoints/handoff_planejamento_roadmap_v2.md);
2. [handoff complementar](planejamento/checkpoints/handoff_planejamento_roadmap_v2_complementar_di006_di008.md);
3. [continuação 03](planejamento/checkpoints/handoff_planejamento_roadmap_v2_continuacao_03_di008_di010.md).

Um checkpoint posterior prevalece somente sobre o ponto que revisou. A
cronologia e o conteúdo histórico devem ser preservados.

## 4. Uso seguro e manutenção

- Prompts históricos não devem ser executados novamente nem promovidos
  implicitamente a instruções atuais.
- Caminhos, premissas e decisões presentes em artefatos históricos pertencem
  ao momento em que foram registrados.
- Sínteses servem para conferir integridade, sem autorizar que os checkpoints
  sejam ignorados.
- A autoridade documental do sistema é definida em
  [`docs/README.md`](../docs/README.md).
- Uma instrução reutilizável que se torne obsoleta deve ser arquivada em
  `sprints/prompts/`, com sua condição histórica registrada neste índice ou no
  índice de sprints.
- Novas instruções permanentes devem ser consolidadas em `AGENTS.md`, evitando
  fontes operacionais concorrentes.
