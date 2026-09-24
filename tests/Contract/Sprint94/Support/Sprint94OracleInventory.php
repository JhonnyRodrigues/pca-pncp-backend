<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint94\Support;

use Semae\Pca\Composition\PerfilPublicacaoPca;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Throwable;

final class Sprint94OracleInventory
{
    /** @param int[] $years @return array<string, mixed> */
    public function collect(object $adodb, PerfilPublicacaoPca $profile, array $years): array
    {
        $connection = new ConexaoAdodbEmprestada(
            new Sprint94ReadOnlyAdodbGuard($adodb),
            $profile->getCharsetAdodb()
        );
        $result = [
            'estrategia_corrente' => $this->read(
                'ORACLE_ESTRATEGIA_INCONCLUSIVA',
                fn (): array => $this->currentStrategy($connection, $profile)
            ),
            'exercicios' => [],
        ];
        foreach ($years as $year) {
            $result['exercicios'][(string) $year] = [
                'unidades' => $this->read(
                    "ORACLE_UNIDADES_{$year}_INCONCLUSIVAS",
                    fn (): array => $this->units($connection, $profile, $year)
                ),
                'versoes_e_projecao' => $this->read(
                    "ORACLE_VERSOES_{$year}_INCONCLUSIVAS",
                    fn (): array => $this->versions($connection, $profile, $year)
                ),
                'demandas_vigentes' => $this->read(
                    "ORACLE_DEMANDAS_{$year}_INCONCLUSIVAS",
                    fn (): array => $this->currentDemands($connection, $profile, $year)
                ),
            ];
        }

        return $result;
    }

    /** @param callable(): array<int, array<string, mixed>> $operation @return array<int, array<string, mixed>> */
    private function read(string $errorCode, callable $operation): array
    {
        try {
            return $operation();
        } catch (Throwable $failure) {
            throw new Sprint94InventoryException(
                $errorCode,
                'Uma leitura Oracle do inventario nao foi concluida.',
                $failure
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function currentStrategy(ConexaoAdodbEmprestada $connection, PerfilPublicacaoPca $profile): array
    {
        $m = $profile->getConfiguracaoEstrategia();
        return $connection->buscarTodos(
            "SELECT c.{$m->getColunaChave()} AS CHAVE_CONFIGURACAO, "
                . "c.{$m->getColunaValor()} AS VALOR_CONFIGURACAO "
                . "FROM {$m->getTabelaQualificada()} c "
                . "WHERE c.{$m->getColunaChave()} IN (?, ?) "
                . "ORDER BY c.{$m->getColunaChave()}",
            [$m->getChaveEstrategia(), $m->getChaveUasgConsolidadora()]
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function units(ConexaoAdodbEmprestada $connection, PerfilPublicacaoPca $profile, int $year): array
    {
        $p = $profile->getPlanos();
        $u = $profile->getUnidadesOrcamentarias();
        return $connection->buscarTodos(
            "SELECT base.CHAVE_UO_PLANO, u.{$u->getColunaIdentidade()} AS IDENTIDADE_UO, "
                . "u.{$u->getColunaCodigoUasg()} AS CODIGO_UASG, "
                . "u.{$u->getColunaDescricaoUasg()} AS DESCRICAO_UASG "
                . "FROM (SELECT DISTINCT p.{$p->getColunaUnidadeOrcamentaria()} AS CHAVE_UO_PLANO "
                . "FROM {$p->getTabelaQualificada()} p WHERE p.{$p->getColunaAnoExecucao()} = ?) base "
                . "LEFT JOIN {$u->getTabelaQualificada()} u "
                . "ON u.{$u->getColunaRelacaoPlano()} = base.CHAVE_UO_PLANO "
                . "AND u.{$u->getColunaAtivo()} = ? "
                . "ORDER BY base.CHAVE_UO_PLANO, u.{$u->getColunaIdentidade()}",
            [$year, $u->getValorAtivo()]
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function versions(ConexaoAdodbEmprestada $connection, PerfilPublicacaoPca $profile, int $year): array
    {
        $p = $profile->getPlanos();
        $s = $profile->getEstadoOperacional();
        return $connection->buscarTodos(
            "SELECT p.{$p->getColunaId()} AS ID_PLANO, "
                . "p.{$p->getColunaUnidadeOrcamentaria()} AS FK_UNIDADE_ORCAMENTARIA, "
                . "p.{$p->getColunaVersao()} AS VERSAO, "
                . "TO_CHAR(p.{$p->getColunaDataConfirmacao()}, 'YYYY-MM-DD HH24:MI:SS') AS DATA_CONFIRMACAO, "
                . "p.{$s->getColunaEstrategia()} AS ESTRATEGIA_ADOTADA_PUBLICACAO, "
                . "p.{$s->getColunaUasg()} AS UASG_ADOTADA, "
                . "p.{$s->getColunaEstadoPublicacao()} AS ESTADO_PUBLICACAO, "
                . "p.{$s->getColunaSequencialPublicacaoPncp()} AS SEQUENCIAL_PUBLICACAO_PNCP "
                . "FROM {$p->getTabelaQualificada()} p "
                . "WHERE p.{$p->getColunaAnoExecucao()} = ? "
                . "ORDER BY p.{$p->getColunaUnidadeOrcamentaria()}, p.{$p->getColunaVersao()}, p.{$p->getColunaId()}",
            [$year]
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function currentDemands(ConexaoAdodbEmprestada $connection, PerfilPublicacaoPca $profile, int $year): array
    {
        $p = $profile->getPlanos();
        $d = $profile->getDemandas();
        return $connection->buscarTodos(
            "SELECT origem.{$p->getColunaUnidadeOrcamentaria()} AS FK_UNIDADE_ORCAMENTARIA, "
                . "d.{$d->getColunaId()} AS ID_DEMANDA "
                . "FROM {$d->getTabelaQualificada()} d "
                . "JOIN {$p->getTabelaQualificada()} origem "
                . "ON origem.{$p->getColunaId()} = d.{$d->getColunaPlanoOrigem()} "
                . "WHERE origem.{$p->getColunaAnoExecucao()} = ? "
                . "AND d.{$d->getColunaSituacao()} IN (?, ?, ?) "
                . "ORDER BY origem.{$p->getColunaUnidadeOrcamentaria()}, d.{$d->getColunaId()}",
            [$year, 2, 3, 5]
        );
    }
}
