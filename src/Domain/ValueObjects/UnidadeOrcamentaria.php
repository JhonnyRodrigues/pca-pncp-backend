<?php

namespace Semae\Pca\Domain\ValueObjects;

use Semae\Pca\Domain\Exceptions\UnidadeOrcamentariaInvalidaException;

class UnidadeOrcamentaria
{
    private readonly int $codigo; /* imutável */
    private readonly string $nome;

    public function __construct(int $codigo, string $nome)
    {
        $nome = trim($nome);

        if ($codigo <= 0) {
            throw new UnidadeOrcamentariaInvalidaException('Codigo da unidade orcamentaria invalido.');
        }

        if ($nome === '' || mb_strlen($nome) < 3) {
            throw new UnidadeOrcamentariaInvalidaException('Nome da unidade orcamentaria invalido.');
        }

        $this->codigo = $codigo;
        $this->nome = $nome;
    }

    public function codigo(): int
    {
        return $this->codigo;
    }

    public function nome(): string
    {
        return $this->nome;
    }
}
