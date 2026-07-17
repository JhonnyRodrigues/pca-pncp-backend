<?php

declare(strict_types=1);

namespace Tests\Application\UseCases\PublicarPca;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaRequest;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use ReflectionClass;

class PublicarPcaRequestTest extends TestCase
{
    public function testDeveCriarPublicarPcaRequestValido(): void
    {
        $anoExecucao = new AnoExecucao((int) date('Y') + 1);
        $cnpj = new Cnpj('12.345.678/0001-95');
        $nomeUsuario = 'jsilva';

        $request = new PublicarPcaRequest($cnpj, $anoExecucao, $nomeUsuario);

        $this->assertSame($anoExecucao, $request->getAnoExecucao());
        $this->assertSame($cnpj, $request->getCnpj());
        $this->assertSame($nomeUsuario, $request->getNomeUsuario());
    }

    public function testDevePermitirConsultarAnoExecucao(): void
    {
        $anoExecucao = new AnoExecucao((int) date('Y') + 1);

        $request = new PublicarPcaRequest(
            new Cnpj('12.345.678/0001-95'),
            $anoExecucao,
            'jsilva'
        );

        $this->assertSame($anoExecucao, $request->getAnoExecucao());
    }

    public function testDevePermitirConsultarCnpj(): void
    {
        $cnpj = new Cnpj('12.345.678/0001-95');

        $request = new PublicarPcaRequest(
            $cnpj,
            new AnoExecucao((int) date('Y') + 1),
            'jsilva'
        );

        $this->assertSame($cnpj, $request->getCnpj());
    }

    public function testDevePermitirConsultarNomeUsuario(): void
    {
        $request = new PublicarPcaRequest(
            new Cnpj('12.345.678/0001-95'),
            new AnoExecucao((int) date('Y') + 1),
            'jsilva'
        );

        $this->assertSame('jsilva', $request->getNomeUsuario());
    }

    public function testDeveSerImutavel(): void
    {
        $reflection = new ReflectionClass(PublicarPcaRequest::class);

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly());
        }

        foreach ($reflection->getMethods() as $method) {
            $this->assertStringStartsNotWith('set', $method->getName());
        }
    }
}
