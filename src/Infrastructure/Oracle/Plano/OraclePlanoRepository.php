<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Plano;

use DateTimeImmutable;
use DateTimeZone;
use LogicException;
use Semae\Pca\Domain\Enums\SituacaoDemanda;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Ports\PlanoRepository;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;

final class OraclePlanoRepository implements PlanoRepository
{
    private readonly MapeadorDadosPlanoOracle $mapeador;

    public function __construct(
        private readonly ConexaoAdodbEmprestada $conexao,
        private readonly MapeamentoPlanosOracle $planos,
        private readonly MapeamentoDemandasOracle $demandas,
        private readonly MapeamentoUnidadesOrcamentariasOracle $unidadesOrcamentarias,
        private readonly MapeamentoLotacoesOracle $lotacoes,
        DateTimeZone $fusoOperacional
    ) {
        $this->mapeador = new MapeadorDadosPlanoOracle($fusoOperacional);
    }

    /** @return IdentidadePlano[] */
    public function listarIdentidades(AnoExecucao $anoExecucao): array
    {
        $linhas = $this->conexao->buscarTodos(
            $this->sqlListarIdentidades(),
            [$anoExecucao->getValor(), $this->unidadesOrcamentarias->getValorAtivo()]
        );
        $chavesFisicas = [];
        $identidadesLogicas = [];
        $identidades = [];

        foreach ($linhas as $linha) {
            $chaveFisica = $this->chaveFisicaUnidade($linha);

            if (isset($chavesFisicas[$chaveFisica])) {
                throw new DadosPlanoOracleInvalidosException(
                    'A Unidade Orçamentária participante não possui cadastro ativo único.'
                );
            }

            $unidadeOrcamentaria = $this->mapeador->mapearUnidadeOrcamentaria($linha);
            $identidadeLogica = $unidadeOrcamentaria->getIdentidade()->getValor();

            if (isset($identidadesLogicas[$identidadeLogica])) {
                throw new DadosPlanoOracleInvalidosException(
                    'A descoberta Oracle retornou identidade lógica duplicada de Plano.'
                );
            }

            $chavesFisicas[$chaveFisica] = true;
            $identidadesLogicas[$identidadeLogica] = true;
            $identidades[] = new IdentidadePlano($unidadeOrcamentaria, $anoExecucao);
        }

        usort(
            $identidades,
            static fn (IdentidadePlano $primeira, IdentidadePlano $segunda): int => strcmp(
                $primeira->getUnidadeOrcamentaria()->getIdentidade()->getValor(),
                $segunda->getUnidadeOrcamentaria()->getIdentidade()->getValor()
            )
        );

        return $identidades;
    }

    public function reconstruirFotografiaVigente(IdentidadePlano $identidade): Plano
    {
        if (!$this->conexao->estaEmTransacao()) {
            throw new LogicException(
                'A reconstrução do Plano exige uma transação Oracle ativa.'
            );
        }

        $chaveUnidade = $identidade->getUnidadeOrcamentaria()->getLotacao()->getId();
        $anoExecucao = $identidade->getAnoExecucao()->getValor();
        $linhasPlano = $this->conexao->buscarTodos(
            $this->sqlBuscarVersaoVigente(),
            [$chaveUnidade, $anoExecucao]
        );

        if (count($linhasPlano) !== 1) {
            throw new DadosPlanoOracleInvalidosException(
                'A identidade do Plano não possui versão vigente única no Oracle.'
            );
        }

        $linhasDemandas = $this->conexao->buscarTodos(
            $this->sqlBuscarDemandasVigentes(),
            [
                $chaveUnidade,
                $anoExecucao,
                SituacaoDemanda::HOMOLOGADA->value,
                SituacaoDemanda::COMPRADA->value,
                SituacaoDemanda::PUBLICADA->value,
            ]
        );
        $demandas = [];

        foreach ($linhasDemandas as $linhaDemanda) {
            $demandas[] = $this->mapeador->mapearDemanda($linhaDemanda, $identidade);
        }

        return $this->mapeador->mapearPlano($linhasPlano[0], $identidade, $demandas);
    }

