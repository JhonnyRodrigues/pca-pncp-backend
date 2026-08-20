<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Estrategia;

use Semae\Pca\Application\Enums\ModoPublicacao;
use Semae\Pca\Application\Enums\SituacaoConsultaEstrategia;
use Semae\Pca\Application\Ports\EstrategiaPublicacaoPort;
use Semae\Pca\Application\Results\ResultadoAdocaoEstrategia;
use Semae\Pca\Application\Results\ResultadoConsultaEstrategia;
use Semae\Pca\Application\ValueObjects\EstrategiaPublicacao;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Oracle\Adodb\FalhaOracleException;
use Throwable;

final class OracleEstrategiaPublicacaoAdapter implements EstrategiaPublicacaoPort
{
    public function __construct(
        private readonly ConexaoAdodbEmprestada $conexao,
        private readonly MapeamentoConfiguracaoEstrategiaOracle $configuracao,
        private readonly MapeamentoPlanosEstrategiaOracle $planos,
        private readonly MapeamentoCadastroUasgOracle $cadastroUasg
    ) {}

    public function consultarCorrente(AnoExecucao $anoExecucao): ResultadoConsultaEstrategia
    {
        try {
            $linhas = $this->conexao->buscarTodos(
                $this->sqlConfiguracaoCorrente(),
                [
                    $this->configuracao->getChaveModo(),
                    $this->configuracao->getChaveUasgConsolidadora(),
                ]
            );

            if ($linhas === []) {
                return ResultadoConsultaEstrategia::ausente();
            }

            $valores = $this->indexarConfiguracao($linhas);
            $chaveModo = $this->configuracao->getChaveModo();

            if (!array_key_exists($chaveModo, $valores)) {
                throw new EstrategiaOracleInvalidaException();
            }

            $modo = $valores[$chaveModo];

            if ($modo === $this->configuracao->getLiteralModoIndividual()) {
                return ResultadoConsultaEstrategia::disponivel(
                    EstrategiaPublicacao::individual()
                );
            }

            if ($modo !== $this->configuracao->getLiteralModoConsolidado()) {
                throw new EstrategiaOracleInvalidaException();
            }

            $chaveUasg = $this->configuracao->getChaveUasgConsolidadora();

            if (!array_key_exists($chaveUasg, $valores)) {
                throw new EstrategiaOracleInvalidaException();
            }

            try {
                $uasg = $this->resolverUasgAtiva($valores[$chaveUasg]);
            } catch (EstrategiaOracleInvalidaException) {
                return ResultadoConsultaEstrategia::invalida(new FalhaSegura(
                    'UASG_CONSOLIDADORA_INVALIDA',
                    'A UASG consolidadora não possui um cadastro ativo único e válido.'
                ));
            }

            return ResultadoConsultaEstrategia::disponivel(
                EstrategiaPublicacao::consolidada($uasg)
            );
        } catch (FalhaOracleException) {
            return $this->falhaConsultaCorrente();
        } catch (EstrategiaOracleInvalidaException) {
            return ResultadoConsultaEstrategia::invalida(new FalhaSegura(
                'ESTRATEGIA_CORRENTE_INVALIDA',
                'A configuração da estratégia anual está ausente ou incoerente.'
            ));
        } catch (Throwable) {
            return $this->falhaConsultaCorrente();
        }
    }

    public function consultarAdotada(AnoExecucao $anoExecucao): ResultadoConsultaEstrategia
    {
        try {
            $linhas = $this->conexao->buscarTodos(
                $this->sqlEstrategiaAdotada(),
                [$anoExecucao->getValor()]
            );

            if ($linhas === []) {
                return ResultadoConsultaEstrategia::ausente();
            }

            return $this->interpretarEstrategiaAdotada($linhas);
        } catch (FalhaOracleException) {
            return ResultadoConsultaEstrategia::falhaSistemica(new FalhaSegura(
                'CONSULTA_ESTRATEGIA_ADOTADA_FALHOU',
                'Não foi possível consultar a estratégia anual adotada.'
            ));
        } catch (EstrategiaOracleInvalidaException) {
            return $this->estrategiaAdotadaInvalida();
        } catch (Throwable) {
            return ResultadoConsultaEstrategia::falhaSistemica(new FalhaSegura(
                'CONSULTA_ESTRATEGIA_ADOTADA_FALHOU',
                'Não foi possível consultar a estratégia anual adotada.'
            ));
        }
    }

