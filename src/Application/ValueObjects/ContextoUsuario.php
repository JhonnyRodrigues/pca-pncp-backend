<?php

declare(strict_types=1);

namespace Semae\Pca\Application\ValueObjects;

use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Representante;

final class ContextoUsuario
{
    public function __construct(
        private readonly Representante $representante,
        private readonly Lotacao $lotacao
    ) {}

    public function getRepresentante(): Representante
    {
        return $this->representante;
    }

    public function getLotacao(): Lotacao
    {
        return $this->lotacao;
    }
}
