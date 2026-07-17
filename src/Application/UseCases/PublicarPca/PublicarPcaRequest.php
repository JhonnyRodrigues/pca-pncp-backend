<?php

declare(strict_types=1);

namespace Semae\Pca\Application\UseCases\PublicarPca;

use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;

class PublicarPcaRequest
{
    public function __construct(
        private readonly Cnpj $cnpj,
        private readonly AnoExecucao $anoExecucao,
        private readonly string $nomeUsuario
    ) {}

    public function getAnoExecucao(): AnoExecucao
    {
        return $this->anoExecucao;
    }

    public function getCnpj(): Cnpj
    {
        return $this->cnpj;
    }

    public function getNomeUsuario(): string
    {
        return $this->nomeUsuario;
    }
}
