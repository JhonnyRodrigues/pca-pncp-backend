<?php

declare(strict_types=1);

namespace Tests\Domain\ValueObjects;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\ValueObjects\DecimalExato;
use TypeError;

final class DecimalExatoTest extends TestCase
{
    public function testDevePreservarIntegralmenteARepresentacaoDecimalRecebida(): void
    {
        $decimal = new DecimalExato('1234567890123456.70');

        $this->assertSame('1234567890123456.70', $decimal->getValor());
    }

    #[DataProvider('representacoesInvalidas')]
    public function testDeveRejeitarRepresentacaoQueNaoSejaDecimalExata(string $valor): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DecimalExato($valor);
    }

    public function testNaoDeveAceitarFloatComoEntrada(): void
    {
        $this->expectException(TypeError::class);

        new DecimalExato(1.25);
    }

    /** @return array<string, array{string}> */
    public static function representacoesInvalidas(): array
    {
        return [
            'vazio' => [''],
            'vírgula decimal' => ['1,25'],
            'notação científica' => ['1e3'],
            'espaços' => [' 1.25 '],
            'zeros não canônicos' => ['01.25'],
        ];
    }
}
