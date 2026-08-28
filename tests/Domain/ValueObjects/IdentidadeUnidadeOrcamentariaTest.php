<?php

declare(strict_types=1);

namespace Tests\Domain\ValueObjects;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;

class IdentidadeUnidadeOrcamentariaTest extends TestCase
{
    /** @regra RN-PLA-001 */
    public function testDeveRepresentarIdentidadeOpacaDaUnidadeOrcamentaria(): void
    {
        $identidade = new IdentidadeUnidadeOrcamentaria('uo-diretoria-administrativa');

        $this->assertSame('uo-diretoria-administrativa', $identidade->getValor());
    }

    /** @regra RN-PLA-001 */
    public function testDeveCompararIdentidadesPorValor(): void
    {
        $identidade = new IdentidadeUnidadeOrcamentaria('uo-101');

        $this->assertTrue($identidade->equals(new IdentidadeUnidadeOrcamentaria('uo-101')));
        $this->assertFalse($identidade->equals(new IdentidadeUnidadeOrcamentaria('uo-102')));
    }

    /** @regra RN-PLA-001 */
    #[DataProvider('valoresInvalidos')]
    public function testDeveRejeitarIdentidadeVazia(string $valor): void
    {
        $this->expectException(InvalidArgumentException::class);

        new IdentidadeUnidadeOrcamentaria($valor);
    }

    public static function valoresInvalidos(): array
    {
        return [
            'vazia' => [''],
            'apenas espaços' => ['   '],
        ];
    }
}
