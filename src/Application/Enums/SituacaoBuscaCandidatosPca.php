<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Enums;

enum SituacaoBuscaCandidatosPca: string
{
    case CONCLUIDA = 'CONCLUIDA';
    case INCONCLUSIVA = 'INCONCLUSIVA';
    case CONFLITO = 'CONFLITO';
    case FALHA_SISTEMICA = 'FALHA_SISTEMICA';
}
