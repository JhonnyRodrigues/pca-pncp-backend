<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Pncp\Http;

use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Semae\Pca\Infrastructure\Pncp\Http\ConfiguracaoTransportePncp;
use Semae\Pca\Infrastructure\Pncp\Http\ExecutorCurlNativoPncp;
use Semae\Pca\Infrastructure\Pncp\Http\ExecutorCurlPncp;
use Semae\Pca\Infrastructure\Pncp\Http\FalhaTransportePncpException;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\ResultadoExecucaoCurlPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteCurlPncp;

final class TransporteCurlPncpTest extends TestCase
{
    public function testDeveExecutarUmaUnicaRequisicaoComTlsETimeoutsSeguros(): void
    {
        $executor = new ExecutorCurlPncpFake();
        $executor->status = 201;
        $executor->headerLines = [
            "HTTP/1.1 201 Created\r\n",
            "Location: /pca/2027/42\r\n",
            "X-Teste: primeiro\r\n",
            "X-Teste: ultimo\r\n",
            "\r\n",
        ];
        $executor->bodyChunks = ['{"sequencial', 'Pca":42}'];
        $configuracao = $this->configuracao(caminhoCaBundle: 'C:\\ca\\pncp.pem');
        $transporte = new TransporteCurlPncp($configuracao, $executor);

        $resposta = $transporte->enviar(new RequisicaoHttpPncp(
            'POST',
            '/v1/orgaos/123/pca',
            ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
            '{"itensPlano":[]}',
            60
        ));

        $this->assertSame(1, $executor->execucoes);
        $this->assertSame(
            'https://treina.pncp.gov.br/api/pncp/v1/orgaos/123/pca',
            $executor->url
        );
        $this->assertSame(false, $executor->opcoes[CURLOPT_FOLLOWLOCATION]);
        $this->assertSame(true, $executor->opcoes[CURLOPT_SSL_VERIFYPEER]);
        $this->assertSame(2, $executor->opcoes[CURLOPT_SSL_VERIFYHOST]);
        $this->assertSame(CURLPROTO_HTTPS, $executor->opcoes[CURLOPT_PROTOCOLS]);
        $this->assertSame('C:\\ca\\pncp.pem', $executor->opcoes[CURLOPT_CAINFO]);
        $this->assertSame(5, $executor->opcoes[CURLOPT_CONNECTTIMEOUT]);
        $this->assertSame(60, $executor->opcoes[CURLOPT_TIMEOUT]);
        $this->assertSame('POST', $executor->opcoes[CURLOPT_CUSTOMREQUEST]);
        $this->assertSame('{"itensPlano":[]}', $executor->opcoes[CURLOPT_POSTFIELDS]);
        $this->assertContains('accept: application/json', $executor->opcoes[CURLOPT_HTTPHEADER]);
        $this->assertContains('content-type: application/json', $executor->opcoes[CURLOPT_HTTPHEADER]);
        $this->assertSame(201, $resposta->getStatus());
        $this->assertSame('/pca/2027/42', $resposta->getHeader('location'));
        $this->assertSame('ultimo', $resposta->getHeader('x-teste'));
        $this->assertSame('{"sequencialPca":42}', $resposta->getCorpo());
    }

    public function testDeveRetornarErroHttpComoRespostaSemConfundirComFalhaDeTransporte(): void
    {
        $executor = new ExecutorCurlPncpFake();
        $executor->status = 422;
        $executor->headerLines = ["HTTP/1.1 422 Unprocessable Entity\r\n", "\r\n"];
        $executor->bodyChunks = ['{"codigo":"campo_invalido"}'];

        $resposta = (new TransporteCurlPncp($this->configuracao(), $executor))->enviar(
            new RequisicaoHttpPncp('POST', '/v1/recurso', [], '{}', 60)
        );

        $this->assertSame(422, $resposta->getStatus());
        $this->assertSame('{"codigo":"campo_invalido"}', $resposta->getCorpo());
        $this->assertSame(1, $executor->execucoes);
    }

