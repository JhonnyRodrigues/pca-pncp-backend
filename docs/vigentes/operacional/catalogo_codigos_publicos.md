# Catálogo operacional dos códigos públicos

- **Classificação documental:** Operacional
- **Status:** Vigente
- **Escopo:** triagem da resposta da Blank e dos eventos pela mesma correlação

## Uso pelo suporte

Leia primeiro `situacao`, `codigoFalha`, `correlacao` e os resultados unitários.
Use a [consulta por correlação](../../../database/queries/seguranca_log_por_correlacao.sql)
para verificar a sequência dos eventos.

`BLOQUEADO` e `FALHA_RECONCILIAVEL` preservam a unidade afetada; `INTERROMPIDA` encerra a
varredura e pode preservar resultados de unidades anteriores. Contagens e IDs
planejados não comprovam escrita. Em qualquer falha após possível escrita,
confira PNCP e Oracle antes de decidir uma nova execução. Eventos HTTP de erro
podem conter payload e resposta integrais em `metadata.diagnosticoHttp`; trate
seus exports como restritos e nunca copie credenciais para um chamado.

Os grupos abaixo reúnem códigos com a mesma conduta operacional. Nas células
com `{operação}`, substitua por `INCLUSAO_ITENS`, `EXCLUSAO_ITENS` ou
`EXCLUSAO_PCA`; são os três nomes usados pelo escritor PNCP. O código exato
da resposta prevalece sobre uma interpretação apenas pelo prefixo.

