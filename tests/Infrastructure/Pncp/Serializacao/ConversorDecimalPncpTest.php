<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Pncp\Serializacao;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\ValueObjects\DecimalExato;
use Semae\Pca\Infrastructure\Pncp\Serializacao\CodificadorJsonExato;
use Semae\Pca\Infrastructure\Pncp\Serializacao\ConversorDecimalPncp;
use Semae\Pca\Infrastructure\Pncp\Serializacao\FalhaSerializacaoPncp;
use Semae\Pca\Infrastructure\Pncp\Serializacao\NumeroJsonExato;

final class ConversorDecimalPncpTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function valoresConvertidosEmEscalaFixa(): iterable
    {
        yield 'inteiro' => ['12', '12.0000'];
        yield 'escala menor' => ['12.3', '12.3000'];
        yield 'escala exata' => ['12.3456', '12.3456'];
        yield 'escala maior abaixo do empate' => ['12.34564', '12.3456'];
        yield 'empate half up' => ['12.34565', '12.3457'];
        yield 'acima do empate' => ['12.34569', '12.3457'];
        yield 'carry entre casas' => ['12.99995', '13.0000'];
        yield 'zero' => ['0', '0.0000'];
        yield 'zero arredondado para cima' => ['0.00005', '0.0001'];
        yield 'limite decimal' => ['9999999999999.9999', '9999999999999.9999'];
    }

    #[DataProvider('valoresConvertidosEmEscalaFixa')]
    public function testDeveConverterDecimalComQuatroCasasEHalfUp(
        string $entrada,
        string $esperado
    ): void {
        $numero = (new ConversorDecimalPncp())->converterValor(new DecimalExato($entrada));

        $this->assertNumeroExato($esperado, $numero);
    }

    /** @return iterable<string, array{int, string}> */
    public static function quantidadesPositivas(): iterable
    {
        yield 'unidade' => [1, '1.0000'];
        yield 'quantidade comum' => [125, '125.0000'];
        yield 'limite inteiro do decimal' => [9_999_999_999_999, '9999999999999.0000'];
    }

    #[DataProvider('quantidadesPositivas')]
    public function testDeveConverterQuantidadeInteiraEmEscalaFixa(
        int $quantidade,
        string $esperado
    ): void {
        $numero = (new ConversorDecimalPncp())->converterQuantidade($quantidade);

        $this->assertNumeroExato($esperado, $numero);
    }

    /** @return iterable<string, array{string, int, string}> */
    public static function valoresUnitarios(): iterable
    {
        yield 'divisao exata' => ['10', 4, '2.5000'];
        yield 'divisao nao exata' => ['1', 6, '0.1667'];
        yield 'empate half up' => ['1', 32, '0.0313'];
        yield 'carry no arredondamento' => ['9.99995', 1, '10.0000'];
        yield 'total zero' => ['0', 7, '0.0000'];
        yield 'fracao com mais de cinco casas' => ['0.000099', 1, '0.0001'];
        yield 'limite decimal' => ['9999999999999.9999', 1, '9999999999999.9999'];
    }

    #[DataProvider('valoresUnitarios')]
    public function testDeveCalcularValorUnitarioTextualmenteComHalfUp(
        string $valorTotal,
        int $quantidade,
        string $esperado
    ): void {
        $numero = (new ConversorDecimalPncp())->calcularValorUnitario(
            new DecimalExato($valorTotal),
            $quantidade
        );

        $this->assertNumeroExato($esperado, $numero);
    }

    public function testDeveCalcularUnitarioPeloTotalOriginalSemUsarTotalArredondado(): void
    {
        $conversor = new ConversorDecimalPncp();
        $valorTotal = new DecimalExato('1.00005');

        $this->assertNumeroExato('1.0001', $conversor->converterValor($valorTotal));
        $this->assertNumeroExato(
            '0.5000',
            $conversor->calcularValorUnitario($valorTotal, 2)
        );
    }

    /** @return iterable<string, array{string}> */
    public static function valoresNegativos(): iterable
    {
        yield 'negativo' => ['-1'];
        yield 'zero negativo' => ['-0'];
        yield 'zero decimal negativo' => ['-0.0000'];
    }

    #[DataProvider('valoresNegativos')]
    public function testDeveRejeitarValorNegativo(string $valor): void
    {
        $this->esperarFalhaSegura();

        (new ConversorDecimalPncp())->converterValor(new DecimalExato($valor));
    }

    public function testDeveRejeitarValorUnitarioCalculadoDeTotalNegativo(): void
    {
        $this->esperarFalhaSegura();

        (new ConversorDecimalPncp())->calcularValorUnitario(new DecimalExato('-1'), 1);
    }

    /** @return iterable<string, array{int}> */
    public static function quantidadesInvalidas(): iterable
    {
        yield 'zero' => [0];
        yield 'negativa' => [-1];
        yield 'acima do decimal' => [10_000_000_000_000];
    }

    #[DataProvider('quantidadesInvalidas')]
    public function testDeveRejeitarQuantidadeInvalida(int $quantidade): void
    {
        $this->esperarFalhaSegura();

        (new ConversorDecimalPncp())->converterQuantidade($quantidade);
    }

    #[DataProvider('quantidadesInvalidas')]
    public function testDeveRejeitarDivisorInvalido(int $quantidade): void
    {
        $this->esperarFalhaSegura();

        (new ConversorDecimalPncp())->calcularValorUnitario(new DecimalExato('1'), $quantidade);
    }

    /** @return iterable<string, array{string}> */
    public static function valoresComOverflow(): iterable
    {
        yield 'parte inteira acima do limite' => ['10000000000000'];
        yield 'overflow causado por arredondamento' => ['9999999999999.99995'];
    }

    #[DataProvider('valoresComOverflow')]
    public function testDeveRejeitarValorForaDeDecimalDezesseteQuatro(string $valor): void
    {
        $this->esperarFalhaSegura();

        (new ConversorDecimalPncp())->converterValor(new DecimalExato($valor));
    }

    #[DataProvider('valoresComOverflow')]
    public function testDeveRejeitarValorUnitarioForaDeDecimalDezesseteQuatro(
        string $valor
    ): void {
        $this->esperarFalhaSegura();

        (new ConversorDecimalPncp())->calcularValorUnitario(new DecimalExato($valor), 1);
    }

    private function assertNumeroExato(string $esperado, NumeroJsonExato $numero): void
    {
        $this->assertSame($esperado, (new CodificadorJsonExato())->codificar($numero));
    }

    private function esperarFalhaSegura(): void
    {
        $this->expectException(FalhaSerializacaoPncp::class);
        $this->expectExceptionMessage('Não foi possível serializar o payload PNCP.');
    }
}
