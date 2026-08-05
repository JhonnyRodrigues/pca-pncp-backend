<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Enums;

enum SituacaoConsultaEstadoOperacional: string
{
    case DISPONIVEL = 'DISPONIVEL';
    case CONFLITO = 'CONFLITO';
    case FALHA_SISTEMICA = 'FALHA_SISTEMICA';
}
