<?php

declare(strict_types=1);

namespace Tests\Application\UseCases\PublicarPca;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\UseCases\PublicarPca\ProcessarPublicacaoPcaUseCase;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaRequest;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaValidator;
use Semae\Pca\Application\UseCases\PublicarPca\ValidationResult;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;
use Semae\Pca\Domain\Ports\PlanoRepository;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;

class ProcessarPublicacaoPcaUseCaseTest extends TestCase
{
    public function testDeveProcessarPlanoComDecisaoPublicar(): void
    {
        $plano = $this->criarPlanoComDecisao(DecisaoProcessamentoPlano::PUBLICAR);
        $resultado = $this->executarComPlanos([$plano]);

        $this->assertSame(
            DecisaoProcessamentoPlano::PUBLICAR,
            $resultado['processamentos'][0]['decisao']
        );
    }

    public function testDeveProcessarPlanoComDecisaoRetificar(): void
    {
        $plano = $this->criarPlanoComDecisao(DecisaoProcessamentoPlano::RETIFICAR);
        $resultado = $this->executarComPlanos([$plano]);

        $this->assertSame(
            DecisaoProcessamentoPlano::RETIFICAR,
            $resultado['processamentos'][0]['decisao']
        );
    }

    public function testDeveProcessarPlanoComDecisaoNenhumaAcao(): void
    {
        $plano = $this->criarPlanoComDecisao(DecisaoProcessamentoPlano::NENHUMA_ACAO);
        $resultado = $this->executarComPlanos([$plano]);

        $this->assertSame(
            DecisaoProcessamentoPlano::NENHUMA_ACAO,
            $resultado['processamentos'][0]['decisao']
        );
    }

    public function testDeveProcessarMultiplosPlanos(): void
    {
        $planos = [
            $this->criarPlanoComDecisao(DecisaoProcessamentoPlano::PUBLICAR),
            $this->criarPlanoComDecisao(DecisaoProcessamentoPlano::RETIFICAR),
            $this->criarPlanoComDecisao(DecisaoProcessamentoPlano::NENHUMA_ACAO),
        ];
        $resultado = $this->executarComPlanos($planos);

        $this->assertSame([], $resultado['erros']);
        $this->assertSame(
            [
                [
                    'plano' => $planos[0],
                    'decisao' => DecisaoProcessamentoPlano::PUBLICAR,
                ],
                [
                    'plano' => $planos[1],
                    'decisao' => DecisaoProcessamentoPlano::RETIFICAR,
                ],
                [
                    'plano' => $planos[2],
                    'decisao' => DecisaoProcessamentoPlano::NENHUMA_ACAO,
                ],
            ],
            $resultado['processamentos']
        );
    }

    public function testDeveRetornarResultadoQuandoNaoExistiremPlanosParaProcessamento(): void
    {
        $resultado = $this->executarComPlanos([]);
        $this->assertSame(
            ['erros' => [], 'processamentos' => []],
            $resultado
        );
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
            ->executar($request);

        $this->assertSame(['Entrada inválida.'], $resultado['erros']);
        $this->assertSame([], $resultado['processamentos']);
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
            ->executar($request);

        $this->assertSame([], $resultado['processamentos']);
    }

    public function testNaoDeveDuplicarRegrasDoDominio(): void
    {
        $plano = $this->criarPlanoComDecisao(DecisaoProcessamentoPlano::RETIFICAR);
        $resultado = $this->executarComPlanos([$plano]);

        $this->assertSame($plano, $resultado['processamentos'][0]['plano']);
        $this->assertSame(DecisaoProcessamentoPlano::RETIFICAR, $resultado['processamentos'][0]['decisao']);
    }

    private function executarComPlanos(array $planos): array
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
        ))->executar($request);
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
