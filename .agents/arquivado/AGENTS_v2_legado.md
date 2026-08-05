# AGENTS.md

## 1. Finalidade e autoridade

Este arquivo é a única instrução operacional permanente para agentes que
trabalham neste repositório. Ele orienta a execução das tarefas, mas não cria
regras de negócio, conceitos de domínio, decisões de integração nem etapas de
planejamento.

O projeto implementa um backend em PHP 8.1 para a publicação e sincronização do
Plano de Contratações Anual (PCA) com o Portal Nacional de Contratações
Públicas (PNCP). O backend será consumido inicialmente, em processo, por uma
aplicação Scriptcase.

### Finalidade didática e feedback adaptativo

Além de entregar funcionalidades, este projeto serve como ambiente de estudo de
regras de negócio, modelagem de domínio, DDD, Clean Architecture, TDD,
integração e refatoração.

Quando uma tarefa envolver modelagem de domínio, regras de negócio, decisões
arquiteturais ou de integração, TDD, refatorações relevantes ou componentes
importantes, o agente deve tratar a explicação das decisões como parte do
resultado.

A profundidade deve ser proporcional à complexidade e ao valor pedagógico da
alteração. Quando aplicável, o feedback deve explicar:
- qual problema estava sendo resolvido;
- quais conceitos do negócio e elementos da linguagem ubíqua participaram;
- quais documentos autoritativos ou de apoio foram consultados;
- quais regras de negócio e decisões de integração influenciaram a solução;
- por que cada responsabilidade relevante pertence à camada escolhida;
- quais princípios de DDD, Clean Architecture ou TDD orientaram a decisão;
- quais alternativas relevantes foram consideradas e por que foram
  descartadas;
- quais trade-offs, limitações ou riscos foram aceitos;
- como os testes demonstram o comportamento e, quando executado, como o ciclo
  Red → Green → Refactor contribuiu para a solução;
- quais aprendizados arquiteturais ou de modelagem surgiram durante a tarefa.

Esse feedback é adaptativo:
- não existe formato obrigatório de relatório;
- tarefas pequenas podem receber explicações breves;
- tarefas documentais devem destacar somente decisões e aprendizados
  relevantes;
- justificativas de Domain não devem ser forçadas quando o Domain não
  participou da alteração;
- informações já evidentes não precisam ser repetidas apenas para preencher
  seções;
- somente testes e etapas efetivamente executados podem ser apresentados como
  evidência.

As explicações devem apresentar decisões, evidências e consequências de forma
compreensível, sem exigir a exposição de raciocínio interno passo a passo.

A finalidade didática não altera a governança documental. Explicações do agente
não criam regras ou decisões e não prevalecem sobre as fontes autoritativas. É
necessário distinguir fatos aprovados, inferências, alternativas e lacunas,
sempre respeitando a classificação e a precedência definidas em
`docs/README.md` e `.agents/README.md`.

#### Aprendizagem incremental e transparência arquitetural

A implementação deste projeto ocorre de forma assistida. O agente pode
manipular o workspace, criar e alterar arquivos, enquanto o mantenedor
acompanha, revisa, compreende e aprova as mudanças realizadas.

Por esse motivo, a transferência de conhecimento é considerada parte do
resultado esperado das atividades arquiteturais, de modelagem e de
implementação.

Sempre que a tarefa envolver:

- modelagem de domínio;
- arquitetura;
- contratos;
- abstrações;
- padrões de projeto;
- novas estruturas de diretórios;
- refatorações relevantes;
- componentes centrais da solução;

o agente deve buscar tornar o processo de construção compreensível e
auditável, e não apenas apresentar o estado final da implementação.

Ao apresentar propostas ou alterações relevantes, o agente deve explicar,
quando aplicável:

- qual problema está sendo resolvido;
- por que o artefato passou a existir;
- quem será seu consumidor;
- por que a responsabilidade pertence à camada escolhida;
- quais alternativas razoáveis foram consideradas;
- quais trade-offs motivaram a decisão adotada;
- qual impacto a decisão pode ter na manutenção futura.

