<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Pncp\Http;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Semae\Pca\Infrastructure\Pncp\Http\ConfiguracaoTransportePncp;
use Semae\Pca\Infrastructure\Pncp\Http\FalhaTransportePncpException;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;

final class ContratosHttpPncpTest extends TestCase
{
    public function testDeveResolverSomenteOsAmbientesOficiais(): void
    {
        $this->assertSame(
            'https://treina.pncp.gov.br/api/pncp',
            AmbientePncp::HOMOLOGACAO->getBaseUrl()
        );
        $this->assertSame(
            'https://pncp.gov.br/api/pncp',
            AmbientePncp::PRODUCAO->getBaseUrl()
        );
    }

    public function testDeveExporConfiguracaoSeguraComTimeoutsIniciais(): void
    {
        $configuracao = new ConfiguracaoTransportePncp(
            AmbientePncp::HOMOLOGACAO,
            16_384,
            65_536,
            caminhoCaBundle: ' C:\\certificados\\pncp-ca.pem '
        );

        $this->assertSame('https://treina.pncp.gov.br/api/pncp', $configuracao->getBaseUrl());
        $this->assertSame(16_384, $configuracao->getLimitePayloadBytes());
        $this->assertSame(65_536, $configuracao->getLimiteRespostaBytes());
        $this->assertSame(5, $configuracao->getTimeoutConexaoSegundos());
        $this->assertSame(30, $configuracao->getTimeoutLoginSegundos());
        $this->assertSame(30, $configuracao->getTimeoutLeituraSegundos());
        $this->assertSame(60, $configuracao->getTimeoutEscritaSegundos());
        $this->assertSame('C:\\certificados\\pncp-ca.pem', $configuracao->getCaminhoCaBundle());
    }

    /** @return iterable<string, array{int, int, int, int, int, int, ?string}> */
    public static function configuracoesInvalidas(): iterable
    {
        yield 'payload sem limite' => [0, 1, 5, 30, 30, 60, null];
        yield 'resposta sem limite' => [1, 0, 5, 30, 30, 60, null];
        yield 'conexão infinita' => [1, 1, 0, 30, 30, 60, null];
        yield 'login infinito' => [1, 1, 5, 0, 30, 60, null];
        yield 'leitura infinita' => [1, 1, 5, 30, 0, 60, null];
        yield 'escrita infinita' => [1, 1, 5, 30, 30, 0, null];
        yield 'CA vazio' => [1, 1, 5, 30, 30, 60, '   '];
    }

    #[DataProvider('configuracoesInvalidas')]
    public function testDeveRejeitarLimitesTimeoutsOuCaInvalidos(
        int $limitePayload,
        int $limiteResposta,
        int $timeoutConexao,
        int $timeoutLogin,
        int $timeoutLeitura,
        int $timeoutEscrita,
        ?string $caBundle
    ): void {
        $this->expectException(InvalidArgumentException::class);

        new ConfiguracaoTransportePncp(
            AmbientePncp::HOMOLOGACAO,
            $limitePayload,
            $limiteResposta,
            $timeoutConexao,
            $timeoutLogin,
            $timeoutLeitura,
            $timeoutEscrita,
            $caBundle
        );
    }

    public function testDeveModelarRequisicaoComMetodoCaminhoHeadersCorpoETimeout(): void
    {
        $requisicao = new RequisicaoHttpPncp(
            ' post ',
            '/v1/usuarios/login',
            ['Accept' => 'application/json', 'X-Correlation-Id' => 'corr-123'],
            '{"login":"usuario"}',
            30
        );

        $this->assertSame('POST', $requisicao->getMetodo());
        $this->assertSame('/v1/usuarios/login', $requisicao->getCaminho());
        $this->assertSame([
            'accept' => 'application/json',
            'x-correlation-id' => 'corr-123',
        ], $requisicao->getHeaders());
        $this->assertSame('{"login":"usuario"}', $requisicao->getCorpo());
        $this->assertSame(30, $requisicao->getTimeoutSegundos());
    }

    /** @return iterable<string, array{string, string, array<mixed>, int}> */
    public static function requisicoesInvalidas(): iterable
    {
        yield 'método não autorizado' => ['PATCH', '/v1/recurso', [], 30];
        yield 'URL absoluta' => ['GET', 'https://pncp.gov.br/api/pncp/v1/recurso', [], 30];
        yield 'caminho sem barra' => ['GET', 'v1/recurso', [], 30];
        yield 'autoridade relativa' => ['GET', '//destino.example/recurso', [], 30];
        yield 'quebra no caminho' => ['GET', "/v1/recurso\r\nHost: destino.example", [], 30];
        yield 'header inseguro' => ['GET', '/v1/recurso', ['X-Teste' => "ok\r\nAuthorization: Bearer segredo"], 30];
        yield 'header não textual' => ['GET', '/v1/recurso', ['X-Teste' => 123], 30];
        yield 'timeout infinito' => ['GET', '/v1/recurso', [], 0];
    }

    /** @param array<mixed> $headers */
    #[DataProvider('requisicoesInvalidas')]
    public function testDeveRejeitarRequisicaoInsegura(
        string $metodo,
        string $caminho,
        array $headers,
        int $timeout
    ): void {
        $this->expectException(InvalidArgumentException::class);

        new RequisicaoHttpPncp($metodo, $caminho, $headers, null, $timeout);
    }

    public function testDeveManterStatusHeadersECorpoDaRespostaSeparados(): void
    {
        $resposta = new RespostaHttpPncp(
            201,
            ['Location' => ['/pca/2027/42'], 'X-Teste' => ['primeiro', 'ultimo']],
            '{"sequencialPca":42}'
        );

        $this->assertSame(201, $resposta->getStatus());
        $this->assertSame([
            'location' => ['/pca/2027/42'],
            'x-teste' => ['primeiro', 'ultimo'],
        ], $resposta->getHeaders());
        $this->assertSame('/pca/2027/42', $resposta->getHeader('LOCATION'));
        $this->assertSame('ultimo', $resposta->getHeader('x-teste'));
        $this->assertNull($resposta->getHeader('ausente'));
        $this->assertSame('{"sequencialPca":42}', $resposta->getCorpo());
    }

    public function testDeveRejeitarRespostaForaDoContratoHttp(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RespostaHttpPncp(0, [], '');
    }

    public function testFalhaDeTransporteDeveSerTipadaESanitizada(): void
    {
        $falha = FalhaTransportePncpException::execucaoCurlFalhou(28);

        $this->assertInstanceOf(RuntimeException::class, $falha);
        $this->assertSame('PNCP_TRANSPORTE_EXECUCAO_FALHOU', $falha->getCodigoSeguro());
        $this->assertSame(28, $falha->getErrnoCurlSeguro());
        $this->assertSame('Falha de transporte PNCP (cURL 28).', $falha->getMessage());
        $this->assertStringNotContainsString('https://', $falha->getMessage());
        $this->assertStringNotContainsString('Bearer', $falha->getMessage());
    }
}
