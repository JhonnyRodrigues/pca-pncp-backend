<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Http;

final class ResultadoExecucaoCurlPncp
{
    public function __construct(
        private readonly bool $executado,
        private readonly int $errno,
        private readonly int $statusHttp,
        private readonly int $resultadoVerificacaoTls
    ) {}

    public function foiExecutado(): bool
    {
        return $this->executado;
    }

    public function getErrno(): int
    {
        return $this->errno;
    }

    public function getStatusHttp(): int
    {
        return $this->statusHttp;
    }

    public function getResultadoVerificacaoTls(): int
    {
        return $this->resultadoVerificacaoTls;
    }
}
