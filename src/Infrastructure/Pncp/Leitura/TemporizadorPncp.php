<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Leitura;

use DateTimeImmutable;

interface TemporizadorPncp
{
    public function agoraUtc(): DateTimeImmutable;

    public function aguardarMilissegundos(int $milissegundos): void;
}
