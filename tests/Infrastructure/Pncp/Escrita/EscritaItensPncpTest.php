<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Pncp\Escrita;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Semae\Pca\Application\Commands\ComandoExcluirItensPca;
use Semae\Pca\Application\Commands\ComandoInserirItensPca;
use Semae\Pca\Application\Enums\SituacaoEscritaPncp;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\Demanda;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\SituacaoDemanda;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\DecimalExato;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Semae\Pca\Infrastructure\Pncp\Autenticacao\SessaoPncpAutenticada;
use Semae\Pca\Infrastructure\Pncp\Escrita\EscritorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Semae\Pca\Infrastructure\Pncp\Http\ConfiguracaoTransportePncp;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Serializacao\SerializadorPayloadPncp;

final class EscritaItensPncpTest extends TestCase
{
    #[DataProvider('statusInclusaoAceitos')]
    public function testDeveInserirColecaoCompletaEmUmaUnicaRequisicao(int $status): void
    {
        $transporte = new TransporteItensPncpFake(new RespostaHttpPncp($status, [], ''));
        $comando = $this->comandoInsercao([
            $this->demanda(1003),
            $this->demanda(1001),
        ]);

        $resultado = $this->adapter($transporte)->inserirItens($this->sessao(), $comando);

        $this->assertSame(SituacaoEscritaPncp::ACEITA, $resultado->getSituacao());
        $this->assertNull($resultado->getFalha());
        $this->assertCount(1, $transporte->requisicoes);
        $this->assertRequisicaoJson(
            $transporte->requisicoes[0],
            'POST',
            '/v1/orgaos/12345678000195/pca/2026/77/itens',
            (new SerializadorPayloadPncp())->serializarInclusaoItens($comando)
        );
    }

    public function testNaoDeveParticionarColecaoRecebidaDaApplication(): void
    {
        $demandas = [];

        for ($id = 1; $id <= 1001; $id++) {
            $demandas[] = $this->demanda($id);
        }

        $transporte = new TransporteItensPncpFake(new RespostaHttpPncp(201, [], ''));
        $resultado = $this->adapter($transporte)->inserirItens(
            $this->sessao(),
            $this->comandoInsercao($demandas)
        );

        $this->assertSame(SituacaoEscritaPncp::ACEITA, $resultado->getSituacao());
        $this->assertCount(1, $transporte->requisicoes);
        $corpo = json_decode(
            (string) $transporte->requisicoes[0]->getCorpo(),
            true,
            64,
            JSON_THROW_ON_ERROR
        );
        $this->assertCount(1001, $corpo);
        $this->assertSame(1, $corpo[0]['numeroItem']);
        $this->assertSame(1001, $corpo[1000]['numeroItem']);
    }

    public function testDeveExcluirItensEmLoteComJustificativaIntegral(): void
    {
        $transporte = new TransporteItensPncpFake(new RespostaHttpPncp(200, [], ''));
        $comando = new ComandoExcluirItensPca(
            $this->cnpj(),
            $this->unidadeExterna(),
            new SequencialPca(77),
            [1003, 1001],
            'Exclusão por cancelamentos homologados no SGI.'
        );

        $resultado = $this->adapter($transporte)->excluirItens($this->sessao(), $comando);

        $this->assertSame(SituacaoEscritaPncp::ACEITA, $resultado->getSituacao());
        $this->assertCount(1, $transporte->requisicoes);
        $this->assertRequisicaoJson(
            $transporte->requisicoes[0],
            'DELETE',
            '/v1/orgaos/12345678000195/pca/2026/77/itens',
            (new SerializadorPayloadPncp())->serializarExclusaoItens($comando)
        );
    }

    public function testRejeicaoDaInclusaoNaoDeveGerarRetry(): void
    {
        $transporte = new TransporteItensPncpFake(new RespostaHttpPncp(422, [], '{"erro":true}'));

        $resultado = $this->adapter($transporte)->inserirItens(
            $this->sessao(),
            $this->comandoInsercao([$this->demanda(1001)])
        );

        $this->assertSame(SituacaoEscritaPncp::REJEITADA, $resultado->getSituacao());
        $this->assertSame('PNCP_INCLUSAO_ITENS_REJEITADA', $resultado->getFalha()?->getCodigo());
        $this->assertCount(1, $transporte->requisicoes);
    }

