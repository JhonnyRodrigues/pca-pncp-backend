<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Results;

use Semae\Pca\Application\Enums\SituacaoInicioUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\FalhaSegura;

final class ResultadoInicioUnidadeTrabalho
{
    private function __construct(
        private readonly SituacaoInicioUnidadeTrabalho $situacao,
        private readonly ?FalhaSegura $falha
    ) {}

    public static function iniciada(): self
    {
        return new self(SituacaoInicioUnidadeTrabalho::INICIADA, null);
    }

    public static function bloqueada(FalhaSegura $falha): self
    {
        return new self(SituacaoInicioUnidadeTrabalho::BLOQUEADA, $falha);
    }

    public static function falhaSistemica(FalhaSegura $falha): self
    {
        return new self(SituacaoInicioUnidadeTrabalho::FALHA_SISTEMICA, $falha);
    }

    public function getSituacao(): SituacaoInicioUnidadeTrabalho
    {
        return $this->situacao;
    }

    public function getFalha(): ?FalhaSegura
    {
        return $this->falha;
    }
}
