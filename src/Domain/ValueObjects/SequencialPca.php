<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\ValueObjects;

use InvalidArgumentException;

final class SequencialPca
{
    public function __construct(private readonly int $valor)
    {
        if ($valor <= 0) {
            throw new InvalidArgumentException('Sequencial do PCA deve ser maior que zero.');
        }
    }

    public function getValor(): int
    {
        return $this->valor;
    }

    public function equals(self $outro): bool
    {
        return $this->valor === $outro->valor;
    }
}
