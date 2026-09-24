<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint94\Support;

use LogicException;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;

final class Sprint94PncpReadOnlyTransport implements TransporteHttpPncp
{
    public function __construct(private readonly TransporteHttpPncp $inner) {}

    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        $method = strtoupper($requisicao->getMetodo());
        $login = $method === 'POST' && $requisicao->getCaminho() === '/v1/usuarios/login';
        if ($method !== 'GET' && !$login) {
            throw new LogicException('Escrita PNCP proibida no diagnostico da Sprint 9.4.');
        }

        return $this->inner->enviar($requisicao);
    }
}
