<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Oracle\Contexto;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\SituacaoResolucaoContextoUsuario;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Oracle\Contexto\MapeamentoLotacoesUsuarioOracle;
use Semae\Pca\Infrastructure\Oracle\Contexto\MapeamentoRepresentantesOracle;
use Semae\Pca\Infrastructure\Oracle\Contexto\OracleContextoUsuarioAdapter;

final class OracleContextoUsuarioAdapterTest extends TestCase
{
    /** @regra RN-AUD-004 */
    public function testDeveResolverRepresentanteAtivoELotacaoVigenteAtiva(): void
    {
        [$adapter, $adodb] = $this->criarContexto('  Maria.Silva  ');
        $adodb->resultadosGetAll = [
            [['ID_REPRESENTANTE' => '709', 'NOME_REPRESENTANTE' => ' Maria da Silva ']],
            [['ID_LOTACAO' => 159, 'NOME_LOTACAO' => ' Coordenadoria de Tecnologia ']],
        ];

        $resultado = $adapter->resolver();

        $this->assertSame(SituacaoResolucaoContextoUsuario::RESOLVIDO, $resultado->getSituacao());
        $this->assertSame(709, $resultado->getContexto()?->getRepresentante()->getId());
        $this->assertSame('Maria da Silva', $resultado->getContexto()?->getRepresentante()->getNome());
        $this->assertSame(159, $resultado->getContexto()?->getLotacao()->getId());
        $this->assertSame('Coordenadoria de Tecnologia', $resultado->getContexto()?->getLotacao()->getNome());

        $representante = $adodb->consultas[0];
        $this->assertStringContainsString('FROM SGI.RH_FUNCIONARIOS f', $representante['sql']);
        $this->assertStringContainsString(
            'UPPER(f.NOME_USUARIO) = UPPER(:param_0)',
            $representante['sql']
        );
        $this->assertStringContainsString('f.ATIVO = :param_1', $representante['sql']);
        $this->assertSame(['param_0' => 'Maria.Silva', 'param_1' => 1], $representante['parametros']);

        $lotacao = $adodb->consultas[1];
        $this->assertStringContainsString('FROM SGI.RH_FUNCIONARIOS_LOTACOES fl', $lotacao['sql']);
        $this->assertStringContainsString(
            'JOIN SGI.RH_LOTACOES l ON l.COD_LOTACAO = fl.COD_LOTACAO',
            $lotacao['sql']
        );
        $this->assertStringContainsString('fl.COD_FUNCIONARIO = :param_0', $lotacao['sql']);
        $this->assertStringContainsString('fl.DT_FIM_LOTACAO IS NULL', $lotacao['sql']);
        $this->assertStringContainsString('l.ATIVO = :param_1', $lotacao['sql']);
        $this->assertSame(['param_0' => 709, 'param_1' => 1], $lotacao['parametros']);
        $this->assertStringNotContainsString('PAC_UO', implode(' ', array_column($adodb->consultas, 'sql')));
        $this->assertStringNotContainsString('UASG', implode(' ', array_column($adodb->consultas, 'sql')));
    }

    public function testDeveRecusarLoginVazioSemConsultarOracle(): void
    {
        [$adapter, $adodb] = $this->criarContexto(" \t ");

        $resultado = $adapter->resolver();

        $this->assertSame(SituacaoResolucaoContextoUsuario::NAO_RESOLVIDO, $resultado->getSituacao());
        $this->assertSame('LOGIN_USUARIO_AUSENTE', $resultado->getFalha()?->getCodigo());
        $this->assertSame([], $adodb->consultas);
    }

    /** @regra RN-AUD-004 */
    public function testDeveRecusarZeroOuMultiplosRepresentantesAtivos(): void
    {
        foreach ([
            [],
            [
                ['ID_REPRESENTANTE' => 709, 'NOME_REPRESENTANTE' => 'Maria'],
                ['ID_REPRESENTANTE' => 710, 'NOME_REPRESENTANTE' => 'Maria duplicada'],
            ],
        ] as $linhas) {
            [$adapter, $adodb] = $this->criarContexto();
            $adodb->resultadosGetAll = [$linhas];

            $resultado = $adapter->resolver();

            $this->assertSame(SituacaoResolucaoContextoUsuario::NAO_RESOLVIDO, $resultado->getSituacao());
            $this->assertSame('REPRESENTANTE_USUARIO_NAO_RESOLVIDO', $resultado->getFalha()?->getCodigo());
            $this->assertCount(1, $adodb->consultas);
        }
    }

