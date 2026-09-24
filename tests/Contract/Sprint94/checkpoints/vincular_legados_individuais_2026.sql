-- Sprint 9.4 / Microciclo 4.
-- Baseline aprovado: 7823d46dae06aff72119d2c1d2262afa2c4af82e24e28ae2ce69d1d296345975
-- Execute no DBeaver como script, com auto-commit desativado.
-- O script vincula somente os três PCAs oficiais de 2026 e não confirma versões.

SELECT p.ID_PLANO,
       p.FK_UNIDADE_ORCAMENTARIA,
       p.VERSAO,
       p.ESTRATEGIA_ADOTADA_PUBLICACAO,
       p.UASG_ADOTADA,
       p.ESTADO_PUBLICACAO,
       p.SEQUENCIAL_PUBLICACAO_PNCP,
       p.DATA_PUBLICACAO
  FROM SGI.PAC_PLANOS p
 WHERE p.ANO_EXERCICIO = 2026
   AND p.FK_UNIDADE_ORCAMENTARIA IN (3, 94, 149)
 ORDER BY p.FK_UNIDADE_ORCAMENTARIA, p.VERSAO, p.ID_PLANO;

DECLARE
    V_CONFIGURACAO VARCHAR2(30);
    V_UO_3         PLS_INTEGER := 0;
    V_UO_94        PLS_INTEGER := 0;
    V_UO_149       PLS_INTEGER := 0;
    V_ATUALIZADAS  PLS_INTEGER := 0;
    V_CONFIRMADAS  PLS_INTEGER := 0;
