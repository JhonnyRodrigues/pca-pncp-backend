<?php

declare(strict_types=1);

namespace Semae\Pca\Application\UseCases\PublicarPca;

use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;

class PublicarPcaRequest
{
    public function __construct(
        private readonly Cnpj $cnpj,
        private readonly AnoExecucao $anoExecucao
    ) {}

    public function getAnoExecucao(): AnoExecucao
    {
        return $this->anoExecucao;
    }

    public function getCnpj(): Cnpj
    {
        return $this->cnpj;
    }
}