O agente deve distinguir explicitamente entre:

- exigências documentais;
- princípios arquiteturais obrigatórios;
- decisões de modelagem;
- convenções de organização adotadas.

Quando existirem alternativas igualmente compatíveis com DDD, Clean
Architecture e a documentação vigente, o agente deve deixar claro que se trata
de uma decisão de modelagem e não de uma obrigação arquitetural.

Ao introduzir conceitos menos familiares, o agente deve explicar:

- o que são;
- qual problema procuram resolver;
- de qual literatura, padrão ou escola arquitetural derivam;
- quais nomenclaturas ou abordagens alternativas são comuns;
- por que a alternativa escolhida foi considerada adequada ao projeto.

Exemplos incluem:

- Aggregate;
- Entity;
- Value Object;
- Policy;
- Domain Service;
- Fact;
- Event;
- Port;
- Contract;
- Adapter;
- Repository;
- Use Case;
- Unit of Work;
- Composition Root.

O objetivo é permitir que o mantenedor acompanhe a evolução da solução,
compreenda as decisões tomadas e desenvolva autonomia para manter e evoluir a
arquitetura ao longo do tempo.

#### Transparência arquitetural e transferência de conhecimento

Ao apresentar propostas arquiteturais, estruturas de diretórios,
abstrações, contratos, nomenclaturas ou padrões de modelagem,
o agente deve distinguir explicitamente:
- exigência documental;
- decisão arquitetural;
- decisão de modelagem;
- preferência ou convenção adotada.

O agente deve distinguir explicitamente entre:
- exigências documentais;
- princípios arquiteturais obrigatórios;
- decisões de modelagem adotadas;
- convenções de organização escolhidas.

Quando existirem alternativas igualmente compatíveis com DDD,
Clean Architecture e a documentação vigente, o agente deve explicar
que se trata de uma escolha de modelagem, justificando a alternativa
recomendada para este projeto.

Sempre que introduzir um conceito menos familiar, o agente deve explicar:
- o que é;
- qual problema procura resolver;
- de qual escola ou literatura arquitetural se origina;
- quais alternativas comuns existem;
- por que a solução escolhida foi considerada mais adequada ao projeto.

Exemplos incluem, mas não se limitam a:
- Policy;
- Domain Service;
- Fact;
- Event;
- Port;
- Adapter;
- Contract;
- Repository;
- Aggregate;
- Value Object;
- Use Case;
- Unit of Work;
- Composition Root.

Quando existirem alternativas razoáveis, o agente deve comparar
vantagens, desvantagens e trade-offs antes de recomendar uma delas.

O objetivo não é apenas justificar a solução proposta, mas permitir
a transferência gradual de conhecimento para que o mantenedor seja
capaz de compreender, auditar e evoluir a arquitetura sem depender
continuamente do agente.

## 2. Antes de atuar

Leia, nesta ordem:
1. `README.md`, para compreender o projeto e sua finalidade;
2. `.agents/AGENTS.md`, para conhecer estas instruções;
3. `.agents/README.md`, para interpretar os artefatos de agentes;
4. `docs/README.md`, para conhecer a taxonomia documental;
5. os checkpoints aplicáveis à tarefa, respeitando sua precedência;
6. os documentos vigentes indicados pelo índice documental.

Documentos históricos, investigações, sínteses e prompts antigos não são
fontes normativas ou instruções operacionais. Eles só podem ser usados conforme
a classificação definida nos respectivos índices.

Antes de alterar qualquer arquivo:
- inspecione o estado atual do Git;
- leia os arquivos relacionados;
- identifique alterações locais existentes e preserve-as;
- confirme o escopo autorizado;
- identifique a validação proporcional à tarefa.

## 3. Autoridade por assunto

Respeite a autoridade documental definida em `docs/README.md`:

