<?php

declare(strict_types=1);

namespace Tests\Presentation\Facades;

use Error;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\Pca\Application\Contracts\PublicadorPca;
use Semae\Pca\Application\Results\PublicarPcaResponse;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaRequest;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Presentation\Controllers\PublicarPcaController;
use Semae\Pca\Presentation\Facades\FachadaPublicarPca;
use Semae\Pca\Presentation\Inputs\EntradaPublicarPca;
use Semae\Pca\Presentation\Presenters\PublicarPcaPresenter;
use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;
use Throwable;

final class FachadaPublicarPcaTest extends TestCase
{
    public function testCaminhoValidoDevePreservarOViewModelDoController(): void
    {
        $correlacao = new CorrelacaoExecucao('corr-fachada-valida');
        [$fachada, $publicador] = $this->criarFachada(
            PublicarPcaResponse::concluida([], 0, 0, $correlacao)
        );

        $viewModel = $fachada->executar(
            new EntradaPublicarPca('12.345.678/0001-95', (int) date('Y')),
            $correlacao
        );

        $this->assertSame('CONCLUIDA', $viewModel->getSituacao());
        $this->assertSame([], $viewModel->getResultadosUnitarios());
        $this->assertNull($viewModel->getCodigoFalha());
        $this->assertNull($viewModel->getMensagemFalha());
        $this->assertSame(0, $viewModel->getQuantidadePlanejadas());
        $this->assertSame(0, $viewModel->getQuantidadeVisitadas());
        $this->assertSame(0, $viewModel->getQuantidadeNaoProcessadas());
        $this->assertSame('corr-fachada-valida', $viewModel->getCorrelacao());
        $this->assertSame(1, $publicador->chamadas);
    }

    public function testCnpjInvalidoDeveProduzirSolicitacaoInvalidaSemChamarUseCase(): void
    {
        $correlacao = new CorrelacaoExecucao('  corr-cnpj-fachada  ');
        [$fachada, $publicador] = $this->criarFachada(
            PublicarPcaResponse::concluida([], 0, 0, $correlacao)
        );

        $viewModel = $fachada->executar(
            new EntradaPublicarPca('cnpj-invalido', (int) date('Y')),
            $correlacao
        );

        $this->assertSolicitacaoInvalida(
            $viewModel,
            'CNPJ_INVALIDO',
            'CNPJ inválido.',
            '  corr-cnpj-fachada  '
        );
        $this->assertSame(0, $publicador->chamadas);
    }

    public function testAnoInvalidoDeveProduzirSolicitacaoInvalidaSemChamarUseCase(): void
    {
        $correlacao = new CorrelacaoExecucao('corr-ano-fachada');
        [$fachada, $publicador] = $this->criarFachada(
            PublicarPcaResponse::concluida([], 0, 0, $correlacao)
        );

        $viewModel = $fachada->executar(
            new EntradaPublicarPca('12.345.678/0001-95', (int) date('Y') - 1),
            $correlacao
        );

        $this->assertSolicitacaoInvalida(
            $viewModel,
            'ANO_EXECUCAO_INVALIDO',
            'Ano de execução inválido.',
            'corr-ano-fachada'
        );
        $this->assertSame(0, $publicador->chamadas);
    }

    #[DataProvider('falhasInesperadas')]
    public function testFalhaInesperadaDeveProduzirInterrupcaoSegura(
        Throwable $falhaOriginal
    ): void {
        $correlacao = new CorrelacaoExecucao('  corr-falha-inesperada  ');
        $fachada = $this->criarFachadaComFalha($falhaOriginal);

        $viewModel = $fachada->executar(
            new EntradaPublicarPca('12.345.678/0001-95', (int) date('Y')),
            $correlacao
        );

        $this->assertSame('INTERROMPIDA', $viewModel->getSituacao());
        $this->assertSame([], $viewModel->getResultadosUnitarios());
        $this->assertSame('FALHA_INESPERADA', $viewModel->getCodigoFalha());
        $this->assertSame(
            'Não foi possível concluir a publicação do PCA.',
            $viewModel->getMensagemFalha()
        );
        $this->assertNotSame($falhaOriginal->getMessage(), $viewModel->getMensagemFalha());
        $this->assertSame(0, $viewModel->getQuantidadePlanejadas());
        $this->assertSame(0, $viewModel->getQuantidadeVisitadas());
        $this->assertSame(0, $viewModel->getQuantidadeNaoProcessadas());
        $this->assertSame('  corr-falha-inesperada  ', $viewModel->getCorrelacao());
    }

