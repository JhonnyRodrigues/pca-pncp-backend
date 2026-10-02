<?php

declare(strict_types=1);

namespace Tests\Application\Ports;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Commands\ComandoExcluirItensPca;
use Semae\Pca\Application\Commands\ComandoExcluirPca;
use Semae\Pca\Application\Commands\ComandoInserirItensPca;
use Semae\Pca\Application\Commands\ComandoPublicarPca;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Enums\SituacaoEscritaPncp;
use Semae\Pca\Application\Ports\AutenticadorPncpPort;
use Semae\Pca\Application\Ports\EscritorPncpPort;
use Semae\Pca\Application\Ports\EstrategiaPublicacaoPort;
use Semae\Pca\Application\Ports\LeitorPncpPort;
use Semae\Pca\Application\Results\ResultadoAdocaoEstrategia;
use Semae\Pca\Application\Results\ResultadoAutenticacaoPncp;
use Semae\Pca\Application\Results\ResultadoBuscaCandidatosPca;
use Semae\Pca\Application\Results\ResultadoConsultaEstrategia;
use Semae\Pca\Application\Results\ResultadoEscritaPncp;
use Semae\Pca\Application\Results\ResultadoObservacaoPca;
use Semae\Pca\Application\Results\ResultadoPublicacaoPncp;
use Semae\Pca\Application\ValueObjects\EstrategiaPublicacao;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\Demanda;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\SituacaoDemanda;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\DecimalExato;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class IntegracaoPncpEEstrategiaPortsTest extends TestCase
{
    public function testConsumidorDeveConsultarEAdotarEstrategiaPeloExercicio(): void
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $estrategia = EstrategiaPublicacao::individual();
        $port = $this->createMock(EstrategiaPublicacaoPort::class);

        $port->expects($this->once())
            ->method('consultarCorrente')
            ->with($ano)
            ->willReturn(ResultadoConsultaEstrategia::disponivel($estrategia));
        $port->expects($this->once())
            ->method('consultarAdotada')
            ->with($ano)
            ->willReturn(ResultadoConsultaEstrategia::ausente());
        $port->expects($this->once())
            ->method('adotar')
            ->with($ano, $estrategia)
            ->willReturn(ResultadoAdocaoEstrategia::adotada());

        $this->assertSame($estrategia, $port->consultarCorrente($ano)->getEstrategia());
        $this->assertNull($port->consultarAdotada($ano)->getEstrategia());
        $this->assertTrue($port->adotar($ano, $estrategia)->foiAdotada());
    }

    public function testConsumidorDeveReutilizarSessaoOpacaNasLeituras(): void
    {
        [$cnpj, $unidade] = $this->criarContextoPublicacao();
        $sequencial = new SequencialPca(55);
        $sessao = new SessaoPncpFake();
        $autenticador = $this->createMock(AutenticadorPncpPort::class);
        $leitor = $this->createMock(LeitorPncpPort::class);

        $autenticador->expects($this->once())
            ->method('autenticar')
            ->willReturn(ResultadoAutenticacaoPncp::autenticada($sessao));
        $leitor->expects($this->once())
            ->method('observarPcaConhecido')
            ->with($sessao, $cnpj, $unidade, $sequencial)
            ->willReturn(ResultadoObservacaoPca::ausenciaDefinitiva());
        $leitor->expects($this->once())
            ->method('localizarCandidatos')
            ->with($sessao, $cnpj, $unidade)
            ->willReturn(ResultadoBuscaCandidatosPca::concluida([]));

        $sessaoAutenticada = $autenticador->autenticar()->getSessao();

        $this->assertSame($sessao, $sessaoAutenticada);
        $this->assertNull(
            $leitor->observarPcaConhecido($sessao, $cnpj, $unidade, $sequencial)->getFalha()
        );
        $this->assertSame([], $leitor->localizarCandidatos($sessao, $cnpj, $unidade)->getCandidatos());
    }

    public function testConsumidorDeveExecutarCadaCapacidadeDeEscritaPorComandoTipado(): void
    {
        [$cnpj, $unidade, $demanda] = $this->criarContextoPublicacao();
        $sequencial = new SequencialPca(55);
        $sessao = new SessaoPncpFake();
        $publicar = new ComandoPublicarPca($cnpj, $unidade, [$demanda]);
        $inserir = new ComandoInserirItensPca($cnpj, $unidade, $sequencial, [$demanda]);
        $excluirItens = new ComandoExcluirItensPca(
            $cnpj,
            $unidade,
            $sequencial,
            [$demanda->getIdDemanda()],
            'Cancelamento homologado.'
        );
        $excluirPca = new ComandoExcluirPca(
            $cnpj,
            $unidade,
            $sequencial,
            'Cancelamento da última Demanda ativa.'
        );
        $port = $this->createMock(EscritorPncpPort::class);

        $port->expects($this->once())
            ->method('publicar')
            ->with($sessao, $publicar)
            ->willReturn(ResultadoPublicacaoPncp::aceita($sequencial));
        $port->expects($this->once())
            ->method('inserirItens')
            ->with($sessao, $inserir)
            ->willReturn(ResultadoEscritaPncp::aceita());
        $port->expects($this->once())
            ->method('excluirItens')
            ->with($sessao, $excluirItens)
            ->willReturn(ResultadoEscritaPncp::aceita());
        $port->expects($this->once())
            ->method('excluirPca')
            ->with($sessao, $excluirPca)
            ->willReturn(ResultadoEscritaPncp::aceita());

        $this->assertSame($sequencial, $port->publicar($sessao, $publicar)->getSequencialPca());
        $this->assertSame(
            SituacaoEscritaPncp::ACEITA,
            $port->inserirItens($sessao, $inserir)->getSituacao()
        );
        $this->assertSame(
            SituacaoEscritaPncp::ACEITA,
            $port->excluirItens($sessao, $excluirItens)->getSituacao()
        );
        $this->assertSame(
            SituacaoEscritaPncp::ACEITA,
            $port->excluirPca($sessao, $excluirPca)->getSituacao()
        );
    }

    /** @return array{Cnpj, UnidadeExternaPublicacao, Demanda} */
    private function criarContextoPublicacao(): array
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidadeOrcamentaria = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria('UO-01'),
            new Lotacao(10, 'Diretoria'),
            new Uasg('986789', 'UASG da UO'),
            'Unidade Orçamentária'
        );
        $unidade = UnidadeExternaPublicacao::individual(
            new IdentidadePlano($unidadeOrcamentaria, $ano)
        );

        return [
            new Cnpj('12.345.678/0001-95'),
            $unidade,
            new Demanda(
                1001,
                SituacaoDemanda::HOMOLOGADA,
                159,
                $unidadeOrcamentaria,
                $ano,
                new DateTimeImmutable($ano->getValor() . '-03-15'),
                1,
                'ITEM 1001',
                'UNIDADE',
                1,
                new DecimalExato('100.00'),
                new DecimalExato('100.00'),
                2,
                1,
                '1001',
                '98',
                'INFORMÁTICA',
                'N'
            ),
        ];
    }
}

final class SessaoPncpFake implements SessaoPncp
{
}
