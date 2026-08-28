<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Oracle\Plano;

use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\Enums\SituacaoDemanda;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Infrastructure\Oracle\Plano\DadosPlanoOracleInvalidosException;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeadorDadosPlanoOracle;

final class MapeadorDadosPlanoOracleTest extends TestCase
{
    private MapeadorDadosPlanoOracle $mapeador;

    protected function setUp(): void
    {
        $this->mapeador = new MapeadorDadosPlanoOracle(
            new DateTimeZone('America/Sao_Paulo')
        );
    }

    public function testDeveMapearAliasesCanonicosParaConceitosDoDomain(): void
    {
        $unidade = $this->mapeador->mapearUnidadeOrcamentaria($this->linhaUnidade());
        $identidade = new IdentidadePlano($unidade, new AnoExecucao(2027));
        $demanda = $this->mapeador->mapearDemanda($this->linhaDemanda(), $identidade);
        $plano = $this->mapeador->mapearPlano(
            [
                'VERSAO' => '3',
                'DATA_HOMOLOGACAO' => '2026-08-20 14:35:12',
                'DATA_CONFIRMACAO' => '2026-08-21 09:10:11',
            ],
            $identidade,
            [$demanda]
        );

        $this->assertSame('101', $unidade->getIdentidade()->getValor());
        $this->assertSame(101, $unidade->getLotacao()->getId());
        $this->assertSame('Diretoria de Planejamento', $unidade->getLotacao()->getNome());
        $this->assertSame('Diretoria de Planejamento', $unidade->getDescricao());
        $this->assertSame($unidade->getLotacao()->getNome(), $unidade->getDescricao());
        $this->assertSame('928344', $unidade->getUasg()->getCodigo());
        $this->assertSame('UASG Principal', $unidade->getUasg()->getDescricao());

        $this->assertSame(1001, $demanda->getIdDemanda());
        $this->assertSame(SituacaoDemanda::COMPRADA, $demanda->getSituacao());
        $this->assertSame('2027-06-30', $demanda->getDataDesejada()->format('Y-m-d'));
        $this->assertSame('America/Sao_Paulo', $demanda->getDataDesejada()->getTimezone()->getName());
        $this->assertSame('1234567890123.4500', $demanda->getValorTotal()->getValor());
        $this->assertSame('1200000000000.0000', $demanda->getValorOrcamentoExercicio()->getValor());

        $this->assertSame(3, $plano->getSequencialVersao());
        $this->assertSame('2026-08-20 14:35:12', $plano->getDataHomologacao()->format('Y-m-d H:i:s'));
        $this->assertSame(
            'America/Sao_Paulo',
            $plano->getDataHomologacao()->getTimezone()->getName()
        );
        $this->assertSame(
            '2026-08-21 09:10:11',
            $plano->getDataConfirmacaoSincronizacao()?->format('Y-m-d H:i:s')
        );
        $this->assertSame([$demanda], $plano->getDemandas());
    }

