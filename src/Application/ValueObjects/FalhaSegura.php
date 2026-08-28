<?php

declare(strict_types=1);

namespace Semae\Pca\Application\ValueObjects;

use InvalidArgumentException;

final class FalhaSegura
{
    private readonly string $codigo;
    private readonly string $mensagem;

    public function __construct(string $codigo, string $mensagem)
    {
        $codigo = trim($codigo);
        $mensagem = trim($mensagem);

        if ($codigo === '') {
            throw new InvalidArgumentException('Código da falha é obrigatório.');
        }

        if ($mensagem === '') {
            throw new InvalidArgumentException('Mensagem segura da falha é obrigatória.');
        }

        $this->codigo = $codigo;
        $this->mensagem = $mensagem;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function getMensagem(): string
    {
        return $this->mensagem;
    }
}
