<?php

declare(strict_types=1);

namespace Tests\Integration\Composition;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\SituacaoProcessamentoUnidade;
use Semae\Pca\Application\Enums\SituacaoPublicacaoPca;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;
use Tests\Integration\Composition\Support\CenarioIntegracaoNucleo;

final class MontadorNucleoPublicacaoPcaIntegrationTest extends TestCase
{
    public function testDeveReconstruirUnidadeIndividualJaConvergenteComNoveAdapters(): void
    {
        $cenario = CenarioIntegracaoNucleo::individualJaConvergente();

        $response = $cenario->executar();

        $this->assertSame(
            SituacaoPublicacaoPca::CONCLUIDA,
            $response->getSituacao(),
            json_encode([
                'falha' => $response->getFalhaGlobal()?->getCodigo(),
                'eventos' => $cenario->eventos->todos(),
                'oraclePendentes' => $cenario->adodb->respostasPendentes(),
                'pncpPendentes' => $cenario->curl->respostasPendentes(),
            ], JSON_THROW_ON_ERROR)
        );
        $this->assertCount(1, $response->getResultadosUnitarios());
        $resultado = $response->getResultadosUnitarios()[0];
        $this->assertSame(SituacaoProcessamentoUnidade::SEM_ALTERACAO, $resultado->getSituacao());
        $this->assertSame(DecisaoProcessamentoPlano::NENHUMA_ACAO, $resultado->getDecisao());
        $this->assertSame(77, $resultado->getSequencialPca()?->getValor());

        $this->assertSame(2, $cenario->adodb->quantidadeLocks());
        $this->assertSame(4, $cenario->adodb->inicios);
        $this->assertSame(4, $cenario->adodb->commits);
        $this->assertSame(0, $cenario->adodb->rollbacks);
        $this->assertSame(0, $cenario->adodb->fechamentos);
        $this->assertSame([], $cenario->adodb->respostasPendentes());

        $this->assertSame(1, $cenario->curl->quantidadeChamadas('POST', 'login'));
        $this->assertSame(1, $cenario->curl->quantidadeChamadas('GET', 'identidade'));
        $this->assertSame(1, $cenario->curl->quantidadeChamadas('GET', 'itens'));
        $this->assertSame(0, $cenario->curl->quantidadeEscritasPncp());
        $this->assertSame([], $cenario->curl->respostasPendentes());

        $this->assertSame(
            [
                'oracle:query:contexto_representante',
                'oracle:query:contexto_lotacao',
                'oracle:query:identidades',
                'oracle:begin',
                'oracle:write:provisionar_controle',
                'oracle:commit',
                'oracle:begin',
                'oracle:query:lock',
                'oracle:query:estrategia_corrente',
                'oracle:query:estrategia_adotada',
                'oracle:commit',
                'pncp:POST:login',
                'oracle:begin',
                'oracle:write:provisionar_controle',
                'oracle:commit',
                'oracle:begin',
                'oracle:query:lock',
                'oracle:query:identidades',
                'oracle:query:plano_vigente',
                'oracle:query:demandas_vigentes',
                'oracle:query:estado_operacional',
                'pncp:GET:identidade',
                'pncp:GET:itens',
                'oracle:commit',
            ],
            $cenario->eventos->todos()
        );
    }

    public function testDeveRetificarComSessaoCompartilhadaEPersistenciaOrdenada(): void
    {
        $cenario = CenarioIntegracaoNucleo::individualParaRetificacao();

        $response = $cenario->executar();

        $this->assertSame(SituacaoPublicacaoPca::CONCLUIDA, $response->getSituacao());
        $this->assertCount(1, $response->getResultadosUnitarios());
        $resultado = $response->getResultadosUnitarios()[0];
        $this->assertSame(SituacaoProcessamentoUnidade::CONCLUIDO, $resultado->getSituacao());
        $this->assertSame(DecisaoProcessamentoPlano::RETIFICAR, $resultado->getDecisao());
        $this->assertSame(77, $resultado->getSequencialPca()?->getValor());
        $this->assertSame(1, $resultado->getQuantidadeInclusoes());
        $this->assertSame(1, $resultado->getQuantidadeExclusoes());

        $this->assertSame(1, $cenario->curl->quantidadeChamadas('POST', 'login'));
        $this->assertSame(2, $cenario->curl->quantidadeChamadas('GET', 'identidade'));
        $this->assertSame(2, $cenario->curl->quantidadeChamadas('GET', 'itens'));
        $this->assertSame(1, $cenario->curl->quantidadeChamadas('DELETE', 'excluir_itens'));
        $this->assertSame(1, $cenario->curl->quantidadeChamadas('POST', 'inserir_itens'));
        $this->assertSame(
            array_fill(0, 6, 'Bearer token-ficticio'),
            $cenario->curl->bearersAposLogin()
        );
        $this->assertSame([1001], $cenario->curl->idsDoPayload('excluir_itens'));
        $this->assertSame([1002], $cenario->curl->idsDoPayload('inserir_itens'));

        $this->assertSame(4, $cenario->adodb->inicios);
        $this->assertSame(4, $cenario->adodb->commits);
        $this->assertSame(0, $cenario->adodb->rollbacks);
        $this->assertSame(0, $cenario->adodb->fechamentos);
        $this->assertSame([], $cenario->adodb->respostasPendentes());
        $this->assertSame([], $cenario->curl->respostasPendentes());

        $this->assertSame(
            [
                'pncp:GET:identidade',
                'pncp:GET:itens',
                'pncp:DELETE:excluir_itens',
                'pncp:POST:inserir_itens',
                'pncp:GET:identidade',
                'pncp:GET:itens',
                'oracle:query:confirmacao_plano',
                'oracle:write:confirmar_plano',
                'oracle:query:confirmacao_plano',
                'oracle:write:auditoria',
                'oracle:commit',
            ],
            array_slice($cenario->eventos->todos(), -11)
        );
        $this->assertNotContains('oracle:write:estado_operacional', $cenario->eventos->todos());
    }

