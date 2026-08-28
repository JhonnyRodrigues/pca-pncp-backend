<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Results;

use Semae\Pca\Application\ValueObjects\FalhaSegura;

final class ResultadoAdocaoEstrategia
{
    private function __construct(
        private readonly bool $adotada,
        private readonly ?FalhaSegura $falha
    ) {}

    public static function adotada(): self
    {
        return new self(true, null);
    }

    public static function falha(FalhaSegura $falha): self
    {
        return new self(false, $falha);
    }

    public function foiAdotada(): bool
    {
        return $this->adotada;
    }

    public function getFalha(): ?FalhaSegura
    {
        return $this->falha;
    }
}
