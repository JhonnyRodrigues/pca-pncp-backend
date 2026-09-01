<?php

declare(strict_types=1);

namespace Tests\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Results\PublicarPcaResponse;
use Semae\Pca\Application\Results\ResultadoProcessamentoUnidade;
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
use Semae\Pca\Presentation\Presenters\PublicarPcaPresenter;

final class PublicarPcaPresenterTest extends TestCase
{
    public function testDeveProjetarConclusaoLegitimaSemUnidades(): void
    {
        $response = PublicarPcaResponse::concluida(
            [],
            0,
            0,
            new CorrelacaoExecucao('  corr-sem-unidades  ')
        );

        $viewModel = (new PublicarPcaPresenter())->apresentar($response);

        $this->assertSame('CONCLUIDA', $viewModel->getSituacao());
        $this->assertSame([], $viewModel->getResultadosUnitarios());
        $this->assertNull($viewModel->getCodigoFalha());
        $this->assertNull($viewModel->getMensagemFalha());
        $this->assertSame(0, $viewModel->getQuantidadePlanejadas());
        $this->assertSame(0, $viewModel->getQuantidadeVisitadas());
        $this->assertSame(0, $viewModel->getQuantidadeNaoProcessadas());
        $this->assertSame('  corr-sem-unidades  ', $viewModel->getCorrelacao());
    }

    public function testDeveProjetarFalhaGlobalSemCriarCodigoOuMensagem(): void
    {
        $response = PublicarPcaResponse::interrompida(
            [],
            new FalhaSegura('FALHA_GLOBAL_SEGURA', 'Mensagem global segura.'),
            3,
            0,
            new CorrelacaoExecucao('corr-interrompida')
        );

        $viewModel = (new PublicarPcaPresenter())->apresentar($response);

        $this->assertSame('INTERROMPIDA', $viewModel->getSituacao());
        $this->assertSame('FALHA_GLOBAL_SEGURA', $viewModel->getCodigoFalha());
        $this->assertSame('Mensagem global segura.', $viewModel->getMensagemFalha());
        $this->assertSame(3, $viewModel->getQuantidadePlanejadas());
        $this->assertSame(0, $viewModel->getQuantidadeVisitadas());
        $this->assertSame(3, $viewModel->getQuantidadeNaoProcessadas());
    }

    public function testDeveProjetarResultadoUnitarioRepresentativoEPreservarOrdem(): void
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $primeiraUnidade = $this->criarUnidade('uo-101', '928344', $ano);
        $segundaUnidade = $this->criarUnidade('uo-202', '928355', $ano);
        $response = PublicarPcaResponse::concluida(
            [
                ResultadoProcessamentoUnidade::concluido(
                    $primeiraUnidade,
                    DecisaoProcessamentoPlano::RETIFICAR,
                    new SequencialPca(77),
                    2,
                    1
                ),
                ResultadoProcessamentoUnidade::semAlteracao($segundaUnidade),
            ],
            2,
            2,
            new CorrelacaoExecucao('corr-resultados')
        );

        $viewModel = (new PublicarPcaPresenter())->apresentar($response);
        $resultados = $viewModel->getResultadosUnitarios();

