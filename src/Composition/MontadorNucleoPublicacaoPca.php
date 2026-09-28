<?php

declare(strict_types=1);

namespace Semae\Pca\Composition;

use Psr\Log\LoggerInterface;
use Semae\Pca\Application\Ports\AutenticadorPncpPort;
use Semae\Pca\Application\Ports\ContextoUsuarioPort;
use Semae\Pca\Application\Ports\EscritorPncpPort;
use Semae\Pca\Application\Ports\EstadoOperacionalPublicacaoPort;
use Semae\Pca\Application\Ports\EstrategiaPublicacaoPort;
use Semae\Pca\Application\Ports\LeitorPncpPort;
use Semae\Pca\Application\Ports\RegistroAuditoriaPort;
use Semae\Pca\Application\Ports\UnidadeDeTrabalhoPort;
use Semae\Pca\Application\UseCases\PublicarPca\CoordenadorVarreduraUnidades;
use Semae\Pca\Application\UseCases\PublicarPca\DecisorUnidadeSobExclusividade;
use Semae\Pca\Application\UseCases\PublicarPca\DespachadorComandosUnidade;
use Semae\Pca\Application\UseCases\PublicarPca\ExecutorComandosUnidade;
use Semae\Pca\Application\UseCases\PublicarPca\FinalizadorProcessamentoUnidadeInterno;
use Semae\Pca\Application\UseCases\PublicarPca\PreparadorExecucaoPublicacaoPca;
use Semae\Pca\Application\UseCases\PublicarPca\PreparadorUnidadeSobExclusividade;
use Semae\Pca\Application\UseCases\PublicarPca\ProcessadorUnidadeInterno;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaUseCase;
use Semae\Pca\Domain\Policies\PoliticaProcessamentoPlano;
use Semae\Pca\Domain\Ports\PlanoRepository;

final class MontadorNucleoPublicacaoPca
{
    public function __construct(
        private readonly ContextoUsuarioPort $contextoUsuario,
        private readonly PlanoRepository $planoRepository,
        private readonly EstrategiaPublicacaoPort $estrategiaPublicacao,
        private readonly UnidadeDeTrabalhoPort $unidadeDeTrabalho,
        private readonly AutenticadorPncpPort $autenticadorPncp,
        private readonly LeitorPncpPort $leitorPncp,
        private readonly EscritorPncpPort $escritorPncp,
        private readonly EstadoOperacionalPublicacaoPort $estadoOperacional,
        private readonly RegistroAuditoriaPort $registroAuditoria,
        private readonly LoggerInterface $logger
    ) {}

    public function montar(): PublicarPcaUseCase
    {
        $politica = new PoliticaProcessamentoPlano();
        $executor = new ExecutorComandosUnidade(
            $this->escritorPncp,
            $this->unidadeDeTrabalho
        );
        $finalizador = new FinalizadorProcessamentoUnidadeInterno(
            $this->leitorPncp,
            $politica,
            $executor,
            $this->planoRepository,
            $this->estadoOperacional,
            $this->registroAuditoria,
            $this->unidadeDeTrabalho,
            $this->logger
        );
        $processador = new ProcessadorUnidadeInterno(
            new PreparadorUnidadeSobExclusividade(
                $this->unidadeDeTrabalho,
                $this->planoRepository,
                $this->estadoOperacional
            ),
            new DecisorUnidadeSobExclusividade(
                $this->leitorPncp,
                $politica,
                $this->unidadeDeTrabalho
            ),
            new DespachadorComandosUnidade(
                $executor,
                $finalizador,
                $this->unidadeDeTrabalho,
                $this->logger
            )
        );

        return new PublicarPcaUseCase(
            new PreparadorExecucaoPublicacaoPca(
                $this->contextoUsuario,
                $this->planoRepository,
                $this->estrategiaPublicacao,
                $this->unidadeDeTrabalho,
                $this->autenticadorPncp
            ),
            new CoordenadorVarreduraUnidades($processador)
        );
    }
}
