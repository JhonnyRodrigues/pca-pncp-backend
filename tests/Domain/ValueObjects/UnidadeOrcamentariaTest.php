<?php

namespace Tests\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\ValueObjects\UnidadeOrcamentaria;
use Semae\Pca\Domain\Exceptions\UnidadeOrcamentariaInvalidaException;

class UnidadeOrcamentariaTest extends TestCase
{
    public function testDeveAceitarCodigoPositivoENomeValido(): void
    {
        $unidade = new UnidadeOrcamentaria(101, 'Diretoria Administrativa');

        $this->assertSame(101, $unidade->getId());
        $this->assertSame('Diretoria Administrativa', $unidade->getNome());
    }

    public function testDeveRejeitarCodigoZero(): void
    {
        $this->expectException(UnidadeOrcamentariaInvalidaException::class);

        new UnidadeOrcamentaria(0, 'Diretoria Administrativa');
    }

    public function testDeveRejeitarCodigoNegativo(): void
    {
        $this->expectException(UnidadeOrcamentariaInvalidaException::class);

        new UnidadeOrcamentaria(-1, 'Diretoria Administrativa');
    }

    public function testDeveRejeitarNomeVazio(): void
    {
        $this->expectException(UnidadeOrcamentariaInvalidaException::class);

        new UnidadeOrcamentaria(101, '   ');
    }

    public function testDeveRejeitarNomeComMenosDeTresCaracteres(): void
    {
        $this->expectException(UnidadeOrcamentariaInvalidaException::class);

        new UnidadeOrcamentaria(101, 'TI');
    }

    public function testDeveRemoverEspacosDasExtremidadesDoNome(): void
    {
        $unidade = new UnidadeOrcamentaria(101, '  Diretoria Administrativa  ');

        $this->assertSame('Diretoria Administrativa', $unidade->getNome());
    }

    public function testDeveAceitarNomeComAcentos(): void
    {
        $unidade = new UnidadeOrcamentaria(101, 'Procuradoria Jurídica');

        $this->assertSame('Procuradoria Jurídica', $unidade->getNome());
    }

    public function testDeveAceitarNomeComNumeros(): void
    {
        $unidade = new UnidadeOrcamentaria(101, 'Diretoria 2');

        $this->assertSame('Diretoria 2', $unidade->getNome());
    }

    public function testNaoDeveExporMetodosMutadores(): void
    {
        $unidade = new UnidadeOrcamentaria(101, 'Diretoria Administrativa');

        $this->assertFalse(method_exists($unidade, 'alterarCodigo'));
        $this->assertFalse(method_exists($unidade, 'alterarNome'));
    }
}
