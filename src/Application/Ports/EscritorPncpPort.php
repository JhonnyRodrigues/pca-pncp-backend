<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Ports;

use Semae\Pca\Application\Commands\ComandoExcluirItensPca;
use Semae\Pca\Application\Commands\ComandoExcluirPca;
use Semae\Pca\Application\Commands\ComandoInserirItensPca;
use Semae\Pca\Application\Commands\ComandoPublicarPca;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Results\ResultadoEscritaPncp;
use Semae\Pca\Application\Results\ResultadoPublicacaoPncp;

interface EscritorPncpPort
{
    public function publicar(
        SessaoPncp $sessao,
        ComandoPublicarPca $comando
    ): ResultadoPublicacaoPncp;

    public function inserirItens(
        SessaoPncp $sessao,
        ComandoInserirItensPca $comando
    ): ResultadoEscritaPncp;

    public function excluirItens(
        SessaoPncp $sessao,
        ComandoExcluirItensPca $comando
    ): ResultadoEscritaPncp;

    public function excluirPca(
        SessaoPncp $sessao,
        ComandoExcluirPca $comando
    ): ResultadoEscritaPncp;
}
