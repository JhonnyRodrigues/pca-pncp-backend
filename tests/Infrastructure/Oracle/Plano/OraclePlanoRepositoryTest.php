<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Oracle\Plano;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Oracle\Adodb\FalhaOracleException;
use Semae\Pca\Infrastructure\Oracle\Plano\DadosPlanoOracleInvalidosException;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoDemandasOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoLotacoesOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoPlanosOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoUnidadesOrcamentariasOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\OraclePlanoRepository;

final class OraclePlanoRepositoryTest extends TestCase
{
    public function testDeveListarIdentidadesDistintasAtivasEOrdenadas(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $adodb->resultadoGetAll = [
            $this->linhaUnidade('202', '202', '928345'),
            $this->linhaUnidade('101', '101', '928344'),
        ];
        $repository = $this->criarRepository($adodb);

        $identidades = $repository->listarIdentidades(new AnoExecucao(2027));

        $this->assertCount(2, $identidades);
        $this->assertSame(
            ['101', '202'],
            array_map(
                static fn ($identidade): string => $identidade
                    ->getUnidadeOrcamentaria()
                    ->getIdentidade()
                    ->getValor(),
                $identidades
            )
        );
        $this->assertSame(2027, $identidades[0]->getAnoExecucao()->getValor());
        $this->assertSame('928344', $identidades[0]->getUnidadeOrcamentaria()->getUasg()->getCodigo());

        $consulta = $adodb->consultas[0];
        $this->assertStringContainsString(
            'SELECT DISTINCT P.FK_UO AS CHAVE_UO_PLANO',
            $consulta['sql']
        );
        $this->assertStringContainsString(
            'U.ID_UNIDADE_ORCAMENTARIA AS IDENTIDADE_UO',
            $consulta['sql']
        );
        $this->assertStringContainsString(
            'L.COD_LOTACAO AS ID_LOTACAO',
            $consulta['sql']
        );
        $this->assertStringContainsString(
            'L.NOME_LOTACAO AS DESCRICAO_LOTACAO',
            $consulta['sql']
        );
        $this->assertStringContainsString(
            'L.NOME_LOTACAO AS DESCRICAO_UO',
            $consulta['sql']
        );
        $this->assertStringContainsString(
            'U.CODIGO_UASG AS CODIGO_UASG',
            $consulta['sql']
        );
        $this->assertStringContainsString(
            'U.DESCRICAO_UASG AS DESCRICAO_UASG',
            $consulta['sql']
        );
        $this->assertStringContainsString(
            'LEFT JOIN APP.PAC_UO U '
                . 'ON U.ID_UNIDADE_ORCAMENTARIA = BASE.CHAVE_UO_PLANO',
            $consulta['sql']
        );
        $this->assertStringContainsString(
            'LEFT JOIN APP.RH_LOTACOES L '
                . 'ON L.COD_LOTACAO = U.ID_UNIDADE_ORCAMENTARIA',
            $consulta['sql']
        );
        $this->assertStringContainsString(
            'AND U.ATIVA = :param_1',
            $consulta['sql']
        );
        $this->assertStringContainsString(
            'ORDER BY BASE.CHAVE_UO_PLANO, U.ID_UNIDADE_ORCAMENTARIA',
            $consulta['sql']
        );
        $this->assertSame(
            ['param_0' => 2027, 'param_1' => 'S'],
            $consulta['parametros']
        );
        $this->assertStringNotContainsString('CREATE ', strtoupper($consulta['sql']));
        $this->assertStringNotContainsString('DROP ', strtoupper($consulta['sql']));
    }

    public function testDeveRetornarColecaoVaziaQuandoNaoExistiremPlanosNoExercicio(): void
    {
        $repository = $this->criarRepository(new AdodbDescobertaPlanosFake());

        $this->assertSame([], $repository->listarIdentidades(new AnoExecucao(2027)));
    }

    public function testDevePermitirDescobertaAntesEDuranteATransacaoCompartilhada(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $adodb->resultadoGetAll = [$this->linhaUnidade('101', '101', '928344')];
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $repository = $this->criarRepository($adodb, $conexao);

        $this->assertCount(1, $repository->listarIdentidades(new AnoExecucao(2027)));
        $conexao->iniciarTransacao();
        $this->assertCount(1, $repository->listarIdentidades(new AnoExecucao(2027)));

        $this->assertTrue($conexao->estaEmTransacao());
        $this->assertSame(1, $adodb->inicios);
        $this->assertSame(0, $adodb->commits);
        $this->assertSame(0, $adodb->rollbacks);
    }