    public function adotar(
        AnoExecucao $anoExecucao,
        EstrategiaPublicacao $estrategia
    ): ResultadoAdocaoEstrategia {
        if (!$this->conexao->estaEmTransacao()) {
            return ResultadoAdocaoEstrategia::falha(new FalhaSegura(
                'ADOCAO_ESTRATEGIA_EXIGE_TRANSACAO',
                'A estratégia anual somente pode ser adotada em uma transação ativa.'
            ));
        }

        $adotada = $this->consultarAdotada($anoExecucao);

        if ($adotada->getSituacao() === SituacaoConsultaEstrategia::DISPONIVEL) {
            if ($adotada->getEstrategia()?->equals($estrategia) === true) {
                return ResultadoAdocaoEstrategia::adotada();
            }

            return ResultadoAdocaoEstrategia::falha(new FalhaSegura(
                'ESTRATEGIA_ADOTADA_DIVERGENTE',
                'A estratégia anual informada diverge da estratégia já adotada.'
            ));
        }

        if ($adotada->getSituacao() !== SituacaoConsultaEstrategia::AUSENTE) {
            return ResultadoAdocaoEstrategia::falha(
                $adotada->getFalha() ?? new FalhaSegura(
                    'ADOCAO_ESTRATEGIA_FALHOU',
                    'Não foi possível adotar a estratégia anual.'
                )
            );
        }

        try {
            if ($estrategia->getModo() === ModoPublicacao::INDIVIDUAL) {
                $this->adotarIndividual($anoExecucao);
            } else {
                $this->adotarConsolidada($anoExecucao, $estrategia);
            }
        } catch (EstrategiaOracleInvalidaException) {
            return ResultadoAdocaoEstrategia::falha(new FalhaSegura(
                'DADOS_PARA_ADOCAO_ESTRATEGIA_INVALIDOS',
                'Os dados Oracle necessários à adoção da estratégia são incoerentes.'
            ));
        } catch (Throwable) {
            return ResultadoAdocaoEstrategia::falha(new FalhaSegura(
                'ADOCAO_ESTRATEGIA_FALHOU',
                'Não foi possível adotar a estratégia anual.'
            ));
        }

        $releitura = $this->consultarAdotada($anoExecucao);

        if (
            $releitura->getSituacao() === SituacaoConsultaEstrategia::DISPONIVEL
            && $releitura->getEstrategia()?->equals($estrategia) === true
        ) {
            return ResultadoAdocaoEstrategia::adotada();
        }

        return ResultadoAdocaoEstrategia::falha(new FalhaSegura(
            'ADOCAO_ESTRATEGIA_NAO_CONFIRMADA',
            'A releitura Oracle não confirmou a adoção da estratégia anual.'
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $linhas
     * @return array<string, string>
     */
    private function indexarConfiguracao(array $linhas): array
    {
        $valores = [];

        foreach ($linhas as $linha) {
            $chave = $this->textoObrigatorio($linha['CHAVE_CONFIGURACAO'] ?? null);
            $valor = $this->textoObrigatorio($linha['VALOR_CONFIGURACAO'] ?? null);

            if (array_key_exists($chave, $valores)) {
                throw new EstrategiaOracleInvalidaException();
            }

            $valores[$chave] = $valor;
        }

        return $valores;
    }

    /**
     * @param array<int, array<string, mixed>> $linhas
     */
    private function interpretarEstrategiaAdotada(array $linhas): ResultadoConsultaEstrategia
    {
        $linhasVazias = 0;
        $porUo = [];
        $modoUnico = null;
        $uasgConsolidada = null;

        foreach ($linhas as $linha) {
            $uo = $this->textoObrigatorio($linha['UNIDADE_ORCAMENTARIA'] ?? null);
            $modo = $this->textoOpcional($linha['MODO_ADOTADO'] ?? null);
            $uasg = $this->textoOpcional($linha['UASG_ADOTADA'] ?? null);

            if ($modo === null && $uasg === null) {
                $linhasVazias++;
                continue;
            }

            if ($modo === null || $uasg === null || $linhasVazias > 0) {
                throw new EstrategiaOracleInvalidaException();
            }

            if ($modoUnico !== null && $modoUnico !== $modo) {
                throw new EstrategiaOracleInvalidaException();
            }

            $modoUnico = $modo;

            if ($modo === $this->planos->getLiteralModoIndividual()) {
                if (isset($porUo[$uo]) && $porUo[$uo] !== $uasg) {
                    throw new EstrategiaOracleInvalidaException();
                }

                $porUo[$uo] = $uasg;
                continue;
            }

            if ($modo !== $this->planos->getLiteralModoConsolidado()) {
                throw new EstrategiaOracleInvalidaException();
            }

            if ($uasgConsolidada !== null && $uasgConsolidada !== $uasg) {
                throw new EstrategiaOracleInvalidaException();
            }

            $uasgConsolidada = $uasg;
        }

        if ($linhasVazias === count($linhas)) {
            return ResultadoConsultaEstrategia::ausente();
        }

        if ($linhasVazias > 0 || $modoUnico === null) {
            throw new EstrategiaOracleInvalidaException();
        }

        if ($modoUnico === $this->planos->getLiteralModoIndividual()) {
            if (count(array_unique(array_values($porUo), SORT_STRING)) !== count($porUo)) {
                throw new EstrategiaOracleInvalidaException();
            }

            return ResultadoConsultaEstrategia::disponivel(
                EstrategiaPublicacao::individual()
            );
        }

        if ($uasgConsolidada === null) {
            throw new EstrategiaOracleInvalidaException();
        }

        return ResultadoConsultaEstrategia::disponivel(
            EstrategiaPublicacao::consolidada(
                $this->resolverUasgAtiva($uasgConsolidada)
            )
        );
    }

    private function adotarIndividual(AnoExecucao $anoExecucao): void
    {
        $uasgsPorUo = $this->resolverUasgsIndividuais($anoExecucao);

        foreach ($uasgsPorUo as $vinculo) {
            $this->conexao->executar(
                $this->sqlAdocaoIndividual(),
                [
                    $this->planos->getLiteralModoIndividual(),
                    $vinculo['uasg'],
                    $anoExecucao->getValor(),
                    $vinculo['uo'],
                ]
            );
        }
    }

    private function adotarConsolidada(
        AnoExecucao $anoExecucao,
        EstrategiaPublicacao $estrategia
    ): void {
        $uasgInformada = $estrategia->getUasgConsolidadora();

        if ($uasgInformada === null) {
            throw new EstrategiaOracleInvalidaException();
        }

        $uasgAtiva = $this->resolverUasgAtiva($uasgInformada->getCodigo());

        $this->conexao->executar(
            $this->sqlAdocaoConsolidada(),
            [
                $this->planos->getLiteralModoConsolidado(),
                $uasgAtiva->getCodigo(),
                $anoExecucao->getValor(),
            ]
        );
    }

    /** @return array<int, array{uo: string, uasg: string}> */
    private function resolverUasgsIndividuais(AnoExecucao $anoExecucao): array
    {
        $linhas = $this->conexao->buscarTodos(
            $this->sqlUasgsIndividuais(),
            [$anoExecucao->getValor(), $this->cadastroUasg->getValorAtivo()]
        );

        if ($linhas === []) {
            throw new EstrategiaOracleInvalidaException();
        }

        $uasgsPorUo = [];
        $uosEncontradas = [];
        $uasgsEncontradas = [];

        foreach ($linhas as $linha) {
            $uo = $this->textoObrigatorio($linha['UNIDADE_ORCAMENTARIA'] ?? null);
            $codigo = $this->textoObrigatorio($linha['CODIGO_UASG'] ?? null);
            $this->textoObrigatorio($linha['DESCRICAO_UASG'] ?? null);

            if (isset($uosEncontradas[$uo])) {
                throw new EstrategiaOracleInvalidaException();
            }

            if (isset($uasgsEncontradas[$codigo])) {
                throw new EstrategiaOracleInvalidaException();
            }

            $uosEncontradas[$uo] = true;
            $uasgsEncontradas[$codigo] = true;
            $uasgsPorUo[] = ['uo' => $uo, 'uasg' => $codigo];
        }

        return $uasgsPorUo;
    }

    private function resolverUasgAtiva(string $codigo): Uasg
    {
        $codigo = $this->textoObrigatorio($codigo);
        $linhas = $this->conexao->buscarTodos(
            $this->sqlLookupUasg(),
            [$codigo, $this->cadastroUasg->getValorAtivo()]
        );

        if (count($linhas) !== $this->cadastroUasg->getCardinalidadeExataDoLookup()) {
            throw new EstrategiaOracleInvalidaException();
        }

        $codigoEncontrado = $this->textoObrigatorio($linhas[0]['CODIGO_UASG'] ?? null);
        $descricao = $this->textoObrigatorio($linhas[0]['DESCRICAO_UASG'] ?? null);

        if ($codigoEncontrado !== $codigo) {
            throw new EstrategiaOracleInvalidaException();
        }

        return new Uasg($codigoEncontrado, $descricao);
    }

    private function sqlConfiguracaoCorrente(): string
    {
        $tabela = $this->configuracao->getTabelaQualificada();
        $chave = $this->configuracao->getColunaChave();
        $valor = $this->configuracao->getColunaValor();

        return "SELECT c.{$chave} AS CHAVE_CONFIGURACAO, "
            . "c.{$valor} AS VALOR_CONFIGURACAO FROM {$tabela} c "
            . "WHERE c.{$chave} IN (?, ?)";
    }

    private function sqlEstrategiaAdotada(): string
    {
        $tabela = $this->planos->getTabelaQualificada();
        $ano = $this->planos->getColunaAnoExecucao();
        $uo = $this->planos->getColunaUnidadeOrcamentaria();
        $modo = $this->planos->getColunaModoAdotado();
        $uasg = $this->planos->getColunaUasgAdotada();

        return "SELECT p.{$uo} AS UNIDADE_ORCAMENTARIA, "
            . "p.{$modo} AS MODO_ADOTADO, p.{$uasg} AS UASG_ADOTADA "
            . "FROM {$tabela} p WHERE p.{$ano} = ?";
    }

    private function sqlLookupUasg(): string
    {
        $tabela = $this->cadastroUasg->getTabelaQualificada();
        $codigo = $this->cadastroUasg->getColunaCodigo();
        $descricao = $this->cadastroUasg->getColunaDescricao();
        $ativo = $this->cadastroUasg->getColunaAtivo();

        return "SELECT u.{$codigo} AS CODIGO_UASG, u.{$descricao} AS DESCRICAO_UASG "
            . "FROM {$tabela} u WHERE u.{$codigo} = ? AND u.{$ativo} = ?";
    }

    private function sqlUasgsIndividuais(): string
    {
        $tabelaPlanos = $this->planos->getTabelaQualificada();
        $ano = $this->planos->getColunaAnoExecucao();
        $uoPlano = $this->planos->getColunaUnidadeOrcamentaria();
        $tabelaUasg = $this->cadastroUasg->getTabelaQualificada();
        $uoCadastro = $this->cadastroUasg->getColunaUnidadeOrcamentaria();
        $codigo = $this->cadastroUasg->getColunaCodigo();
        $descricao = $this->cadastroUasg->getColunaDescricao();
        $ativo = $this->cadastroUasg->getColunaAtivo();

        return "SELECT p.{$uoPlano} AS UNIDADE_ORCAMENTARIA, "
            . "u.{$codigo} AS CODIGO_UASG, u.{$descricao} AS DESCRICAO_UASG "
            . "FROM (SELECT DISTINCT {$uoPlano} FROM {$tabelaPlanos} WHERE {$ano} = ?) p "
            . "LEFT JOIN {$tabelaUasg} u ON u.{$uoCadastro} = p.{$uoPlano} "
            . "AND u.{$ativo} = ?";
    }

    private function sqlAdocaoIndividual(): string
    {
        return $this->sqlAdocaoBase(true);
    }

    private function sqlAdocaoConsolidada(): string
    {
        return $this->sqlAdocaoBase(false);
    }

    private function sqlAdocaoBase(bool $porUo): string
    {
        $tabela = $this->planos->getTabelaQualificada();
        $ano = $this->planos->getColunaAnoExecucao();
        $uo = $this->planos->getColunaUnidadeOrcamentaria();
        $modo = $this->planos->getColunaModoAdotado();
        $uasg = $this->planos->getColunaUasgAdotada();
        $filtroUo = $porUo ? " AND {$uo} = ?" : '';

        return "UPDATE {$tabela} SET {$modo} = ?, {$uasg} = ? "
            . "WHERE {$ano} = ?{$filtroUo} "
            . "AND {$modo} IS NULL AND {$uasg} IS NULL";
    }

    private function textoObrigatorio(mixed $valor): string
    {
        $texto = $this->textoOpcional($valor);

        if ($texto === null) {
            throw new EstrategiaOracleInvalidaException();
        }

        return $texto;
    }

    private function textoOpcional(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        if (!is_string($valor) && !is_int($valor)) {
            throw new EstrategiaOracleInvalidaException();
        }

        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }

    private function falhaConsultaCorrente(): ResultadoConsultaEstrategia
    {
        return ResultadoConsultaEstrategia::falhaSistemica(new FalhaSegura(
            'CONSULTA_ESTRATEGIA_CORRENTE_FALHOU',
            'Não foi possível consultar a estratégia anual corrente.'
        ));
    }

    private function estrategiaAdotadaInvalida(): ResultadoConsultaEstrategia
    {
        return ResultadoConsultaEstrategia::invalida(new FalhaSegura(
            'ESTRATEGIA_ADOTADA_INVALIDA',
            'A estratégia anual adotada está parcial ou incoerente.'
        ));
    }
}
