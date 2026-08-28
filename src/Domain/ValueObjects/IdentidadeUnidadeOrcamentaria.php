<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\ValueObjects;

use InvalidArgumentException;

final class IdentidadeUnidadeOrcamentaria
{
    private readonly string $valor;

    public function __construct(string $valorCanonico)
    {
        $valorCanonico = trim($valorCanonico);

        if ($valorCanonico === '') {
            throw new InvalidArgumentException('Identidade da Unidade Orçamentária é obrigatória.');
        }

        $this->valor = $valorCanonico;
    }

    public function getValor(): string
    {
        return $this->valor;
    }

    public function equals(self $outra): bool
    {
        return $this->valor === $outra->valor;
    }
}
