<?php

declare(strict_types=1);

namespace Tests\Application\UseCases\PublicarPca;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaResponse;
use Semae\Pca\Application\UseCases\PublicarPca\ResultadoProcessamentoPlano;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;

class PublicarPcaResponseTest extends TestCase
{
    public function testDeveCriarPublicarPcaResponseSemErros(): void
    {
        $response = new PublicarPcaResponse([], []);

        $this->assertSame([], $response->getErros());
    }

    public function testDeveCriarPublicarPcaResponseComErros(): void
    {
        $erros = ['Entrada inválida.'];

        $response = new PublicarPcaResponse($erros, []);

        $this->assertSame($erros, $response->getErros());
    }

    public function testDeveRetornarResultadosProcessados(): void
    {
        $resultado = $this->criarResultado(DecisaoProcessamentoPlano::PUBLICAR);

        $response = new PublicarPcaResponse([], [$resultado]);

        $this->assertSame([$resultado], $response->getResultadoProcessamentoPlano());
    }

    public function testDeveRetornarListaDeErros(): void
    {
        $erros = ['Primeiro erro.', 'Segundo erro.'];

        $response = new PublicarPcaResponse($erros, []);

        $this->assertSame($erros, $response->getErros());
    }

    public function testDevePermitirMultiplosResultados(): void
    {
        $resultados = [
            $this->criarResultado(DecisaoProcessamentoPlano::PUBLICAR),
            $this->criarResultado(DecisaoProcessamentoPlano::RETIFICAR),
            $this->criarResultado(DecisaoProcessamentoPlano::NENHUMA_ACAO),
        ];

        $response = new PublicarPcaResponse([], $resultados);

        $this->assertSame($resultados, $response->getResultadoProcessamentoPlano());
    }

    public function testDeveSerImutavel(): void
    {
        $reflection = new ReflectionClass(PublicarPcaResponse::class);

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly());
        }

        foreach ($reflection->getMethods() as $method) {
            $this->assertStringStartsNotWith('set', $method->getName());
        }
    }

    private function criarResultado(DecisaoProcessamentoPlano $decisao): ResultadoProcessamentoPlano 
    {
        return new ResultadoProcessamentoPlano($this->createMock(Plano::class), $decisao);
    }
}
