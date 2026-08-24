<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Oracle\Auditoria;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Infrastructure\Oracle\Auditoria\MapeamentoHistoricosOracle;

final class MapeamentoHistoricosOracleTest extends TestCase
{
    public function testDeveNormalizarTodosOsNomesFisicosSemDefaults(): void
    {
        $mapeamento = new MapeamentoHistoricosOracle(
            'app', 'pac_historicos', 'data_historico', 'fk_procedimento',
            'mensagem', 'fk_representante', 'fk_lotacao'
        );

        $this->assertSame('APP.PAC_HISTORICOS', $mapeamento->getTabelaQualificada());
        $this->assertSame('DATA_HISTORICO', $mapeamento->getColunaInstante());
        $this->assertSame('FK_PROCEDIMENTO', $mapeamento->getColunaProcedimento());
        $this->assertSame('MENSAGEM', $mapeamento->getColunaMensagem());
        $this->assertSame('FK_REPRESENTANTE', $mapeamento->getColunaRepresentante());
        $this->assertSame('FK_LOTACAO', $mapeamento->getColunaLotacao());
    }

    public function testDeveRejeitarQualquerIdentificadorOracleInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('identificador Oracle inválido');

        new MapeamentoHistoricosOracle(
            'APP', 'PAC_HISTORICOS', 'DATA_HISTORICO', 'FK_PROCEDIMENTO',
            'MENSAGEM) VALUES (SENHA', 'FK_REPRESENTANTE', 'FK_LOTACAO'
        );
    }
}
