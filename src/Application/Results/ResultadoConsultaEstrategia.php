<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Results;

use Semae\Pca\Application\Enums\SituacaoConsultaEstrategia;
use Semae\Pca\Application\ValueObjects\EstrategiaPublicacao;
use Semae\Pca\Application\ValueObjects\FalhaSegura;

final class ResultadoConsultaEstrategia
{
    private function __construct(
        private readonly SituacaoConsultaEstrategia $situacao,
        private readonly ?EstrategiaPublicacao $estrategia,
        private readonly ?FalhaSegura $falha
    ) {}

    public static function disponivel(EstrategiaPublicacao $estrategia): self
    {
        return new self(SituacaoConsultaEstrategia::DISPONIVEL, $estrategia, null);
    }

    public static function ausente(): self
    {
        return new self(SituacaoConsultaEstrategia::AUSENTE, null, null);
    }

    public static function invalida(FalhaSegura $falha): self
    {
        return new self(SituacaoConsultaEstrategia::INVALIDA, null, $falha);
    }

    public static function falhaSistemica(FalhaSegura $falha): self
    {
        return new self(SituacaoConsultaEstrategia::FALHA_SISTEMICA, null, $falha);
    }

    public function getSituacao(): SituacaoConsultaEstrategia
    {
        return $this->situacao;
    }

    public function getEstrategia(): ?EstrategiaPublicacao
    {
        return $this->estrategia;
    }

    public function getFalha(): ?FalhaSegura
    {
        return $this->falha;
    }
}
