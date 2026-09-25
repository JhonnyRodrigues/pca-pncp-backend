<?php

declare(strict_types=1);

namespace Semae\Pca\Presentation\Presenters;

use Semae\Pca\Application\Results\PublicarPcaResponse;
use Semae\Pca\Application\Results\ResultadoProcessamentoUnidade;
use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;
use Semae\Pca\Presentation\ViewModels\ResultadoUnidadeViewModel;

final class PublicarPcaPresenter
{
    public function apresentar(PublicarPcaResponse $response): PublicarPcaViewModel
    {
        $falha = $response->getFalhaGlobal();
        $resultados = [];

        foreach ($response->getResultadosUnitarios() as $resultado) {
            $resultados[] = $this->apresentarResultadoUnitario($resultado);
        }

        return new PublicarPcaViewModel(
            $response->getSituacao()->value,
            $resultados,
            $falha?->getCodigo(),
            $falha?->getMensagem(),
            $response->getQuantidadePlanejadas(),
            $response->getQuantidadeVisitadas(),
            $response->getQuantidadeNaoProcessadas(),
            $response->getCorrelacao()->getValor()
        );
    }

    private function apresentarResultadoUnitario(
        ResultadoProcessamentoUnidade $resultado
    ): ResultadoUnidadeViewModel {
        $unidade = $resultado->getUnidadeExterna();
        $identidadePlano = $unidade->getIdentidadePlano();
        $idUnidadeOrcamentaria = $identidadePlano === null
            ? null
            : $identidadePlano
                ->getUnidadeOrcamentaria()
                ->getIdentidade()
                ->getValor();
        $uasg = $unidade->getUasgExterna();
        $falha = $resultado->getFalha();

        return new ResultadoUnidadeViewModel(
            $idUnidadeOrcamentaria,
            $unidade->getModo()->value,
            $unidade->getAnoExecucao()->getValor(),
            $uasg->getCodigo(),
            $uasg->getDescricao(),
            $resultado->getSituacao()->value,
            $resultado->getDecisao()?->value,
            $resultado->getSequencialPca()?->getValor(),
            $resultado->getQuantidadeInclusoes(),
            $resultado->getQuantidadeExclusoes(),
            $falha?->getCodigo(),
            $falha?->getMensagem(),
            $resultado->getNumerosItensIncluidos(),
            $resultado->getNumerosItensExcluidos()
        );
    }
}
