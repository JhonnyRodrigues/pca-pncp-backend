# Sprint 9.4 — vínculo e conciliação de publicações legadas

Este diretório contém os contratos e artefatos temporários usados para localizar
publicações que já existem no PNCP, mas ainda não possuem vínculo operacional no
SGI.

## Por que o vínculo é necessário

Uma publicação pode existir corretamente no PNCP e, ainda assim, o SGI não
conhecer sua identidade externa. Para o backend, a identidade de um PCA é
formada pelo exercício, modo de publicação, UASG e sequencial retornado pelo
PNCP.

Quando os campos operacionais de `SGI.PAC_PLANOS` estão vazios, o backend não
tem como afirmar que um candidato encontrado no PNCP pertence ao SGI. A adoção
automática é intencionalmente proibida: um PCA desconhecido pode ter sido criado
por outro processo ou usuário. Por isso, sem o vínculo assistido, o fluxo normal
interrompe a operação em vez de reconciliar ou criar uma possível duplicata.

O vínculo assistido registra somente a identidade externa cuja procedência foi
comprovada. Depois dele, o fluxo normal consegue observar o PCA conhecido,
comparar os `ID_DEMANDA` vigentes com os `numeroItem` existentes e decidir entre
`NENHUMA_ACAO`, `RETIFICAR` ou `EXCLUIR_PCA`.

## O que o vínculo altera

Somente estas quatro colunas da projeção operacional em `SGI.PAC_PLANOS` podem
ser preenchidas:

- `ESTRATEGIA_ADOTADA_PUBLICACAO`;
- `UASG_ADOTADA`;
- `ESTADO_PUBLICACAO`;
- `SEQUENCIAL_PUBLICACAO_PNCP`.

O vínculo não publica, retifica ou exclui dados no PNCP. Também não altera
`DATA_PUBLICACAO`, Demandas, históricos, auditorias ou controles de
concorrência. A confirmação das versões vigentes e as alterações externas
pertencem ao fluxo normal, executado somente depois de uma avaliação
pós-vínculo.

Todas as versões do Plano pertencentes à mesma unidade externa recebem uma
projeção uniforme. Isso é necessário porque o adapter Oracle interpreta o
estado anual da UO a partir do conjunto das versões e rejeita projeções
parciais ou divergentes.

## Caso tratado em 2026

O inventário somente leitura de estratégia `INDIVIDUAL`, com SHA-256
`7823d46dae06aff72119d2c1d2262afa2c4af82e24e28ae2ce69d1d296345975`, e a
confirmação humana de procedência identificaram:

| UO | UASG | Sequencial PNCP | Linhas esperadas |
| ---: | ---: | ---: | ---: |
| 3 | 207 | 2 | 3 |
| 94 | 210 | 3 | 38 |
| 149 | 205 | 4 | 71 |

O script específico desse caso está em
[`checkpoints/vincular_legados_individuais_2026.sql`](checkpoints/vincular_legados_individuais_2026.sql).
Ele contém os `ID_PLANO` exatos daquela fotografia, bloqueia as linhas, exige
que os quatro campos estejam vazios, confere a cardinalidade, executa o `UPDATE`
e faz o pós-check. Não existe `COMMIT` automático.

No DBeaver, o auto-commit deve permanecer desativado. Se o pós-check retornar
exatamente os três grupos e as cardinalidades da tabela acima, o operador pode
executar o `COMMIT` manual. Qualquer erro ou diferença exige `ROLLBACK` e novo
diagnóstico.

## Como realizar outra conciliação no futuro

O SQL de 2026 é específico e não deve ser reutilizado para outro exercício,
outra UASG, outro sequencial ou outra fotografia de planos. Uma nova
conciliação deve seguir esta ordem:

1. Executar inventário somente leitura do Oracle e do PNCP, com paginação
   completa e relatório sanitizado.
2. Confirmar manualmente a procedência de cada candidato. Candidato
   desconhecido ou ambíguo deve ser preservado e bloquear apenas seu caso.
3. Confirmar que a estratégia corrente corresponde à topologia externa. Uma
   mudança entre `INDIVIDUAL` e `CONSOLIDADO` exige tratamento assistido antes
   do vínculo.
4. Registrar o hash do inventário e mapear exercício, UO, UASG, sequencial,
   `ID_PLANO` e cardinalidade esperada.
5. Gerar um novo SQL específico, com fotografia anterior, `FOR UPDATE`, campos
   operacionais inicialmente nulos, IDs exatos, cardinalidade, pós-check,
   `ROLLBACK` em falha e sem `COMMIT` automático.
6. Executar com auto-commit desativado e sem usuários acionando o fluxo normal.
7. Conferir o pós-check antes do `COMMIT`. Resultado diferente exige
   `ROLLBACK`; não se deve ajustar o SQL durante a transação para “fazê-lo
   passar”.
8. Repetir o inventário por leitura e calcular a diferença de pertencimento
   entre `ID_DEMANDA` e `numeroItem`.
9. Executar a aplicação normal uma única vez somente quando a decisão esperada
   estiver demonstrada. Falha ou resposta incerta não autoriza repetição
   automática.

Diferença entre os itens atuais e os itens do PCA legado não invalida, sozinha,
a procedência. O vínculo identifica o recurso externo; a reconciliação posterior
é que remove itens antigos e inclui os itens vigentes. A V1 compara
pertencimento por identificador e não compara todos os atributos item a item.

## Pré-requisitos aprendidos após a reconciliação

Uma retificação real de 2027 recompôs três itens no PNCP, mas a transação local
foi interrompida ao tentar registrar o procedimento `8` em `PAC_HISTORICOS`.
O catálogo de produção possuía somente o procedimento `5`; também faltava o
procedimento `9`, necessário para uma futura exclusão integral confirmada.

As migrations `V202609241700` e `V202609241701` passam a tratar esses
pré-requisitos: a primeira assegura os procedimentos 8 e 9; a segunda registra
a estratégia `INDIVIDUAL` sem alterar a migration consolidada já aplicada. A
ausência do histórico daquela retificação foi aceita pelo mantenedor e não deve
ser preenchida retroativamente. O objetivo é garantir que fatos futuros sejam
registrados pelo fluxo normal dentro da própria transação.

Para diagnosticar outra execução, use
`database/queries/seguranca_log_por_correlacao.sql` com o UUID retornado pela
Blank. `STATUS_HTTP` nulo significa que não houve resposta HTTP comprovada; não
deve ser interpretado como um código omitido.
