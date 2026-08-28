<?php

declare(strict_types=1);

namespace Tests\Domain\ValueObjects;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;

class LotacaoTest extends TestCase
{
    public function testDeveCriarLotacaoValida(): void
    {
        $lotacao = new Lotacao(159, '  Coordenadoria de Tecnologia  ');

        $this->assertSame(159, $lotacao->getId());
        $this->assertSame('Coordenadoria de Tecnologia', $lotacao->getNome());
    }

    /** @regra RN-CAD-004 */
    public function testDeveRejeitarIdentificadorZero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Lotacao(0, 'Coordenadoria de Tecnologia');
    }

    /** @regra RN-CAD-004 */
    public function testDeveRejeitarIdentificadorNegativo(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Lotacao(-1, 'Coordenadoria de Tecnologia');
    }

    /** @regra RN-CAD-004 */
    public function testDeveRejeitarDescricaoVazia(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Lotacao(159, '   ');
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
