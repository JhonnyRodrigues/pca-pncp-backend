# Estado operacional do projeto PCA/PNCP

**Classificação documental:** Operacional

**Status:** Vigente

**Autoridade/Escopo:** progresso do projeto, última sprint concluída, próxima
sprint na ordem, gates, pendências e restrições operacionais

**Atualizado em:** 25/08/2026

## Limites de autoridade

Este documento centraliza somente o estado corrente do trabalho. Ele não cria
nem altera regras de negócio, modelo de domínio, decisões de integração,
arquitetura, fluxo de implementação, ordem das sprints ou critérios de saída.

Para esses assuntos, prevalecem as fontes autoritativas registradas no
[índice documental](../../README.md). A ordem de construção permanece definida
pelo [Roadmap](../autoritativos/roadmap.md).

## Progresso

- A Fase 4 — Application está concluída.
- A Sprint 5.1 — Gates contratuais Oracle e PNCP está concluída.
- A Sprint 5.2 — Sessão Oracle, unidade de trabalho, estratégia e locks está
  concluída.
- A Sprint 5.3 — Mapeamento e PlanoRepository está concluída.
- A Sprint 5.4 — Estado operacional, contexto, auditoria e eventos técnicos
  está concluída.
- A Sprint 5.5 — Transporte cURL, TLS e autenticação está concluída.
- A Sprint 5.6 — Serialização e payloads exatos está concluída.
- A última sprint concluída é a Sprint 5.6.
- A próxima sprint na ordem é a Sprint 5.7 — Observação, paginação e ausência
  definitiva.
- A Sprint 5.7 não foi iniciada e este registro não constitui autorização
  implícita para iniciá-la.

## Evidências da Sprint 5.6

- O marcador numérico valida a gramática JSON e preserva o lexema exato; o
  codificador recursivo aceita somente arrays, strings, inteiros, booleanos,
  `null` e números exatos, sem converter números para `float`.
- `float`, recursos, objetos desconhecidos, lexemas inválidos e UTF-8 inválido
  são recusados com uma única mensagem segura, sem valores ou payloads.
- Quantidade, totais e valor unitário são produzidos por operações textuais com
  quatro casas e `HALF_UP`; negativos, divisor zero e resultados fora de
  `DECIMAL(17,4)` são rejeitados, inclusive carry que provoca overflow.
- O valor unitário usa o valor total interno original dividido pela quantidade;
  valor total e orçamento do exercício são convertidos independentemente e não
  são recalculados pelo unitário arredondado.
- Os quatro corpos preservam e ordenam numericamente `ID_DEMANDA` como
  `numeroItem`, sem renumeração, e emitem categoria do PCA inteira,
  classificação Material/Serviço textual, catálogo independente, datas
  `YYYY-MM-DD` e números JSON exatos.
- Textos são validados por caracteres Unicode e nunca truncados. Campos sem
  fonte semanticamente compatível são omitidos, sem envio de `null`.
- O serializer não redefine o limite operacional de 1.000 itens protegido pela
  Application; um teste direto confirmou a ausência dessa duplicação com 1.001
  itens.
- A suíte focada de serialização aprovou 88 testes e 149 asserções.
- A regressão de `Infrastructure/Pncp` aprovou 124 testes e 296 asserções.
- A suíte padrão aprovou 597 testes e 2.102 asserções.
- A suíte contratual não real aprovou 24 testes e 51 asserções.
- Lint dos 10 arquivos PHP envolvidos, autoload otimizado com PSR-4 estrito,
  direção das dependências, ausência de HTTP na serialização, ausência de
  `float` e divisão nativa no conversor e `git diff --check` foram aprovados.
- As execuções usaram PHP 8.4.7. A implementação foi inspecionada para manter
  sintaxe e APIs compatíveis com PHP 8.1, mas não havia runtime PHP 8.1
  disponível para execução.

Nenhum teste `pncp-live`, probe Oracle/PNCP, endpoint, paginação, escrita externa
ou integração Scriptcase foi executado durante a Sprint 5.6.

## Evidências da Sprint 5.5

- Os ambientes PNCP são fechados em Homologação e Produção, associados somente
  aos endpoints canônicos HTTPS; Request e usuário não fornecem URL livre.
- A configuração exige limites positivos de payload e resposta, não admite
  timeout infinito e aplica os valores iniciais de 5 segundos para conexão,
  30 para login e leitura e 60 para escrita, com CA bundle opcional.
- O transporte cURL nativo executa exatamente uma requisição por chamada,
  verifica certificado e hostname, restringe o protocolo a HTTPS, desabilita
  redirects e captura status, headers e corpo separadamente.
- Payload acima do limite é recusado antes da execução; resposta acima do
  limite é interrompida; falha de transporte permanece distinta de resposta
  HTTP e não decide retry ou reconciliação.
- A autenticação envia login e senha somente no corpo interno da requisição,
  ocorre uma vez por execução e exige `Authorization: Bearer` em resposta 200.
- O token permanece opaco em sessão sem contrato de serialização, sem leitura
  de claims, persistência, renovação ou exposição em eventos e falhas.
- `401`, `403`, falha de transporte e resposta de autenticação inválida geram
  falha sistêmica sanitizada; a ação técnica `AUTENTICAR_PNCP` não amplia o
  catálogo de ações da Application.
