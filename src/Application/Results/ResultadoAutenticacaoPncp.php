<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Results;

use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Enums\SituacaoAutenticacaoPncp;
use Semae\Pca\Application\ValueObjects\FalhaSegura;

final class ResultadoAutenticacaoPncp
{
    private function __construct(
        private readonly SituacaoAutenticacaoPncp $situacao,
        private readonly ?SessaoPncp $sessao,
        private readonly ?FalhaSegura $falha
    ) {}

    public static function autenticada(SessaoPncp $sessao): self
    {
        return new self(SituacaoAutenticacaoPncp::AUTENTICADA, $sessao, null);
    }

    public static function falhaSistemica(FalhaSegura $falha): self
    {
        return new self(SituacaoAutenticacaoPncp::FALHA_SISTEMICA, null, $falha);
    }

    public function getSituacao(): SituacaoAutenticacaoPncp
    {
        return $this->situacao;
    }

    public function getSessao(): ?SessaoPncp
    {
        return $this->sessao;
    }

    public function getFalha(): ?FalhaSegura
    {
        return $this->falha;
    }
}
