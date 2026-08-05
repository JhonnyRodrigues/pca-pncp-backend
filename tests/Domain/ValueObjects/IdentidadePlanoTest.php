<?php

declare(strict_types=1);

namespace Tests\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Uasg;

class IdentidadePlanoTest extends TestCase
{
    /** @regra RN-PLA-001 */
    public function testDeveCombinarUnidadeOrcamentariaEAnoExecucao(): void
    {
        $unidade = $this->criarUnidade('uo-101');
        $ano = new AnoExecucao((int) date('Y') + 1);

        $identidade = new IdentidadePlano($unidade, $ano);

        $this->assertSame($unidade, $identidade->getUnidadeOrcamentaria());
        $this->assertSame($ano, $identidade->getAnoExecucao());
    }

    /** @regra RN-PLA-001 */
    public function testDeveCompararPlanosPorIdentidadeDaUoEAno(): void
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $identidade = new IdentidadePlano($this->criarUnidade('uo-101'), $ano);

        $this->assertTrue($identidade->equals(new IdentidadePlano(
            $this->criarUnidade('uo-101'),
            new AnoExecucao($ano->getValor())
        )));
        $this->assertFalse($identidade->equals(new IdentidadePlano(
            $this->criarUnidade('uo-102'),
            new AnoExecucao($ano->getValor())
        )));
    }

    private function criarUnidade(string $identidade): UnidadeOrcamentaria
    {
        return new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria($identidade),
            new Lotacao(101, 'Diretoria Administrativa'),
            new Uasg('928344', 'SEMAE Piracicaba'),
            'Diretoria Administrativa'
        );
    }
}
