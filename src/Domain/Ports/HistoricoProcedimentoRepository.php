<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Ports;

use Semae\Pca\Domain\Entities\HistoricoProcedimento;

interface HistoricoProcedimentoRepository
{
    public function registrar(HistoricoProcedimento $historico): void;
}
