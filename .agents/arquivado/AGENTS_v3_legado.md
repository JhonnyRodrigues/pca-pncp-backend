# AGENTS.md

## 1. Finalidade e autoridade

Este arquivo contém as instruções operacionais permanentes para agentes que
trabalham neste repositório. Ele orienta a forma de planejar, implementar,
validar e comunicar as tarefas, mas não cria regras de negócio, conceitos de
domínio, decisões de integração nem etapas do roadmap.

O projeto implementa um backend em PHP 8.1 para publicação e sincronização do
Plano de Contratações Anual (PCA) com o Portal Nacional de Contratações Públicas
(PNCP). Inicialmente, o backend será consumido por uma aplicação Scriptcase.

Além da entrega do sistema, o projeto possui finalidade didática. Ele é usado
para estudar e praticar:

- Domain-Driven Design (DDD);
- Clean Architecture;
- Design Patterns;
- SOLID;
- Test-Driven Development (TDD);
- testes unitários;
- boas práticas de programação em PHP;
- Spec-Driven Development (SDD).

A finalidade didática não autoriza complexidade desnecessária. O agente deve
ensinar por meio da solução mais simples que preserve o comportamento, as regras
do negócio e as fronteiras arquiteturais necessárias.

### 1.1 Princípio central

> Implementar somente o necessário para a sprint atual, da forma mais simples,
> clara, testável e reversível possível.

DDD, Clean Architecture, SOLID, padrões de projeto e TDD são instrumentos para
reduzir riscos e organizar responsabilidades. Eles não são objetivos isolados e
não justificam a criação antecipada de classes, interfaces, camadas ou
mecanismos sem necessidade concreta.

## 2. Fontes e precedência

Antes de atuar, leia somente o necessário para compreender a tarefa, respeitando
esta ordem:

1. `README.md`, para compreender o projeto e sua finalidade;
2. `.agents/AGENTS.md`, para conhecer estas instruções;
3. `.agents/README.md`, para interpretar os artefatos de agentes;
4. `docs/README.md`, para conhecer a taxonomia e a autoridade documental;
5. `docs/roadmap.md`, para identificar a sprint autorizada;
6. especificações, regras, modelos, decisões e checkpoints diretamente
   relacionados à sprint.

Não é necessário reler documentos sem relação com a tarefa apenas para cumprir
formalidade.

A autoridade por assunto é definida em `docs/README.md`. Em termos gerais:

| Assunto | Fonte autoritativa |
|---|---|
| Visão e finalidade do projeto | `README.md` |
| Ordem de construção | roadmap vigente |
| Regras de negócio | catálogo autoritativo de regras |
| Conceitos, identidades e invariantes | modelo de domínio autoritativo |
| Integração, persistência e operação | decisões de integração autoritativas |
| Continuidade do planejamento | checkpoints aplicáveis |

Documentos históricos, investigações, sínteses e prompts antigos não são fontes
normativas, salvo indicação expressa nos índices documentais.

Quando houver divergência entre fontes, não combine premissas incompatíveis.
Aplique a fonte responsável pelo assunto e informe a inconsistência.

Nunca invente regra de negócio, validação, valor padrão ou decisão de integração
para preencher uma lacuna. Quando a lacuna impedir materialmente a
implementação, solicite esclarecimento antes de definir o comportamento.

## 3. Antes de alterar o código

Antes de modificar arquivos:

- inspecione o estado atual do Git;
- leia os arquivos diretamente envolvidos;
- preserve alterações locais existentes;
- confirme a sprint e o escopo autorizado;
- identifique o comportamento esperado e sua fonte;
- defina uma validação proporcional à mudança.

Não investigue áreas adjacentes sem necessidade concreta para concluir a sprint.

## 4. Planejamento da sprint

Quando o modo de planejamento estiver habilitado, produza um plano de
implementação antes de alterar o código.

O plano deve ser suficiente para revisão, mas não deve transformar uma sprint
pequena em um projeto arquitetural completo.

### 4.1 Conteúdo do plano

O plano deve apresentar, de forma objetiva:

