<?php

declare(strict_types=1);

namespace Semae\Pca\Presentation\ViewModels;

final class PublicarPcaViewModel
{
    /**
     * @param ResultadoUnidadeViewModel[] $resultadosUnitarios
     */
    public function __construct(
        private readonly string $situacao,
        private readonly array $resultadosUnitarios,
        private readonly ?string $codigoFalha,
        private readonly ?string $mensagemFalha,
        private readonly int $quantidadePlanejadas,
        private readonly int $quantidadeVisitadas,
        private readonly int $quantidadeNaoProcessadas,
        private readonly ?string $correlacao
    ) {}

    public function getSituacao(): string
    {
        return $this->situacao;
    }

    /** @return ResultadoUnidadeViewModel[] */
    public function getResultadosUnitarios(): array
    {
        return $this->resultadosUnitarios;
    }

    public function getCodigoFalha(): ?string
    {
        return $this->codigoFalha;
    }

    public function getMensagemFalha(): ?string
    {
        return $this->mensagemFalha;
    }

    public function getQuantidadePlanejadas(): int
    {
        return $this->quantidadePlanejadas;
    }

    public function getQuantidadeVisitadas(): int
    {
        return $this->quantidadeVisitadas;
    }

    public function getQuantidadeNaoProcessadas(): int
    {
        return $this->quantidadeNaoProcessadas;
    }

    public function getCorrelacao(): ?string
    {
        return $this->correlacao;
    }
}