    public function confirmarSincronizacao(Plano $plano): void
    {
        if (!$this->conexao->estaEmTransacao()) {
            throw new LogicException(
                'A confirmação do Plano exige uma transação Oracle ativa.'
            );
        }

        if ($plano->possuiSincronizacaoConfirmada()) {
            throw new LogicException(
                'A confirmação Oracle exige um Plano pendente de sincronização.'
            );
        }

        $confirmacaoAtual = $this->lerConfirmacaoExata(
            $plano,
            'A confirmação Oracle exige uma linha exata única para a versão do Plano.'
        );

        if ($confirmacaoAtual !== null) {
            throw new DadosPlanoOracleInvalidosException(
                'A versão física do Plano já possui confirmação de sincronização.'
            );
        }

        $parametros = $this->parametrosIdentidadeVersao($plano);
        $this->conexao->executar($this->sqlConfirmarSincronizacao(), $parametros);
        $confirmacaoRegistrada = $this->lerConfirmacaoExata(
            $plano,
            'A releitura Oracle não confirmou a intenção de sincronização.'
        );

        if ($confirmacaoRegistrada === null) {
            throw new DadosPlanoOracleInvalidosException(
                'A releitura Oracle não confirmou a intenção de sincronização.'
            );
        }
    }

    private function sqlListarIdentidades(): string
    {
        $tabelaPlanos = $this->planos->getTabelaQualificada();
        $colunaUoPlano = $this->planos->getColunaUnidadeOrcamentaria();
        $colunaAno = $this->planos->getColunaAnoExecucao();
        $tabelaUo = $this->unidadesOrcamentarias->getTabelaQualificada();
        $colunaRelacao = $this->unidadesOrcamentarias->getColunaRelacaoPlano();
        $colunaIdentidade = $this->unidadesOrcamentarias->getColunaIdentidade();
        $colunaCodigoUasg = $this->unidadesOrcamentarias->getColunaCodigoUasg();
        $colunaDescricaoUasg = $this->unidadesOrcamentarias->getColunaDescricaoUasg();
        $colunaAtivo = $this->unidadesOrcamentarias->getColunaAtivo();
        $tabelaLotacoes = $this->lotacoes->getTabelaQualificada();
        $colunaIdLotacao = $this->lotacoes->getColunaId();
        $colunaDescricaoLotacao = $this->lotacoes->getColunaDescricao();

        return "SELECT BASE.CHAVE_UO_PLANO, U.{$colunaIdentidade} AS IDENTIDADE_UO, "
            . "L.{$colunaIdLotacao} AS ID_LOTACAO, "
            . "L.{$colunaDescricaoLotacao} AS DESCRICAO_LOTACAO, "
            . "L.{$colunaDescricaoLotacao} AS DESCRICAO_UO, "
            . "U.{$colunaCodigoUasg} AS CODIGO_UASG, "
            . "U.{$colunaDescricaoUasg} AS DESCRICAO_UASG "
            . "FROM (SELECT DISTINCT P.{$colunaUoPlano} AS CHAVE_UO_PLANO "
            . "FROM {$tabelaPlanos} P WHERE P.{$colunaAno} = ?) BASE "
            . "LEFT JOIN {$tabelaUo} U "
            . "ON U.{$colunaRelacao} = BASE.CHAVE_UO_PLANO "
            . "AND U.{$colunaAtivo} = ? "
            . "LEFT JOIN {$tabelaLotacoes} L "
            . "ON L.{$colunaIdLotacao} = U.{$colunaIdentidade} "
            . "ORDER BY BASE.CHAVE_UO_PLANO, U.{$colunaIdentidade}";
    }

