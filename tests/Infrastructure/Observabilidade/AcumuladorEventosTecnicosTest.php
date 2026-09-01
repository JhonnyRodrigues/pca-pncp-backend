<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Observabilidade;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException as InvalidArgumentExceptionPsr;
use Semae\Pca\Application\Enums\AcaoProcessamentoUnidade;
use Semae\Pca\Infrastructure\Observabilidade\AcumuladorEventosTecnicos;
use Semae\Pca\Infrastructure\Observabilidade\RelogioEventosTecnicos;
use Semae\Pca\Infrastructure\Pncp\Observabilidade\AcaoTecnicaPncp;

final class AcumuladorEventosTecnicosTest extends TestCase
{
    private const CORRELACAO = '550e8400-e29b-41d4-a716-446655440000';

    public function testDeveImplementarPsr3EProduzirEnvelopeExato(): void
    {
        [$logger] = $this->criarLogger();

        $logger->info('Resultado externo recebido.', [
            'action' => AcaoProcessamentoUnidade::PROCESSAR_RESULTADO_ESCRITA->value,
            'context' => ['anoExecucao' => 2027],
            'metadata' => ['tentativa' => 1],
        ]);

        $this->assertInstanceOf(LoggerInterface::class, $logger);
        $eventos = $logger->getEventos();
        $this->assertCount(1, $eventos);
        $this->assertSame([
            'timestamp' => '2026-08-24T15:30:45+00:00',
            'level' => 'INFO',
            'correlationId' => self::CORRELACAO,
            'environment' => 'treinamento',
            'system' => 'pca-pncp-backend',
            'module' => 'publicacao-pca',
            'action' => 'PROCESSAR_RESULTADO_ESCRITA',
            'message' => 'Resultado externo recebido.',
            'error' => [],
            'context' => ['anoExecucao' => 2027],
            'metadata' => ['tentativa' => 1],
            'ipAddress' => '192.0.2.10',
        ], $eventos[0]->paraEnvelope());
    }

