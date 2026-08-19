# Evidências da Sprint 5.1 — gates Oracle e PNCP

**Classificação documental:** Investigação

**Status:** Concluída — Sprint 5.2 autorizável, mas não iniciada

**Período de referência:** 17 a 19/08/2026

**Autoridade/Escopo:** evidências contratuais não normativas anteriores à Infrastructure

## 1. Finalidade e limites

Este relatório consolida as provas reproduzíveis da Sprint 5.1. Ele não altera
regras de negócio, contratos internos, limites da Application ou decisões de
integração vigentes. Em caso de conflito, prevalecem as fontes autoritativas
registradas em [`docs/README.md`](../README.md).

A sprint não implementou adapters concretos das Sprints 5.2 a 5.8. Os probes
opt-in em `tests/Contract/Sprint51/` são ferramentas de diagnóstico e não
implementam nenhuma Port.

Os JSON integrais permaneceram fora do repositório. Este documento registra
somente hashes e resultados sanitizados, sem token, credencial, string de
conexão, SQL de negócio, linha de negócio ou payload/resposta integral.

## 2. Matriz final dos gates

| Gate | Ambiente/prova | Estado final | Resultado e efeito |
|---|---|---|---|
| Linha de base | PHPUnit padrão no workspace | **CONFIRMADO** | 339 testes e 1139 asserções |
| Isolamento da suíte externa | PHPUnit/Composer | **CONFIRMADO** | `composer tests` não alcança Oracle nem PNCP |
| Fail-closed e configuração externa | preflight estrutural | **CONFIRMADO** | configuração ausente ou insegura é recusada antes de I/O externo |
| Runtime Scriptcase/ADOdb | Scriptcase real | **CONFIRMADO** | PHP 8.1.28, OCI8 3.1.0, ADOdb embarcado e conexão emprestada observados |
| Bind, erro e mesma sessão | Scriptcase/Oracle | **CONFIRMADO** | bind Oracle normalizado, erro controlado e identidade de sessão preservada |
| Rollback, commit e restauração | tabela técnica dedicada | **CONFIRMADO** | rollback real, commit controlado e restauração comprovados |
| `FOR UPDATE NOWAIT` | duas sessões reais | **CONFIRMADO** | contenção imediata com `ORA-00054` e nova aquisição após rollback |
| Schema, tipos, constraints e agregações | dicionário Oracle/consultas allowlisted | **CONFIRMADO** | 8 tabelas, 65 colunas, 73 constraints, 12 entradas de índice e 7 agregações aprovadas |
| Manual PNCP | manual oficial, versão 2.5 | **CONFIRMADO** | conteúdo e data confirmados; o hash do HTML bruto é evidência auxiliar não obtida e não bloqueia os contratos exercitados |
| OpenAPI PNCP | produção e treinamento | **CONFIRMADO** | snapshots e hashes registrados; divergência documental de limite preservada |
| TLS, CA e redirects | PHP/cURL → treinamento | **CONFIRMADO** | CA explícita, verificação de peer/host ativa e redirects desabilitados em todas as chamadas |
| Autenticação e `Bearer` | treinamento | **CONFIRMADO** | JWT usado após login e não registrado |
| Escritas, paginação e ausência | massa dedicada no treinamento | **CONFIRMADO** | publicação, inserção, paginação, rejeição, exclusões e ausência comprovadas |
| IDs com lacunas e decimal exato | fluxo pequeno no treinamento | **CONFIRMADO** | lacunas preservadas e representação decimal exata aceita |
| Chamada única com 1001 itens | não executada por decisão explícita | **AINDA NÃO COMPROVADO** | não bloqueante; limite operacional permanece 1000 por chamada |
| CNPJ alfanumérico | organização adequada indisponível | **AINDA NÃO COMPROVADO** | não bloqueante; decisões especificamente dependentes continuam bloqueadas |

Nenhum gate obrigatório para o encerramento aprovado terminou
`INCOMPATÍVEL`. Os dois itens ainda não comprovados foram expressamente
classificados como não bloqueantes para esta sprint.

## 3. Suíte contratual e segurança

### 3.1 Linha de base e isolamento

Comandos reproduzíveis:

```bash
composer tests
composer contract:tests
```

Resultados finais:

- suíte padrão: `OK (339 tests, 1139 assertions)`;
- suíte estrutural opt-in: `OK (24 tests, 51 assertions)`;
- `phpunit.xml` exclui `tests/Contract`;
- o grupo externo `pncp-live` somente é alcançado pelo comando explícito
  `composer contract:pncp`.

