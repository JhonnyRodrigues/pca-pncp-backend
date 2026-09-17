<?php

declare(strict_types=1);

namespace Tests\Integration\Sprint84;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\SituacaoProcessamentoUnidade;
use Semae\Pca\Application\Enums\SituacaoPublicacaoPca;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;
use Semae\Pca\Domain\Enums\ProcedimentoPublicacaoPca;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Composition\CompositionRootPublicacaoPca;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteCurlPncp;
use Semae\Pca\Presentation\Inputs\EntradaPublicarPca;
use Tests\Contract\Sprint84\Support\Sprint84PncpTransportGuard;
use Tests\Integration\Sprint84\Support\CenarioMatrizSprint84;
use Tests\Support\PerfilPublicacaoPcaFixture;

final class FluxosCompletosMatrizTest extends TestCase
{
    /** @regra RN-PUB-008 */
    public function testNenhumaAcaoDeveCompletarNovasVersoesConfirmadasNosDoisModos(): void
    {
        foreach (['INDIVIDUAL', 'CONSOLIDADO'] as $modo) {
            $cenario = CenarioMatrizSprint84::criar($modo, DecisaoProcessamentoPlano::NENHUMA_ACAO, true);
            $resultado = $cenario->executar();

            $this->assertSame(SituacaoPublicacaoPca::CONCLUIDA, $resultado->getSituacao());
            foreach ($resultado->getResultadosUnitarios() as $unidade) {
                $this->assertSame(SituacaoProcessamentoUnidade::SEM_ALTERACAO, $unidade->getSituacao());
                $this->assertSame(0, $unidade->getQuantidadeInclusoes());
                $this->assertSame(0, $unidade->getQuantidadeExclusoes());
            }
            $this->assertSame($modo === 'INDIVIDUAL' ? 2 : 1, $cenario->adodb->quantidadeEscritas('estado_operacional'));
            $this->assertSame(0, $cenario->adodb->quantidadeEscritas('confirmar_plano'));
            $this->assertSame(0, $cenario->adodb->quantidadeEscritas('auditoria'));
            $this->assertSame(0, $cenario->curl->quantidadeEscritasPncp());
            $this->assertSame([], $cenario->adodb->respostasPendentes());
            $this->assertSame([], $cenario->curl->respostasPendentes());
            $this->assertSame(0, $cenario->adodb->fechamentos);
        }
    }

    public function testCompositionRootDeveUsarGuardaCompartilhadaERegistrarPostsDaFachada(): void
    {
        $cenario = CenarioMatrizSprint84::criar(
            'INDIVIDUAL',
            DecisaoProcessamentoPlano::PUBLICAR
        );
        $perfil = PerfilPublicacaoPcaFixture::valido();
        $perfilTipado = new PerfilPublicacaoPca($perfil);
        $guard = new Sprint84PncpTransportGuard(new TransporteCurlPncp(
            $perfilTipado->getConfiguracaoTransporte(),
            $cenario->curl
        ));
        $correlacao = new CorrelacaoExecucao('00000000-0000-4000-8000-000000000084');
        $fachada = (new CompositionRootPublicacaoPca($guard))->montar(
            $cenario->adodb,
            'usuario.teste',
            $perfil,
            $correlacao
        );

        $viewModel = $fachada->executar(
            new EntradaPublicarPca('12345678000195', 2027),
            $correlacao
        );

        $posts = array_values(array_filter(
            $guard->diagnostico(),
            static fn (array $call): bool => ($call['requisicao']['metodo'] ?? null) === 'POST'
                && ($call['requisicao']['caminho'] ?? null)
                    === '/v1/orgaos/12345678000195/pca'
        ));

        $this->assertSame('CONCLUIDA', $viewModel->getSituacao());
        $this->assertCount(2, $posts);
        $this->assertSame([201, 201], array_column(array_column($posts, 'resposta'), 'status_http'));
        $this->assertStringContainsString('"codigoUnidade":"928341"', $posts[0]['requisicao']['corpo']);
        $this->assertStringContainsString('"numeroItem":1011', $posts[0]['requisicao']['corpo']);
        $this->assertStringContainsString('"codigoUnidade":"928342"', $posts[1]['requisicao']['corpo']);
        $this->assertStringContainsString('"numeroItem":2021', $posts[1]['requisicao']['corpo']);
    }