    public function testDeveDesfazerRetificacaoQuandoObservacaoFinalEsgotaRetry(): void
    {
        $cenario = CenarioIntegracaoNucleo::individualParaRetificacaoInconclusiva();

        $response = $cenario->executar();

        $this->assertSame(
            SituacaoPublicacaoPca::CONCLUIDA_COM_PENDENCIAS,
            $response->getSituacao()
        );
        $this->assertCount(1, $response->getResultadosUnitarios());
        $resultado = $response->getResultadosUnitarios()[0];
        $this->assertSame(
            SituacaoProcessamentoUnidade::FALHA_RECONCILIAVEL,
            $resultado->getSituacao()
        );
        $this->assertSame(DecisaoProcessamentoPlano::RETIFICAR, $resultado->getDecisao());
        $this->assertSame('PNCP_RESPOSTA_INCONCLUSIVA', $resultado->getFalha()?->getCodigo());
        $this->assertSame(1, $resultado->getQuantidadeInclusoes());
        $this->assertSame(1, $resultado->getQuantidadeExclusoes());

        $this->assertSame(3, $cenario->curl->quantidadeChamadas('GET', 'identidade'));
        $this->assertSame(1, $cenario->curl->quantidadeChamadas('GET', 'itens'));
        $this->assertSame(1, $cenario->curl->quantidadeChamadas('DELETE', 'excluir_itens'));
        $this->assertSame(1, $cenario->curl->quantidadeChamadas('POST', 'inserir_itens'));
        $this->assertSame([1], $cenario->temporizador?->esperasMs);
        $this->assertSame([], $cenario->curl->respostasPendentes());

        $this->assertSame(4, $cenario->adodb->inicios);
        $this->assertSame(3, $cenario->adodb->commits);
        $this->assertSame(1, $cenario->adodb->rollbacks);
        $this->assertSame(0, $cenario->adodb->fechamentos);
        $this->assertSame([], $cenario->adodb->respostasPendentes());
        $this->assertSame(
            [
                'oracle:query:estrategia_corrente',
                'oracle:query:estrategia_adotada',
                'oracle:commit',
                'pncp:POST:login',
            ],
            array_slice($cenario->eventos->todos(), 8, 4)
        );
        $this->assertSame(
            [
                'pncp:DELETE:excluir_itens',
                'pncp:POST:inserir_itens',
                'pncp:GET:identidade',
                'pncp:GET:identidade',
                'oracle:rollback',
            ],
            array_slice($cenario->eventos->todos(), -5)
        );

        $eventos = implode("\n", $cenario->eventos->todos());
        $this->assertStringNotContainsString('oracle:write:confirmar_plano', $eventos);
        $this->assertStringNotContainsString('oracle:write:estado_operacional', $eventos);
        $this->assertStringNotContainsString('oracle:write:auditoria', $eventos);
        $this->assertStringNotContainsString('login-ficticio', $eventos);
        $this->assertStringNotContainsString('senha-ficticia', $eventos);
        $this->assertStringNotContainsString('token-ficticio', $eventos);
        $this->assertStringNotContainsString('CATMAT-1002', $eventos);
        $this->assertStringNotContainsString('SELECT ', $eventos);
        $this->assertStringNotContainsString('INSERT INTO ', $eventos);
        $this->assertStringNotContainsString('UPDATE ', $eventos);
    }
}
