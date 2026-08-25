<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Pncp\Leitura;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Semae\Pca\Application\Enums\SituacaoObservacaoPca;
use Semae\Pca\Application\Results\ResultadoObservacaoPca;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Semae\Pca\Infrastructure\Pncp\Autenticacao\SessaoPncpAutenticada;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Semae\Pca\Infrastructure\Pncp\Http\ConfiguracaoTransportePncp;
use Semae\Pca\Infrastructure\Pncp\Http\FalhaTransportePncpException;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\ConfiguracaoLeituraPncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\LeitorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Leitura\TemporizadorPncp;
use Stringable;

final class RetryLeituraPncpTest extends TestCase
{
    /** @return iterable<string, array{int}> */
    public static function statusTransitorios(): iterable
    {
        yield 'request timeout' => [408];
        yield 'too many requests' => [429];
        yield 'bad gateway' => [502];
        yield 'service unavailable' => [503];
        yield 'gateway timeout' => [504];
    }

    #[DataProvider('statusTransitorios')]
    public function testStatusTransitorioPermiteExatamenteUmaSegundaTentativa(int $status): void
    {
        $transporte = new TransporteRetryPncpFake([
            new RespostaHttpPncp($status, [], ''),
            $this->respostaJson(200, $this->identidade()),
            new RespostaHttpPncp(204, [], ''),
        ]);
        $temporizador = $this->temporizador();

        $resultado = $this->observar($transporte, $temporizador);

        $this->assertSame(SituacaoObservacaoPca::ENCONTRADO, $resultado->getSituacao());
        $this->assertCount(3, $transporte->requisicoes);
        $this->assertSame([250], $temporizador->esperasMs);
    }

    public function testDuasFalhasTransitoriasDevemSerInconclusivasSemTerceiraTentativa(): void
    {
        $transporte = new TransporteRetryPncpFake([
            new RespostaHttpPncp(503, [], ''),
            new RespostaHttpPncp(504, [], ''),
        ]);
        $temporizador = $this->temporizador();

        $resultado = $this->observar($transporte, $temporizador);

        $this->assertSame(SituacaoObservacaoPca::INCONCLUSIVA, $resultado->getSituacao());
        $this->assertCount(2, $transporte->requisicoes);
        $this->assertSame([250], $temporizador->esperasMs);
    }

    public function testSomenteTimeoutCurlDeveSerRepetido(): void
    {
        $transporte = new TransporteRetryPncpFake([
            FalhaTransportePncpException::execucaoCurlFalhou(28),
            $this->respostaJson(200, $this->identidade()),
            new RespostaHttpPncp(204, [], ''),
        ]);
        $temporizador = $this->temporizador();

        $resultado = $this->observar($transporte, $temporizador);

        $this->assertSame(SituacaoObservacaoPca::ENCONTRADO, $resultado->getSituacao());
        $this->assertCount(3, $transporte->requisicoes);
        $this->assertSame([250], $temporizador->esperasMs);
    }

    public function testErroCurlPermanenteNaoDeveSerRepetido(): void
    {
        $transporte = new TransporteRetryPncpFake([
            FalhaTransportePncpException::execucaoCurlFalhou(7),
        ]);
        $temporizador = $this->temporizador();

        $resultado = $this->observar($transporte, $temporizador);

        $this->assertSame(SituacaoObservacaoPca::FALHA_SISTEMICA, $resultado->getSituacao());
        $this->assertCount(1, $transporte->requisicoes);
        $this->assertSame([], $temporizador->esperasMs);
    }

    /** @return iterable<string, array{int}> */
    public static function statusDeAutorizacao(): iterable
    {
        yield 'não autenticado' => [401];
        yield 'não autorizado' => [403];
    }

