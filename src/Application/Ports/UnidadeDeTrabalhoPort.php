<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Ports;

use Semae\Pca\Application\Results\ResultadoFinalizacaoUnidadeTrabalho;
use Semae\Pca\Application\Results\ResultadoInicioUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\ChaveUnidadeTrabalho;

interface UnidadeDeTrabalhoPort
{
    public function iniciar(ChaveUnidadeTrabalho $chave): ResultadoInicioUnidadeTrabalho;

    public function confirmar(): ResultadoFinalizacaoUnidadeTrabalho;

    public function desfazer(): ResultadoFinalizacaoUnidadeTrabalho;
}
