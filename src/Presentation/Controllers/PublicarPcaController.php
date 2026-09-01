<?php

declare(strict_types=1);

namespace Semae\Pca\Presentation\Controllers;

use Semae\Pca\Application\Contracts\PublicadorPca;
use Semae\Pca\Application\Results\PublicarPcaResponse;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaRequest;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Presentation\Inputs\EntradaPublicarPca;

final class PublicarPcaController
{
    public function __construct(private readonly PublicadorPca $publicador) {}

    public function executar(
        EntradaPublicarPca $entrada,
        CorrelacaoExecucao $correlacao
    ): PublicarPcaResponse {
        $request = new PublicarPcaRequest(
            new Cnpj($entrada->getCnpj()),
            new AnoExecucao($entrada->getAnoExecucao())
        );

        return $this->publicador->executar($request, $correlacao);
    }
}
