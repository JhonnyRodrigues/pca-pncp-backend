<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Plano;

use InvalidArgumentException;

final class MapeamentoLotacoesOracle
{
    private readonly string $schema;
    private readonly string $tabela;
    private readonly string $colunaId;
    private readonly string $colunaDescricao;

    public function __construct(
        string $schema,
        string $tabela,
        string $colunaId,
        string $colunaDescricao
    ) {
        $this->schema = self::normalizarIdentificador($schema, 'schema');
        $this->tabela = self::normalizarIdentificador($tabela, 'tabela de Lotações');
        $this->colunaId = self::normalizarIdentificador(
            $colunaId,
            'coluna de identidade da Lotação'
        );
        $this->colunaDescricao = self::normalizarIdentificador(
            $colunaDescricao,
            'coluna da descrição da Lotação'
        );
    }

    public function getTabelaQualificada(): string
    {
        return $this->schema . '.' . $this->tabela;
    }

    public function getColunaId(): string
    {
        return $this->colunaId;
    }

    public function getColunaDescricao(): string
    {
        return $this->colunaDescricao;
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
}
