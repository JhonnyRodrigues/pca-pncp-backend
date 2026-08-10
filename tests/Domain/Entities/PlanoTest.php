<?php

declare(strict_types=1);

namespace Tests\Domain\Entities;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Semae\Pca\Domain\Entities\Demanda;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\SituacaoDemanda;
use Semae\Pca\Domain\Exceptions\PlanoException;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\DecimalExato;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Uasg;

class PlanoTest extends TestCase
{
    /** @regra RN-PLA-001 @regra RN-PLA-004 */
    public function testDeveRepresentarFotografiaVigentePelaIdentidadeLogica(): void
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidade = $this->criarUnidadeOrcamentaria('uo-101');
        $identidade = new IdentidadePlano($unidade, $ano);
        $demanda = $this->criarDemanda($unidade, $ano, 1001);

        $plano = new Plano(
            $identidade,
            2,
            new DateTimeImmutable('2026-07-16'),
            [$demanda],
            null
        );

        $this->assertSame($identidade, $plano->getIdentidade());
        $this->assertSame(2, $plano->getSequencialVersao());
        $this->assertSame([$demanda], $plano->getDemandas());
    }

    /** @regra RN-PLA-005 @regra RN-DEM-008 */
    public function testDeveRepresentarPlanoSemDemandasAtivas(): void
    {
        $plano = $this->criarPlano(demandas: []);

        $this->assertFalse($plano->possuiDemandas());
        $this->assertSame(0, $plano->getQuantidadeDemandas());
    }

    /** @regra RN-PLA-007 */
    public function testDeveDistinguirConfirmacaoVersionadaDePublicacaoAtiva(): void
    {
        $confirmacao = new DateTimeImmutable('2026-07-17 10:30:00');
        $plano = $this->criarPlano(dataConfirmacaoSincronizacao: $confirmacao);

        $this->assertSame($confirmacao, $plano->getDataConfirmacaoSincronizacao());
        $this->assertTrue($plano->possuiSincronizacaoConfirmada());
    }

    /** @regra RN-PLA-007 */
    public function testNovaVersaoPodeEstarPendenteDeConfirmacao(): void
    {
        $plano = $this->criarPlano(sequencialVersao: 2);

        $this->assertNull($plano->getDataConfirmacaoSincronizacao());
        $this->assertFalse($plano->possuiSincronizacaoConfirmada());
    }

    /** @regra RN-PLA-002 */
    public function testDeveExigirVersaoPositiva(): void
    {
        $this->expectException(PlanoException::class);

        $this->criarPlano(sequencialVersao: 0);
    }

    /** @regra RN-PLA-006 */
    public function testDeveRejeitarDemandaDeOutraUnidadeOrcamentaria(): void
    {
        $this->expectException(PlanoException::class);

        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidade = $this->criarUnidadeOrcamentaria('uo-101');

        new Plano(
            new IdentidadePlano($unidade, $ano),
            1,
            new DateTimeImmutable('2026-07-16'),
            [$this->criarDemanda($this->criarUnidadeOrcamentaria('uo-102'), $ano, 1001)],
            null
        );
    }

    /** @regra RN-PLA-006 */
    public function testDeveRejeitarDemandaDeOutroAnoExecucao(): void
    {
        $this->expectException(PlanoException::class);

        $ano = new AnoExecucao((int) date('Y'));
        $outroAno = new AnoExecucao((int) date('Y') + 1);
        $unidade = $this->criarUnidadeOrcamentaria('uo-101');

        new Plano(
            new IdentidadePlano($unidade, $ano),
            1,
            new DateTimeImmutable('2026-07-16'),
            [$this->criarDemanda($unidade, $outroAno, 1001)],
            null
        );
    }

    /** @regra RN-DEM-009 @regra RN-PLA-004 */
    #[DataProvider('situacoesParticipantes')]
    public function testDeveAceitarSomenteSituacoesParticipantesNaFotografia(
        SituacaoDemanda $situacao
    ): void {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidade = $this->criarUnidadeOrcamentaria('uo-101');

        $plano = new Plano(
            new IdentidadePlano($unidade, $ano),
            1,
            new DateTimeImmutable('2026-07-16'),
            [$this->criarDemanda($unidade, $ano, 1001, $situacao)],
            null
        );

        $this->assertSame($situacao, $plano->getDemandas()[0]->getSituacao());
    }

    /** @regra RN-DEM-009 @regra RN-PLA-004 */
    #[DataProvider('situacoesNaoParticipantes')]
    public function testDeveRejeitarSituacoesQueNaoParticipamDaFotografia(
        SituacaoDemanda $situacao
    ): void {
        $this->expectException(PlanoException::class);

        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidade = $this->criarUnidadeOrcamentaria('uo-101');

        new Plano(
            new IdentidadePlano($unidade, $ano),
            1,
            new DateTimeImmutable('2026-07-16'),
            [$this->criarDemanda($unidade, $ano, 1001, $situacao)],
            null
        );
    }

    /** @regra RN-DEM-002 @regra RN-PLA-004 */
    public function testDeveRejeitarDemandaDuplicadaNaFotografia(): void
    {
        $this->expectException(PlanoException::class);

        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidade = $this->criarUnidadeOrcamentaria('uo-101');
        $demanda = $this->criarDemanda($unidade, $ano, 1001);

        new Plano(
            new IdentidadePlano($unidade, $ano),
            1,
            new DateTimeImmutable('2026-07-16'),
            [$demanda, $demanda],
            null
        );
    }

    /** @regra RN-PLA-004 */
    public function testDeveRejeitarElementoQueNaoSejaDemandaNaFotografia(): void
    {
        $this->expectException(PlanoException::class);

        $this->criarPlano(demandas: [new \stdClass()]);
    }

    private function criarPlano(
        int $sequencialVersao = 1,
        ?array $demandas = null,
        ?DateTimeImmutable $dataConfirmacaoSincronizacao = null
    ): Plano {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidade = $this->criarUnidadeOrcamentaria('uo-101');
        $demandas ??= [$this->criarDemanda($unidade, $ano, 1001)];

        return new Plano(
            new IdentidadePlano($unidade, $ano),
            $sequencialVersao,
            new DateTimeImmutable('2026-07-16'),
            $demandas,
            $dataConfirmacaoSincronizacao
        );
    }

    private function criarDemanda(
        UnidadeOrcamentaria $unidade,
        AnoExecucao $ano,
        int $idDemanda,
        SituacaoDemanda $situacao = SituacaoDemanda::HOMOLOGADA
    ): Demanda {
        return new Demanda(
            $idDemanda,
            $situacao,
            159,
            $unidade,
            $ano,
            new DateTimeImmutable($ano->getValor() . '-03-15'),
            1,
            'MICROCOMPUTADOR DESKTOP TIPO PC PARA APLICAÇÕES AVANÇADAS',
            'UNIDADE',
            2,
            new DecimalExato('7100.00'),
            new DecimalExato('7100.00'),
            2,
            '98.004.000004',
            '98',
            'INFORMÁTICA',
            'N'
        );
    }

    public static function situacoesParticipantes(): array
    {
        return [
            'homologada' => [SituacaoDemanda::HOMOLOGADA],
            'comprada' => [SituacaoDemanda::COMPRADA],
            'publicada' => [SituacaoDemanda::PUBLICADA],
        ];
    }

    public static function situacoesNaoParticipantes(): array
    {
        return [
            'planejada' => [SituacaoDemanda::PLANEJADA],
            'cancelada' => [SituacaoDemanda::CANCELADA],
        ];
    }

    private function criarUnidadeOrcamentaria(string $identidade): UnidadeOrcamentaria
    {
        return new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria($identidade),
            new Lotacao(101, 'Diretoria Geral Administrativa e Financeira'),
            new Uasg('928344', 'SEMAE Piracicaba'),
            'Diretoria Geral Administrativa e Financeira'
        );
    }
}
