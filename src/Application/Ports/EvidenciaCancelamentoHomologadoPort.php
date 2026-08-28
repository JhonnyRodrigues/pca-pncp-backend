<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Ports;

use Semae\Pca\Application\Results\ResultadoConsultaEvidenciaCancelamento;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;

interface EvidenciaCancelamentoHomologadoPort
{
    /**
     * @param non-empty-list<IdentidadePlano> $identidades
     */
    public function consultarUltimoCancelamentoQueTornouUnidadeVazia(
        UnidadeExternaPublicacao $unidade,
        array $identidades
    ): ResultadoConsultaEvidenciaCancelamento;
}
