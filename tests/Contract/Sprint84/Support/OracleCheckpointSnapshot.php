<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84\Support;

use InvalidArgumentException;
use JsonException;

final class OracleCheckpointSnapshot
{
    /** @param array<string, mixed> $snapshot */
    private function __construct(private readonly array $snapshot) {}

    /**
     * @param array<string, mixed> $queries
     * @param array<string, mixed> $results
     */
    public static function fromResults(array $queries, array $results): self
    {
        $queryNames = array_keys($queries);
        $resultNames = array_keys($results);
        sort($queryNames, SORT_STRING);
        sort($resultNames, SORT_STRING);

        if ($queryNames !== $resultNames) {
            throw new InvalidArgumentException(
                'O snapshot deve conter exatamente um resultado para cada consulta.'
            );
        }

        $ordered = [];
        foreach ($queryNames as $name) {
            $ordered[$name] = self::normalize($results[$name]);
        }

        return new self([
            'contrato' => 'SPRINT84_ORACLE_CHECKPOINT_V1',
            'resultados' => $ordered,
        ]);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->snapshot;
    }

    /** @throws JsonException */
    public function toJson(): string
    {
        return json_encode(
            $this->snapshot,
            JSON_PRESERVE_ZERO_FRACTION
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_THROW_ON_ERROR
        );
    }

    /** @throws JsonException */
    public function hash(): string
    {
        return hash('sha256', $this->toJson());
    }

    private static function normalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(self::normalize(...), $value);
        }

        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) {
            $value[$key] = self::normalize($item);
        }

        return $value;
    }
}
