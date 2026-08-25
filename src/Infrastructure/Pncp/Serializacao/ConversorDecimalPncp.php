<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Serializacao;

use Semae\Pca\Domain\ValueObjects\DecimalExato;

final class ConversorDecimalPncp
{
    private const CASAS_DECIMAIS = 4;
    private const DIGITOS_PRECISAO = 17;
    private const DIGITOS_INTEIROS = self::DIGITOS_PRECISAO - self::CASAS_DECIMAIS;

    public function converterValor(DecimalExato $decimal): NumeroJsonExato
    {
        [$inteiro, $fracao] = $this->separarValorNaoNegativo($decimal);

        $fracaoNaEscala = str_pad(
            substr($fracao, 0, self::CASAS_DECIMAIS),
            self::CASAS_DECIMAIS,
            '0'
        );
        $valorEscalado = $inteiro . $fracaoNaEscala;

        if (($fracao[self::CASAS_DECIMAIS] ?? '0') >= '5') {
            $valorEscalado = $this->incrementarInteiroTextual($valorEscalado);
        }

        return $this->criarNumeroEscalado($valorEscalado);
    }

    public function converterQuantidade(int $quantidade): NumeroJsonExato
    {
        $this->validarQuantidade($quantidade);

        return new NumeroJsonExato((string) $quantidade . '.0000');
    }

    public function calcularValorUnitario(
        DecimalExato $valorTotal,
        int $quantidade
    ): NumeroJsonExato {
        $this->validarQuantidade($quantidade);
        [$inteiro, $fracao] = $this->separarValorNaoNegativo($valorTotal);
        [$inteiroQuociente, $resto] = $this->dividirInteiroTextual(
            $inteiro,
            $quantidade
        );

        $fracaoQuociente = '';
        $casasParaArredondamento = self::CASAS_DECIMAIS + 1;

        for ($indice = 0; $indice < $casasParaArredondamento; $indice++) {
            $digito = isset($fracao[$indice]) ? (int) $fracao[$indice] : 0;
            $parcial = ($resto * 10) + $digito;
            $fracaoQuociente .= (string) intdiv($parcial, $quantidade);
            $resto = $parcial % $quantidade;
        }

        $valorEscalado = $inteiroQuociente
            . substr($fracaoQuociente, 0, self::CASAS_DECIMAIS);

        if ($fracaoQuociente[self::CASAS_DECIMAIS] >= '5') {
            $valorEscalado = $this->incrementarInteiroTextual($valorEscalado);
        }

        return $this->criarNumeroEscalado($valorEscalado);
    }

    /** @return array{string, string} */
    private function separarValorNaoNegativo(DecimalExato $decimal): array
    {
        $valor = $decimal->getValor();

        if ($valor[0] === '-') {
            throw new FalhaSerializacaoPncp();
        }

        $partes = explode('.', $valor, 2);

        return [$partes[0], $partes[1] ?? ''];
    }

    private function validarQuantidade(int $quantidade): void
    {
        if (
            $quantidade <= 0
            || strlen((string) $quantidade) > self::DIGITOS_INTEIROS
        ) {
            throw new FalhaSerializacaoPncp();
        }
    }

    /** @return array{string, int} */
    private function dividirInteiroTextual(string $dividendo, int $divisor): array
    {
        $quociente = '';
        $resto = 0;
        $quantidadeDigitos = strlen($dividendo);

        for ($indice = 0; $indice < $quantidadeDigitos; $indice++) {
            $parcial = ($resto * 10) + (int) $dividendo[$indice];
            $quociente .= (string) intdiv($parcial, $divisor);
            $resto = $parcial % $divisor;
        }

        $quociente = ltrim($quociente, '0');

        return [$quociente === '' ? '0' : $quociente, $resto];
    }

    private function incrementarInteiroTextual(string $valor): string
    {
        for ($indice = strlen($valor) - 1; $indice >= 0; $indice--) {
            $digito = (int) $valor[$indice];

            if ($digito < 9) {
                $valor[$indice] = (string) ($digito + 1);

                return $valor;
            }

            $valor[$indice] = '0';
        }

        return '1' . $valor;
    }

    private function criarNumeroEscalado(string $valorEscalado): NumeroJsonExato
    {
        $valorCanonico = ltrim($valorEscalado, '0');
        $valorCanonico = $valorCanonico === '' ? '0' : $valorCanonico;

        if (strlen($valorCanonico) > self::DIGITOS_PRECISAO) {
            throw new FalhaSerializacaoPncp();
        }

        $valorCanonico = str_pad(
            $valorCanonico,
            self::CASAS_DECIMAIS + 1,
            '0',
            STR_PAD_LEFT
        );
        $inteiro = substr($valorCanonico, 0, -self::CASAS_DECIMAIS);
        $fracao = substr($valorCanonico, -self::CASAS_DECIMAIS);

        return new NumeroJsonExato($inteiro . '.' . $fracao);
    }
}
