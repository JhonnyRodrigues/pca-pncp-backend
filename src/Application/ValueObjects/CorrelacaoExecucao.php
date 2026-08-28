<?php

declare(strict_types=1);

namespace Semae\Pca\Application\ValueObjects;

use InvalidArgumentException;

final class CorrelacaoExecucao
{
    public function __construct(private readonly string $valor)
    {
        if (trim($valor) === '') {
            throw new InvalidArgumentException('Correlação da execução é obrigatória.');
        }
    }

    public function getValor(): string
    {
        return $this->valor;
    }
}