| Assunto | Fonte |
|---|---|
| Visão, onboarding e finalidade didática | `README.md` |
| Regras de negócio | catálogo autoritativo de regras |
| Conceitos, identidades, relações e invariantes | modelo de domínio autoritativo |
| Integração, portas, persistência técnica e operação | decisões de integração autoritativas |
| Colaboração dinâmica | fluxo de implementação vigente, quando criado |
| Ordem de construção | roadmap vigente, quando criado |
| Fronteiras e vocabulário arquitetural | documentos de apoio, sem autoridade normativa |
| Continuidade do planejamento | checkpoints aplicáveis, em ordem incremental |

Quando houver divergência, não combine premissas incompatíveis. Aplique a fonte
autoritativa responsável pelo assunto, respeite a revisão incremental dos
checkpoints e informe a inconsistência.

Nunca invente regras, validações, defaults ou decisões para preencher lacunas.
Se uma decisão indispensável não puder ser obtida das fontes vigentes, solicite
esclarecimento antes de implementar o comportamento afetado.

## 4. Arquitetura-alvo

O projeto adota PHP 8.1, Clean Architecture, Domain-Driven Design, SOLID,
injeção de dependências, PSR-4, PSR-12, PSR-3 e TDD.

A direção principal das dependências é:
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
  mecanismos do PNCP.
- Presentation depende da Application e adapta entrada e saída.
- Application depende do Domain e coordena os casos de uso.
- Infrastructure implementa portas pertencentes à camada interna que necessita
  da capacidade.
- O Composition Root pertence ao backend, conhece as implementações concretas
  e realiza o wiring manual por construtores.
- O Scriptcase conhece somente o autoloader e a fachada pública do backend.

### 4.1 Domain

O Domain representa o negócio de planejamento do PCA. Ele contém Aggregate,
entidades, Objetos de Valor, invariantes, fatos imutáveis, exceções e políticas
puras.

O `PlanoRepository` pertence ao Domain porque expressa reconstrução e
confirmação orientadas ao Aggregate. O Domain pode decidir intenções a partir
de fatos externos tipados, mas não conhece como eles são obtidos, persistidos
ou transportados.

### 4.2 Application

Application valida sua fronteira, orquestra casos de uso, agrupa unidades
externas, obtém fatos por portas, fornece dados às políticas do Domain,
coordena intenções, confirmações e auditoria e produz resultados tipados.

Pertencem à Application os contratos necessários para capacidades operacionais,
como:
- autenticação, leitura e escrita PNCP;
- estratégia corrente e adotada;
- unidade de trabalho e exclusividade;
- contexto do usuário;
- persistência de auditoria;
- estado operacional cross-Plan.

### 4.3 Infrastructure

Infrastructure implementa as portas internas por adapters concretos de
persistência, transporte, configuração, transação, contexto hospedeiro e
observabilidade.

Adapters traduzem contratos tipados para representações físicas. Eles não
escolhem ações de negócio, não alteram invariantes e não decidem convergência.

### 4.4 Presentation e fronteira pública

Presentation contém a fachada, o Controller, o Presenter e os ViewModels. Não
contém regras de negócio, não acessa adapters diretamente e não realiza wiring.

O Controller adapta a entrada para o caso de uso. O Presenter transforma a
saída em ViewModel estruturado. Nenhuma exceção deve atravessar a fachada
pública.

## 5. Regras de modelagem

- Use a linguagem ubíqua do planejamento PCA.
- Não promova mecanismos ou referências PNCP a conceitos constitutivos do
  Aggregate.
- Não modele o domínio a partir da estrutura física do banco.
- Proteja invariantes no Domain.
- Evite entidades anêmicas, serviços sem justificativa e abstrações prematuras.
- Use Objetos de Valor quando o conceito for imutável e possuir igualdade por
  valor.
- Separe identidade do Plano, fotografia vigente, confirmação versionada e
  estado operacional externo.
- Não coloque regras de negócio em Controller, Presenter, implementação de
  Repository, Adapter ou Infrastructure.
- Não faça o UseCase acessar Oracle ou outro mecanismo externo diretamente;
  use a Port apropriada.
- Mapeamentos e políticas de integração devem seguir as decisões autoritativas.
  A Application expressa intenções tipadas e a Infrastructure realiza a
  adaptação física.

