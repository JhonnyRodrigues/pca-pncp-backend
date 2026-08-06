Vamos iniciar a execução da **Sprint 4.9**, definida em:

`docs/vigentes/autoritativos/roadmap.md`

## 1. Leitura obrigatória

Antes de criar, alterar ou excluir qualquer artefato, leia integralmente a documentação do projeto, respeitando rigorosamente a seguinte ordem:

1. `README.md`;
2. `.agents/AGENTS.md`;
3. `.agents/README.md`;
4. `docs/README.md`;
5. os três checkpoints, respeitando sua ordem cronológica e sua precedência incremental;
6. documento de Arquitetura;
7. documento de Regras de Negócio;
8. Modelo de Domínio;
9. Decisões de Integração;
10. Glossário;
11. Contexto Funcional;
12. Fluxo de Implementação;
13. Roadmap V2 vigente, em `docs/vigentes/autoritativos/roadmap.md`.

Não inicie a implementação antes de concluir essa leitura.

## 2. Critério de precedência documental

Considere os checkpoints como registros históricos de continuidade do planejamento.

Um checkpoint posterior prevalece sobre os anteriores somente nos pontos que ele tenha revisado de forma explícita.

Para determinar o estado atual do projeto, prevalecem os documentos classificados como **vigentes e autoritativos**, nos quais as decisões válidas já foram consolidadas.

Em caso de aparente conflito:

1. identifique os documentos envolvidos;
2. aplique a precedência documental definida pelo projeto;
3. não substitua uma decisão vigente por interpretação própria;
4. registre a conclusão adotada e sua fundamentação antes de implementar.

## 3. Validação inicial de escopo

Após concluir a leitura e antes de alterar o código:

1. sintetize o objetivo da Sprint 4.9;
2. identifique os contratos, regras, componentes e camadas envolvidos;
3. informe as dependências com artefatos existentes;
4. aponte eventuais ambiguidades, conflitos ou lacunas documentais;
5. proponha a divisão da sprint em microciclos coesos e verificáveis.

Essa síntese não deve reinterpretar ou ampliar o escopo definido no roadmap.

## 4. Execução em microciclos

Execute a Sprint 4.9 em microciclos pequenos, coesos e validáveis.

Cada microciclo deve seguir, quando aplicável, esta sequência:

1. **Red:** criar ou ajustar os testes que expressem o comportamento esperado;
2. **Green:** implementar apenas o necessário para satisfazer os testes e os contratos;
3. **Refactor:** melhorar nomes, responsabilidades, invariantes e dependências sem alterar o comportamento;
4. executar os testes focados;
5. executar a regressão pertinente;
6. revisar aderência à documentação autoritativa;
7. apresentar o relato do microciclo;
8. pausar obrigatoriamente para validação.

Não antecipe artefatos pertencentes a microciclos posteriores.

Não inicie a sprint seguinte nem implemente itens fora do escopo da Sprint 4.9.

## 5. Relato obrigatório após cada microciclo

Ao concluir cada microciclo relevante, informe:

* quais arquivos e artefatos foram criados, alterados ou removidos;
* qual responsabilidade cada artefato passou a exercer;
* por que cada alteração foi necessária;
* quais regras de negócio, invariantes ou contratos foram atendidos;
* quais alternativas foram consideradas e descartadas;
* por que essas alternativas foram descartadas;
* quais princípios de DDD, Clean Architecture, SOLID ou orientação a objetos influenciaram as decisões;
* quais testes foram criados ou modificados;
* quais comandos de teste foram executados;
* os resultados obtidos;
* eventuais riscos, débitos técnicos ou pontos ainda pendentes.

Diferencie claramente:

* decisões exigidas pela documentação;
* decisões técnicas necessárias para viabilizar a implementação;
* sugestões opcionais de melhoria.

## 6. Restrições de execução

Durante o trabalho:

* preserve todas as alterações locais já existentes;
* não reverta arquivos que não pertençam ao escopo do microciclo;
* não altere contratos públicos sem justificativa documental;
* não introduza abstrações especulativas;
* não crie infraestrutura antes de existir uma necessidade definida pelas camadas internas;
* não mova regras de domínio para controllers, gateways, repositórios ou serviços de infraestrutura;
* não utilize strings soltas ou valores padrão silenciosos quando o domínio exigir tipos explícitos;
* mantenha as dependências apontando para as camadas mais internas;
* respeite os padrões, nomes, namespaces e convenções já adotados pelo projeto;
* prefira alterações mínimas, explícitas e testáveis;
* não prossiga diante de falhas nos testes sem antes explicar e corrigir a causa.

## 7. Pausa obrigatória

Após apresentar o relato de cada microciclo, interrompa a execução e aguarde minha validação explícita.

A validação de um microciclo autoriza somente o início do microciclo imediatamente seguinte.

Não prossiga automaticamente para outro grupo de contratos, mesmo que o próximo passo pareça evidente.
