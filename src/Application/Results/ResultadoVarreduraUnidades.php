<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Results;

use InvalidArgumentException;
use Semae\Pca\Application\ValueObjects\FalhaSegura;

final class ResultadoVarreduraUnidades
{
    /**
     * @param ResultadoProcessamentoUnidade[] $resultadosUnitarios
     */
    private function __construct(
        private readonly array $resultadosUnitarios,
        private readonly ?FalhaSegura $falhaSistemica,
        private readonly int $quantidadeVisitadas
    ) {
        $this->validarResultados($resultadosUnitarios);

        if (
            $quantidadeVisitadas < 0
            || $quantidadeVisitadas < count($resultadosUnitarios)
        ) {
            throw new InvalidArgumentException(
                'Unidades visitadas não podem ser menos que os resultados conhecidos.'
            );
        }
    }

    /** @param ResultadoProcessamentoUnidade[] $resultadosUnitarios */
    public static function concluida(array $resultadosUnitarios): self
    {
        return new self($resultadosUnitarios, null, count($resultadosUnitarios));
    }

    /** @param ResultadoProcessamentoUnidade[] $resultadosUnitarios */
    public static function interrompida(
        array $resultadosUnitarios,
        FalhaSegura $falhaSistemica,
        int $quantidadeVisitadas
    ): self {
        return new self($resultadosUnitarios, $falhaSistemica, $quantidadeVisitadas);
    }

    /** @return ResultadoProcessamentoUnidade[] */
    public function getResultadosUnitarios(): array
    {
        return $this->resultadosUnitarios;
    }

    public function getFalhaSistemica(): ?FalhaSegura
    {
        return $this->falhaSistemica;
    }

    public function foiInterrompida(): bool
    {
        return $this->falhaSistemica !== null;
    }

    public function getQuantidadeVisitadas(): int
    {
        return $this->quantidadeVisitadas;
    }

    /** @param mixed[] $resultadosUnitarios */
    private function validarResultados(array $resultadosUnitarios): void
    {
        $unidades = [];

        foreach ($resultadosUnitarios as $resultado) {
            if (!$resultado instanceof ResultadoProcessamentoUnidade) {
                throw new InvalidArgumentException(
                    'Varredura exige resultados unitários válidos.'
                );
            }

            foreach ($unidades as $unidade) {
                if ($unidade->equals($resultado->getUnidadeExterna())) {
                    throw new InvalidArgumentException(
                        'Varredura não pode possuir resultados duplicados.'
                    );
                }
            }

            $unidades[] = $resultado->getUnidadeExterna();
        }
    }
}
