<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Oracle\EstadoOperacional;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\SituacaoConsultaEstadoOperacional;
use Semae\Pca\Application\Enums\SituacaoPersistenciaLocal;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\EstadoPublicacao;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Oracle\EstadoOperacional\MapeamentoEstadoOperacionalOracle;
use Semae\Pca\Infrastructure\Oracle\EstadoOperacional\OracleEstadoOperacionalPublicacaoAdapter;

final class OracleEstadoOperacionalPublicacaoAdapterTest extends TestCase
{
    /** @regra RN-PUB-008 */
    public function testDeveConsultarEstadoIndividualCoerenteEntreVersoesEIgnorarLinhaNovaNula(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $conexao->iniciarTransacao();
        $adodb->resultadosGetAll = [[
            $this->linha('I', '928344', 'P', '77'),
            $this->linha(null, null, null, null),
        ]];

        $resultado = $adapter->consultar($this->unidadeIndividual());

        $this->assertSame(SituacaoConsultaEstadoOperacional::DISPONIVEL, $resultado->getSituacao());
        $this->assertSame(EstadoPublicacao::PUBLICADO, $resultado->getEstadoOperacional()?->getEstado());
        $this->assertSame(77, $resultado->getEstadoOperacional()?->getSequencialPca()?->getValor());
        $this->assertStringContainsString('FROM APP.PAC_PLANOS p', $adodb->consultas[0]['sql']);
        $this->assertStringContainsString('p.ANO_EXERCICIO = :param_0', $adodb->consultas[0]['sql']);
        $this->assertStringContainsString('p.FK_UO = :param_1', $adodb->consultas[0]['sql']);
        $this->assertSame(['param_0' => 2027, 'param_1' => 101], $adodb->consultas[0]['parametros']);
        $this->assertTrue($conexao->estaEmTransacao());
        $this->assertSame(0, $adodb->commits);
        $this->assertSame(0, $adodb->rollbacks);
    }

    /** @regra RN-PUB-008 */
    public function testDeveConsultarConsolidadoEmTodasAsLinhasDoExercicio(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $conexao->iniciarTransacao();
        $adodb->resultadosGetAll = [[
            $this->linha('C', 986700, 'E', 88),
            $this->linha('C', '986700', 'E', '88'),
        ]];

        $resultado = $adapter->consultar($this->unidadeConsolidada());

        $this->assertSame(EstadoPublicacao::EXCLUIDO, $resultado->getEstadoOperacional()?->getEstado());
        $this->assertSame(88, $resultado->getEstadoOperacional()?->getSequencialPca()?->getValor());
        $this->assertStringNotContainsString('p.FK_UO =', $adodb->consultas[0]['sql']);
        $this->assertSame(['param_0' => 2027], $adodb->consultas[0]['parametros']);
    }

    /** @regra RN-PUB-008 */
    public function testDeveInterpretarAusenciaIntegralDeEstadoESequencialComoNaoPublicado(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $conexao->iniciarTransacao();
        $adodb->resultadosGetAll = [[
            $this->linha('I', '928344', null, null),
            $this->linha(null, null, null, null),
        ]];

        $resultado = $adapter->consultar($this->unidadeIndividual());

        $this->assertSame(SituacaoConsultaEstadoOperacional::DISPONIVEL, $resultado->getSituacao());
        $this->assertSame(EstadoPublicacao::NAO_PUBLICADO, $resultado->getEstadoOperacional()?->getEstado());
        $this->assertNull($resultado->getEstadoOperacional()?->getSequencialPca());
    }

    /** @regra RN-PUB-008 */
    public function testDeveReconhecerOsTresEstadosTipados(): void
    {
        foreach ([
            ['N', null, EstadoPublicacao::NAO_PUBLICADO],
            ['P', '12', EstadoPublicacao::PUBLICADO],
            ['E', 12, EstadoPublicacao::EXCLUIDO],
        ] as [$literal, $sequencial, $esperado]) {
            [$adapter, $conexao, $adodb] = $this->criarContexto();
            $conexao->iniciarTransacao();
            $adodb->resultadosGetAll = [[
                $this->linha('I', '928344', $literal, $sequencial),
            ]];

            $resultado = $adapter->consultar($this->unidadeIndividual());

            $this->assertSame($esperado, $resultado->getEstadoOperacional()?->getEstado());
        }
    }

