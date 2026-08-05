<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Enums;

enum DecisaoProcessamentoPlano: string
{
    case PUBLICAR = 'PUBLICAR';
    case RETIFICAR = 'RETIFICAR';
    case EXCLUIR_PCA = 'EXCLUIR_PCA';
    case NENHUMA_ACAO = 'NENHUMA_ACAO';
}
