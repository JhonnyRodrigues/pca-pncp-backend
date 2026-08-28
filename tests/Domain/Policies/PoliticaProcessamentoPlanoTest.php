<?php

declare(strict_types=1);

namespace Tests\Domain\Policies;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\Entities\Demanda;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;
use Semae\Pca\Domain\Enums\SituacaoDemanda;
use Semae\Pca\Domain\Exceptions\ConflitoEstadoPublicacaoException;
use Semae\Pca\Domain\Policies\PoliticaProcessamentoPlano;
use Semae\Pca\Domain\Policies\ResultadoDecisaoProcessamentoPlano;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\DecimalExato;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\PcaObservado;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;

class PoliticaProcessamentoPlanoTest extends TestCase
{
    /** @regra RN-PUB-001 @regra RN-PUB-005 */
    public function testDevePublicarFotografiaNaoVaziaQuandoPcaEstaAusenteIndependentementeDaVersao(): void
    {
        $resultado = $this->politica()->decidir(
            [$this->criarPlano([1001, 1002], sequencialVersao: 2)],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente()
        );

        $this->assertSame(DecisaoProcessamentoPlano::PUBLICAR, $resultado->getDecisao());
        $this->assertSame([1001, 1002], $resultado->getDemandasAIncluir());
        $this->assertSame([], $resultado->getDemandasAExcluir());
    }

    /** @regra RN-PUB-007 */
    public function testDevePublicarNovamenteDepoisDeExclusao(): void
    {
        $resultado = $this->politica()->decidir(
            [$this->criarPlano([1001])],
            EstadoOperacionalPublicacao::excluido(new SequencialPca(55)),
            PcaObservado::ausente()
        );

        $this->assertSame(DecisaoProcessamentoPlano::PUBLICAR, $resultado->getDecisao());
    }

    /** @regra RN-PUB-001 @regra RN-PUB-008 */
    public function testDevePublicarQuandoAusenciaExternaDefinitivaSuperaVinculoAnterior(): void
    {
        $resultado = $this->politica()->decidir(
            [$this->criarPlano([1001])],
            EstadoOperacionalPublicacao::publicado(new SequencialPca(55)),
            PcaObservado::ausente()
        );

        $this->assertSame(DecisaoProcessamentoPlano::PUBLICAR, $resultado->getDecisao());
        $this->assertSame([1001], $resultado->getDemandasAIncluir());
    }

    /** @regra RN-PUB-002 @regra RN-PUB-006 */
    public function testDeveRetificarSomenteAsDiferencasDePertencimento(): void
    {
        $sequencial = new SequencialPca(77);
        $resultado = $this->politica()->decidir(
            [$this->criarPlano([1001, 1003])],
            EstadoOperacionalPublicacao::publicado($sequencial),
            PcaObservado::ativo($sequencial, [1001, 1002])
        );

        $this->assertSame(DecisaoProcessamentoPlano::RETIFICAR, $resultado->getDecisao());
        $this->assertSame([1003], $resultado->getDemandasAIncluir());
        $this->assertSame([1002], $resultado->getDemandasAExcluir());
    }

    /** @regra RN-PUB-003 @regra RN-PLA-005 */
    public function testDeveExcluirPcaAtivoQuandoFotografiaEstaVazia(): void
    {
        $sequencial = new SequencialPca(77);
        $resultado = $this->politica()->decidir(
            [$this->criarPlano([])],
            EstadoOperacionalPublicacao::publicado($sequencial),
            PcaObservado::ativo($sequencial, [1001])
        );

        $this->assertSame(DecisaoProcessamentoPlano::EXCLUIR_PCA, $resultado->getDecisao());
        $this->assertSame([], $resultado->getDemandasAIncluir());
        $this->assertSame([], $resultado->getDemandasAExcluir());
    }

    /** @regra RN-PUB-004 */
    public function testDeveDecidirNenhumaAcaoQuandoPcaAtivoEstaConvergente(): void
    {
        $sequencial = new SequencialPca(77);
        $resultado = $this->politica()->decidir(
            [$this->criarPlano([1002, 1001])],
            EstadoOperacionalPublicacao::publicado($sequencial),
            PcaObservado::ativo($sequencial, [1001, 1002])
        );

        $this->assertSame(DecisaoProcessamentoPlano::NENHUMA_ACAO, $resultado->getDecisao());
    }

    /** @regra RN-PUB-004 */
    public function testDeveDecidirNenhumaAcaoQuandoFotografiaEPcaEstaoInativos(): void
    {
        $resultado = $this->politica()->decidir(
            [$this->criarPlano([])],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente()
        );

        $this->assertSame(DecisaoProcessamentoPlano::NENHUMA_ACAO, $resultado->getDecisao());
    }

    /** @regra RN-PUB-005 @regra RN-PLA-007 */
    public function testVersaoEConfirmacaoNaoDeterminamDecisao(): void
    {
        $semConfirmacao = $this->criarPlano([1001], 1, null);
        $confirmado = $this->criarPlano([1001], 3, new DateTimeImmutable('2026-07-20'));

        $primeiro = $this->politica()->decidir(
            [$semConfirmacao],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente()
        );
        $segundo = $this->politica()->decidir(
            [$confirmado],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente()
        );

        $this->assertSame($primeiro->getDecisao(), $segundo->getDecisao());
        $this->assertSame(DecisaoProcessamentoPlano::PUBLICAR, $segundo->getDecisao());
    }

