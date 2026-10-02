<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Results;

use Semae\Pca\Application\Enums\SituacaoEscritaPncp;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Domain\ValueObjects\SequencialPca;

final class ResultadoPublicacaoPncp
{
    private function __construct(
        private readonly SituacaoEscritaPncp $situacao,
        private readonly ?SequencialPca $sequencialPca,
        private readonly ?FalhaSegura $falha
    ) {}

    public static function aceita(SequencialPca $sequencialPca): self
    {
        return new self(SituacaoEscritaPncp::ACEITA, $sequencialPca, null);
    }

    public static function rejeitada(FalhaSegura $falha): self
    {
        return new self(SituacaoEscritaPncp::REJEITADA, null, $falha);
    }

    public static function resultadoIncerto(FalhaSegura $falha): self
    {
        return new self(SituacaoEscritaPncp::RESULTADO_INCERTO, null, $falha);
    }

    public static function falhaSistemica(FalhaSegura $falha): self
    {
        return new self(SituacaoEscritaPncp::FALHA_SISTEMICA, null, $falha);
    }

    public function getSituacao(): SituacaoEscritaPncp
    {
        return $this->situacao;
    }

    public function getSequencialPca(): ?SequencialPca
    {
        return $this->sequencialPca;
    }

    public function getFalha(): ?FalhaSegura
    {
        return $this->falha;
    }
}
