<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Http;

interface TransporteHttpPncp
{
    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp;
}
