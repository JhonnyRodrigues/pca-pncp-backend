<?php

declare(strict_types=1);

namespace Tests\Application\Results;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\SituacaoConsultaEvidenciaCancelamento;
use Semae\Pca\Application\Results\ResultadoConsultaEvidenciaCancelamento;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\EvidenciaCancelamentoHomologado;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class ResultadoConsultaEvidenciaCancelamentoTest extends TestCase
{
    public function testDisponivelDeveConterEvidenciaSemFalha(): void
    {
        $evidencia = $this->criarEvidencia();

        $resultado = ResultadoConsultaEvidenciaCancelamento::disponivel($evidencia);

        $this->assertSame(
            SituacaoConsultaEvidenciaCancelamento::DISPONIVEL,
            $resultado->getSituacao()
        );
        $this->assertSame($evidencia, $resultado->getEvidencia());
        $this->assertNull($resultado->getFalha());
    }

    #[DataProvider('consultasSemEvidencia')]
    public function testSituacaoSemEvidenciaDevePreservarFalhaSegura(
        SituacaoConsultaEvidenciaCancelamento $situacao,
        string $factory
    ): void {
        $falha = new FalhaSegura('EVIDENCIA_INDISPONIVEL', 'Evidência indisponível.');

        $resultado = ResultadoConsultaEvidenciaCancelamento::{$factory}($falha);

        $this->assertSame($situacao, $resultado->getSituacao());
        $this->assertNull($resultado->getEvidencia());
        $this->assertSame($falha, $resultado->getFalha());
    }

    /** @return array<string, array{SituacaoConsultaEvidenciaCancelamento, string}> */
    public static function consultasSemEvidencia(): array
    {
        return [
            'ausente' => [SituacaoConsultaEvidenciaCancelamento::AUSENTE, 'ausente'],
            'conflito' => [SituacaoConsultaEvidenciaCancelamento::CONFLITO, 'conflito'],
            'falha sistêmica' => [
                SituacaoConsultaEvidenciaCancelamento::FALHA_SISTEMICA,
                'falhaSistemica',
            ],
        ];
    }

    private function criarEvidencia(): EvidenciaCancelamentoHomologado
    {
        $identidade = new IdentidadePlano(
            new UnidadeOrcamentaria(
                new IdentidadeUnidadeOrcamentaria('uo-101'),
                new Lotacao(101, 'Diretoria Administrativa'),
                new Uasg('928344', 'SEMAE Piracicaba'),
                'Diretoria Administrativa'
            ),
            new AnoExecucao((int) date('Y') + 1)
        );

        return new EvidenciaCancelamentoHomologado(
            $identidade,
            731,
            'Cancelamento homologado.'
        );
    }
}