    public function testStatusTransitorioDaExclusaoDeveSerIncertoSemRetry(): void
    {
        $transporte = new TransporteItensPncpFake(new RespostaHttpPncp(503, [], 'indisponível'));
        $comando = new ComandoExcluirItensPca(
            $this->cnpj(),
            $this->unidadeExterna(),
            new SequencialPca(77),
            [1001],
            'Exclusão por cancelamento homologado no SGI.'
        );

        $resultado = $this->adapter($transporte)->excluirItens($this->sessao(), $comando);

        $this->assertSame(SituacaoEscritaPncp::RESULTADO_INCERTO, $resultado->getSituacao());
        $this->assertSame(
            'PNCP_EXCLUSAO_ITENS_RESULTADO_INCERTO',
            $resultado->getFalha()?->getCodigo()
        );
        $this->assertCount(1, $transporte->requisicoes);
    }

    /** @return array<string, array{int}> */
    public static function statusInclusaoAceitos(): array
    {
        return [
            'manual 2.5' => [200],
            'gate contratual' => [201],
        ];
    }

    private function assertRequisicaoJson(
        RequisicaoHttpPncp $requisicao,
        string $metodo,
        string $caminho,
        string $corpo
    ): void {
        $this->assertSame($metodo, $requisicao->getMetodo());
        $this->assertSame($caminho, $requisicao->getCaminho());
        $this->assertSame([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'authorization' => 'Bearer token-opaco',
        ], $requisicao->getHeaders());
        $this->assertSame(60, $requisicao->getTimeoutSegundos());
        $this->assertSame($corpo, $requisicao->getCorpo());
    }

    /** @param Demanda[] $demandas */
    private function comandoInsercao(array $demandas): ComandoInserirItensPca
    {
        return new ComandoInserirItensPca(
            $this->cnpj(),
            $this->unidadeExterna(),
            new SequencialPca(77),
            $demandas
        );
    }

    private function adapter(TransporteHttpPncp $transporte): EscritorPncpAdapter
    {
        return new EscritorPncpAdapter(
            $transporte,
            new ConfiguracaoTransportePncp(
                AmbientePncp::HOMOLOGACAO,
                10_000_000,
                1_000_000
            ),
            new SerializadorPayloadPncp(),
            new NullLogger()
        );
    }

    private function sessao(): SessaoPncpAutenticada
    {
        return new SessaoPncpAutenticada('token-opaco');
    }

    private function cnpj(): Cnpj
    {
        return new Cnpj('12.345.678/0001-95');
    }

    private function unidadeExterna(): UnidadeExternaPublicacao
    {
        return UnidadeExternaPublicacao::consolidada(
            new AnoExecucao(2026),
            new Uasg('110001', 'UASG de teste')
        );
    }

    private function demanda(int $id): Demanda
    {
        $ano = new AnoExecucao(2026);
        $unidade = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria('UO-10'),
            new Lotacao(20, 'Lotação de teste'),
            new Uasg('110001', 'UASG de teste'),
            'Unidade de teste'
        );

        return new Demanda(
            $id,
            SituacaoDemanda::HOMOLOGADA,
            159,
            $unidade,
            $ano,
            new DateTimeImmutable('2026-03-15'),
            1,
            'Item de teste',
            'UNIDADE',
            2,
            new DecimalExato('100'),
            new DecimalExato('100'),
            2,
            'ITEM-1',
            'GRUPO-1',
            'Grupo de teste',
            'N'
        );
    }
}

final class TransporteItensPncpFake implements TransporteHttpPncp
{
    /** @var RequisicaoHttpPncp[] */
    public array $requisicoes = [];

    public function __construct(private readonly RespostaHttpPncp $resposta) {}

    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        $this->requisicoes[] = $requisicao;

        return $this->resposta;
    }
}
