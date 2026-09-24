<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint94\Support;

use InvalidArgumentException;
use JsonException;

final class Sprint94Report
{
    private const SENSITIVE_KEYS = [
        'authorization', 'body', 'cnpj', 'corpo', 'jwt', 'password', 'senha', 'sql', 'token',
    ];

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public static function create(array $data): array
    {
        if (array_key_exists('sha256', $data)) {
            throw new InvalidArgumentException('O hash do relatorio nao pode ser informado pelo chamador.');
        }

        $normalized = self::normalize($data);
        if (!is_array($normalized) || array_is_list($normalized)) {
            throw new InvalidArgumentException('O relatorio deve ser um objeto associativo.');
        }

        try {
            $canonical = json_encode(
                $normalized,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                    | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
            );
        } catch (JsonException $failure) {
            throw new InvalidArgumentException('O relatorio nao pode ser serializado.', 0, $failure);
        }

        $normalized['sha256'] = hash('sha256', $canonical);

        return $normalized;
    }

    private static function normalize(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
            throw new InvalidArgumentException("O campo sensivel {$key} nao pode constar no relatorio.");
        }
        if (is_string($value) && preg_match('/\bBearer\s+\S+/i', $value) === 1) {
            throw new InvalidArgumentException('Credencial de autorizacao proibida no relatorio.');
        }
        if (!is_array($value)) {
            if (is_object($value) || is_resource($value)) {
                throw new InvalidArgumentException('Tipo nao serializavel no relatorio.');
            }
            return $value;
        }

        $normalized = [];
        foreach ($value as $childKey => $childValue) {
            $normalized[$childKey] = self::normalize(
                $childValue,
                is_string($childKey) ? $childKey : null
            );
        }
        if (!array_is_list($normalized)) {
            ksort($normalized, SORT_STRING);
        }

        return $normalized;
    }
}
