<?php

declare(strict_types=1);

namespace Tests\Domain\Entities;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\Entities\Demanda;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;
use Semae\Pca\Domain\Exceptions\PlanoException;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Uasg;

class PlanoTest extends TestCase
{
    public function testDeveCriarPlanoHomologadoComDemandas(): void
    {
        $anoExecucao = new AnoExecucao((int) date('Y') + 1);
        $unidadeOrcamentaria = $this->criarUnidadeOrcamentaria();
        $dataHomologacao = new DateTimeImmutable('2026-07-16');
        $demanda = $this->criarDemanda(42, $unidadeOrcamentaria, $anoExecucao->getValor());
        $plano = new Plano(
            42,
            $anoExecucao,
            $unidadeOrcamentaria,
            1,
            $dataHomologacao,
            [$demanda]
        );

        $this->assertSame(42, $plano->getIdPlano());
        $this->assertSame($anoExecucao, $plano->getAnoExecucao());
        $this->assertSame($unidadeOrcamentaria, $plano->getUnidadeOrcamentaria());
        $this->assertSame(1, $plano->getSequencialVersao());
        $this->assertSame($dataHomologacao, $plano->getDataHomologacao());
        $this->assertSame([$demanda], $plano->getDemandas());
        $this->assertTrue($plano->possuiDemandas());
        $this->assertSame(1, $plano->getQuantidadeDemandas());
    }

    public function testDeveImpedirPlanoSemDemandas(): void
    {
        $this->expectException(PlanoException::class);

        new Plano(
            42,
            new AnoExecucao((int) date('Y') + 1),
            $this->criarUnidadeOrcamentaria(),
            1,
            new DateTimeImmutable('2026-07-16'),
            [] // <---
        );
    }

    public function testDevePermitirPlanoAindaNaoPublicado(): void
    {
        $plano = $this->criarPlano(dataPublicacao: null);

        $this->assertNull($plano->getDataPublicacao());
        $this->assertFalse($plano->foiPublicado());
    }

    public function testDeveReconhecerPlanoPublicado(): void
    {
        $dataPublicacao = new DateTimeImmutable('2026-07-16');

        $plano = $this->criarPlano(dataPublicacao: $dataPublicacao);

        $this->assertSame($dataPublicacao, $plano->getDataPublicacao());
        $this->assertTrue($plano->foiPublicado());
    }

    public function testDeveDecidirPublicacaoQuandoVersaoForUmENaoEstiverPublicado(): void
    {
        $plano = $this->criarPlano(
            sequencialVersao: 1,
            dataPublicacao: null
        );

        $this->assertSame(
            DecisaoProcessamentoPlano::PUBLICAR,
            $plano->decidirProcessamento()
        );
    }

    public function testDeveDecidirRetificacaoQuandoVersaoForMaiorQueUmENaoEstiverPublicado(): void
    {
        $plano = $this->criarPlano(
            sequencialVersao: 2,
            dataPublicacao: null
        );

        $this->assertSame(
            DecisaoProcessamentoPlano::RETIFICAR,
            $plano->decidirProcessamento()
        );
    }

    public function testDeveDecidirNenhumaAcaoQuandoPlanoJaEstiverPublicado(): void
    {
        $plano = $this->criarPlano(
            sequencialVersao: 2,
            dataPublicacao: new DateTimeImmutable('2026-07-16')
        );

        $this->assertSame(
            DecisaoProcessamentoPlano::NENHUMA_ACAO,
            $plano->decidirProcessamento()
        );
    }

    public function testDeveRetornarDecisaoProcessamentoPlano(): void
    {
        $plano = $this->criarPlano();

        $this->assertInstanceOf(
            DecisaoProcessamentoPlano::class,
            $plano->decidirProcessamento()
        );
    }

    public function testDeveExigirSequencialVersaoMaiorQueZero(): void
    {
        $this->expectException(PlanoException::class);

        $anoExecucao = new AnoExecucao((int) date('Y') + 1);
        $unidadeOrcamentaria = $this->criarUnidadeOrcamentaria();

        new Plano(
            42,
            $anoExecucao,
            $unidadeOrcamentaria,
            0, // <---
            new DateTimeImmutable('2026-07-16'),
            [$this->criarDemanda(42, $unidadeOrcamentaria, $anoExecucao->getValor())]
        );
    }

