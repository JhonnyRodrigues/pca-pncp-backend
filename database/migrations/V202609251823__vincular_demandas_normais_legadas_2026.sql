-- Saneamento excepcional de Demandas normais legadas do PCA 2026.
-- PREPARADA PARA REVISAO; NAO EXECUTAR SEM MICROCICLO E PRECHECK AUTORIZADOS.
-- Fonte dos IDs: build/itens_2026.json, SHA-256
-- B8A9A6A363807D52BEBB87E745480E3D0BC6A541414BC02552274FC9AEAA0877.
-- Os grupos representam UASG 205/4, 207/2 e 210/3 no PNCP, respectivamente.
-- Escopo: 366 IDs ja publicados no PNCP. O inventario nao comprova que sejam
-- todas as Demandas normais legadas de 2026; confirmar isso antes da execucao.
-- A associacao de cada ID a UO e inferida do inventario PNCP, pois FK_PLANO
-- nulo nao permite comprova-la apenas pela PAC_DEMANDA.
-- Somente FK_PLANO e alterado. Divergencia aborta e desfaz toda a transacao.
-- Executar como script completo em sessao dedicada, com a Blank 2026 suspensa.
-- A aplicacao manual exige autorizacao especifica de um microciclo de DML.

DECLARE
    V_IDS_205 CONSTANT SYS.ODCINUMBERLIST := SYS.ODCINUMBERLIST(
        414, 415, 434, 435, 436, 437, 438, 439, 440, 441, 442, 443,
        444, 445, 446, 447, 448, 449, 450, 451, 452, 463, 464, 467,
        468, 469, 470, 471, 472, 473, 474, 475, 476, 477, 478, 479,
        480, 481, 482, 483, 484, 485, 486, 487, 488, 489, 490, 491,
        492, 493, 494, 495, 496, 497, 498, 499, 500, 501, 502, 503,
        504, 505, 506, 507, 508, 510, 516, 517, 518, 519, 521, 522,
        523, 524, 525, 526, 527, 528, 529, 530, 532, 533, 534, 535,
        536, 537, 539, 540, 541, 542, 543, 544, 545, 546, 547, 548,
        549, 550, 551, 552, 553, 554, 555, 556, 557, 558, 559, 560,
        561, 562, 563, 564, 565, 566, 567, 568, 569, 570, 571, 572,
        573, 574, 575, 576, 577, 578, 579, 580, 581, 582, 583, 584,
        586, 587, 588, 589, 590, 591, 593, 622, 623, 624, 625, 626,
        627, 636, 650, 651, 652, 656, 659, 660, 663, 664, 666, 667,
        671, 673, 705, 706, 708, 714, 715, 716, 717, 718, 719, 720,
        721, 722, 723, 724, 725, 726, 727, 728, 729, 730, 731, 732,
        733, 734, 735, 736, 737, 739, 740, 744, 746, 747, 748, 749,
        750, 764, 765, 766, 768, 783, 784, 785, 786, 787, 788, 789,
        790, 791, 792, 793
    );
    V_IDS_207 CONSTANT SYS.ODCINUMBERLIST := SYS.ODCINUMBERLIST(
        411, 412, 413, 418, 419
    );
    V_IDS_210 CONSTANT SYS.ODCINUMBERLIST := SYS.ODCINUMBERLIST(
        416, 417, 420, 421, 422, 423, 424, 425, 426, 427, 428, 429,
        430, 431, 432, 433, 453, 454, 455, 456, 457, 458, 459, 460,
        461, 462, 465, 466, 509, 511, 512, 513, 514, 592, 594, 597,
        598, 599, 600, 602, 603, 604, 605, 606, 607, 608, 609, 610,
        611, 612, 613, 614, 615, 616, 617, 618, 619, 620, 628, 629,
        630, 631, 632, 633, 634, 635, 637, 639, 640, 641, 642, 643,
        644, 645, 646, 647, 648, 649, 653, 654, 655, 657, 658, 661,
        662, 665, 668, 669, 670, 672, 674, 675, 676, 677, 678, 679,
        680, 681, 682, 683, 684, 685, 686, 687, 688, 689, 690, 691,
        692, 693, 694, 695, 696, 697, 698, 699, 701, 702, 703, 704,
        707, 709, 710, 712, 713, 738, 741, 742, 743, 745, 751, 752,
        753, 754, 755, 757, 760, 761, 762, 763, 767, 769, 770, 772,
        774, 775, 776, 777, 778, 779, 780, 781, 782
    );
    V_DISTINTOS PLS_INTEGER;
    V_ATUALIZADOS PLS_INTEGER := 0;

    PROCEDURE VINCULAR(
        P_UO IN NUMBER,
        P_UASG IN VARCHAR2,
        P_SEQUENCIAL IN NUMBER,
        P_ID_PLANO IN NUMBER,
        P_ESPERADOS IN PLS_INTEGER,
        P_IDS IN SYS.ODCINUMBERLIST
    ) IS
        V_ID_PLANO SGI.PAC_PLANOS.ID_PLANO%TYPE;
        V_UNICOS PLS_INTEGER;
        V_TOTAL PLS_INTEGER;
        V_SEM_PLANO PLS_INTEGER;
        V_NO_PLANO_ALVO PLS_INTEGER;
        V_CONFIRMACAO PLS_INTEGER;
    BEGIN
        IF P_IDS.COUNT <> P_ESPERADOS THEN
            RAISE_APPLICATION_ERROR(-20020, 'Lista de IDs com cardinalidade divergente');
        END IF;

        SELECT COUNT(DISTINCT I.COLUMN_VALUE)
          INTO V_UNICOS
          FROM TABLE(P_IDS) I;
        IF V_UNICOS <> P_ESPERADOS THEN
            RAISE_APPLICATION_ERROR(-20021, 'Lista de IDs com duplicidade');
        END IF;

        SELECT P.ID_PLANO
          INTO V_ID_PLANO
          FROM SGI.PAC_PLANOS P
         WHERE P.ID_PLANO = P_ID_PLANO
           AND P.ANO_EXERCICIO = 2026
           AND P.FK_UNIDADE_ORCAMENTARIA = P_UO
           AND P.VERSAO = 1
           AND P.ESTRATEGIA_ADOTADA_PUBLICACAO = 'INDIVIDUAL'
           AND P.UASG_ADOTADA = P_UASG
           AND P.ESTADO_PUBLICACAO = 'PUBLICADO'
           AND P.SEQUENCIAL_PUBLICACAO_PNCP = P_SEQUENCIAL
           FOR UPDATE NOWAIT;

        SELECT COUNT(*),
               COUNT(CASE WHEN D.FK_PLANO IS NULL THEN 1 END),
               COUNT(CASE WHEN D.FK_PLANO = V_ID_PLANO THEN 1 END)
          INTO V_TOTAL, V_SEM_PLANO, V_NO_PLANO_ALVO
          FROM SGI.PAC_DEMANDA D
         WHERE D.ID_DEMANDA IN (
                   SELECT I.COLUMN_VALUE FROM TABLE(P_IDS) I
               )
           AND D.ORIGEM = 'N'
           AND D.SITUACAO IN (2, 3, 5);

        IF V_TOTAL <> P_ESPERADOS THEN
            RAISE_APPLICATION_ERROR(-20022, 'ID ausente, origem ou situacao divergente na UO ' || P_UO);
        END IF;

        IF V_NO_PLANO_ALVO = P_ESPERADOS THEN
            RETURN;
        END IF;

        IF V_SEM_PLANO <> P_ESPERADOS THEN
            RAISE_APPLICATION_ERROR(-20023, 'FK_PLANO parcialmente preenchido ou divergente na UO ' || P_UO);
        END IF;

        UPDATE SGI.PAC_DEMANDA D
           SET D.FK_PLANO = V_ID_PLANO
         WHERE D.ID_DEMANDA IN (
                   SELECT I.COLUMN_VALUE FROM TABLE(P_IDS) I
               )
           AND D.ORIGEM = 'N'
           AND D.SITUACAO IN (2, 3, 5)
           AND D.FK_PLANO IS NULL;

        IF SQL%ROWCOUNT <> P_ESPERADOS THEN
            RAISE_APPLICATION_ERROR(-20024, 'Quantidade atualizada divergente na UO ' || P_UO);
        END IF;

        SELECT COUNT(*)
          INTO V_CONFIRMACAO
          FROM SGI.PAC_DEMANDA D
         WHERE D.ID_DEMANDA IN (
                   SELECT I.COLUMN_VALUE FROM TABLE(P_IDS) I
               )
           AND D.ORIGEM = 'N'
           AND D.SITUACAO IN (2, 3, 5)
           AND D.FK_PLANO = V_ID_PLANO;

        IF V_CONFIRMACAO <> P_ESPERADOS THEN
            RAISE_APPLICATION_ERROR(-20025, 'Releitura do vinculo divergente na UO ' || P_UO);
        END IF;

        V_ATUALIZADOS := V_ATUALIZADOS + P_ESPERADOS;
    END VINCULAR;
BEGIN
    SELECT COUNT(DISTINCT X.ID_DEMANDA)
      INTO V_DISTINTOS
      FROM (
          SELECT I.COLUMN_VALUE AS ID_DEMANDA FROM TABLE(V_IDS_205) I
          UNION ALL
          SELECT I.COLUMN_VALUE FROM TABLE(V_IDS_207) I
          UNION ALL
          SELECT I.COLUMN_VALUE FROM TABLE(V_IDS_210) I
      ) X;

    IF V_DISTINTOS <> 366 THEN
        RAISE_APPLICATION_ERROR(-20026, 'IDs repetidos entre unidades');
    END IF;

    VINCULAR(149, '205', 4, 61, 208, V_IDS_205);
    VINCULAR(3, '207', 2, 141, 5, V_IDS_207);
    VINCULAR(94, '210', 3, 1, 153, V_IDS_210);

    IF V_ATUALIZADOS NOT IN (0, 366) THEN
        RAISE_APPLICATION_ERROR(-20027, 'Migracao parcialmente aplicada');
    END IF;

    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE;
END;
/