    public function testDeveRejeitarDoisCadastrosAtivosParaAMesmaRelacaoFisica(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $adodb->resultadoGetAll = [
            $this->linhaUnidade('101', 'UO-101-A', '928344'),
            $this->linhaUnidade('101', 'UO-101-B', '928345'),
        ];

        $this->expectException(DadosPlanoOracleInvalidosException::class);
        $this->expectExceptionMessage('cadastro ativo único');

        $this->criarRepository($adodb)->listarIdentidades(new AnoExecucao(2027));
    }

    public function testDeveRejeitarMesmaIdentidadeLogicaResolvidaPorRelacoesDiferentes(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $adodb->resultadoGetAll = [
            $this->linhaUnidade('101', 'UO-101', '928344'),
            $this->linhaUnidade('102', 'UO-101', '928345'),
        ];

        $this->expectException(DadosPlanoOracleInvalidosException::class);
        $this->expectExceptionMessage('identidade lógica duplicada');

        $this->criarRepository($adodb)->listarIdentidades(new AnoExecucao(2027));
    }

    public function testDeveRejeitarUnidadeAtivaAusenteOuInvalida(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $adodb->resultadoGetAll = [[
            'CHAVE_UO_PLANO' => '101',
            'IDENTIDADE_UO' => null,
            'ID_LOTACAO' => null,
            'DESCRICAO_LOTACAO' => null,
            'DESCRICAO_UO' => null,
            'CODIGO_UASG' => null,
            'DESCRICAO_UASG' => null,
        ]];

        $this->expectException(DadosPlanoOracleInvalidosException::class);
        $this->expectExceptionMessage('Unidade Orçamentária');

        $this->criarRepository($adodb)->listarIdentidades(new AnoExecucao(2027));
    }

    public function testDevePreservarFalhaOracleSanitizadaDaConsulta(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $adodb->resultadoGetAll = false;
        $adodb->numeroErro = 942;
        $adodb->mensagemErro = 'ORA-00942: tabela APP.SENSIVEL não existe';

        try {
            $this->criarRepository($adodb)->listarIdentidades(new AnoExecucao(2027));
            $this->fail('Era esperada falha Oracle sanitizada.');
        } catch (FalhaOracleException $exception) {
            $this->assertSame('ORA-00942', $exception->getCodigoOracle());
            $this->assertSame('Falha ao consultar dados no Oracle.', $exception->getMessage());
            $this->assertStringNotContainsString('SENSIVEL', $exception->getMessage());
        }
    }

