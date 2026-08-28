<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Estrategia;

use InvalidArgumentException;

final class MapeamentoConfiguracaoEstrategiaOracle
{
    private readonly string $schema;
    private readonly string $tabela;
    private readonly string $colunaChave;
    private readonly string $colunaValor;
    private readonly string $colunaDescricao;
    private readonly string $chaveEstrategia;
    private readonly string $chaveUasgConsolidadora;
    private readonly string $literalEstrategiaIndividual;
    private readonly string $literalEstrategiaConsolidada;

    public function __construct(
        string $schema,
        string $tabela,
        string $colunaChave,
        string $colunaValor,
        string $colunaDescricao,
        string $chaveEstrategia,
        string $chaveUasgConsolidadora,
        string $literalEstrategiaIndividual,
        string $literalEstrategiaConsolidada
    ) {
        $this->schema = self::normalizarIdentificador($schema, 'schema');
        $this->tabela = self::normalizarIdentificador($tabela, 'tabela de configuração');
        $this->colunaChave = self::normalizarIdentificador(
            $colunaChave,
            'coluna da chave de configuração'
        );
        $this->colunaValor = self::normalizarIdentificador(
            $colunaValor,
            'coluna do valor de configuração'
        );
        $this->colunaDescricao = self::normalizarIdentificador(
            $colunaDescricao,
            'coluna da descrição de configuração'
        );
        $this->chaveEstrategia = self::normalizarLiteral(
            $chaveEstrategia,
            'chave da estratégia'
        );
        $this->chaveUasgConsolidadora = self::normalizarLiteral(
            $chaveUasgConsolidadora,
            'chave da UASG consolidadora'
        );
        $this->literalEstrategiaIndividual = self::normalizarLiteral(
            $literalEstrategiaIndividual,
            'literal da estratégia individual'
        );
        $this->literalEstrategiaConsolidada = self::normalizarLiteral(
            $literalEstrategiaConsolidada,
            'literal da estratégia consolidada'
        );

        if ($this->chaveEstrategia === $this->chaveUasgConsolidadora) {
            throw new InvalidArgumentException(
                'As chaves físicas da estratégia devem ser distintas.'
            );
        }

        if ($this->literalEstrategiaIndividual === $this->literalEstrategiaConsolidada) {
            throw new InvalidArgumentException(
                'Os literais físicos das estratégias devem ser distintos.'
            );
        }
    }

    public function getTabelaQualificada(): string
    {
        return $this->schema . '.' . $this->tabela;
    }

    public function getColunaChave(): string
    {
        return $this->colunaChave;
    }

    public function getColunaValor(): string
    {
        return $this->colunaValor;
    }

    public function getColunaDescricao(): string
    {
        return $this->colunaDescricao;
    }

    public function getChaveEstrategia(): string
    {
        return $this->chaveEstrategia;
    }

    public function getChaveUasgConsolidadora(): string
    {
        return $this->chaveUasgConsolidadora;
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
