<?php

declare(strict_types=1);

namespace Tests\Application\Results;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Results\ResultadoProcessamentoUnidade;
use Semae\Pca\Application\Results\ResultadoVarreduraUnidades;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class ResultadoVarreduraUnidadesTest extends TestCase
{
    public function testConcluidaDevePreservarSomenteResultadosVisitadosSemFalha(): void
    {
        $resultadoUnitario = ResultadoProcessamentoUnidade::semAlteracao(
            $this->criarUnidade('uo-101', '928344')
        );

        $resultado = ResultadoVarreduraUnidades::concluida([$resultadoUnitario]);

        $this->assertSame([$resultadoUnitario], $resultado->getResultadosUnitarios());
        $this->assertNull($resultado->getFalhaSistemica());
        $this->assertFalse($resultado->foiInterrompida());
    }

    public function testInterrompidaDevePreservarResultadosAnterioresEFalhaGlobal(): void
    {
        $resultadoUnitario = ResultadoProcessamentoUnidade::semAlteracao(
            $this->criarUnidade('uo-101', '928344')
        );
        $falha = new FalhaSegura('VARREDURA_INTERROMPIDA', 'Varredura interrompida.');

        $resultado = ResultadoVarreduraUnidades::interrompida(
            [$resultadoUnitario],
            $falha
        );

        $this->assertSame([$resultadoUnitario], $resultado->getResultadosUnitarios());
        $this->assertSame($falha, $resultado->getFalhaSistemica());
        $this->assertTrue($resultado->foiInterrompida());
    }

    public function testResultadoNaoDeveAceitarUnidadesDuplicadas(): void
    {
        $unidade = $this->criarUnidade('uo-101', '928344');
        $primeiro = ResultadoProcessamentoUnidade::semAlteracao($unidade);
        $segundo = ResultadoProcessamentoUnidade::semAlteracao($unidade);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Varredura não pode possuir resultados duplicados.');

        ResultadoVarreduraUnidades::concluida([$primeiro, $segundo]);
    }

    private function criarUnidade(string $idUo, string $uasg): UnidadeExternaPublicacao
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidade = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria($idUo),
            new Lotacao(101, 'Diretoria Administrativa'),
            new Uasg($uasg, 'UASG da UO'),
            'Diretoria Administrativa'
        );

        return UnidadeExternaPublicacao::individual(new IdentidadePlano($unidade, $ano));
    }
}
