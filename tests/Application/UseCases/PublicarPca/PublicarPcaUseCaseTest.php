<?php

declare(strict_types=1);

namespace Tests\Application\UseCases\PublicarPca;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\Pca\Application\Contracts\ProcessadorUnidade;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Enums\SituacaoPublicacaoPca;
use Semae\Pca\Application\Ports\AutenticadorPncp;
use Semae\Pca\Application\Ports\ContextoUsuarioPort;
use Semae\Pca\Application\Ports\EstrategiaPublicacaoPort;
use Semae\Pca\Application\Ports\UnidadeDeTrabalhoPort;
use Semae\Pca\Application\Results\DesfechoProcessamentoUnidade;
use Semae\Pca\Application\Results\ResultadoAutenticacaoPncp;
use Semae\Pca\Application\Results\ResultadoConsultaEstrategia;
use Semae\Pca\Application\Results\ResultadoFinalizacaoUnidadeTrabalho;
use Semae\Pca\Application\Results\ResultadoInicioUnidadeTrabalho;
use Semae\Pca\Application\Results\ResultadoProcessamentoUnidade;
use Semae\Pca\Application\Results\ResultadoResolucaoContextoUsuario;
use Semae\Pca\Application\UseCases\PublicarPca\CoordenadorVarreduraUnidades;
use Semae\Pca\Application\UseCases\PublicarPca\PreparadorExecucaoPublicacaoPca;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaRequest;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaUseCase;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Application\ValueObjects\EstrategiaPublicacao;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;
use Semae\Pca\Domain\Ports\PlanoRepository;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Representante;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class PublicarPcaUseCaseTest extends TestCase
{
    public function testZeroUnidadesDeveConcluirSemVarreduraEComContagensZeradas(): void
    {
        $request = $this->criarRequest();
        $processador = new ProcessadorFakeUseCase([]);
        $useCase = $this->criarUseCaseSemUnidades($request, $processador);
        $correlacao = new CorrelacaoExecucao('corr-zero');

        $response = $useCase->executar($request, $correlacao);

        $this->assertSame(SituacaoPublicacaoPca::CONCLUIDA, $response->getSituacao());
        $this->assertSame([], $response->getResultadosUnitarios());
        $this->assertSame(0, $response->getQuantidadePlanejadas());
        $this->assertSame(0, $response->getQuantidadeVisitadas());
        $this->assertSame(0, $response->getQuantidadeNaoProcessadas());
        $this->assertSame($correlacao, $response->getCorrelacao());
        $this->assertSame(0, $processador->chamadas);
    }

    public function testUmaUnidadeConcluidaDeveProduzirResponseGlobalConcluida(): void
    {
        $request = $this->criarRequest();
        $identidade = $this->criarIdentidade($request->getAnoExecucao(), 'uo-101', '928344');
        $unidade = UnidadeExternaPublicacao::individual($identidade);
        $resultado = ResultadoProcessamentoUnidade::concluido(
            $unidade,
            DecisaoProcessamentoPlano::PUBLICAR,
            new SequencialPca(88),
            1,
            0
        );
        $processador = new ProcessadorFakeUseCase([
            DesfechoProcessamentoUnidade::resultadoUnitario($resultado),
        ]);
        $useCase = $this->criarUseCasePreparado($request, [$identidade], $processador);

        $response = $useCase->executar($request, new CorrelacaoExecucao('corr-individual'));

        $this->assertSame(SituacaoPublicacaoPca::CONCLUIDA, $response->getSituacao());
        $this->assertSame([$resultado], $response->getResultadosUnitarios());
        $this->assertSame(1, $response->getQuantidadePlanejadas());
        $this->assertSame(1, $response->getQuantidadeVisitadas());
        $this->assertSame(0, $response->getQuantidadeNaoProcessadas());
    }

    public function testVariasUnidadesSemPendenciasDevemProduzirResponseGlobalConcluida(): void
    {
        $request = $this->criarRequest();
        $identidades = [
            $this->criarIdentidade($request->getAnoExecucao(), 'uo-101', '928344'),
            $this->criarIdentidade($request->getAnoExecucao(), 'uo-102', '928345'),
        ];
        $primeiro = ResultadoProcessamentoUnidade::semAlteracao(
            UnidadeExternaPublicacao::individual($identidades[0])
        );
        $segundo = ResultadoProcessamentoUnidade::semAlteracao(
            UnidadeExternaPublicacao::individual($identidades[1])
        );
        $processador = new ProcessadorFakeUseCase([
            DesfechoProcessamentoUnidade::resultadoUnitario($primeiro),
            DesfechoProcessamentoUnidade::resultadoUnitario($segundo),
        ]);
        $useCase = $this->criarUseCasePreparado($request, $identidades, $processador);

        $response = $useCase->executar($request, new CorrelacaoExecucao('corr-varias'));

        $this->assertSame(SituacaoPublicacaoPca::CONCLUIDA, $response->getSituacao());
        $this->assertSame([$primeiro, $segundo], $response->getResultadosUnitarios());
        $this->assertSame(2, $response->getQuantidadePlanejadas());
        $this->assertSame(2, $response->getQuantidadeVisitadas());
        $this->assertSame(0, $response->getQuantidadeNaoProcessadas());
    }

    public function testFalhaReconciliavelDeveProduzirResponseGlobalComPendencias(): void
    {
        $request = $this->criarRequest();
        $identidade = $this->criarIdentidade($request->getAnoExecucao(), 'uo-101', '928344');
        $unidade = UnidadeExternaPublicacao::individual($identidade);
        $resultado = ResultadoProcessamentoUnidade::falhaReconciliavel(
            $unidade,
            DecisaoProcessamentoPlano::PUBLICAR,
            null,
            1,
            0,
            new FalhaSegura('CONVERGENCIA_PENDENTE', 'Convergência pendente.')
        );
        $processador = new ProcessadorFakeUseCase([
            DesfechoProcessamentoUnidade::resultadoUnitario($resultado),
        ]);
        $useCase = $this->criarUseCasePreparado($request, [$identidade], $processador);

        $response = $useCase->executar($request, new CorrelacaoExecucao('corr-reconciliavel'));

        $this->assertSame(
            SituacaoPublicacaoPca::CONCLUIDA_COM_PENDENCIAS,
            $response->getSituacao()
        );
        $this->assertSame([$resultado], $response->getResultadosUnitarios());
        $this->assertNull($response->getFalhaGlobal());
        $this->assertSame(1, $response->getQuantidadeVisitadas());
    }

    public function testVarreduraComBloqueioDeveConcluirComPendenciasEPreservarCorrelacao(): void
    {
        $request = $this->criarRequest();
        $identidades = [
            $this->criarIdentidade($request->getAnoExecucao(), 'uo-101', '928344'),
            $this->criarIdentidade($request->getAnoExecucao(), 'uo-102', '928345'),
        ];
        $primeira = UnidadeExternaPublicacao::individual($identidades[0]);
        $segunda = UnidadeExternaPublicacao::individual($identidades[1]);
        $semAlteracao = ResultadoProcessamentoUnidade::semAlteracao($primeira);
        $bloqueado = ResultadoProcessamentoUnidade::bloqueado(
            $segunda,
            null,
            null,
            0,
            0,
            new FalhaSegura('UNIDADE_BLOQUEADA', 'Unidade bloqueada.')
        );
        $processador = new ProcessadorFakeUseCase([
            DesfechoProcessamentoUnidade::resultadoUnitario($semAlteracao),
            DesfechoProcessamentoUnidade::resultadoUnitario($bloqueado),
        ]);
        $useCase = $this->criarUseCasePreparado($request, $identidades, $processador);
        $correlacao = new CorrelacaoExecucao('corr-pendencia');

        $response = $useCase->executar($request, $correlacao);

        $this->assertSame(
            SituacaoPublicacaoPca::CONCLUIDA_COM_PENDENCIAS,
            $response->getSituacao()
        );
        $this->assertSame([$semAlteracao, $bloqueado], $response->getResultadosUnitarios());
        $this->assertSame(2, $response->getQuantidadePlanejadas());
        $this->assertSame(2, $response->getQuantidadeVisitadas());
        $this->assertSame(0, $response->getQuantidadeNaoProcessadas());
        $this->assertSame($correlacao, $response->getCorrelacao());
        $this->assertSame($request->getCnpj(), $processador->cnpjRecebido);
    }

    public function testInterrupcaoSistemicaDeveContarUnidadeCausadoraEPreservarResultadoAnterior(): void
    {
        $request = $this->criarRequest();
        $identidades = [
            $this->criarIdentidade($request->getAnoExecucao(), 'uo-101', '928344'),
            $this->criarIdentidade($request->getAnoExecucao(), 'uo-102', '928345'),
            $this->criarIdentidade($request->getAnoExecucao(), 'uo-103', '928346'),
        ];
        $primeira = UnidadeExternaPublicacao::individual($identidades[0]);
        $resultadoAnterior = ResultadoProcessamentoUnidade::semAlteracao($primeira);
        $falha = new FalhaSegura('PNCP_INDISPONIVEL', 'PNCP indisponível.');
        $processador = new ProcessadorFakeUseCase([
            DesfechoProcessamentoUnidade::resultadoUnitario($resultadoAnterior),
            DesfechoProcessamentoUnidade::falhaSistemica($falha),
        ]);
        $useCase = $this->criarUseCasePreparado($request, $identidades, $processador);
        $correlacao = new CorrelacaoExecucao('corr-interrompida');

        $response = $useCase->executar($request, $correlacao);

        $this->assertSame(SituacaoPublicacaoPca::INTERROMPIDA, $response->getSituacao());
        $this->assertSame([$resultadoAnterior], $response->getResultadosUnitarios());
        $this->assertSame($falha, $response->getFalhaGlobal());
        $this->assertSame(3, $response->getQuantidadePlanejadas());
        $this->assertSame(2, $response->getQuantidadeVisitadas());
        $this->assertSame(1, $response->getQuantidadeNaoProcessadas());
        $this->assertSame($correlacao, $response->getCorrelacao());
    }

    public function testFalhaDeAutenticacaoDevePreservarUnidadesPlanejadasSemVarrer(): void
    {
        $request = $this->criarRequest();
        $identidade = $this->criarIdentidade(
            $request->getAnoExecucao(),
            'uo-101',
            '928344'
        );
        $processador = new ProcessadorFakeUseCase([]);
        $falha = new FalhaSegura('AUTENTICACAO_FALHOU', 'Autenticação falhou.');
        $useCase = $this->criarUseCasePreparado(
            $request,
            [$identidade],
            $processador,
            ResultadoAutenticacaoPncp::falhaSistemica($falha)
        );
        $correlacao = new CorrelacaoExecucao('corr-preparacao');

        $response = $useCase->executar($request, $correlacao);

        $this->assertSame(SituacaoPublicacaoPca::INTERROMPIDA, $response->getSituacao());
        $this->assertSame($falha, $response->getFalhaGlobal());
        $this->assertSame(1, $response->getQuantidadePlanejadas());
        $this->assertSame(0, $response->getQuantidadeVisitadas());
        $this->assertSame(1, $response->getQuantidadeNaoProcessadas());
        $this->assertSame([], $response->getResultadosUnitarios());
        $this->assertSame(0, $processador->chamadas);
    }

    /** @regra RN-PUB-010 */
    public function testModoConsolidadoDeveProduzirUmaUnicaUnidadeEUmUnicoResultado(): void
    {
        $request = $this->criarRequest();
        $identidades = [
            $this->criarIdentidade($request->getAnoExecucao(), 'uo-101', '928344'),
            $this->criarIdentidade($request->getAnoExecucao(), 'uo-102', '928345'),
        ];
        $uasgConsolidadora = new Uasg('986700', 'UASG Consolidadora');
        $unidadeConsolidada = UnidadeExternaPublicacao::consolidada(
            $request->getAnoExecucao(),
            $uasgConsolidadora
        );
        $resultado = ResultadoProcessamentoUnidade::semAlteracao($unidadeConsolidada);
        $processador = new ProcessadorFakeUseCase([
            DesfechoProcessamentoUnidade::resultadoUnitario($resultado),
        ]);
        $useCase = $this->criarUseCasePreparado(
            $request,
            $identidades,
            $processador,
            null,
            EstrategiaPublicacao::consolidada($uasgConsolidadora)
        );

        $response = $useCase->executar(
            $request,
            new CorrelacaoExecucao('corr-consolidada')
        );

        $this->assertSame(SituacaoPublicacaoPca::CONCLUIDA, $response->getSituacao());
        $this->assertSame([$resultado], $response->getResultadosUnitarios());
        $this->assertSame(1, $response->getQuantidadePlanejadas());
        $this->assertSame(1, $response->getQuantidadeVisitadas());
        $this->assertSame(1, $processador->chamadas);
    }

    public function testExcecaoInesperadaNaPreparacaoDeveVirarInterrupcaoSegura(): void
    {
        $request = $this->criarRequest();
        $contexto = $this->createMock(ContextoUsuarioPort::class);
        $repository = $this->createMock(PlanoRepository::class);
        $contexto->method('resolver')->willReturn(ResultadoResolucaoContextoUsuario::resolvido(
            $this->criarContextoUsuario()
        ));
        $repository->method('listarIdentidades')->willThrowException(new RuntimeException('segredo'));
        $processador = new ProcessadorFakeUseCase([]);
        $useCase = new PublicarPcaUseCase(
            new PreparadorExecucaoPublicacaoPca(
                $contexto,
                $repository,
                $this->createMock(EstrategiaPublicacaoPort::class),
                $this->createMock(UnidadeDeTrabalhoPort::class),
                $this->createMock(AutenticadorPncp::class)
            ),
            new CoordenadorVarreduraUnidades($processador)
        );
        $correlacao = new CorrelacaoExecucao('corr-excecao');

        $response = $useCase->executar($request, $correlacao);

        $this->assertSame(SituacaoPublicacaoPca::INTERROMPIDA, $response->getSituacao());
        $this->assertSame('PREPARACAO_PUBLICACAO_FALHOU', $response->getFalhaGlobal()?->getCodigo());
        $this->assertSame(0, $response->getQuantidadePlanejadas());
        $this->assertSame($correlacao, $response->getCorrelacao());
    }

    private function criarUseCaseSemUnidades(
        PublicarPcaRequest $request,
        ProcessadorFakeUseCase $processador
    ): PublicarPcaUseCase {
        $contexto = $this->createMock(ContextoUsuarioPort::class);
        $repository = $this->createMock(PlanoRepository::class);
        $contexto->expects($this->once())
            ->method('resolver')
            ->willReturn(ResultadoResolucaoContextoUsuario::resolvido(
                $this->criarContextoUsuario()
            ));
        $repository->expects($this->once())
            ->method('listarIdentidades')
            ->with($request->getAnoExecucao())
            ->willReturn([]);

        return new PublicarPcaUseCase(
            new PreparadorExecucaoPublicacaoPca(
                $contexto,
                $repository,
                $this->createMock(EstrategiaPublicacaoPort::class),
                $this->createMock(UnidadeDeTrabalhoPort::class),
                $this->createMock(AutenticadorPncp::class)
            ),
            new CoordenadorVarreduraUnidades($processador)
        );
    }

    /** @param IdentidadePlano[] $identidades */
    private function criarUseCasePreparado(
        PublicarPcaRequest $request,
        array $identidades,
        ProcessadorFakeUseCase $processador,
        ?ResultadoAutenticacaoPncp $resultadoAutenticacao = null,
        ?EstrategiaPublicacao $estrategia = null
    ): PublicarPcaUseCase {
        $contexto = $this->createMock(ContextoUsuarioPort::class);
        $repository = $this->createMock(PlanoRepository::class);
        $estrategiaPort = $this->createMock(EstrategiaPublicacaoPort::class);
        $unidadeTrabalho = $this->createMock(UnidadeDeTrabalhoPort::class);
        $autenticador = $this->createMock(AutenticadorPncp::class);
        $estrategia ??= EstrategiaPublicacao::individual();
        $contexto->method('resolver')->willReturn(ResultadoResolucaoContextoUsuario::resolvido(
            $this->criarContextoUsuario()
        ));
        $repository->method('listarIdentidades')
            ->with($request->getAnoExecucao())
            ->willReturn($identidades);
        $unidadeTrabalho->method('iniciar')
            ->willReturn(ResultadoInicioUnidadeTrabalho::iniciada());
        $unidadeTrabalho->method('confirmar')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());
        $estrategiaPort->method('consultarCorrente')
            ->willReturn(ResultadoConsultaEstrategia::disponivel($estrategia));
        $estrategiaPort->method('consultarAdotada')
            ->willReturn(ResultadoConsultaEstrategia::disponivel($estrategia));
        $resultadoAutenticacao ??= ResultadoAutenticacaoPncp::autenticada(
            new SessaoPncpFakeUseCase()
        );
        $autenticador->method('autenticar')->willReturn($resultadoAutenticacao);

        return new PublicarPcaUseCase(
            new PreparadorExecucaoPublicacaoPca(
                $contexto,
                $repository,
                $estrategiaPort,
                $unidadeTrabalho,
                $autenticador
            ),
            new CoordenadorVarreduraUnidades($processador)
        );
    }

    private function criarRequest(): PublicarPcaRequest
    {
        return new PublicarPcaRequest(
            new Cnpj('12.345.678/0001-95'),
            new AnoExecucao((int) date('Y') + 1)
        );
    }

    private function criarContextoUsuario(): ContextoUsuario
    {
        return new ContextoUsuario(
            new Representante(709, 'Maria da Silva'),
            new Lotacao(159, 'Coordenadoria de Tecnologia')
        );
    }

    private function criarIdentidade(
        AnoExecucao $ano,
        string $idUo,
        string $uasg
    ): IdentidadePlano {
        return new IdentidadePlano(
            new UnidadeOrcamentaria(
                new IdentidadeUnidadeOrcamentaria($idUo),
                new Lotacao(101, 'Diretoria Administrativa'),
                new Uasg($uasg, 'UASG da UO'),
                'Diretoria Administrativa'
            ),
            $ano
        );
    }
}

final class SessaoPncpFakeUseCase implements SessaoPncp
{
}

final class ProcessadorFakeUseCase implements ProcessadorUnidade
{
    public int $chamadas = 0;
    public ?Cnpj $cnpjRecebido = null;

    /** @param DesfechoProcessamentoUnidade[] $desfechos */
    public function __construct(private readonly array $desfechos) {}

    public function processar(
        ContextoUsuario $contextoUsuario,
        SessaoPncp $sessao,
        Cnpj $cnpj,
        UnidadeExternaPublicacao $unidade
    ): DesfechoProcessamentoUnidade {
        $this->cnpjRecebido = $cnpj;

        return $this->desfechos[$this->chamadas++];
    }
}