1. objetivo da sprint;
2. comportamento ou capacidade que será entregue;
3. fontes documentais diretamente aplicáveis;
4. menor conjunto de arquivos ou componentes provavelmente necessário;
5. sequência de implementação em incrementos pequenos;
6. testes e validações previstos;
7. riscos, lacunas ou decisões que realmente exigem atenção;
8. itens deliberadamente adiados por não pertencerem à sprint.

### 4.2 Regra de simplicidade do plano

Para cada novo artefato proposto, o agente deve conseguir responder:

- qual necessidade atual exige sua existência;
- quem o utilizará nesta sprint ou em código já existente;
- qual problema concreto ele evita;
- por que uma solução mais direta seria insuficiente.

Se essas respostas não forem claras, o artefato não deve ser criado agora.

O plano deve preferir:

- poucos passos coesos;
- alterações pequenas e revisáveis;
- uma implementação mínima funcional;
- evolução posterior orientada por novas necessidades reais.

O plano não deve:

- antecipar sprints futuras;
- criar extensibilidade hipotética;
- propor abstrações apenas porque são comuns em exemplos de arquitetura;
- multiplicar DTOs, interfaces, factories, services, policies ou mappers sem
  uso concreto;
- listar todas as alternativas teoricamente possíveis quando uma decisão
  simples já é suficiente;
- transformar cada classe em um padrão de projeto nomeado.

### 4.3 Complexidade proporcional

Classifique informalmente a solução proposta como baixa, média ou alta
complexidade.

Quando a complexidade parecer maior que o comportamento solicitado, revise o
plano e apresente primeiro uma alternativa mais simples.

Uma solução de maior complexidade somente deve ser adotada quando exigida por:

- regra ou invariante real do domínio;
- fronteira externa efetivamente existente;
- necessidade atual de substituição de implementação;
- requisito de transação, segurança, auditoria ou confiabilidade;
- duplicação ou acoplamento já observados no código;
- decisão arquitetural autoritativa vigente.

## 5. Aprendizagem incremental

A implementação deve ocorrer em incrementos menores de aprendizado sempre que
isso tornar a construção mais compreensível sem fragmentá-la artificialmente.

Cada incremento deve:

- entregar ou demonstrar um comportamento observável;
- introduzir apenas os conceitos necessários naquele momento;
- manter o código executável ou os testes em estado conhecido;
- ser pequeno o bastante para revisão e compreensão;
- preparar o próximo passo sem implementá-lo antecipadamente.

Ao explicar um incremento, informe apenas o que for útil:

- qual problema foi resolvido;
- qual conceito foi aplicado;
- por que a responsabilidade ficou naquela camada;
- como o teste demonstra o comportamento;
- qual decisão foi adiada e por quê.

Não produza aulas extensas por padrão. Conceitos conhecidos ou já documentados
podem ser mencionados brevemente. Aprofunde a explicação quando houver decisão
nova, ambiguidade relevante ou solicitação do mantenedor.

## 6. Arquitetura-alvo

O projeto adota PHP 8.1, Clean Architecture, DDD, SOLID, injeção de dependências,
PSR-4, PSR-12, PSR-3 e TDD.

A regra essencial da Clean Architecture é a direção das dependências:

```text
Scriptcase
    ↓
Fachada pública
    ↓
Presentation → Application → Domain
                    ↑          ↑
                    └ Infrastructure
                      implementa portas internas
```

- Domain não depende de Presentation, Infrastructure, Scriptcase, Oracle ou
  PNCP.
- Application coordena casos de uso e depende apenas de abstrações internas.
- Presentation adapta entrada e saída e não contém regras de negócio.
- Infrastructure contém detalhes técnicos e implementa contratos necessários
  às camadas internas.
- O Composition Root conhece as implementações concretas e realiza o wiring.
- O Scriptcase conhece somente o autoloader e a fachada pública.

Clean Architecture não exige uma classe para cada etapa do fluxo. Uma camada
pode começar pequena. Novos componentes devem surgir apenas quando houver
responsabilidade real a separar.

### 6.1 Domain

O Domain representa conceitos e regras do planejamento do PCA.

