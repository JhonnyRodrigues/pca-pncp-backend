<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Plano;

use InvalidArgumentException;

final class MapeamentoDemandasOracle
{
    private const DBLINK_GRUPO_AUTORIZADO = 'READ_ORCL';

    private readonly string $schema;
    private readonly string $tabela;
    private readonly string $colunaId;
    private readonly string $colunaPlanoOrigem;
    private readonly string $colunaSituacao;
    private readonly string $colunaUnidadeRequisitante;
    private readonly string $colunaDataDesejada;
    private readonly string $colunaCategoria;
    private readonly string $colunaDescricao;
    private readonly string $colunaUnidadeMedida;
    private readonly string $colunaQuantidade;
    private readonly string $colunaValorTotal;
    private readonly string $colunaValorOrcamentoExercicio;
    private readonly string $colunaClassificacaoCatalogo;
    private readonly string $colunaCodigoItem;
    private readonly string $colunaGrupo;
    private readonly string $schemaGrupoRemoto;
    private readonly string $tabelaGrupoRemoto;
    private readonly string $dblinkGrupoRemoto;
    private readonly string $colunaCodigoGrupoRemoto;
    private readonly string $colunaDescricaoGrupoRemoto;
    private readonly string $colunaOrigem;

    /**
     * @param array{
     *     schema: string,
     *     tabela: string,
     *     dblink: string,
     *     colunaCodigo: string,
     *     colunaDescricao: string
     * } $grupoRemoto
     */
    public function __construct(
        string $schema,
        string $tabela,
        string $colunaId,
        string $colunaPlanoOrigem,
        string $colunaSituacao,
        string $colunaUnidadeRequisitante,
        string $colunaDataDesejada,
        string $colunaCategoria,
        string $colunaDescricao,
        string $colunaUnidadeMedida,
        string $colunaQuantidade,
        string $colunaValorTotal,
        string $colunaValorOrcamentoExercicio,
        string $colunaClassificacaoCatalogo,
        string $colunaCodigoItem,
        string $colunaGrupo,
        array $grupoRemoto,
        string $colunaOrigem
    ) {
        $campos = [
            'schema' => $schema,
            'tabela de Demandas' => $tabela,
            'coluna de ID da Demanda' => $colunaId,
            'coluna do Plano de origem' => $colunaPlanoOrigem,
            'coluna de situação da Demanda' => $colunaSituacao,
            'coluna da unidade requisitante' => $colunaUnidadeRequisitante,
            'coluna da data desejada' => $colunaDataDesejada,
            'coluna da categoria' => $colunaCategoria,
            'coluna da descrição da Demanda' => $colunaDescricao,
            'coluna da unidade de medida' => $colunaUnidadeMedida,
            'coluna da quantidade' => $colunaQuantidade,
            'coluna do valor total' => $colunaValorTotal,
            'coluna do orçamento do exercício' => $colunaValorOrcamentoExercicio,
            'coluna da classificação do catálogo' => $colunaClassificacaoCatalogo,
            'coluna do código do item' => $colunaCodigoItem,
            'coluna do grupo' => $colunaGrupo,
            'coluna da origem' => $colunaOrigem,
        ];

        $normalizados = [];

        foreach ($campos as $campo => $valor) {
            $normalizados[] = self::normalizarIdentificador($valor, $campo);
        }

        [
            $this->schema,
            $this->tabela,
            $this->colunaId,
            $this->colunaPlanoOrigem,
            $this->colunaSituacao,
            $this->colunaUnidadeRequisitante,
            $this->colunaDataDesejada,
            $this->colunaCategoria,
            $this->colunaDescricao,
            $this->colunaUnidadeMedida,
            $this->colunaQuantidade,
            $this->colunaValorTotal,
            $this->colunaValorOrcamentoExercicio,
            $this->colunaClassificacaoCatalogo,
            $this->colunaCodigoItem,
            $this->colunaGrupo,
            $this->colunaOrigem,
        ] = $normalizados;

        [
            $this->schemaGrupoRemoto,
            $this->tabelaGrupoRemoto,
            $this->dblinkGrupoRemoto,
            $this->colunaCodigoGrupoRemoto,
            $this->colunaDescricaoGrupoRemoto,
        ] = self::normalizarGrupoRemoto($grupoRemoto);
    }

