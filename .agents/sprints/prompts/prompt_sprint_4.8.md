Vamos iniciar a Sprint 4.8 — Ports e contratos tipados da Application.

Antes de atuar, leia integralmente e nesta ordem:

1. README.md
2. .agents/AGENTS.md
3. .agents/README.md
4. docs/README.md
5. os três checkpoints do planejamento do Roadmap V2, respeitando sua
   precedência incremental;
6. os documentos vigentes na ordem definida por docs/README.md, incluindo
   docs/vigentes/autoritativos/roadmap.md;
7. o relatório final da auditoria da Sprint 4.7, se estiver disponível.

Estado confirmado:

- o Roadmap V2 está vigente;
- a Sprint 4.7 foi concluída e auditada;
- o próximo incremento autorizado é exclusivamente a Sprint 4.8;
- a Sprint 4.9 e as posteriores não devem ser antecipadas;
- Infrastructure não deve ser iniciada;
- o código atual pode ser inspecionado, mas não é fonte normativa;
- todas as alterações locais existentes devem ser preservadas.

Objetivo da Sprint 4.8:

Definir Ports e contratos tipados da Application necessários ao fluxo futuro,
sem implementar adapters, orquestração de processamento ou detalhes de
Infrastructure.

A Sprint deve tratar, conforme DI-006, DI-007, Fluxo e Roadmap:

- autenticação PNCP retornando sessão opaca reutilizável;
- leitura PNCP separada de escrita PNCP;
- consulta de PCA conhecido por sequencial;
- busca de candidatos somente para diagnóstico ou migração assistida;
- comandos tipados para:
  - publicar PCA;
  - inserir itens;
  - excluir itens;
  - excluir integralmente o PCA;
- estratégia de publicação corrente e estratégia adotada;
- estado operacional cross-Plan em porta própria da Application;
- contexto coeso do usuário, Representante e Lotação;
- persistência do fato de auditoria;
- unidade de trabalho;
- exclusividade;
- sessão, observações, comandos, resultados e falhas tipadas;
- ausência de HTTP, JSON, URL, SQL, Oracle, ADOdb, OCI8 ou macros nos
  contratos internos;
- preservação do PlanoRepository no Domain.

Antes de alterar arquivos:

1. inspecione git status;
2. preserve todas as alterações locais;
3. confronte o código atual com RN, Modelo, DI, Fluxo e Roadmap;
4. inventarie as capacidades requeridas;
5. identifique contratos existentes que possam ser preservados;
6. identifique contratos provisórios ou ausentes;
7. apresente um plano decision-complete;
8. associe cada contrato proposto à sua autoridade normativa;
9. organize a execução em ciclos pequenos Red → Green → Refactor;
10. não implemente até minha aprovação explícita do plano.

O plano deve especificar para cada contrato:

- nome e namespace propostos;
- ownership;
- responsabilidade única;
- entradas e saídas tipadas;
- invariantes;
- falhas representáveis;
- consumidor futuro;
- motivo para existir na Sprint 4.8;
- motivo para não pertencer ao Domain ou à Infrastructure;
- teste que demonstrará seu contrato.

Fronteiras obrigatórias:

- não formar unidades individuais ou consolidadas;
- não implementar preparação global da Sprint 4.9;
- não reconstruir o UseCase completo;
- não solicitar locks nem executar processamento unitário;
- não observar ou escrever no PNCP concretamente;
- não implementar confirmação, transação ou auditoria coordenada;
- não criar adapters;
- não alterar Infrastructure;
- não aplicar defaults de modelagem ou arquitetura;
- não criar abstrações sem consumidor futuro identificado nas fontes vigentes.

Critérios de saída:

- Ports e contratos da Application definidos e testados;
- ownership coerente com DI-007;
- PlanoRepository permanece no Domain;
- contratos não expõem tecnologias externas;
- nenhuma responsabilidade da Sprint 4.9 ou posterior foi antecipada;
- suíte aplicável verde;
- diff revisado;
- resultado apresentado para minha validação;
- Roadmap atualizado somente após confirmação real da conclusão.