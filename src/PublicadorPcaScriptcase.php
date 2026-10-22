<?php

declare(strict_types=1);

namespace Semae\Pca;

use Semae\Pca\Composition\ExecutorPublicacaoPcaScriptcase;
use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;

final class PublicadorPcaScriptcase
{
    /**
     * Consumidor que precisa apenas do resultado
     *
     * devolve somente o resultado funcional da publicação
     *
     * Método mantido para que os testes, probes e consumidores criados antes da Sprint 8.5 continuem funcionando sem alteração
     */
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

    /**
     * Método variante que também devolve os eventos técnicos acumulados para registro posterior pela Blank.
     *
     * devolve três informações: resultado, eventos e avisoRegistroTecnico
     *
     * É a extensão criada na Sprint 8.5 para ser usado pela blank adaptada (front controller)
     */
    public function executarComEventos(
        object $conexaoAdodb,
        string $loginAutenticado,
        string $serverName,
        int $anoExecucao,
        ?string $requestId
    ): ResultadoPublicacaoPcaScriptcase {
        return (new ExecutorPublicacaoPcaScriptcase())->executarComEventos(
            $conexaoAdodb,
            $loginAutenticado,
            $serverName,
            $anoExecucao,
            $requestId
        );
    }
}
