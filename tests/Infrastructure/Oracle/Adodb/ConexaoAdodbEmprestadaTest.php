<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Oracle\Adodb;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Oracle\Adodb\FalhaOracleException;
use stdClass;

final class ConexaoAdodbEmprestadaTest extends TestCase
{
    public function testDeveRejeitarObjetoSemOContratoAdodbObrigatorio(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('BeginTrans');

        new ConexaoAdodbEmprestada(new stdClass());
    }

    public function testDeveExecutarComBindsNomeadosGeradosPorParam(): void
    {
        $adodb = new AdodbEmprestadoFake();
        $conexao = new ConexaoAdodbEmprestada($adodb);

        $conexao->executar(
            'UPDATE PAC_TESTE SET VALOR = ? WHERE ID = ?',
            ['novo', 10]
        );

        $this->assertSame([false, 'param_0', 'param_1'], $adodb->parametrosSolicitados);
        $this->assertSame(
            'UPDATE PAC_TESTE SET VALOR = :param_0 WHERE ID = :param_1',
            $adodb->execucoes[0]['sql']
        );
        $this->assertSame(
            ['param_0' => 'novo', 'param_1' => 10],
            $adodb->execucoes[0]['parametros']
        );
    }

    public function testDeveConsultarColecaoComAMesmaConversaoDeBinds(): void
    {
        $adodb = new AdodbEmprestadoFake();
        $adodb->resultadoGetAll = [
            ['CODIGO' => '986700', 'DESCRICAO' => 'UASG Consolidadora'],
        ];
        $conexao = new ConexaoAdodbEmprestada($adodb);

        $linhas = $conexao->buscarTodos(
            'SELECT CODIGO, DESCRICAO FROM PAC_UASG WHERE CODIGO = ?',
            ['986700']
        );

        $this->assertSame($adodb->resultadoGetAll, $linhas);
        $this->assertSame(
            'SELECT CODIGO, DESCRICAO FROM PAC_UASG WHERE CODIGO = :param_0',
            $adodb->consultas[0]['sql']
        );
        $this->assertSame(['param_0' => '986700'], $adodb->consultas[0]['parametros']);
    }

    public function testDeveControlarInicioCommitERollbackDaTransacao(): void
    {
        $adodb = new AdodbEmprestadoFake();
        $conexao = new ConexaoAdodbEmprestada($adodb);

        $this->assertFalse($conexao->estaEmTransacao());

        $conexao->iniciarTransacao();
        $this->assertTrue($conexao->estaEmTransacao());
        $conexao->confirmarTransacao();
        $this->assertFalse($conexao->estaEmTransacao());

        $conexao->iniciarTransacao();
        $conexao->desfazerTransacao();
        $this->assertFalse($conexao->estaEmTransacao());

        $this->assertSame(2, $adodb->inicios);
        $this->assertSame(1, $adodb->commits);
        $this->assertSame(1, $adodb->rollbacks);
    }

    public function testDeveImpedirTransacaoAninhadaSemTocarNovamenteNoAdodb(): void
    {
        $adodb = new AdodbEmprestadoFake();
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $conexao->iniciarTransacao();

        try {
            $conexao->iniciarTransacao();
            $this->fail('Era esperada falha ao iniciar uma transacao aninhada.');
        } catch (LogicException $exception) {
            $this->assertSame('Já existe uma transação Oracle ativa.', $exception->getMessage());
        }

        $this->assertSame(1, $adodb->inicios);
        $this->assertTrue($conexao->estaEmTransacao());
    }

    public function testDeveExigirTransacaoAtivaParaCommitERollback(): void
    {
        $conexao = new ConexaoAdodbEmprestada(new AdodbEmprestadoFake());

        foreach (['confirmarTransacao', 'desfazerTransacao'] as $metodo) {
            try {
                $conexao->{$metodo}();
                $this->fail("Era esperada falha em {$metodo} sem transacao ativa.");
            } catch (LogicException $exception) {
                $this->assertSame('Não existe transação Oracle ativa.', $exception->getMessage());
            }
        }
    }

