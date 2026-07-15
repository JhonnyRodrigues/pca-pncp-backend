<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\ValueObjects;

class Lotacao
{
    public function __construct(
        private readonly int $idLotacao,
        private readonly string $descricao
    ) {}

    public function id(): int
    {
        return $this->idLotacao;
    }

    public function descricao(): string
    {
        return $this->descricao;
    }
}
