<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\ValueObjects;

use InvalidArgumentException;

final class EvidenciaCancelamentoHomologado
{
    private readonly string $justificativa;

    public function __construct(
        private readonly IdentidadePlano $identidadePlano,
        private readonly int $idDemanda,
        string $justificativa
    ) {
        if ($idDemanda <= 0) {
            throw new InvalidArgumentException('Identificador da Demanda deve ser positivo.');
        }

        $justificativa = trim($justificativa);

        if ($justificativa === '') {
            throw new InvalidArgumentException('Justificativa do cancelamento é obrigatória.');
        }

        $this->justificativa = $justificativa;
    }

    public function getIdentidadePlano(): IdentidadePlano
    {
        return $this->identidadePlano;
    }

    public function getIdDemanda(): int
    {
        return $this->idDemanda;
    }

    public function getJustificativa(): string
    {
        return $this->justificativa;
    }

    public function equals(self $outra): bool
    {
        return $this->identidadePlano->equals($outra->identidadePlano)
            && $this->idDemanda === $outra->idDemanda
            && $this->justificativa === $outra->justificativa;
    }
}
