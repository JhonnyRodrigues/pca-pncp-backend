<?php

declare(strict_types=1);

namespace Tests\Integration\Composition\Support;

use DateTimeImmutable;
use DateTimeZone;
use Semae\Pca\Infrastructure\Pncp\Leitura\TemporizadorPncp;

final class TemporizadorControlado implements TemporizadorPncp
{
    /** @var int[] */
    public array $esperasMs = [];

    public function agoraUtc(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-31 12:00:00', new DateTimeZone('UTC'));
    }

    public function aguardarMilissegundos(int $milissegundos): void
    {
        $this->esperasMs[] = $milissegundos;
    }
}
