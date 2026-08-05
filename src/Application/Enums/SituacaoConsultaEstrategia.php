<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Enums;

enum SituacaoConsultaEstrategia: string
{
    case DISPONIVEL = 'DISPONIVEL';
    case AUSENTE = 'AUSENTE';
    case INVALIDA = 'INVALIDA';
    case FALHA_SISTEMICA = 'FALHA_SISTEMICA';
}
