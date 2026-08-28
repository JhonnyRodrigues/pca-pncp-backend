<?php

namespace Semae\Pca\Domain\ValueObjects;

use Semae\Pca\Domain\Exceptions\AnoExecucaoInvalidoException;

class AnoExecucao
{
    public function __construct(
        private readonly int $valor
    ) {
        $anoAtual = (int) date('Y');
        $proximoAno = $anoAtual + 1;

        if ($valor < $anoAtual || $valor > $proximoAno) {
            throw new AnoExecucaoInvalidoException('Ano de execução inválido.');
        }
    }

    public function getValor(): int
    {
        return $this->valor;
    }
}