| Código público | Significado e possível efeito | Evidência e ação segura |
| --- | --- | --- |
| `ENTRADA_HTTP_INVALIDA`, `CNPJ_INVALIDO`, `ANO_EXECUCAO_INVALIDO`, `REQUEST_ID_INVALIDO` | Entrada rejeitada antes da varredura; nenhuma escrita dessa execução é comprovada. | Conferir método, JSON, exercício e correlação da resposta; corrigir a entrada ou a configuração do servidor. `REQUEST_ID_INVALIDO` não tem correlação técnica fabricada. |
| `FALHA_INESPERADA`, `RESPOSTA_INVALIDA` | Falha capturada na fronteira ou resposta da Blank não interpretável; o efeito anterior pode existir. | Preservar resposta e horário sem dados sensíveis; localizar eventos e consultar o estado antes de repetir. |
| `AUTENTICACAO_PNCP_FALHOU`, `PNCP_AUTENTICACAO_NAO_AUTORIZADA` | A autenticação única da execução falhou antes do processamento das unidades. | Conferir na correlação o login e `status_http`; validar conta, permissão e ambiente com o responsável. |
| `PNCP_AUTENTICACAO_TRANSPORTE_FALHOU`, `PNCP_AUTENTICACAO_RESPOSTA_INVALIDA`, `PNCP_AUTENTICACAO_CREDENCIAIS_INVALIDAS`, `SESSAO_PNCP_INCONSISTENTE` | A sessão única não pôde ser obtida ou validada. | Usar o evento de login e o diagnóstico de transporte; corrigir o perfil ou investigar o PNCP antes de nova execução. |
| `PNCP_{operação}_NAO_AUTORIZADA`, `PNCP_PUBLICACAO_NAO_AUTORIZADA` | Resposta 401/403 em escrita; unidade interrompida, sem retry mutante. | Ler o detalhe do PNCP na mensagem da Blank e em `diagnosticoHttp`; verificar PNCP e Oracle antes de acionar suporte de acesso. O status isolado não prova a causa quando o serviço não trouxer detalhe. |
| `PNCP_{operação}_REJEITADA`, `PNCP_PUBLICACAO_REJEITADA` | PNCP rejeitou comando com resposta definitiva de validação. | Consultar a mensagem externa e o diagnóstico HTTP; corrigir dados ou requisito identificado, sem forçar repetição. |
| `PNCP_{operação}_RESULTADO_INCERTO`, `PNCP_PUBLICACAO_RESULTADO_INCERTO`, `PNCP_RESPOSTA_INCONCLUSIVA`, `RESULTADO_INCERTO` | O efeito externo não foi determinado com segurança. | Comparar inventário PNCP, projeção e confirmação Oracle; aplicar o runbook de recuperação, sem reenviar a escrita às cegas. |
| `PNCP_{operação}_TRANSPORTE_FALHOU`, `PNCP_PUBLICACAO_TRANSPORTE_FALHOU`, `PNCP_LEITURA_TRANSPORTE_FALHOU`, `PNCP_LEITURA_FALHOU` | Falha de transporte ou leitura externa; escrita pode ser incerta conforme a operação. | Ver `categoriaFalha`, `curl_errno`, `duracaoTentativaMs` e presença de `status_http`; confirmar o estado antes de qualquer repetição mutante. |
| `PNCP_{operação}_PREPARACAO_FALHOU`, `PNCP_PUBLICACAO_PREPARACAO_FALHOU`, `PNCP_{operação}_SESSAO_INVALIDA`, `PNCP_PUBLICACAO_SESSAO_INVALIDA`, `PNCP_LEITURA_SESSAO_INVALIDA` | Comando não foi preparado ou sessão local é inválida. | Conferir eventos anteriores da unidade e configuração; encaminhar ao mantenedor. Não corrigir dados Oracle manualmente por inferência. |
| `PNCP_IDENTIDADE_DIVERGENTE`, `CANDIDATO_EXTERNO_SEM_VINCULO`, `CANDIDATO_APOS_ESCRITA_SEM_VINCULO`, `BUSCA_CANDIDATOS_INDEFINIDA`, `BUSCA_CANDIDATOS_FINAL_INDEFINIDA` | Identidade externa não coincide ou vínculo não foi comprovado. | Conferir CNPJ, ano, UASG, sequencial e vínculos por leitura; preservar o bloqueio até decisão consciente. |
| `OBSERVACAO_PNCP_FALHOU`, `OBSERVACAO_PNCP_INCONSISTENTE`, `OBSERVACAO_PNCP_INDEFINIDA`, `OBSERVACAO_FINAL_FALHOU`, `OBSERVACAO_FINAL_INCONSISTENTE`, `OBSERVACAO_FINAL_INDEFINIDA`, `CONVERGENCIA_PENDENTE`, `CONVERGENCIA_NAO_COMPROVADA`, `RECONCILIACAO_NAO_CONVERGIU`, `DIVERGENCIA_FINAL_INCOMPATIVEL` | Leitura ou comparação final não confirmou o conjunto desejado. | Recolher fotografia PNCP e Oracle da mesma unidade, sem tratar contagens planejadas como itens concluídos. |
| `ESTADO_OPERACIONAL_CONFLITANTE`, `ESTADO_OPERACIONAL_ORACLE_CONFLITANTE`, `ESTADO_OPERACIONAL_INCONSISTENTE`, `CONVERGENCIA_UNIDADE_CONFLITANTE`, `DECISAO_DOMINIO_CONFLITANTE`, `ESTADO_PUBLICADO_SEM_SEQUENCIAL`, `SEQUENCIAL_PCA_AUSENTE` | Estado ou identidade local incompatível com uma decisão segura. | Conferir vínculos e projeções em leitura; encaminhar divergência ao mantenedor antes de escrita. |
| `UNIDADE_TRABALHO_BLOQUEADA`, `UNIDADE_EXTERNA_BLOQUEADA`, `TABELA_ANCORAS_INDISPONIVEL` | Exclusividade ou âncora Oracle impediu processamento. | Verificar sessão concorrente, bloqueio e pré-requisitos Oracle; aguardar liberação ou corrigir infraestrutura, sem DML improvisada. |
| `INICIO_UNIDADE_FALHOU`, `INICIO_UNIDADE_TRABALHO_FALHOU`, `UNIDADE_TRABALHO_JA_INICIADA`, `UNIDADE_TRABALHO_NAO_INICIADA`, `COMMIT_UNIDADE_FALHOU`, `CONFIRMACAO_UNIDADE_TRABALHO_FALHOU`, `DESFAZIMENTO_UNIDADE_FALHOU`, `DESFAZIMENTO_UNIDADE_TRABALHO_FALHOU` | Transação local não iniciou, não confirmou ou não foi desfeita com segurança. | Verificar estado da transação, logs Oracle e efeito PNCP; escalar antes de repetir. |
| `PERSISTENCIA_CONVERGENCIA_FALHOU`, `REGISTRO_ESTADO_OPERACIONAL_FALHOU`, `REGISTRO_ESTADO_OPERACIONAL_NAO_CONFIRMADO`, `REGISTRO_ESTADO_OPERACIONAL_EXIGE_TRANSACAO`, `REGISTRO_AUDITORIA_FALHOU`, `REGISTRO_AUDITORIA_EXIGE_TRANSACAO`, `CONSULTA_ESTADO_OPERACIONAL_FALHOU`, `CONSULTA_ESTADO_OPERACIONAL_EXIGE_TRANSACAO` | Projeção ou auditoria Oracle falhou; efeito PNCP pode ter ocorrido. | Conferir PNCP, projeção, confirmação e auditoria separadamente; seguir runbook de recuperação. |
| `ESTRATEGIA_ANUAL_DIVERGENTE`, `ESTRATEGIA_CORRENTE_AUSENTE`, `ESTRATEGIA_CORRENTE_INCONSISTENTE`, `ESTRATEGIA_CORRENTE_INVALIDA`, `ESTRATEGIA_ADOTADA_DIVERGENTE`, `ESTRATEGIA_ADOTADA_INVALIDA`, `UASG_CONSOLIDADORA_INVALIDA`, `DADOS_PARA_ADOCAO_ESTRATEGIA_INVALIDOS`, `ADOCAO_ESTRATEGIA_FALHOU`, `ADOCAO_ESTRATEGIA_NAO_CONFIRMADA`, `CONFIRMACAO_ESTRATEGIA_FALHOU`, `DESFAZIMENTO_ESTRATEGIA_FALHOU`, `CONSULTA_ESTRATEGIA_CORRENTE_FALHOU`, `CONSULTA_ESTRATEGIA_ADOTADA_FALHOU`, `INICIO_ESTRATEGIA_FALHOU`, `PREPARACAO_ESTRATEGIA_FALHOU` | Estratégia anual ausente, divergente ou não persistida. | Comparar configuração e planos do exercício; não alterar a estratégia em meio a uma publicação. |
| `LOGIN_USUARIO_AUSENTE`, `REPRESENTANTE_USUARIO_NAO_RESOLVIDO`, `LOTACAO_USUARIO_NAO_RESOLVIDA`, `RESOLUCAO_CONTEXTO_USUARIO_FALHOU`, `DADOS_REPRESENTANTE_INVALIDOS`, `DADOS_LOTACAO_INVALIDOS`, `CONTEXTO_USUARIO_NAO_RESOLVIDO`, `CONTEXTO_USUARIO_INCONSISTENTE`, `IDENTIDADE_PLANO_DUPLICADA`, `IDENTIDADE_PLANO_INVALIDA`, `PLANO_RECONSTRUIDO_INCOERENTE`, `RECONSTRUCAO_PLANO_FALHOU`, `DEMANDA_DE_INCLUSAO_NAO_LOCALIZADA`, `LIMITE_ITENS_PUBLICACAO_EXCEDIDO` | Contexto, plano ou demanda não satisfaz o contrato de entrada. | Conferir fotografias Oracle e regra aplicável; corrigir origem por processo próprio antes de nova execução. |
| `EXERCICIO_IDENTIDADE_INCOERENTE`, `UNIDADE_INDIVIDUAL_INCONSISTENTE`, `IDENTIDADE_INDIVIDUAL_DESAPARECEU`, `IDENTIDADES_CONSOLIDADAS_DESAPARECERAM`, `UASG_INDIVIDUAL_INCOERENTE`, `UASG_INDIVIDUAL_COMPARTILHADA`, `UO_PARTICIPANTE_DUPLICADA`, `VARREDURA_UNIDADE_DUPLICADA`, `VARREDURA_UNIDADES_INCONSISTENTE`, `VARREDURA_CONSOLIDADA_INCONSISTENTE`, `VARREDURA_SEM_UNIDADES`, `RESULTADO_UNIDADE_INCOERENTE`, `DESFECHO_UNIDADE_INCONSISTENTE` | Identidades ou resultados da varredura não formam um conjunto coerente. | Comparar unidades e UASGs esperadas com a fotografia Oracle; encaminhar divergência antes de escrever. |
| `PREPARACAO_PUBLICACAO_FALHOU`, `PREPARACAO_INTERROMPIDA`, `PREPARACAO_INCONSISTENTE`, `VARREDURA_INTERROMPIDA`, `PROCESSAMENTO_UNIDADE_FALHOU`, `DESPACHO_COMANDO_FALHOU`, `ESCRITA_PNCP_FALHOU`, `FINALIZACAO_UNIDADE_FALHOU`, `VARREDURA_PUBLICACAO_FALHOU`, `RECONCILIACAO_UNIDADE_FALHOU`, `RELEITURA_UNIDADE_FALHOU`, `DECISAO_DOMINIO_FALHOU`, `DADOS_AUDITORIA_INVALIDOS` | Falha agregada de etapa; o código sozinho não identifica o ponto externo. | Consultar eventos anteriores pela correlação e verificar efeitos PNCP e Oracle antes de ação. |

