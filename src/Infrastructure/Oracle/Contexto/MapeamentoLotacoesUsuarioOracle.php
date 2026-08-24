<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Contexto;

use InvalidArgumentException;

final class MapeamentoLotacoesUsuarioOracle
{
    private readonly string $schema;
    private readonly string $tabelaVinculos;
    private readonly string $tabelaLotacoes;
    private readonly string $colunaFuncionarioVinculo;
    private readonly string $colunaLotacaoVinculo;
    private readonly string $colunaFimVinculo;
    private readonly string $colunaIdLotacao;
    private readonly string $colunaNomeLotacao;
    private readonly string $colunaAtivoLotacao;
    private readonly int|string $valorAtivo;

    public function __construct(
        string $schema,
        string $tabelaVinculos,
        string $tabelaLotacoes,
        string $colunaFuncionarioVinculo,
        string $colunaLotacaoVinculo,
        string $colunaFimVinculo,
        string $colunaIdLotacao,
        string $colunaNomeLotacao,
        string $colunaAtivoLotacao,
        int|string $valorAtivo
    ) {
        $this->schema = self::normalizarIdentificador($schema, 'schema');
        $this->tabelaVinculos = self::normalizarIdentificador(
            $tabelaVinculos,
            'tabela de vínculos de Lotação'
        );
        $this->tabelaLotacoes = self::normalizarIdentificador(
            $tabelaLotacoes,
            'tabela de Lotações'
        );
        $this->colunaFuncionarioVinculo = self::normalizarIdentificador(
            $colunaFuncionarioVinculo,
            'coluna de funcionário do vínculo'
        );
        $this->colunaLotacaoVinculo = self::normalizarIdentificador(
            $colunaLotacaoVinculo,
            'coluna de Lotação do vínculo'
        );
        $this->colunaFimVinculo = self::normalizarIdentificador(
            $colunaFimVinculo,
            'coluna de fim do vínculo'
        );
        $this->colunaIdLotacao = self::normalizarIdentificador(
            $colunaIdLotacao,
            'coluna de ID da Lotação'
        );
        $this->colunaNomeLotacao = self::normalizarIdentificador(
            $colunaNomeLotacao,
            'coluna de nome da Lotação'
        );
        $this->colunaAtivoLotacao = self::normalizarIdentificador(
            $colunaAtivoLotacao,
            'coluna de Lotação ativa'
        );
        $this->valorAtivo = self::normalizarLiteral($valorAtivo, 'literal de Lotação ativa');
    }

    public function getTabelaVinculosQualificada(): string
    {
        return $this->schema . '.' . $this->tabelaVinculos;
    }

    public function getTabelaLotacoesQualificada(): string
    {
        return $this->schema . '.' . $this->tabelaLotacoes;
    }

    public function getColunaFuncionarioVinculo(): string
    {
        return $this->colunaFuncionarioVinculo;
    }

    public function getColunaLotacaoVinculo(): string
    {
        return $this->colunaLotacaoVinculo;
    }

    public function getColunaFimVinculo(): string
    {
        return $this->colunaFimVinculo;
    }

    public function getColunaIdLotacao(): string
    {
        return $this->colunaIdLotacao;
    }

    public function getColunaNomeLotacao(): string
    {
        return $this->colunaNomeLotacao;
    }

    public function getColunaAtivoLotacao(): string
    {
        return $this->colunaAtivoLotacao;
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
