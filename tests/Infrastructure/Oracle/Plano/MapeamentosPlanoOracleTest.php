<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Oracle\Plano;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoDemandasOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoLotacoesOracle;
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
            'grupo_codigo',
            [
                'schema' => 'cebisis',
                'tabela' => 'mt_grupos_materiais',
                'dblink' => 'read_orcl',
                'colunaCodigo' => 'codigo',
                'colunaDescricao' => 'descricao',
            ],
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
        $this->assertSame('GRUPO_CODIGO', $mapeamento->getColunaGrupo());
        $this->assertSame(
            'CEBISIS.MT_GRUPOS_MATERIAIS@READ_ORCL',
            $mapeamento->getTabelaGrupoRemotoQualificada()
        );
        $this->assertSame('CODIGO', $mapeamento->getColunaCodigoGrupoRemoto());
        $this->assertSame('DESCRICAO', $mapeamento->getColunaDescricaoGrupoRemoto());
        $this->assertSame('ORIGEM', $mapeamento->getColunaOrigem());
    }

    /** @return array<string, array{array<string, string>}> */
    public static function gruposRemotosInvalidos(): array
    {
        $valido = [
            'schema' => 'CEBISIS',
            'tabela' => 'MT_GRUPOS_MATERIAIS',
            'dblink' => 'READ_ORCL',
            'colunaCodigo' => 'CODIGO',
            'colunaDescricao' => 'DESCRICAO',
        ];

        return [
            'schema com SQL' => [[...$valido, 'schema' => 'CEBISIS;DROP']],
            'tabela qualificada livre' => [[...$valido, 'tabela' => 'CEBISIS.MT_GRUPOS_MATERIAIS']],
            'coluna com expressão' => [[...$valido, 'colunaDescricao' => 'MGM.DESCRICAO']],
            'DBLINK não autorizado' => [[...$valido, 'dblink' => 'OUTRO_LINK']],
        ];
    }

    /** @param array<string, string> $grupoRemoto */
    #[DataProvider('gruposRemotosInvalidos')]
    public function testDeveRejeitarMapeamentoRemotoInseguro(array $grupoRemoto): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->criarMapeamentoDemandas($grupoRemoto);
    }

    public function testDeveDeclararMapeamentoFisicoDasUnidadesOrcamentarias(): void
    {
        $mapeamento = new MapeamentoUnidadesOrcamentariasOracle(
            'app',
            'pac_unidades_orcamentarias',
            'id_unidade_orcamentaria',
            'id_unidade_orcamentaria',
            'codigo_uasg',
            'descricao_uasg',
            'ativa',
            'S'
        );
        $lotacoes = new MapeamentoLotacoesOracle(
            'app',
            'rh_lotacoes',
            'cod_lotacao',
            'nome_lotacao'
        );

        $this->assertSame(
            'APP.PAC_UNIDADES_ORCAMENTARIAS',
            $mapeamento->getTabelaQualificada()
        );
        $this->assertSame('ID_UNIDADE_ORCAMENTARIA', $mapeamento->getColunaRelacaoPlano());
        $this->assertSame('ID_UNIDADE_ORCAMENTARIA', $mapeamento->getColunaIdentidade());
        $this->assertSame('CODIGO_UASG', $mapeamento->getColunaCodigoUasg());
        $this->assertSame('DESCRICAO_UASG', $mapeamento->getColunaDescricaoUasg());
        $this->assertSame('ATIVA', $mapeamento->getColunaAtivo());
        $this->assertSame('S', $mapeamento->getValorAtivo());
        $this->assertSame('APP.RH_LOTACOES', $lotacoes->getTabelaQualificada());
        $this->assertSame('COD_LOTACAO', $lotacoes->getColunaId());
        $this->assertSame('NOME_LOTACAO', $lotacoes->getColunaDescricao());
    }

    /** @return array<string, array{class-string, array<int, mixed>}> */
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
                    [
                        'schema' => 'CEBISIS',
                        'tabela' => 'MT_GRUPOS_MATERIAIS',
                        'dblink' => 'READ_ORCL',
                        'colunaCodigo' => 'CODIGO',
                        'colunaDescricao' => 'DESCRICAO',
                    ],
                    'ORIGEM',
                ],
            ],
            'Unidades Orçamentárias' => [
                MapeamentoUnidadesOrcamentariasOracle::class,
                [
                    'APP', 'PAC_UO', 'RELACAO', 'IDENTIDADE', 'UASG',
                    'DESCRICAO_UASG', 'ATIVA = S', 'S',
                ],
            ],
            'Lotações' => [
                MapeamentoLotacoesOracle::class,
                ['APP', 'RH_LOTACOES', 'ID', 'NOME || SEGREDO'],
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
            'UASG',
            'DESCRICAO_UASG',
            'ATIVA',
            ' '
        );
    }

    /** @param array<string, string> $grupoRemoto */
    private function criarMapeamentoDemandas(array $grupoRemoto): MapeamentoDemandasOracle
    {
        return new MapeamentoDemandasOracle(
            'APP',
            'PAC_DEMANDAS',
            'ID_DEMANDA',
            'FK_PLANO',
            'SITUACAO',
            'FK_UNIDADE_REQUISITANTE',
            'DATA_DESEJADA',
            'CATEGORIA',
            'DESCRICAO',
            'UNIDADE_MEDIDA',
            'QUANTIDADE',
            'VALOR_TOTAL',
            'VALOR_ORCAMENTO_EXERCICIO',
            'CATALOGO',
            'CODIGO_ITEM',
            'GRUPO_CODIGO',
            $grupoRemoto,
            'ORIGEM'
        );
    }
}
