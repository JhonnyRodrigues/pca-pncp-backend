<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint85;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;
use Semae\Pca\Presentation\ViewModels\ResultadoUnidadeViewModel;
use Semae\Pca\ResultadoPublicacaoPcaScriptcase;
use Tests\Contract\Sprint84\Support\Sprint84GateException;
use Tests\Contract\Sprint85\Support\Sprint85ContainmentGate;

final class Sprint85ContainmentGateTest extends TestCase
{
    private const REQUEST_ID = '123e4567-e89b-42d3-a456-426614174000';

    public function testPrecheckDeveSerSomenteLeituraESemAcessarPncp(): void
    {
        $called = [];
        $gate = $this->gate(
            ['etapa_autorizada' => 'PRECHECK', 'ano' => 2026],
            static function () use (&$called): array {
                $called[] = 'checkpoint';
                return ['snapshot' => ['estado' => 'original'], 'sha256' => str_repeat('a', 64)];
            },
            static function () use (&$called): void { $called[] = 'lock'; },
            static function () use (&$called): void { $called[] = 'publisher'; },
            static function () use (&$called): void { $called[] = 'registrar'; },
            static fn (): array => []
        );

        $report = $gate->precheck();

        $this->assertSame('PRECHECK_CONTENCAO_CONFIRMADO', $report['resultado']);
        $this->assertSame(['checkpoint'], $called);
        $this->assertFalse($report['oracle_writes']);
        $this->assertFalse($report['pncp_access']);
    }

    public function testContencaoDeveValidarLockResultadoSnapshotsPncpELogs(): void
    {
        $events = [['event' => ['action' => 'blocked'], 'context' => []]];
        $locks = [];
        $registered = [];
        $checkpoint = $this->checkpoint();
        $gate = $this->gate(
            $this->configuration(),
            static fn (): array => $checkpoint,
            static function (string $state) use (&$locks): void { $locks[] = $state; },
            fn (string $requestId): ResultadoPublicacaoPcaScriptcase =>
                $this->blockedExecution($requestId, $events),
            static function (array $received) use (&$registered): ?string {
                $registered = $received;
                return null;
            },
            static fn (): array => [[
                'requisicao' => ['metodo' => 'POST', 'caminho' => '/v1/usuarios/login'],
                'resposta' => ['status_http' => 200],
            ]]
        );

        $report = $gate->executar(self::REQUEST_ID);

        $this->assertSame('CONTENCAO_CONFIRMADA', $report['resultado']);
        $this->assertSame('CONCLUIDA_COM_PENDENCIAS', $report['situacao_global']);
        $this->assertSame('BLOQUEADO', $report['resultado_unitario']['situacao']);
        $this->assertSame(['BLOQUEADA'], $locks);
        $this->assertSame($events, $registered);
        $this->assertTrue($report['oracle_snapshot_preservado']);
        $this->assertFalse($report['pncp_writes']);
        $this->assertSame('POST', $report['pncp_diagnostico'][0]['metodo']);
    }

    public function testDevePararAntesDaPublicacaoQuandoLockNaoFoiComprovado(): void
    {
        $published = false;
        $gate = $this->gate(
            $this->configuration(),
            fn (): array => $this->checkpoint(),
            static function (): void {
                throw new Sprint84GateException(
                    'ANCORA_NAO_BLOQUEADA',
                    'CONTENCAO_ORACLE',
                    'Ancora livre.'
                );
            },
            static function () use (&$published): void { $published = true; },
            static fn (): ?string => null,
            static fn (): array => []
        );

        try {
            $gate->executar(self::REQUEST_ID);
            $this->fail('O gate deveria recusar uma ancora livre.');
        } catch (Sprint84GateException $failure) {
            $this->assertSame('ANCORA_NAO_BLOQUEADA', $failure->getErrorCode());
            $this->assertFalse($published);
        }
    }

    public function testDeveRecusarAlteracaoDoSnapshotAntesDeRegistrarLogs(): void
    {
        $checkpoints = [
            $this->checkpoint(),
            ['snapshot' => ['estado' => 'alterado'], 'sha256' => str_repeat('b', 64)],
        ];
        $registered = false;
        $gate = $this->gate(
            $this->configuration(),
            static function () use (&$checkpoints): array {
                return array_shift($checkpoints);
            },
            static fn (): null => null,
            fn (string $requestId): ResultadoPublicacaoPcaScriptcase =>
                $this->blockedExecution($requestId, [['event' => ['action' => 'blocked']]]),
            static function () use (&$registered): ?string { $registered = true; return null; },
            static fn (): array => [[
                'requisicao' => ['metodo' => 'POST', 'caminho' => '/v1/usuarios/login'],
                'resposta' => ['status_http' => 200],
            ]]
        );

        $this->expectException(Sprint84GateException::class);
        $this->expectExceptionMessage('snapshot Oracle mudou');

        try {
            $gate->executar(self::REQUEST_ID);
        } finally {
            $this->assertFalse($registered);
        }
    }

    public function testDisponibilidadeDeveSomenteTestarELiberarAncora(): void
    {
        $locks = [];
        $gate = $this->gate(
            $this->configuration(),
            fn (): array => $this->checkpoint(),
            static function (string $state) use (&$locks): void { $locks[] = $state; },
            static fn (): null => null,
            static fn (): null => null,
            static fn (): array => []
        );

        $report = $gate->verificarDisponibilidade();

        $this->assertSame('ANCORA_DISPONIVEL', $report['resultado']);
        $this->assertSame(['DISPONIVEL'], $locks);
    }

    /** @param array<string, mixed> $configuration */
    private function gate(
        array $configuration,
        callable $checkpoint,
        callable $lock,
        callable $publisher,
        callable $registrar,
        callable $pncp
    ): Sprint85ContainmentGate {
        return Sprint85ContainmentGate::fromComponents(
            $configuration,
            $checkpoint,
            $lock,
            $publisher,
            $registrar,
            $pncp
        );
    }

    /** @return array<string, mixed> */
    private function configuration(): array
    {
        return [
            'etapa_autorizada' => 'CONTENCAO',
            'ano' => 2026,
            'oracle_checkpoint_sha256' => str_repeat('a', 64),
        ];
    }

    /** @return array<string, mixed> */
    private function checkpoint(): array
    {
        return ['snapshot' => ['estado' => 'original'], 'sha256' => str_repeat('a', 64)];
    }

    /** @param array<int, array<string, mixed>> $events */
    private function blockedExecution(
        string $requestId,
        array $events
    ): ResultadoPublicacaoPcaScriptcase {
        $unit = new ResultadoUnidadeViewModel(
            null,
            'CONSOLIDADO',
            2026,
            '930281',
            'Consolidadora',
            'BLOQUEADO',
            null,
            null,
            0,
            0,
            'PUBLICACAO_EM_ANDAMENTO',
            'Publicacao em andamento.'
        );

        return new ResultadoPublicacaoPcaScriptcase(
            new PublicarPcaViewModel(
                'CONCLUIDA_COM_PENDENCIAS',
                [$unit],
                null,
                null,
                1,
                1,
                0,
                $requestId
            ),
            $events,
            null
        );
    }
}
