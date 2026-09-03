<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Pncp\Leitura;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
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
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\ConfiguracaoLeituraPncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\LeitorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Leitura\TemporizadorSistemaPncp;

final class PaginacaoItensPncpTest extends TestCase
{
    #[DataProvider('statusInconclusivoNosItens')]
    public function testStatus400Ou404NaPaginacaoDeItensPermaneceInconclusivo(
        int $status
    ): void {
        $transporte = new TransportePaginacaoPncpFake([
            $this->respostaJson($this->identidade()),
            $this->respostaJson($this->itens([1001, 1002])),
            new RespostaHttpPncp($status, [], '{"message":"texto ignorado"}'),
        ]);

        $resultado = $this->observar($transporte, 2, 5);

        $this->assertSame(SituacaoObservacaoPca::INCONCLUSIVA, $resultado->getSituacao());
        $this->assertNull($resultado->getPcaObservado());
    }

    /** @return iterable<string, array{int}> */
    public static function statusInconclusivoNosItens(): iterable
    {
        yield '400' => [400];
        yield '404' => [404];
    }

    public function testDeveLerPaginasSequenciaisAtePaginaCurtaEPreservarLacunas(): void
    {
        $transporte = new TransportePaginacaoPncpFake([
            $this->respostaJson($this->identidade()),
            $this->respostaJson($this->itens([1001, 1002])),
            $this->respostaJson($this->itens([1004])),
        ]);

        $resultado = $this->observar($transporte, 2, 5);

        $this->assertSame(SituacaoObservacaoPca::ENCONTRADO, $resultado->getSituacao());
        $this->assertSame([1001, 1002, 1004], $resultado->getPcaObservado()?->getIdsDemandas());
        $this->assertCount(3, $transporte->requisicoes);
        $this->assertStringEndsWith(
            '/itens?pagina=1&tamanhoPagina=2',
            $transporte->requisicoes[1]->getCaminho()
        );
        $this->assertStringEndsWith(
            '/itens?pagina=2&tamanhoPagina=2',
            $transporte->requisicoes[2]->getCaminho()
        );
    }

    public function testDeveEncerrarEm204SemDescartarItensAnteriores(): void
    {
        $transporte = new TransportePaginacaoPncpFake([
            $this->respostaJson($this->identidade()),
            $this->respostaJson($this->itens([1001, 1002])),
            new RespostaHttpPncp(204, [], ''),
        ]);

        $resultado = $this->observar($transporte, 2, 5);

        $this->assertSame(SituacaoObservacaoPca::ENCONTRADO, $resultado->getSituacao());
        $this->assertSame([1001, 1002], $resultado->getPcaObservado()?->getIdsDemandas());
    }

    /** @return iterable<string, array{array<int, array<string, mixed>>}> */
    public static function paginasComIdentidadesDuplicadas(): iterable
    {
        yield 'item repetido entre paginas' => [[
            self::itemEstatico(1001),
            self::itemEstatico(1003),
        ]];
        yield 'pagina repetida' => [[
            self::itemEstatico(1001),
            self::itemEstatico(1002),
        ]];
        yield 'pagina repetida reordenada' => [[
            self::itemEstatico(1002),
            self::itemEstatico(1001),
        ]];
    }

    /** @param array<int, array<string, mixed>> $segundaPagina */
    #[DataProvider('paginasComIdentidadesDuplicadas')]
    public function testDeveConflitarEmDuplicidadeOuRepeticaoDePagina(array $segundaPagina): void
    {
        $transporte = new TransportePaginacaoPncpFake([
            $this->respostaJson($this->identidade()),
            $this->respostaJson($this->itens([1001, 1002])),
            $this->respostaJson($segundaPagina),
        ]);

        $resultado = $this->observar($transporte, 2, 5);

        $this->assertSame(SituacaoObservacaoPca::CONFLITO, $resultado->getSituacao());
        $this->assertNull($resultado->getPcaObservado());
    }

    /** @return iterable<string, array{string, string|int}> */
    public static function metadadosDivergentes(): iterable
    {
        yield 'CNPJ' => ['cnpj', 'J3ABB3LZ000146'];
        yield 'ano' => ['anoPca', (int) date('Y') + 1];
        yield 'UASG' => ['codigoUnidade', '654321'];
        yield 'sequencial' => ['sequencialPca', 78];
    }