    private function sqlBuscarVersaoVigente(): string
    {
        $tabelaPlanos = $this->planos->getTabelaQualificada();
        $colunaUo = $this->planos->getColunaUnidadeOrcamentaria();
        $colunaAno = $this->planos->getColunaAnoExecucao();
        $colunaVersao = $this->planos->getColunaVersao();
        $colunaHomologacao = $this->planos->getColunaDataHomologacao();
        $colunaConfirmacao = $this->planos->getColunaDataConfirmacao();

        return "SELECT p.{$colunaVersao} AS VERSAO, "
            . "TO_CHAR(p.{$colunaHomologacao}, 'YYYY-MM-DD HH24:MI:SS') AS DATA_HOMOLOGACAO, "
            . "TO_CHAR(p.{$colunaConfirmacao}, 'YYYY-MM-DD HH24:MI:SS') AS DATA_CONFIRMACAO "
            . "FROM {$tabelaPlanos} p "
            . "WHERE p.{$colunaUo} = ? AND p.{$colunaAno} = ? "
            . "AND p.{$colunaVersao} = (SELECT MAX(pv.{$colunaVersao}) "
            . "FROM {$tabelaPlanos} pv "
            . "WHERE pv.{$colunaUo} = p.{$colunaUo} "
            . "AND pv.{$colunaAno} = p.{$colunaAno})";
    }

    private function sqlBuscarDemandasVigentes(): string
    {
        $tabelaDemandas = $this->demandas->getTabelaQualificada();
        $tabelaPlanos = $this->planos->getTabelaQualificada();
        $colunaIdDemanda = $this->demandas->getColunaId();
        $colunaPlanoOrigem = $this->demandas->getColunaPlanoOrigem();
        $colunaSituacao = $this->demandas->getColunaSituacao();
        $colunaUnidadeRequisitante = $this->demandas->getColunaUnidadeRequisitante();
        $colunaDataDesejada = $this->demandas->getColunaDataDesejada();
        $colunaCategoria = $this->demandas->getColunaCategoria();
        $colunaDescricao = $this->demandas->getColunaDescricao();
        $colunaUnidadeMedida = $this->demandas->getColunaUnidadeMedida();
        $colunaQuantidade = $this->demandas->getColunaQuantidade();
        $colunaValorTotal = $this->demandas->getColunaValorTotal();
        $colunaValorOrcamento = $this->demandas->getColunaValorOrcamentoExercicio();
        $colunaClassificacaoCatalogo = $this->demandas->getColunaClassificacaoCatalogo();
        $colunaCodigoItem = $this->demandas->getColunaCodigoItem();
        $colunaGrupo = $this->demandas->getColunaGrupo();
        $tabelaGrupoRemoto = $this->demandas->getTabelaGrupoRemotoQualificada();
        $colunaCodigoGrupoRemoto = $this->demandas->getColunaCodigoGrupoRemoto();
        $colunaDescricaoGrupoRemoto = $this->demandas->getColunaDescricaoGrupoRemoto();
        $colunaOrigem = $this->demandas->getColunaOrigem();
        $colunaIdPlano = $this->planos->getColunaId();
        $colunaUoPlano = $this->planos->getColunaUnidadeOrcamentaria();
        $colunaAnoPlano = $this->planos->getColunaAnoExecucao();
        $mascaraDecimal = 'FM9999999999990D0000';
        $configuracaoDecimal = "NLS_NUMERIC_CHARACTERS=''.,''";

        return "SELECT D.{$colunaIdDemanda} AS ID_DEMANDA, "
            . "D.{$colunaSituacao} AS SITUACAO, "
            . "D.{$colunaUnidadeRequisitante} AS ID_UNIDADE_REQUISITANTE, "
            . "TO_CHAR(D.{$colunaDataDesejada}, 'YYYY-MM-DD') AS DATA_DESEJADA, "
            . "D.{$colunaCategoria} AS CATEGORIA, "
            . "D.{$colunaDescricao} AS DESCRICAO_DEMANDA, "
            . "D.{$colunaUnidadeMedida} AS UNIDADE_MEDIDA, "
            . "D.{$colunaQuantidade} AS QUANTIDADE, "
            . "TO_CHAR(D.{$colunaValorTotal}, '{$mascaraDecimal}', "
            . "'{$configuracaoDecimal}') AS VALOR_TOTAL, "
            . "TO_CHAR(D.{$colunaValorOrcamento}, '{$mascaraDecimal}', "
            . "'{$configuracaoDecimal}') AS VALOR_ORCAMENTO_EXERCICIO, "
            . "D.{$colunaClassificacaoCatalogo} AS CLASSIFICACAO_CATALOGO, "
            . "D.{$colunaCodigoItem} AS CODIGO_ITEM, "
            . "D.{$colunaGrupo} AS GRUPO, "
            . "MGM.{$colunaDescricaoGrupoRemoto} AS DESCRICAO_GRUPO, "
            . "D.{$colunaOrigem} AS ORIGEM "
            . "FROM {$tabelaDemandas} D "
            . "JOIN {$tabelaPlanos} origem ON origem.{$colunaIdPlano} = D.{$colunaPlanoOrigem} "
            . "LEFT JOIN {$tabelaGrupoRemoto} MGM "
            . "ON MGM.{$colunaCodigoGrupoRemoto} = D.{$colunaGrupo} "
            . "WHERE origem.{$colunaUoPlano} = ? AND origem.{$colunaAnoPlano} = ? "
            . "AND D.{$colunaSituacao} IN (?, ?, ?) "
            . "ORDER BY D.{$colunaIdDemanda}";
    }

