<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84;

use LogicException;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Tests\Contract\Sprint84\Support\OracleCheckpointExecutor;
use Tests\Contract\Sprint84\Support\Sprint84AdodbGuard;
use Tests\Contract\Sprint84\Support\Sprint84GateException;
use Tests\Contract\Sprint84\Support\Sprint84PncpTransportGuard;

final class Sprint84GateTest extends TestCase
{
    public function testGuardaDeveRegistrarConteudoIntegralEImpedirClose(): void
    {
        $inner = new AdodbDiagnosticoSprint84();
        $guard = new Sprint84AdodbGuard($inner);

        $this->assertSame([['ID' => 7]], $guard->GetAll('SELECT ID FROM TESTE WHERE ID = ?', [7]));
        $this->assertTrue($guard->Execute('UPDATE TESTE SET VALOR = ? WHERE ID = ?', ['novo', 7]));

        try {
            $guard->Close();
            $this->fail('Close deveria ser bloqueado.');
        } catch (LogicException $failure) {
            $this->assertStringContainsString('emprestada', $failure->getMessage());
        }

        $this->assertSame(0, $inner->closes);
        $this->assertSame([
            [
                'metodo' => 'GetAll',
                'sql' => 'SELECT ID FROM TESTE WHERE ID = ?',
                'binds' => [7],
                'linhas' => [['ID' => 7]],
                'quantidade_linhas' => 1,
            ],
            [
                'metodo' => 'Execute',
                'sql' => 'UPDATE TESTE SET VALOR = ? WHERE ID = ?',
                'binds' => ['novo', 7],
                'retorno' => true,
            ],
            ['metodo' => 'Close', 'bloqueado' => true],
        ], $guard->diagnostico());
    }

    public function testCheckpointDeveConferirColunasCardinalidadeEHash(): void
    {
        $inner = new AdodbDiagnosticoSprint84();
        $guard = new Sprint84AdodbGuard($inner);
        $queries = $this->queries();

        $checkpoint = (new OracleCheckpointExecutor($guard))->execute($queries);

        $this->assertSame(
            'SELECT ID FROM TESTE WHERE ID = :s84_p0',
            $inner->consultas[0]['sql']
        );
        $this->assertSame(['s84_p0' => 7], $inner->consultas[0]['binds']);
        $this->assertSame(64, strlen($checkpoint['sha256']));
        $this->assertSame([['ID' => 7]], $checkpoint['consultas']['uma']['linhas']);
        $this->assertSame(1, $checkpoint['consultas']['uma']['cardinalidade_encontrada']);
        $this->assertSame([], $checkpoint['consultas']['vazia']['linhas']);
    }

    public function testCheckpointDeveExporErroOracleQuandoAdodbRetornaFalse(): void
    {
        try {
            (new OracleCheckpointExecutor(
                new Sprint84AdodbGuard(new AdodbFalhoSprint84())
            ))->execute($this->queries());
            $this->fail('Retorno false do ADOdb deveria interromper o gate.');
        } catch (Sprint84GateException $failure) {
            $this->assertSame('PRECHECK_CONSULTA_FALHOU', $failure->getErrorCode());
            $this->assertSame('PRECHECK_ORACLE', $failure->getPhase());
            $this->assertSame('uma', $failure->getDetails()['consulta']);
            $this->assertSame(911, $failure->getDetails()['erro_adodb']['numero']);
            $this->assertSame(
                'ORA-00911: invalid character',
                $failure->getDetails()['erro_adodb']['mensagem']
            );
            $this->assertSame(
                'SELECT ID FROM TESTE WHERE ID = :s84_p0',
                $failure->getDetails()['sql_preparado']
            );
            $this->assertSame(['s84_p0' => 7], $failure->getDetails()['binds_preparados']);
        }
    }

