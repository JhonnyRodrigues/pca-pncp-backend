# Sprint 5.8 — Escritas PNCP e resultados incertos

## Base confirmada

- A Sprint 5.7 está concluída e a Sprint 5.8 é a próxima autorizada.
- O Git está em `main`, alinhado com `origin/main`; somente o prompt 5.8 está não rastreado e será preservado.
- Fontes principais: [Roadmap](/C:/Users/jsilva/repositorios-semae/pca-pncp-backend/docs/vigentes/autoritativos/roadmap.md:670), [DI-001, DI-004 e DI-009](/C:/Users/jsilva/repositorios-semae/pca-pncp-backend/docs/vigentes/autoritativos/decisoes_de_integracao.md:82), fluxo vigente e [Manual PNCP 2.5](https://pncp.gov.br/manual/pt-br/latest/plano_de_contratacao/inserir_plano_de_contratacoes.html), confrontados com o [OpenAPI oficial](https://pncp.gov.br/pncp-api/v3/api-docs).
- Linhas de base novamente aprovadas em PHP 8.4.7: 668 testes/2.290 asserções; contratos não reais 24/51; Infrastructure/Pncp 195/484; serialização 88/149.
- Nenhum arquivo foi alterado e nenhum teste `pncp-live`, probe ou chamada à API PNCP foi executado.

## Contratos e classificação

| Operação | Port, Command e Result | Requisição |
|---|---|---|
| Publicação | `publicar`, `ComandoPublicarPca`, `ResultadoPublicacaoPncp` | `POST /v1/orgaos/{cnpj}/pca`, com o envelope já produzido por `serializarPublicacao()` |
| Inclusão | `inserirItens`, `ComandoInserirItensPca`, `ResultadoEscritaPncp` | `POST /v1/orgaos/{cnpj}/pca/{ano}/{sequencial}/itens`, com a lista integral serializada |
| Exclusão de itens | `excluirItens`, `ComandoExcluirItensPca`, `ResultadoEscritaPncp` | `DELETE` no mesmo endpoint de itens, com `justificativa` e `listaNumerosItens` |
| Exclusão integral | `excluirPca`, `ComandoExcluirPca`, `ResultadoEscritaPncp` | `DELETE /v1/orgaos/{cnpj}/pca/{ano}/{sequencial}`, com a justificativa integral |

- Cada chamada usará Bearer da sessão opaca, JSON UTF-8, timeout de escrita configurado e exatamente uma execução do transporte.
- Conforme a escolha do mantenedor, publicação e inclusão aceitarão somente `200` ou `201`. Exclusões aceitarão somente `200`.
- Publicação será `ACEITA` apenas com um único `Location` válido, compatível com CNPJ e ano enviados e contendo `sequencialPca` positivo. Header ausente, duplicado, malformado ou divergente produzirá `RESULTADO_INCERTO`; não haverá fallback por corpo JSON.
- Inclusão e exclusões não precisam extrair identificadores: corpo vazio e ausência de `Location` são válidos com o status exato. Corpos e mensagens externas não controlarão o fluxo nem serão registrados.
- `400` e `422` recebidos como resposta HTTP válida serão `REJEITADA`; `401` e `403`, `FALHA_SISTEMICA`; `408`, `429`, `5xx` e qualquer status não aprovado serão `RESULTADO_INCERTO`.
- Falha de serialização, sessão incompatível, configuração/runtime local, preparação da requisição, limite local de payload ou TLS não confirmado serão `FALHA_SISTEMICA`.
- Timeout, execução cURL interrompida, resposta excedendo o limite ou impossibilidade de construir uma resposta HTTP válida após o despacho serão `RESULTADO_INCERTO`. Retorno de `RespostaHttpPncp` comprova resposta recebida; exceção do transporte indica ausência de resposta HTTP confiável.
- As `FalhaSegura` usarão códigos e mensagens fixos. Eventos poderão conter somente ação técnica, situação, status HTTP e quantidade; ficarão excluídos token, Authorization, headers, corpos, URLs, CNPJ, UASG, sequencial e IDs.

## Implementação e microciclos

1. **Publicação e sequencial**
   - Red: testes do endpoint, headers, payload, timeout, chamada única, `200`/`201` e validação estrita de `Location`.
   - Green: criar `Infrastructure\Pncp\Escrita\EscritorPncpAdapter`, reutilizando transporte, configuração, serializer, sessão opaca e logger existentes.
   - Refactor: concentrar montagem segura de requisição e criação dos Results sem antecipar abstrações.
   - A aprovação inicial libera somente este microciclo; ao final, pausar.

2. **Inclusão e exclusão de itens**
   - Testar POST e DELETE exatos, coleções completas, justificativa padronizada já recebida pelo Command, chamada única e ausência de particionamento.
   - Implementar ambos os métodos no mesmo adapter.
   - Confirmar por regressão da Application que exclusão continua ocorrendo antes da inclusão e que inclusão não ocorre após exclusão rejeitada ou incerta.
   - Pausar para autorização.

3. **Exclusão integral**
   - Testar caminho, payload integral da justificativa, status `200`, chamada única e classificações básicas.
   - Implementar `excluirPca()` sem inferir, truncar ou substituir a justificativa.
   - Pausar para autorização.

4. **Matriz de falhas e observabilidade**
   - Completar em TDD a matriz de rejeição, incerteza e falha sistêmica para as quatro operações.
   - Cobrir corpo vazio, corpo não utilizado, `Location` ausente/inválido, status inesperado, falhas antes e depois do despacho, ausência de retry e sanitização.
   - Acrescentar quatro ações internas a `AcaoTecnicaPncp`; extrair helper somente se a duplicação comprovada justificar.
   - Pausar para autorização.

5. **Gate final e documentação**
   - Executar testes focados de escrita, Infrastructure/Pncp, serialização, contratos não reais e suíte completa; depois lint, PSR-4 estrito, `git diff --check`, inspeção integral do diff e `git status`.
   - Registrar o plano aprovado, atualizar catálogo e estado operacional com somente evidências executadas. Não alterar fontes normativas salvo incompatibilidade material efetivamente descoberta.
   - Não criar commit.

## Interfaces, limites e adiamentos

- Nenhuma alteração em Domain, Application, `EscritorPncp`, Commands, Results, enums públicos ou APIs públicas.
- A Application continuará responsável pela ordem excluir → incluir, limite de 1.000 itens, observação final e reconciliação. O adapter enviará cada coleção completa em uma única requisição, sem retry, loop, compensação ou particionamento.
- A Sprint 5.9 permanece responsável pelos contratos autorizados dos adapters e evidências em ambientes reais. Composition Root, Presentation, fachada e Scriptcase continuam posteriores.
- O gate será executado em PHP 8.4.7; a compatibilidade com PHP 8.1 será preservada por sintaxe/APIs e inspeção, pois não há runtime 8.1 disponível.