    public function testDeveRepresentarUmaVersaoEspecificaDoPlano(): void
    {
        $plano = $this->criarPlano(sequencialVersao: 2);

        $this->assertSame(2, $plano->getSequencialVersao());
    }

    public function testDeveImpedirDemandasDeOutraUnidadeOrcamentaria(): void
    {
        $this->expectException(PlanoException::class);

        $anoExecucao = new AnoExecucao((int) date('Y') + 1);
        $unidadeOrcamentaria = $this->criarUnidadeOrcamentaria();
        $outraUnidadeOrcamentaria = new UnidadeOrcamentaria(
            new Lotacao(102, 'Procuradoria Juridica'),
            new Uasg('928344', 'SEMAE Piracicaba'),
            'Procuradoria Juridica'
        );

        new Plano(
            42,
            $anoExecucao,
            $unidadeOrcamentaria,
            1,
            new DateTimeImmutable('2026-06-23'),
            [$this->criarDemanda(42, $outraUnidadeOrcamentaria, $anoExecucao->getValor())] // <---
        );
    }

    public function testDeveImpedirDemandasDeOutroAnoExecucao(): void
    {
        $this->expectException(PlanoException::class);

        $anoExecucao = new AnoExecucao((int) date('Y'));
        $unidadeOrcamentaria = $this->criarUnidadeOrcamentaria();

        new Plano(
            42,
            $anoExecucao,
            $unidadeOrcamentaria,
            1,
            new DateTimeImmutable('2026-06-23'),
            [$this->criarDemanda(42, $unidadeOrcamentaria, $anoExecucao->getValor() + 1)] // <---
        );
    }

    public function testDeveImpedirDemandasDeOutroPlano(): void
    {
        $this->expectException(PlanoException::class);

        $anoExecucao = new AnoExecucao((int) date('Y') + 1);
        $unidadeOrcamentaria = $this->criarUnidadeOrcamentaria();

        new Plano(
            42,
            $anoExecucao,
            $unidadeOrcamentaria,
            1,
            new DateTimeImmutable('2026-06-23'),
            [$this->criarDemanda(43, $unidadeOrcamentaria, $anoExecucao->getValor())] // <---
        );
    }

    public function testNaoDeveAnteciparDecisoesDePublicacaoOuRetificacao(): void
    {
        $plano = $this->criarPlano();

        $this->assertFalse(method_exists($plano, 'devePublicar'));
        $this->assertFalse(method_exists($plano, 'deveRetificar'));
    }

    private function criarPlano(int $sequencialVersao = 1, ?DateTimeImmutable $dataPublicacao = null): Plano {
        $anoExecucao = new AnoExecucao((int) date('Y') + 1);
        $unidadeOrcamentaria = $this->criarUnidadeOrcamentaria();

        return new Plano(
            42,
            $anoExecucao,
            $unidadeOrcamentaria,
            $sequencialVersao,
            new DateTimeImmutable('2026-06-23'),
            [$this->criarDemanda(42, $unidadeOrcamentaria, $anoExecucao->getValor())],
            $dataPublicacao
        );
    }

    private function criarDemanda(int $idPlano, UnidadeOrcamentaria $unidadeOrcamentaria, int $anoExecucao): Demanda {
        return new Demanda(
            1001,
            2,
            159,
            $idPlano,
            $unidadeOrcamentaria,
            new DateTimeImmutable($anoExecucao . '-03-15'),
            1,
            'MICROCOMPUTADOR DESKTOP TIPO PC PARA APLICACOES AVANCADAS',
            'UNIDADE',
            2,
            7100.00,
            7100.00,
            2,
            '98.004.000004',
            '98',
            'INFORMATICA',
            'N'
        );
    }

    private function criarUnidadeOrcamentaria(): UnidadeOrcamentaria {
        return new UnidadeOrcamentaria(
            new Lotacao(101, 'Diretoria Geral Administrativa e Financeira'),
            new Uasg('928344', 'SEMAE Piracicaba'),
            'Diretoria Geral Administrativa e Financeira'
        );
    }
}
