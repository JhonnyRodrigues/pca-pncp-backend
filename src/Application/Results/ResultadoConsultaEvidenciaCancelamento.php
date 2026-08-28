<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Results;

use Semae\Pca\Application\Enums\SituacaoConsultaEvidenciaCancelamento;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Domain\ValueObjects\EvidenciaCancelamentoHomologado;

final class ResultadoConsultaEvidenciaCancelamento
{
    private function __construct(
        private readonly SituacaoConsultaEvidenciaCancelamento $situacao,
        private readonly ?EvidenciaCancelamentoHomologado $evidencia,
        private readonly ?FalhaSegura $falha
    ) {}

    public static function disponivel(EvidenciaCancelamentoHomologado $evidencia): self
    {
        return new self(
            SituacaoConsultaEvidenciaCancelamento::DISPONIVEL,
            $evidencia,
            null
        );
    }

    public static function ausente(FalhaSegura $falha): self
    {
        return new self(SituacaoConsultaEvidenciaCancelamento::AUSENTE, null, $falha);
    }

    public static function conflito(FalhaSegura $falha): self
    {
        return new self(SituacaoConsultaEvidenciaCancelamento::CONFLITO, null, $falha);
    }

    public static function falhaSistemica(FalhaSegura $falha): self
    {
        return new self(SituacaoConsultaEvidenciaCancelamento::FALHA_SISTEMICA, null, $falha);
    }

    public function getSituacao(): SituacaoConsultaEvidenciaCancelamento
    {
        return $this->situacao;
    }

    public function getEvidencia(): ?EvidenciaCancelamentoHomologado
    {
        return $this->evidencia;
    }

    public function getFalha(): ?FalhaSegura
    {
        return $this->falha;
    }
}
