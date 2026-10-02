<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Enums;

enum SituacaoDesfechoProcessamentoUnidade: string
{
    case RESULTADO_UNITARIO = 'RESULTADO_UNITARIO';
    case FALHA_SISTEMICA = 'FALHA_SISTEMICA';
}