    public function testDeveExigirUuidV4SomenteNoAcumulador(): void
    {
        foreach ([
            '',
            'corr-legada',
            '550e8400-e29b-11d4-a716-446655440000',
            '550e8400-e29b-41d4-7716-446655440000',
        ] as $correlacao) {
            try {
                new AcumuladorEventosTecnicos(
                    $correlacao,
                    'treinamento',
                    'publicacao-pca',
                    null,
                    new RelogioEventosTecnicosFake()
                );
                $this->fail('Era esperado UUID v4 inválido.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testDevePreservarUuidV4LiteralEAceitarAcaoGlobal(): void
    {
        $correlacao = '550E8400-E29B-41D4-A716-446655440000';
        $logger = new AcumuladorEventosTecnicos(
            $correlacao,
            'HOMOLOGACAO',
            'PUBLICACAO_PCA',
            null,
            new RelogioEventosTecnicosFake()
        );

        $logger->error('Não foi possível concluir a publicação do PCA.', [
            'action' => 'EXECUTAR_PUBLICACAO_PCA',
            'error' => [
                'code' => 'FALHA_INESPERADA',
                'message' => 'Não foi possível concluir a publicação do PCA.',
            ],
        ]);

        $this->assertCount(1, $logger->getEventos());
        $envelope = $logger->getEventos()[0]->paraEnvelope();
        $this->assertSame($correlacao, $envelope['correlationId']);
        $this->assertSame('EXECUTAR_PUBLICACAO_PCA', $envelope['action']);
    }

    public function testDeveExigirAmbienteEModuloExplicitosETratarIpInvalidoComoAusente(): void
    {
        foreach ([['', 'modulo'], ['producao', '   ']] as [$ambiente, $modulo]) {
            try {
                new AcumuladorEventosTecnicos(
                    self::CORRELACAO,
                    $ambiente,
                    $modulo,
                    null,
                    new RelogioEventosTecnicosFake()
                );
                $this->fail('Era esperada configuração técnica inválida.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }

        $logger = new AcumuladorEventosTecnicos(
            self::CORRELACAO,
            'producao',
            'publicacao-pca',
            'HTTP_X_FORWARDED_FOR: 203.0.113.1',
            new RelogioEventosTecnicosFake()
        );
        $logger->info('Evento.', ['action' => AcaoProcessamentoUnidade::FINALIZAR_UNIDADE->value]);

        $this->assertNull($logger->getEventos()[0]->getIpAddress());
    }

    public function testDeveNormalizarTodosOsNiveisPsr3ParaQuatroNiveisTecnicos(): void
    {
        [$logger] = $this->criarLogger();
        $esperados = [
            LogLevel::DEBUG => 'DEBUG',
            LogLevel::INFO => 'INFO',
            LogLevel::NOTICE => 'INFO',
            LogLevel::WARNING => 'WARNING',
            LogLevel::ERROR => 'ERROR',
            LogLevel::CRITICAL => 'ERROR',
            LogLevel::ALERT => 'ERROR',
            LogLevel::EMERGENCY => 'ERROR',
        ];

        foreach ($esperados as $nivelPsr => $nivelEsperado) {
            $logger->log($nivelPsr, 'Evento.', [
                'action' => AcaoProcessamentoUnidade::FINALIZAR_UNIDADE->value,
            ]);
        }

        $this->assertSame(
            array_values($esperados),
            array_map(static fn ($evento): string => $evento->getLevel(), $logger->getEventos())
        );
    }

    public function testDeveRejeitarNivelForaDoContratoPsr3(): void
    {
        [$logger] = $this->criarLogger();

        $this->expectException(InvalidArgumentExceptionPsr::class);

        $logger->log('sucesso', 'Evento.', [
            'action' => AcaoProcessamentoUnidade::FINALIZAR_UNIDADE->value,
        ]);
    }

    public function testDeveManterOperacaoNaActionEFalhaSeguraSeparadaNoError(): void
    {
        [$logger] = $this->criarLogger();

        $logger->error('Não foi possível finalizar a unidade.', [
            'action' => AcaoProcessamentoUnidade::FINALIZAR_UNIDADE->value,
            'error' => [
                'code' => 'FINALIZACAO_UNIDADE_FALHOU',
                'message' => 'Não foi possível finalizar a unidade.',
            ],
        ]);

        $envelope = $logger->getEventos()[0]->paraEnvelope();
        $this->assertSame('FINALIZAR_UNIDADE', $envelope['action']);
        $this->assertSame('FINALIZACAO_UNIDADE_FALHOU', $envelope['error']['code']);
        $this->assertSame(
            'Não foi possível finalizar a unidade.',
            $envelope['error']['message']
        );
        $this->assertNotSame($envelope['error']['code'], $envelope['action']);
    }

    public function testDevePreservarOrdemDosObjetosImutaveis(): void
    {
        [$logger, $relogio] = $this->criarLogger();
        $logger->debug('Primeiro.', ['action' => AcaoProcessamentoUnidade::VALIDAR_CONVERGENCIA->value]);
        $relogio->agora = new DateTimeImmutable('2026-08-24 15:30:46', new DateTimeZone('UTC'));
        $logger->warning('Segundo.', ['action' => AcaoProcessamentoUnidade::DESFAZER_UNIDADE->value]);

        $eventos = $logger->getEventos();
        $this->assertSame('Primeiro.', $eventos[0]->getMessage());
        $this->assertSame('Segundo.', $eventos[1]->getMessage());
        $this->assertSame('2026-08-24T15:30:45+00:00', $eventos[0]->getTimestamp());
        $this->assertSame('2026-08-24T15:30:46+00:00', $eventos[1]->getTimestamp());
    }

    public function testDeveSanitizarRecursivamenteMapasMensagemEValoresNaoSerializaveis(): void
    {
        [$logger] = $this->criarLogger();
        $recurso = fopen('php://memory', 'r');

        $logger->error(
            'Falha com Bearer abc.def.ghi ao executar SELECT senha FROM usuarios.',
            [
                'action' => AcaoProcessamentoUnidade::EXECUTAR_RECONCILIACAO->value,
                'error' => [
                    'code' => 'RECONCILIACAO_FALHOU',
                    'message' => 'Falha técnica sanitizada.',
                    'exception' => new \RuntimeException('segredo'),
                ],
                'context' => [
                    'authorization' => 'Bearer abc.def.ghi',
                    'nested' => ['password' => '123', 'permitido' => 'valor'],
                    'jwtDisfarçado' => 'aaa.bbb.ccc',
                    'sql' => 'SELECT * FROM sensivel',
                    'recurso' => $recurso,
                    'infinito' => INF,
                ],
                'metadata' => [
                    'payload' => ['senha' => '123'],
                    'response' => '{"token":"segredo"}',
                    'permitido' => true,
                ],
            ]
        );

        if (is_resource($recurso)) {
            fclose($recurso);
        }

        $envelope = $logger->getEventos()[0]->paraEnvelope();
        $serializado = json_encode($envelope, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('abc.def.ghi', $serializado);
        $this->assertStringNotContainsString('SELECT', $serializado);
        $this->assertStringNotContainsString('senha', strtolower($serializado));
        $this->assertStringNotContainsString('segredo', $serializado);
        $this->assertSame(['permitido' => 'valor'], $envelope['context']['nested']);
        $this->assertSame(['permitido' => true], $envelope['metadata']);
        $this->assertSame('RECONCILIACAO_FALHOU', $envelope['error']['code']);
    }

    public function testDeveIgnorarChamadaSemAcaoOperacionalValidaSemViolarPsr3(): void
    {
        [$logger] = $this->criarLogger();

        $logger->info('Sem ação.');
        $logger->warning('Ação desconhecida.', ['action' => 'CODIGO_FALHA_SEGURA']);

        $this->assertSame([], $logger->getEventos());
    }

    public function testDeveAceitarAcaoTecnicaPncpSemAmpliarEnumDaApplication(): void
    {
        [$logger] = $this->criarLogger();

        $logger->info('Autenticação PNCP concluída.', [
            'action' => AcaoTecnicaPncp::AUTENTICAR_PNCP->value,
        ]);

        $this->assertSame(
            'AUTENTICAR_PNCP',
            $logger->getEventos()[0]->paraEnvelope()['action']
        );
    }

    public function testLoteDeveAdicionarDuracaoSomenteACopiaDoUltimoEnvelope(): void
    {
        [$logger, $relogio] = $this->criarLogger();
        $logger->info('Primeiro.', [
            'action' => AcaoProcessamentoUnidade::PROCESSAR_RESULTADO_ESCRITA->value,
            'metadata' => ['ordem' => 1],
        ]);
        $logger->info('Segundo.', [
            'action' => AcaoProcessamentoUnidade::PERSISTIR_CONVERGENCIA->value,
            'metadata' => ['ordem' => 2],
        ]);
        $ultimoOriginal = $logger->getEventos()[1];
        $metadataOriginal = $ultimoOriginal->getMetadata();
        $relogio->tempoMonotonicoMs = 1125;

        $lote = $logger->criarLoteParaExportacao();
        $envelopes = $lote->getEnvelopes();

        $this->assertSame(125, $lote->getExecutionTimeMs());
        $this->assertArrayNotHasKey('executionTimeMs', $envelopes[0]['metadata']);
        $this->assertSame(
            ['ordem' => 2, 'executionTimeMs' => 125],
            $envelopes[1]['metadata']
        );
        $this->assertSame($metadataOriginal, $ultimoOriginal->getMetadata());
        $this->assertArrayNotHasKey('executionTimeMs', $ultimoOriginal->paraEnvelope()['metadata']);

        $logger->info('Terceiro.', ['action' => AcaoProcessamentoUnidade::FINALIZAR_UNIDADE->value]);
        $relogio->tempoMonotonicoMs = 1200;
        $segundoLote = $logger->criarLoteParaExportacao();
        $this->assertCount(3, $segundoLote->getEnvelopes());
        $this->assertSame(
            200,
            $segundoLote->getEnvelopes()[2]['metadata']['executionTimeMs']
        );
    }

    public function testLoteVazioDevePermanecerVazioMesmoComDuracaoCalculada(): void
    {
        [$logger, $relogio] = $this->criarLogger();
        $relogio->tempoMonotonicoMs = 1050;

        $lote = $logger->criarLoteParaExportacao();

        $this->assertSame([], $lote->getEnvelopes());
        $this->assertSame(50, $lote->getExecutionTimeMs());
    }

    /** @return array{AcumuladorEventosTecnicos, RelogioEventosTecnicosFake} */
    private function criarLogger(): array
    {
        $relogio = new RelogioEventosTecnicosFake();

        return [
            new AcumuladorEventosTecnicos(
                self::CORRELACAO,
                'treinamento',
                'publicacao-pca',
                '192.0.2.10',
                $relogio
            ),
            $relogio,
        ];
    }
}

final class RelogioEventosTecnicosFake implements RelogioEventosTecnicos
{
    public DateTimeImmutable $agora;
    public int $tempoMonotonicoMs = 1000;

    public function __construct()
    {
        $this->agora = new DateTimeImmutable('2026-08-24 15:30:45', new DateTimeZone('UTC'));
    }

    public function agora(): DateTimeImmutable
    {
        return $this->agora;
    }

    public function tempoMonotonicoMs(): int
    {
        return $this->tempoMonotonicoMs;
    }
}
