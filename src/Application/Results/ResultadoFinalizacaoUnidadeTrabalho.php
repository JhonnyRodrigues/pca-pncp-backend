<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Results;

use Semae\Pca\Application\Enums\SituacaoFinalizacaoUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\FalhaSegura;

final class ResultadoFinalizacaoUnidadeTrabalho
{
    private function __construct(
        private readonly SituacaoFinalizacaoUnidadeTrabalho $situacao,
        private readonly ?FalhaSegura $falha
    ) {}

    public static function concluida(): self
    {
        return new self(SituacaoFinalizacaoUnidadeTrabalho::CONCLUIDA, null);
    }

    public static function falhaSistemica(FalhaSegura $falha): self
    {
        return new self(SituacaoFinalizacaoUnidadeTrabalho::FALHA_SISTEMICA, $falha);
    }

    public function getSituacao(): SituacaoFinalizacaoUnidadeTrabalho
    {
        return $this->situacao;
    }

    public function getFalha(): ?FalhaSegura
    {
        return $this->falha;
    }
}
