<?php

declare(strict_types=1);

namespace Tests\Domain\Entities;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Semae\Pca\Domain\Entities\Procedimento;

class ProcedimentoTest extends TestCase
{
    public function testDeveRepresentarProcedimentoDoCatalogo(): void
    {
        $procedimento = new Procedimento(5, 'PUBLICAÇÃO DE PLANO');

        $this->assertSame(5, $procedimento->getId());
        $this->assertSame('PUBLICAÇÃO DE PLANO', $procedimento->getDescricao());
    }

    public function testNaoDeveExporMetodosMutadores(): void
    {
        $reflection = new ReflectionClass(Procedimento::class);

        foreach ($reflection->getMethods() as $method) {
            $this->assertStringStartsNotWith('set', $method->getName());
        }
    }

    public function testDeveRepresentarApenasCatalogoENaoOcorrenciaHistorica(): void
    {
        $procedimento = new Procedimento(4, 'HOMOLOGAÇÃO DO PLANEJAMENTO');

        $this->assertFalse(method_exists($procedimento, 'getResponsavel'));
        $this->assertFalse(method_exists($procedimento, 'getDataOcorrencia'));
        $this->assertFalse(method_exists($procedimento, 'getMensagem'));
    }
}