    /** @regra RN-PLA-003 @regra RN-PLA-004 @regra RN-DEM-009 */
    public function testDeveReconstruirMaiorVersaoComDemandasVigentesDeTodasAsOrigens(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $adodb->resultadosGetAll = [
            [[
                'VERSAO' => '3',
                'DATA_HOMOLOGACAO' => '2026-08-20 14:35:12',
                'DATA_CONFIRMACAO' => null,
            ]],
            [
                $this->linhaDemanda('10', '2', '1234.5000'),
                $this->linhaDemanda('20', '3', '2345.6700'),
                $this->linhaDemanda('30', '5', '3456.0000'),
            ],
        ];
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $repository = $this->criarRepository($adodb, $conexao);
        $conexao->iniciarTransacao();

        $plano = $repository->reconstruirFotografiaVigente($this->identidadePlano());

        $this->assertSame(3, $plano->getSequencialVersao());
        $this->assertSame('2026-08-20 14:35:12', $plano->getDataHomologacao()->format('Y-m-d H:i:s'));
        $this->assertNull($plano->getDataConfirmacaoSincronizacao());
        $this->assertSame(
            [10, 20, 30],
            array_map(static fn ($demanda): int => $demanda->getIdDemanda(), $plano->getDemandas())
        );
        $this->assertSame(
            ['1234.5000', '2345.6700', '3456.0000'],
            array_map(
                static fn ($demanda): string => $demanda->getValorTotal()->getValor(),
                $plano->getDemandas()
            )
        );

        $consultaPlano = $adodb->consultas[0];
        $this->assertStringContainsString('p.VERSAO = (SELECT MAX(pv.VERSAO)', $consultaPlano['sql']);
        $this->assertStringContainsString('pv.FK_UO = p.FK_UO', $consultaPlano['sql']);
        $this->assertStringContainsString('pv.ANO_EXERCICIO = p.ANO_EXERCICIO', $consultaPlano['sql']);
        $this->assertStringNotContainsString('pv.DATA_PUBLICACAO', $consultaPlano['sql']);
        $this->assertStringContainsString(
            "TO_CHAR(p.DATA_HOMOLOGACAO, 'YYYY-MM-DD HH24:MI:SS') AS DATA_HOMOLOGACAO",
            $consultaPlano['sql']
        );
        $this->assertSame(['param_0' => 101, 'param_1' => 2027], $consultaPlano['parametros']);

        $consultaDemandas = $adodb->consultas[1];
        $this->assertStringContainsString(
            'JOIN APP.PAC_PLANOS origem ON origem.ID_PLANO = D.FK_PLANO',
            $consultaDemandas['sql']
        );
        $this->assertStringContainsString(
            'LEFT JOIN CEBISIS.MT_GRUPOS_MATERIAIS@READ_ORCL MGM '
                . 'ON MGM.CODIGO = D.GRUPO_CODIGO',
            $consultaDemandas['sql']
        );
        $this->assertStringContainsString(
            'MGM.DESCRICAO AS DESCRICAO_GRUPO',
            $consultaDemandas['sql']
        );
        $this->assertStringNotContainsString(
            'D.DESCRICAO_GRUPO AS DESCRICAO_GRUPO',
            $consultaDemandas['sql']
        );
        $this->assertStringNotContainsString('TO_CHAR(D.GRUPO_CODIGO', $consultaDemandas['sql']);
        $this->assertStringNotContainsString('TO_NUMBER(MGM.CODIGO', $consultaDemandas['sql']);
        $this->assertStringNotContainsString('WHERE MGM.', $consultaDemandas['sql']);
        $this->assertStringNotContainsString('origem.VERSAO =', $consultaDemandas['sql']);
        $this->assertStringContainsString(
            'D.SITUACAO IN (:param_2, :param_3, :param_4)',
            $consultaDemandas['sql']
        );
        $this->assertStringContainsString('ORDER BY D.ID_DEMANDA', $consultaDemandas['sql']);
        $this->assertStringNotContainsString('SEQUENCIAL', strtoupper($consultaDemandas['sql']));
        $this->assertStringContainsString(
            "TO_CHAR(D.VALOR_TOTAL, 'FM9999999999990D0000', "
                . "'NLS_NUMERIC_CHARACTERS=''.,''') AS VALOR_TOTAL",
            $consultaDemandas['sql']
        );
        $this->assertSame(
            ['param_0' => 101, 'param_1' => 2027, 'param_2' => 2, 'param_3' => 3, 'param_4' => 5],
            $consultaDemandas['parametros']
        );
        $this->assertTrue($conexao->estaEmTransacao());
        $this->assertSame(0, $adodb->commits);
        $this->assertSame(0, $adodb->rollbacks);
    }

    /** @regra RN-PLA-005 */
    public function testDeveReconstruirPlanoSemDemandasAtivas(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $adodb->resultadosGetAll = [
            [[
                'VERSAO' => '2',
                'DATA_HOMOLOGACAO' => '2026-08-21 08:00:00',
                'DATA_CONFIRMACAO' => '2026-08-21 09:00:00',
            ]],
            [],
        ];
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $repository = $this->criarRepository($adodb, $conexao);
        $conexao->iniciarTransacao();

        $plano = $repository->reconstruirFotografiaVigente($this->identidadePlano());

        $this->assertSame(0, $plano->getQuantidadeDemandas());
        $this->assertSame(
            '2026-08-21 09:00:00',
            $plano->getDataConfirmacaoSincronizacao()?->format('Y-m-d H:i:s')
        );
    }

    public function testDeveExigirTransacaoAtivaParaReconstrucao(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $repository = $this->criarRepository($adodb);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('transação Oracle ativa');

        try {
            $repository->reconstruirFotografiaVigente($this->identidadePlano());
        } finally {
            $this->assertSame([], $adodb->consultas);
        }
    }

