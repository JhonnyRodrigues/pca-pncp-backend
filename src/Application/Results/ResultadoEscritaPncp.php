<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Results;

use Semae\Pca\Application\Enums\SituacaoEscritaPncp;
use Semae\Pca\Application\ValueObjects\FalhaSegura;

final class ResultadoEscritaPncp
{
    private function __construct(
        private readonly SituacaoEscritaPncp $situacao,
        private readonly ?FalhaSegura $falha
    ) {}

    public static function aceita(): self
    {
        return new self(SituacaoEscritaPncp::ACEITA, null);
    }

    public static function rejeitada(FalhaSegura $falha): self
    {
        return new self(SituacaoEscritaPncp::REJEITADA, $falha);
    }

    public static function resultadoIncerto(FalhaSegura $falha): self
    {
        return new self(SituacaoEscritaPncp::RESULTADO_INCERTO, $falha);
    }

    public static function falhaSistemica(FalhaSegura $falha): self
    {
        return new self(SituacaoEscritaPncp::FALHA_SISTEMICA, $falha);
    }

    public function getSituacao(): SituacaoEscritaPncp
    {
        return $this->situacao;
    }

    public function getFalha(): ?FalhaSegura
    {
        return $this->falha;
    }
}
