<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Ports;

use Semae\Pca\Application\Results\ResultadoAdocaoEstrategia;
use Semae\Pca\Application\Results\ResultadoConsultaEstrategia;
use Semae\Pca\Application\ValueObjects\EstrategiaPublicacao;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;

interface EstrategiaPublicacaoPort
{
    public function consultarCorrente(AnoExecucao $anoExecucao): ResultadoConsultaEstrategia;

    public function consultarAdotada(AnoExecucao $anoExecucao): ResultadoConsultaEstrategia;

    public function adotar(
        AnoExecucao $anoExecucao,
        EstrategiaPublicacao $estrategia
    ): ResultadoAdocaoEstrategia;
}
