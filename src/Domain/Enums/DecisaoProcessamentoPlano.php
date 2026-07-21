<?php

declare(strict_types=1);

namespace Semae\Pca\Domain\Enums;

enum DecisaoProcessamentoPlano: string
{
    case PUBLICAR = 'Primeiro processamento de publicação do plano.';
    case RETIFICAR = 'Processamento de retificação do plano, posterior à primeira publicação.';
    case NENHUMA_ACAO = 'Ultima versão do plano já está publicada. Portanto, nenhuma ação é necessária.';
}
