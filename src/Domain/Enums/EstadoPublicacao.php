<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Enums;

enum EstadoPublicacao: string
{
    case NAO_PUBLICADO = 'NAO_PUBLICADO';
    case PUBLICADO = 'PUBLICADO';
    case EXCLUIDO = 'EXCLUIDO';
}
