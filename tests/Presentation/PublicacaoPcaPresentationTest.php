<?php

declare(strict_types=1);

namespace Tests\Presentation;

use Error;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\Pca\Application\Contracts\PublicadorPca;
use Semae\Pca\Application\Results\PublicarPcaResponse;
use Semae\Pca\Application\Results\ResultadoProcessamentoUnidade;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaRequest;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Semae\Pca\Presentation\Controllers\PublicarPcaController;
use Semae\Pca\Presentation\Facades\FachadaPublicarPca;
use Semae\Pca\Presentation\Inputs\EntradaPublicarPca;
use Semae\Pca\Presentation\Presenters\PublicarPcaPresenter;
use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;
use Semae\Pca\Presentation\ViewModels\ResultadoUnidadeViewModel;
use Throwable;

final class PublicacaoPcaPresentationTest extends TestCase
{
    public function testCaminhoCompletoDeveEntregarSomenteViewModelsRenderizaveis(): void
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $correlacao = new CorrelacaoExecucao('  corr-transversal  ');
        $response = PublicarPcaResponse::concluida(
            [
                ResultadoProcessamentoUnidade::concluido(
                    $this->criarUnidade('uo-701', '928701', $ano),
                    DecisaoProcessamentoPlano::RETIFICAR,
                    new SequencialPca(701),
                    2,
                    1
                ),
                ResultadoProcessamentoUnidade::semAlteracao(
                    $this->criarUnidade('uo-702', '928702', $ano)
                ),
            ],
            2,
            2,
            $correlacao
        );
        $publicador = new PublicadorPcaSpyTransversal($response);

        $viewModel = $this->criarFachada($publicador)->executar(
            new EntradaPublicarPca('12.345.678/0001-95', $ano->getValor()),
            $correlacao
        );

        $this->assertSame(1, $publicador->chamadas);
        $this->assertNotNull($publicador->requestRecebido);
        $this->assertSame('12345678000195', $publicador->requestRecebido->getCnpj()->getValor());
        $this->assertSame($ano->getValor(), $publicador->requestRecebido->getAnoExecucao()->getValor());
        $this->assertSame($correlacao, $publicador->correlacaoRecebida);
        $this->assertSame('CONCLUIDA', $viewModel->getSituacao());
        $this->assertSame(2, $viewModel->getQuantidadePlanejadas());
        $this->assertSame(2, $viewModel->getQuantidadeVisitadas());
        $this->assertSame(0, $viewModel->getQuantidadeNaoProcessadas());
        $this->assertSame('  corr-transversal  ', $viewModel->getCorrelacao());

