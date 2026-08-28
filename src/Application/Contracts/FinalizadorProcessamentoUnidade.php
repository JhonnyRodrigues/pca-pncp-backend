<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Contracts;

use Semae\Pca\Application\Results\DesfechoProcessamentoUnidade;
use Semae\Pca\Application\ValueObjects\ContextoFinalizacaoProcessamentoUnidade;

interface FinalizadorProcessamentoUnidade
{
    public function finalizar(
        ContextoFinalizacaoProcessamentoUnidade $contexto
    ): DesfechoProcessamentoUnidade;
}
