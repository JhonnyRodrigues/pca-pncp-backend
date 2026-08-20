<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Oracle\Estrategia;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\ModoPublicacao;
use Semae\Pca\Application\Enums\SituacaoConsultaEstrategia;
use Semae\Pca\Application\Enums\SituacaoInicioUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\ChaveUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\EstrategiaPublicacao;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Oracle\Estrategia\MapeamentoCadastroUasgOracle;
use Semae\Pca\Infrastructure\Oracle\Estrategia\MapeamentoConfiguracaoEstrategiaOracle;
use Semae\Pca\Infrastructure\Oracle\Estrategia\MapeamentoPlanosEstrategiaOracle;
use Semae\Pca\Infrastructure\Oracle\Estrategia\OracleEstrategiaPublicacaoAdapter;
use Semae\Pca\Infrastructure\Oracle\Transacao\MapeamentoFisicoAncorasOracle;
use Semae\Pca\Infrastructure\Oracle\Transacao\OracleUnidadeDeTrabalhoAdapter;

final class OracleEstrategiaPublicacaoAdapterTest extends TestCase
{
    public function testDeveConsultarEstrategiaCorrenteIndividualSemDefault(): void
    {
        [$adapter, , $adodb] = $this->criarContexto();
        $adodb->resultadosGetAll = [[
            ['CHAVE_CONFIGURACAO' => 'PUBLICAR_EM_UNICA_UASG', 'VALOR_CONFIGURACAO' => 'N'],
        ]];

        $resultado = $adapter->consultarCorrente($this->ano());

        $this->assertSame(SituacaoConsultaEstrategia::DISPONIVEL, $resultado->getSituacao());
        $this->assertSame(ModoPublicacao::INDIVIDUAL, $resultado->getEstrategia()?->getModo());
        $this->assertCount(1, $adodb->consultas);
        $this->assertSame(
            ['PUBLICAR_EM_UNICA_UASG', 'UASG_CONSOLIDADORA'],
            array_values($adodb->consultas[0]['parametros'])
        );
    }

    public function testDeveConsultarEstrategiaCorrenteConsolidadaComUasgAtivaUnica(): void
    {
        [$adapter, , $adodb] = $this->criarContexto();
        $adodb->resultadosGetAll = [
            [
                ['CHAVE_CONFIGURACAO' => 'PUBLICAR_EM_UNICA_UASG', 'VALOR_CONFIGURACAO' => 'S'],
                ['CHAVE_CONFIGURACAO' => 'UASG_CONSOLIDADORA', 'VALOR_CONFIGURACAO' => '986700'],
            ],
            [['CODIGO_UASG' => '986700', 'DESCRICAO_UASG' => ' UASG Central ']],
        ];

        $resultado = $adapter->consultarCorrente($this->ano());

        $this->assertSame(SituacaoConsultaEstrategia::DISPONIVEL, $resultado->getSituacao());
        $this->assertSame('986700', $resultado->getEstrategia()?->getUasgConsolidadora()?->getCodigo());
        $this->assertSame(
            'UASG Central',
            $resultado->getEstrategia()?->getUasgConsolidadora()?->getDescricao()
        );
        $this->assertStringContainsString('APP.PAC_UO', $adodb->consultas[1]['sql']);
        $this->assertStringContainsString('CODIGO_UASG =', $adodb->consultas[1]['sql']);
        $this->assertStringContainsString('ATIVA =', $adodb->consultas[1]['sql']);
        $this->assertSame(['986700', 'S'], array_values($adodb->consultas[1]['parametros']));
    }

    public function testDeveDistinguirConfiguracaoAusenteDeConfiguracaoInvalida(): void
    {
        [$adapterAusente, , $adodbAusente] = $this->criarContexto();
        $adodbAusente->resultadosGetAll = [[]];

        $ausente = $adapterAusente->consultarCorrente($this->ano());

        $this->assertSame(SituacaoConsultaEstrategia::AUSENTE, $ausente->getSituacao());

        foreach (
            [
                [
                    ['CHAVE_CONFIGURACAO' => 'PUBLICAR_EM_UNICA_UASG', 'VALOR_CONFIGURACAO' => 'X'],
                ],
                [
                    ['CHAVE_CONFIGURACAO' => 'PUBLICAR_EM_UNICA_UASG', 'VALOR_CONFIGURACAO' => 'N'],
                    ['CHAVE_CONFIGURACAO' => 'PUBLICAR_EM_UNICA_UASG', 'VALOR_CONFIGURACAO' => 'N'],
                ],
                [
                    ['CHAVE_CONFIGURACAO' => 'PUBLICAR_EM_UNICA_UASG', 'VALOR_CONFIGURACAO' => 'S'],
                ],
            ] as $linhas
        ) {
            [$adapter, , $adodb] = $this->criarContexto();
            $adodb->resultadosGetAll = [$linhas];

            $invalida = $adapter->consultarCorrente($this->ano());

            $this->assertSame(SituacaoConsultaEstrategia::INVALIDA, $invalida->getSituacao());
            $this->assertSame('ESTRATEGIA_CORRENTE_INVALIDA', $invalida->getFalha()?->getCodigo());
        }
    }

