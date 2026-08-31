<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Ports;

use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Results\ResultadoBuscaCandidatosPca;
use Semae\Pca\Application\Results\ResultadoObservacaoPca;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\SequencialPca;

interface LeitorPncpPort
{
    public function observarPcaConhecido(
        SessaoPncp $sessao,
        Cnpj $cnpj,
        UnidadeExternaPublicacao $unidade,
        SequencialPca $sequencialConhecido
    ): ResultadoObservacaoPca;

    public function localizarCandidatos(
        SessaoPncp $sessao,
        Cnpj $cnpj,
        UnidadeExternaPublicacao $unidade
    ): ResultadoBuscaCandidatosPca;
}
