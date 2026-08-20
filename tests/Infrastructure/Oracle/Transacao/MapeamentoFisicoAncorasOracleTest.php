<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Oracle\Transacao;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Infrastructure\Oracle\Transacao\MapeamentoFisicoAncorasOracle;

final class MapeamentoFisicoAncorasOracleTest extends TestCase
{
    public function testDeveNormalizarEExporSomenteOMapeamentoFisicoDeclarado(): void
    {
        $mapeamento = new MapeamentoFisicoAncorasOracle(
            ' app ',
            ' pac_ancoras ',
            ' escopo ',
            ' ano_exercicio ',
            ' identificador_alvo ',
            ' ESTRATEGIA_GLOBAL '
        );

        $this->assertSame('APP.PAC_ANCORAS', $mapeamento->getTabelaQualificada());
        $this->assertSame('ESCOPO', $mapeamento->getColunaEscopo());
        $this->assertSame('ANO_EXERCICIO', $mapeamento->getColunaAnoExecucao());
        $this->assertSame('IDENTIFICADOR_ALVO', $mapeamento->getColunaIdentificadorAlvo());
        $this->assertSame('ESTRATEGIA_GLOBAL', $mapeamento->getIdentificadorAlvoEstrategiaAnual());
    }

    /** @return iterable<string, array{string, string, string, string, string, string}> */
    public static function mapeamentosInvalidos(): iterable
    {
        yield 'schema vazio' => ['', 'PAC_ANCORAS', 'ESCOPO', 'ANO', 'ALVO', 'ANUAL'];
        yield 'tabela insegura' => ['APP', 'PAC_ANCORAS;DROP', 'ESCOPO', 'ANO', 'ALVO', 'ANUAL'];
        yield 'coluna com ponto' => ['APP', 'PAC_ANCORAS', 'A.ESCOPO', 'ANO', 'ALVO', 'ANUAL'];
        yield 'alvo anual vazio' => ['APP', 'PAC_ANCORAS', 'ESCOPO', 'ANO', 'ALVO', '   '];
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

        new MapeamentoFisicoAncorasOracle(
            $schema,
            $tabela,
            $colunaEscopo,
            $colunaAno,
            $colunaAlvo,
            $alvoAnual
        );
    }
}
