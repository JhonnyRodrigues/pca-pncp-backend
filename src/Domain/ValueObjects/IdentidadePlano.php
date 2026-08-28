<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\ValueObjects;

use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;

final class IdentidadePlano
{
    public function __construct(
        private readonly UnidadeOrcamentaria $unidadeOrcamentaria,
        private readonly AnoExecucao $anoExecucao
    ) {}

    public function getUnidadeOrcamentaria(): UnidadeOrcamentaria
    {
        return $this->unidadeOrcamentaria;
    }

    public function getAnoExecucao(): AnoExecucao
    {
        return $this->anoExecucao;
    }

    public function equals(self $outra): bool
    {
        return $this->unidadeOrcamentaria->possuiMesmaIdentidade($outra->unidadeOrcamentaria)
            && $this->anoExecucao->getValor() === $outra->anoExecucao->getValor();
    }
}
