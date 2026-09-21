-- Dados de referência obrigatórios para a publicação consolidada do PCA.
-- Execute como script completo. Uma divergência causa rollback e interrompe.
-- Se o precheck retornar duas linhas CONFORME, registre o requisito como já
-- satisfeito e não execute esta migração apenas para produzir um COMMIT vazio.
-- A execução em produção depende do processo de implantação da Fase 9.

DECLARE
    PROCEDURE ASSEGURAR_CONFIGURACAO(
        P_CHAVE IN SGI.PAC_CONFIGURACOES.CHAVE%TYPE,
        P_VALOR IN SGI.PAC_CONFIGURACOES.VALOR%TYPE,
        P_DESCRICAO IN SGI.PAC_CONFIGURACOES.DESCRICAO%TYPE
    ) IS
        V_QUANTIDADE PLS_INTEGER;
        V_VALOR SGI.PAC_CONFIGURACOES.VALOR%TYPE;
        V_DESCRICAO SGI.PAC_CONFIGURACOES.DESCRICAO%TYPE;
    BEGIN
        SELECT COUNT(*),
               MAX(C.VALOR),
               MAX(C.DESCRICAO)
          INTO V_QUANTIDADE,
               V_VALOR,
               V_DESCRICAO
          FROM SGI.PAC_CONFIGURACOES C
         WHERE C.CHAVE = P_CHAVE;

        IF V_QUANTIDADE = 0 THEN
            INSERT INTO SGI.PAC_CONFIGURACOES (
                CHAVE,
                VALOR,
                DESCRICAO
            ) VALUES (
                P_CHAVE,
                P_VALOR,
                P_DESCRICAO
            );
        ELSIF V_QUANTIDADE <> 1
            OR NVL(V_VALOR, CHR(0)) <> NVL(P_VALOR, CHR(0))
            OR NVL(V_DESCRICAO, CHR(0)) <> NVL(P_DESCRICAO, CHR(0))
        THEN
            RAISE_APPLICATION_ERROR(
                -20001,
                'Configuracao divergente para a chave ' || P_CHAVE
            );
        END IF;
    END ASSEGURAR_CONFIGURACAO;
BEGIN
    ASSEGURAR_CONFIGURACAO(
        'ESTRATEGIA_PUBLICACAO_PLANOS',
        'CONSOLIDADO',
        'Aceita os valores: INDIVIDUAL ou CONSOLIDADO. Altera a decisão operacional da integração. No evento de publicação, consolida as demandas em uma única UASG antes de enviar os dados ao PNCP'
    );

    ASSEGURAR_CONFIGURACAO(
        'UASG_CONSOLIDADORA',
        '930281',
        'MSP - SERVIÇO MUN. ÁGUA E ESGOTO DE PIRACICABA'
    );

    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE;
END;
/
