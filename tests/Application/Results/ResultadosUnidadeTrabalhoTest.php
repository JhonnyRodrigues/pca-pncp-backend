<?php

declare(strict_types=1);

namespace Tests\Application\Results;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\SituacaoFinalizacaoUnidadeTrabalho;
use Semae\Pca\Application\Enums\SituacaoInicioUnidadeTrabalho;
use Semae\Pca\Application\Results\ResultadoFinalizacaoUnidadeTrabalho;
use Semae\Pca\Application\Results\ResultadoInicioUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\FalhaSegura;

final class ResultadosUnidadeTrabalhoTest extends TestCase
{
    public function testInicioComExclusividadeNaoDeveFabricarFalha(): void
    {
        $resultado = ResultadoInicioUnidadeTrabalho::iniciada();

        $this->assertSame(SituacaoInicioUnidadeTrabalho::INICIADA, $resultado->getSituacao());
        $this->assertNull($resultado->getFalha());
    }

    #[DataProvider('iniciosSemExclusividade')]
    public function testInicioSemExclusividadeDeveConterFalhaSegura(
        SituacaoInicioUnidadeTrabalho $situacao,
        string $factory
    ): void {
        $falha = new FalhaSegura('UNIDADE_NAO_INICIADA', 'Unidade não iniciada.');

        $resultado = ResultadoInicioUnidadeTrabalho::{$factory}($falha);

        $this->assertSame($situacao, $resultado->getSituacao());
        $this->assertSame($falha, $resultado->getFalha());
    }

    public function testFinalizacaoConcluidaNaoDeveFabricarFalha(): void
    {
        $resultado = ResultadoFinalizacaoUnidadeTrabalho::concluida();

        $this->assertSame(
            SituacaoFinalizacaoUnidadeTrabalho::CONCLUIDA,
            $resultado->getSituacao()
        );
        $this->assertNull($resultado->getFalha());
    }

    public function testFalhaSistemicaNaFinalizacaoDevePermanecerTipada(): void
    {
        $falha = new FalhaSegura('FINALIZACAO_FALHOU', 'Finalização local falhou.');

        $resultado = ResultadoFinalizacaoUnidadeTrabalho::falhaSistemica($falha);

        $this->assertSame(
            SituacaoFinalizacaoUnidadeTrabalho::FALHA_SISTEMICA,
            $resultado->getSituacao()
        );
        $this->assertSame($falha, $resultado->getFalha());
    }

    /** @return array<string, array{SituacaoInicioUnidadeTrabalho, string}> */
    public static function iniciosSemExclusividade(): array
    {
        return [
            'contenção' => [SituacaoInicioUnidadeTrabalho::BLOQUEADA, 'bloqueada'],
            'falha sistêmica' => [
                SituacaoInicioUnidadeTrabalho::FALHA_SISTEMICA,
                'falhaSistemica',
            ],
        ];
    }
}