    /** @regra RN-PUB-008 */
    public function testDeveRetornarConflitoParaConteudoFisicoIncoerente(): void
    {
        $cenarios = [
            [],
            [$this->linha('I', '928344', 'P', null)],
            [$this->linha('I', '928344', 'N', 1)],
            [$this->linha('I', '928344', 'X', 1)],
            [$this->linha('I', '928344', 'P', '0')],
            [$this->linha('I', '928344', 'P', 1.5)],
            [$this->linha('I', '928344', 'P', '999999999999999999999999999999')],
            [$this->linha('C', '928344', 'P', 1)],
            [$this->linha('I', '999999', 'P', 1)],
            [$this->linha('I', '928344', 'P', 1), $this->linha('I', '928344', 'P', 2)],
        ];

        foreach ($cenarios as $linhas) {
            [$adapter, $conexao, $adodb] = $this->criarContexto();
            $conexao->iniciarTransacao();
            $adodb->resultadosGetAll = [$linhas];

            $resultado = $adapter->consultar($this->unidadeIndividual());

            $this->assertSame(SituacaoConsultaEstadoOperacional::CONFLITO, $resultado->getSituacao());
            $this->assertSame('ESTADO_OPERACIONAL_ORACLE_CONFLITANTE', $resultado->getFalha()?->getCodigo());
        }
    }

    public function testDeveExigirTransacaoAtivaParaConsultaERegistro(): void
    {
        [$adapter, , $adodb] = $this->criarContexto();

        $consulta = $adapter->consultar($this->unidadeIndividual());
        $registro = $adapter->registrar(
            $this->unidadeIndividual(),
            EstadoOperacionalPublicacao::publicado(new SequencialPca(77))
        );

        $this->assertSame(SituacaoConsultaEstadoOperacional::FALHA_SISTEMICA, $consulta->getSituacao());
        $this->assertSame('CONSULTA_ESTADO_OPERACIONAL_EXIGE_TRANSACAO', $consulta->getFalha()?->getCodigo());
        $this->assertSame(SituacaoPersistenciaLocal::FALHA_SISTEMICA, $registro->getSituacao());
        $this->assertSame('REGISTRO_ESTADO_OPERACIONAL_EXIGE_TRANSACAO', $registro->getFalha()?->getCodigo());
        $this->assertSame([], $adodb->consultas);
        $this->assertSame([], $adodb->execucoes);
    }

    /** @regra RN-PUB-008 */
    public function testDeveRegistrarEstadoEmUmStatementERelerAntesDoSucesso(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $conexao->iniciarTransacao();
        $adodb->resultadosGetAll = [[
            $this->linha('I', '928344', 'P', '77'),
            $this->linha('I', '928344', 'P', '77'),
        ]];

        $resultado = $adapter->registrar(
            $this->unidadeIndividual(),
            EstadoOperacionalPublicacao::publicado(new SequencialPca(77))
        );

        $this->assertSame(SituacaoPersistenciaLocal::CONCLUIDA, $resultado->getSituacao());
        $this->assertCount(1, $adodb->execucoes);
        $execucao = $adodb->execucoes[0];
        $this->assertStringContainsString(
            'SET MODO_PUBLICACAO = :param_0, UASG_PUBLICACAO = :param_1, '
                . 'ESTADO_PUBLICACAO = :param_2, SEQUENCIAL_PCA = :param_3',
            $execucao['sql']
        );
        $this->assertStringContainsString('ANO_EXERCICIO = :param_4', $execucao['sql']);
        $this->assertStringContainsString('FK_UO = :param_5', $execucao['sql']);
        $this->assertSame(
            ['param_0' => 'I', 'param_1' => '928344', 'param_2' => 'P',
                'param_3' => 77, 'param_4' => 2027, 'param_5' => 101],
            $execucao['parametros']
        );
        $this->assertCount(1, $adodb->consultas);
        $this->assertTrue($conexao->estaEmTransacao());
        $this->assertSame(0, $adodb->commits);
        $this->assertSame(0, $adodb->rollbacks);
    }

    public function testDeveRegistrarNaoPublicadoComSequencialNuloNoConsolidado(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $conexao->iniciarTransacao();
        $adodb->resultadosGetAll = [[
            $this->linha('C', '986700', 'N', null),
        ]];

        $resultado = $adapter->registrar(
            $this->unidadeConsolidada(),
            EstadoOperacionalPublicacao::naoPublicado()
        );

        $this->assertSame(SituacaoPersistenciaLocal::CONCLUIDA, $resultado->getSituacao());
        $this->assertSame(
            ['C', '986700', 'N', null, 2027],
            array_values($adodb->execucoes[0]['parametros'])
        );
        $this->assertStringNotContainsString('FK_UO =', $adodb->execucoes[0]['sql']);
    }

