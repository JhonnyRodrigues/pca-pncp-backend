<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint59;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Semae\Pca\Application\Results\ResultadoBuscaCandidatosPca;
use Semae\Pca\Application\Results\ResultadoObservacaoPca;
use Semae\Pca\Application\Results\ResultadoPublicacaoPncp;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Domain\ValueObjects\PcaObservado;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Infrastructure\Observabilidade\AcumuladorEventosTecnicos;
use Semae\Pca\Infrastructure\Observabilidade\RelogioSistemaEventosTecnicos;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Observabilidade\AcaoTecnicaPncp;
use Tests\Contract\Sprint59\Support\PncpAdaptersContractRunner;
use Tests\Contract\Sprint59\Support\PncpDiagnosticTransport;

final class PncpAdaptersContractRunnerTest extends TestCase
{
    public function testDeveAceitarPublicacaoAceitaComSequencial(): void
    {
        $this->assertNull($this->invoke(
            'validatePublication',
            [
                ResultadoPublicacaoPncp::aceita(new SequencialPca(77)),
                $this->response([
                    'location' => ['/v1/orgaos/12345678000195/pca/2027/77'],
                ]),
                $this->request(),
                null,
            ]
        ));
    }

    public function testDeveDiagnosticarPublicacaoRejeitada(): void
    {
        $exception = $this->publicationFailure(
            ResultadoPublicacaoPncp::rejeitada(new FalhaSegura(
                'PNCP_PUBLICACAO_REJEITADA',
                'O PNCP rejeitou a publicacao.'
            )),
            new RespostaHttpPncp(422, ['x-diagnostico' => ['rejeitada']], 'erro')
        );

        $diagnostic = $this->decodeDiagnostic($exception);

        $this->assertSame('REJEITADA', $diagnostic['situacao']);
        $this->assertSame('PNCP_PUBLICACAO_REJEITADA', $diagnostic['falha_codigo']);
        $this->assertSame('O PNCP rejeitou a publicacao.', $diagnostic['falha_mensagem']);
        $this->assertSame(422, $diagnostic['status_http']);
        $this->assertSame('PUBLICACAO_REJEITADA', $diagnostic['classificacao_causa']);
    }

    public function testDeveDiagnosticarPublicacaoIncertaPorStatusInesperado(): void
    {
        $exception = $this->publicationFailure(
            $this->uncertainPublication(),
            new RespostaHttpPncp(503, ['retry-after' => ['30']], 'indisponivel')
        );

        $diagnostic = $this->decodeDiagnostic($exception);

        $this->assertSame('RESULTADO_INCERTO', $diagnostic['situacao']);
        $this->assertSame(503, $diagnostic['status_http']);
        $this->assertSame('STATUS_HTTP_INESPERADO', $diagnostic['classificacao_causa']);
    }

    public function testDeveDiagnosticarLocationAusenteSemFallbackPeloCorpo(): void
    {
        $exception = $this->publicationFailure(
            $this->uncertainPublication(),
            $this->response(['x-request-id' => ['abc-123']], '{"sequencialPca":77}')
        );
        $diagnostic = $this->decodeDiagnostic($exception);

        $this->assertSame('RESULTADO_INCERTO', $diagnostic['situacao']);
        $this->assertNull($diagnostic['sequencial_retornado']);
        $this->assertSame(201, $diagnostic['status_http']);
        $this->assertSame([], $diagnostic['location_valores']);
        $this->assertSame(0, $diagnostic['quantidade_location']);
        $this->assertSame(['x-request-id' => ['abc-123']], $diagnostic['headers_resposta']);
        $this->assertSame('{"sequencialPca":77}', $diagnostic['corpo_resposta']);
        $this->assertSame('{"payload":"completo","cnpj":"12345678000195"}', $diagnostic['payload_enviado']);
        $this->assertSame('LOCATION_AUSENTE', $diagnostic['classificacao_causa']);
    }

