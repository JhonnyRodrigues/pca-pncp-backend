<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Pncp\Leitura;

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

final class AusenciaDefinitivaPncpTest extends TestCase
{
    /** @return iterable<string, array{string, AmbientePncp}> */
    public static function corposDeAusenciaNaoAutoritativos(): iterable
    {
        yield 'homologacao sem corpo' => ['', AmbientePncp::HOMOLOGACAO];
        yield 'producao sem corpo' => ['', AmbientePncp::PRODUCAO];
        yield 'homologacao com mensagem textual divergente' => [
            '{"message":"texto externo nao autoritativo"}',
            AmbientePncp::HOMOLOGACAO,
        ];
        yield 'producao com mensagem textual divergente' => [
            '{"message":"outro texto externo"}',
            AmbientePncp::PRODUCAO,
        ];
    }

    #[DataProvider('corposDeAusenciaNaoAutoritativos')]
    public function testStatus400NoConsolidadoExigeListaCompletaSemUasg(
        string $corpo,
        AmbientePncp $ambiente
    ): void {
        $transporte = new TransporteAusenciaPncpFake([
            new RespostaHttpPncp(400, [], $corpo),
            $this->respostaJson([
                $this->candidato('999001', 1),
                $this->candidato('999002', 2),
            ]),
            new RespostaHttpPncp(404, [], '{"message":"CNPJ invalido"}'),
        ]);

        $resultado = $this->observar($transporte, 5, $ambiente);

        $this->assertSame(SituacaoObservacaoPca::AUSENCIA_DEFINITIVA, $resultado->getSituacao());
        $this->assertCount(3, $transporte->requisicoes);
    }

    public function testStatus400NoConsolidadoComMesmaUasgNaListaDeveConflitar(): void
    {
        $transporte = new TransporteAusenciaPncpFake([
            new RespostaHttpPncp(400, [], ''),
            $this->respostaJson([$this->candidato('123456', 88)]),
        ]);

        $resultado = $this->observar($transporte);

        $this->assertSame(SituacaoObservacaoPca::CONFLITO, $resultado->getSituacao());
    }

    public function testStatus400NoConsolidadoComListaInconclusivaNaoFabricaAusencia(): void
    {
        $transporte = new TransporteAusenciaPncpFake([
            new RespostaHttpPncp(400, [], ''),
            new RespostaHttpPncp(400, [], ''),
        ]);

        $resultado = $this->observar($transporte);

        $this->assertSame(SituacaoObservacaoPca::INCONCLUSIVA, $resultado->getSituacao());
    }

    public function testStatus404NoConsolidadoConhecidoPermaneceInconclusivo(): void
    {
        $transporte = new TransporteAusenciaPncpFake([
            new RespostaHttpPncp(404, [], ''),
        ]);

        $resultado = $this->observar($transporte);

        $this->assertSame(SituacaoObservacaoPca::INCONCLUSIVA, $resultado->getSituacao());
        $this->assertCount(1, $transporte->requisicoes);
    }

    #[DataProvider('ambientes')]
    public function testAusenciaDoUltimoPcaDoAnoPodeSerComprovadaAposLista404(
        AmbientePncp $ambiente
    ): void {
        $transporte = new TransporteAusenciaPncpFake([
            new RespostaHttpPncp(400, [], '{"message":"sequencial ausente"}'),
            new RespostaHttpPncp(404, [], '{"message":"CNPJ invalido"}'),
            $this->respostaJson([[
                'orgao' => ['cnpj' => '12345678000195'],
                'codigoUnidade' => '123456',
                'nomeUnidade' => 'UASG de teste',
            ]]),
            new RespostaHttpPncp(404, [], '{"message":"nenhum PCA no ano"}'),
        ]);

        $resultado = $this->observar($transporte, 5, $ambiente);

        $this->assertSame(SituacaoObservacaoPca::AUSENCIA_DEFINITIVA, $resultado->getSituacao());
        $this->assertCount(4, $transporte->requisicoes);
        $this->assertStringEndsWith('/unidades', $transporte->requisicoes[2]->getCaminho());
        $this->assertStringEndsWith(
            '/pca/' . date('Y') . '/consolidado',
            $transporte->requisicoes[3]->getCaminho()
        );
    }

    /** @return iterable<string, array{AmbientePncp}> */
    public static function ambientes(): iterable
    {
        yield 'homologacao' => [AmbientePncp::HOMOLOGACAO];
        yield 'producao' => [AmbientePncp::PRODUCAO];
    }

    public function testAusenciaDiretaEListaSemCandidatosComprovamAusenciaDefinitiva(): void
    {
        $transporte = new TransporteAusenciaPncpFake([
            new RespostaHttpPncp(204, [], ''),
            new RespostaHttpPncp(204, [], ''),
        ]);

        $resultado = $this->observar($transporte);

        $this->assertSame(SituacaoObservacaoPca::AUSENCIA_DEFINITIVA, $resultado->getSituacao());
        $this->assertCount(2, $transporte->requisicoes);
        $this->assertStringEndsWith('/77/consolidado', $transporte->requisicoes[0]->getCaminho());
        $this->assertStringEndsWith(
            '/consolidado/unidades?pagina=1&tamanhoPagina=2',
            $transporte->requisicoes[1]->getCaminho()
        );
    }

