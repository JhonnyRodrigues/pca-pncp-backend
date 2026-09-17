<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84\Support;

use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Throwable;

final class Sprint84PncpTransportGuard implements TransporteHttpPncp
{
    /** @var array<int, array<string, mixed>> */
    private array $calls = [];

    public function __construct(
        private readonly TransporteHttpPncp $inner,
        private readonly bool $allowPcaWrites = true
    ) {}

    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        $request = [
            'metodo' => $requisicao->getMetodo(),
            'caminho' => $requisicao->getCaminho(),
            'headers' => $requisicao->getHeaders(),
            'corpo' => $requisicao->getCorpo(),
            'timeout_segundos' => $requisicao->getTimeoutSegundos(),
        ];

        try {
            if (
                !$this->allowPcaWrites
                && $requisicao->getMetodo() !== 'GET'
                && $requisicao->getCaminho() !== '/v1/usuarios/login'
            ) {
                throw new Sprint84GateException(
                    'PNCP_ESCRITA_NAO_AUTORIZADA',
                    'TRANSPORTE_PNCP',
                    'A etapa ou configuração não autoriza mutações PNCP; requisição não despachada.',
                    ['metodo' => $request['metodo'], 'caminho' => $request['caminho']]
                );
            }
            $response = $this->inner->enviar($requisicao);
            $this->calls[] = [
                'requisicao' => $request,
                'resposta' => [
                    'status_http' => $response->getStatus(),
                    'headers' => $response->getHeaders(),
                    'corpo' => $response->getCorpo(),
                ],
            ];

            return $response;
        } catch (Throwable $failure) {
            $this->calls[] = [
                'requisicao' => $request,
                'erro' => [
                    'classe' => $failure::class,
                    'mensagem' => $failure->getMessage(),
                    'arquivo' => $failure->getFile(),
                    'linha' => $failure->getLine(),
                    'stack' => $failure->getTraceAsString(),
                ],
            ];

            throw $failure;
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function diagnostico(): array
    {
        return $this->calls;
    }
}