    public function testDeveDiagnosticarFalhaSistemicaDeTransporteOuTls(): void
    {
        $exception = $this->publicationFailure(
            ResultadoPublicacaoPncp::falhaSistemica(new FalhaSegura(
                'PNCP_PUBLICACAO_TRANSPORTE_FALHOU',
                'Nao foi possivel executar a publicacao no PNCP.'
            )),
            null,
            new RuntimeException('Falha original segura de TLS.')
        );

        $diagnostic = $this->decodeDiagnostic($exception);

        $this->assertSame('Falha original segura de TLS.', $exception->getPrevious()?->getMessage());
        $this->assertSame('FALHA_SISTEMICA', $diagnostic['situacao']);
        $this->assertNull($diagnostic['status_http']);
        $this->assertSame('FALHA_TRANSPORTE_OU_TLS', $diagnostic['classificacao_causa']);
    }

    public function testDiagnosticoNaoDeveExporDadosSensiveis(): void
    {
        $exception = $this->publicationFailure(
            ResultadoPublicacaoPncp::falhaSistemica(new FalhaSegura(
                'PNCP_PUBLICACAO_TRANSPORTE_FALHOU',
                'usuario-secreto senha=senha-secreta Authorization: Bearer token-secreto'
            )),
            null
        );

        $this->assertStringNotContainsString('usuario-secreto', $exception->getMessage());
        $this->assertStringNotContainsString('senha-secreta', $exception->getMessage());
        $this->assertStringNotContainsString('token-secreto', $exception->getMessage());
        $this->assertStringNotContainsString('Authorization', $exception->getMessage());
        $this->assertStringContainsString('12345678000195', $exception->getMessage());
        $this->assertSame(
            'FALHA_TRANSPORTE_OU_TLS',
            $this->decodeDiagnostic($exception)['classificacao_causa']
        );
    }

    public function testDevePreservarCausaQuandoLimpezaForConfirmada(): void
    {
        $cause = new RuntimeException('diagnostico seguro');
        $failure = $this->invoke('gateFailureAfterCleanup', [$cause, true]);

        $this->assertInstanceOf(RuntimeException::class, $failure);
        $this->assertSame($cause, $failure->getPrevious());
        $diagnostic = $this->decodeDiagnostic($failure);

        $this->assertFalse($diagnostic['confirmado']);
        $this->assertTrue($diagnostic['limpeza_confirmada']);
        $this->assertFalse($diagnostic['residuo_possivel']);
        $this->assertSame('NAO_INICIADA', $diagnostic['resultado_limpeza']['tentativa']);
    }

    public function testDevePreservarGateRecusadoQuandoLimpezaNaoForConfirmada(): void
    {
        $cause = new RuntimeException('diagnostico seguro');
        $failure = $this->invoke('gateFailureAfterCleanup', [$cause, false]);

        $this->assertInstanceOf(RuntimeException::class, $failure);
        $this->assertSame($cause, $failure->getPrevious());
        $diagnostic = $this->decodeDiagnostic($failure);

        $this->assertFalse($diagnostic['confirmado']);
        $this->assertFalse($diagnostic['limpeza_confirmada']);
        $this->assertTrue($diagnostic['residuo_possivel']);
        $this->assertFalse(
            $diagnostic['resultado_limpeza']['ausencia_definitiva_confirmada']
        );
    }

    public function testDeveObterStatusHttpDoEventoTecnicoDaPublicacao(): void
    {
        $logger = new AcumuladorEventosTecnicos(
            '123e4567-e89b-42d3-a456-426614174000',
            'PNCP_TREINAMENTO',
            'CONTRATOS_ADAPTERS_SPRINT_59',
            null,
            new RelogioSistemaEventosTecnicos()
        );
        $logger->error('Publicacao rejeitada.', [
            'action' => AcaoTecnicaPncp::PUBLICAR_PCA_PNCP->value,
            'metadata' => ['situacao' => 'REJEITADA', 'statusHttp' => 422],
        ]);
        $property = new ReflectionProperty(PncpAdaptersContractRunner::class, 'logger');
        $runnerLogger = $property->getValue($this->runner());
        foreach ($logger->getEventos() as $event) {
            $envelope = $event->paraEnvelope();
            $runnerLogger->error($envelope['message'], [
                'action' => $envelope['action'],
                'metadata' => $envelope['metadata'],
            ]);
        }

        $this->assertSame(422, $this->invoke('publicationHttpStatus'));
    }

