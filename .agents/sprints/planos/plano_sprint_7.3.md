# Sprint 7.3 — Fachada e fronteira de exceções

**Status:** Concluída em 01/09/2026 após quatro microciclos autorizados.

## Resumo

Criar uma fachada tipada sobre o Controller que sempre devolva
`PublicarPcaViewModel`, preserve a correlação e impeça exceções ou detalhes
internos de atravessarem a fronteira.

Ao final de cada microciclo serão apresentados diff e validações executadas,
com pausa obrigatória antes do microciclo seguinte.

## Interfaces e comportamento

- Criar `FachadaPublicarPca::executar(EntradaPublicarPca,
  CorrelacaoExecucao): PublicarPcaViewModel` com Controller e Presenter
  injetados.
- Usar `EntradaPublicarPcaInvalidaException` somente entre Controller e
  fachada, transportando uma `FalhaSegura`.
- Classificar CNPJ inválido como `CNPJ_INVALIDO` / `CNPJ inválido.` e ano
  inválido como `ANO_EXECUCAO_INVALIDO` / `Ano de execução inválido.`; CNPJ tem
  precedência.
- Converter entrada inválida em `SOLICITACAO_INVALIDA` sem chamar o UseCase.
- Converter qualquer outro `Throwable` em `INTERROMPIDA`, com código
  `FALHA_INESPERADA`, mensagem `Não foi possível concluir a publicação do
  PCA.`, contagens zeradas e correlação exata.
- Não transportar mensagem original, classe, stack, SQL, payload, credenciais
  ou outros detalhes da exceção.

## Microciclos

1. **Classificação segura da entrada**
   - Registrar este plano aprovado.
   - Em TDD, criar a exceção interna e ajustar somente a materialização de CNPJ
     e ano no Controller.
   - Demonstrar códigos e mensagens fixos, precedência do CNPJ, ausência de
     chamada ao UseCase e propagação inalterada de exceções posteriores.
   - Executar testes focados e pausar.

2. **Fachada e caminhos esperados**
   - Em TDD, criar a fachada tipada.
   - Preservar o ViewModel do caminho válido e converter as duas falhas de
     entrada em `SOLICITACAO_INVALIDA`.
   - Executar testes focados e pausar.

3. **Fronteira de falhas inesperadas**
   - Em TDD, capturar os demais `Throwable` e produzir `INTERROMPIDA` segura.
   - Cobrir `RuntimeException`, `InvalidArgumentException` posterior à entrada
     e `Error`, sem resultados fabricados nem detalhes internos.
   - Executar a regressão da Presentation e pausar.

4. **Gate e encerramento**
   - Executar Presentation, Application, Composition, Integration, suíte
     padrão, autoload PSR-4 estrito, lint, auditoria de dependências e
     `git diff --check`.
   - Atualizar documentação operacional e de apoio somente com evidências
     obtidas, sem integrações externas ou commit.

## Critérios e adiamentos

- Nenhuma exceção atravessa a futura fachada.
- A fachada não realiza wiring, renderização, serialização ou logging.
- Assinatura Scriptcase, Composition Root, perfil, geração da correlação e
  eventos técnicos permanecem na Sprint 8.1 ou posteriores.
- A suíte transversal da Presentation permanece na Sprint 7.4.
- As alterações locais da Sprint 7.2 serão preservadas.

## Evidências do Microciclo 1

- O Red focado falhou nos três cenários porque CNPJ e ano ainda atravessavam
  com suas exceções originais.
- O teste focado do Controller aprovou 6 testes e 23 asserções.
- A suíte da Presentation aprovou 15 testes e 87 asserções.
- CNPJ e ano inválidos agora transportam somente a `FalhaSegura` fixa pelo
  sinal interno, preservando a precedência do CNPJ e sem chamar o UseCase.
- `InvalidArgumentException` e `RuntimeException` posteriores à materialização
  continuam inalteradas para tratamento exclusivo pela futura fachada.
- Lint, direção das dependências, whitespace e `git diff --check` foram
  aprovados.
- Nenhuma fachada, integração externa ou commit foi criado neste microciclo.

## Evidências do Microciclo 2

- O Red focado falhou nos três cenários exclusivamente pela ausência da
  fachada.
- O teste focado da fachada aprovou 3 testes e 27 asserções.
- A suíte da Presentation aprovou 18 testes e 114 asserções.
- O caminho válido preserva o ViewModel produzido pelo Controller; CNPJ e ano
  inválidos produzem `SOLICITACAO_INVALIDA`, contagens zeradas e correlação
  textual exata sem chamar o UseCase.
- A fachada captura somente `EntradaPublicarPcaInvalidaException`; a fronteira
  para falhas inesperadas permanece reservada ao Microciclo 3.
- Lint, direção das dependências, whitespace e `git diff --check` foram
  aprovados.
- A fachada não contém wiring, Infrastructure, Scriptcase, serialização ou
  logging, e nenhuma integração externa ou commit foi executado.

## Evidências do Microciclo 3

- O Red focado mostrou `RuntimeException`, `InvalidArgumentException`
  posterior à entrada e `Error` atravessando a fachada com detalhes internos.
- O teste focado da fachada aprovou 7 testes e 61 asserções.
- A suíte da Presentation aprovou 22 testes e 148 asserções.
- Os três tipos inesperados agora produzem `INTERROMPIDA`, código
  `FALHA_INESPERADA`, mensagem pública fixa, contagens zeradas e correlação
  textual exata, sem resultados fabricados.
- Uma Response `INTERROMPIDA` segura já formada pela Application preserva sua
  classificação, falha e contagens sem ser convertida em falha inesperada.
- A inspeção confirmou que a fachada não consulta nem transporta mensagem,
  tipo, stack, SQL, payload ou credenciais da exceção original e não registra
  eventos técnicos.
- Lint, whitespace e `git diff --check` foram aprovados; nenhuma integração
  externa ou commit foi executado.

## Evidências do Microciclo 4

- Presentation aprovou 22 testes e 148 asserções; Application aprovou 237
  testes e 978 asserções; Composition aprovou 1 teste e 1 asserção; Integration
  aprovou 3 testes e 70 asserções.
- A suíte padrão aprovou 833 testes e 3.147 asserções.
- O autoload PSR-4 estrito gerou 1.753 classes, e o lint global aprovou 285
  arquivos PHP.
- As auditorias confirmaram a direção das dependências e a ausência de wiring,
  logging, serialização ou detalhes internos na fachada.
- A documentação operacional e de apoio foi atualizada somente com as
  evidências obtidas.
- Nenhum preflight, grupo `pncp-live`, probe, acesso externo ou commit foi
  executado.
- A Sprint 7.4 — Suíte da Presentation é a próxima etapa e permanece não
  autorizada.
