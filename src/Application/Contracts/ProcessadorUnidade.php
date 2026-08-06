<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Contracts;

use Semae\Pca\Application\Results\DesfechoProcessamentoUnidade;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\ValueObjects\Cnpj;

interface ProcessadorUnidade
{
    public function processar(
        ContextoUsuario $contextoUsuario,
        SessaoPncp $sessao,
        Cnpj $cnpj,
        UnidadeExternaPublicacao $unidade
    ): DesfechoProcessamentoUnidade;
}
