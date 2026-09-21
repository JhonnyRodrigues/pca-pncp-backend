<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84\Support;

use Semae\Pca\Composition\PerfilPublicacaoPca;

final class OracleCheckpointQueries
{
    /** @param array<string, mixed> $configuration */
    public function __construct(
        private readonly PerfilPublicacaoPca $profile,
        private readonly array $configuration,
        private readonly string $authenticatedLogin
    ) {}

    /**
     * @return array<string, array{
     *     sql: string,
     *     binds: array<int, int|string>,
     *     columns: string[],
     *     expected_cardinality: array<string, int>
     * }>
     */
    public function all(): array
    {
        $year = (int) $this->configuration['ano'];
        $unitIds = array_map(
            static fn (array $unit): int => (int) $unit['id_unidade_orcamentaria'],
            $this->configuration['unidades']
        );
        $versionCount = array_sum(array_column($this->configuration['unidades'], 'quantidade_versoes'));
        $photographCount = array_sum(
            array_column($this->configuration['unidades'], 'quantidade_fotografia')
        );
        $demandCount = array_sum(array_column($this->configuration['unidades'], 'quantidade_demandas'));

        return [
            'estrategia_corrente' => $this->currentStrategy(),
            'representante' => $this->representative(),
            'lotacao_usuario' => $this->userDepartment(),
            'unidades_orcamentarias' => $this->budgetUnits($year, count($unitIds)),
            'todas_versoes' => $this->allVersions($year, $unitIds, $versionCount),
            'versoes_vigentes' => $this->currentVersions($year, $unitIds),
            'fotografia_vigente' => $this->currentPhotograph($year, $unitIds, $photographCount),
            'ciclo_demandas' => $this->demandLifecycle($year, $unitIds, $demandCount),
            'projecao_operacional' => $this->operationalProjection(
                $year,
                $unitIds,
                $versionCount
            ),
            'auditoria' => $this->audit($year),
            'controles_concorrencia' => $this->concurrencyControls($year),
        ];
    }

