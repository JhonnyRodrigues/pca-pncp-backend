Síntese proposta para a DI-003:

    1. PUBLICAR_EM_UNICA_UASG e UASG_CONSOLIDADORA vêm de PAC_CONFIGURACOES, acessada por porta da Application.
    2. Configuração ausente ou inválida bloqueia a execução antes de qualquer comunicação com o PNCP; não existe valor padrão.
    3. Modo NÃO: unidade de processamento é AnoExecucao + UnidadeOrcamentaria; cada UO possui transação e resultado independentes.
    4. No modo NÃO, UASG repetida entre UO do mesmo exercício é erro de configuração bloqueante.
    5. Modo SIM: unidade de processamento é AnoExecucao + UASG_CONSOLIDADORA; existe uma reconciliação e um resultado conjuntos.
    6. O estado consolidado é a união completa das Demandas homologadas ativas de todos os Planos do exercício.
    7. Uma UO vazia remove apenas seus itens; o PCA consolidado é excluído somente quando a união inteira fica vazia.
    8. A exclusão total usa a justificativa do cancelamento homologado que retirou o último item ativo.
    9. Após a primeira publicação, modo e UASG consolidadora ficam congelados para o exercício; mudanças exigem migração assistida. Exercícios novos podem usar outra estratégia.
    10. ID_DEMANDA é globalmente único e continua candidato a numeroItem, sujeito a teste contratual no ambiente de homologação do PNCP.
    11. O agrupamento pertence à Application e não modifica a identidade nem a composição dos Aggregates Plano.
    12. No modo consolidado, falhas parciais entre operações de itens pertencem à unidade inteira e serão detalhadas na próxima decisão sobre transações e falhas parciais.

