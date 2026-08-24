<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Observabilidade;

enum NivelEventoTecnico: string
{
    case DEBUG = 'DEBUG';
    case INFO = 'INFO';
    case WARNING = 'WARNING';
    case ERROR = 'ERROR';
}