    public function testDeveRegistrarDiagnosticoCompletoDoErroHttpSemCredenciais(): void
    {
        $token = 'eyJhbGciOiJIUzI1NiJ9.'
            . 'eyJzdWIiOiIxMjMiLCJnZXN0YW9FbnRlIjp0cnVlfQ.assinatura';
        $executor = new ExecutorCurlPncpFake();
        $executor->status = 403;
        $executor->headerLines = [
            "HTTP/1.1 403 Forbidden\r\n",
            "Content-Type: application/json\r\n",
            "Set-Cookie: segredo-resposta\r\n",
            "\r\n",
        ];
        $executor->bodyChunks = [
            '{"message":"Somente o usuário que publicou pode editar."}',
        ];
        $executor->cabecalhosEnviados = "DELETE /api/pncp/v1/orgaos/123/pca/2026/32/itens HTTP/1.1\r\n"
            . "Host: pncp.gov.br\r\nAuthorization: Bearer {$token}\r\n"
            . "Cookie: sessao=segredo-cookie\r\nContent-Type: application/json\r\n\r\n";
        $executor->diagnosticoConexao = [
            'curlVersion' => '8.0.0',
            'sslVersion' => 'OpenSSL/3',
            'remoteIp' => '192.0.2.20',
            'remotePort' => 443,
        ];
        $logger = new LoggerTentativaHttpFake();

        (new TransporteCurlPncp($this->configuracao(), $executor, $logger))->enviar(
            new RequisicaoHttpPncp(
                'DELETE',
                '/v1/orgaos/123/pca/2026/32/itens',
                ['Authorization' => 'Bearer ' . $token],
                '{"numeroItem":1001}',
                30
            )
        );

        $this->assertCount(1, $logger->eventos);
        $evento = $logger->eventos[0];
        $this->assertSame('EXECUTAR_REQUISICAO_HTTP_PNCP', $evento['context']['action']);
        $this->assertSame('DELETE', $evento['context']['metadata']['metodo']);
        $this->assertSame(1, $evento['context']['metadata']['ordemHttp']);
        $this->assertSame('EXCLUIR_ITENS_PCA', $evento['context']['metadata']['operacao']);
        $this->assertSame('HOMOLOGACAO', $evento['context']['metadata']['ambientePncp']);
        $this->assertSame(strlen('{"numeroItem":1001}'), $evento['context']['metadata']['requisicaoBytes']);
        $this->assertSame(
            hash('sha256', '{"numeroItem":1001}'),
            $evento['context']['metadata']['requisicaoSha256']
        );
        $this->assertTrue($evento['context']['metadata']['sessaoPresente']);
        $this->assertSame(
            hash('sha256', $token),
            $evento['context']['metadata']['sessaoFingerprintSha256']
        );
        $this->assertSame(403, $evento['context']['metadata']['status_http']);
        $this->assertSame('HTTP_4XX', $evento['context']['metadata']['categoriaFalha']);
        $this->assertGreaterThanOrEqual(0, $evento['context']['metadata']['duracaoTentativaMs']);
        $this->assertArrayNotHasKey('curl_errno', $evento['context']['metadata']);
        $diagnostico = $evento['context']['metadata']['diagnosticoHttp'];
        $this->assertSame(
            'https://treina.pncp.gov.br/api/pncp/v1/orgaos/123/pca/2026/32/itens',
            $diagnostico['url']
        );
        $this->assertSame('DELETE', $diagnostico['request']['method']);
        $this->assertSame('{"numeroItem":1001}', $diagnostico['request']['bodyUtf8']);
        $this->assertSame(
            '{"message":"Somente o usuário que publicou pode editar."}',
            $diagnostico['response']['bodyUtf8']
        );
        $this->assertSame('JWT', $diagnostico['request']['authorizationDiagnostic']['format']);
        $this->assertSame(
            ['sub' => '123', 'gestaoEnte' => true],
            $diagnostico['request']['authorizationDiagnostic']['jwtClaims']
        );
        $this->assertSame('192.0.2.20', $diagnostico['connection']['remoteIp']);
        $this->assertStringContainsString('[REDACTED]', $diagnostico['request']['headersSent']);
        $this->assertStringContainsString('[REDACTED]', $diagnostico['response']['headersReceived']);
        $this->assertStringContainsString(
            'Bearer ${PNCP_BEARER_TOKEN}',
            $diagnostico['curlCommandTemplate']
        );
        $serializado = json_encode($evento, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($token, $serializado);
        $this->assertStringNotContainsString('segredo-cookie', $serializado);
        $this->assertStringNotContainsString('segredo-resposta', $serializado);
    }

    public function testDeveDiferenciarChamadasHttpConsecutivasPelaOrdemEOperacao(): void
    {
        $executor = new ExecutorCurlPncpFake();
        $executor->headerLines = ["HTTP/1.1 200 OK\r\n", "\r\n"];
        $logger = new LoggerTentativaHttpFake();
        $transporte = new TransporteCurlPncp($this->configuracao(), $executor, $logger);

        $transporte->enviar(new RequisicaoHttpPncp('POST', '/v1/usuarios/login', [], '{}', 30));
        $transporte->enviar(new RequisicaoHttpPncp(
            'POST',
            '/v1/orgaos/123/pca/2026/4/itens',
            ['Authorization' => 'Bearer token'],
            '[]',
            30
        ));

        $this->assertSame(1, $logger->eventos[0]['context']['metadata']['ordemHttp']);
        $this->assertSame('AUTENTICAR', $logger->eventos[0]['context']['metadata']['operacao']);
        $this->assertSame(2, $logger->eventos[1]['context']['metadata']['ordemHttp']);
        $this->assertSame('INCLUIR_ITENS_PCA', $logger->eventos[1]['context']['metadata']['operacao']);
    }

    public function testNaoDeveRegistrarDiagnosticoCompletoDeLoginNemDeRespostaAceita(): void
    {
        $logger = new LoggerTentativaHttpFake();
        $executorLogin = new ExecutorCurlPncpFake();
        $executorLogin->status = 403;
        $executorLogin->headerLines = ["HTTP/1.1 403 Forbidden\r\n", "\r\n"];
        $executorLogin->bodyChunks = ['{"token":"segredo"}'];
        (new TransporteCurlPncp($this->configuracao(), $executorLogin, $logger))->enviar(
            new RequisicaoHttpPncp(
                'POST',
                '/v1/usuarios/login',
                [],
                '{"login":"x","senha":"segredo"}',
                30
            )
        );

        $executorSucesso = new ExecutorCurlPncpFake();
        $executorSucesso->status = 200;
        $executorSucesso->headerLines = ["HTTP/1.1 200 OK\r\n", "\r\n"];
        (new TransporteCurlPncp($this->configuracao(), $executorSucesso, $logger))->enviar(
            new RequisicaoHttpPncp('GET', '/v1/recurso', [], null, 30)
        );

        $this->assertArrayNotHasKey(
            'diagnosticoHttp',
            $logger->eventos[0]['context']['metadata']
        );
        $this->assertArrayNotHasKey(
            'diagnosticoHttp',
            $logger->eventos[1]['context']['metadata']
        );
        $this->assertStringNotContainsString(
            'segredo',
            json_encode($logger->eventos, JSON_THROW_ON_ERROR)
        );
    }

    public function testFalhaCurlDeveRegistrarErrnoSemInventarRespostaHttp(): void
    {
        $executor = new ExecutorCurlPncpFake();
        $executor->executado = false;
        $executor->errno = 28;
        $executor->status = 0;
        $logger = new LoggerTentativaHttpFake();

        try {
            (new TransporteCurlPncp($this->configuracao(), $executor, $logger))->enviar(
                new RequisicaoHttpPncp('GET', '/v1/recurso', [], null, 30)
            );
            $this->fail('Era esperada falha de transporte.');
        } catch (FalhaTransportePncpException) {
            $metadata = $logger->eventos[0]['context']['metadata'];
            $this->assertSame(28, $metadata['curl_errno']);
            $this->assertSame('CURL', $metadata['categoriaFalha']);
            $this->assertArrayNotHasKey('status_http', $metadata);
        }
    }

    public function testStatusInformadoSemCabecalhoHttpNaoDeveVirarEvidenciaDeResposta(): void
    {
        $executor = new ExecutorCurlPncpFake();
        $executor->status = 200;
        $logger = new LoggerTentativaHttpFake();

        (new TransporteCurlPncp($this->configuracao(), $executor, $logger))->enviar(
            new RequisicaoHttpPncp('GET', '/v1/recurso', [], null, 30)
        );

        $this->assertArrayNotHasKey('status_http', $logger->eventos[0]['context']['metadata']);
    }

    public function testRespostaInterrompidaPorLimiteDevePreservarStatusComprovado(): void
    {
        $executor = new ExecutorCurlPncpFake();
        $executor->headerLines = ["HTTP/1.1 200 OK\r\n", "\r\n"];
        $executor->bodyChunks = ['12345'];
        $logger = new LoggerTentativaHttpFake();

        try {
            (new TransporteCurlPncp(
                $this->configuracao(limiteResposta: 4),
                $executor,
                $logger
            ))->enviar(new RequisicaoHttpPncp('GET', '/v1/recurso', [], null, 30));
            $this->fail('Era esperado limite de resposta.');
        } catch (FalhaTransportePncpException) {
            $metadata = $logger->eventos[0]['context']['metadata'];
            $this->assertSame(200, $metadata['status_http']);
            $this->assertSame(23, $metadata['curl_errno']);
            $this->assertSame('LIMITE_RESPOSTA', $metadata['categoriaFalha']);
        }
    }

    public function testFalhaDoLoggerNaoDeveAlterarRespostaHttp(): void
    {
        $executor = new ExecutorCurlPncpFake();
        $executor->headerLines = ["HTTP/1.1 200 OK\r\n", "\r\n"];
        $logger = new LoggerTentativaHttpFake();
        $logger->falhar = true;

        $resposta = (new TransporteCurlPncp($this->configuracao(), $executor, $logger))
            ->enviar(new RequisicaoHttpPncp('GET', '/v1/recurso', [], null, 30));

        $this->assertSame(200, $resposta->getStatus());
    }

    public function testProducaoDeveManterBaseCanonicaDoTransporte(): void
    {
        $executor = new ExecutorCurlPncpFake();
        $transporte = new TransporteCurlPncp(
            $this->configuracao(ambiente: AmbientePncp::PRODUCAO),
            $executor
        );

        $transporte->enviar(new RequisicaoHttpPncp(
            'GET',
            '/v1/orgaos/12345678000195/pca/2026/32/consolidado',
            [],
            null,
            30
        ));

        $this->assertSame(
            'https://pncp.gov.br/api/pncp/v1/orgaos/12345678000195/pca/2026/32/consolidado',
            $executor->url
        );
        $this->assertSame(CURLPROTO_HTTPS, $executor->opcoes[CURLOPT_PROTOCOLS]);
        $this->assertSame(true, $executor->opcoes[CURLOPT_SSL_VERIFYPEER]);
        $this->assertSame(2, $executor->opcoes[CURLOPT_SSL_VERIFYHOST]);
    }

    public function testDeveRecusarPayloadAcimaDoLimiteAntesDaExecucao(): void
    {
        $executor = new ExecutorCurlPncpFake();
        $transporte = new TransporteCurlPncp(
            $this->configuracao(limitePayload: 4),
            $executor
        );

        try {
            $transporte->enviar(new RequisicaoHttpPncp('POST', '/v1/recurso', [], '12345', 60));
            $this->fail('Era esperado bloqueio do payload acima do limite.');
        } catch (FalhaTransportePncpException $exception) {
            $this->assertSame(
                'PNCP_TRANSPORTE_PAYLOAD_EXCEDE_LIMITE',
                $exception->getCodigoSeguro()
            );
            $this->assertSame(0, $executor->execucoes);
        }
    }

    public function testDeveInterromperRespostaAcimaDoLimiteConfigurado(): void
    {
        $executor = new ExecutorCurlPncpFake();
        $executor->status = 200;
        $executor->headerLines = ["HTTP/1.1 200 OK\r\n", "\r\n"];
        $executor->bodyChunks = ['1234', '5'];
        $transporte = new TransporteCurlPncp(
            $this->configuracao(limiteResposta: 4),
            $executor
        );

        $this->expectException(FalhaTransportePncpException::class);
        $this->expectExceptionMessage('Resposta do PNCP excedeu o limite configurado.');

        $transporte->enviar(new RequisicaoHttpPncp('GET', '/v1/recurso', [], null, 30));
    }

    public function testDeveTraduzirFalhaCurlSemExporDadosDaRequisicao(): void
    {
        $executor = new ExecutorCurlPncpFake();
        $executor->executado = false;
        $executor->errno = 28;
        $transporte = new TransporteCurlPncp($this->configuracao(), $executor);

        try {
            $transporte->enviar(new RequisicaoHttpPncp(
                'POST',
                '/v1/usuarios/login',
                ['Authorization' => 'Bearer segredo.jwt.opaco'],
                '{"senha":"segredo"}',
                30
            ));
            $this->fail('Era esperada falha de transporte.');
        } catch (FalhaTransportePncpException $exception) {
            $this->assertSame('PNCP_TRANSPORTE_EXECUCAO_FALHOU', $exception->getCodigoSeguro());
            $this->assertSame('Falha de transporte PNCP (cURL 28).', $exception->getMessage());
            $this->assertStringNotContainsString('segredo', $exception->getMessage());
            $this->assertStringNotContainsString('usuarios/login', $exception->getMessage());
            $this->assertSame(1, $executor->execucoes);
        }
    }

    public function testDeveFalharSeCurlNaoConfirmarTls(): void
    {
        $executor = new ExecutorCurlPncpFake();
        $executor->status = 200;
        $executor->sslVerifyResult = 60;
        $transporte = new TransporteCurlPncp($this->configuracao(), $executor);

        try {
            $transporte->enviar(new RequisicaoHttpPncp('GET', '/v1/recurso', [], null, 30));
            $this->fail('Era esperada falha de verificação TLS.');
        } catch (FalhaTransportePncpException $exception) {
            $this->assertSame('PNCP_TRANSPORTE_TLS_NAO_CONFIRMADO', $exception->getCodigoSeguro());
            $this->assertStringNotContainsString('60', $exception->getMessage());
        }
    }

    public function testExecutorNativoDeveImplementarFronteiraSemExecutarRede(): void
    {
        $this->assertInstanceOf(ExecutorCurlPncp::class, new ExecutorCurlNativoPncp());
    }

    private function configuracao(
        int $limitePayload = 1_024,
        int $limiteResposta = 1_024,
        ?string $caminhoCaBundle = null,
        AmbientePncp $ambiente = AmbientePncp::HOMOLOGACAO
    ): ConfiguracaoTransportePncp {
        return new ConfiguracaoTransportePncp(
            $ambiente,
            $limitePayload,
            $limiteResposta,
            caminhoCaBundle: $caminhoCaBundle
        );
    }
}

final class LoggerTentativaHttpFake extends AbstractLogger
{
    public bool $falhar = false;