    /** @regra RN-PLA-003 */
    public function testDeveRejeitarCardinalidadeInvalidaDaVersaoVigente(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $adodb->resultadosGetAll = [[], []];
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $repository = $this->criarRepository($adodb, $conexao);
        $conexao->iniciarTransacao();

        $this->expectException(DadosPlanoOracleInvalidosException::class);
        $this->expectExceptionMessage('versão vigente única');

        $repository->reconstruirFotografiaVigente($this->identidadePlano());
    }

    /** @regra RN-PLA-003 */
    public function testDeveRejeitarDuplicidadeDaMaiorVersao(): void
    {
        $linhaPlano = [
            'VERSAO' => '2',
            'DATA_HOMOLOGACAO' => '2026-08-20 14:35:12',
            'DATA_CONFIRMACAO' => null,
        ];
        $adodb = new AdodbDescobertaPlanosFake();
        $adodb->resultadosGetAll = [[$linhaPlano, $linhaPlano]];
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $repository = $this->criarRepository($adodb, $conexao);
        $conexao->iniciarTransacao();

        $this->expectException(DadosPlanoOracleInvalidosException::class);
        $this->expectExceptionMessage('versão vigente única');

        $repository->reconstruirFotografiaVigente($this->identidadePlano());
    }

    /** @regra RN-PLA-004 */
    public function testDeveRejeitarDemandasDuplicadasNaFotografiaReconstruida(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $adodb->resultadosGetAll = [
            [[
                'VERSAO' => '1',
                'DATA_HOMOLOGACAO' => '2026-08-20 14:35:12',
                'DATA_CONFIRMACAO' => null,
            ]],
            [
                $this->linhaDemanda('10', '2', '1234.5000'),
                $this->linhaDemanda('10', '3', '1234.5000'),
            ],
        ];
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $repository = $this->criarRepository($adodb, $conexao);
        $conexao->iniciarTransacao();

        $this->expectException(DadosPlanoOracleInvalidosException::class);
        $this->expectExceptionMessage('dados Oracle do Plano');

        $repository->reconstruirFotografiaVigente($this->identidadePlano());
    }

    public function testDeveRejeitarConteudoFisicoInconsistenteNaReconstrucao(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $adodb->resultadosGetAll = [
            [[
                'VERSAO' => '1',
                'DATA_HOMOLOGACAO' => '2026-08-20 14:35:12',
                'DATA_CONFIRMACAO' => null,
            ]],
            [$this->linhaDemanda('10', '4', '1234.5000')],
        ];
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $repository = $this->criarRepository($adodb, $conexao);
        $conexao->iniciarTransacao();

        $this->expectException(DadosPlanoOracleInvalidosException::class);
        $this->expectExceptionMessage('dados Oracle do Plano');

        $repository->reconstruirFotografiaVigente($this->identidadePlano());
    }

    public function testDevePreservarFalhaOracleSanitizadaNaConsultaDeDemandas(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $adodb->resultadosGetAll = [
            [[
                'VERSAO' => '1',
                'DATA_HOMOLOGACAO' => '2026-08-20 14:35:12',
                'DATA_CONFIRMACAO' => null,
            ]],
            false,
        ];
        $adodb->numeroErro = 942;
        $adodb->mensagemErro = 'ORA-00942: tabela APP.SENSIVEL não existe';
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $repository = $this->criarRepository($adodb, $conexao);
        $conexao->iniciarTransacao();

        try {
            $repository->reconstruirFotografiaVigente($this->identidadePlano());
            $this->fail('Era esperada falha Oracle sanitizada.');
        } catch (FalhaOracleException $exception) {
            $this->assertSame('ORA-00942', $exception->getCodigoOracle());
            $this->assertSame('Falha ao consultar dados no Oracle.', $exception->getMessage());
            $this->assertStringNotContainsString('SENSIVEL', $exception->getMessage());
        }
    }

