<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Enums;

enum SituacaoAutenticacaoPncp: string
{
    case AUTENTICADA = 'AUTENTICADA';
    case FALHA_SISTEMICA = 'FALHA_SISTEMICA';
}
