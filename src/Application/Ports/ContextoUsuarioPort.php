<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Ports;

use Semae\Pca\Application\Results\ResultadoResolucaoContextoUsuario;

interface ContextoUsuarioPort
{
    public function resolver(): ResultadoResolucaoContextoUsuario;
}