    private function sqlLerConfirmacaoExata(): string
    {
        $tabelaPlanos = $this->planos->getTabelaQualificada();
        $colunaUo = $this->planos->getColunaUnidadeOrcamentaria();
        $colunaAno = $this->planos->getColunaAnoExecucao();
        $colunaVersao = $this->planos->getColunaVersao();
        $colunaConfirmacao = $this->planos->getColunaDataConfirmacao();

        return "SELECT TO_CHAR(p.{$colunaConfirmacao}, 'YYYY-MM-DD HH24:MI:SS') "
            . "AS DATA_CONFIRMACAO FROM {$tabelaPlanos} p "
            . "WHERE p.{$colunaUo} = ? AND p.{$colunaAno} = ? "
            . "AND p.{$colunaVersao} = ?";
    }

    private function sqlConfirmarSincronizacao(): string
    {
        $tabelaPlanos = $this->planos->getTabelaQualificada();
        $colunaUo = $this->planos->getColunaUnidadeOrcamentaria();
        $colunaAno = $this->planos->getColunaAnoExecucao();
        $colunaVersao = $this->planos->getColunaVersao();
        $colunaConfirmacao = $this->planos->getColunaDataConfirmacao();

        return "UPDATE {$tabelaPlanos} SET {$colunaConfirmacao} = SYSDATE "
            . "WHERE {$colunaUo} = ? AND {$colunaAno} = ? "
            . "AND {$colunaVersao} = ? AND {$colunaConfirmacao} IS NULL";
    }

    /** @return array{int, int, int} */
    private function parametrosIdentidadeVersao(Plano $plano): array
    {
        return [
            $plano->getUnidadeOrcamentaria()->getLotacao()->getId(),
            $plano->getAnoExecucao()->getValor(),
            $plano->getSequencialVersao(),
        ];
    }

    private function lerConfirmacaoExata(
        Plano $plano,
        string $mensagemCardinalidade
    ): ?DateTimeImmutable {
        $linhas = $this->conexao->buscarTodos(
            $this->sqlLerConfirmacaoExata(),
            $this->parametrosIdentidadeVersao($plano)
        );

        if (count($linhas) !== 1) {
            throw new DadosPlanoOracleInvalidosException($mensagemCardinalidade);
        }

        return $this->mapeador->mapearDataConfirmacao($linhas[0]);
    }

    /** @param array<string, mixed> $linha */
    private function chaveFisicaUnidade(array $linha): string
    {
        $valor = $linha['CHAVE_UO_PLANO'] ?? null;

        if (!is_string($valor) && !is_int($valor)) {
            throw new DadosPlanoOracleInvalidosException(
                'A descoberta Oracle retornou uma relação de Plano inválida.'
            );
        }

        $valor = trim((string) $valor);

        if ($valor === '') {
            throw new DadosPlanoOracleInvalidosException(
                'A descoberta Oracle retornou uma relação de Plano inválida.'
            );
        }

        return $valor;
    }
}
