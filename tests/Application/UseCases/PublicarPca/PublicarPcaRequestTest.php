<?php

declare(strict_types=1);

namespace Tests\Application\UseCases\PublicarPca;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaRequest;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;

class PublicarPcaRequestTest extends TestCase
{
    /** @regra RN-CAD-001 @regra RN-CAD-002 */
    public function testDeveConterSomenteCnpjEAnoExecucao(): void
    {
        $cnpj = new Cnpj('12.345.678/0001-95');
        $ano = new AnoExecucao((int) date('Y') + 1);

        $request = new PublicarPcaRequest($cnpj, $ano);

        $this->assertSame($cnpj, $request->getCnpj());
        $this->assertSame($ano, $request->getAnoExecucao());
        $this->assertSame(
            ['cnpj', 'anoExecucao'],
            array_map(
                static fn (\ReflectionProperty $propriedade): string => $propriedade->getName(),
                (new ReflectionClass($request))->getProperties()
            )
        );
    }

    public function testDeveSerImutavel(): void
    {
        $reflection = new ReflectionClass(PublicarPcaRequest::class);

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly());
        }
    }
}
