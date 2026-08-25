Vamos iniciar o planejamento da Sprint 5.7 — Observação, paginação e ausência definitiva.

Use Goal Mode com o objetivo:

"Planejar e, somente após minha aprovação explícita, implementar a Sprint 5.7 do roadmap"

Antes de atuar, leia integralmente, nesta ordem:

1. README.md;
2. AGENTS.md;
3. .agents/README.md;
4. todos os documentos presentes em docs/vigentes/.

Depois:

1. inspecione o estado do Git e os últimos commits;
2. confirme que a Sprint 5.6 está concluída e que a próxima sprint vigente é a 5.7;
3. leia os componentes existentes relacionados às Ports PNCP, Commands, resultados de observação, vínculo externo, transporte HTTP, autenticação, serialização e testes contratuais;
4. pesquise referências e consumidores antes de propor novos componentes;
5. confronte obrigatoriamente o plano com DI-002, DI-009, Roadmap, regras vigentes e contratos oficiais PNCP aplicáveis.

Escopo da Sprint 5.7:

- implementar leituras completas e paginação segura;
- validar coerência entre páginas;
- distinguir ausência definitiva, resposta inconclusiva e conflito;
- impedir adoção automática de PCA sem vínculo conhecido.

Limites:

- usar TDD;
- implementar somente em Infrastructure, salvo se uma fonte autoritativa demonstrar uma incompatibilidade material — nesse caso, parar e expor o problema antes de alterar qualquer fronteira interna;
- não alterar Domain, Application, Ports, Commands ou APIs públicas apenas para acomodar a implementação;
- não antecipar escritas, endpoints de mutação ou classificação de resultados da Sprint 5.8;
- não adicionar dependências;
- não criar commit;
- preservar alterações locais existentes;
- não executar pncp-live, probes Oracle/PNCP, escrita externa ou integração Scriptcase;
- não tratar resposta vazia, erro HTTP ou paginação interrompida como ausência definitiva sem fundamento autoritativo;
- não adotar automaticamente PCA encontrado externamente quando não houver vínculo conhecido;
- manter compatibilidade com PHP 8.1, mesmo que os testes sejam executados em PHP 8.4.

Antes de propor a implementação, esclareça explicitamente, com indicação das fontes:

1. quais métodos e tipos existentes da Port PNCP serão implementados;
2. quais endpoints e respostas oficiais sustentam cada leitura;
3. como funcionam índice inicial, tamanho, total de páginas, última página e critério de encerramento;
4. como detectar repetição, salto, duplicidade, mudança de metadados ou incoerência entre páginas;
5. quais condições comprovam ausência definitiva;
6. quais condições produzem observação inconclusiva;
7. o que constitui conflito de identidade ou de vínculo;
8. como CNPJ, ano, sequencial e ID_DEMANDA participam da identidade observada;
9. como garantir que observação externa não crie nem adote vínculo automaticamente;
10. quais dados de respostas e falhas precisam permanecer sanitizados.

Se houver lacuna, conflito entre fontes ou decisão material ainda não estabelecida, não invente comportamento: apresente a questão antes do TDD.

Estruture o plano em microciclos Red → Green → Refactor. A aprovação inicial deverá liberar somente o Microciclo 1. Ao final de cada microciclo:

- apresente o diff;
- informe apenas as evidências realmente executadas;
- pause obrigatoriamente;
- aguarde minha autorização explícita para o microciclo seguinte.

Considere como linha de base conhecida, sujeita a confirmação local:

- suíte padrão: 597 testes e 2.102 asserções;
- suíte contratual não real: 24 testes e 51 asserções;
- Infrastructure/Pncp: 124 testes e 296 asserções;
- serialização PNCP: 88 testes e 149 asserções.

Nesta primeira resposta, não implemente nem altere arquivos. Apresente somente:

- entendimento do escopo;
- decisões já sustentadas pelas fontes;
- lacunas ou decisões que precisem de aprovação;
- plano final dos microciclos;
- validações previstas.

Ao final, aguarde minha aprovação explícita.