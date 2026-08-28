<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Policies;

use InvalidArgumentException;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Exceptions\ConflitoEstadoPublicacaoException;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;
use Semae\Pca\Domain\ValueObjects\PcaObservado;

final class PoliticaProcessamentoPlano
{
    /** @param Plano[] $planos */
    public function decidir(
        array $planos,
        EstadoOperacionalPublicacao $estadoOperacional,
        PcaObservado $pcaObservado
    ): ResultadoDecisaoProcessamentoPlano {
        if ($planos === []) {
            throw new InvalidArgumentException('Política deve receber ao menos um Plano.');
        }

        $idsDesejados = $this->obterIdsDesejados($planos);
        $this->validarCoerencia($estadoOperacional, $pcaObservado);

        if (!$pcaObservado->estaAtivo()) {
            if ($idsDesejados === []) {
                return ResultadoDecisaoProcessamentoPlano::nenhumaAcao();
            }

            return ResultadoDecisaoProcessamentoPlano::publicar($idsDesejados);
        }

        if ($idsDesejados === []) {
            return ResultadoDecisaoProcessamentoPlano::excluirPca();
        }

        $idsObservados = $pcaObservado->getIdsDemandas();
        $inclusoes = array_values(array_diff($idsDesejados, $idsObservados));
        $exclusoes = array_values(array_diff($idsObservados, $idsDesejados));

        if ($inclusoes === [] && $exclusoes === []) {
            return ResultadoDecisaoProcessamentoPlano::nenhumaAcao();
        }

        return ResultadoDecisaoProcessamentoPlano::retificar($inclusoes, $exclusoes);
    }

    /**
     * @param Plano[] $planos
     * @return int[]
     */
    private function obterIdsDesejados(array $planos): array
    {
        $ids = [];

        foreach ($planos as $plano) {
            if (!$plano instanceof Plano) {
                throw new InvalidArgumentException('Política deve receber apenas Planos.');
            }

            foreach ($plano->getDemandas() as $demanda) {
                $ids[] = $demanda->getIdDemanda();
            }
        }

        if (count($ids) !== count(array_unique($ids))) {
            throw new InvalidArgumentException('Identidades de Demandas devem ser únicas entre os Planos.');
        }

        $ids = array_values($ids);
        sort($ids, SORT_NUMERIC);

        return $ids;
    }

    private function validarCoerencia(
        EstadoOperacionalPublicacao $estadoOperacional,
        PcaObservado $pcaObservado
    ): void {
        if (!$pcaObservado->estaAtivo()) {
            return;
        }

        if (!$estadoOperacional->estaAtivo()) {
            throw new ConflitoEstadoPublicacaoException('PCA ativo foi observado sem vínculo operacional ativo conhecido.');
        }

        if (!$estadoOperacional->getSequencialPca()?->equals($pcaObservado->getSequencialPca())) {
            throw new ConflitoEstadoPublicacaoException('Sequencial do PCA observado diverge do vínculo operacional conhecido.');
        }
    }
}
