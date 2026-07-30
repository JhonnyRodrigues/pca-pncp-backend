Síntese proposta para a DI-005
    1. Plano representa uma fotografia imutável do estado carregado; não será mutado nem recriado ao final do UseCase.
    2. O Domain continua responsável pelas decisões de processamento, mas a confirmação é uma ordem explícita da Application para o PlanoRepository.
    3. O Repository apenas persiste a confirmação já autorizada; não consulta PNCP nem decide se há convergência.
    4. PAC_PLANOS.DATA_PUBLICACAO significa “momento em que o SGI confirmou a convergência desta versão homologada”.
    5. No Domain, o campo passa a se chamar dataConfirmacaoSincronizacao; a coluna Oracle mantém o nome legado e será mapeada pelo adaptador.
    6. O valor é SYSDATE do Oracle, gravado na mesma transação da confirmação local.
    7. Uma nova versão nasce sem confirmação. Após convergência, somente as versões vigentes pendentes da unidade de processamento recebem a data; confirmações antigas não são sobrescritas.
    8. O número da versão não determina mais publicação inicial ou retificação.
    9. O Domain terá um estado tipado de publicação, conceitualmente NAO_PUBLICADO, PUBLICADO e EXCLUIDO.
    10. sequencialPca identifica a publicação ativa quando o estado é PUBLICADO; após exclusão, o último sequencial permanece como referência histórica.
    11. Plano vazio com publicação ativa decide EXCLUIR_PCA; após confirmação passa a EXCLUIDO.
    12. Nova Demanda homologada após exclusão decide PUBLICAR, cria outro PCA e recebe novo sequencial, sem apagar o histórico anterior.
    13. Em publicação inicial, o sequencial vem do recurso criado e é confirmado pela consulta final; em sincronizações ele permanece o mesmo.
    14. numeroControlePNCP continua metadado de integração, fora do Aggregate enquanto não houver necessidade de negócio documentada.
    15. Eventos e registros de auditoria gerados pela confirmação serão definidos na DI-006.