<?php

declare(strict_types=1);

namespace Tests\Application\UseCases\PublicarPca;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Contracts\FinalizadorProcessamentoUnidade;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Ports\EvidenciaCancelamentoHomologadoPort;
use Semae\Pca\Application\Ports\EscritorPncp;
use Semae\Pca\Application\Ports\EstadoOperacionalPublicacaoPort;
use Semae\Pca\Application\Ports\LeitorPncp;
use Semae\Pca\Application\Ports\UnidadeDeTrabalhoPort;
use Semae\Pca\Application\Results\DesfechoProcessamentoUnidade;
use Semae\Pca\Application\Results\ResultadoBuscaCandidatosPca;
use Semae\Pca\Application\Results\ResultadoConsultaEstadoOperacional;
use Semae\Pca\Application\Results\ResultadoInicioUnidadeTrabalho;
use Semae\Pca\Application\Results\ResultadoProcessamentoUnidade;
use Semae\Pca\Application\UseCases\PublicarPca\DecisorUnidadeSobExclusividade;
use Semae\Pca\Application\UseCases\PublicarPca\DespachadorComandosUnidade;
use Semae\Pca\Application\UseCases\PublicarPca\PreparadorUnidadeSobExclusividade;
use Semae\Pca\Application\UseCases\PublicarPca\ProcessadorUnidadeInterno;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Policies\PoliticaProcessamentoPlano;
use Semae\Pca\Domain\Ports\PlanoRepository;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Representante;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class ProcessadorUnidadeInternoTest extends TestCase
{
    public function testDeveEncadearPreparacaoDecisaoEDespachoSemEscritaEmNenhumaAcao(): void
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidadeOrcamentaria = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria('uo-101'),
            new Lotacao(101, 'Diretoria Administrativa'),
            new Uasg('928344', 'SEMAE Piracicaba'),
            'Diretoria Administrativa'
        );
        $identidade = new IdentidadePlano($unidadeOrcamentaria, $ano);
        $plano = new Plano(
            $identidade,
            2,
            new DateTimeImmutable('2026-07-16'),
            [],
            null
        );
        $unidade = UnidadeExternaPublicacao::individual($identidade);
        $unidadeTrabalho = $this->createMock(UnidadeDeTrabalhoPort::class);
        $repository = $this->createMock(PlanoRepository::class);
        $estado = $this->createMock(EstadoOperacionalPublicacaoPort::class);
        $leitor = $this->createMock(LeitorPncp::class);
        $escritor = $this->createMock(EscritorPncp::class);
        $evidencia = $this->createMock(EvidenciaCancelamentoHomologadoPort::class);
        $finalizador = $this->createMock(FinalizadorProcessamentoUnidade::class);
        $desfechoEsperado = DesfechoProcessamentoUnidade::resultadoUnitario(
            ResultadoProcessamentoUnidade::semAlteracao($unidade)
        );

        $unidadeTrabalho->expects($this->once())
            ->method('iniciar')
            ->willReturn(ResultadoInicioUnidadeTrabalho::iniciada());
        $unidadeTrabalho->expects($this->never())->method('confirmar');
        $unidadeTrabalho->expects($this->never())->method('desfazer');
        $repository->expects($this->once())
            ->method('listarIdentidades')
            ->with($ano)
            ->willReturn([$identidade]);
        $repository->expects($this->once())
            ->method('reconstruirFotografiaVigente')
            ->with($identidade)
            ->willReturn($plano);
        $estado->expects($this->once())
            ->method('consultar')
            ->with($unidade)
            ->willReturn(ResultadoConsultaEstadoOperacional::disponivel(
                EstadoOperacionalPublicacao::naoPublicado()
            ));
        $leitor->expects($this->once())
            ->method('localizarCandidatos')
            ->willReturn(ResultadoBuscaCandidatosPca::concluida([]));
        $leitor->expects($this->never())->method('observarPcaConhecido');
        $escritor->expects($this->never())->method('publicar');
        $escritor->expects($this->never())->method('inserirItens');
        $escritor->expects($this->never())->method('excluirItens');
        $escritor->expects($this->never())->method('excluirPca');
        $evidencia->expects($this->never())
            ->method('consultarUltimoCancelamentoQueTornouUnidadeVazia');
        $finalizador->expects($this->once())
            ->method('finalizar')
            ->willReturn($desfechoEsperado);

        $processador = new ProcessadorUnidadeInterno(
            new PreparadorUnidadeSobExclusividade($unidadeTrabalho, $repository, $estado),
            new DecisorUnidadeSobExclusividade(
                $leitor,
                new PoliticaProcessamentoPlano(),
                $unidadeTrabalho
            ),
            new DespachadorComandosUnidade(
                $escritor,
                $evidencia,
                $finalizador,
                $unidadeTrabalho
            )
        );

        $resultado = $processador->processar(
            new ContextoUsuario(
                new Representante(709, 'Maria da Silva'),
                new Lotacao(159, 'Coordenadoria de Tecnologia')
            ),
            new SessaoPncpFakeProcessadorInterno(),
            new Cnpj('12.345.678/0001-95'),
            $unidade
        );

        $this->assertSame($desfechoEsperado, $resultado);
    }
}

final class SessaoPncpFakeProcessadorInterno implements SessaoPncp
{
}
