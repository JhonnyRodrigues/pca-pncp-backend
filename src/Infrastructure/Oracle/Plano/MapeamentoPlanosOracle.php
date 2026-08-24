<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Plano;

use InvalidArgumentException;

final class MapeamentoPlanosOracle
{
    private readonly string $schema;
    private readonly string $tabela;
    private readonly string $colunaId;
    private readonly string $colunaUnidadeOrcamentaria;
    private readonly string $colunaAnoExecucao;
    private readonly string $colunaVersao;
    private readonly string $colunaDataHomologacao;
    private readonly string $colunaDataConfirmacao;

    public function __construct(
        string $schema,
        string $tabela,
        string $colunaId,
        string $colunaUnidadeOrcamentaria,
        string $colunaAnoExecucao,
        string $colunaVersao,
        string $colunaDataHomologacao,
        string $colunaDataConfirmacao
    ) {
        $this->schema = self::normalizarIdentificador($schema, 'schema');
        $this->tabela = self::normalizarIdentificador($tabela, 'tabela de Planos');
        $this->colunaId = self::normalizarIdentificador($colunaId, 'coluna de ID do Plano');
        $this->colunaUnidadeOrcamentaria = self::normalizarIdentificador(
            $colunaUnidadeOrcamentaria,
            'coluna de UO do Plano'
        );
        $this->colunaAnoExecucao = self::normalizarIdentificador(
            $colunaAnoExecucao,
            'coluna de exercício do Plano'
        );
        $this->colunaVersao = self::normalizarIdentificador(
            $colunaVersao,
            'coluna de versão do Plano'
        );
        $this->colunaDataHomologacao = self::normalizarIdentificador(
            $colunaDataHomologacao,
            'coluna de homologação do Plano'
        );
        $this->colunaDataConfirmacao = self::normalizarIdentificador(
            $colunaDataConfirmacao,
            'coluna de confirmação do Plano'
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

    public function getColunaUnidadeOrcamentaria(): string
    {
        return $this->colunaUnidadeOrcamentaria;
    }

    public function getColunaAnoExecucao(): string
    {
        return $this->colunaAnoExecucao;
    }

    public function getColunaVersao(): string
    {
        return $this->colunaVersao;
    }

    public function getColunaDataHomologacao(): string
    {
        return $this->colunaDataHomologacao;
    }

    public function getColunaDataConfirmacao(): string
    {
        return $this->colunaDataConfirmacao;
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
