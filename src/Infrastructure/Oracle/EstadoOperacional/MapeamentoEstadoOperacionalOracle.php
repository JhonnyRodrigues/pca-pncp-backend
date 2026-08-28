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
    private readonly string $colunaEstrategia;
    private readonly string $colunaUasg;
    private readonly string $colunaEstadoPublicacao;
    private readonly string $colunaSequencialPublicacaoPncp;
    private readonly string $literalEstrategiaIndividual;
    private readonly string $literalEstrategiaConsolidada;
    private readonly string $literalNaoPublicado;
    private readonly string $literalPublicado;
    private readonly string $literalExcluido;

    public function __construct(
        string $schema,
        string $tabela,
        string $colunaUnidadeOrcamentaria,
        string $colunaAnoExecucao,
        string $colunaEstrategia,
        string $colunaUasg,
        string $colunaEstadoPublicacao,
        string $colunaSequencialPublicacaoPncp,
        string $literalEstrategiaIndividual,
        string $literalEstrategiaConsolidada,
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
        $this->colunaEstrategia = self::normalizarIdentificador(
            $colunaEstrategia,
            'coluna de estratégia adotada'
        );
        $this->colunaUasg = self::normalizarIdentificador(
            $colunaUasg,
            'coluna de UASG adotada'
        );
        $this->colunaEstadoPublicacao = self::normalizarIdentificador(
            $colunaEstadoPublicacao,
            'coluna de estado da publicação'
        );
        $this->colunaSequencialPublicacaoPncp = self::normalizarIdentificador(
            $colunaSequencialPublicacaoPncp,
            'coluna do sequencial da publicação no PNCP'
        );
        $this->literalEstrategiaIndividual = self::normalizarLiteral(
            $literalEstrategiaIndividual,
            'literal da estratégia individual'
        );
        $this->literalEstrategiaConsolidada = self::normalizarLiteral(
            $literalEstrategiaConsolidada,
            'literal da estratégia consolidada'
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

        if ($this->literalEstrategiaIndividual === $this->literalEstrategiaConsolidada) {
            throw new InvalidArgumentException(
                'Os literais de estratégia devem ser distintos.'
            );
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

    public function getColunaEstrategia(): string
    {
        return $this->colunaEstrategia;
    }

    public function getColunaUasg(): string
    {
        return $this->colunaUasg;
    }

    public function getColunaEstadoPublicacao(): string
    {
        return $this->colunaEstadoPublicacao;
    }

    public function getColunaSequencialPublicacaoPncp(): string
    {
        return $this->colunaSequencialPublicacaoPncp;
    }

    public function getLiteralEstrategiaIndividual(): string
    {
        return $this->literalEstrategiaIndividual;
    }

    public function getLiteralEstrategiaConsolidada(): string
    {
        return $this->literalEstrategiaConsolidada;
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
