<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Results;

use InvalidArgumentException;
use Semae\Pca\Application\Enums\SituacaoObservacaoPca;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Domain\ValueObjects\PcaObservado;

final class ResultadoObservacaoPca
{
    private function __construct(
        private readonly SituacaoObservacaoPca $situacao,
        private readonly ?PcaObservado $pcaObservado,
        private readonly ?FalhaSegura $falha
    ) {}

    public static function encontrado(PcaObservado $pcaObservado): self
    {
        if (!$pcaObservado->estaAtivo()) {
            throw new InvalidArgumentException('PCA encontrado deve representar publicação ativa.');
        }

        return new self(SituacaoObservacaoPca::ENCONTRADO, $pcaObservado, null);
    }

    public static function ausenciaDefinitiva(): self
    {
        return new self(
            SituacaoObservacaoPca::AUSENCIA_DEFINITIVA,
            PcaObservado::ausente(),
            null
        );
    }

    public static function inconclusiva(FalhaSegura $falha): self
    {
        return new self(SituacaoObservacaoPca::INCONCLUSIVA, null, $falha);
    }

    public static function conflito(FalhaSegura $falha): self
    {
        return new self(SituacaoObservacaoPca::CONFLITO, null, $falha);
    }

    public static function falhaSistemica(FalhaSegura $falha): self
    {
        return new self(SituacaoObservacaoPca::FALHA_SISTEMICA, null, $falha);
    }

    public function getSituacao(): SituacaoObservacaoPca
    {
        return $this->situacao;
    }

    public function getPcaObservado(): ?PcaObservado
    {
        return $this->pcaObservado;
    }

    public function getFalha(): ?FalhaSegura
    {
        return $this->falha;
    }
}
