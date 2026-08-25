<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Serializacao;

use JsonException;

final class CodificadorJsonExato
{
    public function codificar(mixed $valor): string
    {
        if ($valor instanceof NumeroJsonExato) {
            return $valor->getLexema();
        }

        if (is_array($valor)) {
            return array_is_list($valor)
                ? $this->codificarLista($valor)
                : $this->codificarObjeto($valor);
        }

        if (is_string($valor) || is_int($valor) || is_bool($valor) || $valor === null) {
            return $this->codificarComJsonNativo($valor);
        }

        throw new FalhaSerializacaoPncp();
    }

    /** @param array<int, mixed> $valores */
    private function codificarLista(array $valores): string
    {
        $itens = [];

        foreach ($valores as $valor) {
            $itens[] = $this->codificar($valor);
        }

        return '[' . implode(',', $itens) . ']';
    }

    /** @param array<string|int, mixed> $valores */
    private function codificarObjeto(array $valores): string
    {
        $propriedades = [];

        foreach ($valores as $chave => $valor) {
            $propriedades[] = $this->codificarComJsonNativo((string) $chave)
                . ':'
                . $this->codificar($valor);
        }

        return '{' . implode(',', $propriedades) . '}';
    }

    private function codificarComJsonNativo(string|int|bool|null $valor): string
    {
        try {
            return json_encode(
                $valor,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
        } catch (JsonException) {
            throw new FalhaSerializacaoPncp();
        }
    }
}
