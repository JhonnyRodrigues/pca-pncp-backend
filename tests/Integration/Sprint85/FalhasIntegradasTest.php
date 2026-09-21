<?php

declare(strict_types=1);

namespace Tests\Integration\Sprint85;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\SituacaoProcessamentoUnidade;
use Semae\Pca\Application\Enums\SituacaoPublicacaoPca;
use Tests\Integration\Composition\Support\CenarioIntegracaoNucleo;
use Tests\Integration\Sprint84\Support\CenarioMatrizSprint84;

final class FalhasIntegradasTest extends TestCase
{
    public function testContencaoDeUmaUnidadeDevePermitirProcessarAProxima(): void
    {
        $cenario = CenarioMatrizSprint84::comContencaoNaPrimeiraUnidade();

        $resposta = $cenario->executar();

        $this->assertSame(
            SituacaoPublicacaoPca::CONCLUIDA_COM_PENDENCIAS,
            $resposta->getSituacao()
        );
        $this->assertCount(2, $resposta->getResultadosUnitarios());
        [$bloqueada, $concluida] = $resposta->getResultadosUnitarios();

        $this->assertSame(
            SituacaoProcessamentoUnidade::BLOQUEADO,
            $bloqueada->getSituacao()
        );
        $this->assertSame(
            '928341',
            $bloqueada->getUnidadeExterna()->getUasgExterna()->getCodigo()
        );
        $this->assertSame(
            'UNIDADE_TRABALHO_BLOQUEADA',
            $bloqueada->getFalha()?->getCodigo()
        );
        $this->assertSame(
            'A unidade de trabalho já está em processamento.',
            $bloqueada->getFalha()?->getMensagem()
        );
        $this->assertSame(
            SituacaoProcessamentoUnidade::SEM_ALTERACAO,
            $concluida->getSituacao()
        );
        $this->assertSame(
            '928342',
            $concluida->getUnidadeExterna()->getUasgExterna()->getCodigo()
        );
        $this->assertSame(2, $resposta->getQuantidadeVisitadas());
        $this->assertSame(0, $resposta->getQuantidadeNaoProcessadas());
        $this->assertSame(1, $cenario->adodb->rollbacks);
        $this->assertSame(
            1,
            $cenario->curl->quantidadeChamadas('GET', 'identidade')
        );
        $this->assertSame(1, $cenario->curl->quantidadeChamadas('GET', 'itens'));
        $this->assertSame(0, $cenario->curl->quantidadeEscritasPncp());
        $this->assertSame(0, $cenario->adodb->quantidadeEscritas('auditoria'));
        $this->assertSame([], $cenario->adodb->respostasPendentes());
        $this->assertSame([], $cenario->curl->respostasPendentes());
    }

    public function testEscritaIncertaDeveReconciliarUmaVezSemRepetirAEscrita(): void
    {
        $cenario = CenarioIntegracaoNucleo::individualParaRetificacaoInconclusiva();

        $resposta = $cenario->executar();

        $this->assertSame(
            SituacaoPublicacaoPca::CONCLUIDA_COM_PENDENCIAS,
            $resposta->getSituacao()
        );
        $this->assertCount(1, $resposta->getResultadosUnitarios());
        $resultado = $resposta->getResultadosUnitarios()[0];
        $this->assertSame(
            SituacaoProcessamentoUnidade::FALHA_RECONCILIAVEL,
            $resultado->getSituacao()
        );
        $this->assertSame(
            'PNCP_RESPOSTA_INCONCLUSIVA',
            $resultado->getFalha()?->getCodigo()
        );
        $this->assertSame(
            1,
            $cenario->curl->quantidadeChamadas('DELETE', 'excluir_itens')
        );
        $this->assertSame(
            1,
            $cenario->curl->quantidadeChamadas('POST', 'inserir_itens')
        );
        $this->assertSame(
            3,
            $cenario->curl->quantidadeChamadas('GET', 'identidade')
        );
        $this->assertSame([1], $cenario->temporizador?->esperasMs);
        $this->assertSame(1, $cenario->adodb->rollbacks);
        $this->assertSame(0, $cenario->adodb->quantidadeEscritas('confirmar_plano'));
        $this->assertSame(0, $cenario->adodb->quantidadeEscritas('auditoria'));
    }

    public function testFalhaSistemicaDeveInterromperEPreservarResultadoAnterior(): void
    {
        $cenario = CenarioMatrizSprint84::comFalhaSistemicaNaSegundaUnidade();

        $resposta = $cenario->executar();

        $this->assertSame(
            SituacaoPublicacaoPca::INTERROMPIDA,
            $resposta->getSituacao()
        );
        $this->assertSame(
            'PNCP_LEITURA_TRANSPORTE_FALHOU',
            $resposta->getFalhaGlobal()?->getCodigo()
        );
        $this->assertSame(
            'Não foi possível consultar o PNCP.',
            $resposta->getFalhaGlobal()?->getMensagem()
        );
        $this->assertCount(1, $resposta->getResultadosUnitarios());
        $preservado = $resposta->getResultadosUnitarios()[0];
        $this->assertSame(
            SituacaoProcessamentoUnidade::SEM_ALTERACAO,
            $preservado->getSituacao()
        );
        $this->assertSame(
            '928341',
            $preservado->getUnidadeExterna()->getUasgExterna()->getCodigo()
        );
        $this->assertSame(2, $resposta->getQuantidadePlanejadas());
        $this->assertSame(2, $resposta->getQuantidadeVisitadas());
        $this->assertSame(0, $resposta->getQuantidadeNaoProcessadas());
        $this->assertSame(1, $cenario->adodb->rollbacks);
        $this->assertSame(
            2,
            $cenario->curl->quantidadeChamadas('GET', 'identidade')
        );
        $this->assertSame(1, $cenario->curl->quantidadeChamadas('GET', 'itens'));
        $this->assertSame(0, $cenario->curl->quantidadeEscritasPncp());
        $this->assertSame(0, $cenario->adodb->quantidadeEscritas('auditoria'));
        $this->assertSame([], $cenario->adodb->respostasPendentes());
        $this->assertSame([], $cenario->curl->respostasPendentes());
    }
}
