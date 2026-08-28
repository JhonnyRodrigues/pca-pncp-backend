<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint51\Support;

use InvalidArgumentException;

final class ExactJsonNumber
{
    public function __construct(private readonly string $lexeme)
    {
        if (!preg_match('/^-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?$/D', $lexeme)) {
            throw new InvalidArgumentException('Número JSON exato inválido.');
        }
    }

    public function value(): string
    {
        return $this->lexeme;
    }
}