    #[DataProvider('metadadosDivergentes')]
    public function testDeveConflitarQuandoMetadadoMudaEntrePaginas(
        string $campo,
        string|int $valor
    ): void
    {
        $transporte = new TransportePaginacaoPncpFake([
            $this->respostaJson($this->identidade()),
            $this->respostaJson($this->itens([1001, 1002])),
            $this->respostaJson([
                $this->identidade(['numeroItem' => 1003, $campo => $valor]),
            ]),
        ]);

        $resultado = $this->observar($transporte, 2, 5);

        $this->assertSame(SituacaoObservacaoPca::CONFLITO, $resultado->getSituacao());
        $this->assertNull($resultado->getPcaObservado());
    }

    public function testDeveConflitarAoUltrapassarMilItens(): void
    {
        $transporte = new TransportePaginacaoPncpFake([
            $this->respostaJson($this->identidade()),
            $this->respostaJson($this->itens(range(1, 1000))),
            $this->respostaJson($this->itens([1001])),
        ]);

        $resultado = $this->observar($transporte, 1000, 3);

        $this->assertSame(SituacaoObservacaoPca::CONFLITO, $resultado->getSituacao());
        $this->assertNull($resultado->getPcaObservado());
    }

    public function testLimiteDePaginasDeveInterromperSemFabricarFotografiaParcial(): void
    {
        $transporte = new TransportePaginacaoPncpFake([
            $this->respostaJson($this->identidade()),
            $this->respostaJson($this->itens([1001, 1002])),
            $this->respostaJson($this->itens([1003, 1004])),
        ]);

        $resultado = $this->observar($transporte, 2, 2);

        $this->assertSame(SituacaoObservacaoPca::INCONCLUSIVA, $resultado->getSituacao());
        $this->assertNull($resultado->getPcaObservado());
        $this->assertCount(3, $transporte->requisicoes);
    }

    public function testPaginaMalformadaDeveSerInconclusivaSemFotografiaParcial(): void
    {
        $transporte = new TransportePaginacaoPncpFake([
            $this->respostaJson($this->identidade()),
            $this->respostaJson($this->itens([1001, 1002])),
            new RespostaHttpPncp(200, [], '{'),
        ]);

        $resultado = $this->observar($transporte, 2, 5);

        $this->assertSame(SituacaoObservacaoPca::INCONCLUSIVA, $resultado->getSituacao());
        $this->assertNull($resultado->getPcaObservado());
    }

    public function testConfiguracaoDeveExigirLimitePositivoDePaginas(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ConfiguracaoLeituraPncp(100, 32, 0, 250, 5_000);
    }

    private function observar(
        TransportePaginacaoPncpFake $transporte,
        int $tamanhoPagina,
        int $limitePaginas
    ): ResultadoObservacaoPca {
        $adapter = new LeitorPncpAdapter(
            $transporte,
            new ConfiguracaoTransportePncp(AmbientePncp::HOMOLOGACAO, 1_024, 1_048_576),
            new ConfiguracaoLeituraPncp($tamanhoPagina, 32, $limitePaginas, 250, 5_000),
            new NullLogger(),
            new TemporizadorSistemaPncp()
        );

        return $adapter->observarPcaConhecido(
            new SessaoPncpAutenticada('token-opaco'),
            new Cnpj('12.345.678/0001-95'),
            UnidadeExternaPublicacao::consolidada(
                new AnoExecucao((int) date('Y')),
                new Uasg('123456', 'UASG de teste')
            ),
            new SequencialPca(77)
        );
    }

    /** @param int[] $ids */
    private function itens(array $ids): array
    {
        return array_map(fn (int $id): array => $this->identidade(['numeroItem' => $id]), $ids);
    }

    /** @param array<string, mixed> $alteracoes */
    private function identidade(array $alteracoes = []): array
    {
        return array_replace(self::identidadeEstatica(), $alteracoes);
    }

    /** @return array<string, mixed> */
    private static function itemEstatico(int $id): array
    {
        return array_replace(self::identidadeEstatica(), ['numeroItem' => $id]);
    }

    /** @return array<string, mixed> */
    private static function identidadeEstatica(): array
    {
        return [
            'cnpj' => '12345678000195',
            'anoPca' => (int) date('Y'),
            'codigoUnidade' => '123456',
            'sequencialPca' => 77,
        ];
    }

    private function respostaJson(mixed $corpo): RespostaHttpPncp
    {
        return new RespostaHttpPncp(200, [], json_encode($corpo, JSON_THROW_ON_ERROR));
    }
}

final class TransportePaginacaoPncpFake implements TransporteHttpPncp
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
