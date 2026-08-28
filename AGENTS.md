# AGENTS.md

## 1. Finalidade

Este arquivo define as instruções operacionais permanentes para agentes que
atuam neste repositório. Ele orienta como compreender, planejar, implementar,
validar e comunicar uma tarefa. Regras de negócio, decisões de integração,
modelo de domínio e etapas do roadmap pertencem aos documentos autoritativos.

O projeto é um backend em PHP 8.1 para publicação e sincronização do Plano de
Contratações Anual (PCA) com o PNCP, inicialmente consumido pelo Scriptcase.

O projeto também tem finalidade didática. As explicações devem ajudar o
mantenedor a compreender as decisões adotadas, sem transformar cada tarefa em
uma aula ou justificar complexidade desnecessária.

> Implemente somente o escopo autorizado, da forma mais simples, clara,
> testável e reversível que preserve as regras e fronteiras necessárias.

DDD, Clean Architecture, SOLID, Design Patterns, SDD e TDD são meios para
reduzir riscos e organizar responsabilidades, não metas isoladas.

## 2. Fontes e autoridade

Leia somente o necessário para a tarefa. Use como pontos de entrada:

- `README.md`: visão e finalidade do projeto;
- `AGENTS.md`: estas instruções operacionais;
- `.agents/README.md`: interpretação dos artefatos de agentes;
- `docs/README.md`: taxonomia e autoridade documental;
- `docs/vigentes/operacional/estado_atual.md`, quando existir: progresso,
  gates, pendências e próxima sprint autorizada;
- roadmap vigente e documentos autoritativos relacionados à tarefa.

O pedido atual do mantenedor define o objetivo e o escopo. Os documentos
autoritativos definem as regras e decisões de cada assunto. Se houver conflito,
lacuna material ou fontes incompatíveis, não combine premissas: exponha o ponto
e solicite decisão.

Não trate código existente, documentos históricos, investigações, sínteses ou
prompts antigos como fontes normativas. Consulte histórico apenas por busca
direcionada quando precisar recuperar a origem de uma decisão.

Nunca invente regra de negócio, validação, valor padrão ou decisão de integração
para preencher uma lacuna.

## 3. Preparação e escopo

Antes de alterar arquivos:

1. inspecione o estado do Git e preserve alterações locais existentes;
2. confirme a sprint, o objetivo e os limites autorizados;
3. leia os arquivos diretamente envolvidos e suas especificações;
4. pesquise referências, consumidores e contratos afetados;
5. defina testes e validações proporcionais ao risco.

Não investigue nem refatore áreas adjacentes sem necessidade concreta. Não
avance sprints, antecipe Infrastructure, altere APIs públicas, dependências ou
arquitetura fora do escopo sem autorização.

Nunca crie commit sem solicitação explícita.

## 4. Planejamento e decisões

Produza um plano antes de mudanças arquiteturais, tarefas com vários componentes
ou quando o modo de planejamento estiver habilitado. Para correções pequenas e
localizadas, um plano formal pode ser dispensado.

O plano deve informar apenas:

- comportamento a entregar e fontes aplicáveis;
- menor conjunto provável de componentes;
- incrementos de implementação;
- testes e validações;
- riscos, lacunas e itens deliberadamente adiados.

Todo novo artefato deve responder a uma necessidade atual, ter consumidor
identificável e resolver um problema que uma solução mais direta não resolveria
adequadamente. Caso contrário, não o crie agora.

Quando houver duas soluções corretas, prefira a que introduzir menos conceitos,
arquivos e dependências, tiver fluxo mais direto, testes mais simples e menor
custo de reversão.

Complexidade adicional só se justifica por necessidade atual, como invariante
do domínio, fronteira externa, transação, segurança, auditoria, confiabilidade
ou acoplamento já observado.

Depois de aprovado o escopo, prossiga sem solicitar confirmação para cada ação trivial. Quando o plano aprovado estabelecer microciclos pedagógicos ou pontos de validação, pause obrigatoriamente ao final de cada um. Fora desses pontos, solicite nova decisão somente diante de escolha material, risco relevante, lacuna ou ampliação do escopo.

## 5. Arquitetura essencial

O projeto adota PHP 8.1, Clean Architecture, DDD, injeção de dependências,
PSR-4, PSR-12, PSR-3 e TDD.