    #[DataProvider('statusDeAutorizacao')]
    public function testFalhaDeAutorizacaoNaoDeveSerRepetida(int $status): void
    {
        $transporte = new TransporteRetryPncpFake([
            new RespostaHttpPncp($status, [], ''),
        ]);
        $temporizador = $this->temporizador();

        $resultado = $this->observar($transporte, $temporizador);

        $this->assertSame(SituacaoObservacaoPca::FALHA_SISTEMICA, $resultado->getSituacao());
        $this->assertCount(1, $transporte->requisicoes);
        $this->assertSame([], $temporizador->esperasMs);
    }

    public function testSegundaTentativaRecebeSuaClassificacaoNormalQuandoNaoETransitoria(): void
    {
        $transporte = new TransporteRetryPncpFake([
            new RespostaHttpPncp(429, [], ''),
            new RespostaHttpPncp(401, [], ''),
        ]);

        $resultado = $this->observar($transporte, $this->temporizador());

        $this->assertSame(SituacaoObservacaoPca::FALHA_SISTEMICA, $resultado->getSituacao());
        $this->assertCount(2, $transporte->requisicoes);
    }

    /** @return iterable<string, array{array<string, string[]>, int, int, int}> */
    public static function valoresRetryAfter(): iterable
    {
        yield 'segundos' => [['Retry-After' => ['1']], 250, 5_000, 1_000];
        yield 'data futura' => [['Retry-After' => ['Tue, 25 Aug 2026 12:00:03 GMT']], 250, 5_000, 3_000];
        yield 'data passada' => [['Retry-After' => ['Tue, 25 Aug 2026 11:59:59 GMT']], 250, 5_000, 0];
        yield 'invalido' => [['Retry-After' => ['amanha']], 250, 5_000, 250];
        yield 'duplicado' => [['Retry-After' => ['1', '2']], 250, 5_000, 250];
        yield 'acima do teto' => [['Retry-After' => ['99']], 250, 1_000, 1_000];
        yield 'ausente' => [[], 250, 5_000, 250];
    }

    /** @param array<string, string[]> $headers */
    #[DataProvider('valoresRetryAfter')]
    public function testRetryAfterDeveSerDeterministicoELimitado(
        array $headers,
        int $esperaPadraoMs,
        int $tetoMs,
        int $esperaEsperadaMs
    ): void {
        $transporte = new TransporteRetryPncpFake([
            new RespostaHttpPncp(429, $headers, ''),
            $this->respostaJson(200, $this->identidade()),
            new RespostaHttpPncp(204, [], ''),
        ]);
        $temporizador = $this->temporizador();

        $resultado = $this->observar(
            $transporte,
            $temporizador,
            $esperaPadraoMs,
            $tetoMs
        );

        $this->assertSame(SituacaoObservacaoPca::ENCONTRADO, $resultado->getSituacao());
        $this->assertSame([$esperaEsperadaMs], $temporizador->esperasMs);
    }

    public function testEventosDeTentativaDevemPermanecerSanitizados(): void
    {
        $logger = new LoggerRetryPncpFake();
        $transporte = new TransporteRetryPncpFake([
            new RespostaHttpPncp(429, ['Retry-After' => ['invalido-secreto']], 'corpo-secreto'),
            new RespostaHttpPncp(503, [], 'outro-segredo'),
        ]);

        $resultado = $this->observar($transporte, $this->temporizador(), logger: $logger);

        $serializado = json_encode($logger->registros, JSON_THROW_ON_ERROR);
        $this->assertSame(SituacaoObservacaoPca::INCONCLUSIVA, $resultado->getSituacao());
        $this->assertStringContainsString('"tentativa":1', $serializado);
        $this->assertStringContainsString('"tentativa":2', $serializado);
        $this->assertStringNotContainsString('token-ultrassecreto', $serializado);
        $this->assertStringNotContainsString('12345678000195', $serializado);
        $this->assertStringNotContainsString('/v1/orgaos/', $serializado);
        $this->assertStringNotContainsString('invalido-secreto', $serializado);
        $this->assertStringNotContainsString('corpo-secreto', $serializado);
    }

