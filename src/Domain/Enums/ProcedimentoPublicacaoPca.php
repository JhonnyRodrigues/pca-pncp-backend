<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Enums;

enum ProcedimentoPublicacaoPca: int
{
    case PUBLICACAO_INICIAL = 5;
    case RETIFICACAO = 8;
    case EXCLUSAO_INTEGRAL = 9;
}
