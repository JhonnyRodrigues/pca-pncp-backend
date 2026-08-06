<?php

declare(strict_types=1);

namespace Tests\Application\ValueObjects;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\ValueObjects\IntencaoRegistroHistorico;
use Semae\Pca\Domain\Enums\ProcedimentoPublicacaoPca;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Representante;

final class IntencaoRegistroHistoricoTest extends TestCase
{
    /** @regra RN-AUD-001 @regra RN-AUD-004 */
    public function testDeveRepresentarIntencaoSemAnteciparInstanteDoFato(): void
    {
        $representante = new Representante(709, 'Maria da Silva');
        $lotacao = new Lotacao(159, 'Coordenadoria de Tecnologia');
        $intencao = new IntencaoRegistroHistorico(
            ProcedimentoPublicacaoPca::RETIFICACAO,
            'Retificação confirmada.',
            $representante,
            $lotacao
        );

        $this->assertSame(ProcedimentoPublicacaoPca::RETIFICACAO, $intencao->getProcedimento());
        $this->assertSame('Retificação confirmada.', $intencao->getMensagem());
        $this->assertSame($representante, $intencao->getRepresentante());
        $this->assertSame($lotacao, $intencao->getLotacao());
        $this->assertFalse(method_exists($intencao, 'getInstante'));
    }

    public function testDeveRejeitarMensagemVazia(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new IntencaoRegistroHistorico(
            ProcedimentoPublicacaoPca::PUBLICACAO_INICIAL,
            '  ',
            new Representante(709, 'Maria da Silva'),
            new Lotacao(159, 'Coordenadoria de Tecnologia')
        );
    }
}
