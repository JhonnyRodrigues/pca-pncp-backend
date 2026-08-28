<?php

declare(strict_types=1);

namespace Semae\Pca\Application\ValueObjects;

use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Domain\Policies\ResultadoDecisaoProcessamentoPlano;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\PcaObservado;

final class ContextoUnidadeComDecisao
{
    public function __construct(
        private readonly SessaoPncp $sessaoPncp,
        private readonly Cnpj $cnpj,
        private readonly ContextoUnidadeSobExclusividade $contextoSobExclusividade,
        private readonly PcaObservado $pcaObservado,
        private readonly ResultadoDecisaoProcessamentoPlano $resultadoDecisao
    ) {}

    public function getSessaoPncp(): SessaoPncp
    {
        return $this->sessaoPncp;
    }

    public function getCnpj(): Cnpj
    {
        return $this->cnpj;
    }

    public function getContextoSobExclusividade(): ContextoUnidadeSobExclusividade
    {
        return $this->contextoSobExclusividade;
    }

    public function getPcaObservado(): PcaObservado
    {
        return $this->pcaObservado;
    }

    public function getResultadoDecisao(): ResultadoDecisaoProcessamentoPlano
    {
        return $this->resultadoDecisao;
    }
}
