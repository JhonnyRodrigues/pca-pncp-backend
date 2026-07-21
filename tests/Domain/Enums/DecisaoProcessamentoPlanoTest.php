<?php

declare(strict_types=1);

namespace Tests\Domain\Enums;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;

class DecisaoProcessamentoPlanoTest extends TestCase
{
    public function testDeveRepresentarSomenteDecisoesDeProcessamentoPermitidas(): void
    {
        $this->assertEqualsCanonicalizing(
            [
                DecisaoProcessamentoPlano::PUBLICAR,
                DecisaoProcessamentoPlano::RETIFICAR,
                DecisaoProcessamentoPlano::NENHUMA_ACAO,
            ],
            DecisaoProcessamentoPlano::cases()
        );
        // fwrite(STDERR, print_r(DecisaoProcessamentoPlano::cases(), true));
    }
}