    #[DataProvider('matriz')]
    public function testDeveExecutarFluxoCompletoComAdaptersConcretos(
        string $modo,
        DecisaoProcessamentoPlano $decisao
    ): void {
        $cenario = CenarioMatrizSprint84::criar($modo, $decisao);

        $resposta = $cenario->executar();

        $this->assertSame(
            SituacaoPublicacaoPca::CONCLUIDA,
            $resposta->getSituacao(),
            json_encode([
                'falha' => $resposta->getFalhaGlobal()?->getCodigo(),
                'eventos' => $cenario->eventos->todos(),
                'oracle_pendente' => $cenario->adodb->respostasPendentes(),
                'pncp_pendente' => $cenario->curl->respostasPendentes(),
            ], JSON_THROW_ON_ERROR)
        );
        $this->assertCount($modo === 'INDIVIDUAL' ? 2 : 1, $resposta->getResultadosUnitarios());

        foreach ($resposta->getResultadosUnitarios() as $resultado) {
            $this->assertSame($decisao, $resultado->getDecisao());
            $this->assertSame(
                $decisao === DecisaoProcessamentoPlano::NENHUMA_ACAO
                    ? SituacaoProcessamentoUnidade::SEM_ALTERACAO
                    : SituacaoProcessamentoUnidade::CONCLUIDO,
                $resultado->getSituacao()
            );
            $this->assertNotNull($resultado->getSequencialPca());
        }

        $sequenciais = array_map(
            static fn ($resultado): ?int => $resultado->getSequencialPca()?->getValor(),
            $resposta->getResultadosUnitarios()
        );
        sort($sequenciais);
        $this->assertSame($modo === 'INDIVIDUAL' ? [71, 72] : [81], $sequenciais);

        $quantidadeUnidades = $modo === 'INDIVIDUAL' ? 2 : 1;
        $auditavel = $decisao !== DecisaoProcessamentoPlano::NENHUMA_ACAO;
        $confirmaVersoes = $auditavel;
        $alteraEstado = $auditavel;

        $this->assertSame(
            $confirmaVersoes ? 2 : 0,
            $cenario->adodb->quantidadeEscritas('confirmar_plano')
        );
        $this->assertSame(
            $confirmaVersoes ? $this->confirmacoesEsperadas($decisao) : [],
            $cenario->adodb->parametrosEscritas('confirmar_plano')
        );
        $this->assertSame(
            $auditavel ? $quantidadeUnidades : 0,
            $cenario->adodb->quantidadeEscritas('adotar_estrategia')
        );
        $this->assertSame(
            $auditavel ? $quantidadeUnidades : 0,
            $cenario->adodb->quantidadeEscritas('auditoria')
        );
        $this->assertSame(
            $auditavel ? array_fill(0, $quantidadeUnidades, $this->procedimento($decisao)) : [],
            array_column($cenario->adodb->parametrosEscritas('auditoria'), 'param_0')
        );
        $this->assertSame(
            $alteraEstado ? $quantidadeUnidades : 0,
            $cenario->adodb->quantidadeEscritas('estado_operacional')
        );
        $this->assertSame(
            $alteraEstado ? $this->estadosEsperados($modo, $decisao) : [],
            $cenario->adodb->parametrosEscritas('estado_operacional')
        );
        $this->assertSame(0, $cenario->adodb->rollbacks);
        $this->assertSame($modo === 'INDIVIDUAL' ? 6 : 4, $cenario->adodb->commits);
        $this->assertSame($cenario->adodb->commits, $cenario->adodb->inicios);
        $this->assertSame([], $cenario->adodb->respostasPendentes());
        $this->assertSame([], $cenario->curl->respostasPendentes());
        $this->assertSame(
            $cenario->rotasEscritaEsperadas(),
            $cenario->curl->rotasEscritas()
        );

        if ($decisao === DecisaoProcessamentoPlano::RETIFICAR) {
            foreach ($resposta->getResultadosUnitarios() as $resultado) {
                $esperado = $modo === 'INDIVIDUAL' ? 1 : 2;
                $this->assertSame($esperado, $resultado->getQuantidadeInclusoes());
                $this->assertSame($esperado, $resultado->getQuantidadeExclusoes());
            }
            $this->assertOrdemRetificacao($cenario->curl->rotasEscritas());
        }
    }

    /** @return iterable<string, array{string, DecisaoProcessamentoPlano}> */
    public static function matriz(): iterable
    {
        foreach (['INDIVIDUAL', 'CONSOLIDADO'] as $modo) {
            foreach (DecisaoProcessamentoPlano::cases() as $decisao) {
                yield strtolower($modo) . '-' . strtolower($decisao->value) => [$modo, $decisao];
            }
        }
    }

    /** @param string[] $rotas */
    private function assertOrdemRetificacao(array $rotas): void
    {
        foreach (array_chunk($rotas, 2) as $par) {
            $this->assertSame(['excluir_itens', 'inserir_itens'], $par);
        }
    }

    /** @return array<int, array<string, int>> */
    private function confirmacoesEsperadas(DecisaoProcessamentoPlano $decisao): array
    {
        $versao = in_array(
            $decisao,
            [DecisaoProcessamentoPlano::RETIFICAR, DecisaoProcessamentoPlano::EXCLUIR_PCA],
            true
        ) ? 2 : 1;

        return [
            ['param_0' => 101, 'param_1' => 2027, 'param_2' => $versao],
            ['param_0' => 202, 'param_1' => 2027, 'param_2' => $versao],
        ];
    }

    private function procedimento(DecisaoProcessamentoPlano $decisao): int
    {
        return match ($decisao) {
            DecisaoProcessamentoPlano::PUBLICAR => ProcedimentoPublicacaoPca::PUBLICACAO_INICIAL->value,
            DecisaoProcessamentoPlano::RETIFICAR => ProcedimentoPublicacaoPca::RETIFICACAO->value,
            DecisaoProcessamentoPlano::EXCLUIR_PCA => ProcedimentoPublicacaoPca::EXCLUSAO_INTEGRAL->value,
            DecisaoProcessamentoPlano::NENHUMA_ACAO => throw new \LogicException(
                'NENHUMA_ACAO não possui procedimento de auditoria.'
            ),
        };
    }

    /** @return array<int, array<string, int|string>> */
    private function estadosEsperados(
        string $modo,
        DecisaoProcessamentoPlano $decisao
    ): array {
        $estado = $decisao === DecisaoProcessamentoPlano::EXCLUIR_PCA ? 'EXCLUIDO' : 'PUBLICADO';

        if ($modo === 'CONSOLIDADO') {
            return [[
                'param_0' => $estado,
                'param_1' => 81,
                'param_2' => 2027,
            ]];
        }

        return [
            ['param_0' => $estado, 'param_1' => 71, 'param_2' => 2027, 'param_3' => 101],
            ['param_0' => $estado, 'param_1' => 72, 'param_2' => 2027, 'param_3' => 202],
        ];
    }
}