Use entidades, Objetos de Valor, Aggregates, serviços de domínio, políticas,
eventos ou outros padrões táticos somente quando o modelo e as regras atuais os
justificarem.

Não crie um artefato de Domain apenas para reproduzir a estrutura de um livro,
diagrama ou framework.

Priorize:

- linguagem ubíqua;
- invariantes protegidas no ponto correto;
- comportamento próximo dos dados que governa;
- modelos pequenos e expressivos;
- independência de mecanismos externos.

Evite:

- modelar o domínio a partir das tabelas do banco;
- promover detalhes do PNCP a conceitos centrais sem justificativa;
- entidades anêmicas quando existe comportamento real a proteger;
- Domain Services para lógica que cabe naturalmente em uma entidade ou Objeto
  de Valor;
- Aggregates grandes ou fronteiras definidas sem evidência de consistência
  transacional.

### 6.2 Application

Application executa os casos de uso. Ela pode:

- validar dados da fronteira do caso de uso;
- coordenar objetos de Domain;
- chamar portas para capacidades externas;
- controlar o fluxo da operação;
- produzir resultados tipados quando isso simplificar o contrato.

Não crie uma Port para uma operação puramente interna ou para algo que possui
uma única implementação sem necessidade atual de inversão de dependência.

Uma interface é apropriada quando separa uma camada interna de um detalhe
externo, representa um contrato estável do negócio ou permite substituir uma
implementação relevante.

### 6.3 Infrastructure

Infrastructure implementa acesso a Oracle, PNCP, configuração, transações,
logs, contexto hospedeiro e demais detalhes técnicos.

Adapters traduzem representações físicas para contratos internos. Eles não
decidem regras de negócio nem alteram invariantes.

Não introduza Infrastructure antes da sprint autorizada nem crie adapters
especulativos para tecnologias ainda não utilizadas.

### 6.4 Presentation e fronteira pública

Presentation adapta a entrada para o caso de uso e transforma sua saída para o
consumidor.

Controller, Presenter, Request, Response ou ViewModel devem ser criados apenas
quando tiverem responsabilidade própria e melhorarem a fronteira pública.
Para fluxos simples, evite cadeias cerimoniais de objetos que apenas repassam os
mesmos dados sem validação ou transformação.

Nenhuma exceção interna deve atravessar a fachada pública sem tratamento.

## 7. DDD, SOLID e Design Patterns sem overengineering

### 7.1 DDD

Comece pelo entendimento do negócio, pela linguagem ubíqua e pelas regras.
Aplique padrões táticos somente onde eles tornarem o modelo mais correto ou
compreensível.

DDD não significa que toda classe precisa ser Entity, Value Object, Aggregate,
Repository, Policy ou Domain Service.

### 7.2 SOLID

Aplique SOLID de forma contextual:

- separe responsabilidades que realmente mudam por motivos diferentes;
- inverta dependências nas fronteiras relevantes;
- mantenha contratos pequenos quando existirem consumidores reais;
- preserve substituibilidade e clareza.

Não fragmente classes coesas apenas para reduzir artificialmente seu tamanho ou
para afirmar que cada princípio foi utilizado.

### 7.3 Design Patterns

Use um padrão quando houver um problema reconhecível que ele resolva melhor que
uma solução direta.

Antes de introduzir um padrão, verifique:

- o problema já existe no código atual;
- a solução direta geraria acoplamento, duplicação ou ambiguidade relevante;
- o padrão é compreensível para o projeto;
- o benefício supera o custo de novas abstrações.

Nunca implemente padrões somente para fins demonstrativos. Quando houver valor
didático, explique também por que o padrão ainda não é necessário.

## 8. SDD: implementação orientada por especificações

A implementação deve partir das especificações vigentes e rastreáveis.

Antes de codificar um comportamento:

1. identifique a especificação ou regra que o exige;
2. traduza-a para um comportamento observável;
3. represente esse comportamento em teste quando aplicável;
4. implemente somente o necessário para satisfazê-lo;
5. preserve a referência à regra quando prevista pelo projeto.

