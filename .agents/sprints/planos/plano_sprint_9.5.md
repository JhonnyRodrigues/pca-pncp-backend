# Sprint 9.5 — Prontidão de produção e suporte

**Status:** plano aprovado; Microciclos 1 a 5 concluídos localmente. O
Microciclo 6 foi autorizado pelo mantenedor. Cada microciclo termina com pausa
e o próximo exige autorização explícita.

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
o hash do arquivo novo no servidor e a conferência de carregamento ainda
dependem de acesso ao ambiente de produção.
