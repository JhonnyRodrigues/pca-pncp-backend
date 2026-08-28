<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Commands;

use InvalidArgumentException;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\SequencialPca;

final class ComandoExcluirItensPca
{
    /** @var int[] */
    private readonly array $idsDemandas;
    private readonly string $justificativa;

    /** @param int[] $idsDemandas */
    public function __construct(
        private readonly Cnpj $cnpj,
        private readonly UnidadeExternaPublicacao $unidadeExterna,
        private readonly SequencialPca $sequencialPca,
        array $idsDemandas,
        string $justificativa
    ) {
        $this->idsDemandas = self::validarIds($idsDemandas);
        $this->justificativa = self::validarJustificativa($justificativa);
    }

    public function getCnpj(): Cnpj
    {
        return $this->cnpj;
    }

    public function getUnidadeExterna(): UnidadeExternaPublicacao
    {
        return $this->unidadeExterna;
    }

    public function getSequencialPca(): SequencialPca
    {
        return $this->sequencialPca;
    }

    /** @return int[] */
    public function getIdsDemandas(): array
    {
        return $this->idsDemandas;
    }

    public function getJustificativa(): string
    {
        return $this->justificativa;
    }

    /**
     * @param int[] $ids
     * @return int[]
     */
    private static function validarIds(array $ids): array
    {
        if ($ids === []) {
            throw new InvalidArgumentException('Exclusão de itens deve possuir ao menos uma Demanda.');
        }

        foreach ($ids as $id) {
            if (!is_int($id)) {
                throw new InvalidArgumentException('Identidades de Demandas devem ser inteiras.');
            }
        }

        if (count($ids) !== count(array_unique($ids))) {
            throw new InvalidArgumentException('Identidades de Demandas não podem ser duplicadas.');
        }

        return array_values($ids);
    }

    private static function validarJustificativa(string $justificativa): string
    {
        $justificativa = trim($justificativa);

        if ($justificativa === '') {
            throw new InvalidArgumentException('Justificativa da exclusão de itens é obrigatória.');
        }

        return $justificativa;
    }
}
