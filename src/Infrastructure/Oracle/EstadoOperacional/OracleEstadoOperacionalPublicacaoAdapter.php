<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\EstadoOperacional;

use Semae\Pca\Application\Enums\ModoPublicacao;
use Semae\Pca\Application\Enums\SituacaoConsultaEstadoOperacional;
use Semae\Pca\Application\Ports\EstadoOperacionalPublicacaoPort;
use Semae\Pca\Application\Results\ResultadoConsultaEstadoOperacional;
use Semae\Pca\Application\Results\ResultadoPersistenciaLocal;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Enums\EstadoPublicacao;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Throwable;

final class OracleEstadoOperacionalPublicacaoAdapter implements EstadoOperacionalPublicacaoPort
{
    public function __construct(
        private readonly ConexaoAdodbEmprestada $conexao,
        private readonly MapeamentoEstadoOperacionalOracle $mapeamento
    ) {}

    public function consultar(
        UnidadeExternaPublicacao $unidade
    ): ResultadoConsultaEstadoOperacional {
        if (!$this->conexao->estaEmTransacao()) {
            return ResultadoConsultaEstadoOperacional::falhaSistemica(new FalhaSegura(
                'CONSULTA_ESTADO_OPERACIONAL_EXIGE_TRANSACAO',
                'O estado operacional somente pode ser consultado em uma transação ativa.'
            ));
        }

        try {
            $linhas = $this->conexao->buscarTodos(
                $this->sqlConsulta($unidade),
                $this->parametrosEscopo($unidade)
            );

            return ResultadoConsultaEstadoOperacional::disponivel(
                $this->interpretar($linhas, $unidade)
            );
        } catch (EstadoOperacionalOracleInvalidoException) {
            return ResultadoConsultaEstadoOperacional::conflito(new FalhaSegura(
                'ESTADO_OPERACIONAL_ORACLE_CONFLITANTE',
                'A projeção Oracle do estado operacional está ausente ou incoerente.'
            ));
        } catch (Throwable) {
            return ResultadoConsultaEstadoOperacional::falhaSistemica(new FalhaSegura(
                'CONSULTA_ESTADO_OPERACIONAL_FALHOU',
                'Não foi possível consultar o estado operacional.'
            ));
        }
    }

    public function registrar(
        UnidadeExternaPublicacao $unidade,
        EstadoOperacionalPublicacao $estado
    ): ResultadoPersistenciaLocal {
        if (!$this->conexao->estaEmTransacao()) {
            return ResultadoPersistenciaLocal::falhaSistemica(new FalhaSegura(
                'REGISTRO_ESTADO_OPERACIONAL_EXIGE_TRANSACAO',
                'O estado operacional somente pode ser registrado em uma transação ativa.'
            ));
        }

        try {
            $this->conexao->executar(
                $this->sqlRegistro($unidade),
                $this->parametrosRegistro($unidade, $estado)
            );

            $releitura = $this->consultar($unidade);

            if (
                $releitura->getSituacao() === SituacaoConsultaEstadoOperacional::DISPONIVEL
                && $this->estadosIguais($releitura->getEstadoOperacional(), $estado)
            ) {
                return ResultadoPersistenciaLocal::concluida();
            }

            return ResultadoPersistenciaLocal::falhaSistemica(new FalhaSegura(
                'REGISTRO_ESTADO_OPERACIONAL_NAO_CONFIRMADO',
                'A releitura Oracle não confirmou o estado operacional registrado.'
            ));
        } catch (Throwable) {
            return ResultadoPersistenciaLocal::falhaSistemica(new FalhaSegura(
                'REGISTRO_ESTADO_OPERACIONAL_FALHOU',
                'Não foi possível registrar o estado operacional.'
            ));
        }
    }

