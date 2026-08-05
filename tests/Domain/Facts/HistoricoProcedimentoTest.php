<?php

declare(strict_types=1);

namespace Tests\Domain\Facts;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\Enums\ProcedimentoPublicacaoPca;
use Semae\Pca\Domain\Exceptions\HistoricoProcedimentoException;
use Semae\Pca\Domain\Facts\HistoricoProcedimento;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Representante;

class HistoricoProcedimentoTest extends TestCase
{
    /** @regra RN-AUD-002 */
    public function testDeveClassificarSomenteProcedimentosConfirmadosDePublicacaoPca(): void
    {
        $this->assertSame(
            [5, 8, 9],
            array_map(
                static fn (ProcedimentoPublicacaoPca $procedimento): int => $procedimento->value,
                ProcedimentoPublicacaoPca::cases()
            )
        );
    }

    /** @regra RN-AUD-004 */
    public function testDeveRepresentarFatoComAutoriaLotacaoEMensagem(): void
    {
        $instante = new DateTimeImmutable('2026-07-15 14:25:00');
        $representante = new Representante(709, 'Maria da Silva');
        $lotacao = new Lotacao(159, 'Coordenadoria de Tecnologia');

        $historico = new HistoricoProcedimento(
            $instante,
            ProcedimentoPublicacaoPca::RETIFICACAO,
            'Retificação confirmada para o exercício 2027 na UASG 928344, sequencial 77, com 2 inclusões e 1 exclusão.',
            $representante,
            $lotacao
        );

        $this->assertSame($instante, $historico->getInstante());
        $this->assertSame(ProcedimentoPublicacaoPca::RETIFICACAO, $historico->getProcedimento());
        $this->assertSame($representante, $historico->getRepresentante());
        $this->assertSame($lotacao, $historico->getLotacao());
    }

    /** @regra RN-AUD-005 @evidencia-parcial obrigatoriedade da mensagem */
    public function testDeveRejeitarMensagemVazia(): void
    {
        $this->expectException(HistoricoProcedimentoException::class);

        new HistoricoProcedimento(
            new DateTimeImmutable('2026-07-15 14:25:00'),
            ProcedimentoPublicacaoPca::PUBLICACAO_INICIAL,
            '   ',
            new Representante(709, 'Maria da Silva'),
            new Lotacao(159, 'Coordenadoria de Tecnologia')
        );
    }
}
