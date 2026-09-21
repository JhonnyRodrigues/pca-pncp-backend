<?php

declare(strict_types=1);

namespace Semae\Pca;

use Semae\Pca\Composition\ExecutorRegistroEventosTecnicosScriptcase;

final class RegistradorEventosTecnicosScriptcase
{
    /** @param array<int, array<string, mixed>> $eventos */
    public function registrar(
        object $conexaoAdodb,
        string $loginAutenticado,
        string $serverName,
        string $aplicacao,
        array $eventos
    ): ?string {
        return (new ExecutorRegistroEventosTecnicosScriptcase())->registrar(
            $conexaoAdodb,
            $loginAutenticado,
            $serverName,
            $aplicacao,
            $eventos
        );
    }
}
