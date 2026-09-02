<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint82;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Composition\ComposicaoOraclePublicacaoPca;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Tests\Contract\Support\BorrowedAdodbGuard;
use Tests\Contract\Support\OracleAdaptersExercise;
use Tests\Contract\Support\OracleAdaptersSet;
use Tests\Support\PerfilPublicacaoPcaFixture;

final class OracleAdaptersExerciseTest extends TestCase
{
    public function testCommitDeveDistinguirProvisionamentoDaTransacaoControlada(): void
    {
        $adodb = new AdodbCommitControladoSprint82();
        $guard = new BorrowedAdodbGuard($adodb);
        $conexao = new ConexaoAdodbEmprestada($guard);
        $perfil = new PerfilPublicacaoPca(PerfilPublicacaoPcaFixture::valido());
        $oracle = new ComposicaoOraclePublicacaoPca(
            $conexao,
            $perfil,
            'usuario.teste'
        );
        $exercise = new OracleAdaptersExercise(
            $guard,
            $conexao,
            OracleAdaptersSet::fromProductionComposition($oracle),
            $perfil->getHistoricos(),
            $this->massa(),
            'SPRINT_82_BIND'
        );

        $checks = $exercise->run('commit');

        $this->assertSame([
            'transacao_tecnica_iniciada' => true,
            'provisionamento_idempotente_executado' => true,
            'commit_tecnico_provisionamento' => true,
            'linha_controle_disponivel_apos_provisionamento' => true,
            'transacao_controlada_iniciada' => true,
            'lock_controlado_adquirido' => true,
            'mesma_sessao' => true,
            'commit_controlado_concluido' => true,
            'lock_liberado_apos_commit' => true,
            'conexao_reutilizavel' => true,
            'alteracao_negocio_executada' => false,
            'close_invocado' => false,
        ], $checks);
        $this->assertSame(3, $guard->countCalls('CommitTrans'));
        $this->assertSame(1, $guard->countCalls('RollbackTrans'));
        $this->assertFalse($guard->wasCloseCalled());
    }

    /** @return array<string, mixed> */
    private function massa(): array
    {
        return [
            'ano' => (int) date('Y') + 1,
            'id_unidade_orcamentaria' => 10,
            'sequencial_teste' => 82,
            'mensagem_auditoria' => 'PCA-S82-INTEGRACAO-ORACLE',
            'lock_hold_seconds' => 1,
        ];
    }
}

final class AdodbCommitControladoSprint82
{
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

    /** @param array<string, mixed> $parametros */
    public function Execute(string $sql, array $parametros = []): bool
    {
        return true;
    }

    /** @param array<string, mixed> $parametros @return array<int, array<string, mixed>> */
    public function GetAll(string $sql, array $parametros = []): array
    {
        if (str_contains($sql, "SYS_CONTEXT('USERENV', 'SID')")) {
            return [['SESSAO' => '82']];
        }

        if (str_contains($sql, 'FOR UPDATE NOWAIT')) {
            return [['ESCOPO' => 'ESTRATEGIA_ANUAL']];
        }

        return [];
    }

    public function Param(string|false $nome): string
    {
        return ':' . ($nome === false ? 'param' : $nome);
    }

    public function ErrorNo(): int
    {
        return 0;
    }

    public function ErrorMsg(): string
    {
        return '';
    }
}
