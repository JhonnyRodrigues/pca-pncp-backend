# Auditoria da Fase 4 — Application

**Classificação documental:** Histórico  
**Data:** 10/08/2026  
**Autoridade/Escopo:** evidência histórica da reabertura e estabilização da Fase 4

## Objetivo

Verificar se Domain e Application estavam aderentes aos documentos vigentes e
se o projeto podia iniciar adapters concretos da Fase 5. A auditoria foi feita
sem tratar checkpoints, prompts ou sínteses históricas como substitutos das
fontes autoritativas atuais.

## Evidências de entrada

- 321 testes e 1068 asserções passavam antes da Sprint 4.13.
- Domain e Application não importavam Infrastructure, HTTP, Oracle, ADOdb,
  OCI8, cURL, JSON ou Scriptcase.
- Autoload PSR-4 estrito, lint PHP e dependências de plataforma estavam válidos.
- A execução local utilizava PHP 8.3; a sintaxe também foi aceita pelo parser
  direcionado ao PHP 8.1.

## Achados

| ID | Severidade | Achado | Tratamento na Sprint 4.13 |
| --- | --- | --- | --- |
| F4-01 | Crítica | Exceção depois da abertura da transação da estratégia anual podia escapar sem rollback. | Proteção de todo o trecho transacional e falha segura prevalente no rollback. |
| F4-02 | Alta | O limite de 1000 era aplicado a `PUBLICAR`, mas não ao estado desejado de `RETIFICAR`. | Validação da fotografia resultante antes da primeira escrita. |
| F4-03 | Alta | `Demanda` usava `float` para valores monetários, contrariando DI-008 e DI-009. | `DecimalExato` mínimo e remoção do cálculo unitário em ponto flutuante. |
| F4-04 | Alta | Os caminhos concretos de escrita eram testados por estágio, mas não pelo grafo completo da Application. | Testes ponta a ponta com colaboradores concretos e doubles apenas nas Ports. |
| F4-05 | Média | Cinco scripts Composer ativos apontavam para suítes inexistentes e executavam zero testes. | Scripts removidos e aprendizado preservado no histórico didático. |
| F4-06 | Baixa | Teste fictício, exemplos iniciais e placeholders de Infrastructure poluíam a leitura do código ativo. | Exemplos úteis arquivados; teste e placeholders sem valor removidos. |
| F4-07 | Média | Documentos divergiam sobre cancelamento homologado e autoria da validação de entrada bruta. | Textos vigentes reconciliados sem antecipar Presentation. |

## Complexidade observada

Antes da correção, a Application possuía 72 arquivos e aproximadamente 5,3 mil
linhas para um caso de uso; o finalizador concentrava 778 linhas. A complexidade
do fluxo externo é real, mas a quantidade de tipos nomeados e a falta de um mapa
de leitura aumentavam o custo cognitivo. A Sprint 4.13 não consolidou Results,
Enums ou Ports e não fragmentou o finalizador: preferiu estabilizar contratos e
explicar o desenho vigente.

## Conclusão

A estrutura arquitetural era sólida, mas a Fase 4 não estava funcionalmente
encerrada antes dos tratamentos acima. Depois da Sprint 4.13, a regressão
aprovou 339 testes e 1139 asserções; Domain aprovou 99 testes e 159 asserções;
Application aprovou 240 testes e 980 asserções. Lint, parser PHP 8.1, autoload
PSR-4 estrito, requisitos de plataforma e fronteiras arquiteturais passaram.

O `composer validate --strict` manteve apenas o aviso transversal já conhecido
de licença não declarada, sem invalidar o schema do arquivo. A Fase 4 pôde ser
encerrada novamente, liberando somente a Sprint 5.1. Essa conclusão não
substitui os gates reais de Scriptcase, Oracle e PNCP.