    /** @regra RN-AUD-004 */
    public function testDeveRecusarDadosInvalidosDoRepresentanteAntesDeConsultarLotacao(): void
    {
        foreach ([
            ['ID_REPRESENTANTE' => 0, 'NOME_REPRESENTANTE' => 'Maria'],
            ['ID_REPRESENTANTE' => -1, 'NOME_REPRESENTANTE' => 'Maria'],
            ['ID_REPRESENTANTE' => 1.5, 'NOME_REPRESENTANTE' => 'Maria'],
            ['ID_REPRESENTANTE' => '999999999999999999999999', 'NOME_REPRESENTANTE' => 'Maria'],
            ['ID_REPRESENTANTE' => 709, 'NOME_REPRESENTANTE' => '   '],
        ] as $linha) {
            [$adapter, $adodb] = $this->criarContexto();
            $adodb->resultadosGetAll = [[$linha]];

            $resultado = $adapter->resolver();

            $this->assertSame(SituacaoResolucaoContextoUsuario::NAO_RESOLVIDO, $resultado->getSituacao());
            $this->assertSame('DADOS_REPRESENTANTE_INVALIDOS', $resultado->getFalha()?->getCodigo());
            $this->assertCount(1, $adodb->consultas);
        }
    }

    /** @regra RN-AUD-004 */
    public function testDeveRecusarZeroOuMultiplasLotacoesVigentesAtivas(): void
    {
        foreach ([
            [],
            [
                ['ID_LOTACAO' => 159, 'NOME_LOTACAO' => 'Tecnologia'],
                ['ID_LOTACAO' => 160, 'NOME_LOTACAO' => 'Outra lotação'],
            ],
        ] as $lotacoes) {
            [$adapter, $adodb] = $this->criarContexto();
            $adodb->resultadosGetAll = [
                [['ID_REPRESENTANTE' => 709, 'NOME_REPRESENTANTE' => 'Maria']],
                $lotacoes,
            ];

            $resultado = $adapter->resolver();

            $this->assertSame(SituacaoResolucaoContextoUsuario::NAO_RESOLVIDO, $resultado->getSituacao());
            $this->assertSame('LOTACAO_USUARIO_NAO_RESOLVIDA', $resultado->getFalha()?->getCodigo());
            $this->assertCount(2, $adodb->consultas);
        }
    }

    /** @regra RN-AUD-004 */
    public function testDeveRecusarDadosInvalidosDaLotacao(): void
    {
        foreach ([
            ['ID_LOTACAO' => 0, 'NOME_LOTACAO' => 'Tecnologia'],
            ['ID_LOTACAO' => '1.5', 'NOME_LOTACAO' => 'Tecnologia'],
            ['ID_LOTACAO' => 159, 'NOME_LOTACAO' => ''],
        ] as $lotacao) {
            [$adapter, $adodb] = $this->criarContexto();
            $adodb->resultadosGetAll = [
                [['ID_REPRESENTANTE' => 709, 'NOME_REPRESENTANTE' => 'Maria']],
                [$lotacao],
            ];

            $resultado = $adapter->resolver();

            $this->assertSame(SituacaoResolucaoContextoUsuario::NAO_RESOLVIDO, $resultado->getSituacao());
            $this->assertSame('DADOS_LOTACAO_INVALIDOS', $resultado->getFalha()?->getCodigo());
        }
    }

