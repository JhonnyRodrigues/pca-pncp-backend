<?php

declare(strict_types=1);

namespace Semae\Pca\Composition;

use InvalidArgumentException;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Infrastructure\Observabilidade\AcumuladorEventosTecnicos;
use Semae\Pca\Infrastructure\Observabilidade\RelogioSistemaEventosTecnicos;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Pncp\Http\ExecutorCurlNativoPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteCurlPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\TemporizadorSistemaPncp;
use Semae\Pca\Presentation\Controllers\PublicarPcaController;
use Semae\Pca\Presentation\Facades\FachadaPublicarPca;
use Semae\Pca\Presentation\Presenters\PublicarPcaPresenter;

final class CompositionRootPublicacaoPca
{
    private const MODULO_OBSERVABILIDADE = 'publicacao-pca';

    public function __construct(
        private readonly ?TransporteHttpPncp $transportePncp = null
    ) {}

    /** @param array<string, mixed> $perfil */
    public function montar(
        object $conexaoAdodb,
        string $loginAutenticado,
        array $perfil,
        CorrelacaoExecucao $correlacao
    ): FachadaPublicarPca {
        return $this->montarExecucao(
            $conexaoAdodb,
            $loginAutenticado,
            $perfil,
            $correlacao
        )->getFachada();
    }

    /** @param array<string, mixed> $perfil */
    public function montarExecucao(
        object $conexaoAdodb,
        string $loginAutenticado,
        array $perfil,
        CorrelacaoExecucao $correlacao
    ): ComposicaoExecucaoPublicacaoPca {
        $perfilTipado = new PerfilPublicacaoPca($perfil);

        if (trim($loginAutenticado) === '') {
            throw new InvalidArgumentException('O login autenticado é obrigatório.');
        }

        $conexao = new ConexaoAdodbEmprestada(
            $conexaoAdodb,
            $perfilTipado->getCharsetAdodb()
        );
        $oracle = new ComposicaoOraclePublicacaoPca(
            $conexao,
            $perfilTipado,
            $loginAutenticado
        );
        $configuracaoTransporte = $perfilTipado->getConfiguracaoTransporte();
        $transporte = $this->transportePncp ?? new TransporteCurlPncp(
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
        $pncp = new ComposicaoPncpPublicacaoPca(
            $perfilTipado,
            $transporte,
            $perfilTipado->getConfiguracaoLeitura(),
            $logger,
            new TemporizadorSistemaPncp()
        );

        $montadorNucleo = new MontadorNucleoPublicacaoPca(
            $oracle->getContextoUsuario(),
            $oracle->getPlanoRepository(),
            $oracle->getEstrategiaPublicacao(),
            $oracle->getUnidadeDeTrabalho(),
            $pncp->getAutenticador(),
            $pncp->getLeitor(),
            $pncp->getEscritor(),
            $oracle->getEstadoOperacional(),
            $oracle->getRegistroAuditoria(),
            $logger
        );
        $presenter = new PublicarPcaPresenter();

        return new ComposicaoExecucaoPublicacaoPca(
            new FachadaPublicarPca(
                new PublicarPcaController($montadorNucleo->montar(), $presenter),
                $presenter,
                $logger
            ),
            $logger,
            $conexao
        );
    }
}
