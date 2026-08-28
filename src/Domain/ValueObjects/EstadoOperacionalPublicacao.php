<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\ValueObjects;

use Semae\Pca\Domain\Enums\EstadoPublicacao;

final class EstadoOperacionalPublicacao
{
    private function __construct(
        private readonly EstadoPublicacao $estado,
        private readonly ?SequencialPca $sequencialPca
    ) {}

    public static function naoPublicado(): self
    {
        return new self(EstadoPublicacao::NAO_PUBLICADO, null);
    }

    public static function publicado(SequencialPca $sequencialPca): self
    {
        return new self(EstadoPublicacao::PUBLICADO, $sequencialPca);
    }

    public static function excluido(SequencialPca $ultimoSequencialPca): self
    {
        return new self(EstadoPublicacao::EXCLUIDO, $ultimoSequencialPca);
    }

    public function getEstado(): EstadoPublicacao
    {
        return $this->estado;
    }

    public function getSequencialPca(): ?SequencialPca
    {
        return $this->sequencialPca;
    }

    public function estaAtivo(): bool
    {
        return $this->estado === EstadoPublicacao::PUBLICADO;
    }
}
