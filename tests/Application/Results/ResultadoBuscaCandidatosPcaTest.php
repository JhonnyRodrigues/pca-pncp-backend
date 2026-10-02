<?php

declare(strict_types=1);

namespace Tests\Application\Results;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\SituacaoBuscaCandidatosPca;
use Semae\Pca\Application\Results\ResultadoBuscaCandidatosPca;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Domain\ValueObjects\PcaObservado;
use Semae\Pca\Domain\ValueObjects\SequencialPca;

final class ResultadoBuscaCandidatosPcaTest extends TestCase
{
    public function testBuscaConcluidaPodeRetornarNenhumCandidato(): void
    {
        $resultado = ResultadoBuscaCandidatosPca::concluida([]);

        $this->assertSame(SituacaoBuscaCandidatosPca::CONCLUIDA, $resultado->getSituacao());
        $this->assertSame([], $resultado->getCandidatos());
        $this->assertNull($resultado->getFalha());
    }

    public function testBuscaConcluidaDevePreservarCandidatosAtivos(): void
    {
        $candidatos = [
            PcaObservado::ativo(new SequencialPca(55), [1001]),
            PcaObservado::ativo(new SequencialPca(56), [1002]),
        ];

        $resultado = ResultadoBuscaCandidatosPca::concluida($candidatos);

        $this->assertSame($candidatos, $resultado->getCandidatos());
    }

    public function testBuscaConcluidaDeveRejeitarFatoAusenteComoCandidato(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ResultadoBuscaCandidatosPca::concluida([PcaObservado::ausente()]);
    }

    public function testBuscaConcluidaDeveRejeitarSequenciaisDuplicados(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ResultadoBuscaCandidatosPca::concluida([
            PcaObservado::ativo(new SequencialPca(55), [1001]),
            PcaObservado::ativo(new SequencialPca(55), [1002]),
        ]);
    }

    /** @param array<int, mixed> $candidatos */
    #[DataProvider('candidatosInvalidos')]
    public function testBuscaConcluidaDeveRejeitarValoresQueNaoSaoPcaObservado(
        array $candidatos
    ): void {
        $this->expectException(InvalidArgumentException::class);

        ResultadoBuscaCandidatosPca::concluida($candidatos);
    }

    /** @return array<string, array{array<int, mixed>}> */
    public static function candidatosInvalidos(): array
    {
        return [
            'inteiro' => [[55]],
            'texto' => [['55']],
        ];
    }

    #[DataProvider('resultadosComFalha')]
    public function testBuscaNaoConcluidaDeveExporFalhaSemFabricarCandidatos(
        SituacaoBuscaCandidatosPca $situacao,
        string $factory
    ): void {
        $falha = new FalhaSegura('BUSCA_INDISPONIVEL', 'Busca indisponível.');

        $resultado = ResultadoBuscaCandidatosPca::{$factory}($falha);

        $this->assertSame($situacao, $resultado->getSituacao());
        $this->assertSame([], $resultado->getCandidatos());
        $this->assertSame($falha, $resultado->getFalha());
    }

    /** @return array<string, array{SituacaoBuscaCandidatosPca, string}> */
    public static function resultadosComFalha(): array
    {
        return [
            'consulta inconclusiva' => [SituacaoBuscaCandidatosPca::INCONCLUSIVA, 'inconclusiva'],
            'conflito' => [SituacaoBuscaCandidatosPca::CONFLITO, 'conflito'],
            'falha sistêmica' => [SituacaoBuscaCandidatosPca::FALHA_SISTEMICA, 'falhaSistemica'],
        ];
    }
}