    /** @return array<string, mixed> */
    private function currentStrategy(): array
    {
        $mapping = $this->profile->getConfiguracaoEstrategia();
        $keys = [$mapping->getChaveEstrategia()];
        if ($this->configuration['modo'] === 'CONSOLIDADO') {
            $keys[] = $mapping->getChaveUasgConsolidadora();
        }
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));

        return $this->query(
            "SELECT c.{$mapping->getColunaChave()} AS CHAVE_CONFIGURACAO, "
                . "c.{$mapping->getColunaValor()} AS VALOR_CONFIGURACAO, "
                . "c.{$mapping->getColunaDescricao()} AS DESCRICAO_CONFIGURACAO "
                . "FROM {$mapping->getTabelaQualificada()} c "
                . "WHERE c.{$mapping->getColunaChave()} IN ({$placeholders}) "
                . "ORDER BY c.{$mapping->getColunaChave()}",
            $keys,
            ['CHAVE_CONFIGURACAO', 'VALOR_CONFIGURACAO', 'DESCRICAO_CONFIGURACAO'],
            ['exact' => count($keys)]
        );
    }

    /** @return array<string, mixed> */
    private function representative(): array
    {
        $mapping = $this->profile->getRepresentantes();

        return $this->query(
            "SELECT f.{$mapping->getColunaId()} AS ID_REPRESENTANTE, "
                . "f.{$mapping->getColunaNome()} AS NOME_REPRESENTANTE "
                . "FROM {$mapping->getTabelaQualificada()} f "
                . "WHERE UPPER(f.{$mapping->getColunaLogin()}) = UPPER(?) "
                . "AND f.{$mapping->getColunaAtivo()} = ? "
                . "ORDER BY f.{$mapping->getColunaId()}",
            [$this->authenticatedLogin, $mapping->getValorAtivo()],
            ['ID_REPRESENTANTE', 'NOME_REPRESENTANTE'],
            ['exact' => 1]
        );
    }

    /** @return array<string, mixed> */
    private function userDepartment(): array
    {
        $representatives = $this->profile->getRepresentantes();
        $departments = $this->profile->getLotacoesUsuario();

        return $this->query(
            "SELECT l.{$departments->getColunaIdLotacao()} AS ID_LOTACAO, "
                . "l.{$departments->getColunaNomeLotacao()} AS NOME_LOTACAO "
                . "FROM {$representatives->getTabelaQualificada()} f "
                . "JOIN {$departments->getTabelaVinculosQualificada()} fl "
                . "ON fl.{$departments->getColunaFuncionarioVinculo()} = "
                . "f.{$representatives->getColunaId()} "
                . "JOIN {$departments->getTabelaLotacoesQualificada()} l "
                . "ON l.{$departments->getColunaIdLotacao()} = "
                . "fl.{$departments->getColunaLotacaoVinculo()} "
                . "WHERE UPPER(f.{$representatives->getColunaLogin()}) = UPPER(?) "
                . "AND f.{$representatives->getColunaAtivo()} = ? "
                . "AND fl.{$departments->getColunaFimVinculo()} IS NULL "
                . "AND l.{$departments->getColunaAtivoLotacao()} = ? "
                . "ORDER BY l.{$departments->getColunaIdLotacao()}",
            [
                $this->authenticatedLogin,
                $representatives->getValorAtivo(),
                $departments->getValorAtivo(),
            ],
            ['ID_LOTACAO', 'NOME_LOTACAO'],
            ['exact' => 1]
        );
    }

    /** @return array<string, mixed> */
    private function budgetUnits(int $year, int $expected): array
    {
        $plans = $this->profile->getPlanos();
        $units = $this->profile->getUnidadesOrcamentarias();
        $departments = $this->profile->getLotacoes();

        return $this->query(
            "SELECT base.CHAVE_UO_PLANO, u.{$units->getColunaIdentidade()} AS IDENTIDADE_UO, "
                . "l.{$departments->getColunaId()} AS ID_LOTACAO, "
                . "l.{$departments->getColunaDescricao()} AS DESCRICAO_LOTACAO, "
                . "l.{$departments->getColunaDescricao()} AS DESCRICAO_UO, "
                . "u.{$units->getColunaCodigoUasg()} AS CODIGO_UASG, "
                . "u.{$units->getColunaDescricaoUasg()} AS DESCRICAO_UASG "
                . "FROM (SELECT DISTINCT p.{$plans->getColunaUnidadeOrcamentaria()} "
                . "AS CHAVE_UO_PLANO FROM {$plans->getTabelaQualificada()} p "
                . "WHERE p.{$plans->getColunaAnoExecucao()} = ?) base "
                . "LEFT JOIN {$units->getTabelaQualificada()} u "
                . "ON u.{$units->getColunaRelacaoPlano()} = base.CHAVE_UO_PLANO "
                . "AND u.{$units->getColunaAtivo()} = ? "
                . "LEFT JOIN {$departments->getTabelaQualificada()} l "
                . "ON l.{$departments->getColunaId()} = u.{$units->getColunaIdentidade()} "
                . "ORDER BY base.CHAVE_UO_PLANO, u.{$units->getColunaIdentidade()}",
            [$year, $units->getValorAtivo()],
            [
                'CHAVE_UO_PLANO',
                'IDENTIDADE_UO',
                'ID_LOTACAO',
                'DESCRICAO_LOTACAO',
                'DESCRICAO_UO',
                'CODIGO_UASG',
                'DESCRICAO_UASG',
            ],
            ['exact' => $expected]
        );
    }

    /** @param int[] $unitIds @return array<string, mixed> */
    private function allVersions(int $year, array $unitIds, int $expected): array
    {
        $plans = $this->profile->getPlanos();
        $state = $this->profile->getEstadoOperacional();
        $filter = $this->unitFilter($plans->getColunaUnidadeOrcamentaria(), count($unitIds));

        return $this->query(
            "SELECT p.{$plans->getColunaId()} AS ID_PLANO, "
                . "p.{$plans->getColunaUnidadeOrcamentaria()} AS FK_UNIDADE_ORCAMENTARIA, "
                . "p.{$plans->getColunaAnoExecucao()} AS ANO_EXERCICIO, "
                . "p.{$plans->getColunaVersao()} AS VERSAO, "
                . "TO_CHAR(p.{$plans->getColunaDataHomologacao()}, "
                . "'YYYY-MM-DD HH24:MI:SS') AS DATA_HOMOLOGACAO, "
                . "TO_CHAR(p.{$plans->getColunaDataConfirmacao()}, "
                . "'YYYY-MM-DD HH24:MI:SS') AS DATA_PUBLICACAO, "
                . "p.{$state->getColunaEstrategia()} AS ESTRATEGIA_ADOTADA_PUBLICACAO, "
                . "p.{$state->getColunaUasg()} AS UASG_ADOTADA, "
                . "p.{$state->getColunaEstadoPublicacao()} AS ESTADO_PUBLICACAO, "
                . "p.{$state->getColunaSequencialPublicacaoPncp()} AS SEQUENCIAL_PUBLICACAO_PNCP "
                . "FROM {$plans->getTabelaQualificada()} p "
                . "WHERE p.{$plans->getColunaAnoExecucao()} = ? AND {$filter} "
                . "ORDER BY p.{$plans->getColunaUnidadeOrcamentaria()}, "
                . "p.{$plans->getColunaVersao()}, p.{$plans->getColunaId()}",
            [$year, ...$unitIds],
            $this->versionColumns(),
            ['exact' => $expected]
        );
    }

    /** @param int[] $unitIds @return array<string, mixed> */
    private function currentVersions(int $year, array $unitIds): array
    {
        $plans = $this->profile->getPlanos();
        $state = $this->profile->getEstadoOperacional();
        $unitColumn = $plans->getColunaUnidadeOrcamentaria();
        $filter = $this->unitFilter($unitColumn, count($unitIds));

        return $this->query(
            "SELECT p.{$plans->getColunaId()} AS ID_PLANO, "
                . "p.{$unitColumn} AS FK_UNIDADE_ORCAMENTARIA, "
                . "p.{$plans->getColunaAnoExecucao()} AS ANO_EXERCICIO, "
                . "p.{$plans->getColunaVersao()} AS VERSAO, "
                . "TO_CHAR(p.{$plans->getColunaDataHomologacao()}, "
                . "'YYYY-MM-DD HH24:MI:SS') AS DATA_HOMOLOGACAO, "
                . "TO_CHAR(p.{$plans->getColunaDataConfirmacao()}, "
                . "'YYYY-MM-DD HH24:MI:SS') AS DATA_PUBLICACAO, "
                . "p.{$state->getColunaEstrategia()} AS ESTRATEGIA_ADOTADA_PUBLICACAO, "
                . "p.{$state->getColunaUasg()} AS UASG_ADOTADA, "
                . "p.{$state->getColunaEstadoPublicacao()} AS ESTADO_PUBLICACAO, "
                . "p.{$state->getColunaSequencialPublicacaoPncp()} AS SEQUENCIAL_PUBLICACAO_PNCP "
                . "FROM {$plans->getTabelaQualificada()} p "
                . "WHERE p.{$plans->getColunaAnoExecucao()} = ? AND {$filter} "
                . "AND p.{$plans->getColunaVersao()} = (SELECT MAX(pv.{$plans->getColunaVersao()}) "
                . "FROM {$plans->getTabelaQualificada()} pv "
                . "WHERE pv.{$plans->getColunaAnoExecucao()} = p.{$plans->getColunaAnoExecucao()} "
                . "AND pv.{$unitColumn} = p.{$unitColumn}) "
                . "ORDER BY p.{$unitColumn}, p.{$plans->getColunaId()}",
            [$year, ...$unitIds],
            $this->versionColumns(),
            ['exact' => count($unitIds)]
        );
    }

    /** @param int[] $unitIds @return array<string, mixed> */
    private function currentPhotograph(int $year, array $unitIds, int $expected): array
    {
        $demands = $this->profile->getDemandas();
        $plans = $this->profile->getPlanos();
        $unitColumn = $plans->getColunaUnidadeOrcamentaria();
        $filter = $this->unitFilter($unitColumn, count($unitIds), 'origem');

        return $this->query(
            "SELECT d.{$demands->getColunaId()} AS ID_DEMANDA, "
                . "d.{$demands->getColunaSituacao()} AS SITUACAO, "
                . "d.{$demands->getColunaUnidadeRequisitante()} AS ID_UNIDADE_REQUISITANTE, "
                . "TO_CHAR(d.{$demands->getColunaDataDesejada()}, 'YYYY-MM-DD') AS DATA_DESEJADA, "
                . "d.{$demands->getColunaCategoria()} AS CATEGORIA, "
                . "d.{$demands->getColunaDescricao()} AS DESCRICAO_DEMANDA, "
                . "d.{$demands->getColunaUnidadeMedida()} AS UNIDADE_MEDIDA, "
                . "d.{$demands->getColunaQuantidade()} AS QUANTIDADE, "
                . "TO_CHAR(d.{$demands->getColunaValorTotal()}, 'FM9999999999990D0000', "
                . "'NLS_NUMERIC_CHARACTERS=''.,''') AS VALOR_TOTAL, "
                . "TO_CHAR(d.{$demands->getColunaValorOrcamentoExercicio()}, "
                . "'FM9999999999990D0000', 'NLS_NUMERIC_CHARACTERS=''.,''') "
                . "AS VALOR_ORCAMENTO_EXERCICIO, "
                . "2 AS CATALOGO, "
                . "d.{$demands->getColunaClassificacaoCatalogo()} "
                . "AS CLASSIFICACAO_CATALOGO, "
                . "d.{$demands->getColunaCodigoItem()} AS CODIGO_ITEM, "
                . "d.{$demands->getColunaGrupo()} AS GRUPO, "
                . "mgm.{$demands->getColunaDescricaoGrupoRemoto()} AS DESCRICAO_GRUPO, "
                . "d.{$demands->getColunaOrigem()} AS ORIGEM, "
                . "origem.{$unitColumn} AS FK_UNIDADE_ORCAMENTARIA "
                . "FROM {$demands->getTabelaQualificada()} d "
                . "JOIN {$plans->getTabelaQualificada()} origem "
                . "ON origem.{$plans->getColunaId()} = d.{$demands->getColunaPlanoOrigem()} "
                . "LEFT JOIN {$demands->getTabelaGrupoRemotoQualificada()} mgm "
                . "ON mgm.{$demands->getColunaCodigoGrupoRemoto()} = d.{$demands->getColunaGrupo()} "
                . "WHERE origem.{$plans->getColunaAnoExecucao()} = ? AND {$filter} "
                . "AND d.{$demands->getColunaSituacao()} IN (?, ?, ?) "
                . "ORDER BY origem.{$unitColumn}, d.{$demands->getColunaId()}",
            [$year, ...$unitIds, 2, 3, 5],
            [
                'ID_DEMANDA', 'SITUACAO', 'ID_UNIDADE_REQUISITANTE', 'DATA_DESEJADA',
                'CATEGORIA', 'DESCRICAO_DEMANDA', 'UNIDADE_MEDIDA', 'QUANTIDADE',
                'VALOR_TOTAL', 'VALOR_ORCAMENTO_EXERCICIO', 'CATALOGO',
                'CLASSIFICACAO_CATALOGO', 'CODIGO_ITEM',
                'GRUPO', 'DESCRICAO_GRUPO', 'ORIGEM', 'FK_UNIDADE_ORCAMENTARIA',
            ],
            ['exact' => $expected]
        );
    }

    /** @param int[] $unitIds @return array<string, mixed> */
    private function demandLifecycle(int $year, array $unitIds, int $expected): array
    {
        $demands = $this->profile->getDemandas();
        $plans = $this->profile->getPlanos();
        $unitColumn = $plans->getColunaUnidadeOrcamentaria();
        $filter = $this->unitFilter($unitColumn, count($unitIds), 'p');

        return $this->query(
            "SELECT d.{$demands->getColunaId()} AS ID_DEMANDA, "
                . "d.{$demands->getColunaPlanoOrigem()} AS FK_PLANO, "
                . "p.{$unitColumn} AS FK_UNIDADE_ORCAMENTARIA, "
                . "p.{$plans->getColunaVersao()} AS VERSAO_ORIGEM, "
                . "d.{$demands->getColunaSituacao()} AS SITUACAO "
                . "FROM {$demands->getTabelaQualificada()} d "
                . "JOIN {$plans->getTabelaQualificada()} p "
                . "ON p.{$plans->getColunaId()} = d.{$demands->getColunaPlanoOrigem()} "
                . "WHERE p.{$plans->getColunaAnoExecucao()} = ? AND {$filter} "
                . "ORDER BY p.{$unitColumn}, d.{$demands->getColunaId()}",
            [$year, ...$unitIds],
            ['ID_DEMANDA', 'FK_PLANO', 'FK_UNIDADE_ORCAMENTARIA', 'VERSAO_ORIGEM', 'SITUACAO'],
            ['exact' => $expected]
        );
    }

    /** @param int[] $unitIds @return array<string, mixed> */
    private function operationalProjection(int $year, array $unitIds, int $expected): array
    {
        $plans = $this->profile->getPlanos();
        $state = $this->profile->getEstadoOperacional();
        $filter = $this->unitFilter(
            $plans->getColunaUnidadeOrcamentaria(),
            count($unitIds)
        );

        return $this->query(
            "SELECT p.{$plans->getColunaUnidadeOrcamentaria()} "
                . "AS FK_UNIDADE_ORCAMENTARIA, "
                . "p.{$plans->getColunaVersao()} AS VERSAO, "
                . "p.{$state->getColunaEstrategia()} AS ESTRATEGIA_ADOTADA_PUBLICACAO, "
                . "p.{$state->getColunaUasg()} AS UASG_ADOTADA, "
                . "p.{$state->getColunaEstadoPublicacao()} AS ESTADO_PUBLICACAO, "
                . "p.{$state->getColunaSequencialPublicacaoPncp()} AS SEQUENCIAL_PUBLICACAO_PNCP "
                . "FROM {$plans->getTabelaQualificada()} p "
                . "WHERE p.{$plans->getColunaAnoExecucao()} = ? AND {$filter} "
                . "ORDER BY p.{$plans->getColunaUnidadeOrcamentaria()}, "
                . "p.{$plans->getColunaVersao()}, p.{$plans->getColunaId()}",
            [$year, ...$unitIds],
            [
                'FK_UNIDADE_ORCAMENTARIA', 'VERSAO', 'ESTRATEGIA_ADOTADA_PUBLICACAO',
                'UASG_ADOTADA', 'ESTADO_PUBLICACAO', 'SEQUENCIAL_PUBLICACAO_PNCP',
            ],
            ['exact' => $expected]
        );
    }

    /** @return array<string, mixed> */
    private function audit(int $year): array
    {
        $audit = $this->profile->getHistoricos();
        $representatives = $this->profile->getRepresentantes();

        return $this->query(
            "SELECT TO_CHAR(h.{$audit->getColunaInstante()}, 'YYYY-MM-DD HH24:MI:SS') "
                . "AS DATA_REGISTRO, h.{$audit->getColunaProcedimento()} AS PROCEDIMENTO, "
                . "h.{$audit->getColunaMensagem()} AS MENSAGEM, "
                . "h.{$audit->getColunaRepresentante()} AS FK_REPRESENTANTE, "
                . "h.{$audit->getColunaLotacao()} AS FK_LOTACAO "
                . "FROM {$audit->getTabelaQualificada()} h "
                . "JOIN {$representatives->getTabelaQualificada()} f "
                . "ON f.{$representatives->getColunaId()} = h.{$audit->getColunaRepresentante()} "
                . "WHERE UPPER(f.{$representatives->getColunaLogin()}) = UPPER(?) "
                . "AND UPPER(h.{$audit->getColunaMensagem()}) LIKE UPPER(?) "
                . "ORDER BY h.{$audit->getColunaInstante()}, "
                . "h.{$audit->getColunaProcedimento()}, h.{$audit->getColunaMensagem()}",
            [$this->authenticatedLogin, 'Exercício: ' . $year . ';%'],
            ['DATA_REGISTRO', 'PROCEDIMENTO', 'MENSAGEM', 'FK_REPRESENTANTE', 'FK_LOTACAO'],
            ['exact' => (int) $this->configuration['baseline_auditoria']]
        );
    }

    /** @return array<string, mixed> */
    private function concurrencyControls(int $year): array
    {
        $mapping = $this->profile->getControlesConcorrencia();

        return $this->query(
            "SELECT c.{$mapping->getColunaEscopo()} AS ESCOPO, "
                . "c.{$mapping->getColunaAnoExecucao()} AS ANO_EXECUCAO, "
                . "c.{$mapping->getColunaIdentificadorAlvo()} AS IDENTIFICADOR_ALVO "
                . "FROM {$mapping->getTabelaQualificada()} c "
                . "WHERE c.{$mapping->getColunaAnoExecucao()} = ? "
                . "ORDER BY c.{$mapping->getColunaEscopo()}, "
                . "c.{$mapping->getColunaIdentificadorAlvo()}",
            [$year],
            ['ESCOPO', 'ANO_EXECUCAO', 'IDENTIFICADOR_ALVO'],
            ['min' => 0]
        );
    }

    /** @return string[] */
    private function versionColumns(): array
    {
        return [
            'ID_PLANO',
            'FK_UNIDADE_ORCAMENTARIA',
            'ANO_EXERCICIO',
            'VERSAO',
            'DATA_HOMOLOGACAO',
            'DATA_PUBLICACAO',
            'ESTRATEGIA_ADOTADA_PUBLICACAO',
            'UASG_ADOTADA',
            'ESTADO_PUBLICACAO',
            'SEQUENCIAL_PUBLICACAO_PNCP',
        ];
    }

    private function unitFilter(string $column, int $count, string $alias = 'p'): string
    {
        $placeholders = implode(', ', array_fill(0, $count, '?'));

        return "{$alias}.{$column} IN ({$placeholders})";
    }

    /**
     * @param array<int, int|string> $binds
     * @param string[] $columns
     * @param array<string, int> $cardinality
     * @return array<string, mixed>
     */
    private function query(
        string $sql,
        array $binds,
        array $columns,
        array $cardinality
    ): array {
        return [
            'sql' => $sql,
            'binds' => $binds,
            'columns' => $columns,
            'expected_cardinality' => $cardinality,
        ];
    }
}
