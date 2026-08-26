<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Pncp\Escrita;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Semae\Pca\Application\Commands\ComandoExcluirPca;
use Semae\Pca\Application\Enums\SituacaoEscritaPncp;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
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

final class ExclusaoPcaPncpTest extends TestCase
{
    public function testDeveExcluirPcaComJustificativaIntegralEmUmaUnicaRequisicao(): void
    {
        $transporte = new TransporteExclusaoPcaFake(new RespostaHttpPncp(200, [], ''));
        $comando = $this->comando(
            'Exclusão integral porque todas as demandas foram canceladas no SGI.'
        );

        $resultado = $this->adapter($transporte)->excluirPca($this->sessao(), $comando);

        $this->assertSame(SituacaoEscritaPncp::ACEITA, $resultado->getSituacao());
        $this->assertNull($resultado->getFalha());
        $this->assertCount(1, $transporte->requisicoes);
        $requisicao = $transporte->requisicoes[0];
        $this->assertSame('DELETE', $requisicao->getMetodo());
        $this->assertSame(
            '/v1/orgaos/12345678000195/pca/2026/77',
            $requisicao->getCaminho()
        );
        $this->assertSame([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'authorization' => 'Bearer token-opaco',
        ], $requisicao->getHeaders());
        $this->assertSame(60, $requisicao->getTimeoutSegundos());
        $this->assertSame(
            (new SerializadorPayloadPncp())->serializarExclusaoPca($comando),
            $requisicao->getCorpo()
        );
    }

    #[DataProvider('respostasNaoAceitas')]
    public function testDeveClassificarRespostaNaoAceitaSemRetry(
        int $status,
        SituacaoEscritaPncp $situacao,
        string $codigo
    ): void {
        $transporte = new TransporteExclusaoPcaFake(
            new RespostaHttpPncp($status, [], '{"mensagem":"não registrar"}')
        );

        $resultado = $this->adapter($transporte)->excluirPca(
            $this->sessao(),
            $this->comando('Exclusão integral homologada no SGI.')
        );

        $this->assertSame($situacao, $resultado->getSituacao());
        $this->assertSame($codigo, $resultado->getFalha()?->getCodigo());
        $this->assertCount(1, $transporte->requisicoes);
    }

    /** @return array<string, array{int, SituacaoEscritaPncp, string}> */
    public static function respostasNaoAceitas(): array
    {
        return [
            'rejeitada' => [
                422,
                SituacaoEscritaPncp::REJEITADA,
                'PNCP_EXCLUSAO_PCA_REJEITADA',
            ],
            'não autorizada' => [
                401,
                SituacaoEscritaPncp::FALHA_SISTEMICA,
                'PNCP_EXCLUSAO_PCA_NAO_AUTORIZADA',
            ],
            'transitória' => [
                503,
                SituacaoEscritaPncp::RESULTADO_INCERTO,
                'PNCP_EXCLUSAO_PCA_RESULTADO_INCERTO',
            ],
            'status de publicação' => [
                201,
                SituacaoEscritaPncp::RESULTADO_INCERTO,
                'PNCP_EXCLUSAO_PCA_RESULTADO_INCERTO',
            ],
        ];
    }

    private function comando(string $justificativa): ComandoExcluirPca
    {
        return new ComandoExcluirPca(
            new Cnpj('12.345.678/0001-95'),
            UnidadeExternaPublicacao::consolidada(
                new AnoExecucao(2026),
                new Uasg('110001', 'UASG de teste')
            ),
            new SequencialPca(77),
            $justificativa
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
}

final class TransporteExclusaoPcaFake implements TransporteHttpPncp
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
