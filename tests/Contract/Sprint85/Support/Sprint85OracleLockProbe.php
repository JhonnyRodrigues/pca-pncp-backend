<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint85\Support;

use Semae\Pca\Composition\PerfilPublicacaoPca;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Oracle\Adodb\FalhaOracleException;
use Tests\Contract\Sprint84\Support\Sprint84GateException;
use Throwable;

final class Sprint85OracleLockProbe
{
    private readonly ConexaoAdodbEmprestada $conexao;
    private readonly string $tabela;
    private readonly string $colunaEscopo;
    private readonly string $colunaAno;
    private readonly string $colunaAlvo;

    public function __construct(object $db, PerfilPublicacaoPca $perfil)
    {
        $this->conexao = new ConexaoAdodbEmprestada($db, $perfil->getCharsetAdodb());
        $mapeamento = $perfil->getControlesConcorrencia();
        $this->tabela = $mapeamento->getTabelaQualificada();
        $this->colunaEscopo = $mapeamento->getColunaEscopo();
        $this->colunaAno = $mapeamento->getColunaAnoExecucao();
        $this->colunaAlvo = $mapeamento->getColunaIdentificadorAlvo();
    }

    public function exigirBloqueio(int $ano, string $uasg): void
    {
        try {
            $this->adquirir($ano, $uasg);
        } catch (FalhaOracleException $falha) {
            $this->desfazer();
            if ($falha->getCodigoOracle() === 'ORA-00054') {
                return;
            }

            throw new Sprint84GateException(
                'SONDA_LOCK_FALHOU',
                'CONTENCAO_ORACLE',
                'Nao foi possivel comprovar a retencao da ancora consolidada.',
                ['codigo_oracle' => $falha->getCodigoOracle()],
                $falha
            );
        } catch (Throwable $falha) {
            $this->desfazer();

            throw new Sprint84GateException(
                'SONDA_LOCK_FALHOU',
                'CONTENCAO_ORACLE',
                'Nao foi possivel comprovar a retencao da ancora consolidada.',
                [],
                $falha
            );
        }

        $this->desfazer();

        throw new Sprint84GateException(
            'ANCORA_NAO_BLOQUEADA',
            'CONTENCAO_ORACLE',
            'A ancora consolidada nao esta retida por outra sessao.'
        );
    }

    public function exigirDisponibilidade(int $ano, string $uasg): void
    {
        try {
            $this->adquirir($ano, $uasg);
        } catch (Throwable $falha) {
            $this->desfazer();

            throw new Sprint84GateException(
                'ANCORA_AINDA_BLOQUEADA',
                'DISPONIBILIDADE_ORACLE',
                'A ancora consolidada ainda nao esta disponivel.',
                [],
                $falha
            );
        }

        $this->desfazer();
    }

    private function adquirir(int $ano, string $uasg): void
    {
        $this->conexao->iniciarTransacao();
        $linhas = $this->conexao->buscarTodos(
            "SELECT {$this->colunaEscopo} AS ESCOPO "
                . "FROM {$this->tabela} "
                . "WHERE {$this->colunaEscopo} = ? "
                . "AND {$this->colunaAno} = ? "
                . "AND {$this->colunaAlvo} = ? FOR UPDATE NOWAIT",
            ['UNIDADE_EXTERNA', $ano, $uasg]
        );

        if (count($linhas) !== 1) {
            throw new Sprint84GateException(
                'ANCORA_CARDINALIDADE_DIVERGENTE',
                'CONTENCAO_ORACLE',
                'A ancora consolidada nao possui cardinalidade unica.'
            );
        }
    }

    private function desfazer(): void
    {
        if (!$this->conexao->estaEmTransacao()) {
            return;
        }

        try {
            $this->conexao->desfazerTransacao();
        } catch (Throwable) {
        }
    }
}
