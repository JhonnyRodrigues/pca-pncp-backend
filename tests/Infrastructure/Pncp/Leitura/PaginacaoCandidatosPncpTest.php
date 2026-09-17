<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Pncp\Leitura;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Semae\Pca\Application\Enums\SituacaoBuscaCandidatosPca;
use Semae\Pca\Application\Results\ResultadoBuscaCandidatosPca;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
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
use Semae\Pca\Infrastructure\Pncp\Leitura\TemporizadorSistemaPncp;

final class PaginacaoCandidatosPncpTest extends TestCase
{
    #[DataProvider('ambientesPncp')]
    public function testStatus404AposPaginaValidaEncerraListaDeUnidades(
        AmbientePncp $ambiente
    ): void
    {
        $transporte = new TransporteCandidatosPncpFake([
            $this->respostaJson([
                $this->candidato('999001', 1),
                $this->candidato('999002', 2),
            ]),
            new RespostaHttpPncp(404, [], '{"message":"texto ignorado"}'),
        ]);

        $resultado = $this->localizar($transporte, 2, 5, $ambiente);

        $this->assertSame(SituacaoBuscaCandidatosPca::CONCLUIDA, $resultado->getSituacao());
        $this->assertSame([], $resultado->getCandidatos());
        $this->assertCount(2, $transporte->requisicoes);
    }

    public function testStatus400NaPrimeiraPaginaPermaneceInconclusivo(): void
    {
        $transporte = new TransporteCandidatosPncpFake([
            new RespostaHttpPncp(400, [], '{"message":"texto ignorado"}'),
        ]);

        $resultado = $this->localizar($transporte, 2, 5);

        $this->assertSame(SituacaoBuscaCandidatosPca::INCONCLUSIVA, $resultado->getSituacao());
        $this->assertSame([], $resultado->getCandidatos());
    }

    #[DataProvider('ambientesPncp')]
    public function testStatus404NaPrimeiraPaginaExigeTresSinaisParaComprovarAusencia(
        AmbientePncp $ambiente
    ): void
    {
        $transporte = new TransporteCandidatosPncpFake([
            new RespostaHttpPncp(404, [], '{"message":"mensagem deliberadamente ignorada"}'),
            $this->respostaJson([
                $this->unidadeCadastrada('999001'),
                $this->unidadeCadastrada('123456'),
            ]),
            new RespostaHttpPncp(404, [], '{"message":"outro texto ignorado"}'),
        ]);

        $resultado = $this->localizar($transporte, 2, 5, $ambiente);

        $this->assertSame(SituacaoBuscaCandidatosPca::CONCLUIDA, $resultado->getSituacao());
        $this->assertSame([], $resultado->getCandidatos());
        $this->assertCount(3, $transporte->requisicoes);
        $this->assertSame(
            '/v1/orgaos/12345678000195/unidades',
            $transporte->requisicoes[1]->getCaminho()
        );
        $this->assertSame(
            '/v1/orgaos/12345678000195/pca/' . date('Y') . '/consolidado',
            $transporte->requisicoes[2]->getCaminho()
        );
    }

    /** @return iterable<string, array{array<int, array<string, mixed>>}> */
    public static function cadastrosDeUnidadeConflitantes(): iterable
    {
        yield 'UASG ausente' => [[
            self::unidadeCadastradaEstatica('999001'),
        ]];
        yield 'UASG duplicada' => [[
            self::unidadeCadastradaEstatica('123456'),
            self::unidadeCadastradaEstatica('123456'),
        ]];
        yield 'CNPJ divergente' => [[
            self::unidadeCadastradaEstatica('123456', 'J3ABB3LZ000146'),
        ]];
    }

    /** @param array<int, array<string, mixed>> $unidades */
    #[DataProvider('cadastrosDeUnidadeConflitantes')]
    public function testCadastroDoOrgaoDivergenteDeveConflitar(array $unidades): void
    {
        $transporte = new TransporteCandidatosPncpFake([
            new RespostaHttpPncp(404, [], ''),
            $this->respostaJson($unidades),
        ]);

        $resultado = $this->localizar($transporte, 2, 5);

        $this->assertSame(SituacaoBuscaCandidatosPca::CONFLITO, $resultado->getSituacao());
        $this->assertSame([], $resultado->getCandidatos());
        $this->assertCount(2, $transporte->requisicoes);
    }

