<?php

declare(strict_types=1);

namespace Tests\Domain\Entities;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Exceptions\UnidadeOrcamentariaInvalidaException;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Uasg;
use ReflectionClass;

class UnidadeOrcamentariaTest extends TestCase
{
    public function testDeveCriarUnidadeOrcamentariaValida(): void
    {
        $lotacao = new Lotacao(101, 'Diretoria Administrativa');
        $uasg = new Uasg('928344', 'SEMAE Piracicaba');

        $unidade = new UnidadeOrcamentaria(
            $lotacao,
            $uasg,
            'Diretoria Administrativa'
        );

        $this->assertSame($lotacao, $unidade->getLotacao());
        $this->assertSame('Diretoria Administrativa', $unidade->getDescricao());
        $this->assertSame($uasg, $unidade->getUasg());
    }

    public function testDeveRejeitarDescricaoVazia(): void
    {
        $this->expectException(UnidadeOrcamentariaInvalidaException::class);

        new UnidadeOrcamentaria(
            new Lotacao(101, 'Diretoria Administrativa'),
            new Uasg('928344', 'SEMAE Piracicaba'),
            '   ' // <---
        );
    }

    public function testDeveRejeitarDescricaoComMenosDeTresCaracteres(): void
    {
        $this->expectException(UnidadeOrcamentariaInvalidaException::class);

        new UnidadeOrcamentaria(
            new Lotacao(101, 'Diretoria Administrativa'),
            new Uasg('928344', 'SEMAE Piracicaba'),
            'TI' // <---
        );
    }

    public function testDeveRemoverEspacosDasExtremidadesDaDescricao(): void
    {
        $unidade = new UnidadeOrcamentaria(
            new Lotacao(101, 'Diretoria Administrativa'),
            new Uasg('928344', 'SEMAE Piracicaba'),
            '  Diretoria Administrativa  '
        );

        $this->assertSame('Diretoria Administrativa', $unidade->getDescricao());
    }

    public function testDeveAceitarDescricaoComAcentos(): void
    {
        $unidade = new UnidadeOrcamentaria(
            new Lotacao(102, 'Procuradoria Jurídica'),
            new Uasg('928344', 'SEMAE Piracicaba'),
            'Procuradoria Jurídica'
        );

        $this->assertSame('Procuradoria Jurídica', $unidade->getDescricao());
    }

    public function testDeveAceitarDescricaoComNumeros(): void
    {
        $unidade = new UnidadeOrcamentaria(
            new Lotacao(103, 'Diretoria 2'),
            new Uasg('928344', 'SEMAE Piracicaba'),
            'Diretoria 2'
        );

        $this->assertSame('Diretoria 2', $unidade->getDescricao());
    }

    public function testDeveSerImutavel(): void
    {
        $reflection = new ReflectionClass(UnidadeOrcamentaria::class);

        foreach ($reflection->getMethods() as $method) {
            $this->assertStringStartsNotWith('set', $method->getName());
        }
    }
}
