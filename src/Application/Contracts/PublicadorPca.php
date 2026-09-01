<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Contracts;

use Semae\Pca\Application\Results\PublicarPcaResponse;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaRequest;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;

interface PublicadorPca
{
    public function executar(
        PublicarPcaRequest $request,
        CorrelacaoExecucao $correlacao
    ): PublicarPcaResponse;
}