    public function testDevePercorrerListaCompletaComOutrasUasgsAntesDeComprovarAusencia(): void
    {
        $transporte = new TransporteAusenciaPncpFake([
            new RespostaHttpPncp(204, [], ''),
            $this->respostaJson([
                $this->candidato('999001', 1),
                $this->candidato('999002', 2),
            ]),
            $this->respostaJson([$this->candidato('999003', 3)]),
        ]);

        $resultado = $this->observar($transporte);

        $this->assertSame(SituacaoObservacaoPca::AUSENCIA_DEFINITIVA, $resultado->getSituacao());
        $this->assertCount(3, $transporte->requisicoes);
        $this->assertStringContainsString('pagina=2', $transporte->requisicoes[2]->getCaminho());
    }

    public function testMesmoSequencialEncontradoNaListaDeveSerConflitoSemConsultarItens(): void
    {
        $transporte = new TransporteAusenciaPncpFake([
            new RespostaHttpPncp(204, [], ''),
            $this->respostaJson([$this->candidato('123456', 77)]),
        ]);

        $resultado = $this->observar($transporte);

        $this->assertSame(SituacaoObservacaoPca::CONFLITO, $resultado->getSituacao());
        $this->assertCount(2, $transporte->requisicoes);
        $this->assertStringNotContainsString('/itens', $transporte->requisicoes[1]->getCaminho());
    }

    public function testOutroSequencialDaMesmaUasgDeveSerConflitoSemConsultarItens(): void
    {
        $transporte = new TransporteAusenciaPncpFake([
            new RespostaHttpPncp(204, [], ''),
            $this->respostaJson([$this->candidato('123456', 88)]),
        ]);

        $resultado = $this->observar($transporte);

        $this->assertSame(SituacaoObservacaoPca::CONFLITO, $resultado->getSituacao());
        $this->assertCount(2, $transporte->requisicoes);
        $this->assertStringNotContainsString('/itens', $transporte->requisicoes[1]->getCaminho());
    }

    public function testListaMalformadaDeveSerInconclusivaSemFabricarAusencia(): void
    {
        $transporte = new TransporteAusenciaPncpFake([
            new RespostaHttpPncp(204, [], ''),
            new RespostaHttpPncp(200, [], '{'),
        ]);

        $resultado = $this->observar($transporte);

        $this->assertSame(SituacaoObservacaoPca::INCONCLUSIVA, $resultado->getSituacao());
        $this->assertCount(2, $transporte->requisicoes);
    }

    public function testFalhaPermanenteNaListaDeveSerFalhaSistemicaSemFabricarAusencia(): void
    {
        $transporte = new TransporteAusenciaPncpFake([
            new RespostaHttpPncp(204, [], ''),
            new RespostaHttpPncp(500, [], ''),
        ]);

        $resultado = $this->observar($transporte);

        $this->assertSame(SituacaoObservacaoPca::FALHA_SISTEMICA, $resultado->getSituacao());
        $this->assertCount(2, $transporte->requisicoes);
    }

    public function testLimiteDePaginasDeveSerInconclusivoSemFabricarAusencia(): void
    {
        $transporte = new TransporteAusenciaPncpFake([
            new RespostaHttpPncp(204, [], ''),
            $this->respostaJson([
                $this->candidato('999001', 1),
                $this->candidato('999002', 2),
            ]),
        ]);

        $resultado = $this->observar($transporte, 1);

        $this->assertSame(SituacaoObservacaoPca::INCONCLUSIVA, $resultado->getSituacao());
        $this->assertCount(2, $transporte->requisicoes);
    }

    private function observar(
        TransporteAusenciaPncpFake $transporte,
        int $limitePaginas = 5,
        AmbientePncp $ambiente = AmbientePncp::HOMOLOGACAO
    ): ResultadoObservacaoPca {
        $adapter = new LeitorPncpAdapter(
            $transporte,
            new ConfiguracaoTransportePncp($ambiente, 1_024, 1_048_576),
            new ConfiguracaoLeituraPncp(2, 32, $limitePaginas, 250, 5_000),
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

    /** @return array<string, mixed> */
    private function candidato(string $uasg, int $sequencial): array
    {
        return [
            'cnpj' => '12345678000195',
            'anoPca' => (int) date('Y'),
            'codigoUnidade' => $uasg,
            'sequencialPca' => $sequencial,
        ];
    }

    private function respostaJson(mixed $corpo): RespostaHttpPncp
    {
        return new RespostaHttpPncp(200, [], json_encode($corpo, JSON_THROW_ON_ERROR));
    }
}

final class TransporteAusenciaPncpFake implements TransporteHttpPncp
{
    /** @var RequisicaoHttpPncp[] */
    public array $requisicoes = [];

    /** @param RespostaHttpPncp[] $respostas */
    public function __construct(private array $respostas) {}

    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        $this->requisicoes[] = $requisicao;

        return array_shift($this->respostas)
            ?? throw new \RuntimeException('Resposta fake nao configurada.');
    }
}
