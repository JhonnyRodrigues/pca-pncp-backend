<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Pncp\Leitura;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Enums\SituacaoBuscaCandidatosPca;
use Semae\Pca\Application\Enums\SituacaoObservacaoPca;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Semae\Pca\Infrastructure\Pncp\Autenticacao\SessaoPncpAutenticada;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Semae\Pca\Infrastructure\Pncp\Http\ConfiguracaoTransportePncp;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\ConfiguracaoLeituraPncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\LeitorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Leitura\TemporizadorSistemaPncp;
use Stringable;

final class LeitorPncpAdapterTest extends TestCase
{
    public function testDeveObservarPcaConhecidoEmUmaPaginaCurtaSemExporToken(): void
    {
        $transporte = new TransporteLeituraPncpFake([
            $this->respostaJson(200, $this->identidade()),
            $this->respostaJson(200, [
                $this->identidade(['numeroItem' => 1002]),
                $this->identidade(['numeroItem' => 1001]),
            ]),
        ]);
        $logger = new LoggerLeituraPncpFake();
        $adapter = $this->adapter($transporte, $logger, 3);

        $resultado = $adapter->observarPcaConhecido(
            new SessaoPncpAutenticada('token-ultrassecreto'),
            $this->cnpj(),
            $this->unidade(),
            new SequencialPca(77)
        );

        $this->assertSame(SituacaoObservacaoPca::ENCONTRADO, $resultado->getSituacao());
        $this->assertSame([1001, 1002], $resultado->getPcaObservado()?->getIdsDemandas());
        $this->assertSame(77, $resultado->getPcaObservado()?->getSequencialPca()?->getValor());
        $this->assertCount(2, $transporte->requisicoes);
        $this->assertSame(
            '/v1/orgaos/12345678000195/pca/' . date('Y') . '/77/consolidado',
            $transporte->requisicoes[0]->getCaminho()
        );
        $this->assertSame(
            '/v1/orgaos/12345678000195/pca/' . date('Y') . '/77/itens?pagina=1&tamanhoPagina=3',
            $transporte->requisicoes[1]->getCaminho()
        );

        foreach ($transporte->requisicoes as $requisicao) {
            $this->assertSame('GET', $requisicao->getMetodo());
            $this->assertSame('Bearer token-ultrassecreto', $requisicao->getHeaders()['authorization']);
            $this->assertSame('application/json', $requisicao->getHeaders()['accept']);
            $this->assertSame(30, $requisicao->getTimeoutSegundos());
            $this->assertNull($requisicao->getCorpo());
        }

        $serializado = json_encode($logger->registros, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('token-ultrassecreto', $serializado);
        $this->assertStringNotContainsString('12345678000195', $serializado);
        $this->assertStringNotContainsString('/v1/orgaos/', $serializado);
    }

    public function testDeveReconhecerPcaAtivoSemItensDepoisDoConsolidadoEncontrado(): void
    {
        $adapter = $this->adapter(new TransporteLeituraPncpFake([
            $this->respostaJson(200, $this->identidade()),
            new RespostaHttpPncp(204, [], ''),
        ]));

        $resultado = $this->observar($adapter);

        $this->assertSame(SituacaoObservacaoPca::ENCONTRADO, $resultado->getSituacao());
        $this->assertSame([], $resultado->getPcaObservado()?->getIdsDemandas());
    }

    public function testPaginaCheiaDeItensDeveSerInconclusivaSemAnteciparPaginacao(): void
    {
        $adapter = $this->adapter(new TransporteLeituraPncpFake([
            $this->respostaJson(200, $this->identidade()),
            $this->respostaJson(200, [
                $this->identidade(['numeroItem' => 1001]),
                $this->identidade(['numeroItem' => 1002]),
            ]),
        ]), pageSize: 2, maxPages: 1);

        $this->assertSame(
            SituacaoObservacaoPca::INCONCLUSIVA,
            $this->observar($adapter)->getSituacao()
        );
    }

    public function testDeveConcluirBuscaSemCandidatosQuandoPrimeiraPaginaTermina(): void
    {
        $transporte = new TransporteLeituraPncpFake([new RespostaHttpPncp(204, [], '')]);
        $adapter = $this->adapter($transporte);

        $resultado = $adapter->localizarCandidatos(
            new SessaoPncpAutenticada('token-opaco'),
            $this->cnpj(),
            $this->unidade()
        );

        $this->assertSame(SituacaoBuscaCandidatosPca::CONCLUIDA, $resultado->getSituacao());
        $this->assertSame([], $resultado->getCandidatos());
        $this->assertSame(
            '/v1/orgaos/12345678000195/pca/' . date('Y')
                . '/consolidado/unidades?pagina=1&tamanhoPagina=3',
            $transporte->requisicoes[0]->getCaminho()
        );
    }

    public function testListaJsonVaziaTambemDeveConcluirBuscaSemCandidatos(): void
    {
        $adapter = $this->adapter(new TransporteLeituraPncpFake([
            $this->respostaJson(200, []),
        ]));

        $resultado = $adapter->localizarCandidatos(
            new SessaoPncpAutenticada('token-opaco'),
            $this->cnpj(),
            $this->unidade()
        );

        $this->assertSame(SituacaoBuscaCandidatosPca::CONCLUIDA, $resultado->getSituacao());
        $this->assertSame([], $resultado->getCandidatos());
    }

    public function testPaginaCheiaDeCandidatosDeveSerInconclusivaMesmoSemCorrespondencia(): void
    {
        $transporte = new TransporteLeituraPncpFake([
            $this->respostaJson(200, [
                $this->identidade(['codigoUnidade' => '999001', 'sequencialPca' => 1]),
                $this->identidade(['codigoUnidade' => '999002', 'sequencialPca' => 2]),
            ]),
        ]);
        $adapter = $this->adapter($transporte, pageSize: 2, maxPages: 1);

        $resultado = $adapter->localizarCandidatos(
            new SessaoPncpAutenticada('token-opaco'),
            $this->cnpj(),
            $this->unidade()
        );

        $this->assertSame(SituacaoBuscaCandidatosPca::INCONCLUSIVA, $resultado->getSituacao());
    }

    /** @return iterable<string, array{RespostaHttpPncp}> */
    public static function respostasInvalidas(): iterable
    {
        yield 'json malformado' => [new RespostaHttpPncp(200, [], '{')];
        yield 'objeto ausente' => [new RespostaHttpPncp(200, [], '[]')];
        yield 'campo ausente' => [new RespostaHttpPncp(200, [], '{"cnpj":"12345678000195"}')];
        yield 'tipo invalido' => [new RespostaHttpPncp(
            200,
            [],
            '{"cnpj":"12345678000195","anoPca":"2026","codigoUnidade":"123456","sequencialPca":77}'
        )];
    }

    #[DataProvider('respostasInvalidas')]
    public function testRespostaInvalidaDeveSerInconclusiva(RespostaHttpPncp $resposta): void
    {
        $resultado = $this->observar($this->adapter(new TransporteLeituraPncpFake([$resposta])));

        $this->assertSame(SituacaoObservacaoPca::INCONCLUSIVA, $resultado->getSituacao());
    }

    public function testIdentidadeValidaMasDivergenteDeveSerConflito(): void
    {
        $resposta = $this->respostaJson(200, $this->identidade(['sequencialPca' => 88]));

        $resultado = $this->observar($this->adapter(new TransporteLeituraPncpFake([$resposta])));

        $this->assertSame(SituacaoObservacaoPca::CONFLITO, $resultado->getSituacao());
    }

    public function testSessaoIncompativelDeveSerFalhaSistemicaSemRequisicao(): void
    {
        $transporte = new TransporteLeituraPncpFake([]);
        $adapter = $this->adapter($transporte);

        $resultado = $adapter->observarPcaConhecido(
            new SessaoPncpFake(),
            $this->cnpj(),
            $this->unidade(),
            new SequencialPca(77)
        );

        $this->assertSame(SituacaoObservacaoPca::FALHA_SISTEMICA, $resultado->getSituacao());
        $this->assertCount(0, $transporte->requisicoes);
    }

    public function testFalhaNaoDeveRegistrarTokenCorpoOuIdentidadeConsultada(): void
    {
        $logger = new LoggerLeituraPncpFake();
        $adapter = $this->adapter(
            new TransporteLeituraPncpFake([
                new RespostaHttpPncp(200, [], '{"segredo":"token-ultrassecreto"'),
            ]),
            $logger
        );

        $resultado = $adapter->observarPcaConhecido(
            new SessaoPncpAutenticada('token-ultrassecreto'),
            $this->cnpj(),
            $this->unidade(),
            new SequencialPca(77)
        );

        $serializado = json_encode([$resultado->getFalha(), $logger->registros], JSON_THROW_ON_ERROR);
        $this->assertSame(SituacaoObservacaoPca::INCONCLUSIVA, $resultado->getSituacao());
        $this->assertStringNotContainsString('token-ultrassecreto', $serializado);
        $this->assertStringNotContainsString('12345678000195', $serializado);
        $this->assertStringNotContainsString('/v1/orgaos/', $serializado);
    }

    public function testConfiguracaoDeLeituraDeveExigirValoresPositivos(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ConfiguracaoLeituraPncp(0, 32, 5, 250, 5_000);
    }

    public function testConfiguracaoDeLeituraDeveExigirProfundidadePositiva(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ConfiguracaoLeituraPncp(100, 0, 5, 250, 5_000);
    }

    public function testConfiguracaoDeLeituraDeveExporEsperaETetoDeRetry(): void
    {
        $configuracao = new ConfiguracaoLeituraPncp(100, 32, 5, 250, 5_000);

        $this->assertSame(250, $configuracao->getEsperaRetryPadraoMs());
        $this->assertSame(5_000, $configuracao->getTetoRetryAfterMs());
    }

    /** @return iterable<string, array{int, int}> */
    public static function configuracoesRetryInvalidas(): iterable
    {
        yield 'espera nula' => [0, 5_000];
        yield 'teto nulo' => [250, 0];
        yield 'espera acima do teto' => [5_001, 5_000];
    }

    #[DataProvider('configuracoesRetryInvalidas')]
    public function testConfiguracaoDeLeituraDeveRejeitarRetryInvalido(
        int $esperaPadraoMs,
        int $tetoMs
    ): void {
        $this->expectException(\InvalidArgumentException::class);

        new ConfiguracaoLeituraPncp(100, 32, 5, $esperaPadraoMs, $tetoMs);
    }

    private function observar(LeitorPncpAdapter $adapter): \Semae\Pca\Application\Results\ResultadoObservacaoPca
    {
        return $adapter->observarPcaConhecido(
            new SessaoPncpAutenticada('token-opaco'),
            $this->cnpj(),
            $this->unidade(),
            new SequencialPca(77)
        );
    }

    private function adapter(
        TransporteLeituraPncpFake $transporte,
        ?\Psr\Log\LoggerInterface $logger = null,
        int $pageSize = 3,
        int $maxPages = 5
    ): LeitorPncpAdapter {
        return new LeitorPncpAdapter(
            $transporte,
            new ConfiguracaoTransportePncp(AmbientePncp::HOMOLOGACAO, 1_024, 4_096),
            new ConfiguracaoLeituraPncp($pageSize, 32, $maxPages, 250, 5_000),
            $logger ?? new NullLogger(),
            new TemporizadorSistemaPncp()
        );
    }

    private function cnpj(): Cnpj
    {
        return new Cnpj('12.345.678/0001-95');
    }

    private function unidade(): UnidadeExternaPublicacao
    {
        return UnidadeExternaPublicacao::consolidada(
            new AnoExecucao((int) date('Y')),
            new Uasg('123456', 'UASG de teste')
        );
    }

    /** @param array<string, mixed> $alteracoes */
    private function identidade(array $alteracoes = []): array
    {
        return array_replace([
            'cnpj' => '12345678000195',
            'anoPca' => (int) date('Y'),
            'codigoUnidade' => '123456',
            'sequencialPca' => 77,
        ], $alteracoes);
    }

    private function respostaJson(int $status, mixed $corpo): RespostaHttpPncp
    {
        return new RespostaHttpPncp($status, [], json_encode($corpo, JSON_THROW_ON_ERROR));
    }
}

final class TransporteLeituraPncpFake implements TransporteHttpPncp
{
    /** @var RequisicaoHttpPncp[] */
    public array $requisicoes = [];

    /** @param RespostaHttpPncp[] $respostas */
    public function __construct(private array $respostas) {}

    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        $this->requisicoes[] = $requisicao;

        return array_shift($this->respostas)
            ?? throw new \RuntimeException('Resposta fake não configurada.');
    }
}

final class SessaoPncpFake implements SessaoPncp
{
}

final class LoggerLeituraPncpFake extends AbstractLogger
{
    /** @var array<int, array{level: mixed, message: string, context: array<string, mixed>}> */
    public array $registros = [];

    /** @param array<string, mixed> $context */
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->registros[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}
