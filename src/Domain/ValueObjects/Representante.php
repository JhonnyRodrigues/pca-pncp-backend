<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\ValueObjects;

use InvalidArgumentException;

class Representante
{
    private readonly string $nome;

    public function __construct(
        private readonly int $id,
        string $nome
    ) {
        $nome = trim($nome);

        if ($nome === '') {
            throw new InvalidArgumentException('Nome do representante é obrigatório.');
        }

        $this->nome = $nome;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }
}
