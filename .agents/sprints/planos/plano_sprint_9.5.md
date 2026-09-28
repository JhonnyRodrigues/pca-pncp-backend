# Sprint 9.5 — Prontidão de produção e suporte

**Status:** plano aprovado; as 366 demandas normais legadas de 2026 foram
vinculadas e o precheck pós-migration aprovou 478 pares desejados, com somente
71/3/38 inclusões. O Microciclo 8 continua interrompido: três acionamentos
informados pelo mantenedor terminaram com HTTP 403 no primeiro `POST` de itens.
Não repetir a escrita antes de implantar e preparar a captura diagnóstica
autorizada.
Cada microciclo termina com pausa e o próximo exige autorização explícita.

## Objetivo e fontes

Concluir a prontidão operacional, testar 2027 com resultado esperado
`NENHUMA_ACAO` e corrigir o bloqueio HTTP 403 da retificação de 2026.
Acrescentar os `numeroItem` ao diagnóstico: listas planejadas e confirmadas
nos logs; listas de incluídos e excluídos na Blank somente para `RETIFICAR`
concluída. Conforme `DI-001`, cada `numeroItem` corresponde ao `ID_DEMANDA`.

Aplicam-se a Sprint 9.5 do roadmap, `DI-001`, `DI-004`, `DI-006`, `DI-009`,
`DI-010`, a especificação de logs estruturados e o estado operacional vigente.
O conjunto mínimo envolve o despacho e a finalização da unidade, resultado e
Presenter, transporte PNCP, Blank, testes, consultas e documentação de suporte.

## Microciclos aprovados

1. **Diagnóstico sem escrita.** Conferir no DBeaver a estratégia, os
   procedimentos Oracle 5/8/9 e as fotografias de 2026/2027. Comparar, pela
   correlação, autenticação, leituras, duração e primeiro `DELETE` que recebeu
   403. Não alterar dados nem repetir a publicação.
2. **Autenticação antes da escrita.** Revisar pontualmente a `DI-009`. Após a
   decisão de cada unidade que exige escrita, obter uma nova sessão antes do
   primeiro envio mutante e reutilizá-la nas escritas e observações finais
   daquela unidade. `NENHUMA_ACAO` não gera novo login. Falha no login desfaz a
   unidade antes da escrita; 401/403 interrompe sem repetir `POST` ou
   `DELETE`. Preservar as assinaturas da fachada pública.
3. **Identificadores dos itens.** Antes da escrita, registrar em evento
   técnico os arrays ordenados `numerosItensAIncluir` e
   `numerosItensAExcluir`, identificados como planejados. Após convergência
   comprovada e commit, registrar `numerosItensIncluidos` e
   `numerosItensExcluidos` como confirmados. Em `PUBLICAR`, registrar nos logs
   os IDs publicados; em `RETIFICAR`, registrar as duas listas. Não registrar
   payloads completos. Um 403 deve deixar evidência dos IDs planejados, sem
   evento de IDs confirmados.
4. **Resultado público e Blank.** Acrescentar ao resultado unitário e ao JSON
   os arrays `numerosItensIncluidos` e `numerosItensExcluidos`. Eles ficam
   vazios salvo quando `RETIFICAR` termina como `CONCLUIDO`, e devem
   corresponder às quantidades já exibidas. A Blank apresenta então duas
   listas rotuladas “Itens incluídos” e “Itens excluídos”. Em `PUBLICAR`,
   `NENHUMA_ACAO`, bloqueios e falhas, não apresenta listas. Atualizar o
   contrato da `DI-010` e a especificação de logs.
5. **Diagnóstico de transporte e suporte.** Registrar duração da tentativa
   HTTP, `curl_errno` quando aplicável, categoria segura de falha e
   `status_http` somente quando houve resposta. Criar catálogo dos códigos
   públicos com significado, possível efeito, evidência e ação segura.
   Comparar `executionTimeMs` do backend com o tempo da requisição no
   navegador pela mesma correlação, sem atribuir causa sem evidência.
6. **Testes, pacote e implantação.** Cobrir por TDD sessão renovada, falha
   antes da escrita, 403 sem retry, listas planejadas versus confirmadas,
   retificação só com inclusão ou só com exclusão, publicação sem IDs na
   Blank e lista de até 1.000 IDs nos logs. Executar suítes relacionadas e
   completa, lint, autoload e verificação do pacote. Preparar rollback;
   implantar somente após autorização deste microciclo e verificar hashes e
   carregamento sem acionar **Publicar**.
