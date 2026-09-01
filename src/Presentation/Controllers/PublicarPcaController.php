<?php

declare(strict_types=1);

namespace Semae\Pca\Presentation\Controllers;

use InvalidArgumentException;
use Semae\Pca\Application\Contracts\PublicadorPca;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaRequest;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Domain\Exceptions\AnoExecucaoInvalidoException;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Presentation\Exceptions\EntradaPublicarPcaInvalidaException;
use Semae\Pca\Presentation\Inputs\EntradaPublicarPca;
use Semae\Pca\Presentation\Presenters\PublicarPcaPresenter;
use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;

final class PublicarPcaController
{
    public function __construct(
        private readonly PublicadorPca $publicador,
        private readonly PublicarPcaPresenter $presenter
    ) {}

    public function executar(
        EntradaPublicarPca $entrada,
        CorrelacaoExecucao $correlacao
    ): PublicarPcaViewModel {
        try {
            $cnpj = new Cnpj($entrada->getCnpj());
        } catch (InvalidArgumentException $exception) {
            throw new EntradaPublicarPcaInvalidaException(
                new FalhaSegura('CNPJ_INVALIDO', 'CNPJ inválido.'),
                $exception
            );
        }

        try {
            $anoExecucao = new AnoExecucao($entrada->getAnoExecucao());
        } catch (AnoExecucaoInvalidoException $exception) {
            throw new EntradaPublicarPcaInvalidaException(
                new FalhaSegura(
                    'ANO_EXECUCAO_INVALIDO',
                    'Ano de execução inválido.'
                ),
                $exception
            );
        }

        $request = new PublicarPcaRequest($cnpj, $anoExecucao);

        $response = $this->publicador->executar($request, $correlacao);

        return $this->presenter->apresentar($response);
    }
}