    public function testDeveFalharQuandoReleituraNaoConfirmarIntencao(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $conexao->iniciarTransacao();
        $adodb->resultadosGetAll = [[
            $this->linha('I', '928344', 'E', 77),
        ]];

        $resultado = $adapter->registrar(
            $this->unidadeIndividual(),
            EstadoOperacionalPublicacao::publicado(new SequencialPca(77))
        );

        $this->assertSame(SituacaoPersistenciaLocal::FALHA_SISTEMICA, $resultado->getSituacao());
        $this->assertSame('REGISTRO_ESTADO_OPERACIONAL_NAO_CONFIRMADO', $resultado->getFalha()?->getCodigo());
    }

    public function testDeveSanitizarFalhasOracleSemControlarATransacao(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $conexao->iniciarTransacao();
        $adodb->errosGetAll[0] = 942;

        $consulta = $adapter->consultar($this->unidadeIndividual());

        $this->assertSame(SituacaoConsultaEstadoOperacional::FALHA_SISTEMICA, $consulta->getSituacao());
        $this->assertSame('CONSULTA_ESTADO_OPERACIONAL_FALHOU', $consulta->getFalha()?->getCodigo());
        $this->assertStringNotContainsString('ORA', $consulta->getFalha()?->getMensagem() ?? '');
        $this->assertTrue($conexao->estaEmTransacao());
        $this->assertSame(0, $adodb->commits);
        $this->assertSame(0, $adodb->rollbacks);

        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $conexao->iniciarTransacao();
        $adodb->errosExecute[0] = 3113;

        $registro = $adapter->registrar(
            $this->unidadeIndividual(),
            EstadoOperacionalPublicacao::publicado(new SequencialPca(77))
        );

        $this->assertSame(SituacaoPersistenciaLocal::FALHA_SISTEMICA, $registro->getSituacao());
        $this->assertSame('REGISTRO_ESTADO_OPERACIONAL_FALHOU', $registro->getFalha()?->getCodigo());
        $this->assertTrue($conexao->estaEmTransacao());
        $this->assertSame(0, $adodb->commits);
        $this->assertSame(0, $adodb->rollbacks);
    }

    /** @return array{OracleEstadoOperacionalPublicacaoAdapter, ConexaoAdodbEmprestada, AdodbEstadoOperacionalFake} */
    private function criarContexto(): array
    {
        $adodb = new AdodbEstadoOperacionalFake();
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $adapter = new OracleEstadoOperacionalPublicacaoAdapter(
            $conexao,
            new MapeamentoEstadoOperacionalOracle(
                'APP', 'PAC_PLANOS', 'FK_UO', 'ANO_EXERCICIO',
                'MODO_PUBLICACAO', 'UASG_PUBLICACAO', 'ESTADO_PUBLICACAO',
                'SEQUENCIAL_PCA', 'I', 'C', 'N', 'P', 'E'
            )
        );

        return [$adapter, $conexao, $adodb];
    }

    private function unidadeIndividual(): UnidadeExternaPublicacao
    {
        return UnidadeExternaPublicacao::individual(new IdentidadePlano(
            new UnidadeOrcamentaria(
                new IdentidadeUnidadeOrcamentaria('UO-101'),
                new Lotacao(101, 'Lotação 101'),
                new Uasg('928344', 'UASG 928344'),
                'Unidade Orçamentária 101'
            ),
            new AnoExecucao(2027)
        ));
    }

    private function unidadeConsolidada(): UnidadeExternaPublicacao
    {
        return UnidadeExternaPublicacao::consolidada(
            new AnoExecucao(2027),
            new Uasg('986700', 'UASG Central')
        );
    }

    /** @return array<string, mixed> */
    private function linha(mixed $modo, mixed $uasg, mixed $estado, mixed $sequencial): array
    {
        return [
            'MODO_PUBLICACAO' => $modo,
            'UASG_PUBLICACAO' => $uasg,
            'ESTADO_PUBLICACAO' => $estado,
            'SEQUENCIAL_PCA' => $sequencial,
        ];
    }
}

final class AdodbEstadoOperacionalFake
{
    /** @var array<int, array{sql: string, parametros: array<int|string, mixed>}> */
    public array $consultas = [];

    /** @var array<int, array{sql: string, parametros: array<int|string, mixed>}> */
    public array $execucoes = [];

    /** @var array<int, array<int, array<string, mixed>>> */
    public array $resultadosGetAll = [];

    /** @var array<int, int> */
    public array $errosGetAll = [];

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
    public function GetAll(string $sql, array $parametros = []): array|false
    {
        $indice = count($this->consultas);
        $this->consultas[] = ['sql' => $sql, 'parametros' => $parametros];

        if (isset($this->errosGetAll[$indice])) {
            $this->numeroErro = $this->errosGetAll[$indice];

            return false;
        }

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
            : '';
    }
}