    public function testDeveInvalidarUasgConsolidadoraAusenteDuplicadaOuSemDescricao(): void
    {
        foreach (
            [
                [],
                [
                    ['CODIGO_UASG' => '986700', 'DESCRICAO_UASG' => 'Central'],
                    ['CODIGO_UASG' => '986700', 'DESCRICAO_UASG' => 'Duplicada'],
                ],
                [['CODIGO_UASG' => '986700', 'DESCRICAO_UASG' => ' ']],
            ] as $lookup
        ) {
            [$adapter, , $adodb] = $this->criarContexto();
            $adodb->resultadosGetAll = [
                [
                    ['CHAVE_CONFIGURACAO' => 'PUBLICAR_EM_UNICA_UASG', 'VALOR_CONFIGURACAO' => 'S'],
                    ['CHAVE_CONFIGURACAO' => 'UASG_CONSOLIDADORA', 'VALOR_CONFIGURACAO' => '986700'],
                ],
                $lookup,
            ];

            $resultado = $adapter->consultarCorrente($this->ano());

            $this->assertSame(SituacaoConsultaEstrategia::INVALIDA, $resultado->getSituacao());
            $this->assertSame('UASG_CONSOLIDADORA_INVALIDA', $resultado->getFalha()?->getCodigo());
        }
    }

    public function testDeveTiparFalhaOracleAoConsultarEstrategiaCorrente(): void
    {
        [$adapter, , $adodb] = $this->criarContexto();
        $adodb->errosGetAll[0] = 942;

        $resultado = $adapter->consultarCorrente($this->ano());

        $this->assertSame(SituacaoConsultaEstrategia::FALHA_SISTEMICA, $resultado->getSituacao());
        $this->assertSame('CONSULTA_ESTRATEGIA_CORRENTE_FALHOU', $resultado->getFalha()?->getCodigo());
        $this->assertStringNotContainsString('ORA', $resultado->getFalha()?->getMensagem() ?? '');
    }

    public function testDeveConsultarEstrategiaAdotadaIndividualCoerenteEntreVersoes(): void
    {
        [$adapter, , $adodb] = $this->criarContexto();
        $adodb->resultadosGetAll = [[
            ['UNIDADE_ORCAMENTARIA' => '10', 'MODO_ADOTADO' => 'I', 'UASG_ADOTADA' => '986701'],
            ['UNIDADE_ORCAMENTARIA' => '10', 'MODO_ADOTADO' => 'I', 'UASG_ADOTADA' => '986701'],
            ['UNIDADE_ORCAMENTARIA' => '20', 'MODO_ADOTADO' => 'I', 'UASG_ADOTADA' => '986702'],
        ]];

        $resultado = $adapter->consultarAdotada($this->ano());

        $this->assertSame(SituacaoConsultaEstrategia::DISPONIVEL, $resultado->getSituacao());
        $this->assertSame(ModoPublicacao::INDIVIDUAL, $resultado->getEstrategia()?->getModo());
    }

    public function testDeveConsultarEstrategiaAdotadaConsolidadaEResolverDescricao(): void
    {
        [$adapter, , $adodb] = $this->criarContexto();
        $adodb->resultadosGetAll = [
            [
                ['UNIDADE_ORCAMENTARIA' => '10', 'MODO_ADOTADO' => 'C', 'UASG_ADOTADA' => '986700'],
                ['UNIDADE_ORCAMENTARIA' => '20', 'MODO_ADOTADO' => 'C', 'UASG_ADOTADA' => '986700'],
            ],
            [['CODIGO_UASG' => '986700', 'DESCRICAO_UASG' => 'Central']],
        ];

        $resultado = $adapter->consultarAdotada($this->ano());

        $this->assertSame(SituacaoConsultaEstrategia::DISPONIVEL, $resultado->getSituacao());
        $this->assertSame('986700', $resultado->getEstrategia()?->getUasgConsolidadora()?->getCodigo());
    }

