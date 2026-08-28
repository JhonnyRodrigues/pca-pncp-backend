<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint51\Support;

use JsonException;
use RuntimeException;

final class PncpHttpResponse
{
    /** @param array<string, string[]> $headers */
    public function __construct(
        public readonly int $status,
        public readonly array $headers,
        public readonly string $body,
        public readonly int $sslVerifyResult
    ) {
    }

    public function header(string $name): ?string
    {
        $values = $this->headers[strtolower($name)] ?? [];
        return $values === [] ? null : $values[array_key_last($values)];
    }

    /** @return mixed */
    public function json(int $depth = 64): mixed
    {
        if ($this->body === '') {
            return null;
        }

        try {
            return json_decode($this->body, true, $depth, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('PNCP retornou JSON inválido.', 0, $exception);
        }
    }

    /** @return array<string, mixed> */
    public function sanitizedSummary(): array
    {
        $shape = 'empty';
        if ($this->body !== '') {
            try {
                $decoded = $this->json();
                $shape = is_array($decoded)
                    ? (array_is_list($decoded) ? 'list' : 'object')
                    : get_debug_type($decoded);
            } catch (RuntimeException) {
                $shape = 'invalid_json';
            }
        }

        return [
            'status_http' => $this->status,
            'bytes_resposta' => strlen($this->body),
            'forma_corpo' => $shape,
            'tls_verificado' => $this->sslVerifyResult === 0,
            'location_presente' => $this->header('location') !== null,
        ];
    }
}
