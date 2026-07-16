<?php

namespace Semae\Pca\Domain\ValueObjects;

use Semae\Pca\Domain\Exceptions\UnidadeOrcamentariaInvalidaException;

class UnidadeOrcamentaria
{
    private readonly string $nome;

    public function __construct(
        private readonly int $id,
        string $nome
    ) {
        $nome = trim($nome);

        if ($id <= 0) {
            throw new UnidadeOrcamentariaInvalidaException('Identificador da unidade orçamentaria inválido.');
        }

        if ($nome === '' || mb_strlen($nome) < 3) {
            throw new UnidadeOrcamentariaInvalidaException('Nome da unidade orçamentaria inválido.');
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
