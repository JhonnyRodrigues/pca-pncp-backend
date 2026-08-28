<?php

declare(strict_types=1);

namespace Tests\Domain\Enums;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\Enums\SituacaoDemanda;

final class SituacaoDemandaTest extends TestCase
{
    /** @regra RN-DEM-009 */
    public function testDeveRepresentarCatalogoVigenteEParticipacaoNaFotografia(): void
    {
        $this->assertFalse(SituacaoDemanda::PLANEJADA->participaDaFotografiaVigente());
        $this->assertTrue(SituacaoDemanda::HOMOLOGADA->participaDaFotografiaVigente());
        $this->assertTrue(SituacaoDemanda::COMPRADA->participaDaFotografiaVigente());
        $this->assertFalse(SituacaoDemanda::CANCELADA->participaDaFotografiaVigente());
        $this->assertTrue(SituacaoDemanda::PUBLICADA->participaDaFotografiaVigente());
    }

    /** @regra RN-DEM-009 */
    public function testDevePreservarCodigosConfirmadosDaIntegracao(): void
    {
        $this->assertSame([1, 2, 3, 4, 5], array_map(
            static fn (SituacaoDemanda $situacao): int => $situacao->value,
            SituacaoDemanda::cases()
        ));
    }

    /** @regra RN-DEM-009 */
    public function testNaoDeveInventarSituacaoDesconhecida(): void
    {
        $this->assertNull(SituacaoDemanda::tryFrom(999));
    }
}
