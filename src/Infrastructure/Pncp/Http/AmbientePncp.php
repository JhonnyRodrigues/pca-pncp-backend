<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Http;

enum AmbientePncp: string
{
    case HOMOLOGACAO = 'HOMOLOGACAO';
    case PRODUCAO = 'PRODUCAO';

    public function getBaseUrl(): string
    {
        return match ($this) {
            self::HOMOLOGACAO => 'https://treina.pncp.gov.br/api/pncp',
            self::PRODUCAO => 'https://pncp.gov.br/api/pncp',
        };
    }
}
