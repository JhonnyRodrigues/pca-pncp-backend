<?php

declare(strict_types=1);

namespace Semae\Pca\Presentation\Facades;

use Psr\Log\LoggerInterface;
use Semae\Pca\Application\Enums\AcaoExecucaoPublicacao;
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
    private const CODIGO_FALHA_INESPERADA = 'FALHA_INESPERADA';
    private const MENSAGEM_FALHA_INESPERADA = 'Não foi possível concluir a publicação do PCA.';

    public function __construct(
        private readonly PublicarPcaController $controller,
        private readonly PublicarPcaPresenter $presenter,
        private readonly LoggerInterface $logger
    ) {}

    public static function interrompidaPorRequestIdInvalido(): PublicarPcaViewModel
    {
        return self::criarInterrupcaoSegura(
            'REQUEST_ID_INVALIDO',
            'Identificador da requisição ausente ou inválido.',
            null
        );
    }

    public static function interrompidaPorFalhaInesperada(
        CorrelacaoExecucao $correlacao
    ): PublicarPcaViewModel {
        return self::criarInterrupcaoSegura(
            self::CODIGO_FALHA_INESPERADA,
            self::MENSAGEM_FALHA_INESPERADA,
            $correlacao->getValor()
        );
    }

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
            $this->registrarFalhaInesperada();

            return self::interrompidaPorFalhaInesperada($correlacao);
        }
    }

    private static function criarInterrupcaoSegura(
        string $codigo,
        string $mensagem,
        ?string $correlacao
    ): PublicarPcaViewModel {
        return new PublicarPcaViewModel(
            'INTERROMPIDA',
            [],
            $codigo,
            $mensagem,
            0,
            0,
            0,
            $correlacao
        );
    }

    private function registrarFalhaInesperada(): void
    {
        try {
            $this->logger->error(self::MENSAGEM_FALHA_INESPERADA, [
                'action' => AcaoExecucaoPublicacao::EXECUTAR_PUBLICACAO_PCA->value,
                'error' => [
                    'code' => self::CODIGO_FALHA_INESPERADA,
                    'message' => self::MENSAGEM_FALHA_INESPERADA,
                ],
            ]);
        } catch (Throwable) {
            // A falha do logger não pode romper a fronteira pública.
        }
    }
}
