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
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoUnidadesOrcamentariasOracle;
use Throwable;

final class OracleEstrategiaPublicacaoAdapter implements EstrategiaPublicacaoPort
{
    /** @var array<string, string> */
    private array $descricoesUasgConfiguradas = [];

    public function __construct(
        private readonly ConexaoAdodbEmprestada $conexao,
        private readonly MapeamentoConfiguracaoEstrategiaOracle $configuracao,
        private readonly MapeamentoPlanosEstrategiaOracle $planos,
        private readonly MapeamentoUnidadesOrcamentariasOracle $unidadesOrcamentarias
    ) {}

    public function consultarCorrente(AnoExecucao $anoExecucao): ResultadoConsultaEstrategia
    {
        try {
            $linhas = $this->conexao->buscarTodos(
                $this->sqlConfiguracaoCorrente(),
                [
                    $this->configuracao->getChaveEstrategia(),
                    $this->configuracao->getChaveUasgConsolidadora(),
                ]
            );

            if ($linhas === []) {
                return ResultadoConsultaEstrategia::ausente();
            }

            $valores = $this->indexarConfiguracao($linhas);
            $chaveEstrategia = $this->configuracao->getChaveEstrategia();

            if (!array_key_exists($chaveEstrategia, $valores)) {
                throw new EstrategiaOracleInvalidaException();
            }

            $estrategia = $valores[$chaveEstrategia]['valor'];

            if ($estrategia === $this->configuracao->getLiteralEstrategiaIndividual()) {
                return ResultadoConsultaEstrategia::disponivel(
                    EstrategiaPublicacao::individual()
                );
            }

            if ($estrategia !== $this->configuracao->getLiteralEstrategiaConsolidada()) {
                throw new EstrategiaOracleInvalidaException();
            }

            $chaveUasg = $this->configuracao->getChaveUasgConsolidadora();

            if (!array_key_exists($chaveUasg, $valores)) {
                throw new EstrategiaOracleInvalidaException();
            }

            $dadosUasg = $valores[$chaveUasg];

            try {
                $codigoUasg = $this->textoObrigatorio($dadosUasg['valor']);
                $descricaoUasg = $this->textoObrigatorio($dadosUasg['descricao']);
                $uasg = new Uasg($codigoUasg, $descricaoUasg);
                $this->descricoesUasgConfiguradas[$codigoUasg] = $descricaoUasg;
            } catch (Throwable) {
                return ResultadoConsultaEstrategia::invalida(new FalhaSegura(
                    'UASG_CONSOLIDADORA_INVALIDA',
                    'A configuração da UASG consolidadora não possui código e descrição válidos.'
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
     * @return array<string, array{valor: string, descricao: ?string}>
     */
    private function indexarConfiguracao(array $linhas): array
    {
        $valores = [];

        foreach ($linhas as $linha) {
            $chave = $this->textoObrigatorio($linha['CHAVE_CONFIGURACAO'] ?? null);
            $valor = $this->textoObrigatorio($linha['VALOR_CONFIGURACAO'] ?? null);
            $descricao = $this->textoOpcional($linha['DESCRICAO_CONFIGURACAO'] ?? null);

            if (array_key_exists($chave, $valores)) {
                throw new EstrategiaOracleInvalidaException();
            }

            $valores[$chave] = ['valor' => $valor, 'descricao' => $descricao];
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
            $modo = $this->textoOpcional($linha['ESTRATEGIA_ADOTADA'] ?? null);
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

            if ($modo === $this->planos->getLiteralEstrategiaIndividual()) {
                if (isset($porUo[$uo]) && $porUo[$uo] !== $uasg) {
                    throw new EstrategiaOracleInvalidaException();
                }

                $porUo[$uo] = $uasg;
                continue;
            }

            if ($modo !== $this->planos->getLiteralEstrategiaConsolidada()) {
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

        if ($modoUnico === $this->planos->getLiteralEstrategiaIndividual()) {
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
                $this->resolverUasgAdotada($uasgConsolidada)
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
                    $this->planos->getLiteralEstrategiaIndividual(),
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

        $this->descricoesUasgConfiguradas[$uasgInformada->getCodigo()] =
            $uasgInformada->getDescricao();

        $this->conexao->executar(
            $this->sqlAdocaoConsolidada(),
            [
                $this->planos->getLiteralEstrategiaConsolidada(),
                $uasgInformada->getCodigo(),
                $anoExecucao->getValor(),
            ]
        );
    }

    /** @return array<int, array{uo: string, uasg: string}> */
    private function resolverUasgsIndividuais(AnoExecucao $anoExecucao): array
    {
        $linhas = $this->conexao->buscarTodos(
            $this->sqlUasgsIndividuais(),
            [$anoExecucao->getValor(), $this->unidadesOrcamentarias->getValorAtivo()]
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

    private function resolverUasgAdotada(string $codigo): Uasg
    {
        $codigo = $this->textoObrigatorio($codigo);

        if (isset($this->descricoesUasgConfiguradas[$codigo])) {
            return new Uasg($codigo, $this->descricoesUasgConfiguradas[$codigo]);
        }

        $linhas = $this->conexao->buscarTodos(
            $this->sqlConfiguracaoCorrente(),
            [
                $this->configuracao->getChaveEstrategia(),
                $this->configuracao->getChaveUasgConsolidadora(),
            ]
        );
        $valores = $this->indexarConfiguracao($linhas);
        $dadosUasg = $valores[$this->configuracao->getChaveUasgConsolidadora()] ?? null;

        if (
            $dadosUasg === null
            || $this->textoObrigatorio($dadosUasg['valor']) !== $codigo
        ) {
            throw new EstrategiaOracleInvalidaException();
        }

        $descricao = $this->textoObrigatorio($dadosUasg['descricao']);
        $this->descricoesUasgConfiguradas[$codigo] = $descricao;

        return new Uasg($codigo, $descricao);
    }

    private function sqlConfiguracaoCorrente(): string
    {
        $tabela = $this->configuracao->getTabelaQualificada();
        $chave = $this->configuracao->getColunaChave();
        $valor = $this->configuracao->getColunaValor();
        $descricao = $this->configuracao->getColunaDescricao();

        return "SELECT c.{$chave} AS CHAVE_CONFIGURACAO, "
            . "c.{$valor} AS VALOR_CONFIGURACAO, "
            . "c.{$descricao} AS DESCRICAO_CONFIGURACAO FROM {$tabela} c "
            . "WHERE c.{$chave} IN (?, ?)";
    }

    private function sqlEstrategiaAdotada(): string
    {
        $tabela = $this->planos->getTabelaQualificada();
        $ano = $this->planos->getColunaAnoExecucao();
        $uo = $this->planos->getColunaUnidadeOrcamentaria();
        $estrategia = $this->planos->getColunaEstrategiaAdotada();
        $uasg = $this->planos->getColunaUasgAdotada();

        return "SELECT p.{$uo} AS UNIDADE_ORCAMENTARIA, "
            . "p.{$estrategia} AS ESTRATEGIA_ADOTADA, p.{$uasg} AS UASG_ADOTADA "
            . "FROM {$tabela} p WHERE p.{$ano} = ?";
    }

    private function sqlUasgsIndividuais(): string
    {
        $tabelaPlanos = $this->planos->getTabelaQualificada();
        $ano = $this->planos->getColunaAnoExecucao();
        $uoPlano = $this->planos->getColunaUnidadeOrcamentaria();
        $tabelaUasg = $this->unidadesOrcamentarias->getTabelaQualificada();
        $uoCadastro = $this->unidadesOrcamentarias->getColunaRelacaoPlano();
        $codigo = $this->unidadesOrcamentarias->getColunaCodigoUasg();
        $descricao = $this->unidadesOrcamentarias->getColunaDescricaoUasg();
        $ativo = $this->unidadesOrcamentarias->getColunaAtivo();

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
        $modo = $this->planos->getColunaEstrategiaAdotada();
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
