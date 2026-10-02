<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Oracle\Transacao;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\SituacaoFinalizacaoUnidadeTrabalho;
use Semae\Pca\Application\Enums\SituacaoInicioUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\ChaveUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Oracle\Transacao\MapeamentoControlesConcorrenciaOracle;
use Semae\Pca\Infrastructure\Oracle\Transacao\OracleUnidadeDeTrabalhoAdapter;
use Tests\Support\NormalizadorSql;

final class OracleUnidadeDeTrabalhoAdapterTest extends TestCase
{
    public function testDeveProvisionarControleEmTransacaoCurtaEAdquirirLockAnual(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $ano = new AnoExecucao((int) date('Y') + 1);

        $resultado = $adapter->iniciar(ChaveUnidadeTrabalho::estrategiaAnual($ano));

        $this->assertSame(SituacaoInicioUnidadeTrabalho::INICIADA, $resultado->getSituacao());
        $this->assertTrue($conexao->estaEmTransacao());
        $this->assertSame(
            ['BEGIN', 'EXECUTE', 'COMMIT', 'BEGIN', 'GET_ALL'],
            $adodb->eventos
        );
        $this->assertStringContainsString(
            'INSERT INTO SGI.PAC_CONTROLES_CONCORRENCIA',
            $adodb->execucoes[0]['sql']
        );
        $sqlProvisionamento = NormalizadorSql::normalizar($adodb->execucoes[0]['sql']);
        $this->assertSame(
            'INSERT INTO SGI.PAC_CONTROLES_CONCORRENCIA '
            . '(ESCOPO, ANO_EXECUCAO, IDENTIFICADOR_ALVO) '
            . 'SELECT :param_0, :param_1, :param_2 FROM DUAL WHERE NOT EXISTS ('
            . 'SELECT 1 FROM SGI.PAC_CONTROLES_CONCORRENCIA '
            . 'WHERE ESCOPO = :param_3 AND ANO_EXECUCAO = :param_4 '
            . 'AND IDENTIFICADOR_ALVO = :param_5)',
            $sqlProvisionamento
        );
        $this->assertStringContainsString('WHERE NOT EXISTS', $sqlProvisionamento);
        $this->assertStringNotContainsString('CREATE', $sqlProvisionamento);
        $this->assertStringNotContainsString('ALTER', $sqlProvisionamento);
        $this->assertSame(
            ['ESTRATEGIA_ANUAL', $ano->getValor(), 'ESTRATEGIA_ANUAL', 'ESTRATEGIA_ANUAL', $ano->getValor(), 'ESTRATEGIA_ANUAL'],
            array_values($adodb->execucoes[0]['parametros'])
        );
        $this->assertStringContainsString('FOR UPDATE NOWAIT', $adodb->consultas[0]['sql']);
        $sqlAquisicao = NormalizadorSql::normalizar($adodb->consultas[0]['sql']);
        $this->assertSame(
            'SELECT ESCOPO FROM SGI.PAC_CONTROLES_CONCORRENCIA '
            . 'WHERE ESCOPO = :param_0 AND ANO_EXECUCAO = :param_1 '
            . 'AND IDENTIFICADOR_ALVO = :param_2 '
            . 'FOR UPDATE NOWAIT',
            $sqlAquisicao
        );
        $this->assertSame(
            ['ESTRATEGIA_ANUAL', $ano->getValor(), 'ESTRATEGIA_ANUAL'],
            array_values($adodb->consultas[0]['parametros'])
        );
        $this->assertFalse($adodb->closed);
    }

    public function testDeveUsarAUasgExternaComoIdentificadorAlvoDaUnidade(): void
    {
        [$adapter, , $adodb] = $this->criarContexto();
        $chave = ChaveUnidadeTrabalho::unidadeExterna($this->criarUnidadeExterna('986701'));

        $resultado = $adapter->iniciar($chave);

        $this->assertSame(SituacaoInicioUnidadeTrabalho::INICIADA, $resultado->getSituacao());
        $this->assertSame(
            ['UNIDADE_EXTERNA', $chave->getAnoExecucao()->getValor(), '986701'],
            array_values($adodb->consultas[0]['parametros'])
        );
    }

    public function testDeveTratarCorridaDeProvisionamentoComoControleJaExistente(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $adodb->errosExecute[0] = 1;

        $resultado = $adapter->iniciar(
            ChaveUnidadeTrabalho::estrategiaAnual(new AnoExecucao((int) date('Y') + 1))
        );

        $this->assertSame(SituacaoInicioUnidadeTrabalho::INICIADA, $resultado->getSituacao());
        $this->assertTrue($conexao->estaEmTransacao());
        $this->assertSame(
            ['BEGIN', 'EXECUTE', 'ROLLBACK', 'BEGIN', 'GET_ALL'],
            $adodb->eventos
        );
    }

