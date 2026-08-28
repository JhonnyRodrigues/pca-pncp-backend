<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Results;

use InvalidArgumentException;
use Semae\Pca\Application\Enums\SituacaoBuscaCandidatosPca;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Domain\ValueObjects\PcaObservado;

final class ResultadoBuscaCandidatosPca
{
    /**
     * @param PcaObservado[] $candidatos
     */
    private function __construct(
        private readonly SituacaoBuscaCandidatosPca $situacao,
        private readonly array $candidatos,
        private readonly ?FalhaSegura $falha
    ) {}

    /** @param PcaObservado[] $candidatos */
    public static function concluida(array $candidatos): self
    {
        $sequenciais = [];

        foreach ($candidatos as $candidato) {
            if (!$candidato instanceof PcaObservado || !$candidato->estaAtivo()) {
                throw new InvalidArgumentException(
                    'Candidatos devem representar apenas publicações ativas observadas.'
                );
            }

            $sequencial = $candidato->getSequencialPca()?->getValor();

            if ($sequencial === null || isset($sequenciais[$sequencial])) {
                throw new InvalidArgumentException(
                    'Candidatos não podem possuir sequenciais ausentes ou duplicados.'
                );
            }

            $sequenciais[$sequencial] = true;
        }

        return new self(
            SituacaoBuscaCandidatosPca::CONCLUIDA,
            array_values($candidatos),
            null
        );
    }

    public static function inconclusiva(FalhaSegura $falha): self
    {
        return new self(SituacaoBuscaCandidatosPca::INCONCLUSIVA, [], $falha);
    }

    public static function conflito(FalhaSegura $falha): self
    {
        return new self(SituacaoBuscaCandidatosPca::CONFLITO, [], $falha);
    }

    public static function falhaSistemica(FalhaSegura $falha): self
    {
        return new self(SituacaoBuscaCandidatosPca::FALHA_SISTEMICA, [], $falha);
    }

    public function getSituacao(): SituacaoBuscaCandidatosPca
    {
        return $this->situacao;
    }

    /** @return PcaObservado[] */
    public function getCandidatos(): array
    {
        return $this->candidatos;
    }

    public function getFalha(): ?FalhaSegura
    {
        return $this->falha;
    }
}