        $this->assertCount(2, $resultados);
        $this->assertSame('uo-101', $resultados[0]->getIdUnidadeOrcamentaria());
        $this->assertSame('uo-202', $resultados[1]->getIdUnidadeOrcamentaria());
        $this->assertSame('INDIVIDUAL', $resultados[0]->getModo());
        $this->assertSame($ano->getValor(), $resultados[0]->getAnoExecucao());
        $this->assertSame('928344', $resultados[0]->getCodigoUasg());
        $this->assertSame('UASG 928344', $resultados[0]->getDescricaoUasg());
        $this->assertSame('CONCLUIDO', $resultados[0]->getSituacao());
        $this->assertSame('RETIFICAR', $resultados[0]->getDecisao());
        $this->assertSame(77, $resultados[0]->getSequencialPca());
        $this->assertSame(2, $resultados[0]->getQuantidadeInclusoes());
        $this->assertSame(1, $resultados[0]->getQuantidadeExclusoes());
        $this->assertNull($resultados[0]->getCodigoFalha());
        $this->assertNull($resultados[0]->getMensagemFalha());
    }

    public function testDeveProjetarSemAlteracaoComCamposOpcionaisNulos(): void
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $response = PublicarPcaResponse::concluida(
            [ResultadoProcessamentoUnidade::semAlteracao(
                $this->criarUnidade('uo-303', '928366', $ano)
            )],
            1,
            1,
            new CorrelacaoExecucao('corr-sem-alteracao')
        );

        $resultado = (new PublicarPcaPresenter())
            ->apresentar($response)
            ->getResultadosUnitarios()[0];

        $this->assertSame('SEM_ALTERACAO', $resultado->getSituacao());
        $this->assertSame('NENHUMA_ACAO', $resultado->getDecisao());
        $this->assertNull($resultado->getSequencialPca());
        $this->assertSame(0, $resultado->getQuantidadeInclusoes());
        $this->assertSame(0, $resultado->getQuantidadeExclusoes());
        $this->assertNull($resultado->getCodigoFalha());
        $this->assertNull($resultado->getMensagemFalha());
    }

    public function testDeveProjetarFalhaReconciliavelConsolidadaSemDecisao(): void
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidade = UnidadeExternaPublicacao::consolidada(
            $ano,
            new Uasg('928377', 'UASG consolidadora')
        );
        $response = PublicarPcaResponse::concluidaComPendencias(
            [ResultadoProcessamentoUnidade::falhaReconciliavel(
                $unidade,
                null,
                null,
                0,
                0,
                new FalhaSegura('FALHA_RECONCILIAVEL_SEGURA', 'Requer nova observação.')
            )],
            1,
            1,
            new CorrelacaoExecucao('corr-consolidada')
        );

        $viewModel = (new PublicarPcaPresenter())->apresentar($response);
        $resultado = $viewModel->getResultadosUnitarios()[0];

        $this->assertNull($viewModel->getCodigoFalha());
        $this->assertNull($viewModel->getMensagemFalha());
        $this->assertNull($resultado->getIdUnidadeOrcamentaria());
        $this->assertSame('CONSOLIDADO', $resultado->getModo());
        $this->assertSame($ano->getValor(), $resultado->getAnoExecucao());
        $this->assertSame('928377', $resultado->getCodigoUasg());
        $this->assertSame('UASG consolidadora', $resultado->getDescricaoUasg());
        $this->assertSame('FALHA_RECONCILIAVEL', $resultado->getSituacao());
        $this->assertNull($resultado->getDecisao());
        $this->assertNull($resultado->getSequencialPca());
        $this->assertSame(0, $resultado->getQuantidadeInclusoes());
        $this->assertSame(0, $resultado->getQuantidadeExclusoes());
        $this->assertSame('FALHA_RECONCILIAVEL_SEGURA', $resultado->getCodigoFalha());
        $this->assertSame('Requer nova observação.', $resultado->getMensagemFalha());
    }

    public function testDeveProjetarBloqueioComDecisaoSequencialContagensEFalha(): void
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $response = PublicarPcaResponse::concluidaComPendencias(
            [ResultadoProcessamentoUnidade::bloqueado(
                $this->criarUnidade('uo-404', '928388', $ano),
                DecisaoProcessamentoPlano::RETIFICAR,
                new SequencialPca(88),
                0,
                1,
                new FalhaSegura('UNIDADE_BLOQUEADA', 'Unidade temporariamente bloqueada.')
            )],
            1,
            1,
            new CorrelacaoExecucao('corr-bloqueada')
        );

        $resultado = (new PublicarPcaPresenter())
            ->apresentar($response)
            ->getResultadosUnitarios()[0];

        $this->assertSame('BLOQUEADO', $resultado->getSituacao());
        $this->assertSame('RETIFICAR', $resultado->getDecisao());
        $this->assertSame(88, $resultado->getSequencialPca());
        $this->assertSame(0, $resultado->getQuantidadeInclusoes());
        $this->assertSame(1, $resultado->getQuantidadeExclusoes());
        $this->assertSame('UNIDADE_BLOQUEADA', $resultado->getCodigoFalha());
        $this->assertSame(
            'Unidade temporariamente bloqueada.',
            $resultado->getMensagemFalha()
        );
    }

    public function testDevePreservarDecisoesDePublicacaoEExclusaoIntegral(): void
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $response = PublicarPcaResponse::concluida(
            [
                ResultadoProcessamentoUnidade::concluido(
                    $this->criarUnidade('uo-505', '928399', $ano),
                    DecisaoProcessamentoPlano::PUBLICAR,
                    new SequencialPca(90),
                    2,
                    0
                ),
                ResultadoProcessamentoUnidade::concluido(
                    $this->criarUnidade('uo-606', '928400', $ano),
                    DecisaoProcessamentoPlano::EXCLUIR_PCA,
                    new SequencialPca(91),
                    0,
                    0
                ),
            ],
            2,
            2,
            new CorrelacaoExecucao('corr-decisoes')
        );

        $resultados = (new PublicarPcaPresenter())
            ->apresentar($response)
            ->getResultadosUnitarios();

        $this->assertSame('PUBLICAR', $resultados[0]->getDecisao());
        $this->assertSame(90, $resultados[0]->getSequencialPca());
        $this->assertSame('EXCLUIR_PCA', $resultados[1]->getDecisao());
        $this->assertSame(91, $resultados[1]->getSequencialPca());
    }

    private function criarUnidade(
        string $idUnidade,
        string $codigoUasg,
        AnoExecucao $ano
    ): UnidadeExternaPublicacao {
        $unidade = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria($idUnidade),
            new Lotacao(101, 'Diretoria Administrativa'),
            new Uasg($codigoUasg, 'UASG ' . $codigoUasg),
            'Diretoria Administrativa'
        );

        return UnidadeExternaPublicacao::individual(new IdentidadePlano($unidade, $ano));
    }
}
