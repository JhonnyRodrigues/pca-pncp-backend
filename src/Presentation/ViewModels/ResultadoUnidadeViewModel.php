<?php

declare(strict_types=1);

namespace Semae\Pca\Presentation\ViewModels;

final class ResultadoUnidadeViewModel
{
    public function __construct(
        private readonly ?string $idUnidadeOrcamentaria,
        private readonly string $modo,
        private readonly int $anoExecucao,
        private readonly string $codigoUasg,
        private readonly string $descricaoUasg,
        private readonly string $situacao,
        private readonly ?string $decisao,
        private readonly ?int $sequencialPca,
        private readonly int $quantidadeInclusoes,
        private readonly int $quantidadeExclusoes,
        private readonly ?string $codigoFalha,
        private readonly ?string $mensagemFalha
    ) {}

    public function getIdUnidadeOrcamentaria(): ?string
    {
        return $this->idUnidadeOrcamentaria;
    }

    public function getModo(): string
    {
        return $this->modo;
    }

    public function getAnoExecucao(): int
    {
        return $this->anoExecucao;
    }

    public function getCodigoUasg(): string
    {
        return $this->codigoUasg;
    }

    public function getDescricaoUasg(): string
    {
        return $this->descricaoUasg;
    }

    public function getSituacao(): string
    {
        return $this->situacao;
    }

    public function getDecisao(): ?string
    {
        return $this->decisao;
    }

    public function getSequencialPca(): ?int
    {
        return $this->sequencialPca;
    }

    public function getQuantidadeInclusoes(): int
    {
        return $this->quantidadeInclusoes;
    }

    public function getQuantidadeExclusoes(): int
    {
        return $this->quantidadeExclusoes;
    }

    public function getCodigoFalha(): ?string
    {
        return $this->codigoFalha;
    }

    public function getMensagemFalha(): ?string
    {
        return $this->mensagemFalha;
    }
}
