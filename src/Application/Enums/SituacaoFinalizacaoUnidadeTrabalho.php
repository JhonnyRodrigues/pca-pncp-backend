<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Enums;

enum SituacaoFinalizacaoUnidadeTrabalho: string
{
    case CONCLUIDA = 'CONCLUIDA';
    case FALHA_SISTEMICA = 'FALHA_SISTEMICA';
}
