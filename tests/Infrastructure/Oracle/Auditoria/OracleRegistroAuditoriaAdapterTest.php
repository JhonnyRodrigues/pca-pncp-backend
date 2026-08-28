<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Oracle\Auditoria;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\SituacaoPersistenciaLocal;
use Semae\Pca\Application\ValueObjects\IntencaoRegistroHistorico;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\ProcedimentoPublicacaoPca;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Representante;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Oracle\Auditoria\MapeamentoHistoricosOracle;
use Semae\Pca\Infrastructure\Oracle\Auditoria\OracleRegistroAuditoriaAdapter;
use Semae\Pca\Infrastructure\Oracle\EstadoOperacional\MapeamentoEstadoOperacionalOracle;
use Semae\Pca\Infrastructure\Oracle\EstadoOperacional\OracleEstadoOperacionalPublicacaoAdapter;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoDemandasOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoLotacoesOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoPlanosOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoUnidadesOrcamentariasOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\OraclePlanoRepository;

final class OracleRegistroAuditoriaAdapterTest extends TestCase
{
    /** @regra RN-AUD-001 @regra RN-AUD-002 @regra RN-AUD-004 */
    public function testDeveInserirOsTresProcedimentosTipadosComMensagemEAutoriaRecebidas(): void
    {
        foreach ([
            [ProcedimentoPublicacaoPca::PUBLICACAO_INICIAL, 5],
            [ProcedimentoPublicacaoPca::RETIFICACAO, 8],
            [ProcedimentoPublicacaoPca::EXCLUSAO_INTEGRAL, 9],
        ] as [$procedimento, $codigo]) {
            [$adapter, $conexao, $adodb] = $this->criarContexto();
            $conexao->iniciarTransacao();
            $intencao = $this->intencao($procedimento);

            $resultado = $adapter->registrar($intencao);

            $this->assertSame(SituacaoPersistenciaLocal::CONCLUIDA, $resultado->getSituacao());
            $this->assertCount(1, $adodb->execucoes);
            $execucao = $adodb->execucoes[0];
            $this->assertStringContainsString('INSERT INTO APP.PAC_HISTORICOS', $execucao['sql']);
            $this->assertStringContainsString(
                '(DATA_HISTORICO, FK_PROCEDIMENTO, MENSAGEM, FK_REPRESENTANTE, FK_LOTACAO)',
                $execucao['sql']
            );
            $this->assertStringContainsString(
                'VALUES (SYSDATE, :param_0, :param_1, :param_2, :param_3)',
                $execucao['sql']
            );
            $this->assertSame(
                ['param_0' => $codigo, 'param_1' => $intencao->getMensagem(),
                    'param_2' => 709, 'param_3' => 159],
                $execucao['parametros']
            );
            $this->assertSame([], $adodb->consultas);
            $this->assertTrue($conexao->estaEmTransacao());
            $this->assertSame(0, $adodb->commits);
            $this->assertSame(0, $adodb->rollbacks);
        }
    }

    /** @regra RN-AUD-001 */
    public function testDeveSerAppendOnlySemConsultarCatalogoOuAtualizarHistorico(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $conexao->iniciarTransacao();
        $intencao = $this->intencao(ProcedimentoPublicacaoPca::RETIFICACAO);

        $adapter->registrar($intencao);
        $adapter->registrar($intencao);

        $this->assertCount(2, $adodb->execucoes);
        foreach ($adodb->execucoes as $execucao) {
            $sql = strtoupper($execucao['sql']);
            $this->assertStringStartsWith('INSERT INTO APP.PAC_HISTORICOS', $sql);
            $this->assertStringNotContainsString('UPDATE ', $sql);
            $this->assertStringNotContainsString('DELETE ', $sql);
            $this->assertStringNotContainsString('PAC_PROCEDIMENTOS', $sql);
        }
        $this->assertSame([], $adodb->consultas);
    }

    public function testDeveExigirTransacaoAtivaSemExecutarInsert(): void
    {
        [$adapter, , $adodb] = $this->criarContexto();

        $resultado = $adapter->registrar(
            $this->intencao(ProcedimentoPublicacaoPca::PUBLICACAO_INICIAL)
        );

        $this->assertSame(SituacaoPersistenciaLocal::FALHA_SISTEMICA, $resultado->getSituacao());
        $this->assertSame('REGISTRO_AUDITORIA_EXIGE_TRANSACAO', $resultado->getFalha()?->getCodigo());
        $this->assertSame([], $adodb->execucoes);
    }

