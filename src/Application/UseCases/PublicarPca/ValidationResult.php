<?php

declare(strict_types=1);

namespace Semae\Pca\Application\UseCases\PublicarPca;

class ValidationResult
{
    public function __construct(
        private readonly array $erros
    ) {}

    public function isValido(): bool
    {
        return $this->erros === [];
    }

    public function getErros(): array
    {
        return $this->erros;
    }
}
