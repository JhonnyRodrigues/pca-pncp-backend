<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Contexto;

use Semae\Pca\Application\Ports\ContextoUsuarioPort;
use Semae\Pca\Application\Results\ResultadoResolucaoContextoUsuario;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Representante;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Throwable;

final class OracleContextoUsuarioAdapter implements ContextoUsuarioPort
{
    private readonly string $login;

    public function __construct(
        private readonly ConexaoAdodbEmprestada $conexao,
        private readonly MapeamentoRepresentantesOracle $representantes,
        private readonly MapeamentoLotacoesUsuarioOracle $lotacoes,
        string $login
    ) {
        $this->login = trim($login);
    }

    public function resolver(): ResultadoResolucaoContextoUsuario
    {
        if ($this->login === '') {
            return $this->naoResolvido(
                'LOGIN_USUARIO_AUSENTE',
                'O login autenticado não foi informado para resolver o contexto do usuário.'
            );
        }

        try {
            $linhasRepresentante = $this->conexao->buscarTodos(
                $this->sqlRepresentante(),
                [$this->login, $this->representantes->getValorAtivo()]
            );

            if (count($linhasRepresentante) !== 1) {
                return $this->naoResolvido(
                    'REPRESENTANTE_USUARIO_NAO_RESOLVIDO',
                    'O login autenticado não corresponde a um Representante ativo único.'
                );
            }

            try {
                $idRepresentante = $this->inteiroPositivo(
                    $linhasRepresentante[0]['ID_REPRESENTANTE'] ?? null
                );
                $nomeRepresentante = $this->textoObrigatorio(
                    $linhasRepresentante[0]['NOME_REPRESENTANTE'] ?? null
                );
            } catch (DadosContextoUsuarioOracleInvalidosException) {
                return $this->naoResolvido(
                    'DADOS_REPRESENTANTE_INVALIDOS',
                    'Os dados do Representante autenticado são inválidos.'
                );
            }

            $linhasLotacao = $this->conexao->buscarTodos(
                $this->sqlLotacao(),
                [$idRepresentante, $this->lotacoes->getValorAtivo()]
            );

            if (count($linhasLotacao) !== 1) {
                return $this->naoResolvido(
                    'LOTACAO_USUARIO_NAO_RESOLVIDA',
                    'O Representante autenticado não possui uma Lotação vigente e ativa única.'
                );
            }

            try {
                $idLotacao = $this->inteiroPositivo($linhasLotacao[0]['ID_LOTACAO'] ?? null);
                $nomeLotacao = $this->textoObrigatorio(
                    $linhasLotacao[0]['NOME_LOTACAO'] ?? null
                );
            } catch (DadosContextoUsuarioOracleInvalidosException) {
                return $this->naoResolvido(
                    'DADOS_LOTACAO_INVALIDOS',
                    'Os dados da Lotação vigente do usuário são inválidos.'
                );
            }

            return ResultadoResolucaoContextoUsuario::resolvido(new ContextoUsuario(
                new Representante($idRepresentante, $nomeRepresentante),
                new Lotacao($idLotacao, $nomeLotacao)
            ));
        } catch (Throwable) {
            return ResultadoResolucaoContextoUsuario::falhaSistemica(new FalhaSegura(
                'RESOLUCAO_CONTEXTO_USUARIO_FALHOU',
                'Não foi possível resolver o contexto do usuário autenticado.'
            ));
        }
    }

    private function sqlRepresentante(): string
    {
        $tabela = $this->representantes->getTabelaQualificada();
        $login = $this->representantes->getColunaLogin();
        $id = $this->representantes->getColunaId();
        $nome = $this->representantes->getColunaNome();
        $ativo = $this->representantes->getColunaAtivo();

        return "SELECT 
            f.{$id} AS ID_REPRESENTANTE, 
            f.{$nome} AS NOME_REPRESENTANTE
        FROM {$tabela} f 
        WHERE 
            UPPER(f.{$login}) = UPPER(?) 
            AND f.{$ativo} = ?";
    }

    private function sqlLotacao(): string
    {
        $tabelaVinculos = $this->lotacoes->getTabelaVinculosQualificada();
        $tabelaLotacoes = $this->lotacoes->getTabelaLotacoesQualificada();
        $funcionario = $this->lotacoes->getColunaFuncionarioVinculo();
        $lotacaoVinculo = $this->lotacoes->getColunaLotacaoVinculo();
        $fim = $this->lotacoes->getColunaFimVinculo();
        $id = $this->lotacoes->getColunaIdLotacao();
        $nome = $this->lotacoes->getColunaNomeLotacao();
        $ativo = $this->lotacoes->getColunaAtivoLotacao();

        return "SELECT 
            l.{$id} AS ID_LOTACAO, 
            l.{$nome} AS NOME_LOTACAO
        FROM 
            {$tabelaVinculos} fl 
            JOIN {$tabelaLotacoes} l ON l.{$id} = fl.{$lotacaoVinculo} 
        WHERE 
            fl.{$funcionario} = ? 
            AND fl.{$fim} IS NULL 
            AND l.{$ativo} = ?";
    }

    private function naoResolvido(
        string $codigo,
        string $mensagem
    ): ResultadoResolucaoContextoUsuario {
        return ResultadoResolucaoContextoUsuario::naoResolvido(
            new FalhaSegura($codigo, $mensagem)
        );
    }

    private function inteiroPositivo(mixed $valor): int
    {
        if (is_int($valor)) {
            if ($valor <= 0) {
                throw new DadosContextoUsuarioOracleInvalidosException();
            }

            return $valor;
        }

        if (!is_string($valor)) {
            throw new DadosContextoUsuarioOracleInvalidosException();
        }

        $valor = trim($valor);

        if (preg_match('/^[1-9][0-9]*$/D', $valor) !== 1) {
            throw new DadosContextoUsuarioOracleInvalidosException();
        }

        $inteiro = filter_var(
            $valor,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if (!is_int($inteiro)) {
            throw new DadosContextoUsuarioOracleInvalidosException();
        }

        return $inteiro;
    }

    private function textoObrigatorio(mixed $valor): string
    {
        if (!is_string($valor)) {
            throw new DadosContextoUsuarioOracleInvalidosException();
        }

        $valor = trim($valor);

        if ($valor === '') {
            throw new DadosContextoUsuarioOracleInvalidosException();
        }

        return $valor;
    }
}