7. **Smoke de 2027 e precheck de 2026.** Reconfirmar por leitura que 2027
   está convergente; executar a Blank uma vez, esperando `NENHUMA_ACAO`, sem
   escrita PNCP nem nova auditoria. Depois, produzir nova fotografia Oracle no
   DBeaver e inventário PNCP de 2026, demonstrando por unidade os itens a
   incluir/excluir e que a primeira operação ainda exige exclusão. Mudança da
   fotografia ou da decisão interrompe o avanço.
8. **Execução de 2026 e fechamento.** Após autorização específica, executar o
   fluxo normal uma única vez. Conferir resposta da Blank, IDs exibidos,
   eventos planejados/confirmados, itens PNCP, projeção, confirmação e
   auditoria Oracle. Se persistir 403 ou houver resultado incerto, parar,
   preservar evidências e encaminhar o diagnóstico; não declarar a correção
   comprovada nem repetir a operação. Registrar limitações e critérios de
   reabertura.

### Revisão posterior do Microciclo 2

A autenticação adicional descrita no Microciclo 2 foi uma hipótese experimental.
As tentativas reais demonstraram novo login HTTP 200 seguido de HTTP 403 tanto
em `DELETE` quanto em `POST`. Como a renovação não alterou a autorização, o
fluxo voltou a autenticar uma única vez por execução e a reutilizar a mesma
sessão em leituras, escritas e observações finais. O texto original do
microciclo permanece acima como registro da decisão então aprovada; esta revisão
é a decisão vigente.

## Critérios, riscos e adiamentos

- “Confirmado” significa convergência observada e transação Oracle concluída.
  IDs apenas planejados nunca aparecem na Blank como itens retificados. A
  confirmação descreve o estado observado, sem atribuir causalidade a uma
  requisição rejeitada.
- A mudança acrescenta campos ao resultado público unitário; os campos
  existentes permanecem.
- DBeaver é usado primeiro para consultas. DML manual depende de problema
  concreto, roteiro específico e autorização de microciclo próprio.
- Contatos reais de suporte não serão inventados. Sua validação permanece
  pendência aceita.
- A diferença entre tempo do navegador e do backend só pode ser medida quando
  ambos pertencem à mesma correlação. A fotografia histórica preserva apenas
  `executionTimeMs = 6452`, sem tempo correspondente do navegador.
- A Sprint 9.2 permanece fora do ciclo. A alteração local preexistente em
  `docs/vigentes/apoio/arquitetura.md` é preservada.
- Não criar commit sem pedido explícito. O arquivamento de release exige
  worktree limpo e commit identificável; esse gate não será contornado.

## Microciclo 6 — pacote candidato e rollback preparado

Em 25/09/2026, o pacote candidato local foi gerado em
`build/sprint95-candidate` e recebeu `PACOTE_VALIDADO`: 196 arquivos com hashes
verificados, 197 entradas totais, sem dependências de desenvolvimento nem
configurações ou segredos, com as duas fachadas carregáveis. SHA-256 do
`PACKAGE-MANIFEST.json`:
`AF6734C5DBE81B7AE3C84DE44E5C245A47810FEE21788ECCBE7283931DD26B17`.
Esse pacote reflete a árvore de trabalho ainda não commitada; não é o arquivo
de release para transferência.

Antes da troca em produção, registrar o ID e o hash da release ativa, preservar
seu diretório completo e uma cópia do evento `onExecute` da Blank. O arquivo
novo só pode ser transferido após `package:archive` passar com commit limpo;
conferir seu SHA-256 no destino. Extrair em diretório candidato separado,
validar topologia, hashes e autoload como `www-data`, e só então trocar os
diretórios conforme o [guia de implantação e rollback](../../../docs/vigentes/apoio/guia_de_implantacao_e_rollback.md).
Atualizar e compilar a Blank, abrir somente a interface e não acionar
**Publicar** neste microciclo.

Se a candidata falhar, retirar seu diretório ativo, restaurar o diretório
anterior e a versão anterior do evento da Blank, verificar novamente hash e
autoload e preservar a candidata rejeitada para diagnóstico. O rollback de
arquivos não reverte efeitos Oracle ou PNCP; por isso não deve executar o
fluxo funcional durante a implantação. A identificação da release anterior,
o hash do arquivo novo no servidor e a conferência de carregamento estão
registrados no resultado abaixo.

