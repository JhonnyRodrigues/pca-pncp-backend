<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Estrategia;

use InvalidArgumentException;

final class MapeamentoCadastroUasgOracle
{
    private const CARDINALIDADE_EXATA_DO_LOOKUP = 1;

    private readonly string $schema;
    private readonly string $tabela;
    private readonly string $colunaUnidadeOrcamentaria;
    private readonly string $colunaCodigo;
    private readonly string $colunaDescricao;
    private readonly string $colunaAtivo;
    private readonly string $valorAtivo;

    public function __construct(
        string $schema,
        string $tabela,
        string $colunaUnidadeOrcamentaria,
        string $colunaCodigo,
        string $colunaDescricao,
        string $colunaAtivo,
        string $valorAtivo
    ) {
        $this->schema = self::normalizarIdentificador($schema, 'schema');
        $this->tabela = self::normalizarIdentificador($tabela, 'tabela do cadastro de UASG');
        $this->colunaUnidadeOrcamentaria = self::normalizarIdentificador(
            $colunaUnidadeOrcamentaria,
            'coluna de relação com a UO'
        );
        $this->colunaCodigo = self::normalizarIdentificador(
            $colunaCodigo,
            'coluna do código da UASG'
        );
        $this->colunaDescricao = self::normalizarIdentificador(
            $colunaDescricao,
            'coluna da descrição da UASG'
        );
        $this->colunaAtivo = self::normalizarIdentificador(
            $colunaAtivo,
            'coluna do filtro de ativo'
        );
        $this->valorAtivo = trim($valorAtivo);

        if ($this->valorAtivo === '') {
            throw new InvalidArgumentException('O valor físico do filtro de ativo é obrigatório.');
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

    public function getColunaCodigo(): string
    {
        return $this->colunaCodigo;
    }

    public function getColunaDescricao(): string
    {
        return $this->colunaDescricao;
    }

    public function getColunaAtivo(): string
    {
        return $this->colunaAtivo;
    }

    public function getValorAtivo(): string
    {
        return $this->valorAtivo;
    }

    public function getCardinalidadeExataDoLookup(): int
    {
        return self::CARDINALIDADE_EXATA_DO_LOOKUP;
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
