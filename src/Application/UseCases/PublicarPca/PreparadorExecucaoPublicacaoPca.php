<?php

declare(strict_types=1);

namespace Semae\Pca\Application\UseCases\PublicarPca;

use LogicException;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Enums\ModoPublicacao;
use Semae\Pca\Application\Enums\SituacaoAutenticacaoPncp;
use Semae\Pca\Application\Enums\SituacaoConsultaEstrategia;
use Semae\Pca\Application\Enums\SituacaoFinalizacaoUnidadeTrabalho;
use Semae\Pca\Application\Enums\SituacaoInicioUnidadeTrabalho;
use Semae\Pca\Application\Enums\SituacaoResolucaoContextoUsuario;
use Semae\Pca\Application\Ports\AutenticadorPncp;
use Semae\Pca\Application\Ports\ContextoUsuarioPort;
use Semae\Pca\Application\Ports\EstrategiaPublicacaoPort;
use Semae\Pca\Application\Ports\UnidadeDeTrabalhoPort;
use Semae\Pca\Application\Results\ResultadoPreparacaoPublicacaoPca;
use Semae\Pca\Application\ValueObjects\ChaveUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Application\ValueObjects\EstrategiaPublicacao;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Ports\PlanoRepository;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Throwable;

final class PreparadorExecucaoPublicacaoPca
{
    public function __construct(
        private readonly ContextoUsuarioPort $contextoUsuario,
        private readonly PlanoRepository $planoRepository,
        private readonly EstrategiaPublicacaoPort $estrategiaPublicacao,
        private readonly UnidadeDeTrabalhoPort $unidadeDeTrabalho,
        private readonly AutenticadorPncp $autenticadorPncp
    ) {}

    public function preparar(PublicarPcaRequest $request): ResultadoPreparacaoPublicacaoPca
    {
        $contexto = $this->resolverContextoUsuario();

        if ($contexto instanceof FalhaSegura) {
            return ResultadoPreparacaoPublicacaoPca::interrompida($contexto);
        }

        $identidades = $this->planoRepository->listarIdentidades($request->getAnoExecucao());

        if ($identidades === []) {
            return ResultadoPreparacaoPublicacaoPca::semUnidades();
        }

        $estrategia = $this->prepararEstrategiaAnual(
            $request->getAnoExecucao(),
            $identidades
        );

        if ($estrategia instanceof FalhaSegura) {
            return ResultadoPreparacaoPublicacaoPca::interrompida($estrategia);
        }

        $unidadesExternas = $this->formarUnidadesExternas(
            $request->getAnoExecucao(),
            $identidades,
            $estrategia
        );
        try {
            $sessaoPncp = $this->autenticarNoPncp();
        } catch (Throwable) {
            return ResultadoPreparacaoPublicacaoPca::interrompida(
                new FalhaSegura(
                    'AUTENTICACAO_PNCP_FALHOU',
                    'Não foi possível autenticar no PNCP.'
                ),
                $unidadesExternas
            );
        }

        if ($sessaoPncp instanceof FalhaSegura) {
            return ResultadoPreparacaoPublicacaoPca::interrompida(
                $sessaoPncp,
                $unidadesExternas
            );
        }

        return ResultadoPreparacaoPublicacaoPca::preparada(
            $contexto,
            $estrategia,
            $unidadesExternas,
            $sessaoPncp
        );
    }

    private function resolverContextoUsuario(): ContextoUsuario|FalhaSegura
    {
        $resultadoContexto = $this->contextoUsuario->resolver();

        if ($resultadoContexto->getSituacao() !== SituacaoResolucaoContextoUsuario::RESOLVIDO) {
            return $this->falhaOuDiagnostico(
                $resultadoContexto->getFalha(),
                'CONTEXTO_USUARIO_NAO_RESOLVIDO',
                'O contexto do usuário não foi resolvido.'
            );
        }

        $contexto = $resultadoContexto->getContexto();

        if ($contexto === null) {
            return new FalhaSegura(
                'CONTEXTO_USUARIO_INCONSISTENTE',
                'O contexto resolvido não forneceu os dados do usuário.'
            );
        }

        return $contexto;
    }