    public function testCheckpointDeveNormalizarWindows1252AntesDoHashEDiagnostico(): void
    {
        $inner = new AdodbDiagnosticoSprint84();
        $inner->linha = [
            0 => 7,
            'ID' => 7,
            1 => "SERVI\xC7O \x96 JUR\xCDDICO",
            'DESCRICAO' => "SERVI\xC7O \x96 JUR\xCDDICO",
        ];
        $queries = [
            'uma' => [
                'sql' => 'SELECT ID, DESCRICAO FROM TESTE WHERE ID = ?',
                'binds' => [7],
                'columns' => ['ID', 'DESCRICAO'],
                'expected_cardinality' => ['exact' => 1],
            ],
        ];

        $guard = new Sprint84AdodbGuard($inner, 'Windows-1252');
        $checkpoint = (new OracleCheckpointExecutor(
            $guard,
            'Windows-1252'
        ))->execute($queries);

        $this->assertSame(
            'SERVIÇO – JURÍDICO',
            $checkpoint['consultas']['uma']['linhas'][0]['DESCRICAO']
        );
        $this->assertSame(
            ['ID', 'DESCRICAO'],
            array_keys($checkpoint['consultas']['uma']['linhas'][0])
        );
        $this->assertTrue(mb_check_encoding(
            $checkpoint['snapshot']['resultados']['uma'][0]['DESCRICAO'],
            'UTF-8'
        ));
        $this->assertSame(64, strlen($checkpoint['sha256']));
        $getAll = array_values(array_filter(
            $guard->diagnostico(),
            static fn (array $chamada): bool => $chamada['metodo'] === 'GetAll'
        ));
        $this->assertSame(
            'SERVIÇO – JURÍDICO',
            $getAll[0]['linhas'][0]['DESCRICAO']
        );
        $this->assertArrayHasKey(0, $getAll[0]['linhas'][0]);
    }

    public function testCheckpointDeveConverterBindUtf8ParaWindows1252AntesDoAdodb(): void
    {
        $inner = new AdodbDiagnosticoSprint84();
        $guard = new Sprint84AdodbGuard($inner, 'Windows-1252');
        $queries = [
            'auditoria' => [
                'sql' => 'SELECT MENSAGEM FROM HISTORICO WHERE MENSAGEM LIKE ?',
                'binds' => ['Exercício: 2027;%'],
                'columns' => ['ID'],
                'expected_cardinality' => ['exact' => 1],
            ],
        ];

        (new OracleCheckpointExecutor($guard, 'Windows-1252'))->execute($queries);

        $this->assertSame(
            ['s84_p0' => "Exerc\xEDcio: 2027;%"],
            $inner->consultas[0]['binds']
        );
        $getAll = array_values(array_filter(
            $guard->diagnostico(),
            static fn (array $chamada): bool => $chamada['metodo'] === 'GetAll'
        ));
        $this->assertSame(
            ['s84_p0' => 'Exercício: 2027;%'],
            $getAll[0]['binds']
        );
    }

    public function testCheckpointDivergenteDeveFalharComDiagnosticoExato(): void
    {
        $queries = $this->queries();
        $queries['uma']['expected_cardinality'] = ['exact' => 2];

        try {
            (new OracleCheckpointExecutor(
                new Sprint84AdodbGuard(new AdodbDiagnosticoSprint84())
            ))->execute($queries);
            $this->fail('Cardinalidade divergente deveria interromper o gate.');
        } catch (Sprint84GateException $failure) {
            $this->assertSame('PRECHECK_CARDINALIDADE_DIVERGENTE', $failure->getErrorCode());
            $this->assertSame('PRECHECK_ORACLE', $failure->getPhase());
            $this->assertSame('uma', $failure->getDetails()['consulta']);
            $this->assertSame(2, $failure->getDetails()['esperado']['exact']);
            $this->assertSame(1, $failure->getDetails()['encontrado']);
        }
    }

    public function testGuardaPncpDeveRegistrarRequisicaoERespostaHttpIntegrais(): void
    {
        $guard = new Sprint84PncpTransportGuard(new TransportePncpDiagnosticoSprint84());
        $request = new RequisicaoHttpPncp(
            'GET',
            '/v1/orgaos/123/pca/2027/consolidado/unidades?pagina=1&tamanhoPagina=100',
            ['Authorization' => 'Bearer token-integral', 'Accept' => 'application/json'],
            null,
            30
        );

        $response = $guard->enviar($request);

        $this->assertSame(404, $response->getStatus());
        $this->assertSame([[
            'requisicao' => [
                'metodo' => 'GET',
                'caminho' => '/v1/orgaos/123/pca/2027/consolidado/unidades?pagina=1&tamanhoPagina=100',
                'headers' => [
                    'authorization' => 'Bearer token-integral',
                    'accept' => 'application/json',
                ],
                'corpo' => null,
                'timeout_segundos' => 30,
            ],
            'resposta' => [
                'status_http' => 404,
                'headers' => ['content-type' => ['application/json']],
                'corpo' => '{"mensagem":"PCA não encontrado"}',
            ],
        ]], $guard->diagnostico());
    }

