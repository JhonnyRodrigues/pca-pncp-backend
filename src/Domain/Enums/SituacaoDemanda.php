<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Enums;

enum SituacaoDemanda: int
{
    case PLANEJADA = 1;
    case HOMOLOGADA = 2;
    case COMPRADA = 3;
    case PUBLICADA = 5;
    case CANCELADA = 4;

    public function participaDaFotografiaVigente(): bool
    {
        return match ($this) {
            self::HOMOLOGADA, self::PUBLICADA, self::COMPRADA => true,
            self::PLANEJADA, self::CANCELADA => false,
        };
    }
}