    public function testFiltrosFisicosExcluemRepresentanteInativoEVinculoEncerradoOuLotacaoInativa(): void
    {
        [$adapter, $adodb] = $this->criarContexto();
        $adodb->resultadosGetAll = [[]];

        $resultado = $adapter->resolver();

        $this->assertSame(SituacaoResolucaoContextoUsuario::NAO_RESOLVIDO, $resultado->getSituacao());
        $this->assertStringContainsString('f.ATIVO = :param_1', $adodb->consultas[0]['sql']);

        [$adapter, $adodb] = $this->criarContexto();
        $adodb->resultadosGetAll = [
            [['ID_REPRESENTANTE' => 709, 'NOME_REPRESENTANTE' => 'Maria']],
            [],
        ];

        $resultado = $adapter->resolver();

        $this->assertSame(SituacaoResolucaoContextoUsuario::NAO_RESOLVIDO, $resultado->getSituacao());
        $this->assertStringContainsString('fl.DT_FIM_LOTACAO IS NULL', $adodb->consultas[1]['sql']);
        $this->assertStringContainsString('l.ATIVO = :param_1', $adodb->consultas[1]['sql']);
    }

    public function testDeveTraduzirFalhaOracleEmQualquerConsultaSemExporDetalhes(): void
    {
        foreach ([0, 1] as $indiceFalha) {
            [$adapter, $adodb] = $this->criarContexto();
            $adodb->resultadosGetAll = [
                [['ID_REPRESENTANTE' => 709, 'NOME_REPRESENTANTE' => 'Maria']],
                [['ID_LOTACAO' => 159, 'NOME_LOTACAO' => 'Tecnologia']],
            ];
            $adodb->errosGetAll[$indiceFalha] = 942;

            $resultado = $adapter->resolver();

            $this->assertSame(SituacaoResolucaoContextoUsuario::FALHA_SISTEMICA, $resultado->getSituacao());
            $this->assertSame('RESOLUCAO_CONTEXTO_USUARIO_FALHOU', $resultado->getFalha()?->getCodigo());
            $this->assertStringNotContainsString('ORA', $resultado->getFalha()?->getMensagem() ?? '');
            $this->assertStringNotContainsString('SENSIVEL', $resultado->getFalha()?->getMensagem() ?? '');
        }
    }

    public function testNaoDeveControlarTransacaoNemFecharSessaoEmprestada(): void
    {
        [$adapter, $adodb] = $this->criarContexto();
        $adodb->resultadosGetAll = [
            [['ID_REPRESENTANTE' => 709, 'NOME_REPRESENTANTE' => 'Maria']],
            [['ID_LOTACAO' => 159, 'NOME_LOTACAO' => 'Tecnologia']],
        ];

        $adapter->resolver();

        $this->assertSame(0, $adodb->inicios);
        $this->assertSame(0, $adodb->commits);
        $this->assertSame(0, $adodb->rollbacks);
        $this->assertFalse(method_exists($adodb, 'Close'));
    }

    /** @return array{OracleContextoUsuarioAdapter, AdodbContextoUsuarioFake} */
    private function criarContexto(string $login = 'maria.silva'): array
    {
        $adodb = new AdodbContextoUsuarioFake();
        $adapter = new OracleContextoUsuarioAdapter(
            new ConexaoAdodbEmprestada($adodb),
            new MapeamentoRepresentantesOracle(
                'SGI', 'RH_FUNCIONARIOS', 'NOME_USUARIO', 'COD_FUNCIONARIO',
                'NOME_CIVIL', 'ATIVO', 1
            ),
            new MapeamentoLotacoesUsuarioOracle(
                'SGI', 'RH_FUNCIONARIOS_LOTACOES', 'RH_LOTACOES',
                'COD_FUNCIONARIO', 'COD_LOTACAO', 'DT_FIM_LOTACAO',
                'COD_LOTACAO', 'NOME_LOTACAO', 'ATIVO', 1
            ),
            $login
        );

        return [$adapter, $adodb];
    }
}

final class AdodbContextoUsuarioFake
{
    /** @var array<int, array{sql: string, parametros: array<int|string, mixed>}> */
    public array $consultas = [];

    /** @var array<int, array<int, array<string, mixed>>> */
    public array $resultadosGetAll = [];

    /** @var array<int, int> */
    public array $errosGetAll = [];

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
                . ': tabela SGI.SENSIVEL não encontrada'
            : '';
    }
}