    /** @regra RN-PLA-007 */
    public function testDeveConfirmarSomenteAVersaoExataPendenteERelerAIntencao(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $adodb->resultadosGetAll = [
            [['DATA_CONFIRMACAO' => null]],
            [['DATA_CONFIRMACAO' => '2026-08-24 16:45:10']],
        ];
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $repository = $this->criarRepository($adodb, $conexao);
        $plano = $this->planoPendente(3);
        $conexao->iniciarTransacao();

        $repository->confirmarSincronizacao($plano);

        $this->assertCount(2, $adodb->consultas);
        $this->assertCount(1, $adodb->execucoes);
        foreach ($adodb->consultas as $consulta) {
            $this->assertStringContainsString(
                "TO_CHAR(p.DATA_PUBLICACAO, 'YYYY-MM-DD HH24:MI:SS') AS DATA_CONFIRMACAO",
                $consulta['sql']
            );
            $this->assertStringContainsString(
                'WHERE p.FK_UO = :param_0 AND p.ANO_EXERCICIO = :param_1 '
                    . 'AND p.VERSAO = :param_2',
                $consulta['sql']
            );
            $this->assertSame(
                ['param_0' => 101, 'param_1' => 2027, 'param_2' => 3],
                $consulta['parametros']
            );
        }

        $execucao = $adodb->execucoes[0];
        $this->assertStringContainsString('UPDATE APP.PAC_PLANOS', $execucao['sql']);
        $this->assertStringContainsString('SET DATA_PUBLICACAO = SYSDATE', $execucao['sql']);
        $this->assertStringContainsString(
            'WHERE FK_UO = :param_0 AND ANO_EXERCICIO = :param_1 '
                . 'AND VERSAO = :param_2 AND DATA_PUBLICACAO IS NULL',
            $execucao['sql']
        );
        $this->assertSame(
            ['param_0' => 101, 'param_1' => 2027, 'param_2' => 3],
            $execucao['parametros']
        );
        $this->assertNull($plano->getDataConfirmacaoSincronizacao());
        $this->assertTrue($conexao->estaEmTransacao());
        $this->assertSame(0, $adodb->commits);
        $this->assertSame(0, $adodb->rollbacks);
    }

    /** @regra RN-PLA-007 */
    public function testDeveExigirTransacaoAtivaParaConfirmacao(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $repository = $this->criarRepository($adodb);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('transação Oracle ativa');

        try {
            $repository->confirmarSincronizacao($this->planoPendente());
        } finally {
            $this->assertSame([], $adodb->consultas);
            $this->assertSame([], $adodb->execucoes);
        }
    }

    /** @regra RN-PLA-007 */
    public function testDeveRejeitarPlanoJaConfirmadoSemConsultarOuAtualizar(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $repository = $this->criarRepository($adodb, $conexao);
        $conexao->iniciarTransacao();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Plano pendente');

        try {
            $repository->confirmarSincronizacao($this->planoConfirmado());
        } finally {
            $this->assertSame([], $adodb->consultas);
            $this->assertSame([], $adodb->execucoes);
        }
    }

    /** @regra RN-PLA-007 */
    public function testDeveRejeitarVersaoExataInexistenteSemAtualizar(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $adodb->resultadosGetAll = [[]];
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $repository = $this->criarRepository($adodb, $conexao);
        $conexao->iniciarTransacao();

        $this->expectException(DadosPlanoOracleInvalidosException::class);
        $this->expectExceptionMessage('linha exata única');

        try {
            $repository->confirmarSincronizacao($this->planoPendente());
        } finally {
            $this->assertSame([], $adodb->execucoes);
        }
    }

    /** @regra RN-PLA-007 */
    public function testDeveRejeitarDuplicidadeDaLinhaExataSemAtualizar(): void
    {
        $linha = ['DATA_CONFIRMACAO' => null];
        $adodb = new AdodbDescobertaPlanosFake();
        $adodb->resultadosGetAll = [[$linha, $linha]];
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $repository = $this->criarRepository($adodb, $conexao);
        $conexao->iniciarTransacao();

        $this->expectException(DadosPlanoOracleInvalidosException::class);
        $this->expectExceptionMessage('linha exata única');

        try {
            $repository->confirmarSincronizacao($this->planoPendente());
        } finally {
            $this->assertSame([], $adodb->execucoes);
        }
    }

    /** @regra RN-PLA-007 */
    public function testDeveRejeitarConfirmacaoFisicaPreexistenteParaPlanoPendente(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $adodb->resultadosGetAll = [
            [['DATA_CONFIRMACAO' => '2026-08-24 16:45:10']],
        ];
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $repository = $this->criarRepository($adodb, $conexao);
        $conexao->iniciarTransacao();

        $this->expectException(DadosPlanoOracleInvalidosException::class);
        $this->expectExceptionMessage('já possui confirmação');

        try {
            $repository->confirmarSincronizacao($this->planoPendente());
        } finally {
            $this->assertSame([], $adodb->execucoes);
        }
    }

