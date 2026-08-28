Bom dia. Estamos retomando o planejamento para criar a nova versão do roadmap do backend PCA/PNCP, em substituição ao descontinuado roadmap_v1_legado.md

Antes de atuar, leia integralmente e exatamente nesta ordem:

1. `README.md`
2. `.agents/AGENTS.md`
3. `.agents/README.md`
4. `docs/README.md`
5. os checkpoints aplicáveis, na precedência definida por `.agents/README.md`:
   - `.agents/planejamento/checkpoints/handoff_planejamento_roadmap_v2.md`
   - `.agents/planejamento/checkpoints/handoff_planejamento_roadmap_v2_complementar_di006_di008.md`
   - `.agents/planejamento/checkpoints/handoff_planejamento_roadmap_v2_continuacao_03_di008_di010.md`
6. os documentos vigentes, na ordem indicada por `docs/README.md`.

Não use nomes ou caminhos antigos quando existir um caminho canônico registrado em `docs/README.md`.

Não trate como fontes normativas:

- documentos históricos;
- investigações;
- sínteses;
- prompts antigos;
- código atual quando divergir da documentação autoritativa.

Consulte esses materiais somente se os índices determinarem que são relevantes para auditoria ou conferência.

Contexto importante:

- DI-001 a DI-010 estão encerradas nos termos dos checkpoints, respeitando seus gates;
- `regras_de_negocio.md`, `modelo_de_dominio.md` e `decisoes_de_integracao.md` já são documentos vigentes;
- o fluxo de implementação V2 e o Roadmap V2 ainda precisam ser planejados;
- determine o próximo passo realmente pendente a partir do estado atual dos índices, checkpoints e documentos vigentes.
- não repita perguntas já respondidas sem nova evidência;

Antes de alterar qualquer arquivo:

1. inspecione `git status`;
2. preserve todas as alterações locais existentes;
3. confirme os caminhos canônicos e a classificação de cada documento;
4. confronte o estado declarado em `README.md` e `docs/README.md` com os checkpoints;
5. identifique exatamente qual é o próximo passo ainda não concluído da seção 17;
6. informe quais arquivos esse passo poderá alterar;
7. apresente um plano decision-complete e proporcional apenas para esse próximo passo.

Restrições:

- não implemente código;
- não inicie Infrastructure;
- não antecipe etapas posteriores;
- não altere arquivos antes de eu aprovar o plano;
- não invente regras, defaults ou decisões para preencher lacunas;
- não repita perguntas já respondidas nos checkpoints sem nova evidência;
- se uma decisão indispensável não puder ser descoberta no repositório, faça uma pergunta por vez e explique seu impacto.