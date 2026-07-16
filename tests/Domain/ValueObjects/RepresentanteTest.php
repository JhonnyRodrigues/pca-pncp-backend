<?php

declare(strict_types=1);

namespace Tests\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Semae\Pca\Domain\ValueObjects\Representante;

class RepresentanteTest extends TestCase
{
    public function testDeveCriarRepresentanteValido(): void
    {
        $representante = new Representante(709, 'Maria da Silva');

        $this->assertSame(709, $representante->getId());
        $this->assertSame('Maria da Silva', $representante->getNome());
    }

    public function testDeveSerImutavel(): void
    {
        $reflection = new ReflectionClass(Representante::class);

        foreach ($reflection->getMethods() as $method) {
            $this->assertStringStartsNotWith('set', $method->getName());
        }
    }

    public function testNaoDeveRepresentarOcorrenciaHistoricaOuAutenticacao(): void
    {
        $representante = new Representante(709, 'Maria da Silva');

        $this->assertFalse(method_exists($representante, 'getDataInsercao'));
        $this->assertFalse(method_exists($representante, 'getMensagem'));
        $this->assertFalse(method_exists($representante, 'getUsuarioAutenticado'));
    }
}
