<?php

declare(strict_types=1);

namespace Tests\Application\Results;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\SituacaoProcessamentoUnidade;
use Semae\Pca\Application\Results\ResultadoProcessamentoUnidade;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class ResultadoProcessamentoUnidadeTest extends TestCase
{
    public function testConcluidoDevePreservarDecisaoSequencialEContagensSemFalha(): void
    {
        $unidade = $this->criarUnidade();
        $sequencial = new SequencialPca(77);

        $resultado = ResultadoProcessamentoUnidade::concluido(
            $unidade,
            DecisaoProcessamentoPlano::RETIFICAR,
            $sequencial,
            2,
            1,
            [1001, 1002],
            [1003]
        );

        $this->assertSame(SituacaoProcessamentoUnidade::CONCLUIDO, $resultado->getSituacao());
        $this->assertSame($unidade, $resultado->getUnidadeExterna());
        $this->assertSame(DecisaoProcessamentoPlano::RETIFICAR, $resultado->getDecisao());
        $this->assertSame($sequencial, $resultado->getSequencialPca());
        $this->assertSame(2, $resultado->getQuantidadeInclusoes());
        $this->assertSame(1, $resultado->getQuantidadeExclusoes());
        $this->assertSame([1001, 1002], $resultado->getNumerosItensIncluidos());
        $this->assertSame([1003], $resultado->getNumerosItensExcluidos());
        $this->assertNull($resultado->getFalha());
    }

    public function testSemAlteracaoDeveFixarDecisaoEContagensSemExigirSequencial(): void
    {
        $resultado = ResultadoProcessamentoUnidade::semAlteracao($this->criarUnidade());

        $this->assertSame(
            SituacaoProcessamentoUnidade::SEM_ALTERACAO,
            $resultado->getSituacao()
        );
        $this->assertSame(DecisaoProcessamentoPlano::NENHUMA_ACAO, $resultado->getDecisao());
        $this->assertNull($resultado->getSequencialPca());
        $this->assertSame(0, $resultado->getQuantidadeInclusoes());
        $this->assertSame(0, $resultado->getQuantidadeExclusoes());
        $this->assertSame([], $resultado->getNumerosItensIncluidos());
        $this->assertSame([], $resultado->getNumerosItensExcluidos());
        $this->assertNull($resultado->getFalha());
    }

    #[DataProvider('situacoesComFalhaSegura')]
    public function testPendenciaUnitariaDevePreservarFalhaSemFabricarDecisao(
        SituacaoProcessamentoUnidade $situacao,
        string $factory
    ): void {
        $falha = new FalhaSegura('UNIDADE_PENDENTE', 'Unidade requer tratamento consciente.');

        $resultado = ResultadoProcessamentoUnidade::{$factory}(
            $this->criarUnidade(),
            null,
            null,
            0,
            0,
            $falha
        );

        $this->assertSame($situacao, $resultado->getSituacao());
        $this->assertNull($resultado->getDecisao());
        $this->assertNull($resultado->getSequencialPca());
        $this->assertSame(0, $resultado->getQuantidadeInclusoes());
        $this->assertSame(0, $resultado->getQuantidadeExclusoes());
        $this->assertSame([], $resultado->getNumerosItensIncluidos());
        $this->assertSame([], $resultado->getNumerosItensExcluidos());
        $this->assertSame($falha, $resultado->getFalha());
    }

    public function testPublicacaoConcluidaDeveExigirInclusoesSemExclusoes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Contagens incompatíveis com a decisão PUBLICAR.');

        ResultadoProcessamentoUnidade::concluido(
            $this->criarUnidade(),
            DecisaoProcessamentoPlano::PUBLICAR,
            new SequencialPca(77),
            1,
            1
        );
    }

    public function testRetificacaoDeveExigirAoMenosUmaDiferenca(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Contagens incompatíveis com a decisão RETIFICAR.');

        ResultadoProcessamentoUnidade::concluido(
            $this->criarUnidade(),
            DecisaoProcessamentoPlano::RETIFICAR,
            new SequencialPca(77),
            0,
            0
        );
    }

    public function testRetificacaoConcluidaDeveExigirListasCompativeisComContagens(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ResultadoProcessamentoUnidade::concluido(
            $this->criarUnidade(),
            DecisaoProcessamentoPlano::RETIFICAR,
            new SequencialPca(77),
            2,
            0,
            [1001],
            []
        );
    }

    #[DataProvider('retificacoesComUmaOperacao')]
    public function testRetificacaoConcluidaComUmaOperacaoDeveExporSomenteSeusIds(
        array $incluidos,
        array $excluidos
    ): void {
        $resultado = ResultadoProcessamentoUnidade::concluido(
            $this->criarUnidade(),
            DecisaoProcessamentoPlano::RETIFICAR,
            new SequencialPca(77),
            count($incluidos),
            count($excluidos),
            $incluidos,
            $excluidos
        );

        $this->assertSame($incluidos, $resultado->getNumerosItensIncluidos());
        $this->assertSame($excluidos, $resultado->getNumerosItensExcluidos());
    }

    /** @return array<string, array{int[], int[]}> */
    public static function retificacoesComUmaOperacao(): array
    {
        return [
            'somente inclusão' => [[1002], []],
            'somente exclusão' => [[], [1001]],
        ];
    }

    public function testPublicacaoNaoDeveAceitarListasPublicasDeItens(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ResultadoProcessamentoUnidade::concluido(
            $this->criarUnidade(),
            DecisaoProcessamentoPlano::PUBLICAR,
            new SequencialPca(77),
            1,
            0,
            [1001],
            []
        );
    }

    public function testListasPublicasDevemSerArraysIndexadosParaJson(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ResultadoProcessamentoUnidade::concluido(
            $this->criarUnidade(),
            DecisaoProcessamentoPlano::RETIFICAR,
            new SequencialPca(77),
            1,
            0,
            ['id' => 1001],
            []
        );
    }

    public function testExclusaoIntegralNaoDeveFabricarContagensDeItens(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Contagens incompatíveis com a decisão EXCLUIR_PCA.');

        ResultadoProcessamentoUnidade::concluido(
            $this->criarUnidade(),
            DecisaoProcessamentoPlano::EXCLUIR_PCA,
            new SequencialPca(77),
            0,
            1
        );
    }

    public function testConcluidoNaoDeveAceitarNenhumaAcao(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CONCLUIDO exige uma decisão com escrita externa.');

        ResultadoProcessamentoUnidade::concluido(
            $this->criarUnidade(),
            DecisaoProcessamentoPlano::NENHUMA_ACAO,
            new SequencialPca(77),
            0,
            0
        );
    }

    public function testRetificacaoConhecidaDeveExigirSequencialMesmoQuandoBloqueada(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Decisão sobre PCA existente exige sequencial conhecido.');

        ResultadoProcessamentoUnidade::bloqueado(
            $this->criarUnidade(),
            DecisaoProcessamentoPlano::RETIFICAR,
            null,
            1,
            0,
            new FalhaSegura('CONFLITO', 'Unidade bloqueada.')
        );
    }

    public function testContagensNaoPodemSerNegativas(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Contagens do processamento não podem ser negativas.');

        ResultadoProcessamentoUnidade::bloqueado(
            $this->criarUnidade(),
            null,
            null,
            -1,
            0,
            new FalhaSegura('CONFLITO', 'Unidade bloqueada.')
        );
    }

    /** @return array<string, array{SituacaoProcessamentoUnidade, string}> */
    public static function situacoesComFalhaSegura(): array
    {
        return [
            'falha reconciliável' => [
                SituacaoProcessamentoUnidade::FALHA_RECONCILIAVEL,
                'falhaReconciliavel',
            ],
            'bloqueado' => [SituacaoProcessamentoUnidade::BLOQUEADO, 'bloqueado'],
        ];
    }

    private function criarUnidade(): UnidadeExternaPublicacao
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidade = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria('uo-101'),
            new Lotacao(101, 'Diretoria Administrativa'),
            new Uasg('928344', 'SEMAE Piracicaba'),
            'Diretoria Administrativa'
        );

        return UnidadeExternaPublicacao::individual(new IdentidadePlano($unidade, $ano));
    }
}
