<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Enums;

enum SituacaoObservacaoPca: string
{
    case ENCONTRADO = 'ENCONTRADO';
    case AUSENCIA_DEFINITIVA = 'AUSENCIA_DEFINITIVA';
    case INCONCLUSIVA = 'INCONCLUSIVA';
    case CONFLITO = 'CONFLITO';
    case FALHA_SISTEMICA = 'FALHA_SISTEMICA';
}
