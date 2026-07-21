<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Entities;

use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;
use Semae\Pca\Domain\Exceptions\PlanoException;
use DateTimeImmutable;

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

    public function foiPublicado(): bool
    {
        return $this->dataPublicacao !== null;
    }

    public function decidirProcessamento(): DecisaoProcessamentoPlano
    {
        if ($this->foiPublicado()) {
            return DecisaoProcessamentoPlano::NENHUMA_ACAO;
        }

        if ($this->sequencialVersao === 1) {
            return DecisaoProcessamentoPlano::PUBLICAR;
        }

        return DecisaoProcessamentoPlano::RETIFICAR;
    }

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

    public function getDataPublicacao(): ?DateTimeImmutable
    {
        return $this->dataPublicacao;
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

    # métodos de validação
    private function validarSequencialVersao(): void
    {
        if ($this->sequencialVersao <= 0) {
            throw new PlanoException('Versão sequencial do plano deve ser maior que zero.');
        }
    }

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

            if (
                $demanda->getUnidadeOrcamentaria()->getLotacao()->getId()
                !== $this->unidadeOrcamentaria->getLotacao()->getId()
            ) {
                throw new PlanoException('Demandas devem pertencer a mesma Unidade Orçamentária do Plano.');
            }

            if ($demanda->getAnoExecucao() !== $this->anoExecucao->getValor()) {
                throw new PlanoException('Demandas devem pertencer ao mesmo Ano de Execução do Plano.');
            }
        }
    }
}
