<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Adodb;

use RuntimeException;

final class FalhaOracleException extends RuntimeException
{
    public function __construct(
        string $mensagemSegura,
        private readonly ?string $codigoOracle
    ) {
        parent::__construct($mensagemSegura);
    }

    public function getCodigoOracle(): ?string
    {
        return $this->codigoOracle;
    }
}