    public function testResponseSeguraDaApplicationNaoDeveSerReclassificada(): void
    {
        $correlacao = new CorrelacaoExecucao('corr-interrompida-application');
        [$fachada] = $this->criarFachada(PublicarPcaResponse::interrompida(
            [],
            new FalhaSegura(
                'PREPARACAO_PUBLICACAO_FALHOU',
                'Não foi possível preparar a execução da publicação.'
            ),
            2,
            0,
            $correlacao
        ));

        $viewModel = $fachada->executar(
            new EntradaPublicarPca('12.345.678/0001-95', (int) date('Y')),
            $correlacao
        );

        $this->assertSame('INTERROMPIDA', $viewModel->getSituacao());
        $this->assertSame('PREPARACAO_PUBLICACAO_FALHOU', $viewModel->getCodigoFalha());
        $this->assertSame(
            'Não foi possível preparar a execução da publicação.',
            $viewModel->getMensagemFalha()
        );
        $this->assertSame(2, $viewModel->getQuantidadePlanejadas());
        $this->assertSame(0, $viewModel->getQuantidadeVisitadas());
        $this->assertSame(2, $viewModel->getQuantidadeNaoProcessadas());
        $this->assertSame('corr-interrompida-application', $viewModel->getCorrelacao());
    }

    /** @return array<string, array{Throwable}> */
    public static function falhasInesperadas(): array
    {
        return [
            'runtime com SQL' => [new RuntimeException('SELECT segredo FROM tabela')],
            'argumento posterior à entrada' => [
                new InvalidArgumentException('payload interno inválido'),
            ],
            'error com credencial' => [new Error('senha=segredo')],
        ];
    }

    private function assertSolicitacaoInvalida(
        PublicarPcaViewModel $viewModel,
        string $codigo,
        string $mensagem,
        string $correlacao
    ): void {
        $this->assertSame('SOLICITACAO_INVALIDA', $viewModel->getSituacao());
        $this->assertSame([], $viewModel->getResultadosUnitarios());
        $this->assertSame($codigo, $viewModel->getCodigoFalha());
        $this->assertSame($mensagem, $viewModel->getMensagemFalha());
        $this->assertSame(0, $viewModel->getQuantidadePlanejadas());
        $this->assertSame(0, $viewModel->getQuantidadeVisitadas());
        $this->assertSame(0, $viewModel->getQuantidadeNaoProcessadas());
        $this->assertSame($correlacao, $viewModel->getCorrelacao());
    }

    /** @return array{FachadaPublicarPca, PublicadorPcaSpyFachada} */
    private function criarFachada(PublicarPcaResponse $response): array
    {
        $publicador = new PublicadorPcaSpyFachada($response);
        $presenter = new PublicarPcaPresenter();
        $controller = new PublicarPcaController($publicador, $presenter);

        return [new FachadaPublicarPca($controller, $presenter), $publicador];
    }

    private function criarFachadaComFalha(Throwable $falha): FachadaPublicarPca
    {
        $presenter = new PublicarPcaPresenter();
        $controller = new PublicarPcaController(
            new PublicadorPcaFalhoFachada($falha),
            $presenter
        );

        return new FachadaPublicarPca($controller, $presenter);
    }
}

final class PublicadorPcaSpyFachada implements PublicadorPca
{
    public int $chamadas = 0;

    public function __construct(private readonly PublicarPcaResponse $response) {}

    public function executar(
        PublicarPcaRequest $request,
        CorrelacaoExecucao $correlacao
    ): PublicarPcaResponse {
        $this->chamadas++;

        return $this->response;
    }
}

final class PublicadorPcaFalhoFachada implements PublicadorPca
{
    public function __construct(private readonly Throwable $falha) {}

    public function executar(
        PublicarPcaRequest $request,
        CorrelacaoExecucao $correlacao
    ): PublicarPcaResponse {
        throw $this->falha;
    }
}
