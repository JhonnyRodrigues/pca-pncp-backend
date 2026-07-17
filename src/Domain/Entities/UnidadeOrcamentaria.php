<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Entities;

use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Semae\Pca\Domain\Exceptions\UnidadeOrcamentariaInvalidaException;

class UnidadeOrcamentaria
{
    private readonly string $descricao;

    public function __construct(
        private readonly Lotacao $lotacao,
        private readonly Uasg $uasg,
        string $descricao
    ) {
        $descricao = trim($descricao);

        if ($descricao === '' || mb_strlen($descricao) < 3) {
            throw new UnidadeOrcamentariaInvalidaException('Descrição da unidade orçamentaria inválida.');
        }

        $this->descricao = $descricao;
    }

    public function getLotacao(): Lotacao
    {
        return $this->lotacao;
    }

    public function getUasg(): Uasg
    {
        return $this->uasg;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }
}
