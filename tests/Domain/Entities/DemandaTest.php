<?php

declare(strict_types=1);

namespace Tests\Domain\Entities;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\Entities\Demanda;
use Semae\Pca\Domain\ValueObjects\UnidadeOrcamentaria;
use DateTimeImmutable;
use InvalidArgumentException;
use Semae\Pca\Domain\Exceptions\SequencialPncpException;


class DemandaTest extends TestCase
{
    public function testDeveRepresentarUmaDemandaHomologadaValida(): void
    {
        $demanda = $this->criarDemanda();

        $this->assertSame(1001, $demanda->getIdDemanda());
        $this->assertSame('MICROCOMPUTADOR DESKTOP TIPO PC PARA APLICAÇÕES AVANÇADAS', $demanda->getDescricao()
        );
    }

    public function testDeveCalcularValorUnitarioComBaseNoValorTotalEQuantidade(): void
    {
        $demanda = $this->criarDemanda();

        $this->assertEquals(3550.00, $demanda->getValorUnitario());
    }

    public function testDeveIniciarSemSequencialPncp(): void
    {
        $demanda = $this->criarDemanda();

        $this->assertNull($demanda->getSequencialPncp());
    }

    public function testDevePermitirInformarSequencialPncpAposPublicacao(): void
    {
        $demanda = $this->criarDemanda();

        $demanda->setSequencialPncp(77);

        $this->assertSame(77, $demanda->getSequencialPncp());
    }

    public function testDeveLancarExcecaoQuandoSequencialPncpForZero(): void
    {
        $demanda = $this->criarDemanda();

        $this->expectException(SequencialPncpException::class);

        $demanda->setSequencialPncp(0);
    }

    public function testDeveLancarExcecaoQuandoCategoriaForInvalida(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Demanda(
            1001,
            2,
            159,
            42,
            new UnidadeOrcamentaria(101, 'Diretoria Geral Administrativa e Financeira'),
            new DateTimeImmutable('2027-03-15'),
            99, // <---
            'MICROCOMPUTADOR DESKTOP TIPO PC PARA APLICAÇÕES AVANÇADAS',
            'UNIDADE',
            2,
            7100.00,
            7100.00,
            2,
            '98.004.000004',
            '98',
            'INFORMÁTICA',
            'N'
        );
    }

    public function testDeveLancarExcecaoQuandoQuantidadeForMenorOuIgualAZero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Demanda(
            1001,
            2,
            159,
            42,
            new UnidadeOrcamentaria(101, 'Diretoria Geral Administrativa e Financeira'),
            new DateTimeImmutable('2027-03-15'),
            1,
            'MICROCOMPUTADOR DESKTOP TIPO PC PARA APLICAÇÕES AVANÇADAS',
            'UNIDADE',
            0, // <---
            7100.00,
            7100.00,
            2,
            '98.004.000004',
            '98',
            'INFORMÁTICA',
            'N'
        );
    }

    public function testDeveLancarExcecaoQuandoCatalogoForInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Demanda(
            1001,
            2,
            159,
            42,
            new UnidadeOrcamentaria(101, 'Diretoria Geral Administrativa e Financeira'),
            new DateTimeImmutable('2027-03-15'),
            1,
            'MICROCOMPUTADOR DESKTOP TIPO PC PARA APLICAÇÕES AVANÇADAS',
            'UNIDADE',
            2,
            7100.00,
            7100.00,
            99, // <---
            '98.004.000004',
            '98',
            'INFORMÁTICA',
            'N'
        );
    }

    public function testDeveLancarExcecaoQuandoOrigemForInvalida(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Demanda(
            1001,
            2,
            159,
            42,
            new UnidadeOrcamentaria(101, 'Diretoria Geral Administrativa e Financeira'),
            new DateTimeImmutable('2027-03-15'),
            1,
            'MICROCOMPUTADOR DESKTOP TIPO PC PARA APLICAÇÕES AVANÇADAS',
            'UNIDADE',
            2,
            7100.00,
            7100.00,
            2,
            '98.004.000004',
            '98',
            'INFORMÁTICA',
            'X' // <---
        );
    }

    private function criarDemanda(): Demanda
    {
        return new Demanda(
            1001,
            2,
            159,
            42,
            new UnidadeOrcamentaria(101, 'Diretoria Geral Administrativa e Financeira'),
            new DateTimeImmutable('2027-03-15'),
            1,
            'MICROCOMPUTADOR DESKTOP TIPO PC PARA APLICAÇÕES AVANÇADAS',
            'UNIDADE',
            2,
            7100.00,
            7100.00,
            2,
            '98.004.000004',
            '98',
            'INFORMÁTICA',
            'N'
        );
    }
}
