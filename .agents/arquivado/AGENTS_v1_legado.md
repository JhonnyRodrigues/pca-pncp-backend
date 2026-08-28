# AGENTS.md

## Objetivo do projeto

Este projeto implementa um backend em PHP 8.1 responsável pela publicação e retificação do Plano de Contratações Anual (PCA) junto ao Portal Nacional de Contratações Públicas (PNCP).

O backend será consumido inicialmente por uma aplicação Scriptcase.

---

## Antes de qualquer implementação

Leia toda a documentação da pasta `docs/`.

A ordem recomendada é:

1. arquitetura.md
2. modelo_dominio.md
3. fluxo_implementacao.md
4. roadmap.md
5. contexto_planejamento.md
6. explicacao_sistema_PCA.md

---

## Arquitetura

Este projeto utiliza:

- PHP 8.1
- Clean Architecture
- Oracle 19c
- PSR-12
- PSR-4
- PSR-3
- Dependency Injection
- TDD
- DDD

Nunca viole a direção das dependências.

Presentation
↓

Application
↓

Domain

Infrastructure implementa interfaces do domínio.

---

## Preservação Arquitetural

Em caso de dúvida, priorizar:

1. Correção arquitetural
2. Regras de negócio
3. Testabilidade
4. Simplicidade
5. Desempenho

Nunca sacrificar a arquitetura para reduzir quantidade de código.

Sempre preferir soluções consistentes com Clean Architecture.


---

## Regras importantes

Nunca coloque regras de negócio em:

- Repository
- Controller
- Client
- PayloadBuilder
- Infrastructure

As regras pertencem exclusivamente ao Domain.

Também seguir as seguintes regras:

- nunca acessar Oracle diretamente pelo UseCase
- toda regra de negócio fica no Domain
- nunca colocar regras de negócio em Repository
- mapeamentos entre sistemas externos não pertencem ao Domain
- conversões entre identificadores SGI, Oracle, PNCP ou outros sistemas pertencem à Infrastructure
- não criar código que não foi solicitado
- não antecipar implementações futuras
- não modificar arquivos fora do escopo da tarefa
- explicar decisões arquiteturais relevantes
- gerar comentários apenas quando agregarem valor real

---

## Planejamento

Antes de implementar qualquer alteração:

- inspecionar o código relacionado
- identificar padrões existentes
- identificar interfaces reutilizáveis
- verificar testes existentes
- consultar documentação relevante

Para tarefas complexas, ambíguas, com múltiplos arquivos ou impacto arquitetural:

- elaborar um plano de execução
- listar premissas assumidas
- identificar riscos
- definir estratégia de validação

Para aplicar o DDD, entenda as regras de negócio, me fazendo perguntas quando necessário, para delimitar contextos e estruturar o domínio de forma que o código reflita o que acontece no mundo real.

Evite perguntas desnecessárias quando uma premissa segura permitir o avanço da implementação.

---

## Diretrizes de DDD

O código deve refletir o domínio do negócio e utilizar a linguagem utilizada pelos especialistas do processo de PCA e PNCP.

Antes de introduzir novas entidades, objetos de valor, serviços de domínio ou agregados:

- compreender o significado de negócio dos conceitos envolvidos
- identificar responsabilidades reais do domínio
- evitar modelagem baseada apenas na estrutura do banco de dados
- evitar entidades anêmicas
- evitar serviços de domínio sem justificativa clara
- utilizar Value Objects sempre que o conceito possuir identidade apenas pelo valor
- proteger invariantes dentro do Domain

Ao propor alterações relevantes no domínio, explicar:

- qual conceito do negócio está sendo representado
- por que foi modelado dessa forma
- quais invariantes estão sendo protegidas
- quais regras pertencem ao domínio
- quais responsabilidades foram mantidas fora do domínio e por quê

Quando houver incerteza sobre regras de negócio, buscar esclarecimento antes de modelar o domínio.

---

## Dependências Permitidas

Antes de criar novas classes:

- verificar se já existe implementação semelhante
- verificar se já existe interface apropriada
- reutilizar componentes existentes quando possível

Evitar:

- abstrações prematuras
- duplicação de responsabilidades
- proliferação desnecessária de classes

Novas dependências externas só devem ser introduzidas mediante justificativa clara.

---

## Ordem de desenvolvimento

Seguir o roadmap.

Nunca implementar etapas futuras.

Implementar apenas a próxima tarefa prevista.

Caso identifique dependências não previstas, informar antes de prosseguir.

---

## Escopo das Alterações

Realizar apenas mudanças necessárias para atender a tarefa solicitada.

Evitar:

- refatorações não solicitadas
- mudanças amplas sem necessidade
- alterações em APIs públicas sem justificativa
- alterações em arquivos não relacionados

Preferir alterações pequenas, seguras e coesas.

---

## TDD

Para cada funcionalidade:

1. escrever teste
2. executar teste
3. observar falha esperada
4. implementar código mínimo
5. executar novamente
6. refatorar
7. garantir que todos os testes permaneçam verdes

Seguir sempre o ciclo Red → Green → Refactor.

---

## Validação

Sempre que possível:

- executar testes afetados
- executar suíte relacionada
- verificar conformidade com PSR-12
- verificar tipagem
- verificar imports desnecessários
- verificar contratos de interfaces
- verificar compatibilidade arquitetural

Se algum teste ou validação não puder ser executado, informar explicitamente o motivo.

---

## Revisão

Antes de concluir uma tarefa, revisar:

- regressões
- violações arquiteturais
- dependências desnecessárias
- código duplicado
- edge cases
- tratamento de erros
- testes ausentes
- acoplamento excessivo
- arquivos alterados indevidamente

Garantir que a solução permaneça simples e consistente com os padrões do projeto.

---

## Estilo

Priorizar:

- simplicidade
- baixo acoplamento
- alta coesão
- nomes claros. Exemplos: getValor, setNome, validarOrigem, foiPublicado
- métodos pequenos
- legibilidade
- testabilidade

Evitar complexidade desnecessária.

---

## Planos de Execução

Ao implementar funcionalidades complexas ou realizar refatorações significativas:

- utilizar ou criar um Plano de Execução
- seguir as orientações descritas em `.agent/PLANS.md`
- manter o plano atualizado durante a implementação

---

## Ao Responder

Antes de gerar código, confirmar mentalmente que a implementação respeita a arquitetura do projeto.

Ao concluir uma tarefa, apresentar:

### Resumo

- o que foi implementado

### Justificativa de Domínio (DDD)

- qual regra de negócio está sendo atendida
- quais conceitos do domínio foram identificados
- quais entidades, value objects ou serviços de domínio participaram da solução
- por que a modelagem escolhida representa adequadamente o negócio
- quais invariantes ou restrições de negócio estão sendo protegidas
- como a solução preserva a linguagem ubíqua do domínio

### Justificativa Arquitetural

- por que a classe existe
- qual responsabilidade ela possui
- por que pertence àquela camada

### Validação

- quais testes foram criados
- quais testes foram executados
- resultado das validações realizadas

### Riscos ou Pontos de Atenção

- limitações conhecidas
- possíveis melhorias futuras
- dependências para próximas etapas

### Arquivos Alterados

- listar arquivos modificados
- justificar brevemente cada alteração

Ser conciso, técnico e objetivo.
