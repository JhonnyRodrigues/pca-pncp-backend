<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Pncp\Serializacao;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Infrastructure\Pncp\Serializacao\CodificadorJsonExato;
use Semae\Pca\Infrastructure\Pncp\Serializacao\FalhaSerializacaoPncp;
use Semae\Pca\Infrastructure\Pncp\Serializacao\NumeroJsonExato;

final class NumeroJsonExatoTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function lexemasValidos(): iterable
    {
        yield 'zero' => ['0'];
        yield 'zero negativo' => ['-0'];
        yield 'inteiro' => ['123'];
        yield 'decimal com zeros finais' => ['123.4500'];
        yield 'decimal negativo' => ['-0.125'];
        yield 'expoente positivo' => ['1e3'];
        yield 'expoente negativo' => ['1.25E-3'];
        yield 'expoente com sinal' => ['-2e+10'];
    }

    #[DataProvider('lexemasValidos')]
    public function testDevePreservarLexemaNumericoJsonValido(string $lexema): void
    {
        $numero = new NumeroJsonExato($lexema);

        $this->assertSame($lexema, (new CodificadorJsonExato())->codificar($numero));
    }

    /** @return iterable<string, array{string}> */
    public static function lexemasInvalidos(): iterable
    {
        yield 'vazio' => [''];
        yield 'espaco antes' => [' 1'];
        yield 'espaco depois' => ['1 '];
        yield 'sinal positivo inicial' => ['+1'];
        yield 'zero inicial' => ['01'];
        yield 'zero inicial negativo' => ['-01'];
        yield 'sem inteiro' => ['.1'];
        yield 'sem fracao' => ['1.'];
        yield 'expoente sem digitos' => ['1e'];
        yield 'expoente com sinal sem digitos' => ['1e+'];
        yield 'not a number' => ['NaN'];
        yield 'infinito' => ['Infinity'];
        yield 'separador decimal incorreto' => ['1,5'];
    }

    #[DataProvider('lexemasInvalidos')]
    public function testDeveRejeitarLexemaNumericoJsonInvalidoSemExporValor(string $lexema): void
    {
        $this->expectException(FalhaSerializacaoPncp::class);
        $this->expectExceptionMessage('Não foi possível serializar o payload PNCP.');

        new NumeroJsonExato($lexema);
    }
}
