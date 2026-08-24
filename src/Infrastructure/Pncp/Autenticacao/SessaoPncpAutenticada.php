<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Autenticacao;

use InvalidArgumentException;
use Semae\Pca\Application\Contracts\SessaoPncp;

final class SessaoPncpAutenticada implements SessaoPncp
{
    public function __construct(private readonly string $tokenOpaco)
    {
        if ($tokenOpaco === '' || preg_match('/\s/', $tokenOpaco) === 1) {
            throw new InvalidArgumentException('Token da sessão PNCP é inválido.');
        }
    }

    public function getTokenOpaco(): string
    {
        return $this->tokenOpaco;
    }
}
