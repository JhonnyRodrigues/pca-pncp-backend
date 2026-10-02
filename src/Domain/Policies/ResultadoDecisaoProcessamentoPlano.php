<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Policies;

use InvalidArgumentException;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;

final class ResultadoDecisaoProcessamentoPlano
{
    /**
     * @param int[] $demandasAIncluir
     * @param int[] $demandasAExcluir
     */
    private function __construct(
        private readonly DecisaoProcessamentoPlano $decisao,
        private readonly array $demandasAIncluir,
        private readonly array $demandasAExcluir
    ) {}

    /** @param int[] $demandasAIncluir */
    public static function publicar(array $demandasAIncluir): self
    {
        $demandasAIncluir = self::validarIdsUnicos($demandasAIncluir);

        if ($demandasAIncluir === []) {
            throw new InvalidArgumentException('Publicação deve incluir ao menos uma Demanda.');
        }

        return new self(DecisaoProcessamentoPlano::PUBLICAR, $demandasAIncluir, []);
    }

    /**
     * @param int[] $demandasAIncluir
     * @param int[] $demandasAExcluir
     */
    public static function retificar(array $demandasAIncluir, array $demandasAExcluir): self
    {
        $demandasAIncluir = self::validarIdsUnicos($demandasAIncluir);
        $demandasAExcluir = self::validarIdsUnicos($demandasAExcluir);

        if ($demandasAIncluir === [] && $demandasAExcluir === []) {
            throw new InvalidArgumentException('Retificação deve possuir ao menos uma diferença.');
        }

        if (array_intersect($demandasAIncluir, $demandasAExcluir) !== []) {
            throw new InvalidArgumentException('Uma Demanda não pode ser incluída e excluída na mesma Retificação.');
        }

        return new self(DecisaoProcessamentoPlano::RETIFICAR, $demandasAIncluir, $demandasAExcluir);
    }

    public static function excluirPca(): self
    {
        return new self(DecisaoProcessamentoPlano::EXCLUIR_PCA, [], []);
    }

    public static function nenhumaAcao(): self
    {
        return new self(DecisaoProcessamentoPlano::NENHUMA_ACAO, [], []);
    }

    public function getDecisao(): DecisaoProcessamentoPlano
    {
        return $this->decisao;
    }

    /** @return int[] */
    public function getDemandasAIncluir(): array
    {
        return $this->demandasAIncluir;
    }

    /** @return int[] */
    public function getDemandasAExcluir(): array
    {
        return $this->demandasAExcluir;
    }

    /**
     * @param int[] $ids
     * @return int[]
     */
    private static function validarIdsUnicos(array $ids): array
    {
        foreach ($ids as $id) {
            if (!is_int($id)) {
                throw new InvalidArgumentException('Identidades de Demandas devem ser inteiras.');
            }
        }

        if (count($ids) !== count(array_unique($ids))) {
            throw new InvalidArgumentException('Identidades de Demandas não podem ser duplicadas.');
        }

        $ids = array_values($ids);
        sort($ids, SORT_NUMERIC);

        return $ids;
    }
}
