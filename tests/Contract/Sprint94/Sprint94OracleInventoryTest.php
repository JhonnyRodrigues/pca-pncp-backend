<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint94;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Tests\Contract\Sprint94\Support\Sprint94InventoryException;
use Tests\Contract\Sprint94\Support\Sprint94OracleInventory;
use Tests\Support\PerfilPublicacaoPcaFixture;

final class Sprint94OracleInventoryTest extends TestCase
{
    public function testInventarioDeveExecutarSomenteSelectParaOsDoisExercicios(): void
    {
        $adodb = new Sprint94OracleAdodbFake();

        $result = (new Sprint94OracleInventory())->collect(
            $adodb,
            new PerfilPublicacaoPca(PerfilPublicacaoPcaFixture::valido()),
            [2026, 2027]
        );

        $this->assertSame([2026, 2027], array_keys($result['exercicios']));
        $this->assertCount(7, $adodb->queries);
        foreach ($adodb->queries as $query) {
            $this->assertMatchesRegularExpression('/^SELECT\b/i', ltrim($query));
        }
        $this->assertSame(0, $adodb->mutations);
    }

    public function testFalhaNaLeituraDaEstrategiaDeveSerClassificadaSemExporDetalhes(): void
    {
        $adodb = new Sprint94OracleAdodbFake();
        $adodb->failNextQuery = true;

        try {
            (new Sprint94OracleInventory())->collect(
                $adodb,
                new PerfilPublicacaoPca(PerfilPublicacaoPcaFixture::valido()),
                [2026, 2027]
            );
            $this->fail('Era esperada uma falha classificada do inventario Oracle.');
        } catch (Sprint94InventoryException $failure) {
            $this->assertSame('ORACLE_ESTRATEGIA_INCONCLUSIVA', $failure->getErrorCode());
            $this->assertNotSame('falha Oracle sensivel', $failure->getMessage());
        }
    }
}

final class Sprint94OracleAdodbFake
{
    /** @var string[] */
    public array $queries = [];

    public int $mutations = 0;

    public bool $failNextQuery = false;

    /** @param array<int|string, mixed> $binds @return array<int, array<string, mixed>> */
    public function GetAll(string $sql, array $binds = []): array
    {
        $this->queries[] = $sql;
        if ($this->failNextQuery) {
            $this->failNextQuery = false;
            throw new \RuntimeException('falha Oracle sensivel');
        }
        return [];
    }

    public function Param(string|false $name): string|bool
    {
        return $name === false ? true : ':' . $name;
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
        $this->mutations++;
        return true;
    }

    public function BeginTrans(): bool
    {
        $this->mutations++;
        return true;
    }

    public function CommitTrans(): bool
    {
        $this->mutations++;
        return true;
    }

    public function RollbackTrans(): bool
    {
        $this->mutations++;
        return true;
    }
}
