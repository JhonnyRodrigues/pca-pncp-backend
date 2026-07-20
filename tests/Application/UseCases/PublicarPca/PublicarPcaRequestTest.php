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
    public function testDeveCriarPublicarPcaRequestValido(): void
    {
        $anoExecucao = new AnoExecucao((int) date('Y') + 1);
        $cnpj = new Cnpj('12.345.678/0001-95');
        $usuarioLogado = 'jsilva';
        $credenciais = $this->criarCredenciais();

        $request = new PublicarPcaRequest(
            $cnpj,
            $anoExecucao,
            $usuarioLogado,
            $credenciais
        );

        $this->assertSame($anoExecucao, $request->getAnoExecucao());
        $this->assertSame($cnpj, $request->getCnpj());
        $this->assertSame($usuarioLogado, $request->getUsuarioLogado());
        $this->assertSame($credenciais, $request->getCredenciais());
    }

    public function testDevePermitirConsultarAnoExecucao(): void
    {
        $anoExecucao = new AnoExecucao((int) date('Y') + 1);

        $request = new PublicarPcaRequest(
            new Cnpj('12.345.678/0001-95'),
            $anoExecucao,
            'jsilva',
            $this->criarCredenciais()
        );

        $this->assertSame($anoExecucao, $request->getAnoExecucao());
    }

    public function testDevePermitirConsultarCnpj(): void
    {
        $cnpj = new Cnpj('12.345.678/0001-95');

        $request = new PublicarPcaRequest(
            $cnpj,
            new AnoExecucao((int) date('Y') + 1),
            'jsilva',
            $this->criarCredenciais()
        );

        $this->assertSame($cnpj, $request->getCnpj());
    }

    public function testDevePermitirConsultarUsuarioLogado(): void
    {
        $request = new PublicarPcaRequest(
            new Cnpj('12.345.678/0001-95'),
            new AnoExecucao((int) date('Y') + 1),
            'jsilva',
            $this->criarCredenciais()
        );

        $this->assertSame('jsilva', $request->getUsuarioLogado());
    }

    public function testDevePermitirConsultarCredenciais(): void
    {
        $credenciais = $this->criarCredenciais();

        $request = new PublicarPcaRequest(
            new Cnpj('12.345.678/0001-95'),
            new AnoExecucao((int) date('Y') + 1),
            'jsilva',
            $credenciais
        );

        $this->assertSame($credenciais, $request->getCredenciais());
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

    private function criarCredenciais(): array
    {
        return [
            'ambiente' => 'homologacao',
            'baseUrl'  => 'https://treina.pncp.gov.br/api/pncp',
            'usuario'  => '99c7-440ee-c4130',
            'senha'	   => 'ea6s84bvg1s81tsI',
        ];
    }
}