    /** @regra RN-PLA-007 */
    public function testDeveRejeitarQuandoAReleituraNaoConfirmarAIntencao(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $adodb->resultadosGetAll = [
            [['DATA_CONFIRMACAO' => null]],
            [['DATA_CONFIRMACAO' => null]],
        ];
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $repository = $this->criarRepository($adodb, $conexao);
        $conexao->iniciarTransacao();

        $this->expectException(DadosPlanoOracleInvalidosException::class);
        $this->expectExceptionMessage('releitura Oracle não confirmou');

        $repository->confirmarSincronizacao($this->planoPendente());
    }

    /** @regra RN-PLA-007 */
    public function testDeveRejeitarQuandoALinhaDesaparecerNaReleitura(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $adodb->resultadosGetAll = [
            [['DATA_CONFIRMACAO' => null]],
            [],
        ];
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $repository = $this->criarRepository($adodb, $conexao);
        $conexao->iniciarTransacao();

        $this->expectException(DadosPlanoOracleInvalidosException::class);
        $this->expectExceptionMessage('releitura Oracle não confirmou');

        $repository->confirmarSincronizacao($this->planoPendente());
    }

    public function testDevePreservarFalhaOracleSanitizadaNaConfirmacao(): void
    {
        $adodb = new AdodbDescobertaPlanosFake();
        $adodb->resultadosGetAll = [[['DATA_CONFIRMACAO' => null]]];
        $adodb->resultadoExecute = false;
        $adodb->numeroErro = 942;
        $adodb->mensagemErro = 'ORA-00942: tabela APP.SENSIVEL não existe';
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $repository = $this->criarRepository($adodb, $conexao);
        $conexao->iniciarTransacao();

        try {
            $repository->confirmarSincronizacao($this->planoPendente());
            $this->fail('Era esperada falha Oracle sanitizada.');
        } catch (FalhaOracleException $exception) {
            $this->assertSame('ORA-00942', $exception->getCodigoOracle());
            $this->assertSame('Falha ao executar comando Oracle.', $exception->getMessage());
            $this->assertStringNotContainsString('SENSIVEL', $exception->getMessage());
        }
    }

    private function criarRepository(
        AdodbDescobertaPlanosFake $adodb,
        ?ConexaoAdodbEmprestada $conexao = null
    ): OraclePlanoRepository {
        return new OraclePlanoRepository(
            $conexao ?? new ConexaoAdodbEmprestada($adodb),
            new MapeamentoPlanosOracle(
                'APP',
                'PAC_PLANOS',
                'ID_PLANO',
                'FK_UO',
                'ANO_EXERCICIO',
                'VERSAO',
                'DATA_HOMOLOGACAO',
                'DATA_PUBLICACAO'
            ),
            new MapeamentoDemandasOracle(
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
                'CLASSIFICACAO_CATALOGO',
                'CODIGO_ITEM',
                'GRUPO_CODIGO',
                [
                    'schema' => 'CEBISIS',
                    'tabela' => 'MT_GRUPOS_MATERIAIS',
                    'dblink' => 'READ_ORCL',
                    'colunaCodigo' => 'CODIGO',
                    'colunaDescricao' => 'DESCRICAO',
                ],
                'ORIGEM'
            ),
            new MapeamentoUnidadesOrcamentariasOracle(
                'APP',
                'PAC_UO',
                'ID_UNIDADE_ORCAMENTARIA',
                'ID_UNIDADE_ORCAMENTARIA',
                'CODIGO_UASG',
                'DESCRICAO_UASG',
                'ATIVA',
                'S'
            ),
            new MapeamentoLotacoesOracle(
                'APP',
                'RH_LOTACOES',
                'COD_LOTACAO',
                'NOME_LOTACAO'
            ),
            new DateTimeZone('America/Sao_Paulo')
        );
    }

