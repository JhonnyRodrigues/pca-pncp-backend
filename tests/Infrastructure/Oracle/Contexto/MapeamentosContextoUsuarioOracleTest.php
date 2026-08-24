<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Oracle\Contexto;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Infrastructure\Oracle\Contexto\MapeamentoLotacoesUsuarioOracle;
use Semae\Pca\Infrastructure\Oracle\Contexto\MapeamentoRepresentantesOracle;

final class MapeamentosContextoUsuarioOracleTest extends TestCase
{
    public function testDeveNormalizarMapeamentoDeRepresentantesSemDefaults(): void
    {
        $mapeamento = new MapeamentoRepresentantesOracle(
            'sgi', 'rh_funcionarios', 'nome_usuario', 'cod_funcionario',
            'nome_civil', 'ativo', 1
        );

        $this->assertSame('SGI.RH_FUNCIONARIOS', $mapeamento->getTabelaQualificada());
        $this->assertSame('NOME_USUARIO', $mapeamento->getColunaLogin());
        $this->assertSame('COD_FUNCIONARIO', $mapeamento->getColunaId());
        $this->assertSame('NOME_CIVIL', $mapeamento->getColunaNome());
        $this->assertSame('ATIVO', $mapeamento->getColunaAtivo());
        $this->assertSame(1, $mapeamento->getValorAtivo());
    }

    public function testDeveNormalizarMapeamentoDeVinculosELotacoesSemDefaults(): void
    {
        $mapeamento = new MapeamentoLotacoesUsuarioOracle(
            'sgi', 'rh_funcionarios_lotacoes', 'rh_lotacoes',
            'cod_funcionario', 'cod_lotacao', 'dt_fim_lotacao',
            'cod_lotacao', 'nome_lotacao', 'ativo', 1
        );

        $this->assertSame('SGI.RH_FUNCIONARIOS_LOTACOES', $mapeamento->getTabelaVinculosQualificada());
        $this->assertSame('SGI.RH_LOTACOES', $mapeamento->getTabelaLotacoesQualificada());
        $this->assertSame('COD_FUNCIONARIO', $mapeamento->getColunaFuncionarioVinculo());
        $this->assertSame('COD_LOTACAO', $mapeamento->getColunaLotacaoVinculo());
        $this->assertSame('DT_FIM_LOTACAO', $mapeamento->getColunaFimVinculo());
        $this->assertSame('COD_LOTACAO', $mapeamento->getColunaIdLotacao());
        $this->assertSame('NOME_LOTACAO', $mapeamento->getColunaNomeLotacao());
        $this->assertSame('ATIVO', $mapeamento->getColunaAtivoLotacao());
        $this->assertSame(1, $mapeamento->getValorAtivo());
    }

    public function testDeveRejeitarIdentificadoresELiteraisInvalidos(): void
    {
        $fabricas = [
            static fn () => new MapeamentoRepresentantesOracle(
                'SGI', 'RH_FUNCIONARIOS; DELETE X', 'LOGIN', 'ID', 'NOME', 'ATIVO', 1
            ),
            static fn () => new MapeamentoRepresentantesOracle(
                'SGI', 'RH_FUNCIONARIOS', 'LOGIN', 'ID', 'NOME', 'ATIVO', ''
            ),
            static fn () => new MapeamentoLotacoesUsuarioOracle(
                'SGI', 'RH_FUNCIONARIOS_LOTACOES', 'RH LOTACOES',
                'FUNCIONARIO', 'LOTACAO', 'FIM', 'ID', 'NOME', 'ATIVO', 1
            ),
            static fn () => new MapeamentoLotacoesUsuarioOracle(
                'SGI', 'RH_FUNCIONARIOS_LOTACOES', 'RH_LOTACOES',
                'FUNCIONARIO', 'LOTACAO', 'FIM', 'ID', 'NOME', 'ATIVO', ''
            ),
        ];

        foreach ($fabricas as $fabrica) {
            try {
                $fabrica();
                $this->fail('Era esperado mapeamento Oracle inválido.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
