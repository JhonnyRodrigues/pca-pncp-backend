# Vamos planejar a Sprint 5.8 — Escritas e resultados incertos

Objetivo: “Planejar e, somente após minha aprovação explícita, implementar a Sprint 5.8 do roadmap”

Antes de atuar, leia integralmente, nesta ordem:

1. README.md;
2. AGENTS.md;
3. .agents/README.md;
4. docs/README.md;
5. docs/vigentes/operacional/estado_atual.md.

Depois, faça leitura direcionada, sem despejar arquivos inteiros desnecessariamente:

1. seção da Sprint 5.8 no Roadmap vigente;
2. DI-001, DI-004 e DI-009;
3. regras e trechos do fluxo de implementação referenciados por essas decisões;
4. componentes existentes relacionados a:
   - EscritorPncp;
   - Commands de publicação, inclusão e exclusão;
   - resultados e enums de escrita;
   - serialização PNCP;
   - transporte HTTP;
   - autenticação e sessão opaca;
   - observabilidade;
   - consumidores na Application;
   - testes contratuais PNCP.

Antes de propor componentes:

1. inspecione o estado do Git e os últimos commits;
2. preserve todas as alterações locais existentes;
3. confirme que a Sprint 5.7 está concluída e que a próxima sprint vigente é a 5.8;
4. pesquise referências e consumidores das APIs existentes;
5. confronte o desenho com DI-001, DI-004, DI-009, Roadmap, regras vigentes e contratos oficiais PNCP atuais;
6. verifique no Manual e no OpenAPI oficiais os endpoints, métodos, corpos, headers, status e respostas aplicáveis.

Escopo da Sprint 5.8:

- implementar publicação do PCA;
- implementar inclusão de itens;
- implementar exclusão de itens;
- implementar exclusão integral do PCA;
- classificar aceitação, rejeição, resultado incerto e falha sistêmica;
- preservar a ordem de exclusão antes de inclusão na Retificação;
- impedir retry automático ou cego de escritas;
- impedir particionamento não aprovado;
- manter respostas e falhas sanitizadas.

Limites obrigatórios:

- usar TDD;
- implementar somente em Infrastructure;
- se uma fonte autoritativa demonstrar incompatibilidade material com Domain, Application, Ports, Commands ou APIs públicas, parar e apresentar o problema antes de alterar fronteiras;
- não alterar camadas internas apenas para acomodar o adapter;
- não adicionar dependências;
- não criar commit;
- não antecipar a Sprint 5.9, Composition Root, Presentation ou integração Scriptcase;
- não executar pncp-live, probes Oracle/PNCP, escrita externa ou acesso real à rede;
- não repetir automaticamente POST, PUT ou DELETE;
- não transformar timeout ou perda de resposta em rejeição;
- não transformar status ambíguo em sucesso;
- não particionar operações acima do limite sem autorização normativa;
- não registrar token, credenciais, Authorization, headers completos, corpos, URLs, CNPJ, UASG, sequencial ou IDs de itens;
- manter compatibilidade com PHP 8.1, mesmo que os testes executem em PHP 8.4.

Antes do plano, esclareça explicitamente, com indicação das fontes:

1. quais métodos existentes de EscritorPncp serão implementados;
2. quais Commands e Results participam de cada operação;
3. quais endpoints, métodos HTTP, payloads e respostas oficiais sustentam cada escrita;
4. quais respostas comprovam aceitação;
5. quais respostas comprovam rejeição;
6. quais falhas produzem resultado incerto;
7. quais condições são falhas sistêmicas;
8. como distinguir resposta HTTP recebida de falha de transporte;
9. como tratar corpo vazio, JSON inválido, headers ausentes e status inesperado;
10. se algum endpoint devolve ou confirma sequencialPca e como validá-lo;
11. por que nenhuma escrita terá retry automático;
12. como preservar exclusão antes de inclusão sem mover coordenação da Application;
13. como o adapter preservará as coleções recebidas pelos Commands sem particionamento, considerando que o limite de 1.000 já é protegido pela Application;
14. quais dados podem aparecer em eventos e falhas sanitizadas;
15. quais decisões continuam deliberadamente reservadas à Sprint 5.9.

Se houver lacuna, conflito entre Manual, OpenAPI, DI ou contratos internos, não invente comportamento. Apresente a questão material e aguarde decisão antes do TDD.

Estruture o plano em poucos microciclos Red → Green → Refactor, agrupados por responsabilidade, não necessariamente um por endpoint.

Sugestão de organização a confirmar pelas fontes:

1. publicação e sequencial retornado;
2. inclusão e exclusão de itens;
3. exclusão integral;
4. classificação de rejeição, incerteza e falha sistêmica;
5. gate final e documentação.

A aprovação inicial deverá liberar somente o Microciclo 1.

Ao final de cada microciclo:

- explique concisamente a função de cada arquivo criado ou alterado;
- informe somente evidências realmente executadas;
- execute testes focados durante Red/Green;
- não repita a suíte completa em todos os microciclos;
- pause obrigatoriamente;
- aguarde minha autorização explícita para o próximo microciclo.

No gate final, execute:

- testes focados da escrita PNCP;
- Infrastructure/Pncp;
- serialização PNCP;
- suíte contratual não real;
- suíte completa;
- lint dos arquivos afetados;
- PSR-4 estrito;
- git diff --check;
- inspeção integral do diff e git status.

Linha de base conhecida, sujeita a confirmação local:

- suíte padrão: 668 testes e 2.290 asserções;
- suíte contratual não real: 24 testes e 51 asserções;
- Infrastructure/Pncp: 195 testes e 484 asserções;
- serialização PNCP: 88 testes e 149 asserções;
- leitura PNCP: 71 testes e 187 asserções.

Para reduzir consumo de tokens:

- use rg e leituras direcionadas;
- não reproduza grandes trechos dos documentos;
- não repita decisões já estabelecidas;
- mantenha atualizações intermediárias curtas;
- mostre somente saídas relevantes de testes;
- reserve validações amplas para o gate final;
- não investigue áreas sem consumidor ou risco concreto.

Nesta primeira resposta, não implemente nem altere arquivos. Apresente o plano final com microciclos.

Ao final, aguarde minha aprovação explícita.
