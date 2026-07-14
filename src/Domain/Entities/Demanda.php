<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Entities;

use DateTimeImmutable;
use Semae\Pca\Domain\ValueObjects\UnidadeOrcamentaria;
use Semae\Pca\Domain\Exceptions\SequencialPncpException;

class Demanda
{
    public function __construct(
        private readonly int $idDemanda,
        private readonly int $idSituacao,
        private readonly int $idUnidadeRequisitante,
        private readonly int $idPlano,
        private readonly UnidadeOrcamentaria $unidadeOrcamentaria,
        private readonly DateTimeImmutable $dataDesejada,
        private readonly int $categoria,
        private readonly string $descricao,
        private readonly string $unidadeMedida,
        private readonly int $quantidade,
        // private readonly float $valorUnitario, /* $valorTotal / $quantidade */
        private readonly float $valorTotal,
        private readonly float $valorOrcamentoExercicio,
        private readonly int $catalogo,
        private readonly string $codigoItem,
        private readonly string $grupo, /* classificacaoSuperiorCodigo */
        private readonly string $descricaoGrupo, /* MGM.DESCRICAO -> LEFT JOIN CEBISIS.MT_GRUPOS_MATERIAIS@READ_ORCL */
        private readonly string $origem, /* N: normal ou E: extraordinaria*/
        private         ?int $sequencialPncp = null,
    ) {}

    public function getIdDemanda(): int
    {
        return $this->idDemanda;
    }

    public function getIdSituacao(): int
    {
        return $this->idSituacao;
    }

    public function getIdUnidadeRequisitante(): int
    {
        return $this->idUnidadeRequisitante;
    }

    public function getIdPlano(): int
    {
        return $this->idPlano;
    }

    public function getUnidadeOrcamentaria(): UnidadeOrcamentaria
    {
        return $this->unidadeOrcamentaria;
    }

    public function getDataDesejada(): DateTimeImmutable
    {
        return $this->dataDesejada;
    }

    public function getCategoria(): int
    {
        return $this->categoria;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function getUnidadeMedida(): string
    {
        return $this->unidadeMedida;
    }

    public function getQuantidade(): int
    {
        return $this->quantidade;
    }

    public function getValorTotal(): float
    {
        return $this->valorTotal;
    }

    public function getValorOrcamentoExercicio(): float
    {
        return $this->valorOrcamentoExercicio;
    }

    public function getCatalogo(): int
    {
        return $this->catalogo;
    }

    public function getCodigoItem(): string
    {
        return $this->codigoItem;
    }

    public function getGrupo(): string
    {
        return $this->grupo;
    }

    public function getDescricaoGrupo(): string
    {
        return $this->descricaoGrupo;
    }

    public function getOrigem(): string
    {
        return $this->origem;
    }

    public function getValorUnitario(): float {
        return ($this->quantidade > 0) ? $this->valorTotal / $this->quantidade : 0.0;
    }

    public function getSequencialPncp(): ?int {
        return $this->sequencialPncp;
    }

    public function setSequencialPncp(int $sequencialPncp): void
    {
        if ($sequencialPncp <= 0) {
            throw new SequencialPncpException("O número sequencial do PNCP deve ser maior que zero.");
        }

        $this->sequencialPncp = $sequencialPncp;
    }
}
