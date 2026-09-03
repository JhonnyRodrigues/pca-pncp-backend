<?php

declare(strict_types=1);

namespace Semae\Pca\Composition;

use Psr\Log\LoggerInterface;
use Semae\Pca\Infrastructure\Pncp\Autenticacao\AutenticadorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Escrita\EscritorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\ConfiguracaoLeituraPncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\LeitorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Leitura\TemporizadorPncp;
use Semae\Pca\Infrastructure\Pncp\Serializacao\SerializadorPayloadPncp;

final class ComposicaoPncpPublicacaoPca
{
    private readonly AutenticadorPncpAdapter $autenticador;
    private readonly LeitorPncpAdapter $leitor;
    private readonly EscritorPncpAdapter $escritor;

    public function __construct(
        PerfilPublicacaoPca $perfil,
        TransporteHttpPncp $transporte,
        ConfiguracaoLeituraPncp $configuracaoLeitura,
        LoggerInterface $logger,
        TemporizadorPncp $temporizador
    ) {
        $configuracaoTransporte = $perfil->getConfiguracaoTransporte();
        $this->autenticador = new AutenticadorPncpAdapter(
            $transporte,
            $configuracaoTransporte,
            $logger,
            $perfil->getLoginPncp(),
            $perfil->getSenhaPncp()
        );
        $this->leitor = new LeitorPncpAdapter(
            $transporte,
            $configuracaoTransporte,
            $configuracaoLeitura,
            $logger,
            $temporizador
        );
        $this->escritor = new EscritorPncpAdapter(
            $transporte,
            $configuracaoTransporte,
            new SerializadorPayloadPncp(),
            $logger
        );
    }

    public function getAutenticador(): AutenticadorPncpAdapter
    {
        return $this->autenticador;
    }

    public function getLeitor(): LeitorPncpAdapter
    {
        return $this->leitor;
    }

    public function getEscritor(): EscritorPncpAdapter
    {
        return $this->escritor;
    }
}
