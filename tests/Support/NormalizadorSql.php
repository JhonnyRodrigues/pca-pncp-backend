<?php

declare(strict_types=1);

namespace Tests\Support;

use LogicException;

final class NormalizadorSql
{
    public static function normalizar(string $sql): string
    {
        $normalizado = preg_replace(
            ['/\s+/', '/\(\s+/', '/\s+\)/'],
            [' ', '(', ')'],
            trim($sql)
        );

        if (!is_string($normalizado)) {
            throw new LogicException('Não foi possível normalizar a instrução SQL do teste.');
        }

        return $normalizado;
    }
}
