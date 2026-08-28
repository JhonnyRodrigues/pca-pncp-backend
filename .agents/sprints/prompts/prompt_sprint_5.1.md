Você trabalhará no repositório deste backend PCA.

Objetivo

Iniciar e conduzir a Sprint 5.1 — Gates contratuais Oracle e PNCP.

Esta sprint deve produzir evidências reais para validar os contratos previstos antes da implementação da Infrastructure. Ela não autoriza criar adapters concretos das Sprints 5.2 a 5.8.

Trabalhe em português e preserve rigorosamente todas as alterações locais existentes.

Leitura obrigatória

Antes de planejar, auditar, executar comandos ou alterar arquivos, leia integralmente a documentação nesta ordem:

1. README.md
2. .agents/AGENTS.md
3. .agents/README.md
4. docs/README.md
5. .agents/planejamento/checkpoints/handoff_planejamento_roadmap_v2.md
6. .agents/planejamento/checkpoints/handoff_planejamento_roadmap_v2_complementar_di006_di008.md
7. .agents/planejamento/checkpoints/handoff_planejamento_roadmap_v2_continuacao_03_di008_di010.md
8. docs/vigentes/apoio/arquitetura.md
9. docs/vigentes/autoritativos/regras_de_negocio.md
10. docs/vigentes/autoritativos/modelo_de_dominio.md
11. docs/vigentes/autoritativos/decisoes_de_integracao.md
12. docs/vigentes/apoio/glossario.md
13. docs/vigentes/apoio/contexto_funcional_do_sistema_pca.md
14. docs/vigentes/autoritativos/fluxo_de_implementacao.md
15. docs/vigentes/autoritativos/roadmap.md

Depois, como apoio de navegação, leia:

16. docs/vigentes/apoio/guia_de_leitura_do_codigo.md
17. docs/vigentes/apoio/catalogo_de_arquivos.md

Os checkpoints são registros históricos de continuidade do planejamento. O posterior prevalece somente nos pontos que efetivamente revisou. Para o estado atual, prevalecem os documentos vigentes e autoritativos consolidados.

Leia cada arquivo até o fim. Se algum caminho tiver mudado, localize o arquivo correspondente e informe a divergência.

Estado arquitetural de entrada

A Fase 4 está concluída. Domain e Application estão implementados e demonstrados com doubles das Ports.

O fluxo vigente é:

Request
→ UseCase
→ preparação global
→ varredura
→ processamento da unidade
→ decisão do Domain
→ escrita
→ observação e eventual reconciliação
→ persistência e auditoria
→ Response

O Domain decide PUBLICAR, RETIFICAR, EXCLUIR_PCA ou NENHUMA_ACAO.

A Application coordena transações, Ports, ordem das escritas, observação, reconciliação, persistência e auditoria.

A Infrastructure futura deverá implementar os contratos internos existentes. Ela não deve descobrir novas regras silenciosamente nem absorver responsabilidades do Domain ou da Application.

Escopo da Sprint 5.1

Validar com evidências reais:

1. Scriptcase e Oracle
   - versão real do PHP e extensões disponíveis;
   - disponibilidade e comportamento de ADOdb/OCI8;
   - forma correta de obter e compartilhar a conexão emprestada;
   - responsabilidade pelo fechamento da conexão;
   - comportamento de commit e rollback;
   - mecanismo viável de exclusividade e locks;
   - schema, tabelas, colunas, joins, constraints e cardinalidades;
   - tipos Oracle efetivamente retornados;
   - datas, timestamps, strings, inteiros e decimais;
   - tabelas e campos relacionados a Planos, Demandas, estratégia, estado operacional, cancelamento homologado e auditoria.

2. PNCP
   - revalidar as fontes oficiais vigentes, incluindo manual e OpenAPI;
   - confirmar ambiente de treinamento autorizado;
   - validar disponibilidade de cURL, OpenSSL e CA bundle;
   - confirmar TLS, autenticação, formato e opacidade do JWT;
   - verificar endpoints, métodos, parâmetros, paginação e formatos de resposta;
   - verificar contratos de publicação, inclusão, exclusão de itens e exclusão integral;
   - verificar como distinguir aceitação, rejeição, resultado incerto e falha sistêmica;
   - verificar os critérios necessários para comprovar ausência definitiva;
   - verificar representação de datas e valores decimais, sem usar float.

