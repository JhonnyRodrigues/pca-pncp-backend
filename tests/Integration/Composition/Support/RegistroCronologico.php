<?php

declare(strict_types=1);

namespace Tests\Integration\Composition\Support;

final class RegistroCronologico
{
    /** @var string[] */
    private array $eventos = [];

    public function registrar(string $evento): void
    {
        $this->eventos[] = $evento;
    }

    /** @return string[] */
    public function todos(): array
    {
        return $this->eventos;
    }
}
