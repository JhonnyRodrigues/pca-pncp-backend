<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\ValueObjects;

class Lotacao
{
    public function __construct(
        private readonly int $idLotacao,
        private readonly string $descricao
    ) {}

    public function getId(): int
    {
        return $this->idLotacao;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }
}
