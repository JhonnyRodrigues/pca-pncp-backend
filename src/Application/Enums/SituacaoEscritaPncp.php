<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Enums;

enum SituacaoEscritaPncp: string
{
    case ACEITA = 'ACEITA';
    case REJEITADA = 'REJEITADA';
    case RESULTADO_INCERTO = 'RESULTADO_INCERTO';
    case FALHA_SISTEMICA = 'FALHA_SISTEMICA';
}
