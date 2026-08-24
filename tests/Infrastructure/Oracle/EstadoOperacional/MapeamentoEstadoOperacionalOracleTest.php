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

        $this->assertSame('APP.PAC_PLANOS', $mapeamento->getTabelaQualificada());
        $this->assertSame('FK_UO', $mapeamento->getColunaUnidadeOrcamentaria());
        $this->assertSame('ANO_EXERCICIO', $mapeamento->getColunaAnoExecucao());
        $this->assertSame('MODO_PUBLICACAO', $mapeamento->getColunaModo());
        $this->assertSame('UASG_PUBLICACAO', $mapeamento->getColunaUasg());
        $this->assertSame('ESTADO_PUBLICACAO', $mapeamento->getColunaEstado());
        $this->assertSame('SEQUENCIAL_PCA', $mapeamento->getColunaSequencial());
        $this->assertSame('I', $mapeamento->getLiteralModoIndividual());
        $this->assertSame('C', $mapeamento->getLiteralModoConsolidado());
        $this->assertSame('N', $mapeamento->getLiteralNaoPublicado());
        $this->assertSame('P', $mapeamento->getLiteralPublicado());
        $this->assertSame('E', $mapeamento->getLiteralExcluido());
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
            ['', 'C', 'N', 'P', 'E'],
            ['I', 'I', 'N', 'P', 'E'],
            ['I', 'C', 'N', 'N', 'E'],
        ] as [$individual, $consolidado, $naoPublicado, $publicado, $excluido]) {
            try {
                new MapeamentoEstadoOperacionalOracle(
                    'APP', 'PAC_PLANOS', 'FK_UO', 'ANO_EXERCICIO',
                    'MODO_PUBLICACAO', 'UASG_PUBLICACAO', 'ESTADO_PUBLICACAO',
                    'SEQUENCIAL_PCA', $individual, $consolidado, $naoPublicado,
                    $publicado, $excluido
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
            'app', $tabela, 'fk_uo', 'ano_exercicio', 'modo_publicacao',
            'uasg_publicacao', 'estado_publicacao', 'sequencial_pca',
            'I', 'C', 'N', 'P', 'E'
        );
    }
}
