<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Tests\Contract\Sprint84\Support\Sprint84GateException;
use Tests\Contract\Sprint84\Support\Sprint84PncpTransportGuard;

final class Sprint84PncpTransportGuardTest extends TestCase
{
    public function testGuardaSomenteLeituraDeveBloquearMutacoesAntesDoDespacho(): void
    {
        foreach (['POST', 'DELETE'] as $metodo) {
            $inner = $this->createMock(TransporteHttpPncp::class);
            $inner->expects($this->never())->method('enviar');
            $guard = new Sprint84PncpTransportGuard($inner, false);
            try {
                $guard->enviar(new RequisicaoHttpPncp($metodo, '/v1/orgaos/123/pca/2027/1/itens', [], '[]', 30));
                $this->fail('Era esperado bloqueio antes do transporte.');
            } catch (Sprint84GateException $erro) {
                $this->assertSame('PNCP_ESCRITA_NAO_AUTORIZADA', $erro->getErrorCode());
                $this->assertCount(1, $guard->diagnostico());
                $this->assertArrayNotHasKey('resposta', $guard->diagnostico()[0]);
            }
        }
    }

    public function testGuardaSomenteLeituraDevePermitirLoginEGet(): void
    {
        $inner = $this->createMock(TransporteHttpPncp::class);
        $response = new RespostaHttpPncp(200, [], '[]');
        $inner->expects($this->exactly(2))->method('enviar')->willReturn($response);
        $guard = new Sprint84PncpTransportGuard($inner, false);
        $this->assertSame($response, $guard->enviar(new RequisicaoHttpPncp('POST', '/v1/usuarios/login', [], '{}', 30)));
        $this->assertSame($response, $guard->enviar(new RequisicaoHttpPncp('GET', '/v1/orgaos/123/pca/2027', [], null, 30)));
    }
}
