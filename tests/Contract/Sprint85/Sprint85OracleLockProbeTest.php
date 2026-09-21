<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint85;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Tests\Contract\Sprint84\Support\Sprint84GateException;
use Tests\Contract\Sprint85\Support\Sprint85OracleLockProbe;
use Tests\Support\PerfilPublicacaoPcaFixture;

final class Sprint85OracleLockProbeTest extends TestCase
{
    public function testDeveConfirmarLockExternoSomenteAoReceberOra00054(): void
    {
        $db = new AdodbLockSprint85(false, 54, 'ORA-00054: resource busy');
        $probe = new Sprint85OracleLockProbe(
            $db,
            new PerfilPublicacaoPca(PerfilPublicacaoPcaFixture::valido())
        );

        $probe->exigirBloqueio(2026, '930281');

        $this->assertSame(1, $db->begins);
        $this->assertSame(1, $db->rollbacks);
        $this->assertStringContainsString('FOR UPDATE NOWAIT', $db->sql);
        $this->assertSame(['UNIDADE_EXTERNA', 2026, '930281'], array_values($db->binds));
    }

    public function testDeveRecusarContencaoQuandoAncoraEstiverLivre(): void
    {
        $db = new AdodbLockSprint85([['ESCOPO' => 'UNIDADE_EXTERNA']]);
        $probe = new Sprint85OracleLockProbe(
            $db,
            new PerfilPublicacaoPca(PerfilPublicacaoPcaFixture::valido())
        );

        $this->expectException(Sprint84GateException::class);
        $this->expectExceptionMessage('nao esta retida');

        try {
            $probe->exigirBloqueio(2026, '930281');
        } finally {
            $this->assertSame(1, $db->rollbacks);
        }
    }

    public function testDeveConfirmarDisponibilidadeELiberarSeuProprioLock(): void
    {
        $db = new AdodbLockSprint85([['ESCOPO' => 'UNIDADE_EXTERNA']]);
        $probe = new Sprint85OracleLockProbe(
            $db,
            new PerfilPublicacaoPca(PerfilPublicacaoPcaFixture::valido())
        );

        $probe->exigirDisponibilidade(2026, '930281');

        $this->assertSame(1, $db->begins);
        $this->assertSame(1, $db->rollbacks);
    }
}

final class AdodbLockSprint85
{
    public int $begins = 0;
    public int $rollbacks = 0;
    public string $sql = '';

    /** @var array<int|string, mixed> */
    public array $binds = [];

    /** @param array<int, array<string, mixed>>|false $response */
    public function __construct(
        private readonly array|false $response,
        private readonly int $errorNo = 0,
        private readonly string $errorMsg = ''
    ) {}

    public function BeginTrans(): bool
    {
        $this->begins++;
        return true;
    }

    public function CommitTrans(): bool
    {
        return true;
    }

    public function RollbackTrans(): bool
    {
        $this->rollbacks++;
        return true;
    }

    /** @param array<int|string, mixed> $binds */
    public function Execute(string $sql, array $binds = []): bool
    {
        return true;
    }

    /** @param array<int|string, mixed> $binds @return array<int, array<string, mixed>>|false */
    public function GetAll(string $sql, array $binds = []): array|false
    {
        $this->sql = $sql;
        $this->binds = $binds;
        return $this->response;
    }

    public function Param(string|false $name): string
    {
        return ':' . ($name === false ? 'probe' : $name);
    }

    public function ErrorNo(): int
    {
        return $this->errorNo;
    }

    public function ErrorMsg(): string
    {
        return $this->errorMsg;
    }
}
