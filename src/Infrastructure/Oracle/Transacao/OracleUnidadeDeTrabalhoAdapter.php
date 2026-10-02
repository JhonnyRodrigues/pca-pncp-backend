<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Transacao;

use LogicException;
use Semae\Pca\Application\Enums\EscopoUnidadeTrabalho;
use Semae\Pca\Application\Ports\UnidadeDeTrabalhoPort;
use Semae\Pca\Application\Results\ResultadoFinalizacaoUnidadeTrabalho;
use Semae\Pca\Application\Results\ResultadoInicioUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\ChaveUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Oracle\Adodb\FalhaOracleException;
use Throwable;

final class OracleUnidadeDeTrabalhoAdapter implements UnidadeDeTrabalhoPort
{
    public function __construct(
        private readonly ConexaoAdodbEmprestada $conexao,
        private readonly MapeamentoControlesConcorrenciaOracle $mapeamento
    ) {}

    public function iniciar(ChaveUnidadeTrabalho $chave): ResultadoInicioUnidadeTrabalho
    {
        if ($this->conexao->estaEmTransacao()) {
            return ResultadoInicioUnidadeTrabalho::falhaSistemica(new FalhaSegura(
                'UNIDADE_TRABALHO_JA_INICIADA',
                'Já existe uma unidade de trabalho Oracle ativa.'
            ));
        }

        try {
            [$escopo, $anoExecucao, $identificadorAlvo] = $this->traduzirChave($chave);
            $this->provisionarControleConcorrencia(
                $escopo,
                $anoExecucao,
                $identificadorAlvo
            );
            $this->conexao->iniciarTransacao();

            $linhas = $this->conexao->buscarTodos(
                $this->sqlAquisicao(),
                [$escopo, $anoExecucao, $identificadorAlvo]
            );

            if (count($linhas) !== 1) {
                throw new LogicException(
                    'A linha de controle de concorrência Oracle não possui cardinalidade única.'
                );
            }

            return ResultadoInicioUnidadeTrabalho::iniciada();
        } catch (FalhaOracleException $exception) {
            $desfeita = $this->desfazerTransacaoAtiva();

            if (!$desfeita) {
                return $this->falhaInicio();
            }

            return match ($exception->getCodigoOracle()) {
                'ORA-00054' => ResultadoInicioUnidadeTrabalho::bloqueada(new FalhaSegura(
                    'UNIDADE_TRABALHO_BLOQUEADA',
                    'A unidade de trabalho já está em processamento.'
                )),
                'ORA-00942' => ResultadoInicioUnidadeTrabalho::falhaSistemica(
                    new FalhaSegura(
                        'TABELA_ANCORAS_INDISPONIVEL',
                        'A tabela técnica de controles de concorrência Oracle não está disponível.'
                    )
                ),
                default => $this->falhaInicio(),
            };
        } catch (Throwable) {
            $this->desfazerTransacaoAtiva();

            return $this->falhaInicio();
        }
    }

    public function confirmar(): ResultadoFinalizacaoUnidadeTrabalho
    {
        if (!$this->conexao->estaEmTransacao()) {
            return ResultadoFinalizacaoUnidadeTrabalho::falhaSistemica(new FalhaSegura(
                'UNIDADE_TRABALHO_NAO_INICIADA',
                'Não existe unidade de trabalho Oracle ativa para confirmar.'
            ));
        }

        try {
            $this->conexao->confirmarTransacao();

            return ResultadoFinalizacaoUnidadeTrabalho::concluida();
        } catch (Throwable) {
            return ResultadoFinalizacaoUnidadeTrabalho::falhaSistemica(new FalhaSegura(
                'CONFIRMACAO_UNIDADE_TRABALHO_FALHOU',
                'Não foi possível confirmar a unidade de trabalho Oracle.'
            ));
        }
    }

