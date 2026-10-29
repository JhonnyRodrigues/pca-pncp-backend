<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Ports\AutenticadorPncpPort;
use Semae\Pca\Application\Ports\EscritorPncpPort;
use Semae\Pca\Application\Ports\LeitorPncpPort;
use Semae\Pca\Application\Results\ResultadoAutenticacaoPncp;
use Semae\Pca\Application\Results\ResultadoBuscaCandidatosPca;
use Semae\Pca\Application\Results\ResultadoEscritaPncp;
use Semae\Pca\Application\Results\ResultadoObservacaoPca;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\PcaObservado;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Autenticacao\SessaoPncpAutenticada;
use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;
use Semae\Pca\Presentation\ViewModels\ResultadoUnidadeViewModel;
use Tests\Contract\Sprint84\Support\Sprint84PncpVerifier;
use Tests\Contract\Sprint84\Support\Sprint84GateException;
use Tests\Contract\Sprint84\Support\Sprint84PncpTransportGuard;
use Tests\Support\PerfilPublicacaoPcaFixture;

final class Sprint84PncpVerifierTest extends TestCase
{
    public function testNativeDeveReutilizarGuardaDeTransporteRecebida(): void
    {
        $perfil = PerfilPublicacaoPcaFixture::valido();
        $guard = new Sprint84PncpTransportGuard(new TransporteSemIoVerifierSprint84());

        $verifier = Sprint84PncpVerifier::native(
            new PerfilPublicacaoPca($perfil),
            $perfil,
            $this->configuration(),
            transportGuard: $guard
        );

        $this->assertSame([], $verifier->diagnosticoHttp());
    }

    public function testPrecheckDeveComprovarAusenciaNasDuasUasgsAntesDoFluxo(): void
    {
        $reader = $this->createMock(LeitorPncpPort::class);
        $reader->expects($this->exactly(2))
            ->method('localizarCandidatos')
            ->willReturn(ResultadoBuscaCandidatosPca::concluida([]));
        $verifier = Sprint84PncpVerifier::fromPorts(
            $this->authenticator(),
            $reader,
            $this->createStub(EscritorPncpPort::class),
            new Cnpj('12345678000195'),
            $this->configuration()
        );

        $report = $verifier->precheck($this->checkpoint());

        $this->assertSame(['AUSENTE', 'AUSENTE'], array_column($report, 'situacao'));
        $this->assertSame(['928341', '928342'], array_column($report, 'codigo_uasg'));
        $this->assertSame([[], []], array_column($report, 'ids_demandas'));
    }

    public function testPrecheckInconclusivoDeveExporFalhaSeguraIntegral(): void
    {
        $reader = $this->createMock(LeitorPncpPort::class);
        $reader->expects($this->once())
            ->method('localizarCandidatos')
            ->willReturn(ResultadoBuscaCandidatosPca::inconclusiva(new FalhaSegura(
                'PNCP_RESPOSTA_INCONCLUSIVA',
                'A resposta não permitiu conclusão segura.'
            )));
        $verifier = Sprint84PncpVerifier::fromPorts(
            $this->authenticator(),
            $reader,
            $this->createStub(EscritorPncpPort::class),
            new Cnpj('12345678000195'),
            $this->configuration()
        );

        try {
            $verifier->precheck($this->checkpoint());
            $this->fail('Busca inconclusiva deveria interromper o gate.');
        } catch (Sprint84GateException $failure) {
            $this->assertSame('PRECHECK_PNCP_BUSCA_INCONCLUSIVA', $failure->getErrorCode());
            $this->assertSame([
                'codigo' => 'PNCP_RESPOSTA_INCONCLUSIVA',
                'mensagem' => 'A resposta não permitiu conclusão segura.',
            ], $failure->getDetails()['falha']);
            $this->assertSame([], $failure->getDetails()['diagnostico_http']);
        }
    }

    public function testDeveExibirObservacaoPosteriorIntegralDasDuasUnidades(): void
    {
        $authenticator = $this->authenticator();
        $reader = $this->createMock(LeitorPncpPort::class);
        $reader->expects($this->exactly(2))
            ->method('observarPcaConhecido')
            ->willReturnOnConsecutiveCalls(
                ResultadoObservacaoPca::encontrado(
                    PcaObservado::ativo(new SequencialPca(71), [1011, 1012])
                ),
                ResultadoObservacaoPca::encontrado(
                    PcaObservado::ativo(new SequencialPca(72), [2021])
                )
            );
        $writer = $this->createStub(EscritorPncpPort::class);
        $verifier = Sprint84PncpVerifier::fromPorts(
            $authenticator,
            $reader,
            $writer,
            new Cnpj('12345678000195'),
            $this->configuration()
        );

        $observations = $verifier->verify($this->viewModel());

        $this->assertSame('ENCONTRADO', $observations[0]['situacao']);
        $this->assertSame(71, $observations[0]['sequencial_observado']);
        $this->assertSame([1011, 1012], $observations[0]['ids_demandas']);
        $this->assertSame('928342', $observations[1]['codigo_uasg']);
        $this->assertSame([2021], $observations[1]['ids_demandas']);
    }

