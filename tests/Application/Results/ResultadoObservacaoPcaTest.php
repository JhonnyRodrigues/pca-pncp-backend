<?php

declare(strict_types=1);

namespace Tests\Application\Results;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\SituacaoObservacaoPca;
use Semae\Pca\Application\Results\ResultadoObservacaoPca;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Domain\ValueObjects\PcaObservado;
use Semae\Pca\Domain\ValueObjects\SequencialPca;

final class ResultadoObservacaoPcaTest extends TestCase
{
    public function testPcaEncontradoDeveExporFatoAtivoSemFalha(): void
    {
        $pca = PcaObservado::ativo(new SequencialPca(55), [1001, 1002]);

        $resultado = ResultadoObservacaoPca::encontrado($pca);

        $this->assertSame(SituacaoObservacaoPca::ENCONTRADO, $resultado->getSituacao());
        $this->assertSame($pca, $resultado->getPcaObservado());
        $this->assertNull($resultado->getFalha());
    }

    public function testPcaEncontradoDeveRejeitarFatoAusente(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ResultadoObservacaoPca::encontrado(PcaObservado::ausente());
    }

    public function testAusenciaDefinitivaDeveExporFatoAusenteSemFalha(): void
    {
        $resultado = ResultadoObservacaoPca::ausenciaDefinitiva();

        $this->assertSame(
            SituacaoObservacaoPca::AUSENCIA_DEFINITIVA,
            $resultado->getSituacao()
        );
        $this->assertFalse($resultado->getPcaObservado()?->estaAtivo());
        $this->assertNull($resultado->getFalha());
    }

    #[DataProvider('resultadosComFalha')]
    public function testResultadoNaoObservavelDeveExporFalhaSemFabricarPca(
        SituacaoObservacaoPca $situacao,
        string $factory
    ): void {
        $falha = new FalhaSegura('OBSERVACAO_INDISPONIVEL', 'Observação indisponível.');

        $resultado = ResultadoObservacaoPca::{$factory}($falha);

        $this->assertSame($situacao, $resultado->getSituacao());
        $this->assertNull($resultado->getPcaObservado());
        $this->assertSame($falha, $resultado->getFalha());
    }

    /** @return array<string, array{SituacaoObservacaoPca, string}> */
    public static function resultadosComFalha(): array
    {
        return [
            'consulta inconclusiva' => [SituacaoObservacaoPca::INCONCLUSIVA, 'inconclusiva'],
            'conflito' => [SituacaoObservacaoPca::CONFLITO, 'conflito'],
            'falha sistêmica' => [SituacaoObservacaoPca::FALHA_SISTEMICA, 'falhaSistemica'],
        ];
    }
}
