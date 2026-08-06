<?php

declare(strict_types=1);

namespace Semae\Pca\Application\UseCases\PublicarPca;

use Semae\Pca\Application\Contracts\ProcessadorUnidade;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Results\DesfechoProcessamentoUnidade;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\ValueObjects\Cnpj;

final class ProcessadorUnidadeInterno implements ProcessadorUnidade
{
    public function __construct(
        private readonly PreparadorUnidadeSobExclusividade $preparador,
        private readonly DecisorUnidadeSobExclusividade $decisor,
        private readonly DespachadorComandosUnidade $despachador
    ) {}

    public function processar(
        ContextoUsuario $contextoUsuario,
        SessaoPncp $sessao,
        Cnpj $cnpj,
        UnidadeExternaPublicacao $unidade
    ): DesfechoProcessamentoUnidade {
        $preparacao = $this->preparador->preparar($unidade);

        if ($preparacao instanceof DesfechoProcessamentoUnidade) {
            return $preparacao;
        }

        $decisao = $this->decisor->decidir($sessao, $cnpj, $preparacao);

        if ($decisao instanceof DesfechoProcessamentoUnidade) {
            return $decisao;
        }

        return $this->despachador->despachar($contextoUsuario, $decisao);
    }
}
