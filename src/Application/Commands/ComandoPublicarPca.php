<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Commands;

use InvalidArgumentException;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\Demanda;
use Semae\Pca\Domain\ValueObjects\Cnpj;

final class ComandoPublicarPca
{
    /** @var Demanda[] */
    private readonly array $demandas;

    /** @param Demanda[] $demandas */
    public function __construct(
        private readonly Cnpj $cnpj,
        private readonly UnidadeExternaPublicacao $unidadeExterna,
        array $demandas
    ) {
        $this->demandas = self::validarDemandas($demandas);
    }

    public function getCnpj(): Cnpj
    {
        return $this->cnpj;
    }

    public function getUnidadeExterna(): UnidadeExternaPublicacao
    {
        return $this->unidadeExterna;
    }

    /** @return Demanda[] */
    public function getDemandas(): array
    {
        return $this->demandas;
    }

    /**
     * @param Demanda[] $demandas
     * @return Demanda[]
     */
    private static function validarDemandas(array $demandas): array
    {
        if ($demandas === []) {
            throw new InvalidArgumentException('Publicação deve possuir ao menos uma Demanda.');
        }

        $ids = [];

        foreach ($demandas as $demanda) {
            if (!$demanda instanceof Demanda) {
                throw new InvalidArgumentException('Publicação deve possuir apenas Demandas.');
            }

            if (isset($ids[$demanda->getIdDemanda()])) {
                throw new InvalidArgumentException('Publicação não pode possuir Demandas duplicadas.');
            }

            $ids[$demanda->getIdDemanda()] = true;
        }

        return array_values($demandas);
    }
}
