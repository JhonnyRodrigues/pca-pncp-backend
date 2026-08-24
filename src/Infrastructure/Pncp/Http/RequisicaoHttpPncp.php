<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Http;

use InvalidArgumentException;

final class RequisicaoHttpPncp
{
    private readonly string $metodo;
    private readonly string $caminho;

    /** @var array<string, string> */
    private readonly array $headers;

    /** @param array<mixed> $headers */
    public function __construct(
        string $metodo,
        string $caminho,
        array $headers,
        private readonly ?string $corpo,
        private readonly int $timeoutSegundos
    ) {
        $metodo = strtoupper(trim($metodo));

        if (!in_array($metodo, ['GET', 'POST', 'DELETE'], true)) {
            throw new InvalidArgumentException('Método HTTP não permitido para o PNCP.');
        }

        $caminho = trim($caminho);

        if (
            !str_starts_with($caminho, '/')
            || str_starts_with($caminho, '//')
            || str_contains($caminho, '://')
            || str_contains($caminho, "\r")
            || str_contains($caminho, "\n")
        ) {
            throw new InvalidArgumentException('Caminho HTTP inválido para o PNCP.');
        }

        if ($timeoutSegundos <= 0) {
            throw new InvalidArgumentException('Timeout da requisição deve ser maior que zero.');
        }

        $this->metodo = $metodo;
        $this->caminho = $caminho;
        $this->headers = $this->normalizarHeaders($headers);
    }

    public function getMetodo(): string
    {
        return $this->metodo;
    }

    public function getCaminho(): string
    {
        return $this->caminho;
    }

    /** @return array<string, string> */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getCorpo(): ?string
    {
        return $this->corpo;
    }

    public function getTimeoutSegundos(): int
    {
        return $this->timeoutSegundos;
    }

    /**
     * @param array<mixed> $headers
     * @return array<string, string>
     */
    private function normalizarHeaders(array $headers): array
    {
        $normalizados = [];

        foreach ($headers as $nome => $valor) {
            if (
                !is_string($nome)
                || preg_match('/^[A-Za-z0-9!#$%&*+.^_`|~-]+$/D', $nome) !== 1
                || !is_string($valor)
                || str_contains($valor, "\r")
                || str_contains($valor, "\n")
            ) {
                throw new InvalidArgumentException('Header HTTP inválido para o PNCP.');
            }

            $nome = strtolower($nome);

            if (array_key_exists($nome, $normalizados)) {
                throw new InvalidArgumentException('Header HTTP duplicado para o PNCP.');
            }

            $normalizados[$nome] = $valor;
        }

        return $normalizados;
    }
}