Direção das dependências:

```text
Scriptcase -> Fachada pública -> Presentation -> Application -> Domain
Infrastructure -> implementa Ports definidas nas camadas internas
```

Regras obrigatórias:

- Domain não depende de Presentation, Infrastructure, Scriptcase, Oracle ou
  PNCP;
- Application coordena casos de uso e depende de contratos internos;
- Presentation adapta entrada e saída, sem regras de negócio;
- Infrastructure implementa capacidades externas e traduz representações
  físicas para contratos internos;
- Composition Root conhece implementações concretas e realiza o wiring;
- Scriptcase conhece apenas o autoloader e a fachada pública;
- exceções internas não atravessam a fachada pública sem tratamento;
- tabelas, payloads e formatos de transporte não definem o modelo de domínio.

Use entidades, Objetos de Valor, Aggregates, serviços, Ports, DTOs e padrões
somente quando houver responsabilidade, invariante ou fronteira real. Uma
camada pode começar pequena; Clean Architecture não exige uma classe por etapa.

Consulte a documentação arquitetural para detalhes. Não replique o desenho
completo do sistema neste arquivo.

## 6. Implementação

- mantenha o menor diff capaz de entregar corretamente o comportamento;
- prefira código explícito a mecanismos genéricos prematuros;
- prefira composição simples a hierarquias profundas;
- preserve a linguagem do domínio e as convenções existentes;
- mantenha alta coesão, baixo acoplamento e tratamento explícito de erros;
- preserve compatibilidade com PHP 8.1, PSR-4 e PSR-12;
- não adicione dependências externas sem necessidade demonstrada;
- não crie extensibilidade para cenários sem requisito vigente;
- não extraia abstrações antes de existir uma fronteira ou variação comprovada;
- aceite pequena duplicação temporária quando a abstração correta ainda não
  estiver evidente.

Comentários devem explicar decisões, restrições ou motivos não evidentes, e não
repetir o código.

Em tarefas complexas, trabalhe em incrementos pequenos que demonstrem
comportamento observável e mantenham código e testes em estado conhecido. Ao
explicar uma decisão nova, apresente em linguagem simples o problema, a solução,
o conceito aplicado e o motivo de pertencer àquela camada. Na primeira utilização de um jargão, apresente primeiro a tradução simples e depois o termo técnico.

## 7. Especificações e testes

Implemente a partir de especificações vigentes e rastreáveis:

1. identifique a regra, decisão ou contrato aplicável;
2. traduza-o em comportamento observável;
3. escreva um teste que falhe pelo motivo esperado;
4. implemente o mínimo para fazê-lo passar;
5. refatore somente diante de melhoria concreta;
6. mantenha os testes verdes.

Use `Red -> Green -> Refactor` em passos pequenos. Não antecipe uma grande suíte
baseada em arquitetura ainda inexistente.

Teste comportamentos, regras, contratos e casos-limite relevantes. Evite testes
de getters, setters, reflection ou detalhes internos triviais. Use mocks, stubs
e fakes com moderação; prefira objetos reais simples quando não houver efeito
externo ou custo relevante.

Quando aplicável à convenção do projeto, testes de regra de negócio devem
referenciar `@regra RN-*`.

Tarefas exclusivamente documentais não exigem testes de código, mas exigem
validação de consistência, referências, precedência e escopo do diff.

## 8. Validação

Execute, conforme o risco e a abrangência:

1. teste afetado durante o ciclo de desenvolvimento;
2. suíte diretamente relacionada;
3. suíte completa, quando viável e relevante;
4. lint, imports, autoload, tipagem e contratos aplicáveis;
5. inspeção do diff, regressões, casos-limite e direção das dependências.

Declare somente verificações realmente executadas. Informe objetivamente o que
não pôde ser validado e o motivo.

## 9. Comunicação

Durante tarefas longas, comunique marcos relevantes sem narrar cada ação. Ao
concluir, informe de forma proporcional:

- resultado principal e comportamento entregue;
- arquivos alterados;
- testes e validações executados;
- limitações, riscos ou pendências reais;
- decisão ou conceito novo, quando houver valor pedagógico.

Seja conciso, técnico, direto e transparente. Não exponha raciocínio interno
passo a passo nem produza relatório arquitetural extenso para uma tarefa pequena.
