<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Auditoria;

use InvalidArgumentException;

final class MapeamentoHistoricosOracle
{
    private readonly string $schema;
    private readonly string $tabela;
    private readonly string $colunaInstante;
    private readonly string $colunaProcedimento;
    private readonly string $colunaMensagem;
    private readonly string $colunaRepresentante;
    private readonly string $colunaLotacao;

    public function __construct(
        string $schema,
        string $tabela,
        string $colunaInstante,
        string $colunaProcedimento,
        string $colunaMensagem,
        string $colunaRepresentante,
        string $colunaLotacao
    ) {
        $this->schema = self::normalizarIdentificador($schema, 'schema');
        $this->tabela = self::normalizarIdentificador($tabela, 'tabela de históricos');
        $this->colunaInstante = self::normalizarIdentificador(
            $colunaInstante,
            'coluna de instante'
        );
        $this->colunaProcedimento = self::normalizarIdentificador(
            $colunaProcedimento,
            'coluna de procedimento'
        );
        $this->colunaMensagem = self::normalizarIdentificador(
            $colunaMensagem,
            'coluna de mensagem'
        );
        $this->colunaRepresentante = self::normalizarIdentificador(
            $colunaRepresentante,
            'coluna de Representante'
        );
        $this->colunaLotacao = self::normalizarIdentificador(
            $colunaLotacao,
            'coluna de Lotação'
        );
    }

    public function getTabelaQualificada(): string
    {
        return $this->schema . '.' . $this->tabela;
    }

    public function getColunaInstante(): string
    {
        return $this->colunaInstante;
    }

    public function getColunaProcedimento(): string
    {
        return $this->colunaProcedimento;
    }

    public function getColunaMensagem(): string
    {
        return $this->colunaMensagem;
    }

    public function getColunaRepresentante(): string
    {
        return $this->colunaRepresentante;
    }

    public function getColunaLotacao(): string
    {
        return $this->colunaLotacao;
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
