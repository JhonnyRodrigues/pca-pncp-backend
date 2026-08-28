<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Oracle\Transacao;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Infrastructure\Oracle\Transacao\MapeamentoControlesConcorrenciaOracle;

final class MapeamentoControlesConcorrenciaOracleTest extends TestCase
{
    public function testDeveNormalizarEExporSomenteOMapeamentoFisicoDeclarado(): void
    {
        $mapeamento = new MapeamentoControlesConcorrenciaOracle(
            ' sgi ',
            ' pac_controles_concorrencia ',
            ' escopo ',
            ' ano_execucao ',
            ' identificador_alvo ',
            ' ESTRATEGIA_ANUAL '
        );

        $this->assertSame(
            'SGI.PAC_CONTROLES_CONCORRENCIA',
            $mapeamento->getTabelaQualificada()
        );
        $this->assertSame('ESCOPO', $mapeamento->getColunaEscopo());
        $this->assertSame('ANO_EXECUCAO', $mapeamento->getColunaAnoExecucao());
        $this->assertSame('IDENTIFICADOR_ALVO', $mapeamento->getColunaIdentificadorAlvo());
        $this->assertSame('ESTRATEGIA_ANUAL', $mapeamento->getIdentificadorAlvoEstrategiaAnual());
    }

    /** @return iterable<string, array{string, string, string, string, string, string}> */
    public static function mapeamentosInvalidos(): iterable
    {
        yield 'schema vazio' => [
            '', 'PAC_CONTROLES_CONCORRENCIA', 'ESCOPO', 'ANO', 'ALVO', 'ANUAL',
        ];
        yield 'tabela insegura' => [
            'SGI', 'PAC_CONTROLES_CONCORRENCIA;DROP', 'ESCOPO', 'ANO', 'ALVO', 'ANUAL',
        ];
        yield 'coluna com ponto' => [
            'SGI', 'PAC_CONTROLES_CONCORRENCIA', 'C.ESCOPO', 'ANO', 'ALVO', 'ANUAL',
        ];
        yield 'alvo anual vazio' => [
            'SGI', 'PAC_CONTROLES_CONCORRENCIA', 'ESCOPO', 'ANO', 'ALVO', '   ',
        ];
    }

    #[DataProvider('mapeamentosInvalidos')]
    public function testDeveRejeitarMapeamentoIncompletoOuInseguro(
        string $schema,
        string $tabela,
        string $colunaEscopo,
        string $colunaAno,
        string $colunaAlvo,
        string $alvoAnual
    ): void {
        $this->expectException(InvalidArgumentException::class);

        new MapeamentoControlesConcorrenciaOracle(
            $schema,
            $tabela,
            $colunaEscopo,
            $colunaAno,
            $colunaAlvo,
            $alvoAnual
        );
    }
}
