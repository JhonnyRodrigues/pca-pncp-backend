<?php

declare(strict_types=1);

namespace Semae\Pca\Application\ValueObjects;

use Semae\Pca\Application\Enums\ModoPublicacao;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class EstrategiaPublicacao
{
    private function __construct(
        private readonly ModoPublicacao $modo,
        private readonly ?Uasg $uasgConsolidadora
    ) {}

    public static function individual(): self
    {
        return new self(ModoPublicacao::INDIVIDUAL, null);
    }

    public static function consolidada(Uasg $uasgConsolidadora): self
    {
        return new self(ModoPublicacao::CONSOLIDADO, $uasgConsolidadora);
    }

    public function getModo(): ModoPublicacao
    {
        return $this->modo;
    }

    public function getUasgConsolidadora(): ?Uasg
    {
        return $this->uasgConsolidadora;
    }

    public function equals(self $outra): bool
    {
        if ($this->modo !== $outra->modo) {
            return false;
        }

        if ($this->uasgConsolidadora === null || $outra->uasgConsolidadora === null) {
            return $this->uasgConsolidadora === $outra->uasgConsolidadora;
        }

        return $this->uasgConsolidadora->getCodigo() === $outra->uasgConsolidadora->getCodigo();
    }
}