- A suíte focada aprovou 48 testes e 185 asserções.
- A regressão relacionada aprovou 288 testes e 1.171 asserções.
- A suíte padrão aprovou 509 testes e 1.953 asserções.
- A suíte contratual não real aprovou 24 testes e 51 asserções.
- Lint dos 19 arquivos PHP envolvidos, autoload PSR-4 estrito e inspeções de
  segurança, escopo e direção das dependências foram aprovados.

Nenhum probe real Oracle ou PNCP e nenhuma integração Scriptcase foram
executados durante a Sprint 5.5.

## Evidências da Sprint 5.4

- O estado operacional em `PAC_PLANOS` exige transação ativa, projeta todas as
  versões do escopo, rejeita divergências e confirma a atualização por
  releitura na mesma sessão.
- O contexto do usuário resolve exatamente um Representante ativo e uma
  Lotação vigente e ativa pelo login confiável, sem inferência por UO ou UASG.
- A auditoria insere fatos append-only em `PAC_HISTORICOS` com `SYSDATE`, binds
  e procedimentos tipados 5, 8 ou 9 na mesma transação da confirmação e do
  estado operacional.
- O backend produz eventos técnicos imutáveis por PSR-3, com ações operacionais
  da Application, sanitização recursiva e acumulação ordenada apenas em memória.
- O lote para exportação futura calcula `executionTimeMs` sem alterar os
  eventos acumulados nem seu metadata original.
- Os eventos não integram `PublicarPcaResponse`, não escrevem em Oracle ou
  Scriptcase e não possuem adaptação para `sc_log_add` nesta sprint.
- A suíte focada aprovou 67 testes e 432 asserções.
- A suíte padrão aprovou 472 testes e 1.805 asserções.
- A suíte contratual não real aprovou 24 testes e 51 asserções.
- Lint dos arquivos PHP alterados, autoload PSR-4 estrito e inspeções de
  fronteira foram aprovados.

Nenhum probe real Oracle ou PNCP, DDL ou integração Scriptcase foi executado
durante a Sprint 5.4.

## Evidências da Sprint 5.3

- Os mapeamentos de Planos, Demandas e Unidades Orçamentárias validam os
  identificadores Oracle e não possuem nomes físicos implícitos.
- A tradução física usa aliases canônicos, datas com máscaras explícitas,
  fuso operacional injetado e decimais exatos sem `float`.
- A descoberta retorna identidades distintas e ordenadas, exige cadastro ativo
  único de UO e rejeita ausência ou incoerência de Lotação e UASG.
- A reconstrução exige transação ativa, seleciona a maior versão sem usar a
  confirmação e reúne Demandas Homologadas, Compradas e Publicadas de todas as
  versões de origem.
- Plano vazio permanece reconstruível; cardinalidade, duplicidade e conteúdo
  físico inválido são rejeitados antes de atravessar a Infrastructure.
- A confirmação confere a identidade e versão exatas, atualiza somente a linha
  pendente com `SYSDATE` e relê a intenção, sem mutar o Plano carregado.
- O Repository reutiliza a conexão e a transação emprestadas, sem `Close()`,
  commit ou rollback autônomo.
- A suíte padrão aprovou 428 testes e 1.511 asserções.
- A suíte contratual não real aprovou 24 testes e 51 asserções.
- A suíte focada da Infrastructure Oracle aprovou 89 testes e 372 asserções.

Nenhum probe real Oracle ou PNCP e nenhum DDL foram executados durante a
Sprint 5.3.

## Gates confirmados

- O gate Oracle/Scriptcase confirmou ADOdb emprestado, `Param()`, mesma sessão,
  `BeginTrans`, `CommitTrans`, `RollbackTrans`, ausência de `Close()`,
  `FOR UPDATE NOWAIT` e contenção traduzível a partir de `ORA-00054`.
- O gate PNCP confirmou os contratos autorizados de runtime, TLS, autenticação,
  leitura e escrita no ambiente de treinamento.
- Os contratos não reais confirmaram os mapeamentos de contexto, estado e
  auditoria; a validação no Oracle e no Scriptcase reais permanece futura.

As evidências não autoritativas da Sprint 5.1 permanecem em
[Evidências dos gates Oracle e PNCP](../../investigacoes/evidencias_sprint_5_1_gates_oracle_pncp_2026-08-17.md).

## Pendências não bloqueantes

- A compatibilidade externa do PNCP com CNPJ alfanumérico permanece **AINDA
  NÃO COMPROVADA**.
- Uma chamada única com 1.001 itens permanece **AINDA NÃO COMPROVADA**.
- O limite operacional continua em 1.000 itens por chamada.

## Restrições para o próximo avanço

- O provisionamento de âncoras depende de tabela e colunas preexistentes,
  fornecidas pelo mapeamento físico; o backend não cria DDL nem aplica
  fallback se a tabela estiver ausente.
- Nomes físicos, credenciais, tokens e strings de conexão não possuem default
  no código e não devem ser registrados neste documento.
- A conexão ADOdb fornecida pelo Scriptcase permanece emprestada e nunca pode
  ser fechada pelo backend.
- A observação, paginação e ausência definitiva permanecem no escopo ainda não
  iniciado da Sprint 5.7.
- A exposição dos eventos pela fachada e sua persistência pela Blank com
  `sc_log_add` permanecem adiadas para a futura fronteira Scriptcase.
- Uma falha fatal anterior ao retorno à Blank pode impedir a persistência dos
  eventos acumulados em memória; essa limitação foi aceita conscientemente.