    public function testDeveSanitizarFalhaDoDriverEExporSomenteCodigoOracle(): void
    {
        $adodb = new AdodbEmprestadoFake();
        $adodb->falhaExecute = new RuntimeException('segredo presente na mensagem do driver');
        $adodb->numeroErro = 0;
        $adodb->mensagemErro = 'ORA-00942: tabela SENSIVEL.CLIENTES nao existe';
        $conexao = new ConexaoAdodbEmprestada($adodb);

        try {
            $conexao->executar('SELECT 1 FROM TABELA_INTERNA');
            $this->fail('Era esperada uma falha Oracle sanitizada.');
        } catch (FalhaOracleException $exception) {
            $this->assertSame('ORA-00942', $exception->getCodigoOracle());
            $this->assertSame('Falha ao executar comando Oracle.', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
            $this->assertStringNotContainsString('SENSIVEL', $exception->getMessage());
            $this->assertStringNotContainsString('segredo', $exception->getMessage());
        }
    }

    public function testDevePreservarEstadoAtivoQuandoCommitFalhar(): void
    {
        $adodb = new AdodbEmprestadoFake();
        $adodb->resultadoCommit = false;
        $adodb->numeroErro = 3113;
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $conexao->iniciarTransacao();

        try {
            $conexao->confirmarTransacao();
            $this->fail('Era esperada falha no commit.');
        } catch (FalhaOracleException $exception) {
            $this->assertSame('ORA-03113', $exception->getCodigoOracle());
        }

        $this->assertTrue($conexao->estaEmTransacao());
    }

    public function testNuncaDeveFecharAConexaoEmprestada(): void
    {
        $adodb = new AdodbEmprestadoFake();
        $conexao = new ConexaoAdodbEmprestada($adodb);

        $conexao->iniciarTransacao();
        $conexao->desfazerTransacao();
        unset($conexao);
        gc_collect_cycles();

        $this->assertSame(0, $adodb->fechamentos);
    }
}

final class AdodbEmprestadoFake
{
    public int $inicios = 0;
    public int $commits = 0;
    public int $rollbacks = 0;
    public int $fechamentos = 0;

    /** @var array<int, false|string> */
    public array $parametrosSolicitados = [];

    /** @var array<int, array{sql: string, parametros: array<int|string, mixed>}> */
    public array $execucoes = [];

    /** @var array<int, array{sql: string, parametros: array<int|string, mixed>}> */
    public array $consultas = [];

    /** @var array<int, array<string, mixed>>|false */
    public array|false $resultadoGetAll = [];

    public bool $resultadoInicio = true;
    public bool $resultadoCommit = true;
    public bool $resultadoRollback = true;
    public mixed $resultadoExecute = true;
    public int|string $numeroErro = 0;
    public string $mensagemErro = '';
    public ?RuntimeException $falhaExecute = null;

    public function BeginTrans(): bool
    {
        $this->inicios++;

        return $this->resultadoInicio;
    }

    public function CommitTrans(): bool
    {
        $this->commits++;

        return $this->resultadoCommit;
    }

    public function RollbackTrans(): bool
    {
        $this->rollbacks++;

        return $this->resultadoRollback;
    }

    /** @param array<int|string, mixed> $parametros */
    public function Execute(string $sql, array $parametros = []): mixed
    {
        if ($this->falhaExecute !== null) {
            throw $this->falhaExecute;
        }

        $this->execucoes[] = ['sql' => $sql, 'parametros' => $parametros];

        return $this->resultadoExecute;
    }

    /** @param array<int|string, mixed> $parametros */
    public function GetAll(string $sql, array $parametros = []): array|false
    {
        $this->consultas[] = ['sql' => $sql, 'parametros' => $parametros];

        return $this->resultadoGetAll;
    }

    public function Param(false|string $nome): string
    {
        $this->parametrosSolicitados[] = $nome;

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

    public function Close(): void
    {
        $this->fechamentos++;
    }
}
