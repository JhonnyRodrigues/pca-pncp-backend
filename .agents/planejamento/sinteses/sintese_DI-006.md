A DI-006 — Auditoria e procedimento está concluída e aceita.
Regras de negócio
    • PAC_HISTORICOS registra somente fatos concluídos após convergência confirmada com o PNCP.
    • Tentativas e falhas ficam na observabilidade técnica.
    • NENHUMA_ACAO não gera histórico de negócio.
    • Procedimentos confirmados:
        ◦ 5 — PUBLICAÇÃO INICIAL DE PLANO
        ◦ 8 — RETIFICAÇÃO DE PLANO
        ◦ 9 — EXCLUSÃO DE PLANO NO PNCP
    • Gera-se um histórico por PCA externo:
        ◦ modo individual: um por PCA da UO;
        ◦ modo consolidado: um único registro para o PCA consolidado.
    • Responsável: usuário autenticado que acionou a operação.
    • Lotação: lotação desse usuário no momento da execução.
    • Mensagem padronizada com exercício, UO ou indicação de consolidado, UASG, sequencialPca e ação.
    • Retificações também informam quantidades agregadas de itens incluídos e excluídos, sem listar itens ou UOs.
Decisões arquiteturais
    • O Domain define os tipos de fatos auditáveis.
    • A Application materializa o evento e constrói a mensagem com o contexto completo.
    • O evento é um fato de negócio imutável, não uma Entity ou Aggregate Root.
    • ID_HISTORICO permanece exclusivamente técnico.
    • A porta de persistência pertence à Application.
    • Confirmação local, metadados e histórico participam da mesma transação Oracle.
    • A Infrastructure apenas traduz e persiste; não decide quando auditar nem compõe mensagens.