    /** @return array<string, mixed> */
    private function linhaUnidade(string $chave, string $identidade, string $uasg): array
    {
        return [
            'CHAVE_UO_PLANO' => $chave,
            'IDENTIDADE_UO' => $identidade,
            'ID_LOTACAO' => $chave,
            'DESCRICAO_LOTACAO' => 'Lotação ' . $chave,
            'DESCRICAO_UO' => 'Lotação ' . $chave,
            'CODIGO_UASG' => $uasg,
            'DESCRICAO_UASG' => 'UASG ' . $uasg,
        ];
    }

    private function identidadePlano(): IdentidadePlano
    {
        return new IdentidadePlano(
            new UnidadeOrcamentaria(
                new IdentidadeUnidadeOrcamentaria('UO-101'),
                new Lotacao(101, 'Lotação 101'),
                new Uasg('928344', 'UASG 928344'),
                'Unidade Orçamentária 101'
            ),
            new AnoExecucao(2027)
        );
    }

    private function planoPendente(int $versao = 1): Plano
    {
        return new Plano(
            $this->identidadePlano(),
            $versao,
            new DateTimeImmutable('2026-08-20 14:35:12', new DateTimeZone('America/Sao_Paulo')),
            [],
            null
        );
    }

    private function planoConfirmado(): Plano
    {
        return new Plano(
            $this->identidadePlano(),
            1,
            new DateTimeImmutable('2026-08-20 14:35:12', new DateTimeZone('America/Sao_Paulo')),
            [],
            new DateTimeImmutable('2026-08-21 09:00:00', new DateTimeZone('America/Sao_Paulo'))
        );
    }

    /** @return array<string, mixed> */
    private function linhaDemanda(string $id, string $situacao, string $valorTotal): array
    {
        return [
            'ID_DEMANDA' => $id,
            'SITUACAO' => $situacao,
            'ID_UNIDADE_REQUISITANTE' => '44',
            'DATA_DESEJADA' => '2027-05-31',
            'CATEGORIA' => '1',
            'DESCRICAO_DEMANDA' => 'Computadores',
            'UNIDADE_MEDIDA' => 'UN',
            'QUANTIDADE' => '5',
            'VALOR_TOTAL' => $valorTotal,
            'VALOR_ORCAMENTO_EXERCICIO' => '1000.0000',
            'CLASSIFICACAO_CATALOGO' => '1',
            'CODIGO_ITEM' => 'CATMAT-123',
            'GRUPO' => '75',
            'DESCRICAO_GRUPO' => 'Equipamentos',
            'ORIGEM' => 'N',
        ];
    }
}

final class AdodbDescobertaPlanosFake
{
    /** @var array<int, array<string, mixed>>|false */
    public array|false $resultadoGetAll = [];

    /** @var array<int, array<int, array<string, mixed>>|false> */
    public array $resultadosGetAll = [];

    /** @var array<int, array{sql: string, parametros: array<int|string, mixed>}> */
    public array $consultas = [];

    /** @var array<int, array{sql: string, parametros: array<int|string, mixed>}> */
    public array $execucoes = [];

    public int $inicios = 0;
    public int $commits = 0;
    public int $rollbacks = 0;
    public int|string $numeroErro = 0;
    public string $mensagemErro = '';
    public bool $resultadoExecute = true;

    public function BeginTrans(): bool
    {
        $this->inicios++;

        return true;
    }

    public function CommitTrans(): bool
    {
        $this->commits++;

        return true;
    }

    public function RollbackTrans(): bool
    {
        $this->rollbacks++;

        return true;
    }

    /** @param array<int|string, mixed> $parametros */
    public function Execute(string $sql, array $parametros = []): bool
    {
        $this->execucoes[] = ['sql' => $sql, 'parametros' => $parametros];

        return $this->resultadoExecute;
    }

    /** @param array<int|string, mixed> $parametros */
    public function GetAll(string $sql, array $parametros = []): array|false
    {
        $this->consultas[] = ['sql' => $sql, 'parametros' => $parametros];

        if ($this->resultadosGetAll !== []) {
            return array_shift($this->resultadosGetAll);
        }

        return $this->resultadoGetAll;
    }

    public function Param(false|string $nome): string
    {
        return $nome === false ? '' : ':' . $nome;
    }

    public function ErrorNo(): int|string
    {
        return $this->numeroErro;
    }

    public function ErrorMsg(): string
    {
        return $this->mensagemErro;
    }
}
