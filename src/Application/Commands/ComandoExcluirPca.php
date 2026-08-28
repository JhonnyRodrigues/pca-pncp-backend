<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Commands;

use InvalidArgumentException;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\SequencialPca;

final class ComandoExcluirPca
{
    private readonly string $justificativa;

    public function __construct(
        private readonly Cnpj $cnpj,
        private readonly UnidadeExternaPublicacao $unidadeExterna,
        private readonly SequencialPca $sequencialPca,
        string $justificativa
    ) {
        $justificativa = trim($justificativa);

        if ($justificativa === '') {
            throw new InvalidArgumentException('Justificativa da exclusão integral é obrigatória.');
        }

        $this->justificativa = $justificativa;
    }

    public function getCnpj(): Cnpj
    {
        return $this->cnpj;
    }

    public function getUnidadeExterna(): UnidadeExternaPublicacao
    {
        return $this->unidadeExterna;
    }

    public function getSequencialPca(): SequencialPca
    {
        return $this->sequencialPca;
    }

    public function getJustificativa(): string
    {
        return $this->justificativa;
    }
}