    public function testDeveDistinguirAdocaoAusenteDeEstadoParcialOuConflitante(): void
    {
        [$adapterAusente, , $adodbAusente] = $this->criarContexto();
        $adodbAusente->resultadosGetAll = [[
            ['UNIDADE_ORCAMENTARIA' => '10', 'MODO_ADOTADO' => null, 'UASG_ADOTADA' => null],
        ]];

        $this->assertSame(
            SituacaoConsultaEstrategia::AUSENTE,
            $adapterAusente->consultarAdotada($this->ano())->getSituacao()
        );

        $cenariosInvalidos = [
            [
                ['UNIDADE_ORCAMENTARIA' => '10', 'MODO_ADOTADO' => null, 'UASG_ADOTADA' => null],
                ['UNIDADE_ORCAMENTARIA' => '20', 'MODO_ADOTADO' => 'I', 'UASG_ADOTADA' => '986702'],
            ],
            [
                ['UNIDADE_ORCAMENTARIA' => '10', 'MODO_ADOTADO' => 'I', 'UASG_ADOTADA' => null],
            ],
            [
                ['UNIDADE_ORCAMENTARIA' => '10', 'MODO_ADOTADO' => 'I', 'UASG_ADOTADA' => '986701'],
                ['UNIDADE_ORCAMENTARIA' => '20', 'MODO_ADOTADO' => 'C', 'UASG_ADOTADA' => '986700'],
            ],
            [
                ['UNIDADE_ORCAMENTARIA' => '10', 'MODO_ADOTADO' => 'I', 'UASG_ADOTADA' => '986701'],
                ['UNIDADE_ORCAMENTARIA' => '20', 'MODO_ADOTADO' => 'I', 'UASG_ADOTADA' => '986701'],
            ],
        ];

        foreach ($cenariosInvalidos as $linhas) {
            [$adapter, , $adodb] = $this->criarContexto();
            $adodb->resultadosGetAll = [$linhas];

            $resultado = $adapter->consultarAdotada($this->ano());

            $this->assertSame(SituacaoConsultaEstrategia::INVALIDA, $resultado->getSituacao());
            $this->assertSame('ESTRATEGIA_ADOTADA_INVALIDA', $resultado->getFalha()?->getCodigo());
        }
    }

    public function testDeveExigirTransacaoAtivaParaAdotar(): void
    {
        [$adapter, , $adodb] = $this->criarContexto();

        $resultado = $adapter->adotar($this->ano(), EstrategiaPublicacao::individual());

        $this->assertFalse($resultado->foiAdotada());
        $this->assertSame('ADOCAO_ESTRATEGIA_EXIGE_TRANSACAO', $resultado->getFalha()?->getCodigo());
        $this->assertSame([], $adodb->consultas);
        $this->assertSame([], $adodb->execucoes);
    }

    public function testDeveAdotarModoIndividualPorUoSemSobrescreverValoresExistentes(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $conexao->iniciarTransacao();
        $adodb->resultadosGetAll = [
            [
                ['UNIDADE_ORCAMENTARIA' => '10', 'MODO_ADOTADO' => null, 'UASG_ADOTADA' => null],
                ['UNIDADE_ORCAMENTARIA' => '20', 'MODO_ADOTADO' => null, 'UASG_ADOTADA' => null],
            ],
            [
                ['UNIDADE_ORCAMENTARIA' => '10', 'CODIGO_UASG' => '986701', 'DESCRICAO_UASG' => 'UASG A'],
                ['UNIDADE_ORCAMENTARIA' => '20', 'CODIGO_UASG' => '986702', 'DESCRICAO_UASG' => 'UASG B'],
            ],
            [
                ['UNIDADE_ORCAMENTARIA' => '10', 'MODO_ADOTADO' => 'I', 'UASG_ADOTADA' => '986701'],
                ['UNIDADE_ORCAMENTARIA' => '20', 'MODO_ADOTADO' => 'I', 'UASG_ADOTADA' => '986702'],
            ],
        ];

        $resultado = $adapter->adotar($this->ano(), EstrategiaPublicacao::individual());

        $this->assertTrue($resultado->foiAdotada());
        $this->assertTrue($conexao->estaEmTransacao());
        $this->assertCount(2, $adodb->execucoes);
        foreach ($adodb->execucoes as $execucao) {
            $this->assertStringContainsString('MODO_PUBLICACAO IS NULL', $execucao['sql']);
            $this->assertStringContainsString('UASG_PUBLICACAO IS NULL', $execucao['sql']);
        }
        $this->assertSame(
            ['I', '986701', $this->ano()->getValor(), '10'],
            array_values($adodb->execucoes[0]['parametros'])
        );
        $this->assertSame(
            ['I', '986702', $this->ano()->getValor(), '20'],
            array_values($adodb->execucoes[1]['parametros'])
        );
        $this->assertSame(0, $adodb->commits);
        $conexao->desfazerTransacao();
    }

