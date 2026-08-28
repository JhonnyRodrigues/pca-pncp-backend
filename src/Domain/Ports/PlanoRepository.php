<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Ports;

use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;

interface PlanoRepository
{
    /**
     * @return IdentidadePlano[]
     */
    public function listarIdentidades(AnoExecucao $anoExecucao): array;

    public function reconstruirFotografiaVigente(IdentidadePlano $identidade): Plano;

    public function confirmarSincronizacao(Plano $plano): void;
}
