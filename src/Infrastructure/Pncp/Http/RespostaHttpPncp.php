<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Http;

use InvalidArgumentException;

final class RespostaHttpPncp
{
    /** @var array<string, string[]> */
    private readonly array $headers;

    /** @param array<mixed> $headers */
    public function __construct(
        private readonly int $status,
        array $headers,
        private readonly string $corpo
    ) {
        if ($status < 100 || $status > 599) {
            throw new InvalidArgumentException('Status HTTP inválido retornado pelo PNCP.');
        }

        $this->headers = $this->normalizarHeaders($headers);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /** @return array<string, string[]> */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $nome): ?string
    {
        $valores = $this->headers[strtolower($nome)] ?? [];

        return $valores === [] ? null : $valores[array_key_last($valores)];
    }

    public function getCorpo(): string
    {
        return $this->corpo;
    }

    /**
     * @param array<mixed> $headers
     * @return array<string, string[]>
     */
    private function normalizarHeaders(array $headers): array
    {
        $normalizados = [];

        foreach ($headers as $nome => $valores) {
            if (
                !is_string($nome)
                || preg_match('/^[A-Za-z0-9!#$%&*+.^_`|~-]+$/D', $nome) !== 1
                || !is_array($valores)
            ) {
                throw new InvalidArgumentException('Headers da resposta PNCP são inválidos.');
            }

            $nome = strtolower($nome);

            foreach ($valores as $valor) {
                if (
                    !is_string($valor)
                    || str_contains($valor, "\r")
                    || str_contains($valor, "\n")
                ) {
                    throw new InvalidArgumentException('Header da resposta PNCP é inválido.');
                }

                $normalizados[$nome][] = $valor;
            }
        }

        return $normalizados;
    }
}
