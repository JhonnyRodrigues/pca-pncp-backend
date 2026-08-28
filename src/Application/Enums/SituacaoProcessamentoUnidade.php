<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Enums;

enum SituacaoProcessamentoUnidade: string
{
    case CONCLUIDO = 'CONCLUIDO';
    case SEM_ALTERACAO = 'SEM_ALTERACAO';
    case FALHA_RECONCILIAVEL = 'FALHA_RECONCILIAVEL';
    case BLOQUEADO = 'BLOQUEADO';
}
