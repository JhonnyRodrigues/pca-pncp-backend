<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Ports;

use Semae\Pca\Application\Results\ResultadoConsultaEstadoOperacional;
use Semae\Pca\Application\Results\ResultadoPersistenciaLocal;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;

interface EstadoOperacionalPublicacaoPort
{
    public function consultar(
        UnidadeExternaPublicacao $unidade
    ): ResultadoConsultaEstadoOperacional;

    public function registrar(
        UnidadeExternaPublicacao $unidade,
        EstadoOperacionalPublicacao $estado
    ): ResultadoPersistenciaLocal;
}
