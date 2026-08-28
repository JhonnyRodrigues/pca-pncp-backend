<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\ValueObjects;

use InvalidArgumentException;

class Lotacao
{
    private readonly string $descricao;

    public function __construct(
        private readonly int $idLotacao,
        string $descricao
    ) {
        if ($idLotacao <= 0) {
            throw new InvalidArgumentException('Identificador da lotação deve ser positivo.');
        }

        $descricao = trim($descricao);

        if ($descricao === '') {
            throw new InvalidArgumentException('Descrição da lotação é obrigatória.');
        }

        $this->descricao = $descricao;
    }

    public function getId(): int
    {
        return $this->idLotacao;
    }

    public function getNome(): string
    {
        return $this->descricao;
    }
}
