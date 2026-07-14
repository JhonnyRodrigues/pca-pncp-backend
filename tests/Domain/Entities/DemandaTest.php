<?php

declare(strict_types=1);

namespace Tests\Domain\Entities;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\Entities\Demanda;
use Semae\Pca\Domain\ValueObjects\UnidadeOrcamentaria;

class DemandaTest extends TestCase
{
    public function testDeveRepresentarNecessidadeDeContratacaoComDadosDocumentados(): void
    {
        $unidadeOrcamentaria = new UnidadeOrcamentaria(101, 'Diretoria Administrativa');
        $dataDesejada = new DateTimeImmutable('2027-03-15');

        $demanda = new Demanda(
            10,
            $unidadeOrcamentaria,
            20,
            30,
            'Aquisicao de materiais hidraulicos',
            5,
            1500.75,
            $dataDesejada,
            'Material',
            'homologada',
            77
        );

        $this->assertSame(10, $demanda->id());
        $this->assertSame($unidadeOrcamentaria, $demanda->unidadeOrcamentaria());
        $this->assertSame(20, $demanda->idPlano());
        $this->assertSame(30, $demanda->idUnidadeRequisitante());
        $this->assertSame('Aquisicao de materiais hidraulicos', $demanda->descricao());
        $this->assertSame(5, $demanda->quantidade());
        $this->assertSame(1500.75, $demanda->valor());
        $this->assertSame($dataDesejada, $demanda->dataDesejada());
        $this->assertSame('Material', $demanda->categoria());
        $this->assertSame('homologada', $demanda->situacao());
        $this->assertSame(77, $demanda->numeroSequencialPncp());
    }

    public function testNumeroSequencialPncpPermaneceAusenteAntesDaConfirmacaoDaPublicacao(): void
    {
        $demanda = new Demanda(
            10,
            new UnidadeOrcamentaria(101, 'Diretoria Administrativa'),
            20,
            30,
            'Aquisicao de materiais hidraulicos',
            5,
            1500.75,
            new DateTimeImmutable('2027-03-15'),
            'Material',
            'homologada'
        );

        $this->assertNull($demanda->numeroSequencialPncp());
    }

    public function testDeveInformarNumeroSequencialPncpAposConfirmacaoDaPublicacao(): void
    {
        $demanda = new Demanda(
            10,
            new UnidadeOrcamentaria(101, 'Diretoria Administrativa'),
            20,
            30,
            'Aquisicao de materiais hidraulicos',
            5,
            1500.75,
            new DateTimeImmutable('2027-03-15'),
            'Material',
            'homologada'
        );

        $demanda->informarNumeroSequencialPncp(77);

        $this->assertSame(77, $demanda->numeroSequencialPncp());
    }
}
