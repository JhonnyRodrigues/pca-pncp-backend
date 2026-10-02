<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Results;

use Semae\Pca\Application\Enums\SituacaoPersistenciaLocal;
use Semae\Pca\Application\ValueObjects\FalhaSegura;

final class ResultadoPersistenciaLocal
{
    private function __construct(
        private readonly SituacaoPersistenciaLocal $situacao,
        private readonly ?FalhaSegura $falha
    ) {}

    public static function concluida(): self
    {
        return new self(SituacaoPersistenciaLocal::CONCLUIDA, null);
    }

    public static function falhaSistemica(FalhaSegura $falha): self
    {
        return new self(SituacaoPersistenciaLocal::FALHA_SISTEMICA, $falha);
    }

    public function getSituacao(): SituacaoPersistenciaLocal
    {
        return $this->situacao;
    }

    public function getFalha(): ?FalhaSegura
    {
        return $this->falha;
    }
}
