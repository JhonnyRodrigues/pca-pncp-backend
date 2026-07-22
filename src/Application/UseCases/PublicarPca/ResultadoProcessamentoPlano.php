<?php

declare(strict_types=1);

namespace Semae\Pca\Application\UseCases\PublicarPca;

use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;

class ResultadoProcessamentoPlano
{
    public function __construct(
        private readonly Plano $plano,
        private readonly DecisaoProcessamentoPlano $decisao
    ) {}

    public function getPlano(): Plano
    {
        return $this->plano;
    }

    public function getDecisao(): DecisaoProcessamentoPlano
    {
        return $this->decisao;
    }
}
