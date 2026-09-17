<?php

declare(strict_types=1);

namespace Semae\Pca\Composition;

use InvalidArgumentException;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Presentation\Facades\FachadaPublicarPca;
use Semae\Pca\Presentation\Inputs\EntradaPublicarPca;
use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;
use Throwable;

final class ExecutorPublicacaoPcaScriptcase
{
    private const UUID_V4 = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/Di';

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
        if ($requestId === null || preg_match(self::UUID_V4, $requestId) !== 1) {
            return FachadaPublicarPca::interrompidaPorRequestIdInvalido();
        }

        $correlacao = new CorrelacaoExecucao($requestId);

        try {
            $perfil = (new CarregadorPerfilPublicacaoPca())->carregar($serverName);
            $cnpjBruto = $perfil['cnpj'] ?? null;

            if (!is_string($cnpjBruto)) {
                throw new InvalidArgumentException('CNPJ do perfil é inválido.');
            }

            $cnpj = new Cnpj($cnpjBruto);
            $entrada = new EntradaPublicarPca($cnpj->getValor(), $anoExecucao);
            $fachada = $this->compositionRoot->montar(
                $conexaoAdodb,
                $loginAutenticado,
                $perfil,
                $correlacao
            );

            return $fachada->executar($entrada, $correlacao);
        } catch (Throwable) {
            return FachadaPublicarPca::interrompidaPorFalhaInesperada($correlacao);
        }
    }
}
