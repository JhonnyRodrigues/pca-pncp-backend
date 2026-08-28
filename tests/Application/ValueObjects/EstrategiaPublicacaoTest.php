<?php

declare(strict_types=1);

namespace Tests\Application\ValueObjects;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\ModoPublicacao;
use Semae\Pca\Application\ValueObjects\EstrategiaPublicacao;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class EstrategiaPublicacaoTest extends TestCase
{
    public function testDeveRepresentarEstrategiaIndividualSemUasgConsolidadora(): void
    {
        $estrategia = EstrategiaPublicacao::individual();

        $this->assertSame(ModoPublicacao::INDIVIDUAL, $estrategia->getModo());
        $this->assertNull($estrategia->getUasgConsolidadora());
    }

    public function testDeveRepresentarEstrategiaConsolidadaComUasgConsolidadora(): void
    {
        $uasg = new Uasg('986789', 'UASG Consolidadora');

        $estrategia = EstrategiaPublicacao::consolidada($uasg);

        $this->assertSame(ModoPublicacao::CONSOLIDADO, $estrategia->getModo());
        $this->assertSame($uasg, $estrategia->getUasgConsolidadora());
    }

    public function testDeveCompararEstrategiasPeloModoECodigoDaUasgConsolidadora(): void
    {
        $primeira = EstrategiaPublicacao::consolidada(new Uasg('986789', 'Descrição A'));
        $equivalente = EstrategiaPublicacao::consolidada(new Uasg('986789', 'Descrição B'));
        $diferente = EstrategiaPublicacao::consolidada(new Uasg('986790', 'Descrição A'));

        $this->assertTrue($primeira->equals($equivalente));
        $this->assertFalse($primeira->equals($diferente));
        $this->assertFalse($primeira->equals(EstrategiaPublicacao::individual()));
    }
}
