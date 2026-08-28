<?php

declare(strict_types=1);

namespace Tests\Domain\ValueObjects;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\Enums\EstadoPublicacao;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;
use Semae\Pca\Domain\ValueObjects\SequencialPca;

class EstadoOperacionalPublicacaoTest extends TestCase
{
    /** @regra RN-PUB-008 */
    public function testNaoPublicadoNaoPossuiSequencial(): void
    {
        $estado = EstadoOperacionalPublicacao::naoPublicado();

        $this->assertSame(EstadoPublicacao::NAO_PUBLICADO, $estado->getEstado());
        $this->assertNull($estado->getSequencialPca());
    }

    /** @regra RN-PUB-008 */
    public function testPublicadoPossuiSequencialPositivo(): void
    {
        $sequencial = new SequencialPca(77);
        $estado = EstadoOperacionalPublicacao::publicado($sequencial);

        $this->assertSame(EstadoPublicacao::PUBLICADO, $estado->getEstado());
        $this->assertSame($sequencial, $estado->getSequencialPca());
        $this->assertTrue($estado->estaAtivo());
    }

    /** @regra RN-PUB-008 */
    public function testExcluidoPreservaUltimoSequencialSemPermanecerAtivo(): void
    {
        $sequencial = new SequencialPca(77);
        $estado = EstadoOperacionalPublicacao::excluido($sequencial);

        $this->assertSame(EstadoPublicacao::EXCLUIDO, $estado->getEstado());
        $this->assertSame($sequencial, $estado->getSequencialPca());
        $this->assertFalse($estado->estaAtivo());
    }

    /** @regra RN-PUB-008 */
    public function testDeveRejeitarSequencialNaoPositivo(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SequencialPca(0);
    }
}
