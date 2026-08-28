<?php

declare(strict_types=1);

namespace Tests\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\Entities\Demanda;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\SituacaoDemanda;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\DecimalExato;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Uasg;

class DemandaTest extends TestCase
{
    /** @regra RN-DEM-001 @regra RN-DEM-002 */
    public function testDeveRepresentarDemandaComIdentidadeUoEAnoExecucao(): void
    {
        $unidade = $this->criarUnidadeOrcamentaria();
        $ano = new AnoExecucao((int) date('Y') + 1);

        $demanda = $this->criarDemanda($unidade, $ano);

        $this->assertSame(1001, $demanda->getIdDemanda());
        $this->assertSame(SituacaoDemanda::HOMOLOGADA, $demanda->getSituacao());
        $this->assertSame($unidade, $demanda->getUnidadeOrcamentaria());
        $this->assertSame($ano, $demanda->getAnoExecucao());
        $this->assertSame('7100.00', $demanda->getValorTotal()->getValor());
        $this->assertSame('7100.00', $demanda->getValorOrcamentoExercicio()->getValor());
    }

    /** @regra RN-DEM-001 */
    public function testDeveRejeitarCategoriaInvalida(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->criarDemanda(categoria: 99);
    }

    /** @regra RN-DEM-001 */
    public function testDeveRejeitarQuantidadeNaoPositiva(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->criarDemanda(quantidade: 0);
    }

    /** @regra RN-DEM-001 */
    public function testDeveRejeitarOrigemInvalida(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->criarDemanda(origem: 'X');
    }

    private function criarDemanda(
        ?UnidadeOrcamentaria $unidade = null,
        ?AnoExecucao $ano = null,
        int $categoria = 1,
        int $quantidade = 2,
        string $origem = 'N'
    ): Demanda {
        $ano ??= new AnoExecucao((int) date('Y') + 1);

        return new Demanda(
            1001,
            SituacaoDemanda::HOMOLOGADA,
            159,
            $unidade ?? $this->criarUnidadeOrcamentaria(),
            $ano,
            new DateTimeImmutable($ano->getValor() . '-03-15'),
            $categoria,
            'MICROCOMPUTADOR DESKTOP TIPO PC PARA APLICAÇÕES AVANÇADAS',
            'UNIDADE',
            $quantidade,
            new DecimalExato('7100.00'),
            new DecimalExato('7100.00'),
            2,
            '98.004.000004',
            '98',
            'INFORMÁTICA',
            $origem
        );
    }

    private function criarUnidadeOrcamentaria(): UnidadeOrcamentaria
    {
        return new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria('uo-101'),
            new Lotacao(101, 'Diretoria Geral Administrativa e Financeira'),
            new Uasg('928344', 'SEMAE Piracicaba'),
            'Diretoria Geral Administrativa e Financeira'
        );
    }
}
