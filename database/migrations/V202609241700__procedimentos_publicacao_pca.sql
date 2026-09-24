-- Dados de referência obrigatórios para a auditoria de retificação e exclusão.
-- Execute como script completo. Uma divergência causa rollback e interrompe.
-- Se o precheck retornar duas linhas CONFORME, registre o requisito como já
-- satisfeito e não execute esta migração apenas para produzir um COMMIT vazio.

DECLARE
    PROCEDURE ASSEGURAR_PROCEDIMENTO(
        P_ID IN SGI.PAC_PROCEDIMENTOS.ID_PROCEDIMENTO%TYPE,
        P_NOME IN SGI.PAC_PROCEDIMENTOS.PROCEDIMENTO%TYPE
    ) IS
        V_QUANTIDADE PLS_INTEGER;
        V_ID SGI.PAC_PROCEDIMENTOS.ID_PROCEDIMENTO%TYPE;
        V_NOME SGI.PAC_PROCEDIMENTOS.PROCEDIMENTO%TYPE;
    BEGIN
        SELECT COUNT(*),
               MAX(P.ID_PROCEDIMENTO),
               MAX(P.PROCEDIMENTO)
          INTO V_QUANTIDADE,
               V_ID,
               V_NOME
          FROM SGI.PAC_PROCEDIMENTOS P
         WHERE P.ID_PROCEDIMENTO = P_ID
            OR P.PROCEDIMENTO = P_NOME;

        IF V_QUANTIDADE = 0 THEN
            INSERT INTO SGI.PAC_PROCEDIMENTOS (
                ID_PROCEDIMENTO,
                PROCEDIMENTO
            ) VALUES (
                P_ID,
                P_NOME
            );
        ELSIF V_QUANTIDADE <> 1
            OR NVL(V_ID, -1) <> P_ID
            OR NVL(V_NOME, CHR(0)) <> P_NOME
        THEN
            RAISE_APPLICATION_ERROR(
                -20008,
                'Procedimento divergente para o identificador ' || P_ID
            );
        END IF;
    END ASSEGURAR_PROCEDIMENTO;
BEGIN
    ASSEGURAR_PROCEDIMENTO(8, 'RETIFICAÇÃO DE PLANO');
    ASSEGURAR_PROCEDIMENTO(9, 'EXCLUSÃO DE PLANO NO PNCP');

    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE;
END;
/
