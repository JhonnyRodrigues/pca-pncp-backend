<?php

declare(strict_types=1);

namespace Semae\Pca\Application\UseCases\PublicarPca;

use Semae\Pca\Application\Enums\SituacaoFinalizacaoUnidadeTrabalho;
use Semae\Pca\Application\Ports\UnidadeDeTrabalhoPort;
use Semae\Pca\Application\Results\DesfechoProcessamentoUnidade;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Throwable;

final class DesfazedorUnidadeProcessamento
{
    public function __construct(
        private readonly UnidadeDeTrabalhoPort $unidadeDeTrabalho
    ) {}

    public function desfazerPreservando(
        DesfechoProcessamentoUnidade $desfechoOriginal
    ): DesfechoProcessamentoUnidade {
        try {
            $resultado = $this->unidadeDeTrabalho->desfazer();
        } catch (Throwable) {
            return self::falhaDesfazimento();
        }

        if (
            $resultado->getSituacao()
            === SituacaoFinalizacaoUnidadeTrabalho::CONCLUIDA
        ) {
            return $desfechoOriginal;
        }

        return DesfechoProcessamentoUnidade::falhaSistemica(
            $resultado->getFalha() ?? new FalhaSegura(
                'DESFAZIMENTO_UNIDADE_FALHOU',
                'Não foi possível desfazer a unidade de processamento.'
            )
        );
    }

    private static function falhaDesfazimento(): DesfechoProcessamentoUnidade
    {
        return DesfechoProcessamentoUnidade::falhaSistemica(new FalhaSegura(
            'DESFAZIMENTO_UNIDADE_FALHOU',
            'Não foi possível desfazer a unidade de processamento.'
        ));
    }
}
