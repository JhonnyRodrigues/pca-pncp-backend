<?php

declare(strict_types=1);

namespace Semae\Pca\Application\ValueObjects;

use Semae\Pca\Application\Enums\EscopoUnidadeTrabalho;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;

final class ChaveUnidadeTrabalho
{
    private function __construct(
        private readonly EscopoUnidadeTrabalho $escopo,
        private readonly AnoExecucao $anoExecucao,
        private readonly ?UnidadeExternaPublicacao $unidadeExterna
    ) {}

    public static function estrategiaAnual(AnoExecucao $anoExecucao): self
    {
        return new self(EscopoUnidadeTrabalho::ESTRATEGIA_ANUAL, $anoExecucao, null);
    }

    public static function unidadeExterna(UnidadeExternaPublicacao $unidadeExterna): self
    {
        return new self(
            EscopoUnidadeTrabalho::UNIDADE_EXTERNA,
            $unidadeExterna->getAnoExecucao(),
            $unidadeExterna
        );
    }

    public function getEscopo(): EscopoUnidadeTrabalho
    {
        return $this->escopo;
    }

    public function getAnoExecucao(): AnoExecucao
    {
        return $this->anoExecucao;
    }

    public function getUnidadeExterna(): ?UnidadeExternaPublicacao
    {
        return $this->unidadeExterna;
    }

    public function equals(self $outra): bool
    {
        if (
            $this->escopo !== $outra->escopo
            || $this->anoExecucao->getValor() !== $outra->anoExecucao->getValor()
        ) {
            return false;
        }

        if ($this->unidadeExterna === null || $outra->unidadeExterna === null) {
            return $this->unidadeExterna === $outra->unidadeExterna;
        }

        return $this->unidadeExterna->equals($outra->unidadeExterna);
    }
}
