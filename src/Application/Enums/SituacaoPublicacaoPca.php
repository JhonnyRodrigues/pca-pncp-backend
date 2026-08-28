<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Enums;

enum SituacaoPublicacaoPca: string
{
    case SOLICITACAO_INVALIDA = 'SOLICITACAO_INVALIDA';
    case CONCLUIDA = 'CONCLUIDA';
    case CONCLUIDA_COM_PENDENCIAS = 'CONCLUIDA_COM_PENDENCIAS';
    case INTERROMPIDA = 'INTERROMPIDA';
}
