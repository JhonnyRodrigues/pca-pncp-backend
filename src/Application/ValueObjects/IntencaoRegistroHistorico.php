<?php

declare(strict_types=1);

namespace Semae\Pca\Application\ValueObjects;

use InvalidArgumentException;
use Semae\Pca\Domain\Enums\ProcedimentoPublicacaoPca;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Representante;

final class IntencaoRegistroHistorico
{
    private readonly string $mensagem;

    public function __construct(
        private readonly ProcedimentoPublicacaoPca $procedimento,
        string $mensagem,
        private readonly Representante $representante,
        private readonly Lotacao $lotacao
    ) {
        $mensagem = trim($mensagem);

        if ($mensagem === '') {
            throw new InvalidArgumentException('Mensagem da intenção de histórico é obrigatória.');
        }

        $this->mensagem = $mensagem;
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
