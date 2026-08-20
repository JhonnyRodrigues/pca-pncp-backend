<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Estrategia;

use InvalidArgumentException;

final class MapeamentoPlanosEstrategiaOracle
{
    private readonly string $schema;
    private readonly string $tabela;
    private readonly string $colunaAnoExecucao;
    private readonly string $colunaUnidadeOrcamentaria;
    private readonly string $colunaModoAdotado;
    private readonly string $colunaUasgAdotada;
    private readonly string $literalModoIndividual;
    private readonly string $literalModoConsolidado;

    public function __construct(
        string $schema,
        string $tabela,
        string $colunaAnoExecucao,
        string $colunaUnidadeOrcamentaria,
        string $colunaModoAdotado,
        string $colunaUasgAdotada,
        string $literalModoIndividual,
        string $literalModoConsolidado
    ) {
        $this->schema = self::normalizarIdentificador($schema, 'schema');
        $this->tabela = self::normalizarIdentificador($tabela, 'tabela de Planos');
        $this->colunaAnoExecucao = self::normalizarIdentificador(
            $colunaAnoExecucao,
            'coluna de exercício dos Planos'
        );
        $this->colunaUnidadeOrcamentaria = self::normalizarIdentificador(
            $colunaUnidadeOrcamentaria,
            'coluna de UO dos Planos'
        );
        $this->colunaModoAdotado = self::normalizarIdentificador(
            $colunaModoAdotado,
            'coluna do modo adotado'
        );
        $this->colunaUasgAdotada = self::normalizarIdentificador(
            $colunaUasgAdotada,
            'coluna da UASG adotada'
        );
        $this->literalModoIndividual = self::normalizarLiteral(
            $literalModoIndividual,
            'literal persistido do modo individual'
        );
        $this->literalModoConsolidado = self::normalizarLiteral(
            $literalModoConsolidado,
            'literal persistido do modo consolidado'
        );

        if ($this->literalModoIndividual === $this->literalModoConsolidado) {
            throw new InvalidArgumentException(
                'Os literais persistidos dos modos devem ser distintos.'
            );
        }
    }

    public function getTabelaQualificada(): string
    {
        return $this->schema . '.' . $this->tabela;
    }

    public function getColunaAnoExecucao(): string
    {
        return $this->colunaAnoExecucao;
    }

    public function getColunaUnidadeOrcamentaria(): string
    {
        return $this->colunaUnidadeOrcamentaria;
    }

    public function getColunaModoAdotado(): string
    {
        return $this->colunaModoAdotado;
    }

    public function getColunaUasgAdotada(): string
    {
        return $this->colunaUasgAdotada;
    }

    public function getLiteralModoIndividual(): string
    {
        return $this->literalModoIndividual;
    }

    public function getLiteralModoConsolidado(): string
    {
        return $this->literalModoConsolidado;
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

    private static function normalizarLiteral(string $valor, string $campo): string
    {
        $valor = trim($valor);

        if ($valor === '') {
            throw new InvalidArgumentException("O {$campo} é obrigatório.");
        }

        return $valor;
    }
}