Os probes exigem configuração por arquivo secreto externo ao repositório. O
preflight recusa configuração ausente, host diferente do treinamento,
autorização de escrita ausente, tipos inválidos e CA bundle inválido.

### 3.2 CA bundle explícito

A primeira tentativa autenticada no PNCP falhou de forma fechada com cURL
`errno 60`, antes da autenticação e de qualquer escrita. O cleanup foi
confirmado e não havia resíduos. A causa local era a ausência de CA configurada
no PHP usado pelo Composer, embora o cURL CLI validasse a rede e o TLS.

A suíte passou então a aceitar `pncp.ca_bundle` opcional. Quando informado, o
preflight exige string não vazia, caminho absoluto, arquivo existente e
legível. O transporte usa `CURLOPT_CAINFO` sem desativar
`CURLOPT_SSL_VERIFYPEER`, `CURLOPT_SSL_VERIFYHOST` ou a proibição de redirects.
O caminho não é exposto no relatório público.

## 4. Evidências Oracle no Scriptcase real

### 4.1 Discovery, schema e transações

O probe foi executado em uma cópia do backend no Scriptcase, com a conexão
ADOdb emprestada. O código-fonte de trabalho permaneceu no workspace local.

Relatório externo sanitizado:

- execução: `2026-08-19T12:04:23+00:00`;
- SHA-256: `8ccdebaf8d91fab3eecb052d543345623d0567785aa3f6cd90422a672163fbfc`;
- tamanho: 49.881 bytes;
- resultado global: confirmado;
- conexão emprestada: sim;
- conexão fechada pelo probe: não;
- possível resíduo de fixture: não.

Runtime observado:

- PHP 8.1.28, SAPI `cgi-fcgi`;
- OCI8 3.1.0 e Oracle 19.0.0.0.0;
- ADOdb embarcado `v4.991`, com todos os métodos requeridos presentes.

Contratos comprovados:

- bind posicional convertido pela API `Param()` do ADOdb para placeholder
  compatível com Oracle;
- erro controlado traduzido e sanitizado (`ORA-01476`), sem mensagem crua;
- mesma sessão preservada durante o roteiro;
- data Oracle e `NUMBER(17,4)` exato;
- quatro parâmetros NLS relevantes inspecionados;
- metadados de 8 tabelas allowlisted: 65 colunas, 73 constraints e 12 entradas
  de índice;
- sete agregações allowlisted aprovadas, sem registrar SQL ou valores de
  negócio;
- visibilidade anterior ao rollback, rollback real, commit real, restauração
  do estado e mesma sessão em toda a transação.

O primeiro discovery revelou `ORA-00911` porque o ADOdb/Oracle real não
aceitou o placeholder `?` usado diretamente. O probe foi corrigido para usar
`Param()`. Uma segunda execução revelou `ORA-01745` na `agregacao_7`, causada
por nome de bind inválido na configuração externa. Após corrigir a
configuração, o discovery integral foi confirmado. Essas ocorrências foram
falhas concretas de validação, não incompatibilidades residuais do contrato.

### 4.2 Lock concorrente

Foram usadas duas sessões HTTP/Oracle independentes:

1. sessão A, `lock_holder`, às `2026-08-19T13:16:19+00:00`: lock adquirido,
   retido por 30 segundos, mesma sessão e liberação por rollback no `finally`;
2. sessão B, `lock_expect_blocked`, às `13:16:24+00:00`: falha imediata e
   esperada com `ORA-00054` enquanto A mantinha o lock;
3. sessão B, `lock_expect_available`, às `13:17:07+00:00`: lock adquirido após
   a liberação por A.

Os três resultados foram confirmados, não fecharam a conexão emprestada e não
deixaram possível resíduo de fixture.

## 5. Evidências PNCP no treinamento

### 5.1 Configuração e relatório

O preflight confirmou o destino canônico de treinamento, autorização explícita
de escritas, limite operacional de 1000 itens e probe de 1001 itens desabilitado.

Relatório externo sanitizado:

- execução: `2026-08-19T14:27:50+00:00`;
- SHA-256: `515d28217e9e96ab057e4c6b94a3daeea2f744905d9c7b50f48e720d177c7582`;
- tamanho: 6.725 bytes;
- runtime: PHP 8.3.13 CLI, cURL 8.7.0-DEV e OpenSSL 3.0.15;
- resultado global: confirmado;
- cleanup final: confirmado;
- resíduos possíveis: zero.

### 5.2 Fluxo confirmado

O roteiro autenticado e serial realizou:

1. login HTTP 200 e uso posterior do JWT em `Authorization: Bearer`;
2. consulta preventiva de candidatos;
3. publicação HTTP 201 com IDs não contíguos e decimal exato;
4. leitura paginada completa até HTTP 204, sem duplicidades;
5. inserção adicional HTTP 201, preservando nova lacuna;
6. nova leitura paginada completa;
7. requisição inválida controlada, rejeitada com HTTP 400;
8. exclusão em lote HTTP 200 e comprovação posterior;
9. exclusão integral HTTP 200;
10. ausência definitiva por sequencial e pela consulta de candidatos.

Foram registrados 18 eventos HTTP: 11 respostas 200, duas 201, quatro 204 e
uma 400. Todas as chamadas registraram TLS verificado, sem falha de transporte.
O token não consta do relatório, e a varredura de sanitização não encontrou
credenciais, CNPJ, URL base, caminho da CA, SQL ou parâmetros sensíveis.

### 5.3 Paginação pública e documentação oficial

Antes do fluxo autenticado, uma consulta pública de somente leitura no
treinamento, com TLS validado, observou um PCA com 1326 itens únicos. A página
1 retornou 1000 itens e a página 2 retornou 326. Isso comprova que o total de
um PCA pode superar 1000; não comprova que uma única escrita aceite mais de
1000 itens.

Fontes consultadas:

- [Manual de Integração do PNCP](https://pncp.gov.br/manual/pt-br/latest/singlehtml/),
  versão 2.5, datado de 11/06/2026;
- [Swagger oficial de produção](https://pncp.gov.br/api/pncp/swagger-ui/index.html?configUrl=/pncp-api/v3/api-docs/swagger-config);
- [Swagger oficial de treinamento](https://treina.pncp.gov.br/api/pncp/swagger-ui/index.html?configUrl=/pncp-api/v3/api-docs/swagger-config).

Os snapshots OpenAPI de 17/08/2026 tinham 363.110 bytes cada e eram
semanticamente equivalentes, desconsiderando exemplos dinâmicos e ordem de
chaves:

| Ambiente | SHA-256 bruto |
|---|---|
| Produção | `2280edf0855ffc3b808f133a51dd5ac335ff92dd8bdc788d8140ac3260efd5fd` |
| Treinamento | `3b92f43dfa9486affae6a5238219eb6006a062dce80ec170155511dda1d4f759` |

O manual informa 1000 itens por chamada, enquanto os OpenAPI declaram
`maxItems: 2000`. O servidor do manual encerrou as tentativas de captura do
HTML sem corpo (`curl errno 52`); portanto, o hash bruto do manual permanece
não comprovado, embora versão, data e conteúdo aplicável tenham sido
confirmados.

## 6. Limite de itens e CNPJ alfanumérico

A divergência do limite está registrada, mas não será aprofundada nesta
sprint. Permanecem distintos:

1. o máximo recebido por chamada;
2. o total permitido em um PCA;
3. eventual particionamento de escritas;
4. paginação e observação do total.

O teste descartável de 1001 itens não foi executado. Até decisão futura
explícita, `ExecutorComandosUnidade`, contratos internos, testes e fontes
autoritativas permanecem inalterados, e a operação conserva o limite de 1000
itens por chamada.

Uma revisão futura desse limite exigiria análise específica de DI-001, DI-004
e DI-009, do executor/dispatcher de comandos, dos testes da Application, do
fluxo de implementação e do roadmap. A observação pública de 1326 itens não
autoriza essa revisão por si só.

O CNPJ alfanumérico permanece **AINDA NÃO COMPROVADO** porque a organização
disponível possui apenas CNPJ numérico tradicional. Essa lacuna não bloqueia o
encerramento da Sprint 5.1, mas nenhuma decisão ou implementação que dependa
especificamente do comportamento alfanumérico fica liberada sem ambiente ou
organização adequada.

## 7. Conclusão formal

A Sprint 5.1 está **CONCLUÍDA** em 19/08/2026:

- os gates obrigatórios Oracle/Scriptcase foram confirmados no ambiente real;
- os gates obrigatórios PNCP foram confirmados no treinamento;
- a linha de base e a suíte contratual permaneceram verdes;
- nenhuma evidência pública contém segredo ou dado de negócio;
- nenhuma Port, entidade, Command, Result, enum, caso de uso, adapter de
  Infrastructure ou limite da Application foi alterado pela sprint;
- chamada única com 1001 itens e CNPJ alfanumérico permanecem não comprovados,
  de forma explicitamente não bloqueante.

Com esses resultados, a Sprint 5.2 torna-se **autorizável**, mas permanece
**não iniciada** até autorização explícita. As decisões dependentes dos dois
itens não comprovados continuam bloqueadas nos seus escopos específicos.