    public function testDeveRecusarAutoriaFisicamenteInvalidaAntesDoInsert(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $conexao->iniciarTransacao();
        $intencao = new IntencaoRegistroHistorico(
            ProcedimentoPublicacaoPca::PUBLICACAO_INICIAL,
            'Publicação confirmada.',
            new Representante(0, 'Representante inválido'),
            new Lotacao(159, 'Tecnologia')
        );

        $resultado = $adapter->registrar($intencao);

        $this->assertSame(SituacaoPersistenciaLocal::FALHA_SISTEMICA, $resultado->getSituacao());
        $this->assertSame('DADOS_AUDITORIA_INVALIDOS', $resultado->getFalha()?->getCodigo());
        $this->assertSame([], $adodb->execucoes);
        $this->assertTrue($conexao->estaEmTransacao());
    }

    public function testDeveTraduzirFalhaOracleSemControlarTransacaoOuExporDetalhes(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $conexao->iniciarTransacao();
        $adodb->errosExecute[0] = 942;

        $resultado = $adapter->registrar(
            $this->intencao(ProcedimentoPublicacaoPca::EXCLUSAO_INTEGRAL)
        );

        $this->assertSame(SituacaoPersistenciaLocal::FALHA_SISTEMICA, $resultado->getSituacao());
        $this->assertSame('REGISTRO_AUDITORIA_FALHOU', $resultado->getFalha()?->getCodigo());
        $this->assertStringNotContainsString('ORA', $resultado->getFalha()?->getMensagem() ?? '');
        $this->assertStringNotContainsString('SENSIVEL', $resultado->getFalha()?->getMensagem() ?? '');
        $this->assertTrue($conexao->estaEmTransacao());
        $this->assertSame(0, $adodb->commits);
        $this->assertSame(0, $adodb->rollbacks);
    }

    /** @regra RN-AUD-001 @regra RN-AUD-004 */
    public function testDeveCompartilharSessaoETransacaoComConfirmacaoEEstadoOperacional(): void
    {
        $adodb = new AdodbAuditoriaFake();
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $planoRepository = $this->criarPlanoRepository($conexao);
        $estado = $this->criarEstadoAdapter($conexao);
        $auditoria = $this->criarAdapter($conexao);
        $adodb->resultadosGetAll = [
            [['DATA_CONFIRMACAO' => null]],
            [['DATA_CONFIRMACAO' => '2026-08-24 16:45:10']],
            [[
                'ESTRATEGIA_ADOTADA_PUBLICACAO' => 'INDIVIDUAL',
                'UASG_ADOTADA' => '928344',
                'ESTADO_PUBLICACAO' => 'PUBLICADO',
                'SEQUENCIAL_PUBLICACAO_PNCP' => '77',
            ]],
        ];
        $conexao->iniciarTransacao();

        $planoRepository->confirmarSincronizacao($this->planoPendente());
        $resultadoEstado = $estado->registrar(
            UnidadeExternaPublicacao::individual($this->identidadePlano()),
            EstadoOperacionalPublicacao::publicado(new SequencialPca(77))
        );
        $resultadoAuditoria = $auditoria->registrar(
            $this->intencao(ProcedimentoPublicacaoPca::PUBLICACAO_INICIAL)
        );

        $this->assertSame(SituacaoPersistenciaLocal::CONCLUIDA, $resultadoEstado->getSituacao());
        $this->assertSame(SituacaoPersistenciaLocal::CONCLUIDA, $resultadoAuditoria->getSituacao());
        $this->assertCount(3, $adodb->execucoes);
        $this->assertStringContainsString('UPDATE APP.PAC_PLANOS', $adodb->execucoes[0]['sql']);
        $this->assertStringContainsString('DATA_PUBLICACAO = SYSDATE', $adodb->execucoes[0]['sql']);
        $this->assertStringContainsString('UPDATE SGI.PAC_PLANOS', $adodb->execucoes[1]['sql']);
        $this->assertStringNotContainsString(
            'ESTRATEGIA_ADOTADA_PUBLICACAO =',
            $adodb->execucoes[1]['sql']
        );
        $this->assertStringNotContainsString('UASG_ADOTADA =', $adodb->execucoes[1]['sql']);
        $this->assertStringContainsString('INSERT INTO APP.PAC_HISTORICOS', $adodb->execucoes[2]['sql']);
        $this->assertTrue($conexao->estaEmTransacao());
        $this->assertSame(1, $adodb->inicios);
        $this->assertSame(0, $adodb->commits);
        $this->assertSame(0, $adodb->rollbacks);
    }

    /** @return array{OracleRegistroAuditoriaAdapter, ConexaoAdodbEmprestada, AdodbAuditoriaFake} */
    private function criarContexto(): array
    {
        $adodb = new AdodbAuditoriaFake();
        $conexao = new ConexaoAdodbEmprestada($adodb);

        return [$this->criarAdapter($conexao), $conexao, $adodb];
    }

    private function criarAdapter(
        ConexaoAdodbEmprestada $conexao
    ): OracleRegistroAuditoriaAdapter {
        return new OracleRegistroAuditoriaAdapter(
            $conexao,
            new MapeamentoHistoricosOracle(
                'APP', 'PAC_HISTORICOS', 'DATA_HISTORICO', 'FK_PROCEDIMENTO',
                'MENSAGEM', 'FK_REPRESENTANTE', 'FK_LOTACAO'
            )
        );
    }

