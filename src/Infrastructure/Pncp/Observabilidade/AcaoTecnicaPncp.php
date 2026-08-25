<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Observabilidade;

enum AcaoTecnicaPncp: string
{
    case AUTENTICAR_PNCP = 'AUTENTICAR_PNCP';
    case OBSERVAR_PCA_PNCP = 'OBSERVAR_PCA_PNCP';
    case LOCALIZAR_CANDIDATOS_PNCP = 'LOCALIZAR_CANDIDATOS_PNCP';
}