        $resultados = $viewModel->getResultadosUnitarios();
        $this->assertCount(2, $resultados);
        $this->assertSame('uo-701', $resultados[0]->getIdUnidadeOrcamentaria());
        $this->assertSame('uo-702', $resultados[1]->getIdUnidadeOrcamentaria());
        $this->assertSame('RETIFICAR', $resultados[0]->getDecisao());
        $this->assertSame(701, $resultados[0]->getSequencialPca());
        $this->assertSame(2, $resultados[0]->getQuantidadeInclusoes());
        $this->assertSame(1, $resultados[0]->getQuantidadeExclusoes());
        $this->assertSaidaRenderizavel($viewModel);
    }

    /** @param array{cnpj: string, ano: int, codigo: string, mensagem: string} $caso */
    #[DataProvider('entradasInvalidas')]
    public function testEntradaInvalidaNaoDeveAlcancarOUseCase(array $caso): void
    {
        $correlacao = new CorrelacaoExecucao('corr-entrada-transversal');
        $publicador = new PublicadorPcaSpyTransversal(
            PublicarPcaResponse::concluida([], 0, 0, $correlacao)
        );

        $viewModel = $this->criarFachada($publicador)->executar(
            new EntradaPublicarPca($caso['cnpj'], $caso['ano']),
            $correlacao
        );

        $this->assertSame(0, $publicador->chamadas);
        $this->assertSame('SOLICITACAO_INVALIDA', $viewModel->getSituacao());
        $this->assertSame($caso['codigo'], $viewModel->getCodigoFalha());
        $this->assertSame($caso['mensagem'], $viewModel->getMensagemFalha());
        $this->assertSame([], $viewModel->getResultadosUnitarios());
        $this->assertSame(0, $viewModel->getQuantidadePlanejadas());
        $this->assertSame(0, $viewModel->getQuantidadeVisitadas());
        $this->assertSame(0, $viewModel->getQuantidadeNaoProcessadas());
        $this->assertSaidaRenderizavel($viewModel);
    }

    public function testFalhasSegurasEResultadosConhecidosDevemSerPreservados(): void
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $correlacao = new CorrelacaoExecucao('corr-resultados-conhecidos');
        $response = PublicarPcaResponse::interrompida(
            [ResultadoProcessamentoUnidade::falhaReconciliavel(
                $this->criarUnidade('uo-703', '928703', $ano),
                null,
                null,
                0,
                0,
                new FalhaSegura('FALHA_UNITARIA_SEGURA', 'Mensagem unitária segura.')
            )],
            new FalhaSegura('FALHA_GLOBAL_SEGURA', 'Mensagem global segura.'),
            2,
            1,
            $correlacao
        );

        $viewModel = $this->criarFachada(
            new PublicadorPcaSpyTransversal($response)
        )->executar(
            new EntradaPublicarPca('12.345.678/0001-95', $ano->getValor()),
            $correlacao
        );

        $this->assertSame('INTERROMPIDA', $viewModel->getSituacao());
        $this->assertSame('FALHA_GLOBAL_SEGURA', $viewModel->getCodigoFalha());
        $this->assertSame('Mensagem global segura.', $viewModel->getMensagemFalha());
        $this->assertSame(2, $viewModel->getQuantidadePlanejadas());
        $this->assertSame(1, $viewModel->getQuantidadeVisitadas());
        $this->assertSame(1, $viewModel->getQuantidadeNaoProcessadas());
        $this->assertCount(1, $viewModel->getResultadosUnitarios());

        $resultado = $viewModel->getResultadosUnitarios()[0];
        $this->assertSame('uo-703', $resultado->getIdUnidadeOrcamentaria());
        $this->assertSame('FALHA_RECONCILIAVEL', $resultado->getSituacao());
        $this->assertSame('FALHA_UNITARIA_SEGURA', $resultado->getCodigoFalha());
        $this->assertSame('Mensagem unitária segura.', $resultado->getMensagemFalha());
        $this->assertSaidaRenderizavel($viewModel);
    }

    #[DataProvider('falhasInesperadasComDadosSensiveis')]
    public function testFalhaInesperadaNaoDeveVazarDetalhes(Throwable $falha): void
    {
        $correlacao = new CorrelacaoExecucao('corr-sanitizacao-transversal');
        $viewModel = $this->criarFachada(
            new PublicadorPcaFalhoTransversal($falha)
        )->executar(
            new EntradaPublicarPca('12.345.678/0001-95', (int) date('Y')),
            $correlacao
        );

        $this->assertSame('INTERROMPIDA', $viewModel->getSituacao());
        $this->assertSame('FALHA_INESPERADA', $viewModel->getCodigoFalha());
        $this->assertSame(
            'Não foi possível concluir a publicação do PCA.',
            $viewModel->getMensagemFalha()
        );
        $this->assertSame([], $viewModel->getResultadosUnitarios());
        $this->assertSame(0, $viewModel->getQuantidadePlanejadas());
        $this->assertSame(0, $viewModel->getQuantidadeVisitadas());
        $this->assertSame(0, $viewModel->getQuantidadeNaoProcessadas());

        $textos = $this->coletarTextos($viewModel);
        foreach (['SELECT segredo', 'payload=', 'senha=', 'Bearer ', 'stack-interna'] as $dado) {
            $this->assertStringNotContainsString($dado, implode("\n", $textos));
        }
        $this->assertSaidaRenderizavel($viewModel);
    }

    /** @return array<string, array{array{cnpj: string, ano: int, codigo: string, mensagem: string}}> */
    public static function entradasInvalidas(): array
    {
        return [
            'CNPJ inválido' => [[
                'cnpj' => 'cnpj-invalido',
                'ano' => (int) date('Y'),
                'codigo' => 'CNPJ_INVALIDO',
                'mensagem' => 'CNPJ inválido.',
            ]],
            'ano inválido' => [[
                'cnpj' => '12.345.678/0001-95',
                'ano' => (int) date('Y') - 1,
                'codigo' => 'ANO_EXECUCAO_INVALIDO',
                'mensagem' => 'Ano de execução inválido.',
            ]],
        ];
    }

    /** @return array<string, array{Throwable}> */
    public static function falhasInesperadasComDadosSensiveis(): array
    {
        $detalhes = 'SELECT segredo payload={interno} senha=oculta Bearer token stack-interna';

        return [
            'RuntimeException' => [new RuntimeException($detalhes)],
            'Error' => [new Error($detalhes)],
        ];
    }

    private function criarFachada(PublicadorPca $publicador): FachadaPublicarPca
    {
        $presenter = new PublicarPcaPresenter();

        return new FachadaPublicarPca(
            new PublicarPcaController($publicador, $presenter),
            $presenter
        );
    }

    private function criarUnidade(
        string $idUnidade,
        string $codigoUasg,
        AnoExecucao $ano
    ): UnidadeExternaPublicacao {
        $unidade = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria($idUnidade),
            new Lotacao(701, 'Diretoria transversal'),
            new Uasg($codigoUasg, 'UASG ' . $codigoUasg),
            'Diretoria transversal'
        );

        return UnidadeExternaPublicacao::individual(new IdentidadePlano($unidade, $ano));
    }

    private function assertSaidaRenderizavel(PublicarPcaViewModel $viewModel): void
    {
        $this->assertContainsOnlyInstancesOf(
            ResultadoUnidadeViewModel::class,
            $viewModel->getResultadosUnitarios()
        );

        $this->assertSomenteEscalaresENulos($this->projetarParaHospedeiro($viewModel));
    }

    /** @param mixed[] $valores */
    private function assertSomenteEscalaresENulos(array $valores): void
    {
        foreach ($valores as $valor) {
            if (is_array($valor)) {
                $this->assertSomenteEscalaresENulos($valor);
                continue;
            }

            $this->assertTrue(is_scalar($valor) || $valor === null);
        }
    }

    /** @return mixed[] */
    private function projetarParaHospedeiro(PublicarPcaViewModel $viewModel): array
    {
        return [
            'situacao' => $viewModel->getSituacao(),
            'resultadosUnitarios' => array_map(
                static fn (ResultadoUnidadeViewModel $resultado): array => [
                    'idUnidadeOrcamentaria' => $resultado->getIdUnidadeOrcamentaria(),
                    'modo' => $resultado->getModo(),
                    'anoExecucao' => $resultado->getAnoExecucao(),
                    'codigoUasg' => $resultado->getCodigoUasg(),
                    'descricaoUasg' => $resultado->getDescricaoUasg(),
                    'situacao' => $resultado->getSituacao(),
                    'decisao' => $resultado->getDecisao(),
                    'sequencialPca' => $resultado->getSequencialPca(),
                    'quantidadeInclusoes' => $resultado->getQuantidadeInclusoes(),
                    'quantidadeExclusoes' => $resultado->getQuantidadeExclusoes(),
                    'codigoFalha' => $resultado->getCodigoFalha(),
                    'mensagemFalha' => $resultado->getMensagemFalha(),
                ],
                $viewModel->getResultadosUnitarios()
            ),
            'codigoFalha' => $viewModel->getCodigoFalha(),
            'mensagemFalha' => $viewModel->getMensagemFalha(),
            'quantidadePlanejadas' => $viewModel->getQuantidadePlanejadas(),
            'quantidadeVisitadas' => $viewModel->getQuantidadeVisitadas(),
            'quantidadeNaoProcessadas' => $viewModel->getQuantidadeNaoProcessadas(),
            'correlacao' => $viewModel->getCorrelacao(),
        ];
    }

    /** @return string[] */
    private function coletarTextos(PublicarPcaViewModel $viewModel): array
    {
        $textos = [];
        $projecao = $this->projetarParaHospedeiro($viewModel);
        array_walk_recursive(
            $projecao,
            static function (mixed $valor) use (&$textos): void {
                if (is_string($valor)) {
                    $textos[] = $valor;
                }
            }
        );

        return $textos;
    }
}

final class PublicadorPcaSpyTransversal implements PublicadorPca
{
    public int $chamadas = 0;
    public ?PublicarPcaRequest $requestRecebido = null;
    public ?CorrelacaoExecucao $correlacaoRecebida = null;

    public function __construct(private readonly PublicarPcaResponse $response) {}

    public function executar(
        PublicarPcaRequest $request,
        CorrelacaoExecucao $correlacao
    ): PublicarPcaResponse {
        $this->chamadas++;
        $this->requestRecebido = $request;
        $this->correlacaoRecebida = $correlacao;

        return $this->response;
    }
}

final class PublicadorPcaFalhoTransversal implements PublicadorPca
{
    public function __construct(private readonly Throwable $falha) {}

    public function executar(
        PublicarPcaRequest $request,
        CorrelacaoExecucao $correlacao
    ): PublicarPcaResponse {
        throw $this->falha;
    }
}
