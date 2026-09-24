-- Transição da estratégia operacional do PCA para publicação individual.
-- A baseline consolidada permanece imutável; esta versão registra a mudança.
-- Execute como script completo. Estado ausente ou divergente causa rollback.
-- Se o precheck retornar CONFORME, registre o requisito como já satisfeito e
-- não execute esta migração apenas para produzir um COMMIT vazio.

DECLARE
    V_QUANTIDADE PLS_INTEGER;
    V_VALOR SGI.PAC_CONFIGURACOES.VALOR%TYPE;
    V_DESCRICAO SGI.PAC_CONFIGURACOES.DESCRICAO%TYPE;
    V_DESCRICAO_ANTERIOR CONSTANT VARCHAR2(4000) :=
        'Aceita os valores: INDIVIDUAL ou CONSOLIDADO. Altera a decisão operacional da integração. No evento de publicação, consolida as demandas em uma única UASG antes de enviar os dados ao PNCP';
    V_DESCRICAO_ATUAL CONSTANT VARCHAR2(4000) :=
        'Aceita os valores: INDIVIDUAL ou CONSOLIDADO. Define a estratégia operacional usada para publicar os planos no PNCP.';
BEGIN
    SELECT COUNT(*)
      INTO V_QUANTIDADE
      FROM SGI.PAC_CONFIGURACOES C
     WHERE C.CHAVE = 'ESTRATEGIA_PUBLICACAO_PLANOS';

    IF V_QUANTIDADE <> 1 THEN
        RAISE_APPLICATION_ERROR(
            -20010,
            'Configuracao ESTRATEGIA_PUBLICACAO_PLANOS ausente ou duplicada'
        );
    END IF;

    SELECT C.VALOR,
           C.DESCRICAO
      INTO V_VALOR,
           V_DESCRICAO
      FROM SGI.PAC_CONFIGURACOES C
     WHERE C.CHAVE = 'ESTRATEGIA_PUBLICACAO_PLANOS'
       FOR UPDATE;

    IF NVL(V_VALOR, CHR(0)) NOT IN ('CONSOLIDADO', 'INDIVIDUAL') THEN
        RAISE_APPLICATION_ERROR(
            -20011,
            'Valor divergente para ESTRATEGIA_PUBLICACAO_PLANOS'
        );
    END IF;

    IF NVL(V_DESCRICAO, CHR(0)) NOT IN (
        V_DESCRICAO_ANTERIOR,
        V_DESCRICAO_ATUAL
    ) THEN
        RAISE_APPLICATION_ERROR(
            -20012,
            'Descricao divergente para ESTRATEGIA_PUBLICACAO_PLANOS'
        );
    END IF;

    IF V_VALOR <> 'INDIVIDUAL' OR V_DESCRICAO <> V_DESCRICAO_ATUAL THEN
        UPDATE SGI.PAC_CONFIGURACOES C
           SET C.VALOR = 'INDIVIDUAL',
               C.DESCRICAO = V_DESCRICAO_ATUAL
         WHERE C.CHAVE = 'ESTRATEGIA_PUBLICACAO_PLANOS';
    END IF;

    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE;
END;
/
