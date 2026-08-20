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
    private readonly string $chaveModo;
    private readonly string $chaveUasgConsolidadora;
    private readonly string $literalModoIndividual;
    private readonly string $literalModoConsolidado;

    public function __construct(
        string $schema,
        string $tabela,
        string $colunaChave,
        string $colunaValor,
        string $chaveModo,
        string $chaveUasgConsolidadora,
        string $literalModoIndividual,
        string $literalModoConsolidado
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
        $this->chaveModo = self::normalizarLiteral($chaveModo, 'chave do modo');
        $this->chaveUasgConsolidadora = self::normalizarLiteral(
            $chaveUasgConsolidadora,
            'chave da UASG consolidadora'
        );
        $this->literalModoIndividual = self::normalizarLiteral(
            $literalModoIndividual,
            'literal do modo individual'
        );
        $this->literalModoConsolidado = self::normalizarLiteral(
            $literalModoConsolidado,
            'literal do modo consolidado'
        );

        if ($this->chaveModo === $this->chaveUasgConsolidadora) {
            throw new InvalidArgumentException(
                'As chaves físicas da estratégia devem ser distintas.'
            );
        }

        if ($this->literalModoIndividual === $this->literalModoConsolidado) {
            throw new InvalidArgumentException(
                'Os literais físicos dos modos devem ser distintos.'
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

    public function getChaveModo(): string
    {
        return $this->chaveModo;
    }

    public function getChaveUasgConsolidadora(): string
    {
        return $this->chaveUasgConsolidadora;
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
