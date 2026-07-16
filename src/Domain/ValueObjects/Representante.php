<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\ValueObjects;

class Representante
{
    public function __construct(
        private readonly int $id,
        private readonly string $nome
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }
}