BEGIN
    SELECT TRIM(c.VALOR)
      INTO V_CONFIGURACAO
      FROM SGI.PAC_CONFIGURACOES c
     WHERE c.CHAVE = 'ESTRATEGIA_PUBLICACAO_PLANOS'
       FOR UPDATE;

    IF V_CONFIGURACAO <> 'INDIVIDUAL' THEN
        RAISE_APPLICATION_ERROR(-20001, 'A estrategia corrente nao e INDIVIDUAL.');
    END IF;

    FOR r IN (
        SELECT p.ID_PLANO,
               p.FK_UNIDADE_ORCAMENTARIA,
               p.ESTRATEGIA_ADOTADA_PUBLICACAO,
               p.UASG_ADOTADA,
               p.ESTADO_PUBLICACAO,
               p.SEQUENCIAL_PUBLICACAO_PNCP
          FROM SGI.PAC_PLANOS p
         WHERE p.ANO_EXERCICIO = 2026
           AND p.FK_UNIDADE_ORCAMENTARIA IN (3, 94, 149)
         ORDER BY p.FK_UNIDADE_ORCAMENTARIA, p.ID_PLANO
           FOR UPDATE
    ) LOOP
        IF r.ESTRATEGIA_ADOTADA_PUBLICACAO IS NOT NULL
           OR r.UASG_ADOTADA IS NOT NULL
           OR r.ESTADO_PUBLICACAO IS NOT NULL
           OR r.SEQUENCIAL_PUBLICACAO_PNCP IS NOT NULL
        THEN
            RAISE_APPLICATION_ERROR(-20002, 'Existe projecao operacional previamente preenchida.');
        END IF;

        IF r.FK_UNIDADE_ORCAMENTARIA = 3 THEN
            IF r.ID_PLANO NOT IN (141, 142, 143) THEN
                RAISE_APPLICATION_ERROR(-20003, 'ID_PLANO inesperado para a UO 3.');
            END IF;
            V_UO_3 := V_UO_3 + 1;
        ELSIF r.FK_UNIDADE_ORCAMENTARIA = 94 THEN
            IF r.ID_PLANO NOT IN (
                1, 21, 22, 23, 24, 25, 27, 28, 29, 30,
                31, 32, 33, 34, 35, 36, 37, 41, 42, 71,
                72, 73, 74, 75, 76, 77, 102, 103, 104, 105,
                106, 107, 108, 109, 121, 122, 123, 124
            ) THEN
                RAISE_APPLICATION_ERROR(-20004, 'ID_PLANO inesperado para a UO 94.');
            END IF;
            V_UO_94 := V_UO_94 + 1;
        ELSIF r.FK_UNIDADE_ORCAMENTARIA = 149 THEN
            IF r.ID_PLANO NOT IN (
                61, 62, 63, 64, 65, 66, 67, 68, 69, 70,
                78, 79, 80, 81, 82, 83, 84, 85, 86, 87,
                88, 89, 90, 91, 92, 93, 94, 95, 96, 97,
                98, 99, 100, 101, 110, 111, 112, 113, 114, 115,
                116, 117, 118, 119, 161, 162, 163, 164, 165, 181,
                182, 183, 184, 185, 186, 187, 188, 189, 190, 191,
                192, 193, 194, 195, 196, 197, 198, 221, 222, 223,
                224
            ) THEN
                RAISE_APPLICATION_ERROR(-20005, 'ID_PLANO inesperado para a UO 149.');
            END IF;
            V_UO_149 := V_UO_149 + 1;
        END IF;
    END LOOP;

    IF V_UO_3 <> 3 OR V_UO_94 <> 38 OR V_UO_149 <> 71 THEN
        RAISE_APPLICATION_ERROR(-20006, 'Cardinalidade dos planos divergiu do inventario aprovado.');
    END IF;

    UPDATE SGI.PAC_PLANOS p
       SET p.ESTRATEGIA_ADOTADA_PUBLICACAO = 'INDIVIDUAL',
           p.UASG_ADOTADA = CASE p.FK_UNIDADE_ORCAMENTARIA
               WHEN 3 THEN '207'
               WHEN 94 THEN '210'
               WHEN 149 THEN '205'
           END,
           p.ESTADO_PUBLICACAO = 'PUBLICADO',
           p.SEQUENCIAL_PUBLICACAO_PNCP = CASE p.FK_UNIDADE_ORCAMENTARIA
               WHEN 3 THEN 2
               WHEN 94 THEN 3
               WHEN 149 THEN 4
           END
     WHERE p.ANO_EXERCICIO = 2026
       AND p.ESTRATEGIA_ADOTADA_PUBLICACAO IS NULL
       AND p.UASG_ADOTADA IS NULL
       AND p.ESTADO_PUBLICACAO IS NULL
       AND p.SEQUENCIAL_PUBLICACAO_PNCP IS NULL
       AND (
           (p.FK_UNIDADE_ORCAMENTARIA = 3 AND p.ID_PLANO IN (141, 142, 143))
           OR (p.FK_UNIDADE_ORCAMENTARIA = 94 AND p.ID_PLANO IN (
               1, 21, 22, 23, 24, 25, 27, 28, 29, 30,
               31, 32, 33, 34, 35, 36, 37, 41, 42, 71,
               72, 73, 74, 75, 76, 77, 102, 103, 104, 105,
               106, 107, 108, 109, 121, 122, 123, 124
           ))
           OR (p.FK_UNIDADE_ORCAMENTARIA = 149 AND p.ID_PLANO IN (
               61, 62, 63, 64, 65, 66, 67, 68, 69, 70,
               78, 79, 80, 81, 82, 83, 84, 85, 86, 87,
               88, 89, 90, 91, 92, 93, 94, 95, 96, 97,
               98, 99, 100, 101, 110, 111, 112, 113, 114, 115,
               116, 117, 118, 119, 161, 162, 163, 164, 165, 181,
               182, 183, 184, 185, 186, 187, 188, 189, 190, 191,
               192, 193, 194, 195, 196, 197, 198, 221, 222, 223,
               224
           ))
       );

    V_ATUALIZADAS := SQL%ROWCOUNT;
    IF V_ATUALIZADAS <> 112 THEN
        RAISE_APPLICATION_ERROR(-20007, 'Quantidade atualizada diferente de 112.');
    END IF;

    SELECT COUNT(*)
      INTO V_CONFIRMADAS
      FROM SGI.PAC_PLANOS p
     WHERE p.ANO_EXERCICIO = 2026
       AND p.ESTRATEGIA_ADOTADA_PUBLICACAO = 'INDIVIDUAL'
       AND p.ESTADO_PUBLICACAO = 'PUBLICADO'
       AND (
           (p.FK_UNIDADE_ORCAMENTARIA = 3
               AND p.UASG_ADOTADA = '207'
               AND p.SEQUENCIAL_PUBLICACAO_PNCP = 2
               AND p.ID_PLANO IN (141, 142, 143))
           OR (p.FK_UNIDADE_ORCAMENTARIA = 94
               AND p.UASG_ADOTADA = '210'
               AND p.SEQUENCIAL_PUBLICACAO_PNCP = 3
               AND p.ID_PLANO IN (
                   1, 21, 22, 23, 24, 25, 27, 28, 29, 30,
                   31, 32, 33, 34, 35, 36, 37, 41, 42, 71,
                   72, 73, 74, 75, 76, 77, 102, 103, 104, 105,
                   106, 107, 108, 109, 121, 122, 123, 124
               ))
           OR (p.FK_UNIDADE_ORCAMENTARIA = 149
               AND p.UASG_ADOTADA = '205'
               AND p.SEQUENCIAL_PUBLICACAO_PNCP = 4
               AND p.ID_PLANO IN (
                   61, 62, 63, 64, 65, 66, 67, 68, 69, 70,
                   78, 79, 80, 81, 82, 83, 84, 85, 86, 87,
                   88, 89, 90, 91, 92, 93, 94, 95, 96, 97,
                   98, 99, 100, 101, 110, 111, 112, 113, 114, 115,
                   116, 117, 118, 119, 161, 162, 163, 164, 165, 181,
                   182, 183, 184, 185, 186, 187, 188, 189, 190, 191,
                   192, 193, 194, 195, 196, 197, 198, 221, 222, 223,
                   224
               ))
       );

    IF V_CONFIRMADAS <> 112 THEN
        RAISE_APPLICATION_ERROR(-20008, 'O pos-check nao confirmou os 112 vinculos.');
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE;
END;
/

SELECT p.FK_UNIDADE_ORCAMENTARIA,
       p.ESTRATEGIA_ADOTADA_PUBLICACAO,
       p.UASG_ADOTADA,
       p.ESTADO_PUBLICACAO,
       p.SEQUENCIAL_PUBLICACAO_PNCP,
       COUNT(*) AS QUANTIDADE_PLANOS
  FROM SGI.PAC_PLANOS p
 WHERE p.ANO_EXERCICIO = 2026
   AND p.FK_UNIDADE_ORCAMENTARIA IN (3, 94, 149)
 GROUP BY p.FK_UNIDADE_ORCAMENTARIA,
          p.ESTRATEGIA_ADOTADA_PUBLICACAO,
          p.UASG_ADOTADA,
          p.ESTADO_PUBLICACAO,
          p.SEQUENCIAL_PUBLICACAO_PNCP
 ORDER BY p.FK_UNIDADE_ORCAMENTARIA;
