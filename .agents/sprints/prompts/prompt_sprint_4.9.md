Vamos iniciar a Sprint 4.9 — Preparação global e unidades externas.

Antes de atuar, leia integralmente e nesta ordem:

1. README.md
2. .agents/AGENTS.md
3. .agents/README.md
4. docs/README.md
5. os três checkpoints do planejamento do Roadmap V2, respeitando sua
   precedência incremental;
6. os documentos vigentes na ordem definida por docs/README.md, incluindo
   docs/vigentes/autoritativos/roadmap.md;
7. o relatório final da Sprint 4.8 e sua auditoria, se estiverem disponíveis.

Estado confirmado:

- o Roadmap V2 está vigente;
- as Sprints 4.7 e 4.8 foram concluídas e validadas;
- os Ports e contratos tipados da Application estão disponíveis;
- o próximo incremento autorizado é exclusivamente a Sprint 4.9;
- as Sprints 4.10 e posteriores não devem ser antecipadas;
- Infrastructure não deve ser iniciada;
- todas as alterações locais existentes devem ser preservadas;
- código e testes não são fontes normativas.

Objetivo da Sprint 4.9:

Implementar, com mocks e contratos internos, a preparação global da execução e
a formação das unidades externas de publicação, conforme RN-CAD-003 a
RN-CAD-006, RN-PUB-009 a RN-PUB-011, DI-003, DI-007, DI-010, Fluxo e Roadmap.

A Sprint deve tratar:

- validação do contexto do usuário autenticado;
- obtenção coesa de Representante e Lotação;
- descoberta de identidades de Plano para o exercício;
- descoberta sem reconstrução antecipada das fotografias;
- validação das Unidades Orçamentárias participantes;
- validação das UASGs aplicáveis;
- leitura da estratégia anual corrente;
- adoção ou conferência da estratégia de publicação;
- formação de unidades externas no modo individual;
- formação de uma unidade externa no modo consolidado;
- aplicação das regras de UASG de cada modo;
- autenticação uma única vez por execução;
- reutilização da sessão opaca durante a execução preparada;
- falhas sistêmicas de preparação sem fabricação de resultados unitários;
- uso exclusivo dos contratos aprovados na Sprint 4.8.

Antes de alterar arquivos:

1. inspecione git status;
2. preserve todas as alterações locais;
3. confronte código e contratos da 4.8 com RN, Modelo, DI, Fluxo e Roadmap;
4. identifique os incrementos mínimos da preparação global;
5. apresente um plano decision-complete;
6. descreva individualmente os fluxos individual e consolidado;
7. defina as pré-condições e falhas de cada etapa;
8. organize a execução em ciclos pequenos Red → Green → Refactor;
9. não implemente até minha aprovação explícita do plano.

O plano deve esclarecer:

- qual componente coordena a preparação global;
- como o CNPJ e o Ano de Execução entram no fluxo;
- como o contexto do usuário é resolvido;
- como identidades são descobertas sem montar fotografias;
- como UO e UASG são validadas;
- como a estratégia é lida, adotada ou rejeitada;
- como unidades individuais são formadas;
- como a unidade consolidada é formada;
- como a sessão PNCP é criada uma única vez e reutilizada;
- quais falhas interrompem toda a execução;
- quais resultados ainda não podem ser produzidos nesta sprint;
- quais contratos da Sprint 4.8 são consumidos;
- quais testes demonstram RN-PUB-009, RN-PUB-010 e RN-PUB-011.

Fronteiras obrigatórias:

- não solicitar exclusividade por unidade;
- não reler fotografias dentro de lock;
- não observar o estado externo completo;
- não executar a política para processar uma unidade;
- não enviar comandos de publicação, retificação ou exclusão;
- não implementar o processamento unitário da Sprint 4.10;
- não implementar reconciliação adicional;
- não confirmar versões;
- não persistir estado operacional;
- não persistir auditoria coordenada;
- não implementar resultados globais finais;
- não criar adapters;
- não alterar Infrastructure;
- não aplicar defaults implícitos de estratégia, UASG, unidade ou falha.

Critérios de saída:

- preparação global demonstrada por testes com mocks;
- contexto do usuário validado;
- identidades descobertas sem fotografia antecipada;
- UO e UASG validadas;
- estratégia anual conferida ou adotada explicitamente;
- unidades individuais e consolidada corretamente representadas;
- autenticação executada uma única vez;
- nenhuma atividade da Sprint 4.10 ou posterior antecipada;
- suíte aplicável verde;
- diff revisado;
- resultado apresentado para minha validação;
- Roadmap atualizado somente após confirmação real da conclusão.