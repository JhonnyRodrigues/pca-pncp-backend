<?php

declare(strict_types=1);

namespace Tests\Application\UseCases\PublicarPca;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Enums\SituacaoPreparacaoPublicacaoPca;
use Semae\Pca\Application\Ports\AutenticadorPncpPort;
use Semae\Pca\Application\Ports\ContextoUsuarioPort;
use Semae\Pca\Application\Ports\EstrategiaPublicacaoPort;
use Semae\Pca\Application\Ports\UnidadeDeTrabalhoPort;
use Semae\Pca\Application\Results\ResultadoAdocaoEstrategia;
use Semae\Pca\Application\Results\ResultadoAutenticacaoPncp;
use Semae\Pca\Application\Results\ResultadoConsultaEstrategia;
use Semae\Pca\Application\Results\ResultadoFinalizacaoUnidadeTrabalho;
use Semae\Pca\Application\Results\ResultadoInicioUnidadeTrabalho;
use Semae\Pca\Application\Results\ResultadoResolucaoContextoUsuario;
use Semae\Pca\Application\UseCases\PublicarPca\PreparadorExecucaoPublicacaoPca;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaRequest;
use Semae\Pca\Application\ValueObjects\ChaveUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Application\ValueObjects\EstrategiaPublicacao;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Ports\PlanoRepository;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Representante;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class PreparadorExecucaoPublicacaoPcaTest extends TestCase
{
    /** @regra RN-CAD-003 @regra RN-CAD-004 @regra RN-CAD-005 @regra RN-PUB-009 */
    public function testDevePrepararExecucaoIndividualSemReconstruirFotografia(): void
    {
        $request = new PublicarPcaRequest(
            new Cnpj('12.345.678/0001-95'),
            new AnoExecucao((int) date('Y') + 1)
        );
        $identidade = $this->criarIdentidade($request->getAnoExecucao());
        $contexto = new ContextoUsuario(
            new Representante(709, 'Maria da Silva'),
            new Lotacao(159, 'Coordenadoria de Tecnologia')
        );
        $estrategia = EstrategiaPublicacao::individual();
        $sessao = new class implements SessaoPncp {};
        $estrategiaConfirmada = false;

        $contextoUsuario = $this->createMock(ContextoUsuarioPort::class);
        $contextoUsuario->expects($this->once())
            ->method('resolver')
            ->willReturn(ResultadoResolucaoContextoUsuario::resolvido($contexto));

        $planoRepository = $this->createMock(PlanoRepository::class);
        $planoRepository->expects($this->once())
            ->method('listarIdentidades')
            ->with($this->identicalTo($request->getAnoExecucao()))
            ->willReturn([$identidade]);
        $planoRepository->expects($this->never())
            ->method('reconstruirFotografiaVigente');
        $planoRepository->expects($this->never())
            ->method('confirmarSincronizacao');

        $estrategiaPublicacao = $this->createMock(EstrategiaPublicacaoPort::class);
        $estrategiaPublicacao->expects($this->once())
            ->method('consultarCorrente')
            ->with($this->identicalTo($request->getAnoExecucao()))
            ->willReturn(ResultadoConsultaEstrategia::disponivel($estrategia));
        $estrategiaPublicacao->expects($this->once())
            ->method('consultarAdotada')
            ->with($this->identicalTo($request->getAnoExecucao()))
            ->willReturn(ResultadoConsultaEstrategia::disponivel($estrategia));
        $estrategiaPublicacao->expects($this->once())
            ->method('adotar')
            ->with($this->identicalTo($request->getAnoExecucao()), $estrategia)
            ->willReturn(ResultadoAdocaoEstrategia::adotada());

        $unidadeDeTrabalho = $this->createMock(UnidadeDeTrabalhoPort::class);
        $unidadeDeTrabalho->expects($this->once())
            ->method('iniciar')
            ->with($this->callback(
                static fn (ChaveUnidadeTrabalho $chave): bool => $chave->equals(
                    ChaveUnidadeTrabalho::estrategiaAnual($request->getAnoExecucao())
                )
            ))
            ->willReturn(ResultadoInicioUnidadeTrabalho::iniciada());
        $unidadeDeTrabalho->expects($this->once())
            ->method('confirmar')
            ->willReturnCallback(
                static function () use (&$estrategiaConfirmada): ResultadoFinalizacaoUnidadeTrabalho {
                    $estrategiaConfirmada = true;

                    return ResultadoFinalizacaoUnidadeTrabalho::concluida();
                }
            );
        $unidadeDeTrabalho->expects($this->never())
            ->method('desfazer');

        $autenticador = $this->createMock(AutenticadorPncpPort::class);
        $autenticador->expects($this->once())
            ->method('autenticar')
            ->willReturnCallback(
                function () use (&$estrategiaConfirmada, $sessao): ResultadoAutenticacaoPncp {
                    $this->assertTrue($estrategiaConfirmada);

                    return ResultadoAutenticacaoPncp::autenticada($sessao);
                }
            );

        $resultado = (new PreparadorExecucaoPublicacaoPca(
            $contextoUsuario,
            $planoRepository,
            $estrategiaPublicacao,
            $unidadeDeTrabalho,
            $autenticador
        ))->preparar($request);

        $this->assertSame(SituacaoPreparacaoPublicacaoPca::PREPARADA, $resultado->getSituacao());
        $this->assertSame($contexto, $resultado->getContextoUsuario());
        $this->assertSame($estrategia, $resultado->getEstrategia());
        $this->assertSame($sessao, $resultado->getSessaoPncp());
        $this->assertCount(1, $resultado->getUnidadesExternas());
        $this->assertSame(
            $identidade,
            $resultado->getUnidadesExternas()[0]->getIdentidadePlano()
        );
    }

    /** @regra RN-PUB-010 @regra RN-PUB-011 */
    public function testDeveFormarUmaUnicaUnidadeConsolidadaParaTodoOExercicio(): void
    {
        $cenario = $this->criarCenarioBase([
            ['UO-01', '986789'],
            ['UO-02', '986790'],
        ]);
        $uasgConsolidadora = new Uasg('986700', 'UASG Consolidadora');
        $estrategia = EstrategiaPublicacao::consolidada($uasgConsolidadora);
        $sessao = new class implements SessaoPncp {};

        $this->configurarEstrategiaJaAdotada($cenario['estrategia'], $estrategia);
        $this->configurarUnidadeDeTrabalhoConcluida($cenario['unidadeDeTrabalho']);
        $cenario['autenticador']->expects($this->once())
            ->method('autenticar')
            ->willReturn(ResultadoAutenticacaoPncp::autenticada($sessao));

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame(SituacaoPreparacaoPublicacaoPca::PREPARADA, $resultado->getSituacao());
        $this->assertCount(1, $resultado->getUnidadesExternas());
        $unidade = $resultado->getUnidadesExternas()[0];
        $this->assertSame($cenario['request']->getAnoExecucao(), $unidade->getAnoExecucao());
        $this->assertSame($uasgConsolidadora, $unidade->getUasgExterna());
        $this->assertNull($unidade->getIdentidadePlano());
    }

    /** @regra RN-PUB-009 @regra RN-PUB-011 */
    public function testDeveFormarUmaUnidadeIndividualPorUoComUasgsDistintas(): void
    {
        $cenario = $this->criarCenarioBase([
            ['UO-01', '986789'],
            ['UO-02', '986790'],
        ]);
        $estrategia = EstrategiaPublicacao::individual();
        $sessao = new class implements SessaoPncp {};

        $this->configurarEstrategiaJaAdotada($cenario['estrategia'], $estrategia);
        $this->configurarUnidadeDeTrabalhoConcluida($cenario['unidadeDeTrabalho']);
        $cenario['autenticador']->expects($this->once())
            ->method('autenticar')
            ->willReturn(ResultadoAutenticacaoPncp::autenticada($sessao));

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame(SituacaoPreparacaoPublicacaoPca::PREPARADA, $resultado->getSituacao());
        $this->assertCount(2, $resultado->getUnidadesExternas());
        $this->assertSame(
            ['986789', '986790'],
            array_map(
                static fn (UnidadeExternaPublicacao $unidade): string =>
                    $unidade->getUasgExterna()->getCodigo(),
                $resultado->getUnidadesExternas()
            )
        );
    }

    public function testNaoDeveAdotarEstrategiaQuandoNaoExistiremIdentidades(): void
    {
        $cenario = $this->criarCenarioBase([]);

        $cenario['unidadeDeTrabalho']->expects($this->never())->method('iniciar');
        $cenario['unidadeDeTrabalho']->expects($this->never())->method('confirmar');
        $cenario['unidadeDeTrabalho']->expects($this->never())->method('desfazer');
        $cenario['estrategia']->expects($this->never())->method('consultarCorrente');
        $cenario['estrategia']->expects($this->never())->method('consultarAdotada');
        $cenario['estrategia']->expects($this->never())->method('adotar');
        $cenario['autenticador']->expects($this->never())->method('autenticar');

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame(
            SituacaoPreparacaoPublicacaoPca::SEM_UNIDADES,
            $resultado->getSituacao()
        );
        $this->assertSame([], $resultado->getUnidadesExternas());
        $this->assertNull($resultado->getFalha());
    }

    /** @regra RN-PUB-011 */
    public function testDeveDesfazerQuandoUosIndividuaisCompartilharemUasg(): void
    {
        $cenario = $this->criarCenarioBase([
            ['UO-01', '986789'],
            ['UO-02', '986789'],
        ]);
        $estrategia = EstrategiaPublicacao::individual();

        $cenario['estrategia']->expects($this->once())
            ->method('consultarCorrente')
            ->willReturn(ResultadoConsultaEstrategia::disponivel($estrategia));
        $cenario['estrategia']->expects($this->never())->method('consultarAdotada');
        $cenario['estrategia']->expects($this->never())->method('adotar');
        $this->configurarUnidadeDeTrabalhoDesfeita($cenario['unidadeDeTrabalho']);
        $cenario['autenticador']->expects($this->never())->method('autenticar');

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame(SituacaoPreparacaoPublicacaoPca::INTERROMPIDA, $resultado->getSituacao());
        $this->assertSame('UASG_INDIVIDUAL_COMPARTILHADA', $resultado->getFalha()?->getCodigo());
    }

    /** @regra RN-CAD-006 */
    public function testDeveDesfazerQuandoUmaUoForDescobertaMaisDeUmaVez(): void
    {
        $cenario = $this->criarCenarioBase([
            ['UO-01', '986789'],
            ['UO-01', '986790'],
        ]);
        $estrategia = EstrategiaPublicacao::individual();

        $cenario['estrategia']->expects($this->once())
            ->method('consultarCorrente')
            ->willReturn(ResultadoConsultaEstrategia::disponivel($estrategia));
        $cenario['estrategia']->expects($this->never())->method('consultarAdotada');
        $cenario['estrategia']->expects($this->never())->method('adotar');
        $this->configurarUnidadeDeTrabalhoDesfeita($cenario['unidadeDeTrabalho']);
        $cenario['autenticador']->expects($this->never())->method('autenticar');

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame(SituacaoPreparacaoPublicacaoPca::INTERROMPIDA, $resultado->getSituacao());
        $this->assertSame('UO_PARTICIPANTE_DUPLICADA', $resultado->getFalha()?->getCodigo());
    }

    public function testDeveDesfazerQuandoIdentidadeNaoPertencerAoExercicioSolicitado(): void
    {
        $outroAno = new AnoExecucao((int) date('Y'));
        $cenario = $this->criarCenarioBase([['UO-01', '986789', $outroAno]]);
        $estrategia = EstrategiaPublicacao::individual();

        $cenario['estrategia']->expects($this->once())
            ->method('consultarCorrente')
            ->willReturn(ResultadoConsultaEstrategia::disponivel($estrategia));
        $cenario['estrategia']->expects($this->never())->method('consultarAdotada');
        $cenario['estrategia']->expects($this->never())->method('adotar');
        $this->configurarUnidadeDeTrabalhoDesfeita($cenario['unidadeDeTrabalho']);
        $cenario['autenticador']->expects($this->never())->method('autenticar');

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame(SituacaoPreparacaoPublicacaoPca::INTERROMPIDA, $resultado->getSituacao());
        $this->assertSame('IDENTIDADE_PLANO_INVALIDA', $resultado->getFalha()?->getCodigo());
    }

    public function testDeveInterromperAntesDaDescobertaQuandoContextoNaoForResolvido(): void
    {
        $cenario = $this->criarCenarioSemExpectativas();
        $falha = new FalhaSegura('CONTEXTO_NAO_RESOLVIDO', 'Contexto não resolvido.');

        $cenario['contexto']->expects($this->once())
            ->method('resolver')
            ->willReturn(ResultadoResolucaoContextoUsuario::naoResolvido($falha));
        $cenario['repository']->expects($this->never())->method('listarIdentidades');
        $cenario['unidadeDeTrabalho']->expects($this->never())->method('iniciar');
        $cenario['estrategia']->expects($this->never())->method('consultarCorrente');
        $cenario['autenticador']->expects($this->never())->method('autenticar');

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame(SituacaoPreparacaoPublicacaoPca::INTERROMPIDA, $resultado->getSituacao());
        $this->assertSame($falha, $resultado->getFalha());
        $this->assertSame([], $resultado->getUnidadesExternas());
    }

    public function testDeveInterromperAntesDaDescobertaQuandoContextoFalhar(): void
    {
        $cenario = $this->criarCenarioSemExpectativas();
        $falha = new FalhaSegura('CONTEXTO_INDISPONIVEL', 'Contexto indisponível.');

        $cenario['contexto']->expects($this->once())
            ->method('resolver')
            ->willReturn(ResultadoResolucaoContextoUsuario::falhaSistemica($falha));
        $cenario['repository']->expects($this->never())->method('listarIdentidades');
        $cenario['unidadeDeTrabalho']->expects($this->never())->method('iniciar');
        $cenario['estrategia']->expects($this->never())->method('consultarCorrente');
        $cenario['autenticador']->expects($this->never())->method('autenticar');

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame(SituacaoPreparacaoPublicacaoPca::INTERROMPIDA, $resultado->getSituacao());
        $this->assertSame($falha, $resultado->getFalha());
    }

    public function testDeveInterromperQuandoAutenticacaoFalharAposConfirmarEstrategia(): void
    {
        $cenario = $this->criarCenarioBase();
        $estrategia = EstrategiaPublicacao::individual();
        $falha = new FalhaSegura('AUTENTICACAO_INDISPONIVEL', 'Autenticação indisponível.');
        $estrategiaConfirmada = false;

        $this->configurarEstrategiaJaAdotada($cenario['estrategia'], $estrategia);
        $cenario['unidadeDeTrabalho']->expects($this->once())
            ->method('iniciar')
            ->willReturn(ResultadoInicioUnidadeTrabalho::iniciada());
        $cenario['unidadeDeTrabalho']->expects($this->once())
            ->method('confirmar')
            ->willReturnCallback(
                static function () use (&$estrategiaConfirmada): ResultadoFinalizacaoUnidadeTrabalho {
                    $estrategiaConfirmada = true;

                    return ResultadoFinalizacaoUnidadeTrabalho::concluida();
                }
            );
        $cenario['unidadeDeTrabalho']->expects($this->never())->method('desfazer');
        $cenario['autenticador']->expects($this->once())
            ->method('autenticar')
            ->willReturnCallback(
                function () use (&$estrategiaConfirmada, $falha): ResultadoAutenticacaoPncp {
                    $this->assertTrue($estrategiaConfirmada);

                    return ResultadoAutenticacaoPncp::falhaSistemica($falha);
                }
            );

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame(SituacaoPreparacaoPublicacaoPca::INTERROMPIDA, $resultado->getSituacao());
        $this->assertSame($falha, $resultado->getFalha());
        $this->assertNull($resultado->getContextoUsuario());
        $this->assertNull($resultado->getEstrategia());
        $this->assertCount(1, $resultado->getUnidadesExternas());
        $this->assertSame(
            'UO-01',
            $resultado->getUnidadesExternas()[0]
                ->getIdentidadePlano()
                ?->getUnidadeOrcamentaria()
                ->getIdentidade()
                ->getValor()
        );
        $this->assertNull($resultado->getSessaoPncp());
    }

    public function testExcecaoNaAutenticacaoDevePreservarUnidadesJaFormadas(): void
    {
        $cenario = $this->criarCenarioBase();
        $estrategia = EstrategiaPublicacao::individual();

        $this->configurarEstrategiaJaAdotada($cenario['estrategia'], $estrategia);
        $this->configurarUnidadeDeTrabalhoConcluida($cenario['unidadeDeTrabalho']);
        $cenario['autenticador']->expects($this->once())
            ->method('autenticar')
            ->willThrowException(new RuntimeException('detalhe interno'));

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame(
            SituacaoPreparacaoPublicacaoPca::INTERROMPIDA,
            $resultado->getSituacao()
        );
        $this->assertSame('AUTENTICACAO_PNCP_FALHOU', $resultado->getFalha()?->getCodigo());
        $this->assertCount(1, $resultado->getUnidadesExternas());
        $this->assertSame(
            'UO-01',
            $resultado->getUnidadesExternas()[0]
                ->getIdentidadePlano()
                ?->getUnidadeOrcamentaria()
                ->getIdentidade()
                ->getValor()
        );
    }

    public function testDeveAdotarEstrategiaCorrenteQuandoAindaNaoExisteAdotada(): void
    {
        $cenario = $this->criarCenarioBase();
        $estrategia = EstrategiaPublicacao::individual();
        $sessao = new class implements SessaoPncp {};

        $cenario['estrategia']->expects($this->once())
            ->method('consultarCorrente')
            ->with($cenario['request']->getAnoExecucao())
            ->willReturn(ResultadoConsultaEstrategia::disponivel($estrategia));
        $cenario['estrategia']->expects($this->once())
            ->method('consultarAdotada')
            ->with($cenario['request']->getAnoExecucao())
            ->willReturn(ResultadoConsultaEstrategia::ausente());
        $cenario['estrategia']->expects($this->once())
            ->method('adotar')
            ->with($cenario['request']->getAnoExecucao(), $estrategia)
            ->willReturn(ResultadoAdocaoEstrategia::adotada());
        $this->configurarUnidadeDeTrabalhoConcluida($cenario['unidadeDeTrabalho']);
        $cenario['autenticador']->expects($this->once())
            ->method('autenticar')
            ->willReturn(ResultadoAutenticacaoPncp::autenticada($sessao));

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame(SituacaoPreparacaoPublicacaoPca::PREPARADA, $resultado->getSituacao());
        $this->assertSame($estrategia, $resultado->getEstrategia());
    }

    public function testDeveDesfazerEInterromperQuandoEstrategiaAdotadaDivergir(): void
    {
        $cenario = $this->criarCenarioBase();
        $corrente = EstrategiaPublicacao::individual();
        $adotada = EstrategiaPublicacao::consolidada(
            new Uasg('986700', 'UASG Consolidadora')
        );

        $cenario['estrategia']->expects($this->once())
            ->method('consultarCorrente')
            ->willReturn(ResultadoConsultaEstrategia::disponivel($corrente));
        $cenario['estrategia']->expects($this->once())
            ->method('consultarAdotada')
            ->willReturn(ResultadoConsultaEstrategia::disponivel($adotada));
        $cenario['estrategia']->expects($this->never())->method('adotar');
        $this->configurarUnidadeDeTrabalhoDesfeita($cenario['unidadeDeTrabalho']);
        $cenario['autenticador']->expects($this->never())->method('autenticar');

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame(
            SituacaoPreparacaoPublicacaoPca::INTERROMPIDA,
            $resultado->getSituacao()
        );
        $this->assertSame('ESTRATEGIA_ANUAL_DIVERGENTE', $resultado->getFalha()?->getCodigo());
        $this->assertSame([], $resultado->getUnidadesExternas());
    }

    public function testNaoDeveDesfazerQuandoExclusividadeDaEstrategiaNaoForObtida(): void
    {
        $cenario = $this->criarCenarioBase();
        $falha = new FalhaSegura('ESTRATEGIA_BLOQUEADA', 'Estratégia em processamento.');

        $cenario['unidadeDeTrabalho']->expects($this->once())
            ->method('iniciar')
            ->willReturn(ResultadoInicioUnidadeTrabalho::bloqueada($falha));
        $cenario['unidadeDeTrabalho']->expects($this->never())->method('confirmar');
        $cenario['unidadeDeTrabalho']->expects($this->never())->method('desfazer');
        $cenario['estrategia']->expects($this->never())->method('consultarCorrente');
        $cenario['estrategia']->expects($this->never())->method('consultarAdotada');
        $cenario['estrategia']->expects($this->never())->method('adotar');
        $cenario['autenticador']->expects($this->never())->method('autenticar');

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame(SituacaoPreparacaoPublicacaoPca::INTERROMPIDA, $resultado->getSituacao());
        $this->assertSame($falha, $resultado->getFalha());
    }

    public function testDeveDesfazerQuandoEstrategiaCorrenteForInvalida(): void
    {
        $cenario = $this->criarCenarioBase();
        $falha = new FalhaSegura('CONFIGURACAO_INVALIDA', 'Configuração inválida.');

        $cenario['estrategia']->expects($this->once())
            ->method('consultarCorrente')
            ->willReturn(ResultadoConsultaEstrategia::invalida($falha));
        $cenario['estrategia']->expects($this->never())->method('consultarAdotada');
        $cenario['estrategia']->expects($this->never())->method('adotar');
        $this->configurarUnidadeDeTrabalhoDesfeita($cenario['unidadeDeTrabalho']);
        $cenario['autenticador']->expects($this->never())->method('autenticar');

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame(SituacaoPreparacaoPublicacaoPca::INTERROMPIDA, $resultado->getSituacao());
        $this->assertSame($falha, $resultado->getFalha());
    }

    public function testEstrategiaCorrenteAusenteNaoDeveReceberDefault(): void
    {
        $cenario = $this->criarCenarioBase();

        $cenario['estrategia']->expects($this->once())
            ->method('consultarCorrente')
            ->willReturn(ResultadoConsultaEstrategia::ausente());
        $cenario['estrategia']->expects($this->never())->method('consultarAdotada');
        $cenario['estrategia']->expects($this->never())->method('adotar');
        $this->configurarUnidadeDeTrabalhoDesfeita($cenario['unidadeDeTrabalho']);
        $cenario['autenticador']->expects($this->never())->method('autenticar');

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame(SituacaoPreparacaoPublicacaoPca::INTERROMPIDA, $resultado->getSituacao());
        $this->assertSame('ESTRATEGIA_CORRENTE_AUSENTE', $resultado->getFalha()?->getCodigo());
    }

    public function testDeveDesfazerQuandoConsultaDaEstrategiaAdotadaFalhar(): void
    {
        $cenario = $this->criarCenarioBase();
        $estrategia = EstrategiaPublicacao::individual();
        $falha = new FalhaSegura('ESTRATEGIA_ADOTADA_INDISPONIVEL', 'Consulta indisponível.');

        $cenario['estrategia']->expects($this->once())
            ->method('consultarCorrente')
            ->willReturn(ResultadoConsultaEstrategia::disponivel($estrategia));
        $cenario['estrategia']->expects($this->once())
            ->method('consultarAdotada')
            ->willReturn(ResultadoConsultaEstrategia::falhaSistemica($falha));
        $cenario['estrategia']->expects($this->never())->method('adotar');
        $this->configurarUnidadeDeTrabalhoDesfeita($cenario['unidadeDeTrabalho']);
        $cenario['autenticador']->expects($this->never())->method('autenticar');

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame(SituacaoPreparacaoPublicacaoPca::INTERROMPIDA, $resultado->getSituacao());
        $this->assertSame($falha, $resultado->getFalha());
    }

    public function testDeveDesfazerQuandoAdocaoDaEstrategiaFalhar(): void
    {
        $cenario = $this->criarCenarioBase();
        $estrategia = EstrategiaPublicacao::individual();
        $falha = new FalhaSegura('FALHA_AO_ADOTAR', 'Não foi possível adotar a estratégia.');

        $cenario['estrategia']->expects($this->once())
            ->method('consultarCorrente')
            ->willReturn(ResultadoConsultaEstrategia::disponivel($estrategia));
        $cenario['estrategia']->expects($this->once())
            ->method('consultarAdotada')
            ->willReturn(ResultadoConsultaEstrategia::ausente());
        $cenario['estrategia']->expects($this->once())
            ->method('adotar')
            ->with($cenario['request']->getAnoExecucao(), $estrategia)
            ->willReturn(ResultadoAdocaoEstrategia::falha($falha));
        $this->configurarUnidadeDeTrabalhoDesfeita($cenario['unidadeDeTrabalho']);
        $cenario['autenticador']->expects($this->never())->method('autenticar');

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame(SituacaoPreparacaoPublicacaoPca::INTERROMPIDA, $resultado->getSituacao());
        $this->assertSame($falha, $resultado->getFalha());
    }

    public function testDeveDesfazerQuandoPropagacaoDaEstrategiaAdotadaFalhar(): void
    {
        $cenario = $this->criarCenarioBase();
        $estrategia = EstrategiaPublicacao::individual();
        $falha = new FalhaSegura(
            'ADOCAO_ESTRATEGIA_NAO_CONFIRMADA',
            'A releitura ainda encontrou versão pendente.'
        );

        $cenario['estrategia']->expects($this->once())
            ->method('consultarCorrente')
            ->willReturn(ResultadoConsultaEstrategia::disponivel($estrategia));
        $cenario['estrategia']->expects($this->once())
            ->method('consultarAdotada')
            ->willReturn(ResultadoConsultaEstrategia::disponivel($estrategia));
        $cenario['estrategia']->expects($this->once())
            ->method('adotar')
            ->with($cenario['request']->getAnoExecucao(), $estrategia)
            ->willReturn(ResultadoAdocaoEstrategia::falha($falha));
        $this->configurarUnidadeDeTrabalhoDesfeita($cenario['unidadeDeTrabalho']);
        $cenario['autenticador']->expects($this->never())->method('autenticar');

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame(SituacaoPreparacaoPublicacaoPca::INTERROMPIDA, $resultado->getSituacao());
        $this->assertSame($falha, $resultado->getFalha());
    }

    public function testDeveDesfazerQuandoConfirmacaoDaEstrategiaFalhar(): void
    {
        $cenario = $this->criarCenarioBase();
        $estrategia = EstrategiaPublicacao::individual();
        $falha = new FalhaSegura('COMMIT_FALHOU', 'Não foi possível confirmar a estratégia.');

        $this->configurarEstrategiaJaAdotada($cenario['estrategia'], $estrategia);
        $cenario['unidadeDeTrabalho']->expects($this->once())
            ->method('iniciar')
            ->willReturn(ResultadoInicioUnidadeTrabalho::iniciada());
        $cenario['unidadeDeTrabalho']->expects($this->once())
            ->method('confirmar')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::falhaSistemica($falha));
        $cenario['unidadeDeTrabalho']->expects($this->once())
            ->method('desfazer')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());
        $cenario['autenticador']->expects($this->never())->method('autenticar');

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame(SituacaoPreparacaoPublicacaoPca::INTERROMPIDA, $resultado->getSituacao());
        $this->assertSame($falha, $resultado->getFalha());
    }

    public function testFalhaAoDesfazerDevePrevalecerSobreFalhaAnterior(): void
    {
        $cenario = $this->criarCenarioBase();
        $falhaConsulta = new FalhaSegura('CONFIGURACAO_INVALIDA', 'Configuração inválida.');
        $falhaRollback = new FalhaSegura('ROLLBACK_FALHOU', 'Não foi possível desfazer.');

        $cenario['estrategia']->expects($this->once())
            ->method('consultarCorrente')
            ->willReturn(ResultadoConsultaEstrategia::invalida($falhaConsulta));
        $cenario['estrategia']->expects($this->never())->method('consultarAdotada');
        $cenario['unidadeDeTrabalho']->expects($this->once())
            ->method('iniciar')
            ->willReturn(ResultadoInicioUnidadeTrabalho::iniciada());
        $cenario['unidadeDeTrabalho']->expects($this->never())->method('confirmar');
        $cenario['unidadeDeTrabalho']->expects($this->once())
            ->method('desfazer')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::falhaSistemica($falhaRollback));
        $cenario['autenticador']->expects($this->never())->method('autenticar');

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame($falhaRollback, $resultado->getFalha());
    }

    public function testExcecaoNaConsultaCorrenteDeveDesfazerEInterromper(): void
    {
        $cenario = $this->criarCenarioBase();

        $cenario['estrategia']->expects($this->once())
            ->method('consultarCorrente')
            ->willThrowException(new RuntimeException('detalhe interno'));
        $cenario['estrategia']->expects($this->never())->method('consultarAdotada');
        $this->configurarUnidadeDeTrabalhoDesfeita($cenario['unidadeDeTrabalho']);
        $cenario['autenticador']->expects($this->never())->method('autenticar');

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame(SituacaoPreparacaoPublicacaoPca::INTERROMPIDA, $resultado->getSituacao());
        $this->assertSame('PREPARACAO_ESTRATEGIA_FALHOU', $resultado->getFalha()?->getCodigo());
    }

    public function testExcecaoNaConsultaAdotadaDeveDesfazerEInterromper(): void
    {
        $cenario = $this->criarCenarioBase();
        $estrategia = EstrategiaPublicacao::individual();

        $cenario['estrategia']->expects($this->once())
            ->method('consultarCorrente')
            ->willReturn(ResultadoConsultaEstrategia::disponivel($estrategia));
        $cenario['estrategia']->expects($this->once())
            ->method('consultarAdotada')
            ->willThrowException(new RuntimeException('detalhe interno'));
        $this->configurarUnidadeDeTrabalhoDesfeita($cenario['unidadeDeTrabalho']);
        $cenario['autenticador']->expects($this->never())->method('autenticar');

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame('PREPARACAO_ESTRATEGIA_FALHOU', $resultado->getFalha()?->getCodigo());
    }

    public function testExcecaoNaAdocaoDeveDesfazerEInterromper(): void
    {
        $cenario = $this->criarCenarioBase();
        $estrategia = EstrategiaPublicacao::individual();

        $cenario['estrategia']->method('consultarCorrente')
            ->willReturn(ResultadoConsultaEstrategia::disponivel($estrategia));
        $cenario['estrategia']->method('consultarAdotada')
            ->willReturn(ResultadoConsultaEstrategia::ausente());
        $cenario['estrategia']->expects($this->once())
            ->method('adotar')
            ->willThrowException(new RuntimeException('detalhe interno'));
        $this->configurarUnidadeDeTrabalhoDesfeita($cenario['unidadeDeTrabalho']);
        $cenario['autenticador']->expects($this->never())->method('autenticar');

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame('PREPARACAO_ESTRATEGIA_FALHOU', $resultado->getFalha()?->getCodigo());
    }

    public function testExcecaoNaConfirmacaoDeveDesfazerEInterromper(): void
    {
        $cenario = $this->criarCenarioBase();
        $estrategia = EstrategiaPublicacao::individual();

        $this->configurarEstrategiaJaAdotada($cenario['estrategia'], $estrategia);
        $cenario['unidadeDeTrabalho']->expects($this->once())
            ->method('iniciar')
            ->willReturn(ResultadoInicioUnidadeTrabalho::iniciada());
        $cenario['unidadeDeTrabalho']->expects($this->once())
            ->method('confirmar')
            ->willThrowException(new RuntimeException('detalhe interno'));
        $cenario['unidadeDeTrabalho']->expects($this->once())
            ->method('desfazer')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());
        $cenario['autenticador']->expects($this->never())->method('autenticar');

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame('PREPARACAO_ESTRATEGIA_FALHOU', $resultado->getFalha()?->getCodigo());
    }

    public function testExcecaoNoDesfazimentoDaEstrategiaDevePrevalecer(): void
    {
        $cenario = $this->criarCenarioBase();

        $cenario['estrategia']->method('consultarCorrente')
            ->willThrowException(new RuntimeException('detalhe interno'));
        $cenario['unidadeDeTrabalho']->expects($this->once())
            ->method('iniciar')
            ->willReturn(ResultadoInicioUnidadeTrabalho::iniciada());
        $cenario['unidadeDeTrabalho']->expects($this->never())->method('confirmar');
        $cenario['unidadeDeTrabalho']->expects($this->once())
            ->method('desfazer')
            ->willThrowException(new RuntimeException('rollback indisponível'));
        $cenario['autenticador']->expects($this->never())->method('autenticar');

        $resultado = $this->criarPreparador($cenario)->preparar($cenario['request']);

        $this->assertSame('DESFAZIMENTO_ESTRATEGIA_FALHOU', $resultado->getFalha()?->getCodigo());
    }

    /**
     * @return array{
     *     request: PublicarPcaRequest,
     *     contexto: ContextoUsuarioPort,
     *     repository: PlanoRepository,
     *     estrategia: EstrategiaPublicacaoPort,
     *     unidadeDeTrabalho: UnidadeDeTrabalhoPort,
     *     autenticador: AutenticadorPncpPort
     * }
     */
    private function criarCenarioBase(array $unidades = [['UO-01', '986789']]): array
    {
        $cenario = $this->criarCenarioSemExpectativas();
        $request = $cenario['request'];
        $contexto = $cenario['contexto'];
        $contexto->expects($this->once())
            ->method('resolver')
            ->willReturn(ResultadoResolucaoContextoUsuario::resolvido(
                new ContextoUsuario(
                    new Representante(709, 'Maria da Silva'),
                    new Lotacao(159, 'Coordenadoria de Tecnologia')
                )
            ));
        $repository = $cenario['repository'];
        $repository->expects($this->once())
            ->method('listarIdentidades')
            ->with($request->getAnoExecucao())
            ->willReturn(array_map(
                fn (array $unidade): IdentidadePlano => $this->criarIdentidade(
                    $unidade[2] ?? $request->getAnoExecucao(),
                    $unidade[0],
                    $unidade[1]
                ),
                $unidades
            ));

        return [
            'request' => $request,
            'contexto' => $contexto,
            'repository' => $repository,
            'estrategia' => $cenario['estrategia'],
            'unidadeDeTrabalho' => $cenario['unidadeDeTrabalho'],
            'autenticador' => $cenario['autenticador'],
        ];
    }

    /**
     * @return array{
     *     request: PublicarPcaRequest,
     *     contexto: ContextoUsuarioPort,
     *     repository: PlanoRepository,
     *     estrategia: EstrategiaPublicacaoPort,
     *     unidadeDeTrabalho: UnidadeDeTrabalhoPort,
     *     autenticador: AutenticadorPncpPort
     * }
     */
    private function criarCenarioSemExpectativas(): array
    {
        return [
            'request' => new PublicarPcaRequest(
                new Cnpj('12.345.678/0001-95'),
                new AnoExecucao((int) date('Y') + 1)
            ),
            'contexto' => $this->createMock(ContextoUsuarioPort::class),
            'repository' => $this->createMock(PlanoRepository::class),
            'estrategia' => $this->createMock(EstrategiaPublicacaoPort::class),
            'unidadeDeTrabalho' => $this->createMock(UnidadeDeTrabalhoPort::class),
            'autenticador' => $this->createMock(AutenticadorPncpPort::class),
        ];
    }

    private function criarPreparador(array $cenario): PreparadorExecucaoPublicacaoPca
    {
        return new PreparadorExecucaoPublicacaoPca(
            $cenario['contexto'],
            $cenario['repository'],
            $cenario['estrategia'],
            $cenario['unidadeDeTrabalho'],
            $cenario['autenticador']
        );
    }

    private function configurarUnidadeDeTrabalhoConcluida(
        UnidadeDeTrabalhoPort $unidadeDeTrabalho
    ): void {
        $unidadeDeTrabalho->expects($this->once())
            ->method('iniciar')
            ->willReturn(ResultadoInicioUnidadeTrabalho::iniciada());
        $unidadeDeTrabalho->expects($this->once())
            ->method('confirmar')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());
        $unidadeDeTrabalho->expects($this->never())->method('desfazer');
    }

    private function configurarUnidadeDeTrabalhoDesfeita(
        UnidadeDeTrabalhoPort $unidadeDeTrabalho
    ): void {
        $unidadeDeTrabalho->expects($this->once())
            ->method('iniciar')
            ->willReturn(ResultadoInicioUnidadeTrabalho::iniciada());
        $unidadeDeTrabalho->expects($this->never())->method('confirmar');
        $unidadeDeTrabalho->expects($this->once())
            ->method('desfazer')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());
    }

    private function configurarEstrategiaJaAdotada(
        EstrategiaPublicacaoPort $port,
        EstrategiaPublicacao $estrategia
    ): void {
        $port->expects($this->once())
            ->method('consultarCorrente')
            ->willReturn(ResultadoConsultaEstrategia::disponivel($estrategia));
        $port->expects($this->once())
            ->method('consultarAdotada')
            ->willReturn(ResultadoConsultaEstrategia::disponivel($estrategia));
        $port->expects($this->once())
            ->method('adotar')
            ->willReturn(ResultadoAdocaoEstrategia::adotada());
    }

    private function criarIdentidade(
        AnoExecucao $anoExecucao,
        string $identidadeUo = 'UO-01',
        string $codigoUasg = '986789'
    ): IdentidadePlano
    {
        return new IdentidadePlano(
            new UnidadeOrcamentaria(
                new IdentidadeUnidadeOrcamentaria($identidadeUo),
                new Lotacao(10, 'Diretoria'),
                new Uasg($codigoUasg, 'UASG da UO'),
                'Unidade Orçamentária'
            ),
            $anoExecucao
        );
    }
}