    public function testCadastroDoOrgaoMalformadoDeveManterBuscaInconclusiva(): void
    {
        $transporte = new TransporteCandidatosPncpFake([
            new RespostaHttpPncp(404, [], ''),
            new RespostaHttpPncp(200, [], '{'),
        ]);

        $resultado = $this->localizar($transporte, 2, 5);

        $this->assertSame(SituacaoBuscaCandidatosPca::INCONCLUSIVA, $resultado->getSituacao());
        $this->assertCount(2, $transporte->requisicoes);
    }

    public function testConsolidadoAnualDiferenteDe404NaoComprovaAusencia(): void
    {
        $transporte = new TransporteCandidatosPncpFake([
            new RespostaHttpPncp(404, [], ''),
            $this->respostaJson([$this->unidadeCadastrada('123456')]),
            $this->respostaJson(['cnpj' => '12345678000195']),
        ]);

        $resultado = $this->localizar($transporte, 2, 5);

        $this->assertSame(SituacaoBuscaCandidatosPca::INCONCLUSIVA, $resultado->getSituacao());
        $this->assertCount(3, $transporte->requisicoes);
    }

    public function testFalhaDeTransporteNaComprovacaoDeveSerSistemica(): void
    {
        $transporte = new TransporteCandidatosPncpFake([
            new RespostaHttpPncp(404, [], ''),
            $this->respostaJson([$this->unidadeCadastrada('123456')]),
            FalhaTransportePncpException::tlsNaoConfirmado(),
        ]);

        $resultado = $this->localizar($transporte, 2, 5);

        $this->assertSame(SituacaoBuscaCandidatosPca::FALHA_SISTEMICA, $resultado->getSituacao());
        $this->assertCount(3, $transporte->requisicoes);
    }

    /** @return iterable<string, array{AmbientePncp}> */
    public static function ambientesPncp(): iterable
    {
        yield 'homologacao' => [AmbientePncp::HOMOLOGACAO];
        yield 'producao' => [AmbientePncp::PRODUCAO];
    }

    public function testDevePercorrerListaCompletaAntesDeConcluirQueNaoHaCandidato(): void
    {
        $transporte = new TransporteCandidatosPncpFake([
            $this->respostaJson([
                $this->candidato('999001', 1),
                $this->candidato('999002', 2),
            ]),
            $this->respostaJson([$this->candidato('999003', 3)]),
        ]);

        $resultado = $this->localizar($transporte, 2, 5);

        $this->assertSame(SituacaoBuscaCandidatosPca::CONCLUIDA, $resultado->getSituacao());
        $this->assertSame([], $resultado->getCandidatos());
        $this->assertCount(2, $transporte->requisicoes);
        $this->assertStringEndsWith(
            '/consolidado/unidades?pagina=1&tamanhoPagina=2',
            $transporte->requisicoes[0]->getCaminho()
        );
        $this->assertStringEndsWith(
            '/consolidado/unidades?pagina=2&tamanhoPagina=2',
            $transporte->requisicoes[1]->getCaminho()
        );
    }

    public function testDeveCarregarItensSomenteDepoisDeConcluirListaComCandidatoUnico(): void
    {
        $transporte = new TransporteCandidatosPncpFake([
            $this->respostaJson([
                $this->candidato('123456', 77),
                $this->candidato('999001', 1),
            ]),
            $this->respostaJson([$this->candidato('999002', 2)]),
            $this->respostaJson($this->itens([1001, 1002], 77)),
            $this->respostaJson($this->itens([1004], 77)),
        ]);

        $resultado = $this->localizar($transporte, 2, 5);

        $this->assertSame(SituacaoBuscaCandidatosPca::CONCLUIDA, $resultado->getSituacao());
        $this->assertCount(1, $resultado->getCandidatos());
        $this->assertSame(77, $resultado->getCandidatos()[0]->getSequencialPca()?->getValor());
        $this->assertSame([1001, 1002, 1004], $resultado->getCandidatos()[0]->getIdsDemandas());
        $this->assertCount(4, $transporte->requisicoes);
        $this->assertStringContainsString('/consolidado/unidades?pagina=2', $transporte->requisicoes[1]->getCaminho());
        $this->assertStringEndsWith('/77/itens?pagina=1&tamanhoPagina=2', $transporte->requisicoes[2]->getCaminho());
        $this->assertStringEndsWith('/77/itens?pagina=2&tamanhoPagina=2', $transporte->requisicoes[3]->getCaminho());
    }

