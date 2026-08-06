<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Results;

use Semae\Pca\Application\Enums\SituacaoDesfechoProcessamentoUnidade;
use Semae\Pca\Application\ValueObjects\FalhaSegura;

final class DesfechoProcessamentoUnidade
{
    private function __construct(
        private readonly SituacaoDesfechoProcessamentoUnidade $situacao,
        private readonly ?ResultadoProcessamentoUnidade $resultadoUnitario,
        private readonly ?FalhaSegura $falhaSistemica
    ) {}

    public static function resultadoUnitario(ResultadoProcessamentoUnidade $resultado): self
    {
        return new self(
            SituacaoDesfechoProcessamentoUnidade::RESULTADO_UNITARIO,
            $resultado,
            null
        );
    }

    public static function falhaSistemica(FalhaSegura $falha): self
    {
        return new self(
            SituacaoDesfechoProcessamentoUnidade::FALHA_SISTEMICA,
            null,
            $falha
        );
    }

    public function getSituacao(): SituacaoDesfechoProcessamentoUnidade
    {
        return $this->situacao;
    }

    public function getResultadoUnitario(): ?ResultadoProcessamentoUnidade
    {
        return $this->resultadoUnitario;
    }

    public function getFalhaSistemica(): ?FalhaSegura
    {
        return $this->falhaSistemica;
    }
}
