<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint59;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Tests\Contract\Sprint59\Support\OracleAdaptersContractRunner;

final class OracleAdaptersContractRunnerTest extends TestCase
{
    public function testDeveLerLinhaComApenasChaveAssociativaSemDistinguirCaixa(): void
    {
        $this->assertSame(
            'SPRINT_59_BIND',
            $this->firstScalar([['valor_bind' => 'SPRINT_59_BIND']], 'VALOR_BIND')
        );
    }

    public function testDeveIgnorarIndiceNumericoDuplicadoPeloAdodb(): void
    {
        $this->assertSame(
            '123',
            $this->firstScalar([[0 => '123', 'SESSAO' => '123']], 'SESSAO')
        );
    }

    public function testDeveRejeitarLinhaSemAliasEsperado(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('alias esperado');

        $this->firstScalar([['OUTRO_ALIAS' => 1]], 'TOTAL');
    }

    public function testDeveRejeitarMaisDeUmaLinha(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('linha unica');

        $this->firstScalar([['TOTAL' => 1], ['TOTAL' => 2]], 'TOTAL');
    }

    /**
     * @param array<int, array<int|string, mixed>> $rows
     */
    private function firstScalar(array $rows, string $alias): mixed
    {
        $reflection = new ReflectionClass(OracleAdaptersContractRunner::class);
        $runner = $reflection->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(OracleAdaptersContractRunner::class, 'firstScalar');

        return $method->invoke($runner, $rows, $alias);
    }
}
