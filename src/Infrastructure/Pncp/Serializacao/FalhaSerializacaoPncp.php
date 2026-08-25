<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Serializacao;

use RuntimeException;

final class FalhaSerializacaoPncp extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Não foi possível serializar o payload PNCP.');
    }
}
