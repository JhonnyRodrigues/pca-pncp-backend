<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Ports;

use Semae\Pca\Application\Results\ResultadoPersistenciaLocal;
use Semae\Pca\Application\ValueObjects\IntencaoRegistroHistorico;

interface RegistroAuditoriaPort
{
    public function registrar(
        IntencaoRegistroHistorico $intencao
    ): ResultadoPersistenciaLocal;
}
