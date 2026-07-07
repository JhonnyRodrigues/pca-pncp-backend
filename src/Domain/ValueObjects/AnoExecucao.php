<?php

namespace Semae\Pca\Domain\ValueObjects;

use Semae\Pca\Domain\Exceptions\AnoExecucaoInvalidoException;

class AnoExecucao
{
    private int $valor;

    public function __construct(int $valor)
    {
        $anoAtual = (int) date('Y');
        $proximoAno = $anoAtual + 1;

        if ($valor < $anoAtual || $valor > $proximoAno) {
            throw new AnoExecucaoInvalidoException('Ano de execucao invalido.');
        }

        $this->valor = $valor;
    }

    public function valor(): int
    {
        return $this->valor;
    }
}
