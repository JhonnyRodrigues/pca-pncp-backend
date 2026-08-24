<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Observabilidade;

use DateTimeImmutable;
use DateTimeZone;

final class RelogioSistemaEventosTecnicos implements RelogioEventosTecnicos
{
    public function agora(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function tempoMonotonicoMs(): int
    {
        return intdiv((int) hrtime(true), 1_000_000);
    }
}
