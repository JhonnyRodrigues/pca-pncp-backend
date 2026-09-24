<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint94;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Contract\Sprint94\Support\Sprint94Report;

final class Sprint94ReportTest extends TestCase
{
    public function testHashDeveSerDeterministicoApesarDaOrdemDasChaves(): void
    {
        $primeiro = Sprint94Report::create(['b' => 2, 'a' => ['d' => 4, 'c' => 3]]);
        $segundo = Sprint94Report::create(['a' => ['c' => 3, 'd' => 4], 'b' => 2]);

        $this->assertSame($primeiro['sha256'], $segundo['sha256']);
        $this->assertSame(['a', 'b', 'sha256'], array_keys($primeiro));
    }

    /** @dataProvider dadosSensiveis */
    public function testDeveRecusarDadosSensiveis(string $chave, mixed $valor): void
    {
        $this->expectException(InvalidArgumentException::class);
        Sprint94Report::create([$chave => $valor]);
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function dadosSensiveis(): iterable
    {
        yield 'senha' => ['senha', 'segredo'];
        yield 'token' => ['token', 'opaco'];
        yield 'authorization' => ['authorization', 'Bearer segredo'];
        yield 'sql' => ['sql', 'SELECT 1 FROM DUAL'];
        yield 'cnpj integral' => ['cnpj', '12345678000195'];
    }
}