    public function desfazer(): ResultadoFinalizacaoUnidadeTrabalho
    {
        if (!$this->conexao->estaEmTransacao()) {
            return ResultadoFinalizacaoUnidadeTrabalho::concluida();
        }

        try {
            $this->conexao->desfazerTransacao();

            return ResultadoFinalizacaoUnidadeTrabalho::concluida();
        } catch (Throwable) {
            return ResultadoFinalizacaoUnidadeTrabalho::falhaSistemica(new FalhaSegura(
                'DESFAZIMENTO_UNIDADE_TRABALHO_FALHOU',
                'Não foi possível desfazer a unidade de trabalho Oracle.'
            ));
        }
    }

    /** @return array{string, int, string} */
    private function traduzirChave(ChaveUnidadeTrabalho $chave): array
    {
        $escopo = $chave->getEscopo();
        $identificadorAlvo = $this->mapeamento->getIdentificadorAlvoEstrategiaAnual();

        if ($escopo === EscopoUnidadeTrabalho::UNIDADE_EXTERNA) {
            $unidadeExterna = $chave->getUnidadeExterna();

            if ($unidadeExterna === null) {
                throw new LogicException('Chave de unidade externa sem unidade correspondente.');
            }

            $identificadorAlvo = $unidadeExterna->getUasgExterna()->getCodigo();
        }

        return [
            $escopo->value,
            $chave->getAnoExecucao()->getValor(),
            $identificadorAlvo,
        ];
    }

    private function provisionarControleConcorrencia(
        string $escopo,
        int $anoExecucao,
        string $identificadorAlvo
    ): void {
        $this->conexao->iniciarTransacao();

        try {
            $this->conexao->executar(
                $this->sqlProvisionamento(),
                [
                    $escopo,
                    $anoExecucao,
                    $identificadorAlvo,
                    $escopo,
                    $anoExecucao,
                    $identificadorAlvo,
                ]
            );
            $this->conexao->confirmarTransacao();
        } catch (FalhaOracleException $exception) {
            $desfeita = $this->desfazerTransacaoAtiva();

            if ($exception->getCodigoOracle() === 'ORA-00001' && $desfeita) {
                return;
            }

            throw $exception;
        } catch (Throwable $exception) {
            $this->desfazerTransacaoAtiva();

            throw $exception;
        }
    }

    private function sqlProvisionamento(): string
    {
        $tabela = $this->mapeamento->getTabelaQualificada();
        $escopo = $this->mapeamento->getColunaEscopo();
        $ano = $this->mapeamento->getColunaAnoExecucao();
        $alvo = $this->mapeamento->getColunaIdentificadorAlvo();

        return "INSERT INTO {$tabela} (
            {$escopo}, 
            {$ano}, 
            {$alvo}
        ) " . 'SELECT ?, ?, ? FROM DUAL WHERE NOT EXISTS ('
            . "SELECT 1 FROM {$tabela} 
            WHERE 
                {$escopo} = ? 
                AND {$ano} = ? 
                AND {$alvo} = ?"
        . ')';
    }

    private function sqlAquisicao(): string
    {
        $tabela = $this->mapeamento->getTabelaQualificada();
        $escopo = $this->mapeamento->getColunaEscopo();
        $ano = $this->mapeamento->getColunaAnoExecucao();
        $alvo = $this->mapeamento->getColunaIdentificadorAlvo();

        return "SELECT {$escopo} 
        FROM {$tabela} 
        WHERE 
            {$escopo} = ? 
            AND {$ano} = ? 
            AND {$alvo} = ? "
        . 'FOR UPDATE NOWAIT';
    }

    private function desfazerTransacaoAtiva(): bool
    {
        if (!$this->conexao->estaEmTransacao()) {
            return true;
        }

        try {
            $this->conexao->desfazerTransacao();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function falhaInicio(): ResultadoInicioUnidadeTrabalho
    {
        return ResultadoInicioUnidadeTrabalho::falhaSistemica(new FalhaSegura(
            'INICIO_UNIDADE_TRABALHO_FALHOU',
            'Não foi possível iniciar a unidade de trabalho Oracle.'
        ));
    }
}
