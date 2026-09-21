<?php

declare(strict_types=1);

namespace Tests\Composition;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Semae\Pca\Application\Contracts\PublicadorPca;
use Semae\Pca\Application\Results\PublicarPcaResponse;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaRequest;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Composition\ComposicaoExecucaoPublicacaoPca;
use Semae\Pca\Infrastructure\Observabilidade\AcumuladorEventosTecnicos;
use Semae\Pca\Infrastructure\Observabilidade\RelogioEventosTecnicos;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Presentation\Controllers\PublicarPcaController;
use Semae\Pca\Presentation\Facades\FachadaPublicarPca;
use Semae\Pca\Presentation\Presenters\PublicarPcaPresenter;
use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;

final class ComposicaoExecucaoPublicacaoPcaTest extends TestCase
{
    private const CORRELACAO = '550e8400-e29b-41d4-a716-446655440000';

    public function testDeveExportarEnvelopesSanitizadosSemAlterarOResultado(): void
    {
        $relogio = new RelogioExportacaoControlado();
        $logger = $this->criarLogger($relogio);
        $logger->info('Evento com segredo.', [
            'action' => 'EXECUTAR_PUBLICACAO_PCA',
            'context' => [
                'unidade' => '930281',
                'senha' => 'nao-pode-sair',
            ],
        ]);
        $resultado = $this->criarResultado();
        $composicao = $this->criarComposicao($logger);

        $saida = $composicao->exportar($resultado);

        $this->assertSame($resultado, $saida->getResultado());
        $this->assertNull($saida->getAvisoRegistroTecnico());
        $this->assertCount(1, $saida->getEventos());
        $this->assertArrayNotHasKey('senha', $saida->getEventos()[0]['context']);
        $this->assertStringNotContainsString(
            'nao-pode-sair',
            json_encode($saida->getEventos(), JSON_THROW_ON_ERROR)
        );
        $this->assertSame(
            25,
            $saida->getEventos()[0]['metadata']['executionTimeMs']
        );
        $this->assertArrayNotHasKey(
            'executionTimeMs',
            $logger->getEventos()[0]->getMetadata()
        );
    }

    public function testTransacaoAtivaDeveImpedirExportacaoEPreservarResultado(): void
    {
        $logger = $this->criarLogger(new RelogioExportacaoControlado());
        $logger->info('Evento não exportável.', [
            'action' => 'EXECUTAR_PUBLICACAO_PCA',
        ]);
        $conexao = new ConexaoAdodbEmprestada(new AdodbExportacaoControlado());
        $conexao->iniciarTransacao();
        $resultado = $this->criarResultado();
        $composicao = new ComposicaoExecucaoPublicacaoPca(
            $this->criarFachada(),
            $logger,
            $conexao
        );

        $saida = $composicao->exportar($resultado);

        $this->assertSame($resultado, $saida->getResultado());
        $this->assertSame([], $saida->getEventos());
        $this->assertSame(
            'Os eventos técnicos não foram disponibilizados porque o processamento local não foi encerrado.',
            $saida->getAvisoRegistroTecnico()
        );
    }

    public function testFalhaNaExportacaoDevePreservarResultadoEProduzirAvisoSeguro(): void
    {
        $resultado = $this->criarResultado();
        $composicao = $this->criarComposicao(
            $this->criarLogger(new RelogioExportacaoFalho())
        );

        $saida = $composicao->exportar($resultado);

        $this->assertSame($resultado, $saida->getResultado());
        $this->assertSame([], $saida->getEventos());
        $this->assertSame(
            'Os eventos técnicos não puderam ser disponibilizados para registro.',
            $saida->getAvisoRegistroTecnico()
        );
    }

    private function criarComposicao(
        AcumuladorEventosTecnicos $logger
    ): ComposicaoExecucaoPublicacaoPca {
        return new ComposicaoExecucaoPublicacaoPca(
            $this->criarFachada(),
            $logger,
            new ConexaoAdodbEmprestada(new AdodbExportacaoControlado())
        );
    }

    private function criarLogger(
        RelogioEventosTecnicos $relogio
    ): AcumuladorEventosTecnicos {
        return new AcumuladorEventosTecnicos(
            self::CORRELACAO,
            'HOMOLOGACAO',
            'publicacao-pca',
            null,
            $relogio
        );
    }

    private function criarFachada(): FachadaPublicarPca
    {
        $presenter = new PublicarPcaPresenter();

        return new FachadaPublicarPca(
            new PublicarPcaController(
                new PublicadorVazioExportacao(),
                $presenter
            ),
            $presenter,
            new NullLogger()
        );
    }

    private function criarResultado(): PublicarPcaViewModel
    {
        return new PublicarPcaViewModel(
            'CONCLUIDA',
            [],
            null,
            null,
            0,
            0,
            0,
            self::CORRELACAO
        );
    }
}

final class PublicadorVazioExportacao implements PublicadorPca
{
    public function executar(
        PublicarPcaRequest $request,
        CorrelacaoExecucao $correlacao
    ): PublicarPcaResponse {
        return PublicarPcaResponse::concluida([], 0, 0, $correlacao);
    }
}

final class RelogioExportacaoControlado implements RelogioEventosTecnicos
{
    private int $tempo = 1_000;

    public function agora(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-09-18T10:00:00-03:00');
    }

    public function tempoMonotonicoMs(): int
    {
        $atual = $this->tempo;
        $this->tempo += 25;

        return $atual;
    }
}

final class RelogioExportacaoFalho implements RelogioEventosTecnicos
{
    private bool $iniciado = false;

    public function agora(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-09-18T10:00:00-03:00');
    }

    public function tempoMonotonicoMs(): int
    {
        if ($this->iniciado) {
            throw new RuntimeException('detalhe interno que não pode sair');
        }

        $this->iniciado = true;

        return 1_000;
    }
}

final class AdodbExportacaoControlado
{
    public function BeginTrans(): bool
    {
        return true;
    }

    public function CommitTrans(): bool
    {
        return true;
    }

    public function RollbackTrans(): bool
    {
        return true;
    }

    /** @param array<int|string, mixed> $parametros */
    public function Execute(string $sql, array $parametros = []): bool
    {
        return true;
    }

    /** @param array<int|string, mixed> $parametros @return array<int, array<string, mixed>> */
    public function GetAll(string $sql, array $parametros = []): array
    {
        return [];
    }

    public function Param(false|string $nome): string
    {
        return $nome === false ? '' : ':' . $nome;
    }

    public function ErrorNo(): int
    {
        return 0;
    }

    public function ErrorMsg(): string
    {
        return '';
    }
}
