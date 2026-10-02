<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Facts;

use DateTimeImmutable;
use Semae\Pca\Domain\Enums\ProcedimentoPublicacaoPca;
use Semae\Pca\Domain\Exceptions\HistoricoProcedimentoException;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Representante;

final class HistoricoProcedimento
{
    private readonly string $mensagem;

    public function __construct(
        private readonly DateTimeImmutable $instante,
        private readonly ProcedimentoPublicacaoPca $procedimento,
        string $mensagem,
        private readonly Representante $representante,
        private readonly Lotacao $lotacao
    ) {
        $mensagem = trim($mensagem);

        if ($mensagem === '') {
            throw new HistoricoProcedimentoException('Mensagem do histórico é obrigatória.');
        }

        $this->mensagem = $mensagem;
    }

    public function getInstante(): DateTimeImmutable
    {
        return $this->instante;
    }

    public function getProcedimento(): ProcedimentoPublicacaoPca
    {
        return $this->procedimento;
    }

    public function getMensagem(): string
    {
        return $this->mensagem;
    }

    public function getRepresentante(): Representante
    {
        return $this->representante;
    }

    public function getLotacao(): Lotacao
    {
        return $this->lotacao;
    }
}