    public function testDeveTraduzirContencaoNowaitERemoverATransacaoLocal(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $adodb->errosGetAll[0] = 54;

        $resultado = $adapter->iniciar(
            ChaveUnidadeTrabalho::estrategiaAnual(new AnoExecucao((int) date('Y') + 1))
        );

        $this->assertSame(SituacaoInicioUnidadeTrabalho::BLOQUEADA, $resultado->getSituacao());
        $this->assertSame('UNIDADE_TRABALHO_BLOQUEADA', $resultado->getFalha()?->getCodigo());
        $this->assertFalse($conexao->estaEmTransacao());
        $this->assertSame(
            ['BEGIN', 'EXECUTE', 'COMMIT', 'BEGIN', 'GET_ALL', 'ROLLBACK'],
            $adodb->eventos
        );

        $desfazimento = $adapter->desfazer();

        $this->assertSame(
            SituacaoFinalizacaoUnidadeTrabalho::CONCLUIDA,
            $desfazimento->getSituacao()
        );
        $this->assertSame(1, $adodb->rollbacks);
    }

    public function testDeveTiparTabelaDeControlesInexistenteSemTentarCriarDdl(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $adodb->errosExecute[0] = 942;

        $resultado = $adapter->iniciar(
            ChaveUnidadeTrabalho::estrategiaAnual(new AnoExecucao((int) date('Y') + 1))
        );

        $this->assertSame(
            SituacaoInicioUnidadeTrabalho::FALHA_SISTEMICA,
            $resultado->getSituacao()
        );
        $this->assertSame('TABELA_ANCORAS_INDISPONIVEL', $resultado->getFalha()?->getCodigo());
        $this->assertFalse($conexao->estaEmTransacao());
        $this->assertSame(['BEGIN', 'EXECUTE', 'ROLLBACK'], $adodb->eventos);
        $this->assertStringNotContainsString('CREATE', $adodb->execucoes[0]['sql']);
    }

    public function testDeveFalharEExecutarRollbackQuandoControleNaoForLocalizado(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $adodb->resultadosGetAll[0] = [];

        $resultado = $adapter->iniciar(
            ChaveUnidadeTrabalho::estrategiaAnual(new AnoExecucao((int) date('Y') + 1))
        );

        $this->assertSame(
            SituacaoInicioUnidadeTrabalho::FALHA_SISTEMICA,
            $resultado->getSituacao()
        );
        $this->assertSame('INICIO_UNIDADE_TRABALHO_FALHOU', $resultado->getFalha()?->getCodigo());
        $this->assertFalse($conexao->estaEmTransacao());
        $this->assertSame(
            ['BEGIN', 'EXECUTE', 'COMMIT', 'BEGIN', 'GET_ALL', 'ROLLBACK'],
            $adodb->eventos
        );
    }

    public function testDeveConfirmarEDesfazerUnidadesAdquiridas(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $ano = new AnoExecucao((int) date('Y') + 1);

        $adapter->iniciar(ChaveUnidadeTrabalho::estrategiaAnual($ano));
        $confirmacao = $adapter->confirmar();

        $this->assertSame(
            SituacaoFinalizacaoUnidadeTrabalho::CONCLUIDA,
            $confirmacao->getSituacao()
        );
        $this->assertFalse($conexao->estaEmTransacao());

        $adapter->iniciar(ChaveUnidadeTrabalho::estrategiaAnual($ano));
        $desfazimento = $adapter->desfazer();

        $this->assertSame(
            SituacaoFinalizacaoUnidadeTrabalho::CONCLUIDA,
            $desfazimento->getSituacao()
        );
        $this->assertFalse($conexao->estaEmTransacao());
        $this->assertSame(3, $adodb->commits);
        $this->assertSame(1, $adodb->rollbacks);
    }

    public function testDevePreservarTransacaoPreexistenteAoRecusarInicioAninhado(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $conexao->iniciarTransacao();

        $resultado = $adapter->iniciar(
            ChaveUnidadeTrabalho::estrategiaAnual(new AnoExecucao((int) date('Y') + 1))
        );

        $this->assertSame(
            SituacaoInicioUnidadeTrabalho::FALHA_SISTEMICA,
            $resultado->getSituacao()
        );
        $this->assertSame('UNIDADE_TRABALHO_JA_INICIADA', $resultado->getFalha()?->getCodigo());
        $this->assertTrue($conexao->estaEmTransacao());
        $this->assertSame(['BEGIN'], $adodb->eventos);
    }

    public function testDeveTiparFalhaDeCommitEPreservarTransacaoParaDesfazimento(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $adapter->iniciar(
            ChaveUnidadeTrabalho::estrategiaAnual(new AnoExecucao((int) date('Y') + 1))
        );
        $adodb->resultadoCommit = false;

        $resultado = $adapter->confirmar();

        $this->assertSame(
            SituacaoFinalizacaoUnidadeTrabalho::FALHA_SISTEMICA,
            $resultado->getSituacao()
        );
        $this->assertSame(
            'CONFIRMACAO_UNIDADE_TRABALHO_FALHOU',
            $resultado->getFalha()?->getCodigo()
        );
        $this->assertTrue($conexao->estaEmTransacao());

        $adodb->resultadoCommit = true;
        $this->assertSame(
            SituacaoFinalizacaoUnidadeTrabalho::CONCLUIDA,
            $adapter->desfazer()->getSituacao()
        );
    }