3. Decisões de integração
   - confrontar as evidências com DI-008, DI-009 e contratos relacionados;
   - classificar cada premissa como CONFIRMADA, INCOMPATÍVEL ou AINDA NÃO COMPROVADA;
   - se houver incompatibilidade, identificar precisamente a decisão, Port, Command, Result ou regra afetada;
   - não corrigir ou esconder incompatibilidades dentro de um adapter inexistente.

Restrições

- Não implemente adapters concretos nesta sprint.
- Não antecipe as Sprints 5.2 a 5.8.
- Não redesenhe Domain ou Application.
- Não consolide Ports, Results, Enums ou Commands.
- Não altere contratos apenas por preferência estética.
- Não introduza framework, container ou Service Locator.
- Não crie Composition Root; isso pertence à Fase 6.
- Não faça chamadas de escrita em produção.
- Não faça testes destrutivos no Oracle.
- Não use o ambiente de produção do PNCP para testes.
- Chamadas que possam criar, alterar ou excluir estado externo exigem minha autorização explícita.
- Consultas Oracle devem ser somente leitura até autorização diferente.
- Nunca exponha credenciais, tokens, headers de autenticação, SQL sensível ou dados pessoais em comandos, logs ou respostas.
- Não invente evidências quando o ambiente, acesso ou contrato real não estiver disponível.
- Não marque um gate como confirmado apenas porque a documentação afirma que deveria funcionar.
- Preserve alterações locais existentes. Não normalize arquivos em massa, não reverta modificações do usuário e não faça mudanças fora do escopo.

Método de trabalho

1. Conclua toda a leitura obrigatória.
2. Inspecione `git status --short` e identifique alterações locais que precisam ser preservadas.
3. Inspecione o código e os testes somente na medida necessária para mapear cada evidência externa ao contrato interno correspondente.
4. Produza uma matriz inicial contendo:
   - gate;
   - premissa;
   - contrato interno afetado;
   - fonte esperada;
   - ambiente;
   - forma segura de comprovação;
   - evidência obtida;
   - situação;
   - bloqueio ou divergência.
5. Execute autonomamente verificações locais e consultas externas públicas que sejam seguras e somente leitura.
6. Antes de qualquer acesso que exija credenciais, ambiente privado, chamada PNCP que possa escrever ou consulta não claramente segura, pare e peça somente a autorização ou informação mínima necessária.
7. Se um gate estiver bloqueado por falta de acesso, continue nos demais gates independentes.
8. Registre comandos, versões, datas, fontes e resultados de maneira reproduzível, sanitizando segredos.
9. Se forem necessárias alterações documentais ou artefatos de evidência dentro da Sprint 5.1, faça somente mudanças pequenas, rastreáveis e compatíveis com a arquitetura documental existente.
10. Execute validações proporcionais a qualquer arquivo alterado.

Critério de sucesso

A Sprint 5.1 somente poderá ser considerada concluída quando:

- os gates obrigatórios possuírem evidência real e reproduzível;
- cada premissa estiver classificada;
- incompatibilidades estiverem explicitamente registradas;
- nenhum adapter tiver sido antecipado;
- nenhum segredo ou dado sensível tiver sido exposto;
- as validações locais aplicáveis estiverem verdes;
- estiver claro se a Sprint 5.2 pode ou não começar.

Se faltarem acessos ou evidências reais, não declare a sprint concluída. Informe o estado como bloqueado ou parcialmente comprovado, relacionando exatamente o que falta e como obtê-lo.

Primeira entrega

Após concluir a leitura e a auditoria inicial, apresente antes de qualquer mutação relevante:

1. sua compreensão resumida da Sprint 5.1;
2. o estado atual do repositório e das alterações locais;
3. a matriz inicial dos gates;
4. evidências que podem ser coletadas imediatamente;
5. acessos, autorizações ou informações que faltam;
6. arquivos que provavelmente precisarão ser criados ou alterados;
7. plano de execução em etapas;
8. critérios objetivos para liberar a Sprint 5.2.

Depois prossiga com todas as verificações seguras que não dependam de nova autorização. Não implemente adapters.

Entrega final

Ao encerrar a Sprint 5.1, informe:

- documentos e fontes consultados;
- ambientes e versões comprovados;
- comandos e verificações executados;
- evidências Oracle;
- evidências PNCP;
- gates confirmados, incompatíveis e pendentes;
- decisões que precisaram ser reabertas, se houver;
- arquivos criados ou alterados;
- validações executadas;
- limitações e bloqueios;
- estado final da Sprint 5.1;
- condição objetiva para iniciar a Sprint 5.2.