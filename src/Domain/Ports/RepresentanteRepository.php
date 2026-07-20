<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Ports;

use Semae\Pca\Domain\ValueObjects\Representante;

interface RepresentanteRepository
{
    public function obterPorUsuarioLogado(string $usuarioLogado): ?Representante;
}