    public function testDeveDiagnosticarLocationDuplicadoComValoresCompletos(): void
    {
        $locations = [
            '/v1/orgaos/12345678000195/pca/2027/77',
            '/v1/orgaos/12345678000195/pca/2027/78',
        ];
        $exception = $this->publicationFailure(
            $this->uncertainPublication(),
            $this->response(['location' => $locations])
        );
        $diagnostic = $this->decodeDiagnostic($exception);

        $this->assertSame($locations, $diagnostic['location_valores']);
        $this->assertSame(2, $diagnostic['quantidade_location']);
        $this->assertSame('LOCATION_DUPLICADO', $diagnostic['classificacao_causa']);
    }

    #[DataProvider('locations')]
    public function testDeveClassificarFormatoDoLocation(string $location, string $expected): void
    {
        $response = $this->response(['location' => [$location]]);

        $this->assertSame($expected, $this->invoke('classifyLocation', [$response]));
    }

    /** @return array<string, array{string, string}> */
    public static function locations(): array
    {
        return [
            'relativo canonico' => [
                '/v1/orgaos/12345678000195/pca/2027/77',
                'LOCATION_RELATIVO_CANONICO',
            ],
            'absoluto canonico' => [
                'https://treina.pncp.gov.br/api/pncp/v1/orgaos/12345678000195/pca/2027/77',
                'LOCATION_ABSOLUTO_CANONICO',
            ],
            'absoluto emitido pela homologacao' => [
                'https://treina.pncp.gov.br/pncp-api/v1/orgaos/12345678000195/pca/2027/77',
                'LOCATION_ABSOLUTO_CANONICO_ALTERNATIVO',
            ],
            'host divergente' => [
                'https://pncp.gov.br/api/pncp/v1/orgaos/12345678000195/pca/2027/77',
                'LOCATION_HOST_DIVERGENTE',
            ],
            'caminho divergente' => [
                '/v1/orgaos/12345678000195/contratacoes/2027/77',
                'LOCATION_CAMINHO_DIVERGENTE',
            ],
            'CNPJ divergente' => [
                '/v1/orgaos/98765432000198/pca/2027/77',
                'LOCATION_CNPJ_DIVERGENTE',
            ],
            'ano divergente' => [
                '/v1/orgaos/12345678000195/pca/2026/77',
                'LOCATION_ANO_DIVERGENTE',
            ],
            'sequencial invalido' => [
                '/v1/orgaos/12345678000195/pca/2027/0',
                'LOCATION_SEQUENCIAL_INVALIDO',
            ],
            'query string' => [
                '/v1/orgaos/12345678000195/pca/2027/77?origem=externa',
                'LOCATION_COM_QUERY_STRING',
            ],
            'fragmento' => [
                '/v1/orgaos/12345678000195/pca/2027/77#detalhe',
                'LOCATION_COM_FRAGMENTO',
            ],
        ];
    }

    public function testTransporteDiagnosticoDeveDelegarECapturarRequisicaoEResposta(): void
    {
        $request = $this->request();
        $response = $this->response(['location' => ['/v1/orgaos/12345678000195/pca/2027/77']]);
        $inner = new class ($response) implements TransporteHttpPncp {
            public int $calls = 0;

            public function __construct(private readonly RespostaHttpPncp $response)
            {
            }

            public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
            {
                ++$this->calls;

                return $this->response;
            }
        };
        $transport = new PncpDiagnosticTransport($inner);

        $this->assertSame($response, $transport->enviar($request));
        $this->assertSame(1, $inner->calls);
        $this->assertSame($request, $transport->getUltimaRequisicao());
        $this->assertSame($response, $transport->getUltimaResposta());
        $this->assertNull($transport->getUltimaFalha());
        $this->assertCount(1, $transport->getHistorico());
        $this->assertSame($request, $transport->getHistorico()[0]['requisicao']);
        $this->assertSame($response, $transport->getHistorico()[0]['resposta']);

        $transport->limparHistorico();

        $this->assertSame([], $transport->getHistorico());
        $this->assertNull($transport->getUltimaRequisicao());
    }

