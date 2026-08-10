<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;
use Semae\Pca\Domain\Enums\SituacaoDemanda;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\DecimalExato;

class Demanda
{
    public function __construct(
        private readonly int $idDemanda,
        private readonly SituacaoDemanda $situacao,
        private readonly int $idUnidadeRequisitante,
        private readonly UnidadeOrcamentaria $unidadeOrcamentaria,
        private readonly AnoExecucao $anoExecucao,
        private readonly DateTimeImmutable $dataDesejada,
        private readonly int $categoria,
        private readonly string $descricao,
        private readonly string $unidadeMedida,
        private readonly int $quantidade,
        private readonly DecimalExato $valorTotal,
        private readonly DecimalExato $valorOrcamentoExercicio,
        private readonly int $catalogo,
        private readonly string $codigoItem,
        private readonly string $grupo,
        private readonly string $descricaoGrupo,
        private readonly string $origem,
    ) {
        $this->validarCategoria();
        $this->validarQuantidade();
        $this->validarCatalogo();
        $this->validarOrigem();
    }

    public function getIdDemanda(): int
    {
        return $this->idDemanda;
    }

    public function getSituacao(): SituacaoDemanda
    {
        return $this->situacao;
    }

    public function participaDaFotografiaVigente(): bool
    {
        return $this->situacao->participaDaFotografiaVigente();
    }

    public function getIdUnidadeRequisitante(): int
    {
        return $this->idUnidadeRequisitante;
    }

    public function getUnidadeOrcamentaria(): UnidadeOrcamentaria
    {
        return $this->unidadeOrcamentaria;
    }

    public function getDataDesejada(): DateTimeImmutable
    {
        return $this->dataDesejada;
    }

    public function getAnoExecucao(): AnoExecucao
    {
        return $this->anoExecucao;
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

    public function getValorTotal(): DecimalExato
    {
        return $this->valorTotal;
    }

    public function getValorOrcamentoExercicio(): DecimalExato
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

    private function validarCategoria(): void
    {
        if (!in_array($this->categoria, [1, 2], true)) {
            throw new InvalidArgumentException('A categoria deve ser 1 (Material) ou 2 (Serviço).');
        }
    }

    private function validarQuantidade(): void
    {
        if ($this->quantidade <= 0) {
            throw new InvalidArgumentException('A quantidade deve ser maior que zero.');
        }
    }

    private function validarCatalogo(): void
    {
        if (!in_array($this->catalogo, [1, 2], true)) {
            throw new InvalidArgumentException('O catálogo deve ser 1 (Catálogo de bens e serviços do Compras.gov.br) ou 2 (Outros).');
        }
    }

    private function validarOrigem(): void
    {
        if (!in_array($this->origem, ['N', 'E'], true)) {
            throw new InvalidArgumentException('A origem deve ser N (normal) ou E (extraordinária).');
        }
    }
}