    /** @var array<int, array{level: mixed, message: string, context: array}> */
    public array $eventos = [];

    public function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        if ($this->falhar) {
            throw new \RuntimeException('Falha simulada do logger.');
        }

        $this->eventos[] = compact('level', 'message', 'context');
    }
}

final class ExecutorCurlPncpFake implements ExecutorCurlPncp
{
    public int $execucoes = 0;
    public ?string $url = null;

    /** @var array<int, mixed> */
    public array $opcoes = [];

    /** @var string[] */
    public array $headerLines = [];

    /** @var string[] */
    public array $bodyChunks = [];

    public bool $executado = true;
    public int $errno = 0;
    public int $status = 200;
    public int $sslVerifyResult = 0;
    public ?string $cabecalhosEnviados = null;

    /** @var array<string, int|string> */
    public array $diagnosticoConexao = [];

    /** @param array<int, mixed> $opcoes */
    public function executar(string $url, array $opcoes): ResultadoExecucaoCurlPncp
    {
        $this->execucoes++;
        $this->url = $url;
        $this->opcoes = $opcoes;

        foreach ($this->headerLines as $linha) {
            $consumidos = $opcoes[CURLOPT_HEADERFUNCTION](null, $linha);

            if ($consumidos !== strlen($linha)) {
                $this->executado = false;
                $this->errno = 23;
                break;
            }
        }

        if ($this->executado) {
            foreach ($this->bodyChunks as $trecho) {
                $consumidos = $opcoes[CURLOPT_WRITEFUNCTION](null, $trecho);

                if ($consumidos !== strlen($trecho)) {
                    $this->executado = false;
                    $this->errno = 23;
                    break;
                }
            }
        }

        return new ResultadoExecucaoCurlPncp(
            $this->executado,
            $this->errno,
            $this->status,
            $this->sslVerifyResult,
            CURL_HTTP_VERSION_1_1,
            $this->cabecalhosEnviados,
            $this->diagnosticoConexao
        );
    }
}