Não amplie a especificação por inferência. Exemplos, hipóteses e sugestões não
se tornam requisitos automaticamente.

Quando a especificação estiver incompleta, diferencie:

- o que está explicitamente definido;
- o que pode ser inferido com segurança técnica sem alterar o negócio;
- o que precisa de decisão do mantenedor.

## 9. TDD e testes

Para mudanças de comportamento em código, siga:

```text
Red → Green → Refactor
```

1. identifique um comportamento pequeno;
2. escreva um teste que falhe pelo motivo esperado;
3. implemente o mínimo necessário para fazê-lo passar;
4. refatore somente quando houver melhoria concreta;
5. mantenha os testes verdes.

O ciclo deve ser aplicado em passos pequenos. Não escreva antecipadamente uma
grande suíte baseada em uma arquitetura ainda inexistente.

Testes devem demonstrar comportamento, regra, contrato ou caso-limite relevante.
Não crie testes apenas para getters, setters ou detalhes internos triviais.

Quando um teste evidenciar regra de negócio e o projeto adotar essa convenção,
use a referência `@regra RN-*`.

Mocks, stubs e fakes devem ser usados com moderação. Prefira objetos reais e
simples quando não houver custo ou efeito externo relevante.

Tarefas exclusivamente documentais não exigem testes de código. Valide nelas a
consistência, as referências, a precedência e o escopo do diff.

## 10. Regras contra complexidade acidental

Durante o planejamento e a implementação:

- prefira código explícito a mecanismos genéricos prematuros;
- prefira composição simples a hierarquias profundas;
- prefira uma classe concreta clara a uma interface sem fronteira real;
- prefira um fluxo direto a pipelines cerimoniais;
- prefira duplicação pequena e temporária a uma abstração errada;
- extraia abstrações quando o padrão de variação estiver comprovado;
- não crie suporte para cenários futuros sem requisito vigente;
- não adicione dependências externas para resolver problemas simples;
- não faça refatorações adjacentes não solicitadas;
- não altere APIs públicas fora do escopo;
- não antecipe Infrastructure nem sprints futuras;
- mantenha o menor diff capaz de entregar corretamente a sprint.

Quando houver duas soluções corretas, prefira a que tiver:

1. menos conceitos novos;
2. menos arquivos e dependências;
3. fluxo de leitura mais direto;
4. testes mais simples;
5. menor custo de reversão;
6. capacidade suficiente para o requisito atual.

## 11. Estilo de implementação

Priorize:

- simplicidade;
- legibilidade;
- nomes claros na linguagem do domínio;
- baixo acoplamento;
- alta coesão;
- métodos com propósito único e tamanho compreensível;
- tipagem adequada;
- tratamento explícito de erros;
- testabilidade proporcional;
- compatibilidade com PHP 8.1, PSR-4 e PSR-12.

Comentários devem explicar decisões, restrições ou motivos não evidentes. Não
repita em comentários o que o código já expressa claramente.

## 12. Validação

Execute validações proporcionais ao risco e ao escopo:

1. teste afetado durante o ciclo TDD;
2. suíte relacionada;
3. suíte completa, quando viável e relevante;
4. verificação de estilo, tipagem, imports e contratos aplicáveis.

Revise regressões, casos-limite, tratamento de erros e direção das dependências.

Somente declare como executadas as validações realmente realizadas. Informe de
forma objetiva qualquer validação não executada e o motivo.

## 13. Comunicação ao concluir

A resposta final deve ser proporcional à tarefa e informar:

- resultado principal;
- arquivos alterados;
- comportamento entregue;
- testes e validações executados;
- limitações, riscos ou pendências reais;
- conceitos relevantes aprendidos, quando houver valor pedagógico.

Quando uma decisão importante tiver sido tomada, classifique-a apenas se isso
ajudar a compreensão:

- exigência documental;
- decisão arquitetural;
- decisão de modelagem;
- convenção de implementação.

Não repita explicações óbvias, não descreva raciocínio interno passo a passo e
não produza um relatório arquitetural extenso para tarefas pequenas.

Seja conciso, técnico, transparente e orientado ao aprendizado.