    public function testDeveAdotarModoConsolidadoComUmaUasgParaTodasAsLinhas(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $conexao->iniciarTransacao();
        $adodb->resultadosGetAll = [
            [['UNIDADE_ORCAMENTARIA' => '10', 'MODO_ADOTADO' => null, 'UASG_ADOTADA' => null]],
            [['CODIGO_UASG' => '986700', 'DESCRICAO_UASG' => 'Central']],
            [['UNIDADE_ORCAMENTARIA' => '10', 'MODO_ADOTADO' => 'C', 'UASG_ADOTADA' => '986700']],
            [['CODIGO_UASG' => '986700', 'DESCRICAO_UASG' => 'Central']],
        ];

        $resultado = $adapter->adotar(
            $this->ano(),
            EstrategiaPublicacao::consolidada(new Uasg('986700', 'Central'))
        );

        $this->assertTrue($resultado->foiAdotada());
        $this->assertCount(1, $adodb->execucoes);
        $this->assertSame(
            ['C', '986700', $this->ano()->getValor()],
            array_values($adodb->execucoes[0]['parametros'])
        );
        $this->assertTrue($conexao->estaEmTransacao());
        $conexao->desfazerTransacao();
    }

    public function testAdocaoJaCoerenteDeveSerIdempotente(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $conexao->iniciarTransacao();
        $adodb->resultadosGetAll = [[
            ['UNIDADE_ORCAMENTARIA' => '10', 'MODO_ADOTADO' => 'I', 'UASG_ADOTADA' => '986701'],
        ]];

        $resultado = $adapter->adotar($this->ano(), EstrategiaPublicacao::individual());

        $this->assertTrue($resultado->foiAdotada());
        $this->assertSame([], $adodb->execucoes);
        $this->assertTrue($conexao->estaEmTransacao());
        $conexao->desfazerTransacao();
    }

    public function testDeveRecusarEstrategiaDiferenteDaJaAdotada(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $conexao->iniciarTransacao();
        $adodb->resultadosGetAll = [[
            ['UNIDADE_ORCAMENTARIA' => '10', 'MODO_ADOTADO' => 'I', 'UASG_ADOTADA' => '986701'],
        ]];

        $resultado = $adapter->adotar(
            $this->ano(),
            EstrategiaPublicacao::consolidada(new Uasg('986700', 'Central'))
        );

        $this->assertFalse($resultado->foiAdotada());
        $this->assertSame('ESTRATEGIA_ADOTADA_DIVERGENTE', $resultado->getFalha()?->getCodigo());
        $this->assertSame([], $adodb->execucoes);
        $conexao->desfazerTransacao();
    }

    public function testDeveFalharQuandoReleituraNaoConfirmarAAdocao(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $conexao->iniciarTransacao();
        $adodb->resultadosGetAll = [
            [['UNIDADE_ORCAMENTARIA' => '10', 'MODO_ADOTADO' => null, 'UASG_ADOTADA' => null]],
            [['UNIDADE_ORCAMENTARIA' => '10', 'CODIGO_UASG' => '986701', 'DESCRICAO_UASG' => 'UASG A']],
            [['UNIDADE_ORCAMENTARIA' => '10', 'MODO_ADOTADO' => null, 'UASG_ADOTADA' => null]],
        ];

        $resultado = $adapter->adotar($this->ano(), EstrategiaPublicacao::individual());

        $this->assertFalse($resultado->foiAdotada());
        $this->assertSame('ADOCAO_ESTRATEGIA_NAO_CONFIRMADA', $resultado->getFalha()?->getCodigo());
        $this->assertTrue($conexao->estaEmTransacao());
        $conexao->desfazerTransacao();
    }

