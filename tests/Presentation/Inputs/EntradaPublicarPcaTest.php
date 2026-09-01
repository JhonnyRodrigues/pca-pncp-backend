<?php

declare(strict_types=1);

namespace Tests\Presentation\Inputs;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Semae\Pca\Presentation\Inputs\EntradaPublicarPca;

final class EntradaPublicarPcaTest extends TestCase
{
    public function testDevePreservarOsPrimitivosTipadosSemMaterializarODominio(): void
    {
        $entrada = new EntradaPublicarPca('12.345.678/0001-95', 2027);

        $this->assertSame('12.345.678/0001-95', $entrada->getCnpj());
        $this->assertSame(2027, $entrada->getAnoExecucao());
    }

    public function testDeveSerImutavel(): void
    {
        $reflection = new ReflectionClass(EntradaPublicarPca::class);

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly());
        }
    }
}
