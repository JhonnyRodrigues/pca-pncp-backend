<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Adodb;

use InvalidArgumentException;
use UnexpectedValueException;

final class ConversorCharsetAdodbOracle
{
    private const CHARSETS_SUPORTADOS = ['UTF-8', 'Windows-1252'];

    public function __construct(private readonly string $charsetAdodb)
    {
        if (!in_array($this->charsetAdodb, self::CHARSETS_SUPORTADOS, true)) {
            throw new InvalidArgumentException(
                'Charset ADOdb Oracle não suportado: ' . $this->charsetAdodb . '.'
            );
        }
        if (!function_exists('mb_check_encoding') || !function_exists('mb_convert_encoding')) {
            throw new InvalidArgumentException(
                'A extensão mbstring é obrigatória para validar o charset ADOdb Oracle.'
            );
        }
    }

    public function getCharsetAdodb(): string
    {
        return $this->charsetAdodb;
    }

    public function converterLeitura(mixed $valor): mixed
    {
        if (is_array($valor)) {
            foreach ($valor as $chave => $item) {
                $valor[$chave] = $this->converterLeitura($item);
            }

            return $valor;
        }
        if (!is_string($valor)) {
            return $valor;
        }
        if ($this->charsetAdodb === 'UTF-8') {
            if (!mb_check_encoding($valor, 'UTF-8')) {
                throw new UnexpectedValueException(
                    'Texto lido do ADOdb não contém UTF-8 válido.'
                );
            }

            return $valor;
        }

        $convertido = mb_convert_encoding($valor, 'UTF-8', $this->charsetAdodb);
        if (!mb_check_encoding($convertido, 'UTF-8')) {
            throw new UnexpectedValueException(
                'Texto lido do ADOdb não pôde ser convertido para UTF-8.'
            );
        }

        return $convertido;
    }

    public function converterResultadoGetAll(mixed $linhas): mixed
    {
        if (!is_array($linhas)) {
            return $this->converterLeitura($linhas);
        }

        foreach ($linhas as $indice => $linha) {
            if (!is_array($linha)) {
                $linhas[$indice] = $this->converterLeitura($linha);
                continue;
            }

            $associativa = [];
            foreach ($linha as $coluna => $valor) {
                if (is_string($coluna)) {
                    $associativa[$coluna] = $this->converterLeitura($valor);
                }
            }
            $linhas[$indice] = $associativa;
        }

        return $linhas;
    }

    public function converterEscrita(mixed $valor): mixed
    {
        if (!is_string($valor)) {
            return $valor;
        }
        if (!mb_check_encoding($valor, 'UTF-8')) {
            throw new UnexpectedValueException(
                'Texto destinado ao Oracle não contém UTF-8 válido.'
            );
        }
        if ($this->charsetAdodb === 'UTF-8') {
            return $valor;
        }

        $convertido = mb_convert_encoding($valor, $this->charsetAdodb, 'UTF-8');
        $restaurado = mb_convert_encoding($convertido, 'UTF-8', $this->charsetAdodb);
        if ($restaurado !== $valor) {
            throw new UnexpectedValueException(
                'Texto destinado ao Oracle não pode ser representado em '
                    . $this->charsetAdodb . ' sem perda.'
            );
        }

        return $convertido;
    }
}
