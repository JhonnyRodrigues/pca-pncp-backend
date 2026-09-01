<?php

declare(strict_types=1);

namespace Semae\Pca\Presentation\Inputs;

final class EntradaPublicarPca
{
    public function __construct(
        private readonly string $cnpj,
        private readonly int $anoExecucao
    ) {}

    public function getCnpj(): string
    {
        return $this->cnpj;
    }

    public function getAnoExecucao(): int
    {
        return $this->anoExecucao;
    }
}