## Resultado do Microciclo 6

O commit `861d9ef` foi criado com 33 arquivos da Sprint 9.5, preservando fora
dele as alterações preexistentes em `arquitetura.md` e `glossario.md`. A suíte
completa passou com 976 testes e 4.694 asserções; os contratos relacionados,
lint de 434 arquivos PHP, autoload PSR-4 e verificação do pacote passaram.
O arquivo de release `pca-pncp-backend_20260925_861d9ef.tar.gz` foi gerado de
worktree limpa e validado com SHA-256
`b945b73c49b13774b32407f2abeb6fdf42e2c24c8c45ce6454333725e0f82185`.

Em produção, o mantenedor confirmou o mesmo SHA-256 após a transferência. O
manifesto da release anterior tinha SHA-256
`6ff0b8d4118d86d24f340a745a499277a6de7ae61cb09387b81cafb1a3e192ba`
e seu diretório foi preservado como
`pca-pncp-backend-anterior-sprint95-6ff0b8d4`. Após a troca, o manifesto
ativo apresentou SHA-256
`1f86934fbbe36d676cc5763b63a9e9c074f523520f7582f3b67fc8d7214f40df`.
O mantenedor informou que o backend carregou como `www-data` e guardou uma
cópia do evento `onExecute` anterior. A Blank compilou e abriu pela interface,
mas a execução posterior mostrou que a publicação da nova versão havia falhado
por uso de usuário incorreto e que a Blank antiga continuava ativa. A versão
correta foi publicada e compilada durante o Microciclo 7. Nenhum clique em
**Publicar** foi realizado como teste de implantação no Microciclo 6.

## Evidências parciais do Microciclo 7

A fotografia Oracle de 2027 em
`build/fotografia_oracle_sprint95_microciclo7.json` manteve a estratégia
`INDIVIDUAL`, os três planos e todos os 452 registros de demandas idênticos à
fotografia anterior. As leituras PNCP em `build/lista_unidades_2027.json` e
`build/itens_2027.json` encontraram apenas UASG 205/sequencial 2 (260 itens),
207/sequencial 3 (9 itens) e 210/sequencial 4 (165 itens); os conjuntos exatos
de `numeroItem` coincidiram com os `ID_DEMANDA` ativos, sem duplicidade.

O roteiro previa uma execução da Blank. A primeira, correlação
`4f7b548d-2120-4e32-a886-7f46f20028db`, usou a Blank antiga e concluiu as
três unidades como `NENHUMA_ACAO`, com o procedimento 8 de 2027 ainda em uma
linha antes e depois. Após corrigir a implantação da Blank, a execução
`00a67211-2dba-4a95-9a18-16ca0c69e879` terminou com a UASG 205 em
`FALHA_RECONCILIAVEL`: duas leituras expiraram com `curl_errno=28`, sem
resposta HTTP. O mantenedor executou novamente, fora da quantidade prevista;
em `a66fd7d8-796a-497c-8189-50df0775916d`, as três unidades ficaram em
`NENHUMA_ACAO`. Esta última execução também teve um timeout na leitura da
UASG 210, resolvido pela segunda tentativa de `GET`. Os logs dessas duas
correlações não mostram `POST` nem `DELETE`. A Blank nova apresentou os arrays
públicos vazios. O mantenedor confirmou que a contagem de auditoria de 2027
permaneceu em uma linha do procedimento 8 e nenhuma dos procedimentos 5 e 9
após as execuções. A falha transitória não comprova sua causa de rede ou PNCP;
os tempos do navegador e do backend não foram comparados entre correlações
distintas. Não repetir a Blank de 2027.

A nova fotografia Oracle e o inventário PNCP de 2026 foram produzidos somente
por leitura. O resultado está registrado abaixo.

## Resultado do Microciclo 7 — precheck de 2026

