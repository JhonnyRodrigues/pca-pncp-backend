<?php

declare(strict_types=1);

namespace Semae\Pca\Presentation\Exceptions;

use RuntimeException;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Throwable;

final class EntradaPublicarPcaInvalidaException extends RuntimeException
{
    public function __construct(
        private readonly FalhaSegura $falha,
        Throwable $anterior
    ) {
        parent::__construct($falha->getMensagem(), 0, $anterior);
    }

    public function getFalha(): FalhaSegura
    {
        return $this->falha;
    }
}
