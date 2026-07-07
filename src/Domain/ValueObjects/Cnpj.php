<?php

namespace Semae\Pca\Domain\ValueObjects;

use InvalidArgumentException;

class Cnpj
{
    private string $valor;

    public function __construct(string $valor)
    {
        $valorLimpo = preg_replace('/\D/', '', $valor);

        if ($valorLimpo === null || strlen($valorLimpo) !== 14 || !$this->validarDigitos($valorLimpo)) {
            throw new InvalidArgumentException('CNPJ inválido.');
        }

        $this->valor = $valorLimpo;
    }

    public function valor(): string
    {
        return $this->valor;
    }

    private function validarDigitos(string $valor): bool
    {
        if (preg_match('/^(\d)\\1+$/', $valor) === 1) {
            return false;
        }

        $peso1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $peso2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $digitos = str_split($valor);
        $somatorio1 = 0;

        for ($i = 0; $i < 12; $i++) {
            $somatorio1 += (int) $digitos[$i] * $peso1[$i];
        }

        $resto1 = $somatorio1 % 11;
        $digito1 = $resto1 < 2 ? 0 : 11 - $resto1;

        $somatorio2 = 0;
        for ($i = 0; $i < 12; $i++) {
            $somatorio2 += (int) $digitos[$i] * $peso2[$i];
        }
        $somatorio2 += $digito1 * $peso2[12];

        $resto2 = $somatorio2 % 11;
        $digito2 = $resto2 < 2 ? 0 : 11 - $resto2;

        return $digitos[12] === (string) $digito1 && $digitos[13] === (string) $digito2;
    }
}

/*
A responsabilidade desta classe é encapsular um CNPJ do domínio, garantindo que apenas valores válidos sejam representados e mantendo a regra de negócio fora de controllers e repositórios. Ela pertence à camada de domínio porque é um conceito do negócio e não depende de infraestrutura. O teste será voltado a validar exatamente esse comportamento: aceitar um CNPJ corretamente formatado e rejeitar entradas inválidas.
*/