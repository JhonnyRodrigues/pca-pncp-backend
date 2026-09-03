<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint83;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Infrastructure\Pncp\Http\FalhaTransportePncpException;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Tests\Contract\Sprint83\Support\PncpTrainingTransport;

final class PncpTrainingTransportTest extends TestCase
{
    public function testDeveDescartarSomenteUmaRespostaRealDePublicacaoAposDelegar(): void
    {
        $delegate = new TransporteContadoSprint83();
        $transport = new PncpTrainingTransport($delegate);
        $request = new RequisicaoHttpPncp(
            'POST',
            '/v1/orgaos/12345678000195/pca',
            [],
            '{"valorTotal":10.0000}',
            60
        );
        $transport->perderProximaRespostaDePublicacao();

        try {
            $transport->enviar($request);
            $this->fail('Era esperada perda deliberada da resposta.');
        } catch (FalhaTransportePncpException $exception) {
            $this->assertSame('PNCP_TRANSPORTE_EXECUCAO_FALHOU', $exception->getCodigoSeguro());
            $this->assertSame(28, $exception->getErrnoCurlSeguro());
        }

        $this->assertSame(201, $transport->enviar($request)->getStatus());
        $this->assertSame(2, $delegate->chamadas);
        $this->assertSame(2, $transport->contar('POST', '/pca'));
        $this->assertTrue($transport->publicacaoContemLexemas(['10.0000']));
    }
}

final class TransporteContadoSprint83 implements TransporteHttpPncp
{
    public int $chamadas = 0;

    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        $this->chamadas++;

        return new RespostaHttpPncp(201, ['Location' => ['/recurso/77']], '');
    }
}
