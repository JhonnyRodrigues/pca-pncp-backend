<?php

declare(strict_types=1);

namespace Tests\Application\UseCases\PublicarPca;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Semae\Pca\Application\UseCases\PublicarPca\ResultadoProcessamentoPlano;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;

class ResultadoProcessamentoPlanoTest extends TestCase
{
    public function testDeveCriarResultadoProcessamentoPlano(): void
    {
        $plano = $this->createMock(Plano::class);

        $resultado = new ResultadoProcessamentoPlano(
            $plano,
            DecisaoProcessamentoPlano::PUBLICAR
        );

        $this->assertSame($plano, $resultado->getPlano());
    }

    public function testDeveManterDecisaoRecebidaDoDominio(): void
    {
        $resultado = new ResultadoProcessamentoPlano(
            $this->createMock(Plano::class),
            DecisaoProcessamentoPlano::RETIFICAR
        );

        $this->assertSame(
            DecisaoProcessamentoPlano::RETIFICAR,
            $resultado->getDecisao()
        );
    }

    public function testDeveSerImutavel(): void
    {
        $reflection = new ReflectionClass(ResultadoProcessamentoPlano::class);

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly());
        }

        foreach ($reflection->getMethods() as $method) {
            $this->assertStringStartsNotWith('set', $method->getName());
        }
    }
}
