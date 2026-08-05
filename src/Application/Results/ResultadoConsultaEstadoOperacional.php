<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Results;

use Semae\Pca\Application\Enums\SituacaoConsultaEstadoOperacional;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;

final class ResultadoConsultaEstadoOperacional
{
    private function __construct(
        private readonly SituacaoConsultaEstadoOperacional $situacao,
        private readonly ?EstadoOperacionalPublicacao $estadoOperacional,
        private readonly ?FalhaSegura $falha
    ) {}

    public static function disponivel(EstadoOperacionalPublicacao $estadoOperacional): self
    {
        return new self(
            SituacaoConsultaEstadoOperacional::DISPONIVEL,
            $estadoOperacional,
            null
        );
    }

    public static function conflito(FalhaSegura $falha): self
    {
        return new self(SituacaoConsultaEstadoOperacional::CONFLITO, null, $falha);
    }

    public static function falhaSistemica(FalhaSegura $falha): self
    {
        return new self(SituacaoConsultaEstadoOperacional::FALHA_SISTEMICA, null, $falha);
    }

    public function getSituacao(): SituacaoConsultaEstadoOperacional
    {
        return $this->situacao;
    }

    public function getEstadoOperacional(): ?EstadoOperacionalPublicacao
    {
        return $this->estadoOperacional;
    }

    public function getFalha(): ?FalhaSegura
    {
        return $this->falha;
    }
}
