<?php

declare(strict_types=1);

namespace Semae\Pca\Application\ValueObjects;

use Semae\Pca\Application\Enums\ModoPublicacao;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class UnidadeExternaPublicacao
{
    private function __construct(
        private readonly ModoPublicacao $modo,
        private readonly AnoExecucao $anoExecucao,
        private readonly Uasg $uasgExterna,
        private readonly ?IdentidadePlano $identidadePlano
    ) {}

    public static function individual(IdentidadePlano $identidadePlano): self
    {
        return new self(
            ModoPublicacao::INDIVIDUAL,
            $identidadePlano->getAnoExecucao(),
            $identidadePlano->getUnidadeOrcamentaria()->getUasg(),
            $identidadePlano
        );
    }

    public static function consolidada(AnoExecucao $anoExecucao, Uasg $uasgConsolidadora): self
    {
        return new self(
            ModoPublicacao::CONSOLIDADO,
            $anoExecucao,
            $uasgConsolidadora,
            null
        );
    }

    public function getModo(): ModoPublicacao
    {
        return $this->modo;
    }

    public function getAnoExecucao(): AnoExecucao
    {
        return $this->anoExecucao;
    }

    public function getUasgExterna(): Uasg
    {
        return $this->uasgExterna;
    }

    public function getIdentidadePlano(): ?IdentidadePlano
    {
        return $this->identidadePlano;
    }

    public function equals(self $outra): bool
    {
        if (
            $this->modo !== $outra->modo
            || $this->anoExecucao->getValor() !== $outra->anoExecucao->getValor()
            || $this->uasgExterna->getCodigo() !== $outra->uasgExterna->getCodigo()
        ) {
            return false;
        }

        if ($this->identidadePlano === null || $outra->identidadePlano === null) {
            return $this->identidadePlano === $outra->identidadePlano;
        }

        return $this->identidadePlano->equals($outra->identidadePlano);
    }
}