    public function testEventoDePaginaDeveConterSomenteMetadadosTecnicos(): void
    {
        $logger = new LoggerRetryPncpFake();
        $transporte = new TransporteRetryPncpFake([
            $this->respostaJson(200, $this->identidade()),
            new RespostaHttpPncp(204, [], ''),
        ]);

        $resultado = $this->observar($transporte, $this->temporizador(), logger: $logger);

        $serializado = json_encode($logger->registros, JSON_THROW_ON_ERROR);
        $this->assertSame(SituacaoObservacaoPca::ENCONTRADO, $resultado->getSituacao());
        $this->assertStringContainsString('"pagina":1', $serializado);
        $this->assertStringContainsString('"status_http":204', $serializado);
        $this->assertStringContainsString('OBSERVAR_PCA_PNCP', $serializado);
    }

    private function observar(
        TransporteRetryPncpFake $transporte,
        TemporizadorRetryPncpFake $temporizador,
        int $esperaPadraoMs = 250,
        int $tetoMs = 5_000,
        ?LoggerRetryPncpFake $logger = null
    ): ResultadoObservacaoPca {
        $adapter = new LeitorPncpAdapter(
            $transporte,
            new ConfiguracaoTransportePncp(AmbientePncp::HOMOLOGACAO, 1_024, 1_048_576),
            new ConfiguracaoLeituraPncp(2, 32, 5, $esperaPadraoMs, $tetoMs),
            $logger ?? new LoggerRetryPncpFake(),
            $temporizador
        );

        return $adapter->observarPcaConhecido(
            new SessaoPncpAutenticada('token-ultrassecreto'),
            new Cnpj('12.345.678/0001-95'),
            UnidadeExternaPublicacao::consolidada(
                new AnoExecucao((int) date('Y')),
                new Uasg('123456', 'UASG de teste')
            ),
            new SequencialPca(77)
        );
    }

    private function temporizador(): TemporizadorRetryPncpFake
    {
        return new TemporizadorRetryPncpFake(
            new DateTimeImmutable('2026-08-25 12:00:00', new DateTimeZone('UTC'))
        );
    }

    /** @return array<string, mixed> */
    private function identidade(): array
    {
        return [
            'cnpj' => '12345678000195',
            'anoPca' => (int) date('Y'),
            'codigoUnidade' => '123456',
            'sequencialPca' => 77,
        ];
    }

    private function respostaJson(int $status, mixed $corpo): RespostaHttpPncp
    {
        return new RespostaHttpPncp($status, [], json_encode($corpo, JSON_THROW_ON_ERROR));
    }
}

final class TransporteRetryPncpFake implements TransporteHttpPncp
{
    /** @var RequisicaoHttpPncp[] */
    public array $requisicoes = [];

    /** @param array<int, RespostaHttpPncp|FalhaTransportePncpException> $resultados */
    public function __construct(private array $resultados) {}

    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        $this->requisicoes[] = $requisicao;
        $resultado = array_shift($this->resultados)
            ?? throw new \RuntimeException('Resultado fake nao configurado.');

        if ($resultado instanceof FalhaTransportePncpException) {
            throw $resultado;
        }

        return $resultado;
    }
}

final class TemporizadorRetryPncpFake implements TemporizadorPncp
{
    /** @var int[] */
    public array $esperasMs = [];

    public function __construct(private readonly DateTimeImmutable $agora) {}

    public function agoraUtc(): DateTimeImmutable
    {
        return $this->agora;
    }

    public function aguardarMilissegundos(int $milissegundos): void
    {
        $this->esperasMs[] = $milissegundos;
    }
}

final class LoggerRetryPncpFake extends AbstractLogger
{
    /** @var array<int, array{message: string, context: array<string, mixed>}> */
    public array $registros = [];

    /** @param array<string, mixed> $context */
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->registros[] = ['message' => (string) $message, 'context' => $context];
    }
}
