Leia toda a documentação do projeto:

Leia integralmente, nesta ordem:

1. `.agents/AGENTS.md`
2. `.agents/handoff_planejamento_roadmap_v2.md`
3. `.agents/handoff_planejamento_roadmap_v2_complementar_di006_di008.md`
4. `.agents/handoff_planejamento_roadmap_v2_continuacao_03_di008_di010.md`
5. todos os documentos atuais da pasta `docs/`
- os documentos vigentes indicados na ordem de leitura de docs/README.md

Antes de iniciar qualquer implementação, identifique explicitamente:

# Goal

O que deve ser implementado nesta tarefa.

# Context

Quais documentos, regras de negócio, arquivos, testes e componentes são relevantes para a tarefa.

# Constraints

Quais restrições arquiteturais, técnicas e de negócio devem ser respeitadas.

# Done When

Quais critérios devem ser atendidos para considerar a tarefa concluída.

---

Antes de escrever qualquer código:

1. Se o roadmap vigente existir, verifique a próxima tarefa pendente nele;
   enquanto estiver planejado, use somente o plano aprovado da tarefa e os
   checkpoints aplicáveis.
2. Confirme que nenhuma etapa anterior está pendente.
3. Liste todas as regras de negócio relacionadas à tarefa.
4. Informe em qual documento e seção cada regra foi encontrada.
5. Explique a responsabilidade da classe que será criada.
6. Explique por que ela pertence à camada escolhida.
7. Explique o objetivo do teste que será implementado.

---

Siga rigorosamente:

- Clean Architecture
- DDD
- TDD
- PSR-4
- PSR-12
- Dependency Injection

Respeite integralmente as definições presentes em:

- docs/vigentes/apoio/arquitetura.md
- docs/vigentes/autoritativos/regras_de_negocio.md
- docs/vigentes/autoritativos/modelo_de_dominio.md
- docs/vigentes/autoritativos/decisoes_de_integracao.md
- fluxo e roadmap vigentes, quando criados e registrados em docs/README.md

---

Fluxo obrigatório de TDD:

1. Identificar as regras de negócio que serão testadas.
2. Escrever o teste primeiro.
3. Explicar por que o teste falha.
4. Implementar apenas o código mínimo necessário.
5. Executar novamente o raciocínio do teste.
6. Refatorar apenas se necessário.
7. Garantir que nenhuma regra documentada foi violada.

---

Regras obrigatórias:

- Implementar apenas a próxima tarefa do roadmap.
- Não antecipar funcionalidades futuras.
- Não criar classes que ainda não estejam previstas.
- Não alterar arquivos fora do escopo da tarefa.
- Não realizar refatorações não solicitadas.
- Não introduzir dependências sem justificativa clara.

---

Regras de domínio:

- Nunca inventar regras de negócio.
- Nunca inferir regras com base em experiências anteriores, exemplos genéricos ou convenções de mercado.
- Utilizar exclusivamente regras documentadas ou informadas explicitamente pelo usuário.
- Nunca inventar validações técnicas genéricas (por exemplo: limites de datas, tamanhos mínimos ou valores arbitrários) sem documentação explícita.

---

Validação arquitetural obrigatória:

Antes de gerar código, confirme que a solução:

- respeita a direção das dependências da Clean Architecture;
- não introduz regras de negócio fora do Domain;
- não acessa infraestrutura diretamente a partir do Domain;
- não coloca lógica de negócio em Repository, Controller, Client ou PayloadBuilder;
- mantém consistência com o modelo de domínio documentado.

---

Tratamento de inconsistências:

Caso encontre regras:

- ambíguas;
- contraditórias;
- incompletas;
- ausentes;

interrompa a implementação e apresente uma análise das inconsistências encontradas antes de escrever qualquer teste ou código.

---

Ao concluir a tarefa, responda utilizando a estrutura:

# Resumo

O que foi implementado.

# Regras de Negócio Aplicadas

Lista das regras utilizadas e suas respectivas origens na documentação.

# Justificativa Arquitetural

Por que a solução pertence à camada utilizada.

# Validação

Quais testes foram criados e quais comportamentos foram validados.

# Arquivos Alterados

Lista dos arquivos modificados e motivo da alteração.

# Riscos ou Pontos de Atenção

Possíveis limitações, dúvidas ou próximos passos relacionados à tarefa.
