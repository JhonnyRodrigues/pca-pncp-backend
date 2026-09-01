<?php

declare(strict_types=1);

namespace Tests\Presentation\Controllers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\Pca\Application\Contracts\PublicadorPca;
use Semae\Pca\Application\Results\PublicarPcaResponse;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaRequest;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Domain\Exceptions\AnoExecucaoInvalidoException;
use Semae\Pca\Presentation\Controllers\PublicarPcaController;
use Semae\Pca\Presentation\Inputs\EntradaPublicarPca;

final class PublicarPcaControllerTest extends TestCase
{
    /** @regra RN-CAD-001 @regra RN-CAD-002 */
    public function testEntradaValidaDeveCriarRequestEDevolverAMesmaResponse(): void
    {
        $correlacao = new CorrelacaoExecucao('corr-controller-valido');
        $responseEsperada = PublicarPcaResponse::concluida([], 0, 0, $correlacao);
        $publicador = new PublicadorPcaSpy($responseEsperada);
        $controller = new PublicarPcaController($publicador);
        $ano = (int) date('Y') + 1;

        $response = $controller->executar(
            new EntradaPublicarPca('12.345.678/0001-95', $ano),
            $correlacao
        );

        $this->assertSame($responseEsperada, $response);
        $this->assertSame(1, $publicador->chamadas);
        $this->assertSame($correlacao, $publicador->correlacaoRecebida);
        $this->assertNotNull($publicador->requestRecebido);
        $this->assertSame(
            '12345678000195',
            $publicador->requestRecebido->getCnpj()->getValor()
        );
        $this->assertSame(
            $ano,
            $publicador->requestRecebido->getAnoExecucao()->getValor()
        );
    }

    /** @regra RN-CAD-001 */
    public function testCnpjInvalidoDevePropagarSemChamarUseCase(): void
    {
        $correlacao = new CorrelacaoExecucao('corr-cnpj-invalido');
        [$controller, $publicador] = $this->criarControllerComSpy($correlacao);

        try {
            $controller->executar(
                new EntradaPublicarPca('cnpj-invalido', (int) date('Y')),
                $correlacao
            );
            $this->fail('Era esperada a rejeição do CNPJ inválido.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('CNPJ inválido.', $exception->getMessage());
        }

        $this->assertSame(0, $publicador->chamadas);
    }

    /** @regra RN-CAD-002 */
    public function testAnoInvalidoDevePropagarSemChamarUseCase(): void
    {
        $correlacao = new CorrelacaoExecucao('corr-ano-invalido');
        [$controller, $publicador] = $this->criarControllerComSpy($correlacao);

        try {
            $controller->executar(
                new EntradaPublicarPca('12.345.678/0001-95', (int) date('Y') - 1),
                $correlacao
            );
            $this->fail('Era esperada a rejeição do ano inválido.');
        } catch (AnoExecucaoInvalidoException $exception) {
            $this->assertSame('Ano de execução inválido.', $exception->getMessage());
        }

        $this->assertSame(0, $publicador->chamadas);
    }

    /** @regra RN-CAD-001 @regra RN-CAD-002 */
    public function testCnpjDeveTerPrecedenciaQuandoCnpjEAnoForemInvalidos(): void
    {
        $correlacao = new CorrelacaoExecucao('corr-entrada-invalida');
        [$controller, $publicador] = $this->criarControllerComSpy($correlacao);

        try {
            $controller->executar(
                new EntradaPublicarPca('cnpj-invalido', (int) date('Y') - 1),
                $correlacao
            );
            $this->fail('Era esperada a rejeição do CNPJ inválido.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('CNPJ inválido.', $exception->getMessage());
        }

        $this->assertSame(0, $publicador->chamadas);
    }

    public function testFalhaInesperadaDoUseCaseDevePermanecerParaFuturaFachada(): void
    {
        $publicador = new class implements PublicadorPca {
            public function executar(
                PublicarPcaRequest $request,
                CorrelacaoExecucao $correlacao
            ): PublicarPcaResponse {
                throw new RuntimeException('falha inesperada');
            }
        };
        $controller = new PublicarPcaController($publicador);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('falha inesperada');

        $controller->executar(
            new EntradaPublicarPca('12.345.678/0001-95', (int) date('Y')),
            new CorrelacaoExecucao('corr-falha-inesperada')
        );
    }

    /** @return array{PublicarPcaController, PublicadorPcaSpy} */
    private function criarControllerComSpy(
        CorrelacaoExecucao $correlacao
    ): array {
        $publicador = new PublicadorPcaSpy(
            PublicarPcaResponse::concluida([], 0, 0, $correlacao)
        );

        return [new PublicarPcaController($publicador), $publicador];
    }

}

final class PublicadorPcaSpy implements PublicadorPca
{
    public int $chamadas = 0;
    public ?PublicarPcaRequest $requestRecebido = null;
    public ?CorrelacaoExecucao $correlacaoRecebida = null;

    public function __construct(private readonly PublicarPcaResponse $response) {}

    public function executar(
        PublicarPcaRequest $request,
        CorrelacaoExecucao $correlacao
    ): PublicarPcaResponse {
        $this->chamadas++;
        $this->requestRecebido = $request;
        $this->correlacaoRecebida = $correlacao;

        return $this->response;
    }
}
