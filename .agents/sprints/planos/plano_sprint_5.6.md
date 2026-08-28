# Sprint 5.6 — Serialização e payloads exatos

## Resumo

Implementar com TDD a borda interna de serialização PNCP em `Infrastructure`, sem HTTP real, novas dependências, commits ou alterações em Domain, Application, Ports, Commands e APIs públicas existentes.

A aprovação inicial libera somente o Microciclo 1. Ao final de cada microciclo, apresentar diff e evidências executadas, pausar obrigatoriamente e aguardar autorização explícita para o seguinte.

## Componentes e decisões

- Criar em `Infrastructure\Pncp\Serializacao`:
  - `FalhaSerializacaoPncp`, sempre com a mensagem segura `Não foi possível serializar o payload PNCP.`;
  - `NumeroJsonExato`, marcador imutável que aceita exclusivamente a gramática numérica JSON;
  - `CodificadorJsonExato`, recursivo e compacto;
  - `ConversorDecimalPncp`, baseado somente em operações textuais;
  - `SerializadorPayloadPncp`, com métodos tipados para os quatro Commands existentes.
- O codificador aceitará apenas arrays, strings UTF-8 válidas, inteiros, booleanos, `null` e `NumeroJsonExato`. Rejeitará `float`, recursos, objetos desconhecidos, UTF-8 inválido e lexemas inválidos sem revelar dados.
- `HALF_UP` vem da DI-009 vigente, não de `DECIMAL(17,4)`. Quantidade zero já é impossível no Domain, mas o conversor também recusará defensivamente divisor `<= 0`; não haverá regra especial para `0 ÷ 0`.
- `categoriaItemPca` será o inteiro de `Demanda::getCategoria()`. `classificacaoCatalogo` será sua projeção textual `1 → "1"` ou `2 → "2"`, pois o contrato a define como Material/Serviço. `Demanda::getCatalogo()` continuará independente e fornecerá somente `catalogo`.
- A coincidência entre as duas classificações vale apenas para Material e Serviço, o subconjunto atual do Domain; ampliação futura das categorias exigirá reabrir esse mapeamento.
- `SerializadorPayloadPncp` exporá internamente:
  - `serializarPublicacao(ComandoPublicarPca): string`;
  - `serializarInclusaoItens(ComandoInserirItensPca): string`;
  - `serializarExclusaoItens(ComandoExcluirItensPca): string`;
  - `serializarExclusaoPca(ComandoExcluirPca): string`.

## Microciclos

### 1. Número JSON exato e codificador

- **Red:** testar zeros finais, escapes, Unicode, listas e objetos JSON, lexemas inválidos, UTF-8 inválido e rejeição de `float`, recursos e objetos desconhecidos.
- **Green:** implementar marcador, falha segura e codificação recursiva determinística, preservando os números exatos sem aspas.
- **Refactor e pausa:** executar testes focados, lint dos novos PHP e `git diff --check`; apresentar evidências e aguardar autorização.

### 2. Conversão decimal textual

- **Red:** cobrir escala menor e maior que quatro, divisão não exata, empate e carry em `HALF_UP`, zero monetário, negativos, quantidade zero, limites e overflow de `DECIMAL(17,4)`, inclusive após arredondamento.
- **Green:** emitir quantidade positiva como `N.0000`; converter cada total diretamente do `DecimalExato`; calcular o unitário dividindo o valor total interno original pela quantidade; arredondar cada resultado independentemente.
- **Refactor e pausa:** comprovar ausência de `float`, não derivação dos totais pelo unitário, testes focados, regressão dos componentes de serialização, lint e diff; aguardar autorização.

### 3. Projeção e serialização de itens

- **Red:** comparar integralmente itens Material e Serviço, incluindo uma combinação em que categoria e catálogo sejam diferentes; cobrir datas, limites textuais, omissões, IDs fora de ordem e com lacunas.
- **Green:** produzir os campos, nesta ordem determinística: `numeroItem`, `categoriaItemPca`, `catalogo`, `classificacaoCatalogo`, `classificacaoSuperiorCodigo`, `classificacaoSuperiorNome`, `codigoItem`, `descricao`, `unidadeFornecimento`, `quantidade`, `valorUnitario`, `valorTotal`, `valorOrcamentoExercicio`, `dataDesejada`.
- Preservar `ID_DEMANDA`, exigir valor positivo representável como `int32`, garantir unicidade e ordenar numericamente, sem renumeração.
- Formatar `dataDesejada` com `Y-m-d` no próprio calendário do objeto, sem alterar fuso.
- Validar UTF-8 e limites contratuais por caracteres, sem truncar.
- Omitir completamente `pdm*`, unidade requisitante textual, grupo de contratação e renovação contratual.
- **Refactor e pausa:** executar testes focados, regressão de serialização e `Infrastructure/Pncp`, lint e diff; aguardar autorização.

### 4. Quatro corpos de Command

- **Red:** comparar integralmente os JSONs de publicação, inclusão, exclusão de itens e exclusão integral, incluindo ordenação dos itens e dos IDs excluídos.
- **Green:** construir:
  - publicação: `codigoUnidade`, `anoPca`, `itensPlano`;
  - inclusão: lista JSON de itens;
  - exclusão de itens: `justificativa`, `listaNumerosItens`;
  - exclusão integral: `justificativa`.
- Validar `codigoUnidade` até 20 caracteres, justificativa até 255 e os limites dos textos dos itens, sempre sem truncamento.
- Não repetir no serializer o limite operacional de 1.000 itens já protegido pela Application.
- **Refactor e pausa:** executar suíte focada, regressão completa de `Infrastructure/Pncp`, lint e diff; aguardar autorização.

### 5. Gate final e documentação

- Executar:
  - testes focados;
  - regressão de `Infrastructure/Pncp`;
  - `composer tests`;
  - `composer contract:tests`, que exclui `pncp-live`;
  - lint de todos os PHP alterados;
  - `composer dump-autoload --strict-psr`;
  - inspeção da direção das dependências;
  - `git diff --check` e revisão integral do diff.
- Não executar `contract:pncp`, probes Oracle/PNCP, endpoints, paginação ou escrita externa.
- Atualizar somente o catálogo de arquivos e `estado_atual.md`, registrando resultados realmente executados, conclusão da Sprint 5.6, próxima Sprint 5.7 e a limitação de validação em PHP 8.4 com código compatível com PHP 8.1.
- Preservar as alterações locais preexistentes; não alterar Roadmap, DI-009, dependências ou documentação histórica.
- Apresentar o encerramento e pausar para aprovação final.

## Premissas

- Linha de base conhecida: 509 testes/1.953 asserções e 24 contratos não reais/51 asserções; os números finais serão registrados conforme a execução.
- O OpenAPI atual prevalece para o tipo de transporte de `classificacaoCatalogo` como string `"1"`/`"2"`; o manual oficial fornece sua semântica Material/Serviço.
- As Sprints 5.7 e 5.8 continuam responsáveis, respectivamente, por leitura/paginação e endpoints/escritas.
- Nenhuma implementação começará sem aprovação explícita deste plano.
