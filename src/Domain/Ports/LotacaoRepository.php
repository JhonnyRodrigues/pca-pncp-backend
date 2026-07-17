<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Ports;

use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Representante;

interface LotacaoRepository
{
    public function obterPorRepresentante(Representante $representante): ?Lotacao;
}
