<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Serializacao;

final class NumeroJsonExato
{
    public function __construct(private readonly string $lexema)
    {
        if (preg_match('/^-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?$/D',$lexema) !== 1) {
            throw new FalhaSerializacaoPncp();
        }
    }

    public function getLexema(): string
    {
        return $this->lexema;
    }
}
