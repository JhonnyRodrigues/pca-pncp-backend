<?php

declare(strict_types=1);

namespace Tests\Domain\Enums;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;

class DecisaoProcessamentoPlanoTest extends TestCase
{
    /** @regra RN-PUB-001 @regra RN-PUB-002 @regra RN-PUB-003 @regra RN-PUB-004 */
    public function testDeveRepresentarAsQuatroDecisoesVigentes(): void
    {
        $this->assertSame(
            ['PUBLICAR', 'RETIFICAR', 'EXCLUIR_PCA', 'NENHUMA_ACAO'],
            array_map(
                static fn (DecisaoProcessamentoPlano $decisao): string => $decisao->value,
                DecisaoProcessamentoPlano::cases()
            )
        );
    }
}
