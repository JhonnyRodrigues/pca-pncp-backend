<?php

declare(strict_types=1);

namespace Tests\Application\UseCases\PublicarPca;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;
use Semae\Pca\Domain\Ports\PlanoRepository;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaRequest;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaValidator;
use Semae\Pca\Application\UseCases\PublicarPca\ValidationResult;
use Semae\Pca\Application\UseCases\PublicarPca\ProcessarPublicacaoPcaUseCase;
use Semae\Pca\Application\UseCases\PublicarPca\ResultadoProcessamentoPlano;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaResponse;

class ProcessarPublicacaoPcaUseCaseTest extends TestCase
{
    public function testDeveProcessarPlanoComDecisaoPublicar(): void
    {
        $plano = $this->criarPlanoComDecisao(DecisaoProcessamentoPlano::PUBLICAR);
        $resultado = $this->processarComPlanos([$plano]);

        $this->assertSame(
            DecisaoProcessamentoPlano::PUBLICAR,
            $resultado->getResultadoProcessamentoPlano()[0]->getDecisao()
        );
    }

    public function testDeveProcessarPlanoComDecisaoRetificar(): void
    {
        $plano = $this->criarPlanoComDecisao(DecisaoProcessamentoPlano::RETIFICAR);
        $resultado = $this->processarComPlanos([$plano]);

        $this->assertSame(
            DecisaoProcessamentoPlano::RETIFICAR,
            $resultado->getResultadoProcessamentoPlano()[0]->getDecisao()
        );
    }

    public function testDeveProcessarPlanoComDecisaoNenhumaAcao(): void
    {
        $plano = $this->criarPlanoComDecisao(DecisaoProcessamentoPlano::NENHUMA_ACAO);
        $resultado = $this->processarComPlanos([$plano]);

        $this->assertSame(
            DecisaoProcessamentoPlano::NENHUMA_ACAO,
            $resultado->getResultadoProcessamentoPlano()[0]->getDecisao()
        );
    }

    public function testDeveProcessarMultiplosPlanos(): void
    {
        $planos = [
            $this->criarPlanoComDecisao(DecisaoProcessamentoPlano::PUBLICAR),
            $this->criarPlanoComDecisao(DecisaoProcessamentoPlano::RETIFICAR),
            $this->criarPlanoComDecisao(DecisaoProcessamentoPlano::NENHUMA_ACAO),
        ];
        $resultado = $this->processarComPlanos($planos);

        $resultados = $resultado->getResultadoProcessamentoPlano();

        $this->assertSame([], $resultado->getErros());
        $this->assertCount(3, $resultados);
        $this->assertSame($planos[0], $resultados[0]->getPlano());
        $this->assertSame($planos[1], $resultados[1]->getPlano());
        $this->assertSame($planos[2], $resultados[2]->getPlano());
    }

    public function testDeveRetornarResultadoQuandoNaoExistiremPlanosParaProcessamento(): void
    {
        $resultado = $this->processarComPlanos([]);

        $this->assertInstanceOf(PublicarPcaResponse::class, $resultado);
        $this->assertSame([], $resultado->getErros());
        $this->assertSame([], $resultado->getResultadoProcessamentoPlano());
    }

    public function testDeveAcionarValidatorAntesDoProcessamento(): void
    {
        $request = $this->criarRequest();
        $validator = $this->createMock(PublicarPcaValidator::class);
        $repository = $this->createMock(PlanoRepository::class);

        $validator
            ->expects($this->once())
            ->method('validarRequest')
            ->with($this->identicalTo($request))
            ->willReturn(new ValidationResult(['Entrada inválida.']));

        $repository
            ->expects($this->never())
            ->method('obterPlanosHomologadosParaProcessamento');

        $resultado = (new ProcessarPublicacaoPcaUseCase($validator, $repository))
            ->processar($request);

        $this->assertSame(['Entrada inválida.'], $resultado->getErros());
        $this->assertSame([], $resultado->getResultadoProcessamentoPlano());
    }

    public function testDeveConsultarPlanoRepository(): void
    {
        $request = $this->criarRequest();
        $repository = $this->createMock(PlanoRepository::class);

        $repository
            ->expects($this->once())
            ->method('obterPlanosHomologadosParaProcessamento')
            ->with($this->identicalTo($request->getAnoExecucao()))
            ->willReturn([]);

        $resultado = (new ProcessarPublicacaoPcaUseCase(new PublicarPcaValidator(), $repository))
            ->processar($request);

        $this->assertSame([], $resultado->getResultadoProcessamentoPlano());
    }

    public function testNaoDeveDuplicarRegrasDoDominio(): void
    {
        $plano = $this->criarPlanoComDecisao(DecisaoProcessamentoPlano::RETIFICAR);
        $resultado = $this->processarComPlanos([$plano]);

        $this->assertInstanceOf(
            ResultadoProcessamentoPlano::class,
            $resultado->getResultadoProcessamentoPlano()[0]
        );
        $this->assertSame($plano, $resultado->getResultadoProcessamentoPlano()[0]->getPlano());
        $this->assertSame(
            DecisaoProcessamentoPlano::RETIFICAR,
            $resultado->getResultadoProcessamentoPlano()[0]->getDecisao()
        );
    }

    private function processarComPlanos(array $planos): PublicarPcaResponse
    {
        $request = $this->criarRequest();
        $repository = $this->createMock(PlanoRepository::class);

        $repository
            ->expects($this->once())
            ->method('obterPlanosHomologadosParaProcessamento')
            ->with($this->identicalTo($request->getAnoExecucao()))
            ->willReturn($planos);

        return (new ProcessarPublicacaoPcaUseCase(
            new PublicarPcaValidator(),
            $repository
        ))->processar($request);
    }

    private function criarPlanoComDecisao(DecisaoProcessamentoPlano $decisao): Plano
    {
        $plano = $this->createMock(Plano::class);
        $plano
            ->expects($this->once())
            ->method('decidirProcessamento')
            ->willReturn($decisao);

        return $plano;
    }

    private function criarRequest(): PublicarPcaRequest
    {
        return new PublicarPcaRequest(
            new Cnpj('12.345.678/0001-95'),
            new AnoExecucao((int) date('Y')),
            'jsilva',
            [
                'ambiente' => 'homologacao',
                'baseUrl' => 'https://treina.pncp.gov.br/api/pncp',
                'usuario' => 'usuario-pncp',
                'senha' => 'senha-pncp',
            ]
        );
    }
}