    /**
     * @param array<int, array<string, mixed>> $linhas
     */
    private function interpretar(
        array $linhas,
        UnidadeExternaPublicacao $unidade
    ): EstadoOperacionalPublicacao {
        if ($linhas === []) {
            throw new EstadoOperacionalOracleInvalidoException();
        }

        $modoEsperado = $this->literalModo($unidade);
        $uasgEsperada = $unidade->getUasgExterna()->getCodigo();
        $modoEncontrado = null;
        $uasgEncontrada = null;
        $estadoEncontrado = null;
        $sequencialEncontrado = null;

        foreach ($linhas as $linha) {
            $modo = $this->textoOpcional($linha['MODO_PUBLICACAO'] ?? null);
            $uasg = $this->textoOpcional($linha['UASG_PUBLICACAO'] ?? null);

            if (($modo === null) !== ($uasg === null)) {
                throw new EstadoOperacionalOracleInvalidoException();
            }

            if ($modo !== null) {
                if (
                    $modo !== $modoEsperado
                    || $uasg !== $uasgEsperada
                    || ($modoEncontrado !== null && $modoEncontrado !== $modo)
                    || ($uasgEncontrada !== null && $uasgEncontrada !== $uasg)
                ) {
                    throw new EstadoOperacionalOracleInvalidoException();
                }

                $modoEncontrado = $modo;
                $uasgEncontrada = $uasg;
            }

            [$estado, $sequencial] = $this->interpretarParEstado(
                $linha['ESTADO_PUBLICACAO'] ?? null,
                $linha['SEQUENCIAL_PCA'] ?? null
            );

            if ($estado === null) {
                continue;
            }

            if (
                $estadoEncontrado !== null
                && ($estadoEncontrado !== $estado || $sequencialEncontrado !== $sequencial)
            ) {
                throw new EstadoOperacionalOracleInvalidoException();
            }

            $estadoEncontrado = $estado;
            $sequencialEncontrado = $sequencial;
        }

        if ($modoEncontrado === null || $uasgEncontrada === null) {
            throw new EstadoOperacionalOracleInvalidoException();
        }

        if ($estadoEncontrado === null || $estadoEncontrado === EstadoPublicacao::NAO_PUBLICADO) {
            return EstadoOperacionalPublicacao::naoPublicado();
        }

        if ($sequencialEncontrado === null) {
            throw new EstadoOperacionalOracleInvalidoException();
        }

        $sequencial = new SequencialPca($sequencialEncontrado);

        return $estadoEncontrado === EstadoPublicacao::PUBLICADO
            ? EstadoOperacionalPublicacao::publicado($sequencial)
            : EstadoOperacionalPublicacao::excluido($sequencial);
    }

    /** @return array{?EstadoPublicacao, ?int} */
    private function interpretarParEstado(mixed $valorEstado, mixed $valorSequencial): array
    {
        $literal = $this->textoOpcional($valorEstado);

        if ($literal === null) {
            if ($valorSequencial !== null) {
                throw new EstadoOperacionalOracleInvalidoException();
            }

            return [null, null];
        }

        if ($literal === $this->mapeamento->getLiteralNaoPublicado()) {
            if ($valorSequencial !== null) {
                throw new EstadoOperacionalOracleInvalidoException();
            }

            return [EstadoPublicacao::NAO_PUBLICADO, null];
        }

        if ($literal === $this->mapeamento->getLiteralPublicado()) {
            return [EstadoPublicacao::PUBLICADO, $this->inteiroPositivo($valorSequencial)];
        }

        if ($literal === $this->mapeamento->getLiteralExcluido()) {
            return [EstadoPublicacao::EXCLUIDO, $this->inteiroPositivo($valorSequencial)];
        }

        throw new EstadoOperacionalOracleInvalidoException();
    }

    private function sqlConsulta(UnidadeExternaPublicacao $unidade): string
    {
        $tabela = $this->mapeamento->getTabelaQualificada();
        $ano = $this->mapeamento->getColunaAnoExecucao();
        $uo = $this->mapeamento->getColunaUnidadeOrcamentaria();
        $modo = $this->mapeamento->getColunaModo();
        $uasg = $this->mapeamento->getColunaUasg();
        $estado = $this->mapeamento->getColunaEstado();
        $sequencial = $this->mapeamento->getColunaSequencial();
        $filtroUo = $unidade->getModo() === ModoPublicacao::INDIVIDUAL
            ? " AND p.{$uo} = ?"
            : '';

        return "SELECT p.{$modo} AS MODO_PUBLICACAO, "
            . "p.{$uasg} AS UASG_PUBLICACAO, p.{$estado} AS ESTADO_PUBLICACAO, "
            . "p.{$sequencial} AS SEQUENCIAL_PCA FROM {$tabela} p "
            . "WHERE p.{$ano} = ?{$filtroUo}";
    }