    /** @regra RN-PUB-008 */
    public function testDeveBloquearPcaAtivoSemVinculoOperacionalCoerente(): void
    {
        $this->expectException(ConflitoEstadoPublicacaoException::class);

        $this->politica()->decidir(
            [$this->criarPlano([1001])],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ativo(new SequencialPca(77), [1001])
        );
    }

    /** @regra RN-PUB-003 */
    public function testDeveRejeitarAusenciaDePlanosEmVezDeDecidirExclusao(): void
    {
        $sequencial = new SequencialPca(77);
        $this->expectException(InvalidArgumentException::class);

        $this->politica()->decidir(
            [],
            EstadoOperacionalPublicacao::publicado($sequencial),
            PcaObservado::ativo($sequencial, [1001])
        );
    }

    /** @regra RN-PUB-001 @regra RN-PUB-002 @regra RN-PUB-003 @regra RN-PUB-004 */
    public function testDeveRejeitarElementoQueNaoSejaPlano(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->politica()->decidir(
            [new \stdClass()],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente()
        );
    }

    /** @regra RN-PUB-008 */
    public function testDeveBloquearSequencialObservadoDivergente(): void
    {
        $this->expectException(ConflitoEstadoPublicacaoException::class);

        $this->politica()->decidir(
            [$this->criarPlano([1001])],
            EstadoOperacionalPublicacao::publicado(new SequencialPca(77)),
            PcaObservado::ativo(new SequencialPca(78), [1001])
        );
    }

    /** @regra RN-PUB-006 */
    public function testDeveCombinarFotografiasDeMultiplosPlanos(): void
    {
        $resultado = $this->politica()->decidir(
            [
                $this->criarPlano([1002], identidadeUo: 'uo-102'),
                $this->criarPlano([1001], identidadeUo: 'uo-101'),
            ],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente()
        );

        $this->assertSame([1001, 1002], $resultado->getDemandasAIncluir());
    }

    /** @regra RN-DEM-002 */
    public function testDeveRejeitarIdentidadesDuplicadasEntrePlanos(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->politica()->decidir(
            [
                $this->criarPlano([1001], identidadeUo: 'uo-101'),
                $this->criarPlano([1001], identidadeUo: 'uo-102'),
            ],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente()
        );
    }

    /** @regra RN-PUB-001 */
    public function testResultadoPublicarDeveExigirDemandaAIncluir(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ResultadoDecisaoProcessamentoPlano::publicar([]);
    }

    /** @regra RN-PUB-002 */
    public function testResultadoRetificarDeveExigirAlgumaDiferenca(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ResultadoDecisaoProcessamentoPlano::retificar([], []);
    }

    /** @regra RN-PUB-002 @regra RN-PUB-006 */
    public function testResultadoRetificarDeveRejeitarIdentidadeEmInclusaoEExclusao(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ResultadoDecisaoProcessamentoPlano::retificar([1001], [1001]);
    }

    /** @regra RN-DEM-002 @regra RN-PUB-001 */
    public function testResultadoPublicarDeveRejeitarIdentidadesDuplicadas(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ResultadoDecisaoProcessamentoPlano::publicar([1001, 1001]);
    }

    /** @regra RN-DEM-002 */
    public function testResultadoDeveRejeitarIdentidadeSemTipoInteiro(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ResultadoDecisaoProcessamentoPlano::publicar(['1001']);
    }

    /** @regra RN-PUB-001 */
    public function testResultadoDeveOrdenarIdentidadesDeFormaDeterministica(): void
    {
        $resultado = ResultadoDecisaoProcessamentoPlano::publicar([1002, 1001]);

        $this->assertSame([1001, 1002], $resultado->getDemandasAIncluir());
    }

    /** @decisao DI-009 */
    public function testObservacaoDeveRejeitarIdentidadesDuplicadas(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PcaObservado::ativo(new SequencialPca(77), [1001, 1001]);
    }

    /** @decisao DI-009 */
    public function testObservacaoDeveRejeitarIdentidadeSemTipoInteiro(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PcaObservado::ativo(new SequencialPca(77), ['1001']);
    }

    private function politica(): PoliticaProcessamentoPlano
    {
        return new PoliticaProcessamentoPlano();
    }

    /** @param int[] $idsDemandas */
    private function criarPlano(
        array $idsDemandas,
        int $sequencialVersao = 1,
        ?DateTimeImmutable $confirmacao = null,
        string $identidadeUo = 'uo-101'
    ): Plano {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidade = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria($identidadeUo),
            new Lotacao(101, 'Diretoria Administrativa'),
            new Uasg('928344', 'SEMAE Piracicaba'),
            'Diretoria Administrativa'
        );
        $demandas = array_map(
            fn (int $id): Demanda => $this->criarDemanda($id, $unidade, $ano),
            $idsDemandas
        );

        return new Plano(
            new IdentidadePlano($unidade, $ano),
            $sequencialVersao,
            new DateTimeImmutable('2026-07-16'),
            $demandas,
            $confirmacao
        );
    }

    private function criarDemanda(
        int $id,
        UnidadeOrcamentaria $unidade,
        AnoExecucao $ano
    ): Demanda {
        return new Demanda(
            $id,
            SituacaoDemanda::HOMOLOGADA,
            159,
            $unidade,
            $ano,
            new DateTimeImmutable($ano->getValor() . '-03-15'),
            1,
            'ITEM ' . $id,
            'UNIDADE',
            1,
            new DecimalExato('100.00'),
            new DecimalExato('100.00'),
            2,
            (string) $id,
            '98',
            'INFORMÁTICA',
            'N'
        );
    }
}