    public function getTabelaQualificada(): string
    {
        return $this->schema . '.' . $this->tabela;
    }

    public function getColunaId(): string
    {
        return $this->colunaId;
    }

    public function getColunaPlanoOrigem(): string
    {
        return $this->colunaPlanoOrigem;
    }

    public function getColunaSituacao(): string
    {
        return $this->colunaSituacao;
    }

    public function getColunaUnidadeRequisitante(): string
    {
        return $this->colunaUnidadeRequisitante;
    }

    public function getColunaDataDesejada(): string
    {
        return $this->colunaDataDesejada;
    }

    public function getColunaCategoria(): string
    {
        return $this->colunaCategoria;
    }

    public function getColunaDescricao(): string
    {
        return $this->colunaDescricao;
    }

    public function getColunaUnidadeMedida(): string
    {
        return $this->colunaUnidadeMedida;
    }

    public function getColunaQuantidade(): string
    {
        return $this->colunaQuantidade;
    }

    public function getColunaValorTotal(): string
    {
        return $this->colunaValorTotal;
    }

    public function getColunaValorOrcamentoExercicio(): string
    {
        return $this->colunaValorOrcamentoExercicio;
    }

    public function getColunaClassificacaoCatalogo(): string
    {
        return $this->colunaClassificacaoCatalogo;
    }

    public function getColunaCodigoItem(): string
    {
        return $this->colunaCodigoItem;
    }

    public function getColunaGrupo(): string
    {
        return $this->colunaGrupo;
    }

    public function getTabelaGrupoRemotoQualificada(): string
    {
        return $this->schemaGrupoRemoto
            . '.' . $this->tabelaGrupoRemoto
            . '@' . $this->dblinkGrupoRemoto;
    }

    public function getColunaCodigoGrupoRemoto(): string
    {
        return $this->colunaCodigoGrupoRemoto;
    }

    public function getColunaDescricaoGrupoRemoto(): string
    {
        return $this->colunaDescricaoGrupoRemoto;
    }

    public function getColunaOrigem(): string
    {
        return $this->colunaOrigem;
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

    /**
     * @param array<string, mixed> $mapeamento
     * @return array{string, string, string, string, string}
     */
    private static function normalizarGrupoRemoto(array $mapeamento): array
    {
        $campos = [
            'schema' => 'schema remoto do grupo',
            'tabela' => 'tabela remota do grupo',
            'dblink' => 'DBLINK remoto do grupo',
            'colunaCodigo' => 'coluna remota do código do grupo',
            'colunaDescricao' => 'coluna remota da descrição do grupo',
        ];

        if (
            array_diff_key($campos, $mapeamento) !== []
            || array_diff_key($mapeamento, $campos) !== []
        ) {
            throw new InvalidArgumentException(
                'O mapeamento remoto do grupo deve declarar somente os campos obrigatórios.'
            );
        }

        $normalizados = [];

        foreach ($campos as $chave => $campo) {
            $valor = $mapeamento[$chave];

            if (!is_string($valor)) {
                throw new InvalidArgumentException(
                    "O mapeamento da {$campo} contém um identificador Oracle inválido."
                );
            }

            $normalizados[] = self::normalizarIdentificador($valor, $campo);
        }

        if ($normalizados[2] !== self::DBLINK_GRUPO_AUTORIZADO) {
            throw new InvalidArgumentException(
                'O DBLINK remoto do grupo não pertence ao contrato autorizado.'
            );
        }

        return $normalizados;
    }
}