    public function testLimpezaDeveExcluirSomenteSequencialLocalizadoEComprovarAusencia(): void
    {
        $reader = $this->createMock(LeitorPncpPort::class);
        $reader->expects($this->exactly(2))
            ->method('localizarCandidatos')
            ->willReturnOnConsecutiveCalls(
                ResultadoBuscaCandidatosPca::concluida([
                    PcaObservado::ativo(new SequencialPca(71), [1011]),
                ]),
                ResultadoBuscaCandidatosPca::concluida([])
            );
        $reader->expects($this->once())
            ->method('observarPcaConhecido')
            ->willReturn(ResultadoObservacaoPca::ausenciaDefinitiva());
        $writer = $this->createMock(EscritorPncpPort::class);
        $writer->expects($this->once())
            ->method('excluirPca')
            ->with(
                $this->anything(),
                $this->callback(
                    static fn ($command): bool => $command->getSequencialPca()->getValor() === 71
                        && $command->getUnidadeExterna()->getUasgExterna()->getCodigo() === '928341'
                )
            )
            ->willReturn(ResultadoEscritaPncp::aceita());
        $verifier = Sprint84PncpVerifier::fromPorts(
            $this->authenticator(),
            $reader,
            $writer,
            new Cnpj('12345678000195'),
            $this->configuration()
        );

        $report = $verifier->cleanup([71], $this->checkpoint());

        $this->assertSame(71, $report[0]['sequencial']);
        $this->assertTrue($report[0]['exclusao_executada']);
        $this->assertTrue($report[0]['ausencia_confirmada']);
    }

    private function authenticator(): AutenticadorPncpPort
    {
        $authenticator = $this->createMock(AutenticadorPncpPort::class);
        $authenticator->expects($this->once())
            ->method('autenticar')
            ->willReturn(ResultadoAutenticacaoPncp::autenticada(
                new SessaoPncpAutenticada('token-opaco-teste')
            ));

        return $authenticator;
    }

    /** @return array<string, mixed> */
    private function configuration(): array
    {
        return [
            'modo' => 'INDIVIDUAL',
            'ano' => 2027,
            'unidades' => [
                ['id_unidade_orcamentaria' => 101],
                ['id_unidade_orcamentaria' => 202],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function checkpoint(): array
    {
        return [
            'consultas' => [
                'projecao_operacional' => ['linhas' => []],
                'unidades_orcamentarias' => [
                    'linhas' => [
                        [
                            'CHAVE_UO_PLANO' => '101',
                            'IDENTIDADE_UO' => 'UO-101',
                            'ID_LOTACAO' => '101',
                            'DESCRICAO_LOTACAO' => 'Lotação 101',
                            'DESCRICAO_UO' => 'Unidade 101',
                            'CODIGO_UASG' => '928341',
                            'DESCRICAO_UASG' => 'UASG 101',
                        ],
                        [
                            'CHAVE_UO_PLANO' => '202',
                            'IDENTIDADE_UO' => 'UO-202',
                            'ID_LOTACAO' => '202',
                            'DESCRICAO_LOTACAO' => 'Lotação 202',
                            'DESCRICAO_UO' => 'Unidade 202',
                            'CODIGO_UASG' => '928342',
                            'DESCRICAO_UASG' => 'UASG 202',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function viewModel(): PublicarPcaViewModel
    {
        return new PublicarPcaViewModel(
            'CONCLUIDA',
            [
                new ResultadoUnidadeViewModel(
                    'UO-101', 'INDIVIDUAL', 2027, '928341', 'UASG 101',
                    'CONCLUIDO', 'PUBLICAR', 71, 2, 0, null, null, [], []
                ),
                new ResultadoUnidadeViewModel(
                    'UO-202', 'INDIVIDUAL', 2027, '928342', 'UASG 202',
                    'CONCLUIDO', 'PUBLICAR', 72, 1, 0, null, null, [], []
                ),
            ],
            null,
            null,
            2,
            2,
            0,
            '00000000-0000-4000-8000-000000000084'
        );
    }
}

final class TransporteSemIoVerifierSprint84 implements TransporteHttpPncp
{
    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        throw new \LogicException('I/O PNCP não esperado durante a composição.');
    }
}