    private function intencao(
        ProcedimentoPublicacaoPca $procedimento
    ): IntencaoRegistroHistorico {
        return new IntencaoRegistroHistorico(
            $procedimento,
            'Exercício: 2027; UO: UO-101; UASG externa: 928344; sequencialPca: 77.',
            new Representante(709, 'Maria da Silva'),
            new Lotacao(159, 'Coordenadoria de Tecnologia')
        );
    }

    private function criarEstadoAdapter(
        ConexaoAdodbEmprestada $conexao
    ): OracleEstadoOperacionalPublicacaoAdapter {
        return new OracleEstadoOperacionalPublicacaoAdapter(
            $conexao,
            new MapeamentoEstadoOperacionalOracle(
                'SGI', 'PAC_PLANOS', 'FK_UNIDADE_ORCAMENTARIA', 'ANO_EXERCICIO',
                'ESTRATEGIA_ADOTADA_PUBLICACAO', 'UASG_ADOTADA',
                'ESTADO_PUBLICACAO', 'SEQUENCIAL_PUBLICACAO_PNCP',
                'INDIVIDUAL', 'CONSOLIDADO', 'NAO_PUBLICADO', 'PUBLICADO',
                'EXCLUIDO'
            )
        );
    }

    private function criarPlanoRepository(
        ConexaoAdodbEmprestada $conexao
    ): OraclePlanoRepository {
        return new OraclePlanoRepository(
            $conexao,
            new MapeamentoPlanosOracle(
                'APP', 'PAC_PLANOS', 'ID_PLANO', 'FK_UO', 'ANO_EXERCICIO',
                'VERSAO', 'DATA_HOMOLOGACAO', 'DATA_PUBLICACAO'
            ),
            new MapeamentoDemandasOracle(
                'APP', 'PAC_DEMANDAS', 'ID_DEMANDA', 'FK_PLANO', 'SITUACAO',
                'FK_UNIDADE_REQUISITANTE', 'DATA_DESEJADA', 'CATEGORIA',
                'DESCRICAO', 'UNIDADE_MEDIDA', 'QUANTIDADE', 'VALOR_TOTAL',
                'VALOR_ORCAMENTO_EXERCICIO', 'CATALOGO', 'CODIGO_ITEM', 'GRUPO',
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
                'APP', 'PAC_UO', 'ID_UNIDADE_ORCAMENTARIA',
                'ID_UNIDADE_ORCAMENTARIA', 'CODIGO_UASG', 'DESCRICAO_UASG',
                'ATIVA', 'S'
            ),
            new MapeamentoLotacoesOracle(
                'APP', 'RH_LOTACOES', 'COD_LOTACAO', 'NOME_LOTACAO'
            ),
            new DateTimeZone('America/Sao_Paulo')
        );
    }

    private function planoPendente(): Plano
    {
        return new Plano(
            $this->identidadePlano(),
            3,
            new DateTimeImmutable('2026-08-20 14:35:12', new DateTimeZone('America/Sao_Paulo')),
            [],
            null
        );
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
}

final class AdodbAuditoriaFake
{
    /** @var array<int, array{sql: string, parametros: array<int|string, mixed>}> */
    public array $consultas = [];

    /** @var array<int, array{sql: string, parametros: array<int|string, mixed>}> */
    public array $execucoes = [];

    /** @var array<int, array<int, array<string, mixed>>> */
    public array $resultadosGetAll = [];

    /** @var array<int, int> */
    public array $errosExecute = [];

    public int $inicios = 0;
    public int $commits = 0;
    public int $rollbacks = 0;
    private int $numeroErro = 0;

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
        $indice = count($this->execucoes);
        $this->execucoes[] = ['sql' => $sql, 'parametros' => $parametros];

        if (isset($this->errosExecute[$indice])) {
            $this->numeroErro = $this->errosExecute[$indice];

            return false;
        }

        $this->numeroErro = 0;

        return true;
    }

    /** @param array<int|string, mixed> $parametros */
    public function GetAll(string $sql, array $parametros = []): array
    {
        $indice = count($this->consultas);
        $this->consultas[] = ['sql' => $sql, 'parametros' => $parametros];
        $this->numeroErro = 0;

        return $this->resultadosGetAll[$indice] ?? [];
    }

    public function Param(false|string $nome): string
    {
        return $nome === false ? '' : ':' . $nome;
    }

    public function ErrorNo(): int
    {
        return $this->numeroErro;
    }

    public function ErrorMsg(): string
    {
        return $this->numeroErro > 0
            ? 'ORA-' . str_pad((string) $this->numeroErro, 5, '0', STR_PAD_LEFT)
                . ': tabela APP.SENSIVEL não encontrada'
            : '';
    }
}