Ao propor alteração relevante no Domain, explique:
- qual conceito está sendo representado;
- quais regras e invariantes estão envolvidas;
- por que a responsabilidade pertence ao Domain;
- quais fatos ou mecanismos permaneceram fora dele.

## 6. Planejamento e escopo

Antes de uma tarefa complexa, ambígua, multiarquivo ou arquitetural:
- identifique objetivo, contexto, restrições e critérios de conclusão;
- examine padrões, contratos e testes existentes;
- consulte as fontes documentais aplicáveis;
- prepare um plano quando necessário;
- registre premissas, riscos e estratégia de validação.

Quando a tarefa envolver arquitetura, modelagem ou criação de novos
componentes estruturantes, o agente deve avaliar se a implementação pode ser
executada em incrementos menores de aprendizado.

Sempre que isso não conflitar com o objetivo da tarefa, o agente deve preferir
entregas progressivas e revisáveis em vez de alterações extensas realizadas de
uma única vez.

Cada incremento deve buscar deixar claro:
- qual capacidade está sendo construída;
- qual problema está sendo resolvido;
- quais artefatos surgiram;
- por que eles surgiram naquele momento;
- como se conectam à arquitetura do sistema.

O objetivo não é fragmentar artificialmente a implementação, mas tornar o
processo de construção observável para fins de revisão, auditoria e
aprendizagem.

Quando existir roadmap vigente, respeite a próxima etapa autorizada. Enquanto
ele não existir, siga o plano aprovado da tarefa e os checkpoints aplicáveis.

Em qualquer caso:
- implemente somente o escopo solicitado;
- não antecipe etapas futuras;
- não inicie Infrastructure sem autorização e sem cumprir os gates;
- não introduza dependências externas sem justificativa;
- não altere APIs públicas ou arquivos fora do escopo;
- não faça refatorações adjacentes não solicitadas;
- prefira alterações pequenas, coesas e reversíveis.

Pergunte somente quando a resposta não puder ser obtida do repositório e uma
premissa segura não permitir o avanço. Decisões que mudariam materialmente o
comportamento exigem confirmação.

## 7. TDD e validação

Para mudanças de comportamento em código, siga:

```text
Red → Green → Refactor
```

1. identifique o comportamento e as regras relacionadas;
2. escreva o teste que demonstra o comportamento;
3. execute-o e confirme a falha esperada;
4. implemente o mínimo necessário;
5. execute novamente;
6. refatore mantendo os testes verdes.

Testes devem demonstrar comportamentos e contratos relevantes, não existir
apenas para corresponder a uma classe. Quando um teste evidenciar regra de
negócio, use a referência `@regra RN-*`.

Tarefas exclusivamente documentais não exigem testes de código. Nelas, valide
links, referências, precedência, consistência semântica e escopo do diff.

Sempre que aplicável:
- execute primeiro as validações afetadas e depois a suíte relacionada;
- verifique PSR-12, tipagem, imports e contratos;
- revise regressões, casos-limite, tratamento de erros e acoplamento;
- informe explicitamente qualquer validação não executada e o motivo.

## 8. Estilo de implementação

Priorize:
- simplicidade;
- baixo acoplamento;
- alta coesão;
- nomes claros na linguagem do domínio;
- métodos pequenos;
- legibilidade;
- testabilidade.

Comentários devem explicar decisões ou restrições relevantes, não repetir o
código.

## 9. Comunicação ao concluir

Apresente a entrega de forma proporcional à tarefa, informando:
- resultado principal;
- arquivos alterados;
- regras ou decisões relevantes, quando aplicáveis;
- validações executadas e seus resultados;
- limitações, riscos ou pendências reais.- classificação das principais decisões tomadas:
    - exigência documental;
    - decisão arquitetural;
    - decisão de modelagem;
    - convenção de implementação.

Não force justificativas sobre classes, Domain ou testes quando a tarefa não
envolver esses elementos. Quando houver valor pedagógico, aplique a finalidade
didática adaptativa definida neste documento. Seja conciso, técnico e objetivo.
