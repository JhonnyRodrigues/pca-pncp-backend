<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint94;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Tests\Contract\Sprint94\Support\Sprint94InventoryException;
use Tests\Contract\Sprint94\Support\Sprint94OracleScope;
use Tests\Support\PerfilPublicacaoPcaFixture;

final class Sprint94OracleScopeTest extends TestCase
{
    /** @param array<string, mixed> $oracle */
    #[DataProvider('invalidScopes')]
    public function testEscopoInvalidoDeveInformarSomenteACondicaoSegura(
        array $oracle,
        string $expectedCode
    ): void {
        try {
            (new Sprint94OracleScope())->expectedUasgs(
                $oracle,
                new PerfilPublicacaoPca(PerfilPublicacaoPcaFixture::valido()),
                2026
            );
            $this->fail('Era esperada uma falha classificada do escopo Oracle.');
        } catch (Sprint94InventoryException $failure) {
            $this->assertSame($expectedCode, $failure->getErrorCode());
        }
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function invalidScopes(): iterable
    {
        yield 'estrategia ausente' => [
            ['estrategia_corrente' => [], 'exercicios' => []],
            'ORACLE_ESTRATEGIA_AUSENTE',
        ];
        yield 'estrategia desconhecida' => [
            self::oracleWithStrategy('OUTRA'),
            'ORACLE_ESTRATEGIA_DESCONHECIDA',
        ];
        yield 'consolidadora ausente' => [
            self::oracleWithStrategy('CONSOLIDADO'),
            'ORACLE_UASG_CONSOLIDADORA_AUSENTE',
        ];
        yield 'exercicio ausente' => [
            self::oracleWithStrategy('INDIVIDUAL'),
            'ORACLE_EXERCICIO_2026_AUSENTE',
        ];
        yield 'unidade sem UASG' => [
            self::oracleWithStrategy('INDIVIDUAL', [
                '2026' => ['unidades' => [['CODIGO_UASG' => null]]],
            ]),
            'ORACLE_UNIDADE_2026_SEM_UASG',
        ];
    }

    public function testEstrategiaIndividualDeveRetornarUasgsOrdenadasESemDuplicidade(): void
    {
        $oracle = self::oracleWithStrategy('INDIVIDUAL', [
            '2026' => ['unidades' => [
                ['CODIGO_UASG' => '200'],
                ['CODIGO_UASG' => '100'],
                ['CODIGO_UASG' => 200],
            ]],
        ]);

        $result = (new Sprint94OracleScope())->expectedUasgs(
            $oracle,
            new PerfilPublicacaoPca(PerfilPublicacaoPcaFixture::valido()),
            2026
        );

        $this->assertSame(['100', '200'], $result);
    }

    /**
     * @param array<string, mixed> $exercises
     * @return array<string, mixed>
     */
    private static function oracleWithStrategy(string $strategy, array $exercises = []): array
    {
        return [
            'estrategia_corrente' => [[
                'CHAVE_CONFIGURACAO' => 'ESTRATEGIA_PUBLICACAO_PLANOS',
                'VALOR_CONFIGURACAO' => $strategy,
            ]],
            'exercicios' => $exercises,
        ];
    }
}
