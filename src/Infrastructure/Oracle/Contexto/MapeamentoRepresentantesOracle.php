<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Contexto;

use InvalidArgumentException;

final class MapeamentoRepresentantesOracle
{
    private readonly string $schema;
    private readonly string $tabela;
    private readonly string $colunaLogin;
    private readonly string $colunaId;
    private readonly string $colunaNome;
    private readonly string $colunaAtivo;
    private readonly int|string $valorAtivo;

    public function __construct(
        string $schema,
        string $tabela,
        string $colunaLogin,
        string $colunaId,
        string $colunaNome,
        string $colunaAtivo,
        int|string $valorAtivo
    ) {
        $this->schema = self::normalizarIdentificador($schema, 'schema');
        $this->tabela = self::normalizarIdentificador($tabela, 'tabela de Representantes');
        $this->colunaLogin = self::normalizarIdentificador($colunaLogin, 'coluna de login');
        $this->colunaId = self::normalizarIdentificador($colunaId, 'coluna de ID');
        $this->colunaNome = self::normalizarIdentificador($colunaNome, 'coluna de nome');
        $this->colunaAtivo = self::normalizarIdentificador($colunaAtivo, 'coluna de ativo');
        $this->valorAtivo = self::normalizarLiteral($valorAtivo, 'literal de Representante ativo');
    }

    public function getTabelaQualificada(): string
    {
        return $this->schema . '.' . $this->tabela;
    }

    public function getColunaLogin(): string
    {
        return $this->colunaLogin;
    }

    public function getColunaId(): string
    {
        return $this->colunaId;
    }

    public function getColunaNome(): string
    {
        return $this->colunaNome;
    }

    public function getColunaAtivo(): string
    {
        return $this->colunaAtivo;
    }

    public function getValorAtivo(): int|string
    {
        return $this->valorAtivo;
    }

    private static function normalizarIdentificador(string $valor, string $campo): string
    {
        $valor = trim($valor);

        if (!preg_match('/^[A-Za-z][A-Za-z0-9_$#]*$/D', $valor)) {
            throw new InvalidArgumentException(
                "O mapeamento da {$campo} contém um identificador Oracle inválido."
            );
        }

        return strtoupper($valor);
    }

    private static function normalizarLiteral(int|string $valor, string $campo): int|string
    {
        if (is_string($valor)) {
            $valor = trim($valor);

            if ($valor === '') {
                throw new InvalidArgumentException("O {$campo} é obrigatório.");
            }
        }

        return $valor;
    }
}