    public function testTransporteDiagnosticoDevePreservarFalhaOriginal(): void
    {
        $failure = new RuntimeException('Falha original.');
        $inner = new class ($failure) implements TransporteHttpPncp {
            public function __construct(private readonly RuntimeException $failure)
            {
            }

            public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
            {
                throw $this->failure;
            }
        };
        $transport = new PncpDiagnosticTransport($inner);

        try {
            $transport->enviar($this->request());
            $this->fail('Era esperada a falha original.');
        } catch (RuntimeException $actual) {
            $this->assertSame($failure, $actual);
        }

        $this->assertSame($failure, $transport->getUltimaFalha());
        $this->assertNull($transport->getUltimaResposta());
        $this->assertSame($failure, $transport->getHistorico()[0]['falha']);
    }

    public function testFalhaDaLimpezaDeveInformarLeiturasEExclusaoSemDadosSensiveis(): void
    {
        $responses = [
            new RespostaHttpPncp(404, ['x-request-id' => ['interno']], '{"cnpj":"12345678000195"}'),
            new RespostaHttpPncp(200, ['authorization' => ['nao-expor']], 'corpo-nao-expor'),
        ];
        $inner = new class ($responses) implements TransporteHttpPncp {
            /** @param RespostaHttpPncp[] $responses */
            public function __construct(private array $responses)
            {
            }

            public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
            {
                return array_shift($this->responses)
                    ?? throw new RuntimeException('Resposta nao configurada.');
            }
        };
        $transport = new PncpDiagnosticTransport($inner);
        $transport->enviar(new RequisicaoHttpPncp(
            'GET',
            '/v1/orgaos/12345678000195/pca/2027/24/consolidado',
            ['Authorization' => 'Bearer token-secreto'],
            null,
            30
        ));
        $transport->enviar(new RequisicaoHttpPncp(
            'DELETE',
            '/v1/orgaos/12345678000195/pca/2027/24',
            ['Authorization' => 'Bearer token-secreto'],
            '{"justificativa":"nao-expor"}',
            60
        ));
        $observation = ResultadoObservacaoPca::inconclusiva(new FalhaSegura(
            'PNCP_RESPOSTA_INCONCLUSIVA',
            'A resposta nao permitiu conclusao segura.'
        ));
        $failure = new RuntimeException('Falha original segura.');
        $exception = $this->invokeOn(
            $this->runnerWithDiagnosticTransport($transport),
            'cleanupFailure',
            [$failure, $observation]
        );
        $diagnostic = $this->decodeDiagnostic($exception);

        $this->assertSame($failure, $exception->getPrevious());
        $this->assertSame('INCONCLUSIVA', $diagnostic['situacao_observacao']);
        $this->assertSame('PNCP_RESPOSTA_INCONCLUSIVA', $diagnostic['falha_codigo']);
        $this->assertSame(
            'A resposta nao permitiu conclusao segura.',
            $diagnostic['falha_mensagem']
        );
        $this->assertSame(0, $diagnostic['quantidade_itens_observados']);
        $this->assertSame([[
            'status_http' => 404,
            'caminho_logico' => '/v1/orgaos/{cnpj}/pca/2027/24/consolidado',
            'codigo_resposta_seguro' => null,
            'mensagem_resposta_segura' => null,
        ]], $diagnostic['requisicoes_leitura']);
        $this->assertSame(200, $diagnostic['status_http_exclusao']);
        $this->assertTrue($diagnostic['exclusao_aceita']);
        $this->assertFalse($diagnostic['publicacao_executada']);
        $this->assertStringNotContainsString('12345678000195', $exception->getMessage());
        $this->assertStringNotContainsString('token-secreto', $exception->getMessage());
        $this->assertStringNotContainsString('Authorization', $exception->getMessage());
        $this->assertStringNotContainsString('corpo-nao-expor', $exception->getMessage());
        $this->assertStringNotContainsString('nao-expor', $exception->getMessage());
    }

