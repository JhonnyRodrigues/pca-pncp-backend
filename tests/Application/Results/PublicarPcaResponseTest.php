<?php

declare(strict_types=1);

namespace Tests\Application\Results;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\SituacaoPublicacaoPca;
use Semae\Pca\Application\Results\PublicarPcaResponse;
use Semae\Pca\Application\Results\ResultadoProcessamentoUnidade;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class PublicarPcaResponseTest extends TestCase
{
    public function testCorrelacaoDeveSerOpacaNaoVaziaEPreservadaSemNormalizacao(): void
    {
        $correlacao = new CorrelacaoExecucao('  execucao-ABC/123  ');

        $this->assertSame('  execucao-ABC/123  ', $correlacao->getValor());

        $this->expectException(InvalidArgumentException::class);
        new CorrelacaoExecucao('   ');
    }

    public function testSolicitacaoInvalidaDevePossuirConstrucaoValidaComInvariantesProprias(): void
    {
        $falha = new FalhaSegura('ENTRADA_INVALIDA', 'Entrada inválida.');
        $correlacao = new CorrelacaoExecucao('corr-001');

        $response = PublicarPcaResponse::solicitacaoInvalida($falha, $correlacao);

        $this->assertSame(SituacaoPublicacaoPca::SOLICITACAO_INVALIDA, $response->getSituacao());
        $this->assertSame($falha, $response->getFalhaGlobal());
        $this->assertSame([], $response->getResultadosUnitarios());
        $this->assertSame(0, $response->getQuantidadePlanejadas());
        $this->assertSame(0, $response->getQuantidadeVisitadas());
        $this->assertSame(0, $response->getQuantidadeNaoProcessadas());
        $this->assertSame($correlacao, $response->getCorrelacao());
    }

    public function testConcluidaDeveAceitarExecucaoSemUnidades(): void
    {
        $correlacao = new CorrelacaoExecucao('corr-002');

        $response = PublicarPcaResponse::concluida([], 0, 0, $correlacao);

        $this->assertSame(SituacaoPublicacaoPca::CONCLUIDA, $response->getSituacao());
        $this->assertNull($response->getFalhaGlobal());
        $this->assertSame(0, $response->getQuantidadeNaoProcessadas());
        $this->assertSame($correlacao, $response->getCorrelacao());
    }

    public function testConcluidaDeveRejeitarResultadoComPendencia(): void
    {
        $resultado = ResultadoProcessamentoUnidade::bloqueado(
            $this->criarUnidade('uo-101', '928344'),
            null,
            null,
            0,
            0,
            new FalhaSegura('BLOQUEADA', 'Unidade bloqueada.')
        );

        $this->expectException(InvalidArgumentException::class);

        PublicarPcaResponse::concluida(
            [$resultado],
            1,
            1,
            new CorrelacaoExecucao('corr-003')
        );
    }

    public function testConcluidaComPendenciasDeveExigirAoMenosUmaPendencia(): void
    {
        $resultado = ResultadoProcessamentoUnidade::semAlteracao(
            $this->criarUnidade('uo-101', '928344')
        );

        $this->expectException(InvalidArgumentException::class);

        PublicarPcaResponse::concluidaComPendencias(
            [$resultado],
            1,
            1,
            new CorrelacaoExecucao('corr-004')
        );
    }

    public function testInterrompidaDevePreservarResultadosContagensEFalha(): void
    {
        $resultado = ResultadoProcessamentoUnidade::semAlteracao(
            $this->criarUnidade('uo-101', '928344')
        );
        $falha = new FalhaSegura('PNCP_INDISPONIVEL', 'PNCP indisponível.');
        $correlacao = new CorrelacaoExecucao('corr-005');

        $response = PublicarPcaResponse::interrompida(
            [$resultado],
            $falha,
            3,
            2,
            $correlacao
        );

        $this->assertSame(SituacaoPublicacaoPca::INTERROMPIDA, $response->getSituacao());
        $this->assertSame([$resultado], $response->getResultadosUnitarios());
        $this->assertSame($falha, $response->getFalhaGlobal());
        $this->assertSame(3, $response->getQuantidadePlanejadas());
        $this->assertSame(2, $response->getQuantidadeVisitadas());
        $this->assertSame(1, $response->getQuantidadeNaoProcessadas());
        $this->assertSame($correlacao, $response->getCorrelacao());
    }

    public function testResponseDeveRejeitarResultadosDuplicados(): void
    {
        $unidade = $this->criarUnidade('uo-101', '928344');

        $this->expectException(InvalidArgumentException::class);

        PublicarPcaResponse::concluida(
            [
                ResultadoProcessamentoUnidade::semAlteracao($unidade),
                ResultadoProcessamentoUnidade::semAlteracao($unidade),
            ],
            2,
            2,
            new CorrelacaoExecucao('corr-006')
        );
    }

    public function testResponseDeveRejeitarVisitadasAcimaDasPlanejadas(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PublicarPcaResponse::interrompida(
            [],
            new FalhaSegura('FALHA', 'Falha sistêmica.'),
            1,
            2,
            new CorrelacaoExecucao('corr-007')
        );
    }

    private function criarUnidade(string $idUo, string $uasg): UnidadeExternaPublicacao
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidade = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria($idUo),
            new Lotacao(101, 'Diretoria Administrativa'),
            new Uasg($uasg, 'UASG da UO'),
            'Diretoria Administrativa'
        );

        return UnidadeExternaPublicacao::individual(new IdentidadePlano($unidade, $ano));
    }
}
