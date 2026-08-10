<?php

declare(strict_types=1);

namespace Tests\Application\UseCases\PublicarPca;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Enums\SituacaoProcessamentoUnidade;
use Semae\Pca\Application\Ports\EstadoOperacionalPublicacaoPort;
use Semae\Pca\Application\Ports\EvidenciaCancelamentoHomologadoPort;
use Semae\Pca\Application\Ports\EscritorPncp;
use Semae\Pca\Application\Ports\LeitorPncp;
use Semae\Pca\Application\Ports\RegistroAuditoriaPort;
use Semae\Pca\Application\Ports\UnidadeDeTrabalhoPort;
use Semae\Pca\Application\Results\ResultadoBuscaCandidatosPca;
use Semae\Pca\Application\Results\ResultadoEscritaPncp;
use Semae\Pca\Application\Results\ResultadoFinalizacaoUnidadeTrabalho;
use Semae\Pca\Application\Results\ResultadoObservacaoPca;
use Semae\Pca\Application\Results\ResultadoPersistenciaLocal;
use Semae\Pca\Application\Results\ResultadoPublicacaoPncp;
use Semae\Pca\Application\UseCases\PublicarPca\ExecutorComandosUnidade;
use Semae\Pca\Application\UseCases\PublicarPca\FinalizadorProcessamentoUnidadeInterno;
use Semae\Pca\Application\ValueObjects\ContextoFinalizacaoProcessamentoUnidade;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\IntencaoRegistroHistorico;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\Demanda;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\ProcedimentoPublicacaoPca;
use Semae\Pca\Domain\Enums\SituacaoDemanda;
use Semae\Pca\Domain\Policies\PoliticaProcessamentoPlano;
use Semae\Pca\Domain\Policies\ResultadoDecisaoProcessamentoPlano;
use Semae\Pca\Domain\Ports\PlanoRepository;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\DecimalExato;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\PcaObservado;
use Semae\Pca\Domain\ValueObjects\Representante;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Stringable;

