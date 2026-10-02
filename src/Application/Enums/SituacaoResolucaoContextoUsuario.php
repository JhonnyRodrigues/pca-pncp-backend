<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Enums;

enum SituacaoResolucaoContextoUsuario: string
{
    case RESOLVIDO = 'RESOLVIDO';
    case NAO_RESOLVIDO = 'NAO_RESOLVIDO';
    case FALHA_SISTEMICA = 'FALHA_SISTEMICA';
}