    public function testDevePreservarConfirmacaoNulaEPlanoVazio(): void
    {
        $identidade = new IdentidadePlano(
            $this->mapeador->mapearUnidadeOrcamentaria($this->linhaUnidade()),
            new AnoExecucao(2027)
        );

        $plano = $this->mapeador->mapearPlano(
            [
                'VERSAO' => 1,
                'DATA_HOMOLOGACAO' => '2026-08-20 14:35:12',
                'DATA_CONFIRMACAO' => null,
            ],
            $identidade,
            []
        );

        $this->assertNull($plano->getDataConfirmacaoSincronizacao());
        $this->assertFalse($plano->possuiDemandas());
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function unidadesInvalidas(): array
    {
        $valida = self::linhaUnidadeValida();

        return [
            'código UASG ausente' => [array_replace($valida, ['CODIGO_UASG' => null])],
            'descrição UASG ausente' => [array_replace($valida, ['DESCRICAO_UASG' => null])],
            'descrição da Lotação ausente' => [
                array_replace($valida, ['DESCRICAO_LOTACAO' => null]),
            ],
            'descrição da UO ausente' => [array_replace($valida, ['DESCRICAO_UO' => null])],
        ];
    }

    /** @param array<string, mixed> $linha */
    #[DataProvider('unidadesInvalidas')]
    public function testDeveRejeitarDadosInvalidosDaUnidadeOrcamentaria(array $linha): void
    {
        $this->expectException(DadosPlanoOracleInvalidosException::class);
        $this->expectExceptionMessage('Os dados Oracle da Unidade Orçamentária são inválidos.');

        $this->mapeador->mapearUnidadeOrcamentaria($linha);
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function demandasInvalidas(): array
    {
        $valida = self::linhaDemandaValida();

        return [
            'float em decimal exato' => [array_replace($valida, ['VALOR_TOTAL' => 10.5])],
            'decimal com vírgula' => [array_replace($valida, ['VALOR_TOTAL' => '10,5000'])],
            'data inexistente' => [array_replace($valida, ['DATA_DESEJADA' => '2027-02-30'])],
            'situação desconhecida' => [array_replace($valida, ['SITUACAO' => '9'])],
            'inteiro fracionário' => [array_replace($valida, ['QUANTIDADE' => '2.5'])],
            'alias ausente' => [array_diff_key($valida, ['ORIGEM' => true])],
        ];
    }

    /** @param array<string, mixed> $linha */
    #[DataProvider('demandasInvalidas')]
    public function testDeveRejeitarDadosOracleInvalidosSemExporOValor(array $linha): void
    {
        $identidade = new IdentidadePlano(
            $this->mapeador->mapearUnidadeOrcamentaria($this->linhaUnidade()),
            new AnoExecucao(2027)
        );

        try {
            $this->mapeador->mapearDemanda($linha, $identidade);
            $this->fail('Era esperada rejeição dos dados Oracle da Demanda.');
        } catch (DadosPlanoOracleInvalidosException $exception) {
            $this->assertSame(
                'Os dados Oracle da Demanda são inválidos.',
                $exception->getMessage()
            );
            $this->assertNull($exception->getPrevious());
            $this->assertStringNotContainsString('10,5000', $exception->getMessage());
        }
    }

    public function testDeveRejeitarInstanteOracleForaDaMascaraExplicita(): void
    {
        $identidade = new IdentidadePlano(
            $this->mapeador->mapearUnidadeOrcamentaria($this->linhaUnidade()),
            new AnoExecucao(2027)
        );

        $this->expectException(DadosPlanoOracleInvalidosException::class);
        $this->expectExceptionMessage('Os dados Oracle do Plano são inválidos.');

        $this->mapeador->mapearPlano(
            [
                'VERSAO' => 1,
                'DATA_HOMOLOGACAO' => '20/08/2026 14:35:12',
                'DATA_CONFIRMACAO' => null,
            ],
            $identidade,
            []
        );
    }

    /** @return array<string, mixed> */
    private function linhaUnidade(): array
    {
        return self::linhaUnidadeValida();
    }

    /** @return array<string, mixed> */
    private static function linhaUnidadeValida(): array
    {
        return [
            'IDENTIDADE_UO' => ' 101 ',
            'ID_LOTACAO' => '101',
            'DESCRICAO_LOTACAO' => ' Diretoria de Planejamento ',
            'DESCRICAO_UO' => ' Diretoria de Planejamento ',
            'CODIGO_UASG' => '928344',
            'DESCRICAO_UASG' => ' UASG Principal ',
        ];
    }

    /** @return array<string, mixed> */
    private function linhaDemanda(): array
    {
        return self::linhaDemandaValida();
    }

    /** @return array<string, mixed> */
    private static function linhaDemandaValida(): array
    {
        return [
            'ID_DEMANDA' => '1001',
            'SITUACAO' => '3',
            'ID_UNIDADE_REQUISITANTE' => '55',
            'DATA_DESEJADA' => '2027-06-30',
            'CATEGORIA' => '1',
            'DESCRICAO_DEMANDA' => 'Aquisição de equipamentos',
            'UNIDADE_MEDIDA' => 'UN',
            'QUANTIDADE' => '2',
            'VALOR_TOTAL' => '1234567890123.4500',
            'VALOR_ORCAMENTO_EXERCICIO' => '1200000000000.0000',
            'CATALOGO' => '1',
            'CODIGO_ITEM' => 'CATMAT-123',
            'GRUPO' => '44',
            'DESCRICAO_GRUPO' => 'Equipamentos',
            'ORIGEM' => 'N',
        ];
    }
}
