<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Ports;

use Semae\Pca\Application\Results\ResultadoPersistenciaLocal;
use Semae\Pca\Domain\Facts\HistoricoProcedimento;

interface RegistroAuditoriaPort
{
    public function registrar(HistoricoProcedimento $fato): ResultadoPersistenciaLocal;
}
