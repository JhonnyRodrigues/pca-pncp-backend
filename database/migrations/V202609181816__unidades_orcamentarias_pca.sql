-- Dados de referência das unidades orçamentárias participantes do PCA.
-- Execute como script completo. Uma divergência causa rollback e interrompe.
-- Se o precheck retornar quatro linhas CONFORME, registre o requisito como já
-- satisfeito e não execute esta migração apenas para produzir um COMMIT vazio.
-- A execução em produção depende do processo de implantação da Fase 9.

DECLARE
    PROCEDURE ASSEGURAR_UNIDADE(
        P_ID IN SGI.PAC_UNIDADES_ORCAMENTARIAS.ID_UNIDADE_ORCAMENTARIA%TYPE,
        P_DESCRICAO IN SGI.PAC_UNIDADES_ORCAMENTARIAS.DESCRICAO_UASG%TYPE,
        P_CODIGO IN SGI.PAC_UNIDADES_ORCAMENTARIAS.CODIGO_UASG%TYPE,
        P_ATIVA IN SGI.PAC_UNIDADES_ORCAMENTARIAS.ATIVA%TYPE
    ) IS
        V_QUANTIDADE PLS_INTEGER;
        V_QUANTIDADE_LOTACOES PLS_INTEGER;
        V_ID SGI.PAC_UNIDADES_ORCAMENTARIAS.ID_UNIDADE_ORCAMENTARIA%TYPE;
        V_DESCRICAO SGI.PAC_UNIDADES_ORCAMENTARIAS.DESCRICAO_UASG%TYPE;
        V_CODIGO SGI.PAC_UNIDADES_ORCAMENTARIAS.CODIGO_UASG%TYPE;
        V_ATIVA SGI.PAC_UNIDADES_ORCAMENTARIAS.ATIVA%TYPE;
    BEGIN
        SELECT COUNT(*)
          INTO V_QUANTIDADE_LOTACOES
          FROM SGI.RH_LOTACOES L
         WHERE L.COD_LOTACAO = P_ID;

        IF V_QUANTIDADE_LOTACOES <> 1 THEN
            RAISE_APPLICATION_ERROR(
                -20002,
                'Lotacao ausente ou duplicada para a unidade ' || P_ID
            );
        END IF;

        SELECT COUNT(*),
               MAX(U.ID_UNIDADE_ORCAMENTARIA),
               MAX(U.DESCRICAO_UASG),
               MAX(U.CODIGO_UASG),
               MAX(U.ATIVA)
          INTO V_QUANTIDADE,
               V_ID,
               V_DESCRICAO,
               V_CODIGO,
               V_ATIVA
          FROM SGI.PAC_UNIDADES_ORCAMENTARIAS U
         WHERE U.ID_UNIDADE_ORCAMENTARIA = P_ID
            OR U.CODIGO_UASG = P_CODIGO;

        IF V_QUANTIDADE = 0 THEN
            INSERT INTO SGI.PAC_UNIDADES_ORCAMENTARIAS (
                ID_UNIDADE_ORCAMENTARIA,
                DESCRICAO_UASG,
                CODIGO_UASG,
                ATIVA
            ) VALUES (
                P_ID,
                P_DESCRICAO,
                P_CODIGO,
                P_ATIVA
            );
        ELSIF V_QUANTIDADE <> 1
            OR V_ID <> P_ID
            OR NVL(V_DESCRICAO, CHR(0)) <> NVL(P_DESCRICAO, CHR(0))
            OR NVL(V_CODIGO, CHR(0)) <> NVL(P_CODIGO, CHR(0))
            OR NVL(V_ATIVA, CHR(0)) <> NVL(P_ATIVA, CHR(0))
        THEN
            RAISE_APPLICATION_ERROR(
                -20003,
                'Unidade orcamentaria divergente para o ID ' || P_ID
            );
        END IF;
    END ASSEGURAR_UNIDADE;
BEGIN
    ASSEGURAR_UNIDADE(1, 'Gabinete da Presidência', '204', 'S');
    ASSEGURAR_UNIDADE(3, 'Procuradoria Jurídica', '207', 'S');
    ASSEGURAR_UNIDADE(94, 'Diretoria Geral de Saneamento', '210', 'S');
    ASSEGURAR_UNIDADE(
        149,
        'Diretoria Geral Administrativa e Financeira',
        '205',
        'S'
    );

    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE;
END;
/

