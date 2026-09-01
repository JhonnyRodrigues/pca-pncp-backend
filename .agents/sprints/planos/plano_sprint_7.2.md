# Sprint 7.2 — Presenter e ViewModels

**Status:** Concluída em 01/09/2026 após quatro microciclos autorizados.

## Resumo

Projetar `PublicarPcaResponse` em dois ViewModels imutáveis e seguros. O
Presenter realiza todas as conversões; os ViewModels apenas armazenam e
devolvem dados previamente projetados.

Ao final de cada microciclo serão apresentados o diff e as validações
executadas, com pausa obrigatória antes do microciclo seguinte.

## Interfaces

- `PublicarPcaPresenter::apresentar(PublicarPcaResponse): PublicarPcaViewModel`.
- `PublicarPcaViewModel` representa situação, resultados unitários, falha
  global achatada, contagens e correlação textual exata.
- `ResultadoUnidadeViewModel` representa ID da UO opcional, modo, ano, UASG,
  situação, decisão e sequencial opcionais, contagens e falha unitária
  achatada.
- Getters devolvem somente strings, inteiros, `null` ou ViewModels previamente
  armazenados. Conversões pertencem exclusivamente ao Presenter.
- Código e mensagem de falha são ambos nulos ou provêm da mesma
  `FalhaSegura`.
- Não serão criados interfaces, builders, factories genéricas, coleções
  personalizadas ou Value Objects de Presentation.

## Microciclos

1. **Contrato completo mínimo**
   - Registrar este plano aprovado.
   - Em TDD, criar os dois ViewModels e o Presenter.
   - Cobrir `CONCLUIDA` legitimamente vazia, estado com falha global e ao menos
     um resultado unitário representativo.
   - Preservar contagens, ordem e correlação textual sem normalização.
   - Não alterar o Controller.
   - Executar testes focados, lint dos arquivos afetados e
     `git diff --check`; apresentar evidências e pausar.

2. **Matriz unitária**
   - Cobrir os quatro estados unitários, modos individual e consolidado,
     decisões e campos opcionais.
   - Confirmar ID da UO no individual e `null` no consolidado.
   - Confirmar sequencial, inclusões, exclusões e coerência das falhas.
   - Executar regressão focada e pausar.

3. **Integração com o Controller**
   - Injetar o Presenter concreto no Controller e alterar seu retorno para
     `PublicarPcaViewModel`.
   - Preservar Request, correlação e propagação de exceções destinada à futura
     fachada.
   - Executar Presentation e regressões afetadas; pausar.

4. **Gate e encerramento**
   - Executar Presentation, Application, Composition, Integration, suíte
     padrão, autoload PSR-4 estrito, lint, auditoria de dependências e
     `git diff --check`.
   - Atualizar documentação operacional e de apoio somente com evidências
     obtidas.
   - Não executar integrações externas nem criar commit.

## Critérios e limites

- Nenhum objeto de Domain ou Application atravessa o ViewModel.
- Nenhum resultado conhecido é descartado ou fabricado.
- Presenter não produz HTML, JSON, traduções ou mensagens adicionais.
- Fachada, tratamento global de exceções, serialização, Scriptcase e geração
  concreta da correlação permanecem adiados.
- `regras_de_negocio.md` não será alterado.

## Evidências do Microciclo 1

- O Red focado falhou nos três cenários pela ausência esperada do Presenter.
- O Green focado aprovou 3 testes e 28 asserções.
- A suíte da Presentation aprovou 10 testes e 46 asserções.
- O lint aprovou os quatro arquivos PHP criados no microciclo.
- `git diff --check` e a conferência equivalente dos arquivos novos não
  encontraram erros de whitespace; o Git emitiu apenas avisos de conversão
  futura de LF para CRLF pela configuração local.
- Controller, regras de negócio e integrações externas permaneceram intocados.

## Evidências do Microciclo 2

- A matriz focada aprovou 7 testes e 60 asserções.
- A suíte da Presentation aprovou 14 testes e 78 asserções.
- Foram cobertos os quatro estados unitários, os modos individual e
  consolidado, todas as decisões, decisão ausente, sequencial presente e
  ausente, contagens e falhas seguras.
- O ID da UO foi preservado no modo individual e projetado como `null` no modo
  consolidado.
- O Presenter genérico do Microciclo 1 já satisfez a matriz; nenhuma alteração
  adicional em código de produção foi necessária.
- O lint do teste alterado e a conferência de whitespace foram aprovados.
- Controller e integrações externas permaneceram intocados.

## Evidências do Microciclo 3

- O Red focado preservou os cenários de entrada e exceção e falhou somente
  porque o Controller ainda devolvia `PublicarPcaResponse`.
- O teste focado do Controller aprovou 5 testes e 16 asserções.
- A suíte da Presentation aprovou 14 testes e 80 asserções.
- A suíte da Application aprovou 237 testes e 978 asserções.
- O Controller agora recebe o Presenter concreto, entrega-lhe a Response e
  devolve `PublicarPcaViewModel`, sem assumir conversões.
- A busca de consumidores confirmou que somente os testes da Presentation
  instanciam atualmente o Controller.
- O lint dos dois arquivos alterados foi aprovado, e a Presentation continua
  sem dependências de Infrastructure, Composition ou Scriptcase.
- Nenhuma integração externa foi executada.

## Evidências do Microciclo 4

- Presentation aprovou 14 testes e 80 asserções; Application aprovou 237
  testes e 978 asserções; Composition aprovou 1 teste e 1 asserção; Integration
  aprovou 3 testes e 70 asserções.
- A suíte padrão aprovou 825 testes e 3.079 asserções.
- O autoload PSR-4 estrito gerou 1.750 classes, e o lint global aprovou 282
  arquivos PHP.
- As auditorias confirmaram a direção das dependências e a ausência de objetos
  de Domain ou Application nos ViewModels.
- A documentação operacional e de apoio foi atualizada somente com as
  evidências obtidas.
- Nenhum preflight, grupo `pncp-live`, probe, acesso externo ou commit foi
  executado.
- A Sprint 7.3 — Fachada e fronteira de exceções é a próxima etapa e permanece
  não autorizada.
