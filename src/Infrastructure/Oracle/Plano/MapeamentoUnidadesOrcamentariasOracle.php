<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Plano;

use InvalidArgumentException;

final class MapeamentoUnidadesOrcamentariasOracle
{
    private readonly string $schema;
    private readonly string $tabela;
    private readonly string $colunaRelacaoPlano;
    private readonly string $colunaIdentidade;
    private readonly string $colunaLotacao;
    private readonly string $colunaDescricaoLotacao;
    private readonly string $colunaDescricao;
    private readonly string $colunaCodigoUasg;
    private readonly string $colunaDescricaoUasg;
    private readonly string $colunaAtivo;
    private readonly string $valorAtivo;

    public function __construct(
        string $schema,
        string $tabela,
        string $colunaRelacaoPlano,
        string $colunaIdentidade,
        string $colunaLotacao,
        string $colunaDescricaoLotacao,
        string $colunaDescricao,
        string $colunaCodigoUasg,
        string $colunaDescricaoUasg,
        string $colunaAtivo,
        string $valorAtivo
    ) {
        $this->schema = self::normalizarIdentificador($schema, 'schema');
        $this->tabela = self::normalizarIdentificador(
            $tabela,
            'tabela de Unidades Orçamentárias'
        );
        $this->colunaRelacaoPlano = self::normalizarIdentificador(
            $colunaRelacaoPlano,
            'coluna de relação com o Plano'
        );
        $this->colunaIdentidade = self::normalizarIdentificador(
            $colunaIdentidade,
            'coluna de identidade da UO'
        );
        $this->colunaLotacao = self::normalizarIdentificador(
            $colunaLotacao,
            'coluna da Lotação'
        );
        $this->colunaDescricaoLotacao = self::normalizarIdentificador(
            $colunaDescricaoLotacao,
            'coluna da descrição da Lotação'
        );
        $this->colunaDescricao = self::normalizarIdentificador(
            $colunaDescricao,
            'coluna da descrição da UO'
        );
        $this->colunaCodigoUasg = self::normalizarIdentificador(
            $colunaCodigoUasg,
            'coluna do código da UASG'
        );
        $this->colunaDescricaoUasg = self::normalizarIdentificador(
            $colunaDescricaoUasg,
            'coluna da descrição da UASG'
        );
        $this->colunaAtivo = self::normalizarIdentificador(
            $colunaAtivo,
            'coluna do filtro de ativo'
        );
        $this->valorAtivo = trim($valorAtivo);

        if ($this->valorAtivo === '') {
            throw new InvalidArgumentException(
                'O valor físico do filtro de UO ativa é obrigatório.'
            );
        }
    }

    public function getTabelaQualificada(): string
    {
        return $this->schema . '.' . $this->tabela;
    }

    public function getColunaRelacaoPlano(): string
    {
        return $this->colunaRelacaoPlano;
    }

    public function getColunaIdentidade(): string
    {
        return $this->colunaIdentidade;
    }

    public function getColunaLotacao(): string
    {
        return $this->colunaLotacao;
    }

    public function getColunaDescricaoLotacao(): string
    {
        return $this->colunaDescricaoLotacao;
    }

    public function getColunaDescricao(): string
    {
        return $this->colunaDescricao;
    }

    public function getColunaCodigoUasg(): string
    {
        return $this->colunaCodigoUasg;
    }

    public function getColunaDescricaoUasg(): string
    {
        return $this->colunaDescricaoUasg;
    }

    public function getColunaAtivo(): string
    {
        return $this->colunaAtivo;
    }

    public function getValorAtivo(): string
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
}
