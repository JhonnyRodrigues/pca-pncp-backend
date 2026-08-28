<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Oracle\EstadoOperacional;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Infrastructure\Oracle\EstadoOperacional\MapeamentoEstadoOperacionalOracle;

final class MapeamentoEstadoOperacionalOracleTest extends TestCase
{
    public function testDeveNormalizarIdentificadoresELiteraisInjetados(): void
    {
        $mapeamento = $this->criarMapeamento();

        $this->assertSame('SGI.PAC_PLANOS', $mapeamento->getTabelaQualificada());
        $this->assertSame(
            'FK_UNIDADE_ORCAMENTARIA',
            $mapeamento->getColunaUnidadeOrcamentaria()
        );
        $this->assertSame('ANO_EXERCICIO', $mapeamento->getColunaAnoExecucao());
        $this->assertSame(
            'ESTRATEGIA_ADOTADA_PUBLICACAO',
            $mapeamento->getColunaEstrategia()
        );
        $this->assertSame('UASG_ADOTADA', $mapeamento->getColunaUasg());
        $this->assertSame('ESTADO_PUBLICACAO', $mapeamento->getColunaEstadoPublicacao());
        $this->assertSame(
            'SEQUENCIAL_PUBLICACAO_PNCP',
            $mapeamento->getColunaSequencialPublicacaoPncp()
        );
        $this->assertSame('INDIVIDUAL', $mapeamento->getLiteralEstrategiaIndividual());
        $this->assertSame('CONSOLIDADO', $mapeamento->getLiteralEstrategiaConsolidada());
        $this->assertSame('NAO_PUBLICADO', $mapeamento->getLiteralNaoPublicado());
        $this->assertSame('PUBLICADO', $mapeamento->getLiteralPublicado());
        $this->assertSame('EXCLUIDO', $mapeamento->getLiteralExcluido());
    }

    public function testDeveRejeitarIdentificadorOracleInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('identificador Oracle inválido');

        $this->criarMapeamento('PAC_PLANOS; DROP TABLE X');
    }

    public function testDeveRejeitarLiteraisVaziosOuDuplicados(): void
    {
        foreach ([
            ['', 'CONSOLIDADO', 'NAO_PUBLICADO', 'PUBLICADO', 'EXCLUIDO'],
            ['INDIVIDUAL', 'INDIVIDUAL', 'NAO_PUBLICADO', 'PUBLICADO', 'EXCLUIDO'],
            ['INDIVIDUAL', 'CONSOLIDADO', 'PUBLICADO', 'PUBLICADO', 'EXCLUIDO'],
        ] as [$individual, $consolidado, $naoPublicado, $publicado, $excluido]) {
            try {
                new MapeamentoEstadoOperacionalOracle(
                    'SGI', 'PAC_PLANOS', 'FK_UNIDADE_ORCAMENTARIA', 'ANO_EXERCICIO',
                    'ESTRATEGIA_ADOTADA_PUBLICACAO', 'UASG_ADOTADA',
                    'ESTADO_PUBLICACAO', 'SEQUENCIAL_PUBLICACAO_PNCP',
                    $individual, $consolidado, $naoPublicado, $publicado, $excluido
                );
                $this->fail('Era esperado mapeamento inválido.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    private function criarMapeamento(string $tabela = 'pac_planos'): MapeamentoEstadoOperacionalOracle
    {
        return new MapeamentoEstadoOperacionalOracle(
            'sgi',
            $tabela,
            'fk_unidade_orcamentaria',
            'ano_exercicio',
            'estrategia_adotada_publicacao',
            'uasg_adotada',
            'estado_publicacao',
            'sequencial_publicacao_pncp',
            'INDIVIDUAL',
            'CONSOLIDADO',
            'NAO_PUBLICADO',
            'PUBLICADO',
            'EXCLUIDO'
        );
    }
}
