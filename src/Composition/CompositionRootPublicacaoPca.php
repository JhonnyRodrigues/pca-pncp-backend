<?php

declare(strict_types=1);

namespace Semae\Pca\Composition;

use InvalidArgumentException;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Infrastructure\Observabilidade\AcumuladorEventosTecnicos;
use Semae\Pca\Infrastructure\Observabilidade\RelogioSistemaEventosTecnicos;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Pncp\Autenticacao\AutenticadorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Escrita\EscritorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Http\ExecutorCurlNativoPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteCurlPncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\LeitorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Leitura\TemporizadorSistemaPncp;
use Semae\Pca\Infrastructure\Pncp\Serializacao\SerializadorPayloadPncp;
use Semae\Pca\Presentation\Controllers\PublicarPcaController;
use Semae\Pca\Presentation\Facades\FachadaPublicarPca;
use Semae\Pca\Presentation\Presenters\PublicarPcaPresenter;

final class CompositionRootPublicacaoPca
{
    private const MODULO_OBSERVABILIDADE = 'publicacao-pca';

    /** @param array<string, mixed> $perfil */
    public function montar(
        object $conexaoAdodb,
        string $loginAutenticado,
        array $perfil,
        CorrelacaoExecucao $correlacao
    ): FachadaPublicarPca {
        $perfilTipado = new PerfilPublicacaoPca($perfil);

        if (trim($loginAutenticado) === '') {
            throw new InvalidArgumentException('O login autenticado é obrigatório.');
        }

        $conexao = new ConexaoAdodbEmprestada($conexaoAdodb);
        $oracle = new ComposicaoOraclePublicacaoPca(
            $conexao,
            $perfilTipado,
            $loginAutenticado
        );
        $configuracaoTransporte = $perfilTipado->getConfiguracaoTransporte();
        $transporte = new TransporteCurlPncp(
            $configuracaoTransporte,
            new ExecutorCurlNativoPncp()
        );
        $logger = new AcumuladorEventosTecnicos(
            $correlacao->getValor(),
            $perfilTipado->getAmbiente()->value,
            self::MODULO_OBSERVABILIDADE,
            null,
            new RelogioSistemaEventosTecnicos()
        );

        $montadorNucleo = new MontadorNucleoPublicacaoPca(
            $oracle->getContextoUsuario(),
            $oracle->getPlanoRepository(),
            $oracle->getEstrategiaPublicacao(),
            $oracle->getUnidadeDeTrabalho(),
            new AutenticadorPncpAdapter(
                $transporte,
                $configuracaoTransporte,
                $logger,
                $perfilTipado->getLoginPncp(),
                $perfilTipado->getSenhaPncp()
            ),
            new LeitorPncpAdapter(
                $transporte,
                $configuracaoTransporte,
                $perfilTipado->getConfiguracaoLeitura(),
                $logger,
                new TemporizadorSistemaPncp()
            ),
            new EscritorPncpAdapter(
                $transporte,
                $configuracaoTransporte,
                new SerializadorPayloadPncp(),
                $logger
            ),
            $oracle->getEstadoOperacional(),
            $oracle->getRegistroAuditoria(),
            $logger
        );
        $presenter = new PublicarPcaPresenter();

        return new FachadaPublicarPca(
            new PublicarPcaController($montadorNucleo->montar(), $presenter),
            $presenter,
            $logger
        );
    }
}