    private function sqlRegistro(UnidadeExternaPublicacao $unidade): string
    {
        $tabela = $this->mapeamento->getTabelaQualificada();
        $ano = $this->mapeamento->getColunaAnoExecucao();
        $uo = $this->mapeamento->getColunaUnidadeOrcamentaria();
        $modo = $this->mapeamento->getColunaModo();
        $uasg = $this->mapeamento->getColunaUasg();
        $estado = $this->mapeamento->getColunaEstado();
        $sequencial = $this->mapeamento->getColunaSequencial();
        $filtroUo = $unidade->getModo() === ModoPublicacao::INDIVIDUAL
            ? " AND {$uo} = ?"
            : '';

        return "UPDATE {$tabela} SET {$modo} = ?, {$uasg} = ?, "
            . "{$estado} = ?, {$sequencial} = ? WHERE {$ano} = ?{$filtroUo}";
    }

    /** @return array<int, int> */
    private function parametrosEscopo(UnidadeExternaPublicacao $unidade): array
    {
        $parametros = [$unidade->getAnoExecucao()->getValor()];

        if ($unidade->getModo() === ModoPublicacao::INDIVIDUAL) {
            $identidade = $unidade->getIdentidadePlano();

            if ($identidade === null) {
                throw new EstadoOperacionalOracleInvalidoException();
            }

            $parametros[] = $identidade->getUnidadeOrcamentaria()->getLotacao()->getId();
        }

        return $parametros;
    }

    /** @return array<int, int|string|null> */
    private function parametrosRegistro(
        UnidadeExternaPublicacao $unidade,
        EstadoOperacionalPublicacao $estado
    ): array {
        return [
            $this->literalModo($unidade),
            $unidade->getUasgExterna()->getCodigo(),
            $this->literalEstado($estado),
            $estado->getSequencialPca()?->getValor(),
            ...$this->parametrosEscopo($unidade),
        ];
    }

    private function literalModo(UnidadeExternaPublicacao $unidade): string
    {
        return $unidade->getModo() === ModoPublicacao::INDIVIDUAL
            ? $this->mapeamento->getLiteralModoIndividual()
            : $this->mapeamento->getLiteralModoConsolidado();
    }

    private function literalEstado(EstadoOperacionalPublicacao $estado): string
    {
        return match ($estado->getEstado()) {
            EstadoPublicacao::NAO_PUBLICADO => $this->mapeamento->getLiteralNaoPublicado(),
            EstadoPublicacao::PUBLICADO => $this->mapeamento->getLiteralPublicado(),
            EstadoPublicacao::EXCLUIDO => $this->mapeamento->getLiteralExcluido(),
        };
    }

    private function estadosIguais(
        ?EstadoOperacionalPublicacao $primeiro,
        EstadoOperacionalPublicacao $segundo
    ): bool {
        if ($primeiro === null || $primeiro->getEstado() !== $segundo->getEstado()) {
            return false;
        }

        $sequencialPrimeiro = $primeiro->getSequencialPca();
        $sequencialSegundo = $segundo->getSequencialPca();

        if ($sequencialPrimeiro === null || $sequencialSegundo === null) {
            return $sequencialPrimeiro === $sequencialSegundo;
        }

        return $sequencialPrimeiro->equals($sequencialSegundo);
    }

    private function textoOpcional(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        if (!is_string($valor) && !is_int($valor)) {
            throw new EstadoOperacionalOracleInvalidoException();
        }

        $valor = trim((string) $valor);

        if ($valor === '') {
            throw new EstadoOperacionalOracleInvalidoException();
        }

        return $valor;
    }

    private function inteiroPositivo(mixed $valor): int
    {
        if (is_int($valor)) {
            if ($valor <= 0) {
                throw new EstadoOperacionalOracleInvalidoException();
            }

            return $valor;
        }

        if (!is_string($valor)) {
            throw new EstadoOperacionalOracleInvalidoException();
        }

        $valor = trim($valor);

        if (preg_match('/^[1-9][0-9]*$/D', $valor) !== 1) {
            throw new EstadoOperacionalOracleInvalidoException();
        }

        $inteiro = filter_var(
            $valor,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if (!is_int($inteiro)) {
            throw new EstadoOperacionalOracleInvalidoException();
        }

        return $inteiro;
    }
}
