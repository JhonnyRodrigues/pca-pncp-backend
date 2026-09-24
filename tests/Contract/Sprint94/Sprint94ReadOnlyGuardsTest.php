<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint94;

use LogicException;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Tests\Contract\Sprint94\Support\Sprint94PncpReadOnlyTransport;
use Tests\Contract\Sprint94\Support\Sprint94ReadOnlyAdodbGuard;

final class Sprint94ReadOnlyGuardsTest extends TestCase
{
    public function testOracleDevePermitirSomenteGetAllComSelect(): void
    {
        $interno = new Sprint94AdodbFake();
        $guarda = new Sprint94ReadOnlyAdodbGuard($interno);

        $this->assertSame([], $guarda->GetAll('SELECT 1 FROM DUAL'));

        foreach (['Execute', 'BeginTrans', 'CommitTrans', 'RollbackTrans', 'Close'] as $metodo) {
            try {
                $metodo === 'Execute' ? $guarda->{$metodo}('UPDATE T SET X = 1') : $guarda->{$metodo}();
                $this->fail("O método {$metodo} deveria ser recusado.");
            } catch (LogicException) {
            }
        }

        $this->assertSame(0, $interno->execucoes);
    }

    public function testOracleDeveRecusarSelectComComandoMutanteEmSubconsultaOuComentario(): void
    {
        $guarda = new Sprint94ReadOnlyAdodbGuard(new Sprint94AdodbFake());

        $this->expectException(LogicException::class);
        $guarda->GetAll('SELECT 1 FROM DUAL /* UPDATE SGI.PAC_PLANOS */');
    }

    public function testPncpDevePermitirSomenteLoginEGet(): void
    {
        $interno = new Sprint94TransportFake();
        $guarda = new Sprint94PncpReadOnlyTransport($interno);

        $guarda->enviar(new RequisicaoHttpPncp(
            'POST',
            '/v1/usuarios/login',
            ['Content-Type' => 'application/json'],
            '{}',
            30
        ));
        $guarda->enviar(new RequisicaoHttpPncp('GET', '/v1/orgaos/x/pca/2026', [], null, 30));

        $this->expectException(LogicException::class);
        $guarda->enviar(new RequisicaoHttpPncp('DELETE', '/v1/orgaos/x/pca/2026/1', [], null, 30));
    }

    public function testPncpDeveRecusarPublicacaoAntesDoDespacho(): void
    {
        $interno = new Sprint94TransportFake();
        $guarda = new Sprint94PncpReadOnlyTransport($interno);

        try {
            $guarda->enviar(new RequisicaoHttpPncp(
                'POST',
                '/v1/orgaos/x/pca',
                ['Content-Type' => 'application/json'],
                '{}',
                30
            ));
            $this->fail('A publicação PNCP deveria ser recusada.');
        } catch (LogicException) {
        }

        $this->assertSame(0, $interno->calls);
    }
}

final class Sprint94AdodbFake
{
    public int $execucoes = 0;

    public function GetAll(string $sql, array $binds = []): array
    {
        return [];
    }

    public function Param(string|false $nome): string|bool
    {
        return $nome === false ? true : ':' . $nome;
    }

    public function ErrorNo(): int
    {
        return 0;
    }

    public function ErrorMsg(): string
    {
        return '';
    }

    public function Execute(string $sql, array $binds = []): bool
    {
        $this->execucoes++;
        return true;
    }
}

final class Sprint94TransportFake implements TransporteHttpPncp
{
    public int $calls = 0;

    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        $this->calls++;
        return new RespostaHttpPncp(200, [], '{}');
    }
}
