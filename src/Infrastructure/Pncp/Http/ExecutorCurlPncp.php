<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Http;

interface ExecutorCurlPncp
{
    /** @param array<int, mixed> $opcoes */
    public function executar(string $url, array $opcoes): ResultadoExecucaoCurlPncp;
}