final class FinalizadorProcessamentoUnidadeInternoTest extends TestCase
{
    /** @regra RN-PLA-007 @regra RN-PUB-004 */
    public function testNenhumaAcaoConvergenteDeveConfirmarSomentePlanoPendenteECommitar(): void
    {
        $sequencial = new SequencialPca(77);
        $pendente = $this->criarPlano('uo-101', '928344', [1001]);
        $confirmado = $this->criarPlano('uo-102', '928345', [1002], true);
        $contexto = $this->criarContexto(
            [$pendente, $confirmado],
            EstadoOperacionalPublicacao::publicado($sequencial),
            PcaObservado::ativo($sequencial, [1001, 1002]),
            ResultadoDecisaoProcessamentoPlano::nenhumaAcao(),
            []
        );
        $dependencias = $this->criarDependencias();
        $dependencias->leitor->expects($this->never())->method('observarPcaConhecido');
        $dependencias->leitor->expects($this->never())->method('localizarCandidatos');
        $dependencias->estado->expects($this->never())->method('registrar');
        $dependencias->repository->expects($this->once())
            ->method('confirmarSincronizacao')
            ->with($pendente);
        $dependencias->auditoria->expects($this->never())->method('registrar');
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('confirmar')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());
        $dependencias->unidadeTrabalho->expects($this->never())->method('desfazer');

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertSame(
            SituacaoProcessamentoUnidade::SEM_ALTERACAO,
            $resultado->getResultadoUnitario()?->getSituacao()
        );
        $this->assertSame($sequencial, $resultado->getResultadoUnitario()?->getSequencialPca());
    }

    /** @regra RN-PLA-007 @regra RN-PUB-004 */
    public function testNenhumaAcaoComTodosOsPlanosConfirmadosDeveSomenteCommitar(): void
    {
        $sequencial = new SequencialPca(77);
        $confirmado = $this->criarPlano('uo-101', '928344', [1001], true);
        $contexto = $this->criarContexto(
            [$confirmado],
            EstadoOperacionalPublicacao::publicado($sequencial),
            PcaObservado::ativo($sequencial, [1001]),
            ResultadoDecisaoProcessamentoPlano::nenhumaAcao(),
            []
        );
        $dependencias = $this->criarDependencias();
        $dependencias->leitor->expects($this->never())->method('observarPcaConhecido');
        $dependencias->leitor->expects($this->never())->method('localizarCandidatos');
        $dependencias->estado->expects($this->never())->method('registrar');
        $dependencias->repository->expects($this->never())->method('confirmarSincronizacao');
        $dependencias->auditoria->expects($this->never())->method('registrar');
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('confirmar')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());
        $dependencias->unidadeTrabalho->expects($this->never())->method('desfazer');

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertSame(
            SituacaoProcessamentoUnidade::SEM_ALTERACAO,
            $resultado->getResultadoUnitario()?->getSituacao()
        );
        $this->assertSame($sequencial, $resultado->getResultadoUnitario()?->getSequencialPca());
    }

    /** @regra RN-PUB-008 */
    public function testNenhumaAcaoComAusenciaDefinitivaDeveCorrigirEstadoPreservandoSequencial(): void
    {
        $sequencial = new SequencialPca(77);
        $plano = $this->criarPlano('uo-101', '928344', []);
        $contexto = $this->criarContexto(
            [$plano],
            EstadoOperacionalPublicacao::publicado($sequencial),
            PcaObservado::ausente(),
            ResultadoDecisaoProcessamentoPlano::nenhumaAcao(),
            []
        );
        $dependencias = $this->criarDependencias();
        $dependencias->estado->expects($this->once())
            ->method('registrar')
            ->with(
                $contexto->getUnidadeExterna(),
                $this->callback(static fn (EstadoOperacionalPublicacao $estado): bool =>
                    $estado->getEstado()->value === 'EXCLUIDO'
                    && $estado->getSequencialPca()?->getValor() === 77)
            )
            ->willReturn(ResultadoPersistenciaLocal::concluida());
        $dependencias->repository->expects($this->once())->method('confirmarSincronizacao');
        $dependencias->auditoria->expects($this->never())->method('registrar');
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('confirmar')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertSame(
            SituacaoProcessamentoUnidade::SEM_ALTERACAO,
            $resultado->getResultadoUnitario()?->getSituacao()
        );
        $this->assertSame(77, $resultado->getResultadoUnitario()?->getSequencialPca()?->getValor());
    }

    /** @regra RN-PUB-001 @regra RN-AUD-001 @regra RN-AUD-005 */
    public function testPublicacaoAceitaConvergenteDevePersistirAuditarEProduzirSucessoAposCommit(): void
    {
        $plano = $this->criarPlano('uo-101', '928344', [1001]);
        $sequencial = new SequencialPca(88);
        $contexto = $this->criarContexto(
            [$plano],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente(),
            ResultadoDecisaoProcessamentoPlano::publicar([1001]),
            [ResultadoPublicacaoPncp::aceita($sequencial)]
        );
        $dependencias = $this->criarDependencias();
        $ordem = [];
        $dependencias->leitor->expects($this->once())
            ->method('observarPcaConhecido')
            ->with(
                $contexto->getSessaoPncp(),
                $contexto->getCnpj(),
                $contexto->getUnidadeExterna(),
                $sequencial
            )
            ->willReturn(ResultadoObservacaoPca::encontrado(
                PcaObservado::ativo($sequencial, [1001])
            ));
        $dependencias->estado->expects($this->once())
            ->method('registrar')
            ->willReturnCallback(static function () use (&$ordem): ResultadoPersistenciaLocal {
                $ordem[] = 'estado';
                return ResultadoPersistenciaLocal::concluida();
            });
        $dependencias->repository->expects($this->once())
            ->method('confirmarSincronizacao')
            ->willReturnCallback(static function () use (&$ordem): void {
                $ordem[] = 'confirmacao';
            });
        $dependencias->auditoria->expects($this->once())
            ->method('registrar')
            ->with($this->callback(static function (IntencaoRegistroHistorico $intencao): bool {
                $mensagem = $intencao->getMensagem();

                return $intencao->getProcedimento() === ProcedimentoPublicacaoPca::PUBLICACAO_INICIAL
                    && str_contains($mensagem, 'UO: uo-101')
                    && str_contains($mensagem, 'UASG externa: 928344')
                    && str_contains($mensagem, 'sequencialPca: 88')
                    && str_contains($mensagem, 'ação confirmada: PUBLICAR')
                    && !method_exists($intencao, 'getInstante');
            }))
            ->willReturnCallback(static function () use (&$ordem): ResultadoPersistenciaLocal {
                $ordem[] = 'auditoria';
                return ResultadoPersistenciaLocal::concluida();
            });
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('confirmar')
            ->willReturnCallback(static function () use (&$ordem): ResultadoFinalizacaoUnidadeTrabalho {
                $ordem[] = 'commit';
                return ResultadoFinalizacaoUnidadeTrabalho::concluida();
            });

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertSame(['estado', 'confirmacao', 'auditoria', 'commit'], $ordem);
        $this->assertSame(
            SituacaoProcessamentoUnidade::CONCLUIDO,
            $resultado->getResultadoUnitario()?->getSituacao()
        );
        $this->assertSame(88, $resultado->getResultadoUnitario()?->getSequencialPca()?->getValor());
    }

    public function testObservacaoInconclusivaDepoisDaEscritaDeveDesfazerEReconciliar(): void
    {
        $plano = $this->criarPlano('uo-101', '928344', [1001]);
        $sequencial = new SequencialPca(88);
        $contexto = $this->criarContexto(
            [$plano],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente(),
            ResultadoDecisaoProcessamentoPlano::publicar([1001]),
            [ResultadoPublicacaoPncp::aceita($sequencial)]
        );
        $dependencias = $this->criarDependencias();
        $falha = new FalhaSegura('PNCP_INCONCLUSIVO', 'Observação inconclusiva.');
        $dependencias->leitor->expects($this->once())
            ->method('observarPcaConhecido')
            ->willReturn(ResultadoObservacaoPca::inconclusiva($falha));
        $dependencias->estado->expects($this->never())->method('registrar');
        $dependencias->repository->expects($this->never())->method('confirmarSincronizacao');
        $dependencias->auditoria->expects($this->never())->method('registrar');
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('desfazer')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertSame(
            SituacaoProcessamentoUnidade::FALHA_RECONCILIAVEL,
            $resultado->getResultadoUnitario()?->getSituacao()
        );
        $this->assertSame($falha, $resultado->getResultadoUnitario()?->getFalha());
    }

    public function testEscritaIncertaDevePermitirUmaUnicaReconciliacaoEObservarNovamente(): void
    {
        $plano = $this->criarPlano('uo-101', '928344', [1001]);
        $contexto = $this->criarContexto(
            [$plano],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente(),
            ResultadoDecisaoProcessamentoPlano::publicar([1001]),
            [ResultadoPublicacaoPncp::resultadoIncerto(
                new FalhaSegura('PUBLICACAO_INCERTA', 'Publicação incerta.')
            )]
        );
        $dependencias = $this->criarDependencias();
        $sequencial = new SequencialPca(91);
        $dependencias->leitor->expects($this->once())
            ->method('localizarCandidatos')
            ->willReturn(ResultadoBuscaCandidatosPca::concluida([]));
        $dependencias->escritor->expects($this->once())
            ->method('publicar')
            ->willReturn(ResultadoPublicacaoPncp::aceita($sequencial));
        $dependencias->leitor->expects($this->once())
            ->method('observarPcaConhecido')
            ->with(
                $contexto->getSessaoPncp(),
                $contexto->getCnpj(),
                $contexto->getUnidadeExterna(),
                $sequencial
            )
            ->willReturn(ResultadoObservacaoPca::encontrado(
                PcaObservado::ativo($sequencial, [1001])
            ));
        $dependencias->estado->expects($this->once())
            ->method('registrar')
            ->willReturn(ResultadoPersistenciaLocal::concluida());
        $dependencias->repository->expects($this->once())->method('confirmarSincronizacao');
        $dependencias->auditoria->expects($this->once())
            ->method('registrar')
            ->willReturn(ResultadoPersistenciaLocal::concluida());
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('confirmar')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertSame(
            SituacaoProcessamentoUnidade::CONCLUIDO,
            $resultado->getResultadoUnitario()?->getSituacao()
        );
        $this->assertSame(91, $resultado->getResultadoUnitario()?->getSequencialPca()?->getValor());
    }

    public function testNovaIncertezaDeveEncerrarSemTerceiraEscrita(): void
    {
        $plano = $this->criarPlano('uo-101', '928344', [1001]);
        $incerta = ResultadoPublicacaoPncp::resultadoIncerto(
            new FalhaSegura('PUBLICACAO_INCERTA', 'Publicação incerta.')
        );
        $contexto = $this->criarContexto(
            [$plano],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente(),
            ResultadoDecisaoProcessamentoPlano::publicar([1001]),
            [$incerta]
        );
        $dependencias = $this->criarDependencias();
        $dependencias->leitor->expects($this->exactly(2))
            ->method('localizarCandidatos')
            ->willReturn(ResultadoBuscaCandidatosPca::concluida([]));
        $dependencias->escritor->expects($this->once())
            ->method('publicar')
            ->willReturn($incerta);
        $dependencias->leitor->expects($this->never())->method('observarPcaConhecido');
        $dependencias->estado->expects($this->never())->method('registrar');
        $dependencias->repository->expects($this->never())->method('confirmarSincronizacao');
        $dependencias->auditoria->expects($this->never())->method('registrar');
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('desfazer')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertSame(
            SituacaoProcessamentoUnidade::FALHA_RECONCILIAVEL,
            $resultado->getResultadoUnitario()?->getSituacao()
        );
        $this->assertSame('RECONCILIACAO_NAO_CONVERGIU', $resultado->getResultadoUnitario()?->getFalha()?->getCodigo());
    }

    public function testPsr3DeveEvidenciarEscritaReconciliacaoEFalha(): void
    {
        $plano = $this->criarPlano('uo-101', '928344', [1001]);
        $incerta = ResultadoPublicacaoPncp::resultadoIncerto(
            new FalhaSegura('PUBLICACAO_INCERTA', 'Publicação incerta.')
        );
        $contexto = $this->criarContexto(
            [$plano],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente(),
            ResultadoDecisaoProcessamentoPlano::publicar([1001]),
            [$incerta]
        );
        $logger = new LoggerMemoriaFinalizador();
        $dependencias = $this->criarDependencias($logger);
        $dependencias->leitor->expects($this->exactly(2))
            ->method('localizarCandidatos')
            ->willReturn(ResultadoBuscaCandidatosPca::concluida([]));
        $dependencias->escritor->expects($this->once())
            ->method('publicar')
            ->willReturn($incerta);
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('desfazer')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());

        $dependencias->finalizador->finalizar($contexto);

        $this->assertTrue($logger->possui('info', 'Resultado de escrita externa recebido.'));
        $this->assertTrue($logger->possui('info', 'Reconciliação adicional iniciada.'));
        $this->assertTrue(
            $logger->possui(
                'warning',
                'A única reconciliação adicional não comprovou a convergência da unidade.'
            )
        );
    }

    public function testEscritaRejeitadaDeveSerObservadaMasNaoRepetida(): void
    {
        $plano = $this->criarPlano('uo-101', '928344', [1001]);
        $contexto = $this->criarContexto(
            [$plano],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente(),
            ResultadoDecisaoProcessamentoPlano::publicar([1001]),
            [ResultadoPublicacaoPncp::rejeitada(
                new FalhaSegura('PUBLICACAO_REJEITADA', 'Publicação rejeitada.')
            )]
        );
        $dependencias = $this->criarDependencias();
        $dependencias->leitor->expects($this->once())
            ->method('localizarCandidatos')
            ->willReturn(ResultadoBuscaCandidatosPca::concluida([]));
        $dependencias->escritor->expects($this->never())->method('publicar');
        $dependencias->auditoria->expects($this->never())->method('registrar');
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('desfazer')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertSame(
            SituacaoProcessamentoUnidade::FALHA_RECONCILIAVEL,
            $resultado->getResultadoUnitario()?->getSituacao()
        );
    }

    public function testCandidatoDepoisDePublicacaoIncertaDeveBloquearSemAdocaoAutomatica(): void
    {
        $plano = $this->criarPlano('uo-101', '928344', [1001]);
        $contexto = $this->criarContexto(
            [$plano],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente(),
            ResultadoDecisaoProcessamentoPlano::publicar([1001]),
            [ResultadoPublicacaoPncp::resultadoIncerto(
                new FalhaSegura('PUBLICACAO_INCERTA', 'Publicação incerta.')
            )]
        );
        $dependencias = $this->criarDependencias();
        $dependencias->leitor->expects($this->once())
            ->method('localizarCandidatos')
            ->willReturn(ResultadoBuscaCandidatosPca::concluida([
                PcaObservado::ativo(new SequencialPca(91), [1001]),
            ]));
        $dependencias->escritor->expects($this->never())->method('publicar');
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('desfazer')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertSame(
            SituacaoProcessamentoUnidade::BLOQUEADO,
            $resultado->getResultadoUnitario()?->getSituacao()
        );
        $this->assertSame('CANDIDATO_APOS_ESCRITA_SEM_VINCULO', $resultado->getResultadoUnitario()?->getFalha()?->getCodigo());
    }

    /** @regra RN-PUB-007 @regra RN-PUB-008 */
    public function testNovaPublicacaoIncertaDepoisDeExclusaoDeveIgnorarSequencialHistorico(): void
    {
        $plano = $this->criarPlano('uo-101', '928344', [1001]);
        $contexto = $this->criarContexto(
            [$plano],
            EstadoOperacionalPublicacao::excluido(new SequencialPca(77)),
            PcaObservado::ausente(),
            ResultadoDecisaoProcessamentoPlano::publicar([1001]),
            [ResultadoPublicacaoPncp::resultadoIncerto(
                new FalhaSegura('PUBLICACAO_INCERTA', 'Publicação incerta.')
            )]
        );
        $dependencias = $this->criarDependencias();
        $dependencias->leitor->expects($this->once())
            ->method('localizarCandidatos')
            ->with(
                $contexto->getSessaoPncp(),
                $contexto->getCnpj(),
                $contexto->getUnidadeExterna()
            )
            ->willReturn(ResultadoBuscaCandidatosPca::concluida([
                PcaObservado::ativo(new SequencialPca(91), [1001]),
            ]));
        $dependencias->leitor->expects($this->never())->method('observarPcaConhecido');
        $dependencias->escritor->expects($this->never())->method('publicar');
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('desfazer')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertSame(
            SituacaoProcessamentoUnidade::BLOQUEADO,
            $resultado->getResultadoUnitario()?->getSituacao()
        );
        $this->assertSame(
            'CANDIDATO_APOS_ESCRITA_SEM_VINCULO',
            $resultado->getResultadoUnitario()?->getFalha()?->getCodigo()
        );
    }

    /** @regra RN-PUB-002 @regra RN-AUD-002 @regra RN-AUD-005 */
    public function testRetificacaoDevePreservarSequencialEAuditarContagensIniciais(): void
    {
        $plano = $this->criarPlano('uo-101', '928344', [1002, 1003]);
        $sequencial = new SequencialPca(77);
        $contexto = $this->criarContexto(
            [$plano],
            EstadoOperacionalPublicacao::publicado($sequencial),
            PcaObservado::ativo($sequencial, [1001, 1002]),
            ResultadoDecisaoProcessamentoPlano::retificar([1003], [1001]),
            [ResultadoEscritaPncp::aceita(), ResultadoEscritaPncp::aceita()]
        );
        $dependencias = $this->criarDependencias();
        $dependencias->leitor->expects($this->once())
            ->method('observarPcaConhecido')
            ->willReturn(ResultadoObservacaoPca::encontrado(
                PcaObservado::ativo($sequencial, [1002, 1003])
            ));
        $dependencias->estado->expects($this->never())->method('registrar');
        $dependencias->repository->expects($this->once())->method('confirmarSincronizacao');
        $dependencias->auditoria->expects($this->once())
            ->method('registrar')
            ->with($this->callback(static function (IntencaoRegistroHistorico $intencao): bool {
                return $intencao->getProcedimento() === ProcedimentoPublicacaoPca::RETIFICACAO
                    && str_contains($intencao->getMensagem(), 'Itens incluídos: 1')
                    && str_contains($intencao->getMensagem(), 'itens excluídos: 1');
            }))
            ->willReturn(ResultadoPersistenciaLocal::concluida());
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('confirmar')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertSame(77, $resultado->getResultadoUnitario()?->getSequencialPca()?->getValor());
        $this->assertSame(1, $resultado->getResultadoUnitario()?->getQuantidadeInclusoes());
        $this->assertSame(1, $resultado->getResultadoUnitario()?->getQuantidadeExclusoes());
    }

    public function testRetificacaoComAusenciaDoPcaConhecidoDeveBloquearDivergencia(): void
    {
        $plano = $this->criarPlano('uo-101', '928344', [1002]);
        $sequencial = new SequencialPca(77);
        $contexto = $this->criarContexto(
            [$plano],
            EstadoOperacionalPublicacao::publicado($sequencial),
            PcaObservado::ativo($sequencial, [1001]),
            ResultadoDecisaoProcessamentoPlano::retificar([1002], [1001]),
            [ResultadoEscritaPncp::aceita(), ResultadoEscritaPncp::aceita()]
        );
        $dependencias = $this->criarDependencias();
        $dependencias->leitor->expects($this->once())
            ->method('observarPcaConhecido')
            ->willReturn(ResultadoObservacaoPca::ausenciaDefinitiva());
        $dependencias->estado->expects($this->never())->method('registrar');
        $dependencias->repository->expects($this->never())->method('confirmarSincronizacao');
        $dependencias->auditoria->expects($this->never())->method('registrar');
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('desfazer')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertSame(SituacaoProcessamentoUnidade::BLOQUEADO, $resultado->getResultadoUnitario()?->getSituacao());
        $this->assertSame('DIVERGENCIA_FINAL_INCOMPATIVEL', $resultado->getResultadoUnitario()?->getFalha()?->getCodigo());
    }

    /** @regra RN-PUB-003 @regra RN-AUD-002 */
    public function testExclusaoIntegralDevePersistirEstadoExcluidoEAuditarProcedimentoNove(): void
    {
        $plano = $this->criarPlano('uo-101', '928344', []);
        $sequencial = new SequencialPca(77);
        $contexto = $this->criarContexto(
            [$plano],
            EstadoOperacionalPublicacao::publicado($sequencial),
            PcaObservado::ativo($sequencial, [1001]),
            ResultadoDecisaoProcessamentoPlano::excluirPca(),
            [ResultadoEscritaPncp::aceita()]
        );
        $dependencias = $this->criarDependencias();
        $dependencias->leitor->expects($this->once())
            ->method('observarPcaConhecido')
            ->willReturn(ResultadoObservacaoPca::ausenciaDefinitiva());
        $dependencias->estado->expects($this->once())
            ->method('registrar')
            ->with(
                $contexto->getUnidadeExterna(),
                $this->callback(static fn (EstadoOperacionalPublicacao $estado): bool =>
                    $estado->getEstado()->value === 'EXCLUIDO'
                    && $estado->getSequencialPca()?->getValor() === 77)
            )
            ->willReturn(ResultadoPersistenciaLocal::concluida());
        $dependencias->repository->expects($this->once())->method('confirmarSincronizacao');
        $dependencias->auditoria->expects($this->once())
            ->method('registrar')
            ->with($this->callback(static fn (IntencaoRegistroHistorico $intencao): bool =>
                $intencao->getProcedimento() === ProcedimentoPublicacaoPca::EXCLUSAO_INTEGRAL))
            ->willReturn(ResultadoPersistenciaLocal::concluida());
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('confirmar')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertSame(SituacaoProcessamentoUnidade::CONCLUIDO, $resultado->getResultadoUnitario()?->getSituacao());
        $this->assertSame(77, $resultado->getResultadoUnitario()?->getSequencialPca()?->getValor());
    }

    public function testFalhaNoRegistroDeEstadoDeveImpedirDemaisEtapasEDesfazer(): void
    {
        $plano = $this->criarPlano('uo-101', '928344', [1001]);
        $sequencial = new SequencialPca(88);
        $contexto = $this->criarContexto(
            [$plano],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente(),
            ResultadoDecisaoProcessamentoPlano::publicar([1001]),
            [ResultadoPublicacaoPncp::aceita($sequencial)]
        );
        $dependencias = $this->criarDependencias();
        $dependencias->leitor->method('observarPcaConhecido')
            ->willReturn(ResultadoObservacaoPca::encontrado(PcaObservado::ativo($sequencial, [1001])));
        $falha = new FalhaSegura('ESTADO_FALHOU', 'Estado falhou.');
        $dependencias->estado->expects($this->once())
            ->method('registrar')
            ->willReturn(ResultadoPersistenciaLocal::falhaSistemica($falha));
        $dependencias->repository->expects($this->never())->method('confirmarSincronizacao');
        $dependencias->auditoria->expects($this->never())->method('registrar');
        $dependencias->unidadeTrabalho->expects($this->never())->method('confirmar');
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('desfazer')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertSame($falha, $resultado->getFalhaSistemica());
    }

    public function testFalhaNoCommitDeveDesfazerENaoProduzirSucesso(): void
    {
        $plano = $this->criarPlano('uo-101', '928344', []);
        $contexto = $this->criarContexto(
            [$plano],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente(),
            ResultadoDecisaoProcessamentoPlano::nenhumaAcao(),
            []
        );
        $dependencias = $this->criarDependencias();
        $dependencias->repository->expects($this->once())->method('confirmarSincronizacao');
        $falha = new FalhaSegura('COMMIT_FALHOU', 'Commit falhou.');
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('confirmar')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::falhaSistemica($falha));
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('desfazer')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertNull($resultado->getResultadoUnitario());
        $this->assertSame($falha, $resultado->getFalhaSistemica());
    }

    public function testFalhaNaConfirmacaoDosPlanosDeveImpedirAuditoriaECommit(): void
    {
        $plano = $this->criarPlano('uo-101', '928344', []);
        $contexto = $this->criarContexto(
            [$plano],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente(),
            ResultadoDecisaoProcessamentoPlano::nenhumaAcao(),
            []
        );
        $dependencias = $this->criarDependencias();
        $dependencias->repository->expects($this->once())
            ->method('confirmarSincronizacao')
            ->willThrowException(new \RuntimeException('detalhe interno'));
        $dependencias->auditoria->expects($this->never())->method('registrar');
        $dependencias->unidadeTrabalho->expects($this->never())->method('confirmar');
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('desfazer')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertNull($resultado->getResultadoUnitario());
        $this->assertSame(
            'PERSISTENCIA_CONVERGENCIA_FALHOU',
            $resultado->getFalhaSistemica()?->getCodigo()
        );
    }

    public function testFalhaNaAuditoriaDeveImpedirCommitEDesfazerEstadoEConfirmacao(): void
    {
        $plano = $this->criarPlano('uo-101', '928344', [1001]);
        $sequencial = new SequencialPca(88);
        $contexto = $this->criarContexto(
            [$plano],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente(),
            ResultadoDecisaoProcessamentoPlano::publicar([1001]),
            [ResultadoPublicacaoPncp::aceita($sequencial)]
        );
        $dependencias = $this->criarDependencias();
        $dependencias->leitor->method('observarPcaConhecido')
            ->willReturn(ResultadoObservacaoPca::encontrado(
                PcaObservado::ativo($sequencial, [1001])
            ));
        $dependencias->estado->expects($this->once())
            ->method('registrar')
            ->willReturn(ResultadoPersistenciaLocal::concluida());
        $dependencias->repository->expects($this->once())->method('confirmarSincronizacao');
        $falha = new FalhaSegura('AUDITORIA_FALHOU', 'Auditoria falhou.');
        $dependencias->auditoria->expects($this->once())
            ->method('registrar')
            ->willReturn(ResultadoPersistenciaLocal::falhaSistemica($falha));
        $dependencias->unidadeTrabalho->expects($this->never())->method('confirmar');
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('desfazer')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertSame($falha, $resultado->getFalhaSistemica());
    }

    /** @regra RN-AUD-001 */
    public function testSequenciaSomenteRejeitadaNaoDeveGerarAuditoriaMesmoSeObservacaoConvergir(): void
    {
        $plano = $this->criarPlano('uo-101', '928344', [1001]);
        $sequencial = new SequencialPca(77);
        $contexto = $this->criarContexto(
            [$plano],
            EstadoOperacionalPublicacao::publicado($sequencial),
            PcaObservado::ativo($sequencial, [1002]),
            ResultadoDecisaoProcessamentoPlano::retificar([1001], [1002]),
            [ResultadoEscritaPncp::rejeitada(
                new FalhaSegura('RETIFICACAO_REJEITADA', 'Retificação rejeitada.')
            )]
        );
        $dependencias = $this->criarDependencias();
        $dependencias->leitor->expects($this->once())
            ->method('observarPcaConhecido')
            ->willReturn(ResultadoObservacaoPca::encontrado(
                PcaObservado::ativo($sequencial, [1001])
            ));
        $dependencias->repository->expects($this->once())->method('confirmarSincronizacao');
        $dependencias->auditoria->expects($this->never())->method('registrar');
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('confirmar')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertSame(SituacaoProcessamentoUnidade::CONCLUIDO, $resultado->getResultadoUnitario()?->getSituacao());
    }

    public function testConflitoNaObservacaoFinalDeveBloquearEFalhaSistemicaDeveInterromper(): void
    {
        $plano = $this->criarPlano('uo-101', '928344', [1001]);
        $sequencial = new SequencialPca(88);
        $contexto = $this->criarContexto(
            [$plano],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente(),
            ResultadoDecisaoProcessamentoPlano::publicar([1001]),
            [ResultadoPublicacaoPncp::aceita($sequencial)]
        );

        $conflito = $this->criarDependencias();
        $falhaConflito = new FalhaSegura('CONFLITO_PNCP', 'Conflito PNCP.');
        $conflito->leitor->method('observarPcaConhecido')
            ->willReturn(ResultadoObservacaoPca::conflito($falhaConflito));
        $conflito->unidadeTrabalho->expects($this->once())
            ->method('desfazer')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());

        $resultadoConflito = $conflito->finalizador->finalizar($contexto);

        $this->assertSame(SituacaoProcessamentoUnidade::BLOQUEADO, $resultadoConflito->getResultadoUnitario()?->getSituacao());
        $this->assertSame($falhaConflito, $resultadoConflito->getResultadoUnitario()?->getFalha());

        $sistemica = $this->criarDependencias();
        $falhaSistemica = new FalhaSegura('PNCP_INDISPONIVEL', 'PNCP indisponível.');
        $sistemica->leitor->method('observarPcaConhecido')
            ->willReturn(ResultadoObservacaoPca::falhaSistemica($falhaSistemica));
        $sistemica->unidadeTrabalho->expects($this->once())
            ->method('desfazer')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());

        $resultadoSistemico = $sistemica->finalizador->finalizar($contexto);

        $this->assertNull($resultadoSistemico->getResultadoUnitario());
        $this->assertSame($falhaSistemica, $resultadoSistemico->getFalhaSistemica());
    }

    public function testFalhaNoRollbackDevePrevalecerComoSistemica(): void
    {
        $plano = $this->criarPlano('uo-101', '928344', [1001]);
        $sequencial = new SequencialPca(88);
        $contexto = $this->criarContexto(
            [$plano],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente(),
            ResultadoDecisaoProcessamentoPlano::publicar([1001]),
            [ResultadoPublicacaoPncp::aceita($sequencial)]
        );
        $dependencias = $this->criarDependencias();
        $dependencias->leitor->method('observarPcaConhecido')
            ->willReturn(ResultadoObservacaoPca::inconclusiva(
                new FalhaSegura('OBSERVACAO_INCERTA', 'Observação incerta.')
            ));
        $falhaRollback = new FalhaSegura('ROLLBACK_FALHOU', 'Rollback falhou.');
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('desfazer')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::falhaSistemica($falhaRollback));

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertNull($resultado->getResultadoUnitario());
        $this->assertSame($falhaRollback, $resultado->getFalhaSistemica());
    }

    /** @regra RN-PUB-010 @regra RN-PLA-007 */
    public function testConsolidadoDeveConfirmarTodosOsPlanosPendentesEmUmaUnicaUnidade(): void
    {
        $primeiro = $this->criarPlano('uo-101', '928344', [1001]);
        $segundo = $this->criarPlano('uo-102', '928345', [1002]);
        $sequencial = new SequencialPca(77);
        $contexto = new ContextoFinalizacaoProcessamentoUnidade(
            new ContextoUsuario(
                new Representante(709, 'Maria da Silva'),
                new Lotacao(159, 'Coordenadoria de Tecnologia')
            ),
            new class implements SessaoPncp {},
            new Cnpj('12.345.678/0001-95'),
            UnidadeExternaPublicacao::consolidada(
                $primeiro->getAnoExecucao(),
                new Uasg('986700', 'UASG Consolidadora')
            ),
            [$primeiro, $segundo],
            EstadoOperacionalPublicacao::publicado($sequencial),
            PcaObservado::ativo($sequencial, [1001, 1002]),
            ResultadoDecisaoProcessamentoPlano::nenhumaAcao(),
            []
        );
        $dependencias = $this->criarDependencias();
        $confirmados = [];
        $dependencias->repository->expects($this->exactly(2))
            ->method('confirmarSincronizacao')
            ->willReturnCallback(static function (Plano $plano) use (&$confirmados): void {
                $confirmados[] = $plano;
            });
        $dependencias->auditoria->expects($this->never())->method('registrar');
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('confirmar')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertSame([$primeiro, $segundo], $confirmados);
        $this->assertSame(SituacaoProcessamentoUnidade::SEM_ALTERACAO, $resultado->getResultadoUnitario()?->getSituacao());
    }

    /** @regra RN-AUD-003 @regra RN-AUD-005 */
    public function testRetificacaoConsolidadaDeveGerarUmaUnicaAuditoriaSemListarUos(): void
    {
        $primeiro = $this->criarPlano('uo-101', '928344', [1001]);
        $segundo = $this->criarPlano('uo-102', '928345', [1003]);
        $sequencial = new SequencialPca(77);
        $unidade = UnidadeExternaPublicacao::consolidada(
            $primeiro->getAnoExecucao(),
            new Uasg('986700', 'UASG Consolidadora')
        );
        $contexto = new ContextoFinalizacaoProcessamentoUnidade(
            new ContextoUsuario(
                new Representante(709, 'Maria da Silva'),
                new Lotacao(159, 'Coordenadoria de Tecnologia')
            ),
            new class implements SessaoPncp {},
            new Cnpj('12.345.678/0001-95'),
            $unidade,
            [$primeiro, $segundo],
            EstadoOperacionalPublicacao::publicado($sequencial),
            PcaObservado::ativo($sequencial, [1001, 1002]),
            ResultadoDecisaoProcessamentoPlano::retificar([1003], [1002]),
            [ResultadoEscritaPncp::aceita(), ResultadoEscritaPncp::aceita()]
        );
        $dependencias = $this->criarDependencias();
        $dependencias->leitor->expects($this->once())
            ->method('observarPcaConhecido')
            ->willReturn(ResultadoObservacaoPca::encontrado(
                PcaObservado::ativo($sequencial, [1001, 1003])
            ));
        $dependencias->repository->expects($this->exactly(2))
            ->method('confirmarSincronizacao');
        $dependencias->auditoria->expects($this->once())
            ->method('registrar')
            ->with($this->callback(static function (IntencaoRegistroHistorico $intencao): bool {
                $mensagem = $intencao->getMensagem();

                return str_contains($mensagem, 'Consolidado')
                    && str_contains($mensagem, 'UASG externa: 986700')
                    && !str_contains($mensagem, 'uo-101')
                    && !str_contains($mensagem, 'uo-102');
            }))
            ->willReturn(ResultadoPersistenciaLocal::concluida());
        $dependencias->unidadeTrabalho->expects($this->once())
            ->method('confirmar')
            ->willReturn(ResultadoFinalizacaoUnidadeTrabalho::concluida());

        $resultado = $dependencias->finalizador->finalizar($contexto);

        $this->assertSame($unidade, $resultado->getResultadoUnitario()?->getUnidadeExterna());
        $this->assertSame(SituacaoProcessamentoUnidade::CONCLUIDO, $resultado->getResultadoUnitario()?->getSituacao());
    }

    private function criarDependencias(?LoggerInterface $logger = null): DependenciasFinalizador
    {
        $leitor = $this->createMock(LeitorPncp::class);
        $escritor = $this->createMock(EscritorPncp::class);
        $evidencia = $this->createMock(EvidenciaCancelamentoHomologadoPort::class);
        $repository = $this->createMock(PlanoRepository::class);
        $estado = $this->createMock(EstadoOperacionalPublicacaoPort::class);
        $auditoria = $this->createMock(RegistroAuditoriaPort::class);
        $unidadeTrabalho = $this->createMock(UnidadeDeTrabalhoPort::class);
        $executor = new ExecutorComandosUnidade($escritor, $evidencia, $unidadeTrabalho);

        return new DependenciasFinalizador(
            new FinalizadorProcessamentoUnidadeInterno(
                $leitor,
                new PoliticaProcessamentoPlano(),
                $executor,
                $repository,
                $estado,
                $auditoria,
                $unidadeTrabalho,
                $logger ?? new NullLogger()
            ),
            $leitor,
            $escritor,
            $repository,
            $estado,
            $auditoria,
            $unidadeTrabalho
        );
    }

    /**
     * @param Plano[] $planos
     * @param array<ResultadoEscritaPncp|ResultadoPublicacaoPncp> $resultados
     */
    private function criarContexto(
        array $planos,
        EstadoOperacionalPublicacao $estado,
        PcaObservado $observado,
        ResultadoDecisaoProcessamentoPlano $decisao,
        array $resultados
    ): ContextoFinalizacaoProcessamentoUnidade {
        return new ContextoFinalizacaoProcessamentoUnidade(
            new ContextoUsuario(
                new Representante(709, 'Maria da Silva'),
                new Lotacao(159, 'Coordenadoria de Tecnologia')
            ),
            new class implements SessaoPncp {},
            new Cnpj('12.345.678/0001-95'),
            UnidadeExternaPublicacao::individual($planos[0]->getIdentidade()),
            $planos,
            $estado,
            $observado,
            $decisao,
            $resultados
        );
    }

    /** @param int[] $idsDemandas */
    private function criarPlano(
        string $idUo,
        string $uasg,
        array $idsDemandas,
        bool $confirmado = false
    ): Plano {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $uo = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria($idUo),
            new Lotacao(101, 'Diretoria Administrativa'),
            new Uasg($uasg, 'UASG da UO'),
            'Diretoria Administrativa'
        );

        return new Plano(
            new IdentidadePlano($uo, $ano),
            2,
            new DateTimeImmutable('2026-07-16'),
            array_map(
                fn (int $id): Demanda => $this->criarDemanda($id, $uo, $ano),
                $idsDemandas
            ),
            $confirmado ? new DateTimeImmutable('2026-08-01') : null
        );
    }

    private function criarDemanda(
        int $id,
        UnidadeOrcamentaria $unidade,
        AnoExecucao $ano
    ): Demanda {
        return new Demanda(
            $id,
            SituacaoDemanda::HOMOLOGADA,
            159,
            $unidade,
            $ano,
            new DateTimeImmutable($ano->getValor() . '-03-15'),
            1,
            'ITEM ' . $id,
            'UNIDADE',
            1,
            new DecimalExato('100.00'),
            new DecimalExato('100.00'),
            2,
            (string) $id,
            '98',
            'Grupo 98',
            'N'
        );
    }
}

final class DependenciasFinalizador
{
    public function __construct(
        public readonly FinalizadorProcessamentoUnidadeInterno $finalizador,
        public readonly LeitorPncp $leitor,
        public readonly EscritorPncp $escritor,
        public readonly PlanoRepository $repository,
        public readonly EstadoOperacionalPublicacaoPort $estado,
        public readonly RegistroAuditoriaPort $auditoria,
        public readonly UnidadeDeTrabalhoPort $unidadeTrabalho
    ) {}
}

final class LoggerMemoriaFinalizador extends AbstractLogger
{
    /** @var array<int, array{nivel: mixed, mensagem: string, contexto: array<string, mixed>}> */
    private array $registros = [];

    /** @param array<string, mixed> $context */
    public function log(
        mixed $level,
        string|Stringable $message,
        array $context = []
    ): void {
        $this->registros[] = [
            'nivel' => $level,
            'mensagem' => (string) $message,
            'contexto' => $context,
        ];
    }

    public function possui(string $nivel, string $mensagem): bool
    {
        foreach ($this->registros as $registro) {
            if ($registro['nivel'] === $nivel && $registro['mensagem'] === $mensagem) {
                return true;
            }
        }

        return false;
    }
}
