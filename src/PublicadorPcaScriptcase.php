<?php

declare(strict_types=1);

namespace Semae\Pca;

use Semae\Pca\Composition\ExecutorPublicacaoPcaScriptcase;
use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;

final class PublicadorPcaScriptcase
{
    public function executar(
        object $conexaoAdodb,
        string $loginAutenticado,
        string $serverName,
        int $anoExecucao,
        ?string $requestId
    ): PublicarPcaViewModel {
        return (new ExecutorPublicacaoPcaScriptcase())->executar(
            $conexaoAdodb,
            $loginAutenticado,
            $serverName,
            $anoExecucao,
            $requestId
        );
    }
}
