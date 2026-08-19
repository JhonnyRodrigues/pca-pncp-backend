<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint51\Support;

use InvalidArgumentException;
use JsonException;

final class ExactJsonEncoder
{
    /** @throws JsonException */
    public static function encode(mixed $value): string
    {
        if ($value instanceof ExactJsonNumber) {
            return $value->value();
        }

        if (is_float($value)) {
            throw new InvalidArgumentException('Float não é aceito pelo encoder exato do gate.');
        }

        if (is_array($value)) {
            return array_is_list($value)
                ? self::encodeList($value)
                : self::encodeObject($value);
        }

        if (is_string($value) || is_int($value) || is_bool($value) || $value === null) {
            return json_encode(
                $value,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
        }

        throw new InvalidArgumentException('Tipo não aceito pelo encoder exato do gate.');
    }

    /** @param array<int, mixed> $values */
    private static function encodeList(array $values): string
    {
        return '[' . implode(',', array_map(self::encode(...), $values)) . ']';
    }

    /** @param array<string|int, mixed> $values */
    private static function encodeObject(array $values): string
    {
        $properties = [];

        foreach ($values as $key => $value) {
            $encodedKey = json_encode((string) $key, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $properties[] = $encodedKey . ':' . self::encode($value);
        }

        return '{' . implode(',', $properties) . '}';
    }
}
