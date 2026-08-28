<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Enums;

enum SituacaoPreparacaoPublicacaoPca: string
{
    case PREPARADA = 'PREPARADA';
    case SEM_UNIDADES = 'SEM_UNIDADES';
    case INTERROMPIDA = 'INTERROMPIDA';
}