    public function testDeveTiparFalhaOracleSemControlarATransacaoDaApplication(): void
    {
        [$adapter, $conexao, $adodb] = $this->criarContexto();
        $conexao->iniciarTransacao();
        $adodb->resultadosGetAll = [
            [['UNIDADE_ORCAMENTARIA' => '10', 'MODO_ADOTADO' => null, 'UASG_ADOTADA' => null]],
            [['UNIDADE_ORCAMENTARIA' => '10', 'CODIGO_UASG' => '986701', 'DESCRICAO_UASG' => 'UASG A']],
        ];
        $adodb->errosExecute[0] = 3113;

        $resultado = $adapter->adotar($this->ano(), EstrategiaPublicacao::individual());

        $this->assertFalse($resultado->foiAdotada());
        $this->assertSame('ADOCAO_ESTRATEGIA_FALHOU', $resultado->getFalha()?->getCodigo());
        $this->assertTrue($conexao->estaEmTransacao());
        $this->assertSame(0, $adodb->commits);
        $this->assertSame(0, $adodb->rollbacks);
        $conexao->desfazerTransacao();
    }

    public function testDeveCompartilharAMesmaSessaoComAUnidadeDeTrabalho(): void
    {
        [$estrategia, $conexao, $adodb] = $this->criarContexto();
        $unidadeTrabalho = new OracleUnidadeDeTrabalhoAdapter(
            $conexao,
            new MapeamentoFisicoAncorasOracle(
                'APP', 'PAC_ANCORAS', 'ESCOPO', 'ANO', 'ALVO', 'ESTRATEGIA_GLOBAL'
            )
        );
        $adodb->resultadosGetAll = [
            [['ESCOPO' => 'LOCK']],
            [['UNIDADE_ORCAMENTARIA' => '10', 'MODO_ADOTADO' => null, 'UASG_ADOTADA' => null]],
            [['UNIDADE_ORCAMENTARIA' => '10', 'CODIGO_UASG' => '986701', 'DESCRICAO_UASG' => 'UASG A']],
            [['UNIDADE_ORCAMENTARIA' => '10', 'MODO_ADOTADO' => 'I', 'UASG_ADOTADA' => '986701']],
        ];
        $ano = $this->ano();

        $inicio = $unidadeTrabalho->iniciar(ChaveUnidadeTrabalho::estrategiaAnual($ano));
        $adocao = $estrategia->adotar($ano, EstrategiaPublicacao::individual());

        $this->assertSame(SituacaoInicioUnidadeTrabalho::INICIADA, $inicio->getSituacao());
        $this->assertTrue($adocao->foiAdotada());
        $this->assertTrue($conexao->estaEmTransacao());
        $this->assertSame(2, $adodb->inicios);
        $this->assertSame(1, $adodb->commits);
        $this->assertSame(0, $adodb->rollbacks);
        $unidadeTrabalho->desfazer();
    }

    /** @return array{OracleEstrategiaPublicacaoAdapter, ConexaoAdodbEmprestada, AdodbEstrategiaFake} */
    private function criarContexto(): array
    {
        $adodb = new AdodbEstrategiaFake();
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $adapter = new OracleEstrategiaPublicacaoAdapter(
            $conexao,
            new MapeamentoConfiguracaoEstrategiaOracle(
                'APP', 'PAC_CONFIG', 'CHAVE', 'VALOR',
                'PUBLICAR_EM_UNICA_UASG', 'UASG_CONSOLIDADORA', 'N', 'S'
            ),
            new MapeamentoPlanosEstrategiaOracle(
                'APP', 'PAC_PLANOS', 'ANO', 'UO', 'MODO_PUBLICACAO',
                'UASG_PUBLICACAO', 'I', 'C'
            ),
            new MapeamentoCadastroUasgOracle(
                'APP', 'PAC_UO', 'ID_UO', 'CODIGO_UASG', 'DESCRICAO', 'ATIVA', 'S'
            )
        );

        return [$adapter, $conexao, $adodb];
    }

    private function ano(): AnoExecucao
    {
        return new AnoExecucao((int) date('Y') + 1);
    }
}

final class AdodbEstrategiaFake
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
