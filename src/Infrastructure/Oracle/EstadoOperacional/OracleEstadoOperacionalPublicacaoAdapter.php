<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\EstadoOperacional;

use Semae\Pca\Application\Enums\ModoPublicacao;
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
            $linhas = $this->conexao->buscarTodos(
                $this->sqlConsulta($unidade),
                $this->parametrosEscopo($unidade)
            );
            $estadoInicial = $this->interpretar($linhas, $unidade);
            $somentePendencias = $this->estadosIguais($estadoInicial, $estado);

            if ($somentePendencias && $this->projecaoCompleta($linhas, $estado)) {
                return ResultadoPersistenciaLocal::concluida();
            }

            $this->conexao->executar(
                $this->sqlRegistro($unidade, $somentePendencias),
                $this->parametrosRegistro($unidade, $estado)
            );

            $releitura = $this->conexao->buscarTodos(
                $this->sqlConsulta($unidade),
                $this->parametrosEscopo($unidade)
            );
            $estadoRelido = $this->interpretar($releitura, $unidade);

            if (
                count($releitura) === count($linhas)
                && $this->estadosIguais($estadoRelido, $estado)
                && $this->projecaoCompleta($releitura, $estado)
            ) {
                return ResultadoPersistenciaLocal::concluida();
            }

            return ResultadoPersistenciaLocal::falhaSistemica(new FalhaSegura(
                'REGISTRO_ESTADO_OPERACIONAL_NAO_CONFIRMADO',
                'A releitura Oracle não confirmou o estado operacional registrado.'
            ));
        } catch (EstadoOperacionalOracleInvalidoException) {
            return ResultadoPersistenciaLocal::falhaSistemica(new FalhaSegura(
                'ESTADO_OPERACIONAL_ORACLE_CONFLITANTE',
                'A projeção Oracle do estado operacional está ausente ou incoerente.'
            ));
        } catch (Throwable) {
            return ResultadoPersistenciaLocal::falhaSistemica(new FalhaSegura(
                'REGISTRO_ESTADO_OPERACIONAL_FALHOU',
                'Não foi possível registrar o estado operacional.'
            ));
        }
    }

    /** @param array<int, array<string, mixed>> $linhas */
    private function projecaoCompleta(array $linhas, EstadoOperacionalPublicacao $estado): bool
    {
        foreach ($linhas as $linha) {
            [$literal, $sequencial] = $this->interpretarParEstado(
                $linha['ESTADO_PUBLICACAO'] ?? null,
                $linha['SEQUENCIAL_PUBLICACAO_PNCP'] ?? null
            );
            if ($literal !== $estado->getEstado() || $sequencial !== $estado->getSequencialPca()?->getValor()) {
                return false;
            }
        }

        return $linhas !== [];
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

        $estrategiaEsperada = $this->literalEstrategia($unidade);
        $uasgEsperada = $unidade->getUasgExterna()->getCodigo();
        $estrategiaEncontrada = null;
        $uasgEncontrada = null;
        $estadoEncontrado = null;
        $sequencialEncontrado = null;

        foreach ($linhas as $linha) {
            $estrategia = $this->textoOpcional(
                $linha['ESTRATEGIA_ADOTADA_PUBLICACAO'] ?? null
            );
            $uasg = $this->textoOpcional($linha['UASG_ADOTADA'] ?? null);

            if (($estrategia === null) !== ($uasg === null)) {
                throw new EstadoOperacionalOracleInvalidoException();
            }

            if ($estrategia !== null) {
                if (
                    $estrategia !== $estrategiaEsperada
                    || $uasg !== $uasgEsperada
                    || (
                        $estrategiaEncontrada !== null
                        && $estrategiaEncontrada !== $estrategia
                    )
                    || ($uasgEncontrada !== null && $uasgEncontrada !== $uasg)
                ) {
                    throw new EstadoOperacionalOracleInvalidoException();
                }

                $estrategiaEncontrada = $estrategia;
                $uasgEncontrada = $uasg;
            }

            [$estado, $sequencial] = $this->interpretarParEstado(
                $linha['ESTADO_PUBLICACAO'] ?? null,
                $linha['SEQUENCIAL_PUBLICACAO_PNCP'] ?? null
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

        if ($estrategiaEncontrada === null || $uasgEncontrada === null) {
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
        $estrategia = $this->mapeamento->getColunaEstrategia();
        $uasg = $this->mapeamento->getColunaUasg();
        $estado = $this->mapeamento->getColunaEstadoPublicacao();
        $sequencial = $this->mapeamento->getColunaSequencialPublicacaoPncp();
        $filtroUo = $unidade->getModo() === ModoPublicacao::INDIVIDUAL
            ? " AND p.{$uo} = ?"
            : '';

        return "SELECT 
            p.{$estrategia} AS ESTRATEGIA_ADOTADA_PUBLICACAO, 
            p.{$uasg} AS UASG_ADOTADA, 
            p.{$estado} AS ESTADO_PUBLICACAO, 
            p.{$sequencial} AS SEQUENCIAL_PUBLICACAO_PNCP 
        FROM {$tabela} p 
        WHERE p.{$ano} = ?{$filtroUo}";
    }

    private function sqlRegistro(UnidadeExternaPublicacao $unidade, bool $somentePendencias): string
    {
        $tabela = $this->mapeamento->getTabelaQualificada();
        $ano = $this->mapeamento->getColunaAnoExecucao();
        $uo = $this->mapeamento->getColunaUnidadeOrcamentaria();
        $estado = $this->mapeamento->getColunaEstadoPublicacao();
        $sequencial = $this->mapeamento->getColunaSequencialPublicacaoPncp();
        $filtroUo = $unidade->getModo() === ModoPublicacao::INDIVIDUAL
            ? " AND {$uo} = ?"
            : '';

        return "UPDATE {$tabela} SET 
            {$estado} = ?, 
            {$sequencial} = ? 
        WHERE {$ano} = ?{$filtroUo}"
        . ($somentePendencias ? " AND {$estado} IS NULL AND {$sequencial} IS NULL" : '');
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
            $this->literalEstado($estado),
            $estado->getSequencialPca()?->getValor(),
            ...$this->parametrosEscopo($unidade),
        ];
    }

    private function literalEstrategia(UnidadeExternaPublicacao $unidade): string
    {
        return $unidade->getModo() === ModoPublicacao::INDIVIDUAL
            ? $this->mapeamento->getLiteralEstrategiaIndividual()
            : $this->mapeamento->getLiteralEstrategiaConsolidada();
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
