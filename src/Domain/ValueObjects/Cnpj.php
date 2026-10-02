<?php

namespace Semae\Pca\Domain\ValueObjects;

use InvalidArgumentException;

class Cnpj
{
    private string $valor;

    public function __construct(string $valor)
    {
        $valorLimpo = preg_replace('/[^A-Za-z0-9]/', '', $valor);

        if ($valorLimpo !== null) {
            $valorLimpo = strtoupper($valorLimpo);
        }

        if (
            $valorLimpo === null
            || preg_match('/^[A-Z0-9]{12}[0-9]{2}$/', $valorLimpo) !== 1
            || !$this->validarDigitos($valorLimpo)
        ) {
            throw new InvalidArgumentException('CNPJ inválido.');
        }

        $this->valor = $valorLimpo;
    }

    public function getValor(): string
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
            $somatorio1 += $this->valorDoCaractere($digitos[$i]) * $peso1[$i];
        }

        $resto1 = $somatorio1 % 11;
        $digito1 = $resto1 < 2 ? 0 : 11 - $resto1;

        $somatorio2 = 0;
        for ($i = 0; $i < 12; $i++) {
            $somatorio2 += $this->valorDoCaractere($digitos[$i]) * $peso2[$i];
        }
        $somatorio2 += $digito1 * $peso2[12];

        $resto2 = $somatorio2 % 11;
        $digito2 = $resto2 < 2 ? 0 : 11 - $resto2;

        return $digitos[12] === (string) $digito1 && $digitos[13] === (string) $digito2;
    }

    private function valorDoCaractere(string $caractere): int
    {
        return ord($caractere) - 48;
    }
}

/*
A responsabilidade desta classe é encapsular um CNPJ do domínio, garantindo que apenas valores válidos sejam representados e mantendo a regra de negócio fora de controllers e repositórios. Ela pertence à camada de domínio porque é um conceito do negócio e não depende de infraestrutura. O teste será voltado a validar exatamente esse comportamento: aceitar um CNPJ alfanumérico corretamente formatado e rejeitar entradas inválidas.
*/
