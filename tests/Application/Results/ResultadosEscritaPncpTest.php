<?php

declare(strict_types=1);

namespace Tests\Application\Results;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\SituacaoEscritaPncp;
use Semae\Pca\Application\Results\ResultadoEscritaPncp;
use Semae\Pca\Application\Results\ResultadoPublicacaoPncp;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Domain\ValueObjects\SequencialPca;

final class ResultadosEscritaPncpTest extends TestCase
{
    public function testEscritaAceitaNaoDeveFabricarFalha(): void
    {
        $resultado = ResultadoEscritaPncp::aceita();

        $this->assertSame(SituacaoEscritaPncp::ACEITA, $resultado->getSituacao());
        $this->assertNull($resultado->getFalha());
    }

    #[DataProvider('resultadosDeEscritaComFalha')]
    public function testEscritaNaoAceitaDeveConterFalhaSegura(
        SituacaoEscritaPncp $situacao,
        string $factory
    ): void {
        $falha = new FalhaSegura('ESCRITA_NAO_CONFIRMADA', 'Escrita não confirmada.');

        $resultado = ResultadoEscritaPncp::{$factory}($falha);

        $this->assertSame($situacao, $resultado->getSituacao());
        $this->assertSame($falha, $resultado->getFalha());
    }

    public function testPublicacaoAceitaDeveConterNovoSequencialSemFalha(): void
    {
        $sequencial = new SequencialPca(55);

        $resultado = ResultadoPublicacaoPncp::aceita($sequencial);

        $this->assertSame(SituacaoEscritaPncp::ACEITA, $resultado->getSituacao());
        $this->assertSame($sequencial, $resultado->getSequencialPca());
        $this->assertNull($resultado->getFalha());
    }

    #[DataProvider('resultadosDeEscritaComFalha')]
    public function testPublicacaoNaoAceitaNaoDeveFabricarSequencial(
        SituacaoEscritaPncp $situacao,
        string $factory
    ): void {
        $falha = new FalhaSegura('PUBLICACAO_NAO_CONFIRMADA', 'Publicação não confirmada.');

        $resultado = ResultadoPublicacaoPncp::{$factory}($falha);

        $this->assertSame($situacao, $resultado->getSituacao());
        $this->assertNull($resultado->getSequencialPca());
        $this->assertSame($falha, $resultado->getFalha());
    }

    /** @return array<string, array{SituacaoEscritaPncp, string}> */
    public static function resultadosDeEscritaComFalha(): array
    {
        return [
            'rejeitada' => [SituacaoEscritaPncp::REJEITADA, 'rejeitada'],
            'resultado incerto' => [
                SituacaoEscritaPncp::RESULTADO_INCERTO,
                'resultadoIncerto',
            ],
            'falha sistêmica' => [SituacaoEscritaPncp::FALHA_SISTEMICA, 'falhaSistemica'],
        ];
    }
}
