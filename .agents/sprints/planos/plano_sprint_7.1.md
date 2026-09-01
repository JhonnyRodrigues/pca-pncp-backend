# Sprint 7.1 — Controller e entrada tipada

**Status:** Concluída em 01/09/2026 após quatro microciclos autorizados e a
revisão do Microciclo 3.

## Resumo

Iniciar a Presentation com uma entrada imutável baseada em tipos primitivos e
um Controller fino. Ele materializará `Cnpj` e `AnoExecucao`, criará
`PublicarPcaRequest` e acionará o UseCase já montado por meio de um contrato da
Application.

A aprovação deste plano autoriza somente o Microciclo 1. Ao final de cada
microciclo serão apresentados diff e testes executados, com pausa obrigatória
para nova autorização.

## Interfaces e comportamento

- Criar `EntradaPublicarPca` em Presentation com `string $cnpj` e
  `int $anoExecucao`.
- Criar `PublicadorPca` na Application com a assinatura vigente
  `executar(PublicarPcaRequest, CorrelacaoExecucao): PublicarPcaResponse`.
- Fazer `PublicarPcaUseCase` implementar esse contrato sem alterar seu
  comportamento.
- Criar `PublicarPcaController`, injetando `PublicadorPca`, para construir os
  Value Objects e o Request, preservar a correlação e devolver a Response.
- Entradas inválidas não acionam o UseCase e suas exceções propagam até a futura
  fronteira global. A Sprint 7.3 deverá convertê-las em `SOLICITACAO_INVALIDA`:
  - `InvalidArgumentException` do CNPJ: `CNPJ_INVALIDO`;
  - `AnoExecucaoInvalidoException`: `ANO_EXECUCAO_INVALIDO`.
- O Controller não fará wiring, não acessará macros, configuração, credenciais,
  adapters ou Scriptcase e não capturará falhas inesperadas.

## Revisão autorizada do Microciclo 3

Em 31/08/2026, o mantenedor revisou somente o tratamento das entradas
inválidas: o Controller continua responsável por materializar `Cnpj` e
`AnoExecucao`, mas não converte suas exceções em Response nesta sprint. As
exceções propagam até a futura fronteira global, que deverá produzir
`SOLICITACAO_INVALIDA` com `CNPJ_INVALIDO` ou `ANO_EXECUCAO_INVALIDO` na Sprint
7.3. `PublicarPcaRequest` preserva seu contrato anterior de receber Value
Objects válidos.

## Microciclos

1. **Entrada tipada e contrato executável**
   - Registrar este plano aprovado.
   - Em TDD, criar `EntradaPublicarPca`.
   - Criar `PublicadorPca` e fazer o UseCase implementá-lo.
   - Executar os testes afetados de Presentation e Application.
   - Pausar antes do Microciclo 2.

2. **Caminho válido do Controller**
   - Em TDD, criar o Controller e demonstrar com um spy do contrato a
     normalização do CNPJ, a preservação do ano e da correlação, a construção
     correta do Request e a devolução da mesma Response.
   - Pausar antes do Microciclo 3.

3. **Entradas inválidas**
   - Testar separadamente CNPJ e ano inválidos.
   - Propagar as exceções específicas sem acionar o UseCase; a conversão para
     `SOLICITACAO_INVALIDA` pertence à futura fachada.
   - Quando ambos forem inválidos, validar CNPJ primeiro.
   - Pausar antes do Microciclo 4.

4. **Gate e encerramento**
   - Executar testes de Presentation, Application e Composition; suíte padrão;
     autoload PSR-4 estrito; lint; auditoria das dependências; e
     `git diff --check`.
   - Não executar probes, preflights, grupos live ou integrações externas.
   - Atualizar a documentação operacional e de apoio somente com evidências
     obtidas, preservando todas as alterações locais e sem criar commit.

## Critérios de aceitação

- Entrada válida chega ao UseCase como `PublicarPcaRequest` contendo somente
  `Cnpj` e `AnoExecucao`.
- Entrada inválida propaga a exceção específica sem iniciar processamento
  externo.
- Presentation depende apenas de contratos/casos de uso internos e de Value
  Objects do Domain.
- Application e Domain não passam a depender de Presentation.
- Presenter, ViewModels, fachada, geração da correlação, perfil de implantação,
  Composition Root final e integração Scriptcase permanecem adiados.
- Todas as validações previstas ficam verdes.

## Premissas

- A correlação será fornecida ao Controller; sua geração concreta permanece na
  Fase 8.
- Nesta sprint o Controller devolve `PublicarPcaResponse`; o Presenter será
  incorporado na Sprint 7.2.
- A conversão centralizada de exceções esperadas ou inesperadas pertence à
  fachada da Sprint 7.3.
- Não serão adicionadas dependências externas nem realizadas chamadas reais ao
  Oracle, PNCP ou Scriptcase.

## Evidências de encerramento

- Presentation: 7 testes e 18 asserções.
- Application: 237 testes e 978 asserções.
- Composition: 1 teste e 1 asserção.
- Integration: 3 testes e 70 asserções.
- Suíte padrão: 818 testes e 3.017 asserções.
- Autoload PSR-4 estrito: 1.746 classes.
- Lint: 278 arquivos PHP.
- Auditoria de dependências e `git diff --check`: aprovados.
- Nenhum preflight, grupo `pncp-live`, probe ou acesso externo foi executado.
- A Sprint 7.2 — Presenter e ViewModels é a próxima etapa e permanece não
  autorizada.