    public function testGuardaPncpDevePreservarRequisicaoQuandoTransporteFalhar(): void
    {
        $guard = new Sprint84PncpTransportGuard(new TransportePncpFalhoSprint84());
        $request = new RequisicaoHttpPncp(
            'POST',
            '/v1/orgaos/123/pca',
            ['Authorization' => 'Bearer token-integral', 'Content-Type' => 'application/json'],
            '{"codigoUnidade":"204","itensPlanejamento":[{"numeroItem":800}]}',
            60
        );

        try {
            $guard->enviar($request);
            $this->fail('Falha do transporte deveria ser propagada.');
        } catch (\RuntimeException $failure) {
            $this->assertSame('Falha controlada de escrita.', $failure->getMessage());
        }

        $diagnostico = $guard->diagnostico();
        $this->assertCount(1, $diagnostico);
        $this->assertSame('POST', $diagnostico[0]['requisicao']['metodo']);
        $this->assertSame('/v1/orgaos/123/pca', $diagnostico[0]['requisicao']['caminho']);
        $this->assertStringContainsString('"numeroItem":800', $diagnostico[0]['requisicao']['corpo']);
        $this->assertSame(\RuntimeException::class, $diagnostico[0]['erro']['classe']);
        $this->assertSame('Falha controlada de escrita.', $diagnostico[0]['erro']['mensagem']);
    }

    /** @return array<string, array<string, mixed>> */
    private function queries(): array
    {
        return [
            'uma' => [
                'sql' => 'SELECT ID FROM TESTE WHERE ID = ?',
                'binds' => [7],
                'columns' => ['ID'],
                'expected_cardinality' => ['exact' => 1],
            ],
            'vazia' => [
                'sql' => 'SELECT ID FROM VAZIA ORDER BY ID',
                'binds' => [],
                'columns' => ['ID'],
                'expected_cardinality' => ['min' => 0],
            ],
        ];
    }
}

final class AdodbDiagnosticoSprint84
{
    public int $closes = 0;

    /** @var array<int, array{sql: string, binds: array<int|string, mixed>}> */
    public array $consultas = [];

    /** @var array<string, mixed> */
    public array $linha = ['ID' => 7];

    /** @param array<int|string, mixed> $binds */
    public function GetAll(string $sql, array $binds = []): array
    {
        $this->consultas[] = ['sql' => $sql, 'binds' => $binds];

        return str_contains($sql, 'VAZIA') ? [] : [$this->linha];
    }

    /** @param array<int|string, mixed> $binds */
    public function Execute(string $sql, array $binds = []): bool
    {
        return true;
    }

    public function BeginTrans(): bool
    {
        return true;
    }

    public function CommitTrans(): bool
    {
        return true;
    }

    public function RollbackTrans(): bool
    {
        return true;
    }

    public function Param(string|false $name): string
    {
        return ':' . ($name === false ? 'param' : $name);
    }

    public function ErrorNo(): int
    {
        return 0;
    }

    public function ErrorMsg(): string
    {
        return '';
    }

    public function Close(): bool
    {
        $this->closes++;

        return true;
    }
}

final class TransportePncpDiagnosticoSprint84 implements TransporteHttpPncp
{
    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        return new RespostaHttpPncp(
            404,
            ['content-type' => ['application/json']],
            '{"mensagem":"PCA não encontrado"}'
        );
    }
}

final class TransportePncpFalhoSprint84 implements TransporteHttpPncp
{
    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        throw new \RuntimeException('Falha controlada de escrita.');
    }
}

final class AdodbFalhoSprint84
{
    /** @param array<int|string, mixed> $binds */
    public function GetAll(string $sql, array $binds = []): false
    {
        return false;
    }

    public function Param(string|false $name): string
    {
        return ':' . ($name === false ? 'param' : $name);
    }

    public function ErrorNo(): int
    {
        return 911;
    }

    public function ErrorMsg(): string
    {
        return 'ORA-00911: invalid character';
    }
}
