<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Http;

final class ResultadoExecucaoCurlPncp
{
    /** @param array<string, int|string> $diagnosticoConexao */
    public function __construct(
        private readonly bool $executado,
        private readonly int $errno,
        private readonly int $statusHttp,
        private readonly int $resultadoVerificacaoTls,
        private readonly ?int $versaoHttp = null,
        private readonly ?string $cabecalhosEnviados = null,
        private readonly array $diagnosticoConexao = []
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

    public function getVersaoHttp(): ?int
    {
        return $this->versaoHttp;
    }

    public function getCabecalhosEnviados(): ?string
    {
        return $this->cabecalhosEnviados;
    }

    /** @return array<string, int|string> */
    public function getDiagnosticoConexao(): array
    {
        return $this->diagnosticoConexao;
    }
}