    public function testDeveTiparFalhaDeRollbackSemOcultarEstadoTransacional(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $adapter->iniciar(
            ChaveUnidadeTrabalho::estrategiaAnual(new AnoExecucao((int) date('Y') + 1))
        );
        $adodb->resultadoRollback = false;

        $resultado = $adapter->desfazer();

        $this->assertSame(
            SituacaoFinalizacaoUnidadeTrabalho::FALHA_SISTEMICA,
            $resultado->getSituacao()
        );
        $this->assertSame(
            'DESFAZIMENTO_UNIDADE_TRABALHO_FALHOU',
            $resultado->getFalha()?->getCodigo()
        );
        $this->assertTrue($conexao->estaEmTransacao());

        $adodb->resultadoRollback = true;
        $adapter->desfazer();
    }

    public function testDesfazerDeveSerIdempotenteSemTransacaoAtiva(): void
    {
        [$adapter, , $adodb] = $this->criarContexto();

        $resultado = $adapter->desfazer();

        $this->assertSame(
            SituacaoFinalizacaoUnidadeTrabalho::CONCLUIDA,
            $resultado->getSituacao()
        );
        $this->assertSame([], $adodb->eventos);
    }

    /** @return array{OracleUnidadeDeTrabalhoAdapter, ConexaoAdodbEmprestada, AdodbTransacaoFake} */
    private function criarContexto(): array
    {
        $adodb = new AdodbTransacaoFake();
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $mapeamento = new MapeamentoControlesConcorrenciaOracle(
            'SGI',
            'PAC_CONTROLES_CONCORRENCIA',
            'ESCOPO',
            'ANO_EXECUCAO',
            'IDENTIFICADOR_ALVO',
            'ESTRATEGIA_ANUAL'
        );

        return [
            new OracleUnidadeDeTrabalhoAdapter($conexao, $mapeamento),
            $conexao,
            $adodb,
        ];
    }

    private function criarUnidadeExterna(string $codigoUasg): UnidadeExternaPublicacao
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $uo = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria('UO-01'),
            new Lotacao(10, 'Diretoria'),
            new Uasg($codigoUasg, 'UASG da UO'),
            'Unidade Orçamentária'
        );

        return UnidadeExternaPublicacao::individual(new IdentidadePlano($uo, $ano));
    }
}

final class AdodbTransacaoFake
{
    /** @var string[] */
    public array $eventos = [];

    /** @var array<int, array{sql: string, parametros: array<int|string, mixed>}> */
    public array $execucoes = [];

    /** @var array<int, array{sql: string, parametros: array<int|string, mixed>}> */
    public array $consultas = [];

    /** @var array<int, int> */
    public array $errosExecute = [];

    /** @var array<int, int> */
    public array $errosGetAll = [];

    /** @var array<int, array<int, array<string, mixed>>> */
    public array $resultadosGetAll = [];

    public int $rollbacks = 0;
    public int $commits = 0;
    public bool $resultadoCommit = true;
    public bool $resultadoRollback = true;
    public bool $closed = false;
    private int $numeroErro = 0;

    public function BeginTrans(): bool
    {
        $this->eventos[] = 'BEGIN';

        return true;
    }

    public function CommitTrans(): bool
    {
        $this->eventos[] = 'COMMIT';
        $this->commits++;

        return $this->resultadoCommit;
    }

    public function RollbackTrans(): bool
    {
        $this->eventos[] = 'ROLLBACK';
        $this->rollbacks++;

        return $this->resultadoRollback;
    }

    public function Close(): void
    {
        $this->closed = true;
    }

    /** @param array<int|string, mixed> $parametros */
    public function Execute(string $sql, array $parametros = []): mixed
    {
        $indice = count($this->execucoes);
        $this->eventos[] = 'EXECUTE';
        $this->execucoes[] = ['sql' => $sql, 'parametros' => $parametros];

        if (isset($this->errosExecute[$indice])) {
            $this->numeroErro = $this->errosExecute[$indice];

            return false;
        }

        $this->numeroErro = 0;

        return true;
    }

    /** @param array<int|string, mixed> $parametros */
    public function GetAll(string $sql, array $parametros = []): array|false
    {
        $indice = count($this->consultas);
        $this->eventos[] = 'GET_ALL';
        $this->consultas[] = ['sql' => $sql, 'parametros' => $parametros];

        if (isset($this->errosGetAll[$indice])) {
            $this->numeroErro = $this->errosGetAll[$indice];

            return false;
        }

        $this->numeroErro = 0;

        return $this->resultadosGetAll[$indice] ?? [['ESCOPO' => 'LOCK']];
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
            : '';
    }
}
