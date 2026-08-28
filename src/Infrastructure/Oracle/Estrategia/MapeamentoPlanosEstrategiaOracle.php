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
    private readonly string $colunaEstrategiaAdotada;
    private readonly string $colunaUasgAdotada;
    private readonly string $literalEstrategiaIndividual;
    private readonly string $literalEstrategiaConsolidada;

    public function __construct(
        string $schema,
        string $tabela,
        string $colunaAnoExecucao,
        string $colunaUnidadeOrcamentaria,
        string $colunaEstrategiaAdotada,
        string $colunaUasgAdotada,
        string $literalEstrategiaIndividual,
        string $literalEstrategiaConsolidada
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
        $this->colunaEstrategiaAdotada = self::normalizarIdentificador(
            $colunaEstrategiaAdotada,
            'coluna da estratégia adotada'
        );
        $this->colunaUasgAdotada = self::normalizarIdentificador(
            $colunaUasgAdotada,
            'coluna da UASG adotada'
        );
        $this->literalEstrategiaIndividual = self::normalizarLiteral(
            $literalEstrategiaIndividual,
            'literal persistido da estratégia individual'
        );
        $this->literalEstrategiaConsolidada = self::normalizarLiteral(
            $literalEstrategiaConsolidada,
            'literal persistido da estratégia consolidada'
        );

        if ($this->literalEstrategiaIndividual === $this->literalEstrategiaConsolidada) {
            throw new InvalidArgumentException(
                'Os literais persistidos das estratégias devem ser distintos.'
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

    public function getColunaEstrategiaAdotada(): string
    {
        return $this->colunaEstrategiaAdotada;
    }

    public function getColunaUasgAdotada(): string
    {
        return $this->colunaUasgAdotada;
    }

    public function getLiteralEstrategiaIndividual(): string
    {
        return $this->literalEstrategiaIndividual;
    }

    public function getLiteralEstrategiaConsolidada(): string
    {
        return $this->literalEstrategiaConsolidada;
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
