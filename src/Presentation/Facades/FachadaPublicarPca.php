<?php

declare(strict_types=1);

namespace Semae\Pca\Presentation\Facades;

use Semae\Pca\Application\Results\PublicarPcaResponse;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Presentation\Controllers\PublicarPcaController;
use Semae\Pca\Presentation\Exceptions\EntradaPublicarPcaInvalidaException;
use Semae\Pca\Presentation\Inputs\EntradaPublicarPca;
use Semae\Pca\Presentation\Presenters\PublicarPcaPresenter;
use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;
use Throwable;

final class FachadaPublicarPca
{
    public function __construct(
        private readonly PublicarPcaController $controller,
        private readonly PublicarPcaPresenter $presenter
    ) {}

    public function executar(
        EntradaPublicarPca $entrada,
        CorrelacaoExecucao $correlacao
    ): PublicarPcaViewModel {
        try {
            return $this->controller->executar($entrada, $correlacao);
        } catch (EntradaPublicarPcaInvalidaException $exception) {
            return $this->presenter->apresentar(
                PublicarPcaResponse::solicitacaoInvalida(
                    $exception->getFalha(),
                    $correlacao
                )
            );
        } catch (Throwable) {
            return $this->presenter->apresentar(
                PublicarPcaResponse::interrompida(
                    [],
                    new FalhaSegura(
                        'FALHA_INESPERADA',
                        'Não foi possível concluir a publicação do PCA.'
                    ),
                    0,
                    0,
                    $correlacao
                )
            );
        }
    }
}
