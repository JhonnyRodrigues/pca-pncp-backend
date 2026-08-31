<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Ports;

use Semae\Pca\Application\Results\ResultadoAutenticacaoPncp;

interface AutenticadorPncpPort
{
    public function autenticar(): ResultadoAutenticacaoPncp;
}
