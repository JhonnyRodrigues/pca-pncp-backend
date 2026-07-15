<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Entities;

class Procedimento
{
    public function __construct(
        private readonly int $idProcedimento,
        private readonly string $descricao
    ) {}

    public function getId(): int
    {
        return $this->idProcedimento;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }
}