Códigos técnicos `PNCP_TRANSPORTE_*` aparecem na exceção interna; o código
público normalmente é o do adaptador PNCP correspondente. Se surgir um código
fora dos grupos, preservá-lo literalmente no chamado e revisar sua origem no
backend, sem inferir que é seguro repetir a operação.

Versões que continham a renovação experimental podem ter emitido
`AUTENTICACAO_PNCP_ANTES_ESCRITA_FALHOU` ou
`SESSAO_PNCP_ANTES_ESCRITA_INCONSISTENTE`. Esses códigos são legados e não são
emitidos pelo fluxo vigente.

## Comparação de duração pela correlação

Na fotografia salva para a execução informada como
`ee83c8a3-9323-464c-a24b-1dcea03f7f5c`, o último evento traz
`executionTimeMs = 6452` e a exclusão de itens recebeu HTTP 403. Essa
fotografia não contém o tempo da requisição medido pelo navegador. Portanto,
não há diferença numérica comprovada nem causa atribuível à rede, à Blank ou
ao backend.

Para uma comparação futura, registrar na aba Rede do navegador o tempo total
da requisição da Blank e o `correlacao` devolvido pelo mesmo POST; obter
`executionTimeMs` do último evento dessa correlação e, quando disponível,
`duracaoTentativaMs` dos eventos HTTP. Comparar os intervalos e horários sem
somar durações de tentativas paralelas ou equiparar tempo do navegador ao tempo
interno do backend.
