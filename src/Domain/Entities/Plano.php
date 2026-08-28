<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Entities;

use DateTimeImmutable;
use Semae\Pca\Domain\Exceptions\PlanoException;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;

class Plano
{
    /**
     * @param Demanda[] $demandas
     */
    public function __construct(
        private readonly IdentidadePlano $identidade,
        private readonly int $sequencialVersao,
        private readonly DateTimeImmutable $dataHomologacao,
        private readonly array $demandas,
        private readonly ?DateTimeImmutable $dataConfirmacaoSincronizacao
    ) {
        $this->validarSequencialVersao();
        $this->validarDemandas();
    }

    public function getIdentidade(): IdentidadePlano
    {
        return $this->identidade;
    }

    public function getAnoExecucao(): AnoExecucao
    {
        return $this->identidade->getAnoExecucao();
    }

    public function getUnidadeOrcamentaria(): UnidadeOrcamentaria
    {
        return $this->identidade->getUnidadeOrcamentaria();
    }

    public function getSequencialVersao(): int
    {
        return $this->sequencialVersao;
    }

    public function getDataHomologacao(): DateTimeImmutable
    {
        return $this->dataHomologacao;
    }

    public function getDataConfirmacaoSincronizacao(): ?DateTimeImmutable
    {
        return $this->dataConfirmacaoSincronizacao;
    }

    public function possuiSincronizacaoConfirmada(): bool
    {
        return $this->dataConfirmacaoSincronizacao !== null;
    }

    /**
     * @return Demanda[]
     */
    public function getDemandas(): array
    {
        return $this->demandas;
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

    private function validarDemandas(): void
    {
        $idsDemandas = [];

        foreach ($this->demandas as $demanda) {
            if (!$demanda instanceof Demanda) {
                throw new PlanoException('Plano deve possuir apenas Demandas.');
            }

            if (!$demanda->participaDaFotografiaVigente()) {
                throw new PlanoException('Plano deve possuir apenas Demandas participantes da fotografia vigente.');
            }

            if (array_key_exists($demanda->getIdDemanda(), $idsDemandas)) {
                throw new PlanoException('Plano não pode possuir Demandas duplicadas.');
            }

            $idsDemandas[$demanda->getIdDemanda()] = true;

            if (!$demanda->getUnidadeOrcamentaria()->possuiMesmaIdentidade($this->getUnidadeOrcamentaria())) {
                throw new PlanoException('Demandas devem pertencer à mesma Unidade Orçamentária do Plano.');
            }

            if ($demanda->getAnoExecucao()->getValor() !== $this->getAnoExecucao()->getValor()) {
                throw new PlanoException('Demandas devem pertencer ao mesmo Ano de Execução do Plano.');
            }
        }
    }
}
