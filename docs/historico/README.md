# Documentação histórica

**Classificação documental:** Apoio

**Autoridade/Escopo:** índice e critérios de preservação do acervo histórico;
não possui autoridade sobre o conhecimento vigente

Esta pasta contém versões substituídas, investigações encerradas e
procedimentos concluídos que ainda possuem valor permanente para auditoria,
rastreabilidade ou aprendizado.

Os arquivos aqui presentes possuem finalidade histórica, auditável ou didática
e não representam a documentação vigente do projeto.

Ao analisar a arquitetura atual, utilizar o índice e os documentos classificados
como vigentes em [`docs/README.md`](../README.md).

Nem toda investigação encerrada integra este acervo. Durante seu encerramento,
as conclusões estáveis devem ser incorporadas à fonte vigente responsável pelo
assunto. O documento investigativo completo somente deve ser preservado em
`investigacoes/` quando ainda possuir valor permanente para auditoria,
rastreabilidade ou aprendizado. Materiais com valor apenas temporário podem ser
descartados (pelo .gitignore).

A preservação de uma investigação não transforma seu conteúdo em fonte vigente
nem substitui a documentação à qual suas conclusões foram incorporadas.

O acervo está organizado por finalidade:

| Grupo | Conteúdo |
| --- | --- |
| Versões substituídas na raiz desta pasta | Arquitetura, modelo de domínio, fluxo, roadmap e contexto de planejamento anteriores |
| [`didatico/`](didatico/README.md) | Exercícios iniciais e investigações explicativas preservadas para estudo |
| `investigacoes/` | Investigações encerradas cujo documento completo ainda possui valor permanente para auditoria, rastreabilidade ou aprendizado |
| `operacional/` | Gates, migrações e procedimentos concluídos que não devem ser repetidos como rotina vigente |

A pasta `docs/historico/` deve ser consultada apenas para:

- auditoria de decisões;
- comparação de evolução;
- estudo do processo de refatoração documental.

Não existe ordem normal de leitura para este acervo. Consulte-o por busca
direcionada somente quando precisar recuperar a origem de uma decisão, uma
evidência ou uma explicação anterior.