    public function testCandidatoUnicoPodeRepresentarPcaAtivoSemItensDepoisDaConsulta(): void
    {
        $transporte = new TransporteCandidatosPncpFake([
            $this->respostaJson([$this->candidato('123456', 77)]),
            new RespostaHttpPncp(204, [], ''),
        ]);

        $resultado = $this->localizar($transporte, 2, 5);

        $this->assertSame(SituacaoBuscaCandidatosPca::CONCLUIDA, $resultado->getSituacao());
        $this->assertCount(1, $resultado->getCandidatos());
        $this->assertSame([], $resultado->getCandidatos()[0]->getIdsDemandas());
        $this->assertCount(2, $transporte->requisicoes);
    }

    public function testMultiplosCandidatosDaMesmaUasgDevemConflitarSemConsultarItens(): void
    {
        $transporte = new TransporteCandidatosPncpFake([
            $this->respostaJson([
                $this->candidato('123456', 77),
                $this->candidato('123456', 78),
            ]),
            new RespostaHttpPncp(204, [], ''),
        ]);

        $resultado = $this->localizar($transporte, 2, 5);

        $this->assertSame(SituacaoBuscaCandidatosPca::CONFLITO, $resultado->getSituacao());
        $this->assertSame([], $resultado->getCandidatos());
        $this->assertCount(2, $transporte->requisicoes);
    }

    public function testCandidatoDuplicadoEntrePaginasDeveConflitar(): void
    {
        $transporte = new TransporteCandidatosPncpFake([
            $this->respostaJson([
                $this->candidato('123456', 77),
                $this->candidato('999001', 1),
            ]),
            $this->respostaJson([
                $this->candidato('123456', 77),
            ]),
        ]);

        $resultado = $this->localizar($transporte, 2, 5);

        $this->assertSame(SituacaoBuscaCandidatosPca::CONFLITO, $resultado->getSituacao());
        $this->assertCount(2, $transporte->requisicoes);
    }

