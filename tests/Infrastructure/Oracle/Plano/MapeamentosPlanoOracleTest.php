<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Oracle\Plano;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoDemandasOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoPlanosOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoUnidadesOrcamentariasOracle;

final class MapeamentosPlanoOracleTest extends TestCase
{
    public function testDeveDeclararMapeamentoFisicoDosPlanos(): void
    {
        $mapeamento = new MapeamentoPlanosOracle(
            'app',
            'pac_planos',
            'id_plano',
            'fk_unidade_orcamentaria',
            'ano_exercicio',
            'versao',
            'data_homologacao',
            'data_publicacao'
        );

        $this->assertSame('APP.PAC_PLANOS', $mapeamento->getTabelaQualificada());
        $this->assertSame('ID_PLANO', $mapeamento->getColunaId());
        $this->assertSame(
            'FK_UNIDADE_ORCAMENTARIA',
            $mapeamento->getColunaUnidadeOrcamentaria()
        );
        $this->assertSame('ANO_EXERCICIO', $mapeamento->getColunaAnoExecucao());
        $this->assertSame('VERSAO', $mapeamento->getColunaVersao());
        $this->assertSame('DATA_HOMOLOGACAO', $mapeamento->getColunaDataHomologacao());
        $this->assertSame('DATA_PUBLICACAO', $mapeamento->getColunaDataConfirmacao());
    }

    public function testDeveDeclararMapeamentoFisicoDasDemandas(): void
    {
        $mapeamento = new MapeamentoDemandasOracle(
            'app',
            'pac_demandas',
            'id_demanda',
            'fk_plano',
            'situacao',
            'fk_unidade_requisitante',
            'data_desejada',
            'categoria',
            'descricao',
            'unidade_medida',
            'quantidade',
            'valor_total',
            'valor_orcamento_exercicio',
            'catalogo',
            'codigo_item',
            'grupo',
            'descricao_grupo',
            'origem'
        );

        $this->assertSame('APP.PAC_DEMANDAS', $mapeamento->getTabelaQualificada());
        $this->assertSame('ID_DEMANDA', $mapeamento->getColunaId());
        $this->assertSame('FK_PLANO', $mapeamento->getColunaPlanoOrigem());
        $this->assertSame('SITUACAO', $mapeamento->getColunaSituacao());
        $this->assertSame(
            'FK_UNIDADE_REQUISITANTE',
            $mapeamento->getColunaUnidadeRequisitante()
        );
        $this->assertSame('DATA_DESEJADA', $mapeamento->getColunaDataDesejada());
        $this->assertSame('CATEGORIA', $mapeamento->getColunaCategoria());
        $this->assertSame('DESCRICAO', $mapeamento->getColunaDescricao());
        $this->assertSame('UNIDADE_MEDIDA', $mapeamento->getColunaUnidadeMedida());
        $this->assertSame('QUANTIDADE', $mapeamento->getColunaQuantidade());
        $this->assertSame('VALOR_TOTAL', $mapeamento->getColunaValorTotal());
        $this->assertSame(
            'VALOR_ORCAMENTO_EXERCICIO',
            $mapeamento->getColunaValorOrcamentoExercicio()
        );
        $this->assertSame('CATALOGO', $mapeamento->getColunaCatalogo());
        $this->assertSame('CODIGO_ITEM', $mapeamento->getColunaCodigoItem());
        $this->assertSame('GRUPO', $mapeamento->getColunaGrupo());
        $this->assertSame('DESCRICAO_GRUPO', $mapeamento->getColunaDescricaoGrupo());
        $this->assertSame('ORIGEM', $mapeamento->getColunaOrigem());
    }

    public function testDeveDeclararMapeamentoFisicoDasUnidadesOrcamentarias(): void
    {
        $mapeamento = new MapeamentoUnidadesOrcamentariasOracle(
            'app',
            'pac_unidades_orcamentarias',
            'fk_lotacao',
            'codigo_uo',
            'fk_lotacao',
            'descricao_lotacao',
            'descricao_uo',
            'uasg',
            'descricao_uasg',
            'ativa',
            'S'
        );

        $this->assertSame(
            'APP.PAC_UNIDADES_ORCAMENTARIAS',
            $mapeamento->getTabelaQualificada()
        );
        $this->assertSame('FK_LOTACAO', $mapeamento->getColunaRelacaoPlano());
        $this->assertSame('CODIGO_UO', $mapeamento->getColunaIdentidade());
        $this->assertSame('FK_LOTACAO', $mapeamento->getColunaLotacao());
        $this->assertSame(
            'DESCRICAO_LOTACAO',
            $mapeamento->getColunaDescricaoLotacao()
        );
        $this->assertSame('DESCRICAO_UO', $mapeamento->getColunaDescricao());
        $this->assertSame('UASG', $mapeamento->getColunaCodigoUasg());
        $this->assertSame('DESCRICAO_UASG', $mapeamento->getColunaDescricaoUasg());
        $this->assertSame('ATIVA', $mapeamento->getColunaAtivo());
        $this->assertSame('S', $mapeamento->getValorAtivo());
    }

    /** @return array<string, array{class-string, array<int, string>}> */
    public static function mapeamentosInvalidos(): array
    {
        return [
            'Planos' => [
                MapeamentoPlanosOracle::class,
                ['APP', 'PAC_PLANOS', 'ID', 'UO', 'ANO', 'VERSAO;DROP', 'HOMOLOGACAO', 'CONFIRMACAO'],
            ],
            'Demandas' => [
                MapeamentoDemandasOracle::class,
                [
                    'APP', 'PAC DEMANDAS', 'ID', 'PLANO', 'SITUACAO', 'UNIDADE',
                    'DATA', 'CATEGORIA', 'DESCRICAO', 'MEDIDA', 'QUANTIDADE',
                    'TOTAL', 'ORCAMENTO', 'CATALOGO', 'ITEM', 'GRUPO',
                    'DESCRICAO_GRUPO', 'ORIGEM',
                ],
            ],
            'Unidades Orçamentárias' => [
                MapeamentoUnidadesOrcamentariasOracle::class,
                [
                    'APP', 'PAC_UO', 'RELACAO', 'IDENTIDADE', 'LOTACAO',
                    'DESCRICAO_LOTACAO', 'DESCRICAO', 'UASG', 'DESCRICAO_UASG',
                    'ATIVA = S', 'S',
                ],
            ],
        ];
    }

    /** @param class-string $classe */
    #[DataProvider('mapeamentosInvalidos')]
    public function testDeveRejeitarIdentificadorForaDaAllowlist(
        string $classe,
        array $argumentos
    ): void {
        $this->expectException(InvalidArgumentException::class);

        new $classe(...$argumentos);
    }

    public function testDeveRejeitarValorFisicoAtivoVazio(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new MapeamentoUnidadesOrcamentariasOracle(
            'APP',
            'PAC_UO',
            'RELACAO',
            'IDENTIDADE',
            'LOTACAO',
            'DESCRICAO_LOTACAO',
            'DESCRICAO',
            'UASG',
            'DESCRICAO_UASG',
            'ATIVA',
            ' '
        );
    }
}
