# Sprint 7.4 — Suíte transversal da Presentation

**Status:** Concluída em 01/09/2026 após o microciclo único autorizado.

## Resumo

Encerrar a Fase 7 com uma prova integrada da fronteira pública já
implementada, sem criar novo componente de produção.

## Microciclo único

- Exercitar `EntradaPublicarPca`, fachada, Controller, Presenter e ViewModels
  em uma suíte transversal.
- Demonstrar preservação de resultados conhecidos, ordem, contagens,
  correlação e falhas seguras.
- Demonstrar que a saída consumível contém somente ViewModels, escalares e
  valores nulos.
- Demonstrar que entradas inválidas não alcançam o UseCase e que falhas
  inesperadas não expõem detalhes internos.
- Executar os gates da Presentation, regressões internas, autoload, lint,
  direção das dependências, sanitização e `git diff --check`.
- Atualizar a documentação de encerramento somente depois de todos os gates
  verdes.

## Regra de interrupção

Se a suíte transversal ou qualquer gate revelar divergência, nenhuma correção
será realizada automaticamente. O microciclo será interrompido, a evidência
será apresentada e a continuidade dependerá de nova autorização.

## Limites e pendências

- Não executar integrações externas, probes, preflights ou grupos live.
- Não criar renderer, adapter Scriptcase, Composition Root, perfil,
  serializador, logger ou nova API pública.
- A renderização pela Blank real permanece para a Fase 8.
- O registro PSR-3 da falha inesperada capturada e sanitizada pela fachada
  permanece pendente para a Sprint 8.1, quando fachada, acumulador, correlação
  e grafo final compartilharem o mesmo ciclo de execução.
- Não alterar `regras_de_negocio.md` nem criar commit.

## Evidências de encerramento

- A suíte transversal aprovou 6 testes e 152 asserções.
- Presentation aprovou 28 testes e 300 asserções; Application aprovou 237
  testes e 978 asserções; Composition aprovou 1 teste e 1 asserção; Integration
  aprovou 3 testes e 70 asserções.
- A suíte padrão aprovou 839 testes e 3.299 asserções.
- O autoload PSR-4 estrito gerou 1.754 classes, e o lint aprovou 286 arquivos
  PHP.
- As auditorias de direção das dependências, ViewModels, conteúdo proibido,
  sanitização da fachada e whitespace foram aprovadas.
- Nenhuma divergência do comportamento existente foi encontrada e nenhum
  arquivo de produção foi alterado.
- Nenhuma integração externa foi executada e nenhum commit foi criado.
