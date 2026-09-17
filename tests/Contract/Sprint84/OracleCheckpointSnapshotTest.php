<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Contract\Sprint84\Support\OracleCheckpointSnapshot;

final class OracleCheckpointSnapshotTest extends TestCase
{
    public function testHashDeveSerDeterministicoParaOrdemDasChavesAssociativas(): void
    {
        $consultas = ['primeira' => [], 'segunda' => []];
        $primeiro = OracleCheckpointSnapshot::fromResults($consultas, [
            'primeira' => [['B' => '2', 'A' => '1']],
            'segunda' => [['VALOR' => null]],
        ]);
        $segundo = OracleCheckpointSnapshot::fromResults($consultas, [
            'segunda' => [['VALOR' => null]],
            'primeira' => [['A' => '1', 'B' => '2']],
        ]);

        $this->assertSame($primeiro->toArray(), $segundo->toArray());
        $this->assertSame($primeiro->toJson(), $segundo->toJson());
        $this->assertSame($primeiro->hash(), $segundo->hash());
        $this->assertSame(64, strlen($primeiro->hash()));
    }

    public function testOrdemDasLinhasDeveParticiparDoHash(): void
    {
        $consultas = ['consulta' => []];
        $primeiro = OracleCheckpointSnapshot::fromResults($consultas, [
            'consulta' => [['ID' => 1], ['ID' => 2]],
        ]);
        $segundo = OracleCheckpointSnapshot::fromResults($consultas, [
            'consulta' => [['ID' => 2], ['ID' => 1]],
        ]);

        $this->assertNotSame($primeiro->hash(), $segundo->hash());
    }

    public function testOrdemDoMapaDeConsultasNaoDeveParticiparDoHash(): void
    {
        $resultados = [
            'primeira' => [['ID' => 1]],
            'segunda' => [['ID' => 2]],
        ];

        $primeiro = OracleCheckpointSnapshot::fromResults(
            ['primeira' => [], 'segunda' => []],
            $resultados
        );
        $segundo = OracleCheckpointSnapshot::fromResults(
            ['segunda' => [], 'primeira' => []],
            $resultados
        );

        $this->assertSame($primeiro->toJson(), $segundo->toJson());
        $this->assertSame($primeiro->hash(), $segundo->hash());
    }

    public function testDeveExigirResultadoParaCadaConsultaSemChavesAdicionais(): void
    {
        $this->expectException(InvalidArgumentException::class);
        OracleCheckpointSnapshot::fromResults(
            ['esperada' => []],
            ['outra' => []]
        );
    }
}
