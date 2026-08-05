<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\ValueObjects;

use InvalidArgumentException;

final class PcaObservado
{
    /**
     * @param int[] $idsDemandas
     */
    private function __construct(
        private readonly bool $ativo,
        private readonly ?SequencialPca $sequencialPca,
        private readonly array $idsDemandas
    ) {}

    public static function ausente(): self
    {
        return new self(false, null, []);
    }

    /** @param int[] $idsDemandas */
    public static function ativo(SequencialPca $sequencialPca, array $idsDemandas): self
    {
        return new self(true, $sequencialPca, self::normalizarIds($idsDemandas));
    }

    public function estaAtivo(): bool
    {
        return $this->ativo;
    }

    public function getSequencialPca(): ?SequencialPca
    {
        return $this->sequencialPca;
    }

    /** @return int[] */
    public function getIdsDemandas(): array
    {
        return $this->idsDemandas;
    }

    /**
     * @param int[] $ids
     * @return int[]
     */
    private static function normalizarIds(array $ids): array
    {
        foreach ($ids as $id) {
            if (!is_int($id)) {
                throw new InvalidArgumentException('Identidades observadas de Demandas devem ser inteiras.');
            }
        }

        if (count($ids) !== count(array_unique($ids))) {
            throw new InvalidArgumentException('Identidades observadas de Demandas não podem ser duplicadas.');
        }

        $ids = array_values($ids);
        sort($ids, SORT_NUMERIC);

        return $ids;
    }
}
