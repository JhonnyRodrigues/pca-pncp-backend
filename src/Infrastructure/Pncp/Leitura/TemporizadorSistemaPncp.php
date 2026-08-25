<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Leitura;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class TemporizadorSistemaPncp implements TemporizadorPncp
{
    public function agoraUtc(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function aguardarMilissegundos(int $milissegundos): void
    {
        if ($milissegundos < 0) {
            throw new InvalidArgumentException('Espera PNCP não pode ser negativa.');
        }

        if ($milissegundos > 0) {
            usleep($milissegundos * 1_000);
        }
    }
}