    /**
     * @param IdentidadePlano[] $identidades
     */
    private function prepararEstrategiaAnual(
        AnoExecucao $anoExecucao,
        array $identidades
    ): EstrategiaPublicacao|FalhaSegura {
        $resultadoInicio = $this->unidadeDeTrabalho->iniciar(
            ChaveUnidadeTrabalho::estrategiaAnual($anoExecucao)
        );

        if ($resultadoInicio->getSituacao() !== SituacaoInicioUnidadeTrabalho::INICIADA) {
            return $this->falhaOuDiagnostico(
                $resultadoInicio->getFalha(),
                'INICIO_ESTRATEGIA_FALHOU',
                'Não foi possível iniciar a conferência da estratégia anual.'
            );
        }

        $resultadoCorrente = $this->estrategiaPublicacao->consultarCorrente($anoExecucao);

        if ($resultadoCorrente->getSituacao() !== SituacaoConsultaEstrategia::DISPONIVEL) {
            return $this->desfazerPreservandoFalha(
                $this->falhaOuDiagnostico(
                    $resultadoCorrente->getFalha(),
                    'ESTRATEGIA_CORRENTE_AUSENTE',
                    'A estratégia corrente não está disponível.'
                )
            );
        }

        $estrategiaCorrente = $resultadoCorrente->getEstrategia();

        if ($estrategiaCorrente === null) {
            return $this->desfazerPreservandoFalha(
                new FalhaSegura(
                    'ESTRATEGIA_CORRENTE_INCONSISTENTE',
                    'A consulta corrente não forneceu uma estratégia.'
                )
            );
        }

        $falhaValidacao = $this->validarIdentidadesParaEstrategia(
            $anoExecucao,
            $identidades,
            $estrategiaCorrente
        );

        if ($falhaValidacao !== null) {
            return $this->desfazerPreservandoFalha($falhaValidacao);
        }

        $resultadoAdotada = $this->estrategiaPublicacao->consultarAdotada($anoExecucao);

        if ($resultadoAdotada->getSituacao() === SituacaoConsultaEstrategia::AUSENTE) {
            $resultadoAdocao = $this->estrategiaPublicacao->adotar(
                $anoExecucao,
                $estrategiaCorrente
            );

            if (!$resultadoAdocao->foiAdotada()) {
                return $this->desfazerPreservandoFalha(
                    $this->falhaOuDiagnostico(
                        $resultadoAdocao->getFalha(),
                        'ADOCAO_ESTRATEGIA_FALHOU',
                        'Não foi possível adotar a estratégia anual.'
                    )
                );
            }
        } elseif ($resultadoAdotada->getSituacao() === SituacaoConsultaEstrategia::DISPONIVEL) {
            $estrategiaAdotada = $resultadoAdotada->getEstrategia();

            if (
                $estrategiaAdotada === null
                || !$estrategiaCorrente->equals($estrategiaAdotada)
            ) {
                return $this->desfazerPreservandoFalha(
                    new FalhaSegura(
                        'ESTRATEGIA_ANUAL_DIVERGENTE',
                        'A estratégia corrente diverge da estratégia adotada.'
                    )
                );
            }
        } else {
            return $this->desfazerPreservandoFalha(
                $this->falhaOuDiagnostico(
                    $resultadoAdotada->getFalha(),
                    'CONSULTA_ESTRATEGIA_ADOTADA_FALHOU',
                    'Não foi possível consultar a estratégia adotada.'
                )
            );
        }

        $resultadoConfirmacao = $this->unidadeDeTrabalho->confirmar();

        if (
            $resultadoConfirmacao->getSituacao()
            !== SituacaoFinalizacaoUnidadeTrabalho::CONCLUIDA
        ) {
            return $this->desfazerPreservandoFalha(
                $this->falhaOuDiagnostico(
                    $resultadoConfirmacao->getFalha(),
                    'CONFIRMACAO_ESTRATEGIA_FALHOU',
                    'Não foi possível confirmar a estratégia anual.'
                )
            );
        }

        return $estrategiaCorrente;
    }