A fotografia `build/fotografia_oracle_sprint95_microciclo7_2026.json`
(SHA-256 `92143edc15d0a569a219182ba3611661014bfa3412c5f1ee498ed04da74919a2`)
mantém exatamente a estratégia `INDIVIDUAL`, os procedimentos 5/8/9, os 112
planos e as 112 demandas de 2026 da fotografia anterior. O inventário PNCP
`build/lista_unidades_2026.json`
(SHA-256 `137d70fca947540ed53e9cc6e67deaa3ab71ba54f90633b73e7a603033777711`)
lista exatamente os três PCAs vinculados. `build/itens_2026.json`
(SHA-256 `b8a9a6a363807d52bebb87e745480e3d0bc6a541414bc02552274fc9aeaa0877`)
contém os três conjuntos completos de itens, com identidades consistentes,
`numeroItem` inteiro positivo e nenhuma duplicidade.

As situações de Demanda `2`, `3` e `5` participam da fotografia vigente,
conforme `SituacaoDemanda`. A diferença calculada a partir **deste export
incompleto**, que não deve servir de autorização para escrita, foi:

| UO / UASG / sequencial | Itens PNCP | Incluir | Excluir | Manter | Decisão produzida, insegura |
| --- | ---: | ---: | ---: | ---: | --- |
| 149 / 205 / 4 | 208 | 71 | 208 | 0 | `RETIFICAR` |
| 3 / 207 / 2 | 5 | 3 | 5 | 0 | `RETIFICAR` |
| 94 / 210 / 3 | 153 | 38 | 153 | 0 | `RETIFICAR` |

Os conjuntos exportados para este precheck não possuem interseção com os itens
PNCP, por isso a decisão efetiva do backend foi excluir todos os itens atuais e
incluir apenas os IDs do export. **Essa decisão não está validada como correta.**
O mantenedor verificou no SGI que `411`, `412`, `413`, `418` e `419` da UASG 207
continuam homologadas e são desejadas, junto com `814`, `879` e `912`. Para a
UASG 205, confirmou que os 208 itens normais publicados continuam desejados e
devem ser mantidos; os 71 itens extraordinários devem ser acrescentados,
resultando em 279 itens desejados e nenhuma exclusão conhecida. Os IDs normais
antigos não aparecem no export de `SGI.PAC_DEMANDA` unido a
`SGI.PAC_PLANOS` por `FK_PLANO`, usado neste precheck. Portanto, as quantidades
de exclusão desta tabela são apenas o resultado da fotografia exportada; não
representam cancelamentos homologados comprovados. Pela `RN-PLA-004`, a
fotografia vigente deve conter as demandas de todas as versões que continuem
vigentes. A leitura direta dos IDs está registrada abaixo; ainda é necessário
comprovar a fonte de exercício e UO das demandas normais antes de qualquer nova
escrita.

O diagnóstico direto no banco `sgi`, serviço `sgi`, esquema `SGI`, comprovou
`SITUACAO = 2` e `FK_PLANO = NULL` para as amostras normais `411`, `412`, `413`,
`414`, `415`, `418`, `419` e `434`. As amostras extraordinárias `795`, `796`,
`797`, `814`, `879` e `912` possuem `FK_PLANO` preenchido e se ligam a Planos de
2026 das respectivas UOs. O `JOIN` obrigatório por `FK_PLANO` na reconstrução
Oracle omite as demandas normais. O vínculo alternativo que identifica com
segurança exercício e UO dessas demandas ainda não foi comprovado; não basta
trocar `JOIN` por `LEFT JOIN` sem essa regra de pertencimento.
O teste `OraclePlanoRepositoryTest::testDeveReconstruirMaiorVersaoComDemandasVigentesDeTodasAsOrigens`
afirma textualmente esse `JOIN` e usa linhas simuladas já retornadas pela
consulta; por isso não detectou a omissão de registros com `FK_PLANO` nulo.

Na correlação `74936623-5300-4392-b67c-6383d1e70cd8`, a lista planejada
para exclusão é exatamente o conjunto completo dos 208 itens PNCP da UASG 205.
Se esse `DELETE` fosse aceito, a justificativa gerada pelo backend atribuiria
os 208 itens a "cancelamentos homologados no SGI", incompatível com a situação
de negócio informada pelo mantenedor. O HTTP 403 interrompeu a execução. O
mantenedor confirmou que as quantidades PNCP permanecem 5, 153 e 208 para as
UASGs 207, 210 e 205, respectivamente; não será solicitada nova leitura apenas
para reconfirmar essas contagens.

