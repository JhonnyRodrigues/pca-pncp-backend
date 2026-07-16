<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Entities;

use DateTimeImmutable;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\UnidadeOrcamentaria;
use Semae\Pca\Domain\Exceptions\PlanoException;

class Plano
{
    /**
     * @param Demanda[] $demandas
     */
    public function __construct(
        private readonly int $idPlano,
        private readonly AnoExecucao $anoExecucao,
        private readonly UnidadeOrcamentaria $unidadeOrcamentaria,
        private readonly int $sequencialVersao,
        private readonly DateTimeImmutable $dataHomologacao,
        private readonly array $demandas,
        private readonly ?DateTimeImmutable $dataPublicacao = null
    ) {
        $this->validarSequencialVersao();
        $this->validarDemandas();
    }

    # métodos getters
    public function getIdPlano(): int
    {
        return $this->idPlano;
    }

    public function getAnoExecucao(): AnoExecucao
    {
        return $this->anoExecucao;
    }

    public function getUnidadeOrcamentaria(): UnidadeOrcamentaria
    {
        return $this->unidadeOrcamentaria;
    }

    public function getSequencialVersao(): int
    {
        return $this->sequencialVersao;
    }

    public function getDataHomologacao(): DateTimeImmutable
    {
        return $this->dataHomologacao;
    }

    /**
     * @return Demanda[]
     */
    public function getDemandas(): array
    {
        return $this->demandas;
    }

    public function getDataPublicacao(): ?DateTimeImmutable
    {
        return $this->dataPublicacao;
    }

    public function foiPublicado(): bool
    {
        return $this->dataPublicacao !== null;
    }

    public function possuiDemandas(): bool
    {
        return $this->demandas !== [];
    }

    public function getQuantidadeDemandas(): int
    {
        return count($this->demandas);
    }

    private function validarSequencialVersao(): void
    {
        if ($this->sequencialVersao <= 0) {
            throw new PlanoException('Versão sequencial do plano deve ser maior que zero.');
        }
    }

    # metódos validação
    private function validarDemandas(): void
    {
        if ($this->demandas === []) {
            throw new PlanoException('Plano deve possuir ao menos uma Demanda.');
        }

        foreach ($this->demandas as $demanda) {
            if (!$demanda instanceof Demanda) {
                throw new PlanoException('Plano deve possuir apenas Demandas.');
            }

            if ($demanda->getIdPlano() !== $this->idPlano) {
                throw new PlanoException('Demanda deve pertencer ao Plano informado.');
            }

            if ($demanda->getUnidadeOrcamentaria()->codigo() !== $this->unidadeOrcamentaria->codigo()) {
                throw new PlanoException('Demandas devem pertencer a mesma Unidade Orçamentária do Plano.');
            }

            if ($demanda->getAnoExecucao() !== $this->anoExecucao->getValor()) {
                throw new PlanoException('Demandas devem pertencer ao mesmo Ano de Execução do Plano.');
            }
        }
    }
}
