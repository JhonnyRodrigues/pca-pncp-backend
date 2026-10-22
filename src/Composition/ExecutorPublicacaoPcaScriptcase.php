<?php

declare(strict_types=1);

namespace Semae\Pca\Composition;

use InvalidArgumentException;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Presentation\Facades\FachadaPublicarPca;
use Semae\Pca\Presentation\Inputs\EntradaPublicarPca;
use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;
use Semae\Pca\ResultadoPublicacaoPcaScriptcase;
use Throwable;

/**
 * É o coordenador da fronteira Scriptcase.
 */
final class ExecutorPublicacaoPcaScriptcase
{
    private const UUID_V4 = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/Di';
    private const AVISO_REQUEST_ID_INVALIDO =
        'Não há eventos técnicos (logs) disponíveis para esta execução porque '
        . 'o identificador da requisição está ausente ou inválido. Verifique a configuração '
        . 'do servidor desta aplicação responsável por fornecer o X-Request-ID.';
    private const AVISO_INICIALIZACAO =
        'Não há eventos técnicos (logs) disponíveis para esta execução porque '
        . 'a inicialização do processamento falhou. Verifique a configuração '
        . 'e os pré-requisitos do servidor desta aplicação.';

    private readonly CompositionRootPublicacaoPca $compositionRoot;

    public function __construct(?CompositionRootPublicacaoPca $compositionRoot = null)
    {
        $this->compositionRoot = $compositionRoot ?? new CompositionRootPublicacaoPca();
    }

    public function executar(
        object $conexaoAdodb,
        string $loginAutenticado,
        string $serverName,
        int $anoExecucao,
        ?string $requestId
    ): PublicarPcaViewModel {
        return $this->executarInternamente(
            $conexaoAdodb,
            $loginAutenticado,
            $serverName,
            $anoExecucao,
            $requestId,
            false
        )->getResultado();
    }

    public function executarComEventos(
        object $conexaoAdodb,
        string $loginAutenticado,
        string $serverName,
        int $anoExecucao,
        ?string $requestId
    ): ResultadoPublicacaoPcaScriptcase {
        return $this->executarInternamente(
            $conexaoAdodb,
            $loginAutenticado,
            $serverName,
            $anoExecucao,
            $requestId,
            true
        );
    }

    private function executarInternamente(
        object $conexaoAdodb,
        string $loginAutenticado,
        string $serverName,
        int $anoExecucao,
        ?string $requestId,
        bool $exportarEventos
    ): ResultadoPublicacaoPcaScriptcase {
        # valida o request ID
        if ($requestId === null || preg_match(self::UUID_V4, $requestId) !== 1) {
            return new ResultadoPublicacaoPcaScriptcase(
                FachadaPublicarPca::interrompidaPorRequestIdInvalido(),
                [],
                self::AVISO_REQUEST_ID_INVALIDO
            );
        }

        # materializa a correlação
        $correlacao = new CorrelacaoExecucao($requestId);

        try {
            # carrega o perfil externo
            $perfil = (new CarregadorPerfilPublicacaoPca())->carregar($serverName);
            $cnpjBruto = $perfil['cnpj'] ?? null;

            if (!is_string($cnpjBruto)) {
                throw new InvalidArgumentException('CNPJ do perfil é inválido.');
            }

            # materializa o CNPJ
            $cnpj = new Cnpj($cnpjBruto);

            $entrada = new EntradaPublicarPca($cnpj->getValor(), $anoExecucao);

            # pede ao Composition Root um grafo preparado para a execução da publicação
            $composicao = $this->compositionRoot->montarExecucao(
                $conexaoAdodb,
                $loginAutenticado,
                $perfil,
                $correlacao
            );

            # chama o View Model produzido pela execução
            $resultado = $composicao->getFachada()
                ->executar($entrada, $correlacao);

            # envolve o ViewModel e os eventos técnicos
            return $exportarEventos
                ? $composicao->exportar($resultado)
                : new ResultadoPublicacaoPcaScriptcase($resultado, [], null);
        } catch (Throwable) {
            # impede que detalhes internos de inicialização atravessem a API pública.
            return new ResultadoPublicacaoPcaScriptcase(
                FachadaPublicarPca::interrompidaPorFalhaInesperada($correlacao),
                [],
                self::AVISO_INICIALIZACAO
            );
        }
    }
}
