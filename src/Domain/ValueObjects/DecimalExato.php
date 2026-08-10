<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\ValueObjects;

use InvalidArgumentException;

final class DecimalExato
{
    public function __construct(private readonly string $valor)
    {
        if (preg_match('/^-?(?:0|[1-9]\d*)(?:\.\d+)?$/D', $valor) !== 1) {
            throw new InvalidArgumentException('O valor decimal deve usar representação numérica exata.');
        }
    }

    public function getValor(): string
    {
        return $this->valor;
    }
}