    private function desfazerPreservandoFalha(FalhaSegura $falhaOriginal): FalhaSegura
    {
        $resultadoDesfazimento = $this->unidadeDeTrabalho->desfazer();

        if (
            $resultadoDesfazimento->getSituacao()
            === SituacaoFinalizacaoUnidadeTrabalho::CONCLUIDA
        ) {
            return $falhaOriginal;
        }

        return $this->falhaOuDiagnostico(
            $resultadoDesfazimento->getFalha(),
            'DESFAZIMENTO_ESTRATEGIA_FALHOU',
            'Não foi possível desfazer a unidade da estratégia anual.'
        );
    }

    private function falhaOuDiagnostico(
        ?FalhaSegura $falha,
        string $codigo,
        string $mensagem
    ): FalhaSegura {
        return $falha ?? new FalhaSegura($codigo, $mensagem);
    }

    /**
     * @param IdentidadePlano[] $identidades
     */
    private function validarIdentidadesParaEstrategia(
        AnoExecucao $anoExecucao,
        array $identidades,
        EstrategiaPublicacao $estrategia
    ): ?FalhaSegura {
        $uosEncontradas = [];
        $uasgsEncontradas = [];

        foreach ($identidades as $identidade) {
            if (
                !$identidade instanceof IdentidadePlano
                || $identidade->getAnoExecucao()->getValor() !== $anoExecucao->getValor()
            ) {
                return new FalhaSegura(
                    'IDENTIDADE_PLANO_INVALIDA',
                    'A identidade do Plano não pertence ao exercício solicitado.'
                );
            }

            $codigoUo = $identidade->getUnidadeOrcamentaria()->getIdentidade()->getValor();

            if (isset($uosEncontradas[$codigoUo])) {
                return new FalhaSegura(
                    'UO_PARTICIPANTE_DUPLICADA',
                    'A Unidade Orçamentária participante não possui resolução única.'
                );
            }

            $uosEncontradas[$codigoUo] = true;

            if ($estrategia->getModo() !== ModoPublicacao::INDIVIDUAL) {
                continue;
            }

            $codigoUasg = $identidade->getUnidadeOrcamentaria()->getUasg()->getCodigo();

            if (isset($uasgsEncontradas[$codigoUasg])) {
                return new FalhaSegura(
                    'UASG_INDIVIDUAL_COMPARTILHADA',
                    'Unidades Orçamentárias individuais não podem compartilhar UASG.'
                );
            }

            $uasgsEncontradas[$codigoUasg] = true;
        }

        return null;
    }

    /**
     * @param IdentidadePlano[] $identidades
     * @return UnidadeExternaPublicacao[]
     */
    private function formarUnidadesExternas(
        AnoExecucao $anoExecucao,
        array $identidades,
        EstrategiaPublicacao $estrategia
    ): array {
        if ($estrategia->getModo() === ModoPublicacao::CONSOLIDADO) {
            $uasgConsolidadora = $estrategia->getUasgConsolidadora();

            if ($uasgConsolidadora === null) {
                throw new LogicException('Estratégia consolidada sem UASG consolidadora.');
            }

            return [
                UnidadeExternaPublicacao::consolidada($anoExecucao, $uasgConsolidadora),
            ];
        }

        return array_map(
            static fn (IdentidadePlano $identidade): UnidadeExternaPublicacao =>
                UnidadeExternaPublicacao::individual($identidade),
            $identidades
        );
    }

    private function autenticarNoPncp(): SessaoPncp|FalhaSegura
    {
        $resultadoAutenticacao = $this->autenticadorPncp->autenticar();

        if ($resultadoAutenticacao->getSituacao() !== SituacaoAutenticacaoPncp::AUTENTICADA) {
            return $this->falhaOuDiagnostico(
                $resultadoAutenticacao->getFalha(),
                'AUTENTICACAO_PNCP_FALHOU',
                'Não foi possível autenticar no PNCP.'
            );
        }

        $sessaoPncp = $resultadoAutenticacao->getSessao();

        if ($sessaoPncp === null) {
            return new FalhaSegura(
                'SESSAO_PNCP_INCONSISTENTE',
                'A autenticação concluída não forneceu uma sessão PNCP.'
            );
        }

        return $sessaoPncp;
    }
}
