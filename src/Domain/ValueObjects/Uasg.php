<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\ValueObjects;

use InvalidArgumentException;

class Uasg
{
    private readonly string $codigo;
    private readonly string $descricao;

    public function __construct(string $codigo, string $descricao)
    {
        $codigo = trim($codigo);
        $descricao = trim($descricao);

        if ($codigo === '') {
            throw new InvalidArgumentException('Código da UASG é obrigatório.');
        }

        if ($descricao === '') {
            throw new InvalidArgumentException('Descrição da UASG é obrigatória.');
        }

        $this->codigo = $codigo;
        $this->descricao = $descricao;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }
}
