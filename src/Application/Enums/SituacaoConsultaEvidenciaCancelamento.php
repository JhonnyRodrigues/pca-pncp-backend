<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Enums;

enum SituacaoConsultaEvidenciaCancelamento: string
{
    case DISPONIVEL = 'DISPONIVEL';
    case AUSENTE = 'AUSENTE';
    case CONFLITO = 'CONFLITO';
    case FALHA_SISTEMICA = 'FALHA_SISTEMICA';
}
