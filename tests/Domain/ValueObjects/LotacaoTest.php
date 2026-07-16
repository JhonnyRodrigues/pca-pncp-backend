<?php

declare(strict_types=1);

namespace Tests\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\UnidadeOrcamentaria;

class LotacaoTest extends TestCase
{
    public function testDeveCriarLotacaoValida(): void
    {
        $lotacao = new Lotacao(159, 'Coordenadoria de Tecnologia');

        $this->assertSame(159, $lotacao->getId());
        $this->assertSame('Coordenadoria de Tecnologia', $lotacao->getNome());
    }

    public function testDeveSerImutavel(): void
    {
        $reflection = new ReflectionClass(Lotacao::class);

        foreach ($reflection->getMethods() as $method) {
            $this->assertStringStartsNotWith('set', $method->getName());
        }
    }

    public function testNaoDeveReutilizarUnidadeOrcamentariaParaRepresentarLotacao(): void
    {
        $lotacao = new Lotacao(159, 'Coordenadoria de Tecnologia');

        $this->assertNotInstanceOf(UnidadeOrcamentaria::class, $lotacao);
    }
}
