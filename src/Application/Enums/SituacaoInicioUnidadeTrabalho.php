<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Enums;

enum SituacaoInicioUnidadeTrabalho: string
{
    case INICIADA = 'INICIADA';
    case BLOQUEADA = 'BLOQUEADA';
    case FALHA_SISTEMICA = 'FALHA_SISTEMICA';
}
