<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\EstadoOperacional;

use InvalidArgumentException;

final class MapeamentoEstadoOperacionalOracle
{
    private readonly string $schema;
    private readonly string $tabela;
    private readonly string $colunaUnidadeOrcamentaria;
    private readonly string $colunaAnoExecucao;
    private readonly string $colunaModo;
    private readonly string $colunaUasg;
    private readonly string $colunaEstado;
    private readonly string $colunaSequencial;
    private readonly string $literalModoIndividual;
    private readonly string $literalModoConsolidado;
    private readonly string $literalNaoPublicado;
    private readonly string $literalPublicado;
    private readonly string $literalExcluido;

    public function __construct(
        string $schema,
        string $tabela,
        string $colunaUnidadeOrcamentaria,
        string $colunaAnoExecucao,
        string $colunaModo,
        string $colunaUasg,
        string $colunaEstado,
        string $colunaSequencial,
        string $literalModoIndividual,
        string $literalModoConsolidado,
        string $literalNaoPublicado,
        string $literalPublicado,
        string $literalExcluido
    ) {
        $this->schema = self::normalizarIdentificador($schema, 'schema');
        $this->tabela = self::normalizarIdentificador($tabela, 'tabela de Planos');
        $this->colunaUnidadeOrcamentaria = self::normalizarIdentificador(
            $colunaUnidadeOrcamentaria,
            'coluna de UO'
        );
        $this->colunaAnoExecucao = self::normalizarIdentificador(
            $colunaAnoExecucao,
            'coluna de exercício'
        );
        $this->colunaModo = self::normalizarIdentificador($colunaModo, 'coluna de modo');
        $this->colunaUasg = self::normalizarIdentificador($colunaUasg, 'coluna de UASG');
        $this->colunaEstado = self::normalizarIdentificador($colunaEstado, 'coluna de estado');
        $this->colunaSequencial = self::normalizarIdentificador(
            $colunaSequencial,
            'coluna de sequencial'
        );
        $this->literalModoIndividual = self::normalizarLiteral(
            $literalModoIndividual,
            'literal do modo individual'
        );
        $this->literalModoConsolidado = self::normalizarLiteral(
            $literalModoConsolidado,
            'literal do modo consolidado'
        );
        $this->literalNaoPublicado = self::normalizarLiteral(
            $literalNaoPublicado,
            'literal do estado não publicado'
        );
        $this->literalPublicado = self::normalizarLiteral(
            $literalPublicado,
            'literal do estado publicado'
        );
        $this->literalExcluido = self::normalizarLiteral(
            $literalExcluido,
            'literal do estado excluído'
        );

        if ($this->literalModoIndividual === $this->literalModoConsolidado) {
            throw new InvalidArgumentException('Os literais de modo devem ser distintos.');
        }

        if (count(array_unique([
            $this->literalNaoPublicado,
            $this->literalPublicado,
            $this->literalExcluido,
        ], SORT_STRING)) !== 3) {
            throw new InvalidArgumentException('Os literais de estado devem ser distintos.');
        }
    }

    public function getTabelaQualificada(): string
    {
        return $this->schema . '.' . $this->tabela;
    }

    public function getColunaUnidadeOrcamentaria(): string
    {
        return $this->colunaUnidadeOrcamentaria;
    }

    public function getColunaAnoExecucao(): string
    {
        return $this->colunaAnoExecucao;
    }

    public function getColunaModo(): string
    {
        return $this->colunaModo;
    }

    public function getColunaUasg(): string
    {
        return $this->colunaUasg;
    }

    public function getColunaEstado(): string
    {
        return $this->colunaEstado;
    }

    public function getColunaSequencial(): string
    {
        return $this->colunaSequencial;
    }

    public function getLiteralModoIndividual(): string
    {
        return $this->literalModoIndividual;
    }

    public function getLiteralModoConsolidado(): string
    {
        return $this->literalModoConsolidado;
    }

    public function getLiteralNaoPublicado(): string
    {
        return $this->literalNaoPublicado;
    }

    public function getLiteralPublicado(): string
    {
        return $this->literalPublicado;
    }

    public function getLiteralExcluido(): string
    {
        return $this->literalExcluido;
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