Os arrays ordenados exatos de itens a incluir e excluir em cada unidade são
iguais aos da [avaliação pós-vínculo da Sprint 9.4](../../../tests/Contract/Sprint94/avaliacao_pos_vinculo_2026_2027.json),
mas ambas as avaliações usam a mesma premissa incompleta sobre o conjunto
desejado. A igualdade entre elas não valida a regra de negócio.
Com a fotografia incompleta, o executor enviaria `DELETE` antes de `POST`; a
primeira tentativa real recebeu 403 nesse `DELETE`. Essa sequência não
representa a mudança de negócio correta informada pelo mantenedor.
Este precheck não executou a Blank de 2026 nem autoriza sua execução sem o
Microciclo 8. Se a fotografia mudar antes da execução, refazer o diagnóstico
e interromper o avanço.

## Microciclo 8 — referência anterior à execução

Após a autorização específica do Microciclo 8, o mantenedor executou no
DBeaver o `SELECT` de `SGI.PAC_HISTORICOS` filtrado pelo exercício 2026 e pelos
procedimentos 5, 8 e 9. A consulta com `GROUP BY` retornou nenhuma linha, o
que estabelece zero auditorias correspondentes antes da execução da Blank.
A execução funcional de 2026 ainda não havia sido realizada nesse ponto.

## Microciclo 8 — bloqueio observado em produção

O mantenedor informou duas tentativas de acionamento da Blank em 25/09/2026.
A primeira terminou após cerca de 30 segundos com mensagem de falha na
autenticação; sua correlação e seus eventos ainda não foram fornecidos. Portanto,
não há evidência para classificar a causa desse primeiro erro como timeout,
resposta HTTP ou recusa de credenciais. A segunda tentativa retornou
`INTERROMPIDA`, sem resultados unitários, com código
`PNCP_EXCLUSAO_ITENS_NAO_AUTORIZADA`, uma unidade visitada de três e correlação
`74936623-5300-4392-b67c-6383d1e70cd8`.

Nos 16 eventos fornecidos dessa correlação, o login inicial e o novo login após
a decisão da UASG 205 receberam HTTP 200. Após o novo login, o evento
`REGISTRAR_ITENS_PLANEJADOS` apresentou 71 `numeroItem` para incluir e 208 para
excluir, em arrays ordenados exatamente iguais aos da fotografia prévia da UASG
205/sequencial 4. O primeiro `DELETE` recebeu resposta HTTP 403 em 13.706 ms,
classificada `HTTP_4XX`, sem `curl_errno`. Não houve `POST` de inclusão nem evento
`REGISTRAR_ITENS_CONFIRMADOS`. As sete tentativas HTTP registradas somaram
135.938 ms, frente a `executionTimeMs = 136.042` da execução; a maior parte do
tempo medido no backend foi gasta nas chamadas PNCP. Esses dados comprovam que
a sessão renovada não foi suficiente para autorizar este `DELETE`; não identificam
qual regra ou componente do PNCP produziu o 403.

A correção do bloqueio de 2026 não foi comprovada. O mantenedor confirmou que
as contagens PNCP permanecem 5, 153 e 208. A projeção e confirmação Oracle de
2026 e a auditoria dos procedimentos 5/8/9 não foram reconferidas após a
tentativa. Preservar as respostas e os eventos. Não repetir a Blank,
o `DELETE` ou executar DML manual. A comparação com uma exclusão manual
anterior exige confirmar identidade da conta, ambiente, CNPJ, ano, sequencial,
lista de itens, justificativa e origem de rede; o sucesso de outra chamada não
comprova autorização para este lote.

## Saneamento legado informado após a interrupção

O mantenedor identificou que as demandas normais legadas de 2026 precedem o
versionamento dos planos e por isso tinham `FK_PLANO` nulo. A migration
`V202609251823__vincular_demandas_normais_legadas_2026.sql` foi preparada para
vincular os 366 IDs normais já publicados aos planos de versão 1 de suas UOs.
Em 25/09/2026, o mantenedor a executou em produção e conferiu os dados no
banco, com resultado conforme. Em 28/09/2026, confirmou que o arquivo executado
é exatamente o arquivo do repositório, cujo SHA-256 é
`47309C11DEA3E017B7AC129ACBC13CFFFBC9E53CAE2A3E245428B5BE153F2F9F`.
A migration aplicada não será editada.

