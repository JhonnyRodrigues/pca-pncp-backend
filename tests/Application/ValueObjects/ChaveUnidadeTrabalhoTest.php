<?php

declare(strict_types=1);

namespace Tests\Application\ValueObjects;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\EscopoUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\ChaveUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class ChaveUnidadeTrabalhoTest extends TestCase
{
    public function testDeveRepresentarExclusividadeDaEstrategiaAnualSemUnidadeExterna(): void
    {
        $ano = new AnoExecucao((int) date('Y') + 1);

        $chave = ChaveUnidadeTrabalho::estrategiaAnual($ano);

        $this->assertSame(EscopoUnidadeTrabalho::ESTRATEGIA_ANUAL, $chave->getEscopo());
        $this->assertSame($ano, $chave->getAnoExecucao());
        $this->assertNull($chave->getUnidadeExterna());
    }

    public function testDeveRepresentarExclusividadeDaUnidadeExternaSemPerderSuaSemantica(): void
    {
        $unidade = UnidadeExternaPublicacao::consolidada(
            new AnoExecucao((int) date('Y') + 1),
            new Uasg('986700', 'UASG Consolidadora')
        );

        $chave = ChaveUnidadeTrabalho::unidadeExterna($unidade);

        $this->assertSame(EscopoUnidadeTrabalho::UNIDADE_EXTERNA, $chave->getEscopo());
        $this->assertSame($unidade->getAnoExecucao(), $chave->getAnoExecucao());
        $this->assertSame($unidade, $chave->getUnidadeExterna());
    }

    public function testChavesEquivalentesDevemSerComparadasPorEscopoEAlvoLogico(): void
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $primeira = ChaveUnidadeTrabalho::unidadeExterna(
            UnidadeExternaPublicacao::consolidada($ano, new Uasg('986700', 'Descrição A'))
        );
        $equivalente = ChaveUnidadeTrabalho::unidadeExterna(
            UnidadeExternaPublicacao::consolidada(
                new AnoExecucao($ano->getValor()),
                new Uasg('986700', 'Descrição B')
            )
        );

        $this->assertTrue($primeira->equals($equivalente));
        $this->assertFalse($primeira->equals(ChaveUnidadeTrabalho::estrategiaAnual($ano)));
    }
}
