<?php

declare(strict_types=1);

namespace Semae\Pca\Application\ValueObjects;

use InvalidArgumentException;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;

final class ContextoUnidadeSobExclusividade
{
    /** @var Plano[] */
    private readonly array $planos;

    /** @param Plano[] $planos */
    public function __construct(
        private readonly UnidadeExternaPublicacao $unidadeExterna,
        array $planos,
        private readonly EstadoOperacionalPublicacao $estadoOperacional
    ) {
        if ($planos === []) {
            throw new InvalidArgumentException(
                'Contexto sob exclusividade exige ao menos um Plano.'
            );
        }

        foreach ($planos as $plano) {
            if (!$plano instanceof Plano) {
                throw new InvalidArgumentException(
                    'Contexto sob exclusividade deve possuir apenas Planos.'
                );
            }
        }

        $this->planos = array_values($planos);
    }

    public function getUnidadeExterna(): UnidadeExternaPublicacao
    {
        return $this->unidadeExterna;
    }

    /** @return Plano[] */
    public function getPlanos(): array
    {
        return $this->planos;
    }

    public function getEstadoOperacional(): EstadoOperacionalPublicacao
    {
        return $this->estadoOperacional;
    }
}
