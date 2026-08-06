<?php

declare(strict_types=1);

namespace Semae\Pca\Application\UseCases\PublicarPca;

use Semae\Pca\Application\Contracts\FinalizadorProcessamentoUnidade;
use Semae\Pca\Application\Ports\UnidadeDeTrabalhoPort;
use Semae\Pca\Application\Results\DesfechoProcessamentoUnidade;
use Semae\Pca\Application\ValueObjects\ContextoFinalizacaoProcessamentoUnidade;
use Semae\Pca\Application\ValueObjects\ContextoUnidadeComDecisao;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Throwable;

final class DespachadorComandosUnidade
{
    private readonly DesfazedorUnidadeProcessamento $desfazedor;

    public function __construct(
        private readonly ExecutorComandosUnidade $executor,
        private readonly FinalizadorProcessamentoUnidade $finalizador,
        UnidadeDeTrabalhoPort $unidadeDeTrabalho
    ) {
        $this->desfazedor = new DesfazedorUnidadeProcessamento($unidadeDeTrabalho);
    }

    public function despachar(
        ContextoUsuario $contextoUsuario,
        ContextoUnidadeComDecisao $contexto
    ): DesfechoProcessamentoUnidade {
        try {
            $resultados = $this->executor->executar($contexto);
        } catch (Throwable) {
            return $this->interromperEDesfazer(new FalhaSegura(
                'DESPACHO_COMANDO_FALHOU',
                'Não foi possível despachar o comando da unidade.'
            ));
        }

        if ($resultados instanceof DesfechoProcessamentoUnidade) {
            return $resultados;
        }

        $sobExclusividade = $contexto->getContextoSobExclusividade();

        try {
            return $this->finalizador->finalizar(
                new ContextoFinalizacaoProcessamentoUnidade(
                    $contextoUsuario,
                    $contexto->getSessaoPncp(),
                    $contexto->getCnpj(),
                    $sobExclusividade->getUnidadeExterna(),
                    $sobExclusividade->getPlanos(),
                    $sobExclusividade->getEstadoOperacional(),
                    $contexto->getPcaObservado(),
                    $contexto->getResultadoDecisao(),
                    $resultados
                )
            );
        } catch (Throwable) {
            return $this->interromperEDesfazer(new FalhaSegura(
                'FINALIZACAO_UNIDADE_FALHOU',
                'Não foi possível finalizar o processamento da unidade.'
            ));
        }
    }

    private function interromperEDesfazer(
        FalhaSegura $falha
    ): DesfechoProcessamentoUnidade {
        return $this->desfazedor->desfazerPreservando(
            DesfechoProcessamentoUnidade::falhaSistemica($falha)
        );
    }
}
