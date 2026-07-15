<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Entities;

use DateTimeImmutable;
use Semae\Pca\Domain\Exceptions\HistoricoProcedimentoException;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Representante;

class HistoricoProcedimento
{
    private const PROCEDIMENTO_PUBLICACAO_PLANO = 'PUBLICAÇÃO DE PLANO';

    public function __construct(
        private readonly DateTimeImmutable $dataInsercao,
        private readonly Procedimento $procedimento,
        private readonly string $mensagem,
        private readonly ?Representante $representante = null,
        private readonly ?Lotacao $lotacao = null
    ) {
        $this->validarPublicacaoPlano();
    }

    public function getProcedimento(): Procedimento
    {
        return $this->procedimento;
    }

    public function getDataInsercao(): DateTimeImmutable
    {
        return $this->dataInsercao;
    }

    public function getMensagem(): string
    {
        return $this->mensagem;
    }

    public function getLotacao(): ?Lotacao
    {
        return $this->lotacao;
    }

    public function getRepresentante(): ?Representante
    {
        return $this->representante;
    }

    private function validarPublicacaoPlano(): void
    {
        if ($this->procedimento->getDescricao() !== self::PROCEDIMENTO_PUBLICACAO_PLANO) {
            return;
        }

        if ($this->mensagem === '') {
            throw new HistoricoProcedimentoException('Mensagem é obrigatória para publicação de plano.');
        }

        if ($this->lotacao === null) {
            throw new HistoricoProcedimentoException('Lotação é obrigatória para publicação de plano.');
        }

        if ($this->representante === null) {
            throw new HistoricoProcedimentoException('Representante é obrigatório para publicação de plano.');
        }
    }
}
