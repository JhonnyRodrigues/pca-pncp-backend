<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Ports;

use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;

interface PlanoRepository
{
    /**
     * @return Plano[]
     */
    public function recuperarUltimasVersoesPorAnoExecucao(AnoExecucao $anoExecucao): array;

    public function persistir(Plano $plano): void;
}