    public function testPaginaDeCandidatosRepetidaEReordenadaDeveConflitar(): void
    {
        $transporte = new TransporteCandidatosPncpFake([
            $this->respostaJson([
                $this->candidato('999001', 1),
                $this->candidato('999002', 2),
            ]),
            $this->respostaJson([
                $this->candidato('999002', 2),
                $this->candidato('999001', 1),
            ]),
        ]);

        $resultado = $this->localizar($transporte, 2, 5);

        $this->assertSame(SituacaoBuscaCandidatosPca::CONFLITO, $resultado->getSituacao());
        $this->assertCount(2, $transporte->requisicoes);
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function candidatosInvalidosOuDivergentes(): iterable
    {
        yield 'campo ausente' => [[
            'cnpj' => '12345678000195',
            'anoPca' => (int) date('Y'),
            'codigoUnidade' => '123456',
        ]];
        yield 'sequencial com tipo invalido' => [[
            'cnpj' => '12345678000195',
            'anoPca' => (int) date('Y'),
            'codigoUnidade' => '123456',
            'sequencialPca' => '77',
        ]];
    }

    /** @param array<string, mixed> $candidato */
    #[DataProvider('candidatosInvalidosOuDivergentes')]
    public function testCandidatoMalformadoDeveTornarBuscaInconclusiva(array $candidato): void
    {
        $transporte = new TransporteCandidatosPncpFake([
            $this->respostaJson([$candidato]),
        ]);

        $resultado = $this->localizar($transporte, 2, 5);

        $this->assertSame(SituacaoBuscaCandidatosPca::INCONCLUSIVA, $resultado->getSituacao());
        $this->assertSame([], $resultado->getCandidatos());
    }

    /** @return iterable<string, array{string, string|int}> */
    public static function contextoDivergente(): iterable
    {
        yield 'CNPJ' => ['cnpj', 'J3ABB3LZ000146'];
        yield 'ano' => ['anoPca', (int) date('Y') + 1];
    }

    #[DataProvider('contextoDivergente')]
    public function testContextoValidoMasDivergenteDeveConflitar(
        string $campo,
        string|int $valor
    ): void {
        $transporte = new TransporteCandidatosPncpFake([
            $this->respostaJson([
                $this->candidato('123456', 77, [$campo => $valor]),
            ]),
        ]);

        $resultado = $this->localizar($transporte, 2, 5);

        $this->assertSame(SituacaoBuscaCandidatosPca::CONFLITO, $resultado->getSituacao());
    }

    public function testLimiteDePaginasDeveSerInconclusivoSemConsultaAdicional(): void
    {
        $transporte = new TransporteCandidatosPncpFake([
            $this->respostaJson([
                $this->candidato('999001', 1),
                $this->candidato('999002', 2),
            ]),
            $this->respostaJson([
                $this->candidato('999003', 3),
                $this->candidato('999004', 4),
            ]),
        ]);

        $resultado = $this->localizar($transporte, 2, 2);

        $this->assertSame(SituacaoBuscaCandidatosPca::INCONCLUSIVA, $resultado->getSituacao());
        $this->assertCount(2, $transporte->requisicoes);
    }

    public function testFalhaNosItensDoCandidatoUnicoNaoDeveFabricarCandidatoParcial(): void
    {
        $transporte = new TransporteCandidatosPncpFake([
            $this->respostaJson([$this->candidato('123456', 77)]),
            new RespostaHttpPncp(200, [], '{'),
        ]);

        $resultado = $this->localizar($transporte, 2, 5);

        $this->assertSame(SituacaoBuscaCandidatosPca::INCONCLUSIVA, $resultado->getSituacao());
        $this->assertSame([], $resultado->getCandidatos());
        $this->assertCount(2, $transporte->requisicoes);
    }

    private function localizar(
        TransporteCandidatosPncpFake $transporte,
        int $tamanhoPagina,
        int $limitePaginas,
        AmbientePncp $ambiente = AmbientePncp::HOMOLOGACAO
    ): ResultadoBuscaCandidatosPca {
        $adapter = new LeitorPncpAdapter(
            $transporte,
            new ConfiguracaoTransportePncp($ambiente, 1_024, 1_048_576),
            new ConfiguracaoLeituraPncp($tamanhoPagina, 32, $limitePaginas, 250, 5_000),
            new NullLogger(),
            new TemporizadorSistemaPncp()
        );

        return $adapter->localizarCandidatos(
            new SessaoPncpAutenticada('token-opaco'),
            new Cnpj('12.345.678/0001-95'),
            UnidadeExternaPublicacao::consolidada(
                new AnoExecucao((int) date('Y')),
                new Uasg('123456', 'UASG de teste')
            )
        );
    }

    /**
     * @param array<string, mixed> $alteracoes
     * @return array<string, mixed>
     */
    private function candidato(string $uasg, int $sequencial, array $alteracoes = []): array
    {
        return array_replace([
            'cnpj' => '12345678000195',
            'anoPca' => (int) date('Y'),
            'codigoUnidade' => $uasg,
            'sequencialPca' => $sequencial,
        ], $alteracoes);
    }

    /** @return array<string, mixed> */
    private function unidadeCadastrada(
        string $uasg,
        string $cnpj = '12345678000195'
    ): array {
        return self::unidadeCadastradaEstatica($uasg, $cnpj);
    }

    /** @return array<string, mixed> */
    private static function unidadeCadastradaEstatica(
        string $uasg,
        string $cnpj = '12345678000195'
    ): array {
        return [
            'orgao' => ['cnpj' => $cnpj],
            'codigoUnidade' => $uasg,
            'nomeUnidade' => 'Unidade de teste',
        ];
    }

    /**
     * @param int[] $ids
     * @return array<int, array<string, mixed>>
     */
    private function itens(array $ids, int $sequencial): array
    {
        return array_map(fn (int $id): array => [
            'cnpj' => '12345678000195',
            'anoPca' => (int) date('Y'),
            'codigoUnidade' => '123456',
            'sequencialPca' => $sequencial,
            'numeroItem' => $id,
        ], $ids);
    }

    private function respostaJson(mixed $corpo): RespostaHttpPncp
    {
        return new RespostaHttpPncp(200, [], json_encode($corpo, JSON_THROW_ON_ERROR));
    }
}

final class TransporteCandidatosPncpFake implements TransporteHttpPncp
{
    /** @var RequisicaoHttpPncp[] */
    public array $requisicoes = [];

    /** @param array<int, RespostaHttpPncp|\Throwable> $respostas */
    public function __construct(private array $respostas) {}

    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        $this->requisicoes[] = $requisicao;

        $resposta = array_shift($this->respostas)
            ?? throw new \RuntimeException('Resposta fake não configurada.');

        if ($resposta instanceof \Throwable) {
            throw $resposta;
        }

        return $resposta;
    }
}
