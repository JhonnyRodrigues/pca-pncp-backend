<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Observabilidade;

use DateTimeImmutable;

interface RelogioEventosTecnicos
{
    public function agora(): DateTimeImmutable;

    public function tempoMonotonicoMs(): int;
}