A fotografia anterior e a decisão que excluía todos os itens publicados
continuam inválidas como precheck. Antes de retomar a execução da Blank de 2026,
produzir nova fotografia Oracle somente leitura, reconstruir o conjunto
desejado e conferir por unidade as inclusões e exclusões. A aplicação da
migration, isoladamente, não demonstra que a decisão agora é segura nem resolve
a causa do HTTP 403 observado anteriormente.

O novo precheck somente leitura está em
`database/queries/sprint95_precheck_pos_migration_2026.sql`. Ele confirma os
366 vínculos, reconstrói as quantidades desejadas, exporta os 478 IDs para
comparação exata com o inventário PNCP e verifica que a migration não criou
auditoria de publicação. A Blank permanece suspensa até esse precheck resultar
em 71, 3 e 38 inclusões e zero exclusões, respectivamente para as UASGs 205,
207 e 210.

Em 28/09/2026, o mantenedor executou o precheck em produção. O ambiente foi
confirmado como banco e serviço `sgi`, esquema `SGI`. Os 366 vínculos normais
resultaram `CONFORME`: 208 na UASG 205, 5 na 207 e 153 na 210. A fotografia
desejada também resultou `CONFORME`: 279, 8 e 191 itens, compostos por 208/71,
5/3 e 153/38 itens normais/extraordinários. O export foi preservado em
`build/demandas_desejadas_2026_pos_migration.json`, SHA-256
`30CD6CA3B8220527E8DDC935D432745A38CE7785B52D0A55EFAC5FDF8E8F79DA`.

A comparação exata dos 478 pares distintos `UASG + numeroItem` com
`build/itens_2026.json` comprovou somente inclusões: 71 na UASG 205, 3 na 207 e
38 na 210; nenhuma exclusão. A consulta de `PAC_HISTORICOS` não retornou linhas
dos procedimentos 5, 8 ou 9 para 2026, confirmando que a migration não gerou
auditoria de publicação. O precheck pós-migration está aprovado. A Blank ainda
não foi acionada após esse saneamento.

Em 28/09/2026, o mantenedor autorizou explicitamente uma única execução da
Blank para 2026 após o precheck aprovado. A decisão esperada nas três unidades
é `RETIFICAR`, somente com `POST`: UASG 205 com 71 inclusões, UASG 207 com 3 e
UASG 210 com 38. Qualquer exclusão planejada, `DELETE`, 401/403, falha de
autenticação ou resultado incerto interrompe o procedimento sem nova tentativa.

O mantenedor informou posteriormente que acionou a Blank três vezes e recebeu
em todas “A inclusão de itens no PNCP não foi autorizada”. A resposta fornecida,
correlação `2b433428-88ab-4cd5-99db-39bccefcf966`, ficou `INTERROMPIDA`, visitou
uma de três unidades e deixou duas não processadas. Nos 16 eventos anexados,
seis chamadas HTTP receberam 200 e o `POST` de inclusão da primeira unidade
recebeu 403 em 109 ms; não houve `DELETE`. A decisão planejada permaneceu
`RETIFICAR`, agora com 73 inclusões e zero exclusões; os IDs 919 e 937 explicam
a diferença em relação ao precheck anterior de 71 itens.

O artefato protegido da ordem HTTP 7 comprovou a requisição ao ambiente de
produção, o Bearer presente e o payload de 73 itens. O PNCP devolveu
explicitamente `Operação não autorizada. Somente o usuário que publicou a
informação pode editá-la.` O JWT possui `idBaseDados` 397 e `sub/login`
`fc904958-e3e8-48b1-b16d-9cdfd1c46d4c`, iguais ao usuário atual consultado no
PNCP. A causa externa fica identificada: o PNCP não reconhece esse usuário como
publicador do PCA 2026/sequencial 4. A correção funcional continua não
comprovada e nenhuma repetição foi autorizada.

Após a investigação, o mantenedor decidiu confiar no conteúdo de erro do PNCP
para suporte interno e revisar a política de observabilidade. O mecanismo de
arquivo opt-in foi removido na mesma refatoração. Respostas HTTP
`3xx/4xx/5xx`, exceto autenticação, passam a registrar requisição, resposta,
conexão e cURL reproduzível em `metadata.diagnosticoHttp`; Bearer, cookies e
`Set-Cookie` permanecem redigidos. O código público não muda e a mensagem da
Blank acrescenta `O PNCP informou: ...` quando o corpo JSON traz `message` ou
`mensagem`. Esta implementação não autoriza nova execução. O Microciclo 8 não
está concluído.
