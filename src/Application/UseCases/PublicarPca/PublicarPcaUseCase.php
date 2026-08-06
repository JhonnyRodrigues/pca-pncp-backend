<?php

declare(strict_types=1);

namespace Semae\Pca\Application\UseCases\PublicarPca;

use Semae\Pca\Application\Enums\SituacaoPreparacaoPublicacaoPca;
use Semae\Pca\Application\Enums\SituacaoProcessamentoUnidade;
use Semae\Pca\Application\Results\PublicarPcaResponse;
use Semae\Pca\Application\Results\ResultadoProcessamentoUnidade;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Throwable;

final class PublicarPcaUseCase
{
    public function __construct(
        private readonly PreparadorExecucaoPublicacaoPca $preparador,
        private readonly CoordenadorVarreduraUnidades $coordenador
    ) {}

    public function executar(
        PublicarPcaRequest $request,
        CorrelacaoExecucao $correlacao
    ): PublicarPcaResponse {
        try {
            $preparacao = $this->preparador->preparar($request);
        } catch (Throwable) {
            return PublicarPcaResponse::interrompida(
                [],
                new FalhaSegura(
                    'PREPARACAO_PUBLICACAO_FALHOU',
                    'Não foi possível preparar a execução da publicação.'
                ),
                0,
                0,
                $correlacao
            );
        }

        if ($preparacao->getSituacao() === SituacaoPreparacaoPublicacaoPca::SEM_UNIDADES) {
            return PublicarPcaResponse::concluida([], 0, 0, $correlacao);
        }

        $unidades = $preparacao->getUnidadesExternas();
        $quantidadePlanejadas = count($unidades);

        if ($preparacao->getSituacao() === SituacaoPreparacaoPublicacaoPca::INTERROMPIDA) {
            return PublicarPcaResponse::interrompida(
                [],
                $preparacao->getFalha() ?? new FalhaSegura(
                    'PREPARACAO_INTERROMPIDA',
                    'A preparação da publicação foi interrompida.'
                ),
                $quantidadePlanejadas,
                0,
                $correlacao
            );
        }

        $contextoUsuario = $preparacao->getContextoUsuario();
        $sessao = $preparacao->getSessaoPncp();

        if ($contextoUsuario === null || $sessao === null || $unidades === []) {
            return PublicarPcaResponse::interrompida(
                [],
                new FalhaSegura(
                    'PREPARACAO_INCONSISTENTE',
                    'A preparação concluída não forneceu os dados necessários à varredura.'
                ),
                $quantidadePlanejadas,
                0,
                $correlacao
            );
        }

        try {
            $varredura = $this->coordenador->processar(
                $contextoUsuario,
                $sessao,
                $request->getCnpj(),
                $unidades
            );
        } catch (Throwable) {
            return PublicarPcaResponse::interrompida(
                [],
                new FalhaSegura(
                    'VARREDURA_PUBLICACAO_FALHOU',
                    'Não foi possível executar a varredura das unidades.'
                ),
                $quantidadePlanejadas,
                0,
                $correlacao
            );
        }

        $resultados = $varredura->getResultadosUnitarios();
        $quantidadeVisitadas = $varredura->getQuantidadeVisitadas();

        if ($varredura->foiInterrompida()) {
            return PublicarPcaResponse::interrompida(
                $resultados,
                $varredura->getFalhaSistemica() ?? new FalhaSegura(
                    'VARREDURA_INTERROMPIDA',
                    'A varredura das unidades foi interrompida.'
                ),
                $quantidadePlanejadas,
                $quantidadeVisitadas,
                $correlacao
            );
        }

        if ($this->possuiPendencia($resultados)) {
            return PublicarPcaResponse::concluidaComPendencias(
                $resultados,
                $quantidadePlanejadas,
                $quantidadeVisitadas,
                $correlacao
            );
        }

        return PublicarPcaResponse::concluida(
            $resultados,
            $quantidadePlanejadas,
            $quantidadeVisitadas,
            $correlacao
        );
    }

    /** @param ResultadoProcessamentoUnidade[] $resultados */
    private function possuiPendencia(array $resultados): bool
    {
        foreach ($resultados as $resultado) {
            if (
                in_array(
                    $resultado->getSituacao(),
                    [
                        SituacaoProcessamentoUnidade::FALHA_RECONCILIAVEL,
                        SituacaoProcessamentoUnidade::BLOQUEADO,
                    ],
                    true
                )
            ) {
                return true;
            }
        }

        return false;
    }
}
