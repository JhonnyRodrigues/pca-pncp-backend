<?php

declare(strict_types=1);

namespace Tests\Application\Results;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\SituacaoConsultaEstrategia;
use Semae\Pca\Application\Results\ResultadoAdocaoEstrategia;
use Semae\Pca\Application\Results\ResultadoConsultaEstrategia;
use Semae\Pca\Application\ValueObjects\EstrategiaPublicacao;
use Semae\Pca\Application\ValueObjects\FalhaSegura;

final class ResultadoEstrategiaPublicacaoTest extends TestCase
{
    public function testConsultaDisponivelDeveConterSomenteAEstrategia(): void
    {
        $estrategia = EstrategiaPublicacao::individual();

        $resultado = ResultadoConsultaEstrategia::disponivel($estrategia);

        $this->assertSame(SituacaoConsultaEstrategia::DISPONIVEL, $resultado->getSituacao());
        $this->assertSame($estrategia, $resultado->getEstrategia());
        $this->assertNull($resultado->getFalha());
    }

    public function testConsultaAusenteNaoDeveFabricarEstrategiaOuFalha(): void
    {
        $resultado = ResultadoConsultaEstrategia::ausente();

        $this->assertSame(SituacaoConsultaEstrategia::AUSENTE, $resultado->getSituacao());
        $this->assertNull($resultado->getEstrategia());
        $this->assertNull($resultado->getFalha());
    }

    public function testConsultaInvalidaDeveConterFalhaSegura(): void
    {
        $falha = new FalhaSegura('CONFIGURACAO_INVALIDA', 'Configuração inválida.');

        $resultado = ResultadoConsultaEstrategia::invalida($falha);

        $this->assertSame(SituacaoConsultaEstrategia::INVALIDA, $resultado->getSituacao());
        $this->assertNull($resultado->getEstrategia());
        $this->assertSame($falha, $resultado->getFalha());
    }

    public function testConsultaComFalhaSistemicaDeveConterFalhaSegura(): void
    {
        $falha = new FalhaSegura('ESTRATEGIA_INDISPONIVEL', 'Estratégia indisponível.');

        $resultado = ResultadoConsultaEstrategia::falhaSistemica($falha);

        $this->assertSame(
            SituacaoConsultaEstrategia::FALHA_SISTEMICA,
            $resultado->getSituacao()
        );
        $this->assertSame($falha, $resultado->getFalha());
    }

    public function testAdocaoConfirmadaNaoDeveConterFalha(): void
    {
        $resultado = ResultadoAdocaoEstrategia::adotada();

        $this->assertTrue($resultado->foiAdotada());
        $this->assertNull($resultado->getFalha());
    }

    public function testFalhaNaAdocaoDeveSerTipadaESegura(): void
    {
        $falha = new FalhaSegura('FALHA_AO_ADOTAR', 'Não foi possível adotar a estratégia.');

        $resultado = ResultadoAdocaoEstrategia::falha($falha);

        $this->assertFalse($resultado->foiAdotada());
        $this->assertSame($falha, $resultado->getFalha());
    }
}
