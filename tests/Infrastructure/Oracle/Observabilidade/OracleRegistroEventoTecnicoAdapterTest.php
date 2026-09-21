<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Oracle\Observabilidade;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Oracle\Observabilidade\OracleRegistroEventoTecnicoAdapter;

final class OracleRegistroEventoTecnicoAdapterTest extends TestCase
{
    public function testDeveConfirmarEventoIntegralEmTransacaoTecnicaPropria(): void
    {
        $adodb = new AdodbLogTecnicoFake();
        $adodb->resultadoConsulta = [['ID' => '7756000']];
        $adapter = new OracleRegistroEventoTecnicoAdapter(
            new ConexaoAdodbEmprestada($adodb, 'Windows-1252')
        );

        $adapter->registrar(
            $this->evento(str_repeat('Publicação com acentuação. ', 180)),
            'jsilva',
            'PCA_PUBLICAR_B'
        );

        $this->assertSame(1, $adodb->inicios);
        $this->assertSame(1, $adodb->commits);
        $this->assertSame(0, $adodb->rollbacks);
        $this->assertCount(1, $adodb->atualizacoesClob);

        $json = $adodb->atualizacoesClob[0]['conteudo'];
        $this->assertGreaterThan(4000, strlen($json));
        $this->assertStringContainsString('\\u00e7', $json);
        $this->assertStringContainsString('"error":{}', $json);
        $this->assertStringContainsString('"context":{}', $json);
        $this->assertSame('ID = 7756000', $adodb->atualizacoesClob[0]['criterio']);
        $this->assertSame('execute', $adodb->execucoes[0]['parametros']['param_3']);
        $this->assertStringStartsWith(
            'PCA_LOG_PENDENTE_',
            $adodb->execucoes[0]['parametros']['param_4']
        );
    }

    public function testDeveDesfazerSomenteATransacaoTecnicaQuandoClobFalhar(): void
    {
        $adodb = new AdodbLogTecnicoFake();
        $adodb->resultadoConsulta = [['ID' => 7756001]];
        $adodb->resultadoUpdateClob = false;
        $adapter = new OracleRegistroEventoTecnicoAdapter(
            new ConexaoAdodbEmprestada($adodb)
        );

        try {
            $adapter->registrar($this->evento('falha controlada'), 'jsilva', 'PCA_PUBLICAR_B');
            $this->fail('Era esperada falha na gravação do CLOB.');
        } catch (\Throwable) {
            $this->assertSame(1, $adodb->inicios);
            $this->assertSame(0, $adodb->commits);
            $this->assertSame(1, $adodb->rollbacks);
        }
    }

    public function testDeveDesfazerQuandoMarcadorNaoLocalizarUmaUnicaLinha(): void
    {
        $adodb = new AdodbLogTecnicoFake();
        $adodb->resultadoConsulta = [];
        $adapter = new OracleRegistroEventoTecnicoAdapter(
            new ConexaoAdodbEmprestada($adodb)
        );

        try {
            $adapter->registrar($this->evento('cardinalidade'), 'jsilva', 'PCA_PUBLICAR_B');
            $this->fail('Era esperada falha de cardinalidade.');
        } catch (\Throwable) {
            $this->assertSame(1, $adodb->rollbacks);
            $this->assertSame([], $adodb->atualizacoesClob);
        }
    }

    /** @return array<string, mixed> */
    private function evento(string $mensagem): array
    {
        return [
            'timestamp' => '2026-09-20T18:00:00-03:00',
            'level' => 'INFO',
            'correlationId' => '11111111-1111-4111-8111-111111111111',
            'environment' => 'HOMOLOGACAO',
            'system' => 'pca-pncp-backend',
            'module' => 'publicacao-pca',
            'action' => 'PUBLICAR_PCA',
            'message' => $mensagem,
            'error' => [],
            'context' => [],
            'metadata' => [],
            'ipAddress' => null,
        ];
    }
}

final class AdodbLogTecnicoFake
{
    public int $inicios = 0;
    public int $commits = 0;
    public int $rollbacks = 0;

    /** @var array<int, array{sql: string, parametros: array<string, mixed>}> */
    public array $execucoes = [];

    /** @var array<int, array{tabela: string, coluna: string, conteudo: string, criterio: string}> */
    public array $atualizacoesClob = [];

    /** @var array<int, array<string, mixed>> */
    public array $resultadoConsulta = [];

    public bool $resultadoUpdateClob = true;

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

    /** @param array<string, mixed> $parametros */
    public function Execute(string $sql, array $parametros = []): bool
    {
        $this->execucoes[] = compact('sql', 'parametros');

        return true;
    }

    /** @param array<string, mixed> $parametros @return array<int, array<string, mixed>> */
    public function GetAll(string $sql, array $parametros = []): array
    {
        return $this->resultadoConsulta;
    }

    public function UpdateClob(
        string $tabela,
        string $coluna,
        string $conteudo,
        string $criterio
    ): bool {
        $this->atualizacoesClob[] = compact('tabela', 'coluna', 'conteudo', 'criterio');

        return $this->resultadoUpdateClob;
    }

    public function Param(false|string $nome): string
    {
        return $nome === false ? '' : ':' . $nome;
    }

    public function ErrorNo(): int
    {
        return $this->resultadoUpdateClob ? 0 : 1704;
    }

    public function ErrorMsg(): string
    {
        return $this->resultadoUpdateClob ? '' : 'ORA-01704';
    }
}
