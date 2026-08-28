<?php

declare(strict_types=1);

namespace Tests\Application\ValueObjects;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\ModoPublicacao;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class UnidadeExternaPublicacaoTest extends TestCase
{
    public function testDeveRepresentarUnidadeIndividualPelaIdentidadeDoPlano(): void
    {
        $identidade = $this->criarIdentidadePlano('UO-01', '986789');

        $unidade = UnidadeExternaPublicacao::individual($identidade);

        $this->assertSame(ModoPublicacao::INDIVIDUAL, $unidade->getModo());
        $this->assertSame($identidade, $unidade->getIdentidadePlano());
        $this->assertSame($identidade->getAnoExecucao(), $unidade->getAnoExecucao());
        $this->assertSame(
            $identidade->getUnidadeOrcamentaria()->getUasg(),
            $unidade->getUasgExterna()
        );
    }

    public function testDeveRepresentarUnidadeConsolidadaSemElegerUmPlanoComoDono(): void
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $uasg = new Uasg('986700', 'UASG Consolidadora');

        $unidade = UnidadeExternaPublicacao::consolidada($ano, $uasg);

        $this->assertSame(ModoPublicacao::CONSOLIDADO, $unidade->getModo());
        $this->assertSame($ano, $unidade->getAnoExecucao());
        $this->assertSame($uasg, $unidade->getUasgExterna());
        $this->assertNull($unidade->getIdentidadePlano());
    }

    public function testDeveCompararUnidadesPelaIdentidadeExternaSemUsarDescricaoDaUasg(): void
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $primeira = UnidadeExternaPublicacao::consolidada(
            $ano,
            new Uasg('986700', 'Descrição A')
        );
        $equivalente = UnidadeExternaPublicacao::consolidada(
            new AnoExecucao($ano->getValor()),
            new Uasg('986700', 'Descrição B')
        );

        $this->assertTrue($primeira->equals($equivalente));
        $this->assertFalse(
            $primeira->equals(
                UnidadeExternaPublicacao::consolidada($ano, new Uasg('986701', 'Descrição A'))
            )
        );
    }

    private function criarIdentidadePlano(string $idUo, string $codigoUasg): IdentidadePlano
    {
        $unidadeOrcamentaria = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria($idUo),
            new Lotacao(10, 'Diretoria'),
            new Uasg($codigoUasg, 'UASG da UO'),
            'Unidade Orçamentária'
        );

        return new IdentidadePlano(
            $unidadeOrcamentaria,
            new AnoExecucao((int) date('Y') + 1)
        );
    }
}