    public function testDiagnosticoDaLimpezaDeveContarItensObservados(): void
    {
        $transport = new PncpDiagnosticTransport(new class implements TransporteHttpPncp {
            public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
            {
                return new RespostaHttpPncp(200, [], '');
            }
        });
        $observation = ResultadoObservacaoPca::encontrado(PcaObservado::ativo(
            new SequencialPca(24),
            [101, 103]
        ));
        $diagnostic = $this->invokeOn(
            $this->runnerWithDiagnosticTransport($transport),
            'cleanupDiagnostic',
            [$observation]
        );

        $this->assertSame('ENCONTRADO', $diagnostic['situacao_observacao']);
        $this->assertSame(2, $diagnostic['quantidade_itens_observados']);
    }

    public function testDiagnosticoDaLimpezaDeveExtrairErroHttpSanitizado(): void
    {
        $response = new RespostaHttpPncp(400, [], json_encode([
            'error' => 'BAD_REQUEST',
            'message' => 'CNPJ 12345678000195 rejeitado em https://interno.exemplo/recurso',
            'trace' => 'corpo-completo-nao-expor',
        ], JSON_THROW_ON_ERROR));
        $inner = new class ($response) implements TransporteHttpPncp {
            public function __construct(private readonly RespostaHttpPncp $response)
            {
            }

            public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
            {
                return $this->response;
            }
        };
        $transport = new PncpDiagnosticTransport($inner);
        $transport->enviar(new RequisicaoHttpPncp(
            'GET',
            '/v1/orgaos/12345678000195/pca/2027/24/consolidado',
            ['Authorization' => 'Bearer token-secreto'],
            null,
            30
        ));

        $diagnostic = $this->invokeOn(
            $this->runnerWithDiagnosticTransport($transport),
            'cleanupDiagnostic',
            [null]
        );
        $read = $diagnostic['requisicoes_leitura'][0];

        $this->assertSame('BAD_REQUEST', $read['codigo_resposta_seguro']);
        $this->assertSame(
            'CNPJ {cnpj} rejeitado em {url}',
            $read['mensagem_resposta_segura']
        );
        $encoded = json_encode($diagnostic, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('12345678000195', $encoded);
        $this->assertStringNotContainsString('interno.exemplo', $encoded);
        $this->assertStringNotContainsString('corpo-completo-nao-expor', $encoded);
        $this->assertStringNotContainsString('token-secreto', $encoded);
    }

    public function testLimpezaDeveConfirmarAusenciaPorBuscaConcluidaSemCandidatos(): void
    {
        $observation = $this->invoke(
            'cleanupObservationFromSearch',
            [ResultadoBuscaCandidatosPca::concluida([]), 24]
        );

        $this->assertSame('AUSENCIA_DEFINITIVA', $observation->getSituacao()->value);
    }

    public function testLimpezaDeveRecuperarResiduoAutorizadoPelaBusca(): void
    {
        $candidate = PcaObservado::ativo(new SequencialPca(24), [101]);
        $observation = $this->invoke(
            'cleanupObservationFromSearch',
            [ResultadoBuscaCandidatosPca::concluida([$candidate]), 24]
        );

        $this->assertSame('ENCONTRADO', $observation->getSituacao()->value);
        $this->assertSame($candidate, $observation->getPcaObservado());
    }

    public function testLimpezaDeveRecusarCandidatoDiferenteDoResiduoAutorizado(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('residuo diferente do autorizado');

        $this->invoke('cleanupObservationFromSearch', [
            ResultadoBuscaCandidatosPca::concluida([
                PcaObservado::ativo(new SequencialPca(25), []),
            ]),
            24,
        ]);
    }

    public function testGateNaoDevePublicarEnquantoHouverResiduoExplicitamentePendente(): void
    {
        $reflection = new ReflectionClass(PncpAdaptersContractRunner::class);
        $runner = $reflection->newInstanceWithoutConstructor();
        $property = new ReflectionProperty(PncpAdaptersContractRunner::class, 'config');
        $property->setValue($runner, ['owned_residue_sequence' => 24]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('execute a limpeza isolada');

        $runner->run();
    }

    public function testLimpezaDeveExigirResiduoExplicitamenteAutorizado(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('owned_residue_sequence explicitamente autorizado');

        $this->runner()->cleanupOwnedResidue();
    }

    private function uncertainPublication(): ResultadoPublicacaoPncp
    {
        return ResultadoPublicacaoPncp::resultadoIncerto(new FalhaSegura(
            'PNCP_PUBLICACAO_RESULTADO_INCERTO',
            'Nao foi possivel determinar o resultado da publicacao no PNCP.'
        ));
    }

    private function publicationFailure(
        ResultadoPublicacaoPncp $publication,
        ?RespostaHttpPncp $response,
        ?RuntimeException $transportFailure = null
    ): RuntimeException {
        try {
            $this->invoke('validatePublication', [
                $publication,
                $response,
                $this->request(),
                $transportFailure,
            ]);
            $this->fail('Era esperada falha de publicacao.');
        } catch (RuntimeException $exception) {
            return $exception;
        }
    }

    /** @return array<string, mixed> */
    private function decodeDiagnostic(RuntimeException $exception): array
    {
        return json_decode($exception->getMessage(), true, 512, JSON_THROW_ON_ERROR);
    }

    /** @param array<string, string[]> $headers */
    private function response(array $headers, string $body = ''): RespostaHttpPncp
    {
        return new RespostaHttpPncp(201, $headers, $body);
    }

    private function request(): RequisicaoHttpPncp
    {
        return new RequisicaoHttpPncp(
            'POST',
            '/v1/orgaos/12345678000195/pca',
            ['Authorization' => 'Bearer token-secreto'],
            '{"payload":"completo","cnpj":"12345678000195"}',
            60
        );
    }

    /** @param mixed[] $arguments */
    private function invoke(string $methodName, array $arguments = []): mixed
    {
        $method = new ReflectionMethod(PncpAdaptersContractRunner::class, $methodName);

        return $method->invoke($this->runner(), ...$arguments);
    }

    /** @param mixed[] $arguments */
    private function invokeOn(
        PncpAdaptersContractRunner $runner,
        string $methodName,
        array $arguments = []
    ): mixed {
        $method = new ReflectionMethod(PncpAdaptersContractRunner::class, $methodName);

        return $method->invoke($runner, ...$arguments);
    }

    private function runnerWithDiagnosticTransport(
        PncpDiagnosticTransport $transport
    ): PncpAdaptersContractRunner {
        $reflection = new ReflectionClass(PncpAdaptersContractRunner::class);
        $runner = $reflection->newInstanceWithoutConstructor();
        $property = new ReflectionProperty(PncpAdaptersContractRunner::class, 'config');
        $property->setValue($runner, [
            'login' => 'usuario-secreto',
            'senha' => 'senha-secreta',
            'cnpj' => '12345678000195',
            'codigo_uasg' => '986789',
            'descricao_uasg' => 'Unidade de treinamento',
            'ano' => 2027,
            'owned_residue_sequence' => 24,
        ]);
        $property = new ReflectionProperty(
            PncpAdaptersContractRunner::class,
            'diagnosticTransport'
        );
        $property->setValue($runner, $transport);

        return $runner;
    }

    private function runner(): PncpAdaptersContractRunner
    {
        static $runner;

        if (!$runner instanceof PncpAdaptersContractRunner) {
            $reflection = new ReflectionClass(PncpAdaptersContractRunner::class);
            $runner = $reflection->newInstanceWithoutConstructor();
            $property = new ReflectionProperty(PncpAdaptersContractRunner::class, 'config');
            $property->setValue($runner, [
                'login' => 'usuario-secreto',
                'senha' => 'senha-secreta',
                'cnpj' => '12345678000195',
                'codigo_uasg' => '986789',
                'descricao_uasg' => 'Unidade de treinamento',
                'ano' => 2027,
                'owned_residue_sequence' => null,
            ]);
            $logger = new AcumuladorEventosTecnicos(
                '123e4567-e89b-42d3-a456-426614174000',
                'PNCP_TREINAMENTO',
                'CONTRATOS_ADAPTERS_SPRINT_59',
                null,
                new RelogioSistemaEventosTecnicos()
            );
            $property = new ReflectionProperty(PncpAdaptersContractRunner::class, 'logger');
            $property->setValue($runner, $logger);
        }

        return $runner;
    }
}
