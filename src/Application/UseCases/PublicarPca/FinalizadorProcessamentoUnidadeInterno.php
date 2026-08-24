<?php

declare(strict_types=1);

namespace Semae\Pca\Application\UseCases\PublicarPca;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Semae\Pca\Application\Contracts\FinalizadorProcessamentoUnidade;
use Semae\Pca\Application\Enums\AcaoProcessamentoUnidade;
use Semae\Pca\Application\Enums\ModoPublicacao;
use Semae\Pca\Application\Enums\SituacaoBuscaCandidatosPca;
use Semae\Pca\Application\Enums\SituacaoEscritaPncp;
use Semae\Pca\Application\Enums\SituacaoFinalizacaoUnidadeTrabalho;
use Semae\Pca\Application\Enums\SituacaoObservacaoPca;
use Semae\Pca\Application\Enums\SituacaoPersistenciaLocal;
use Semae\Pca\Application\Ports\EstadoOperacionalPublicacaoPort;
use Semae\Pca\Application\Ports\LeitorPncp;
use Semae\Pca\Application\Ports\RegistroAuditoriaPort;
use Semae\Pca\Application\Ports\UnidadeDeTrabalhoPort;
use Semae\Pca\Application\Results\DesfechoProcessamentoUnidade;
use Semae\Pca\Application\Results\ResultadoBuscaCandidatosPca;
use Semae\Pca\Application\Results\ResultadoEscritaPncp;
use Semae\Pca\Application\Results\ResultadoObservacaoPca;
use Semae\Pca\Application\Results\ResultadoProcessamentoUnidade;
use Semae\Pca\Application\Results\ResultadoPublicacaoPncp;
use Semae\Pca\Application\ValueObjects\ContextoFinalizacaoProcessamentoUnidade;
use Semae\Pca\Application\ValueObjects\ContextoUnidadeComDecisao;
use Semae\Pca\Application\ValueObjects\ContextoUnidadeSobExclusividade;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\IntencaoRegistroHistorico;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;
use Semae\Pca\Domain\Enums\ProcedimentoPublicacaoPca;
use Semae\Pca\Domain\Exceptions\ConflitoEstadoPublicacaoException;
use Semae\Pca\Domain\Policies\PoliticaProcessamentoPlano;
use Semae\Pca\Domain\Policies\ResultadoDecisaoProcessamentoPlano;
use Semae\Pca\Domain\Ports\PlanoRepository;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;
use Semae\Pca\Domain\ValueObjects\PcaObservado;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Throwable;

final class FinalizadorProcessamentoUnidadeInterno implements FinalizadorProcessamentoUnidade
{
    private readonly DesfazedorUnidadeProcessamento $desfazedor;

    public function __construct(
        private readonly LeitorPncp $leitorPncp,
        private readonly PoliticaProcessamentoPlano $politica,
        private readonly ExecutorComandosUnidade $executor,
        private readonly PlanoRepository $planoRepository,
        private readonly EstadoOperacionalPublicacaoPort $estadoOperacional,
        private readonly RegistroAuditoriaPort $registroAuditoria,
        private readonly UnidadeDeTrabalhoPort $unidadeDeTrabalho,
        private readonly LoggerInterface $logger
    ) {
        $this->desfazedor = new DesfazedorUnidadeProcessamento($unidadeDeTrabalho);
    }

    public function finalizar(ContextoFinalizacaoProcessamentoUnidade $contexto): DesfechoProcessamentoUnidade
    {
        try {
            if ($contexto->getResultadoDecisao()->getDecisao() === DecisaoProcessamentoPlano::NENHUMA_ACAO) {
                return $this->finalizarSemEscrita($contexto);
            }

            return $this->finalizarDepoisDeEscrita($contexto);
        } catch (ConflitoEstadoPublicacaoException | InvalidArgumentException) {
            return $this->bloquearEDesfazer(
                $contexto,
                new FalhaSegura(
                    'CONVERGENCIA_UNIDADE_CONFLITANTE',
                    'Os fatos finais da unidade são incompatíveis com a fotografia vigente.'
                )
            );
        } catch (Throwable) {
            return $this->interromperEDesfazer(new FalhaSegura(
                'FINALIZACAO_UNIDADE_FALHOU',
                'Não foi possível concluir a finalização da unidade.'
            ));
        }
    }

    private function finalizarSemEscrita(ContextoFinalizacaoProcessamentoUnidade $contexto): DesfechoProcessamentoUnidade
    {
        $decisaoComprovada = $this->politica->decidir(
            $contexto->getPlanos(),
            $contexto->getEstadoOperacionalInicial(),
            $contexto->getPcaObservadoInicial()
        );

        if ($decisaoComprovada->getDecisao() !== DecisaoProcessamentoPlano::NENHUMA_ACAO) {
            return $this->bloquearEDesfazer(
                $contexto,
                new FalhaSegura(
                    'CONVERGENCIA_NAO_COMPROVADA',
                    'A observação vigente não comprova ausência de alterações na unidade.'
                )
            );
        }

        return $this->persistirConvergencia(
            $contexto,
            $contexto->getPcaObservadoInicial(),
            $contexto->getResultadosEscrita()
        );
    }

    private function finalizarDepoisDeEscrita(ContextoFinalizacaoProcessamentoUnidade $contexto): DesfechoProcessamentoUnidade
    {
        $resultados = $contexto->getResultadosEscrita();
        $this->registrarLog(
            'info',
            AcaoProcessamentoUnidade::PROCESSAR_RESULTADO_ESCRITA,
            'Resultado de escrita externa recebido.',
            $contexto
        );
        $observado = $this->observarDepoisDaEscrita($contexto, $resultados);

        if ($observado instanceof DesfechoProcessamentoUnidade) {
            return $observado;
        }

        $decisaoFinal = $this->recalcular($contexto, $observado, $resultados);

        if ($decisaoFinal->getDecisao() === DecisaoProcessamentoPlano::NENHUMA_ACAO) {
            return $this->persistirConvergencia($contexto, $observado, $resultados);
        }

        if ($this->divergenciaFinalIncompativel($contexto, $decisaoFinal)) {
            return $this->bloquearEDesfazer(
                $contexto,
                new FalhaSegura(
                    'DIVERGENCIA_FINAL_INCOMPATIVEL',
                    'O estado externo final diverge de forma incompatível da operação executada.'
                )
            );
        }

        if (!$this->contemResultadoIncerto($resultados)) {
            return $this->falhaReconciliavelEDesfazer(
                $contexto,
                new FalhaSegura(
                    'CONVERGENCIA_PENDENTE',
                    'Ainda existem mudanças homologadas pendentes na unidade.'
                )
            );
        }

        $contextoReconciliacao = $this->criarContextoReconciliacao(
            $contexto,
            $observado,
            $resultados,
            $decisaoFinal
        );
        $this->registrarLog(
            'info',
            AcaoProcessamentoUnidade::EXECUTAR_RECONCILIACAO,
            'Reconciliação adicional iniciada.',
            $contexto
        );

        try {
            $resultadoReconciliacao = $this->executor->executar($contextoReconciliacao);
        } catch (Throwable) {
            return $this->interromperEDesfazer(new FalhaSegura(
                'RECONCILIACAO_UNIDADE_FALHOU',
                'Não foi possível executar a reconciliação controlada da unidade.'
            ));
        }

        if ($resultadoReconciliacao instanceof DesfechoProcessamentoUnidade) {
            return $resultadoReconciliacao;
        }

        $resultados = array_merge($resultados, $resultadoReconciliacao);
        $observado = $this->observarDepoisDaEscrita($contexto, $resultados);

        if ($observado instanceof DesfechoProcessamentoUnidade) {
            return $observado;
        }

        $decisaoFinal = $this->recalcular($contexto, $observado, $resultados);

        if ($decisaoFinal->getDecisao() === DecisaoProcessamentoPlano::NENHUMA_ACAO) {
            return $this->persistirConvergencia($contexto, $observado, $resultados);
        }

        if ($this->divergenciaFinalIncompativel($contexto, $decisaoFinal)) {
            return $this->bloquearEDesfazer(
                $contexto,
                new FalhaSegura(
                    'DIVERGENCIA_FINAL_INCOMPATIVEL',
                    'O estado externo final diverge de forma incompatível da operação executada.'
                )
            );
        }

        return $this->falhaReconciliavelEDesfazer(
            $contexto,
            new FalhaSegura(
                'RECONCILIACAO_NAO_CONVERGIU',
                'A única reconciliação adicional não comprovou a convergência da unidade.'
            )
        );
    }

    /**
     * @param array<ResultadoEscritaPncp|ResultadoPublicacaoPncp> $resultados
     */
    private function observarDepoisDaEscrita(
        ContextoFinalizacaoProcessamentoUnidade $contexto,
        array $resultados
    ): PcaObservado|DesfechoProcessamentoUnidade {
        $sequencial = $this->obterSequencialParaObservacao($contexto, $resultados);

        if ($sequencial === null) {
            try {
                $resultado = $this->leitorPncp->localizarCandidatos(
                    $contexto->getSessaoPncp(),
                    $contexto->getCnpj(),
                    $contexto->getUnidadeExterna()
                );
            } catch (Throwable) {
                return $this->interromperEDesfazer(new FalhaSegura(
                    'OBSERVACAO_FINAL_FALHOU',
                    'Não foi possível localizar candidatos após a escrita.'
                ));
            }

            return $this->classificarCandidatos($contexto, $resultado);
        }

        try {
            $resultado = $this->leitorPncp->observarPcaConhecido(
                $contexto->getSessaoPncp(),
                $contexto->getCnpj(),
                $contexto->getUnidadeExterna(),
                $sequencial
            );
        } catch (Throwable) {
            return $this->interromperEDesfazer(new FalhaSegura(
                'OBSERVACAO_FINAL_FALHOU',
                'Não foi possível observar o PCA depois da escrita.'
            ));
        }

        return $this->classificarObservacao($contexto, $resultado);
    }

    private function classificarObservacao(
        ContextoFinalizacaoProcessamentoUnidade $contexto,
        ResultadoObservacaoPca $resultado
    ): PcaObservado|DesfechoProcessamentoUnidade {
        if (
            $resultado->getSituacao() === SituacaoObservacaoPca::ENCONTRADO
            || $resultado->getSituacao() === SituacaoObservacaoPca::AUSENCIA_DEFINITIVA
        ) {
            $observado = $resultado->getPcaObservado();

            if ($observado !== null) {
                return $observado;
            }

            return $this->interromperEDesfazer(new FalhaSegura(
                'OBSERVACAO_FINAL_INCONSISTENTE',
                'A observação final não forneceu um fato externo válido.'
            ));
        }

        $falha = $resultado->getFalha() ?? new FalhaSegura(
            'OBSERVACAO_FINAL_INDEFINIDA',
            'A observação final não foi conclusiva.'
        );

        return match ($resultado->getSituacao()) {
            SituacaoObservacaoPca::INCONCLUSIVA => $this->falhaReconciliavelEDesfazer(
                $contexto,
                $falha
            ),
            SituacaoObservacaoPca::CONFLITO => $this->bloquearEDesfazer($contexto, $falha),
            SituacaoObservacaoPca::FALHA_SISTEMICA => $this->interromperEDesfazer($falha),
            SituacaoObservacaoPca::ENCONTRADO,
            SituacaoObservacaoPca::AUSENCIA_DEFINITIVA =>
            $this->interromperEDesfazer($falha),
        };
    }

    private function classificarCandidatos(
        ContextoFinalizacaoProcessamentoUnidade $contexto,
        ResultadoBuscaCandidatosPca $resultado
    ): PcaObservado|DesfechoProcessamentoUnidade {
        if ($resultado->getSituacao() === SituacaoBuscaCandidatosPca::CONCLUIDA) {
            if ($resultado->getCandidatos() === []) {
                return PcaObservado::ausente();
            }

            return $this->bloquearEDesfazer(
                $contexto,
                new FalhaSegura(
                    'CANDIDATO_APOS_ESCRITA_SEM_VINCULO',
                    'Foi localizado PCA candidato que não pode ser adotado automaticamente.'
                )
            );
        }

        $falha = $resultado->getFalha() ?? new FalhaSegura(
            'BUSCA_CANDIDATOS_FINAL_INDEFINIDA',
            'A busca de candidatos após a escrita não foi conclusiva.'
        );

        return match ($resultado->getSituacao()) {
            SituacaoBuscaCandidatosPca::INCONCLUSIVA =>
            $this->falhaReconciliavelEDesfazer($contexto, $falha),
            SituacaoBuscaCandidatosPca::CONFLITO => $this->bloquearEDesfazer($contexto, $falha),
            SituacaoBuscaCandidatosPca::FALHA_SISTEMICA =>
            $this->interromperEDesfazer($falha),
            SituacaoBuscaCandidatosPca::CONCLUIDA => $this->interromperEDesfazer($falha),
        };
    }

    /**
     * @param array<ResultadoEscritaPncp|ResultadoPublicacaoPncp> $resultados
     */
    private function recalcular(
        ContextoFinalizacaoProcessamentoUnidade $contexto,
        PcaObservado $observado,
        array $resultados
    ): ResultadoDecisaoProcessamentoPlano {
        return $this->politica->decidir(
            $contexto->getPlanos(),
            $this->estadoCoerenteParaPolitica($contexto, $resultados),
            $observado
        );
    }

    /**
     * @param array<ResultadoEscritaPncp|ResultadoPublicacaoPncp> $resultados
     */
    private function criarContextoReconciliacao(
        ContextoFinalizacaoProcessamentoUnidade $contexto,
        PcaObservado $observado,
        array $resultados,
        ResultadoDecisaoProcessamentoPlano $decisao
    ): ContextoUnidadeComDecisao {
        $sobExclusividade = new ContextoUnidadeSobExclusividade(
            $contexto->getUnidadeExterna(),
            $contexto->getPlanos(),
            $this->estadoCoerenteParaPolitica($contexto, $resultados)
        );

        return new ContextoUnidadeComDecisao(
            $contexto->getSessaoPncp(),
            $contexto->getCnpj(),
            $sobExclusividade,
            $observado,
            $decisao
        );
    }

    private function divergenciaFinalIncompativel(
        ContextoFinalizacaoProcessamentoUnidade $contexto,
        ResultadoDecisaoProcessamentoPlano $decisaoFinal
    ): bool {
        return $contexto->getResultadoDecisao()->getDecisao()
            === DecisaoProcessamentoPlano::RETIFICAR
            && $decisaoFinal->getDecisao() === DecisaoProcessamentoPlano::PUBLICAR;
    }

    /**
     * @param array<ResultadoEscritaPncp|ResultadoPublicacaoPncp> $resultados
     */
    private function persistirConvergencia(
        ContextoFinalizacaoProcessamentoUnidade $contexto,
        PcaObservado $observado,
        array $resultados
    ): DesfechoProcessamentoUnidade {
        $estadoFinal = $this->derivarEstadoFinal($contexto, $observado);

        try {
            if (!$this->estadosIguais($contexto->getEstadoOperacionalInicial(), $estadoFinal)) {
                $resultadoEstado = $this->estadoOperacional->registrar(
                    $contexto->getUnidadeExterna(),
                    $estadoFinal
                );

                if ($resultadoEstado->getSituacao() !== SituacaoPersistenciaLocal::CONCLUIDA) {
                    return $this->interromperEDesfazer(
                        $resultadoEstado->getFalha() ?? new FalhaSegura(
                            'REGISTRO_ESTADO_OPERACIONAL_FALHOU',
                            'Não foi possível registrar o estado operacional convergente.'
                        )
                    );
                }
            }

            foreach ($contexto->getPlanos() as $plano) {
                if (!$plano->possuiSincronizacaoConfirmada()) {
                    $this->planoRepository->confirmarSincronizacao($plano);
                }
            }

            if ($this->deveAuditar($contexto, $resultados)) {
                $sequencial = $estadoFinal->getSequencialPca();

                if ($sequencial === null) {
                    throw new InvalidArgumentException(
                        'Operação auditável exige sequencial externo conhecido.'
                    );
                }

                $resultadoAuditoria = $this->registroAuditoria->registrar(
                    $this->criarIntencaoAuditoria($contexto, $sequencial)
                );

                if ($resultadoAuditoria->getSituacao() !== SituacaoPersistenciaLocal::CONCLUIDA) {
                    return $this->interromperEDesfazer(
                        $resultadoAuditoria->getFalha() ?? new FalhaSegura(
                            'REGISTRO_AUDITORIA_FALHOU',
                            'Não foi possível registrar a auditoria da operação confirmada.'
                        )
                    );
                }
            }

            $commit = $this->unidadeDeTrabalho->confirmar();

            if ($commit->getSituacao() !== SituacaoFinalizacaoUnidadeTrabalho::CONCLUIDA) {
                return $this->interromperEDesfazer(
                    $commit->getFalha() ?? new FalhaSegura(
                        'COMMIT_UNIDADE_FALHOU',
                        'Não foi possível confirmar a unidade local convergente.'
                    )
                );
            }
        } catch (Throwable) {
            return $this->interromperEDesfazer(new FalhaSegura(
                'PERSISTENCIA_CONVERGENCIA_FALHOU',
                'Não foi possível persistir atomicamente a convergência da unidade.'
            ));
        }

        $this->registrarLog(
            'info',
            AcaoProcessamentoUnidade::PERSISTIR_CONVERGENCIA,
            'Unidade finalizada com convergência comprovada.',
            $contexto
        );

        return $this->criarResultadoSucesso($contexto, $estadoFinal);
    }

    private function derivarEstadoFinal(
        ContextoFinalizacaoProcessamentoUnidade $contexto,
        PcaObservado $observado
    ): EstadoOperacionalPublicacao {
        if ($observado->estaAtivo()) {
            $sequencial = $observado->getSequencialPca();

            if ($sequencial === null) {
                throw new InvalidArgumentException('PCA ativo exige sequencial conhecido.');
            }

            return EstadoOperacionalPublicacao::publicado($sequencial);
        }

        $sequencialAnterior = $contexto->getEstadoOperacionalInicial()->getSequencialPca();

        if ($sequencialAnterior !== null) {
            return EstadoOperacionalPublicacao::excluido($sequencialAnterior);
        }

        return EstadoOperacionalPublicacao::naoPublicado();
    }

    private function criarResultadoSucesso(
        ContextoFinalizacaoProcessamentoUnidade $contexto,
        EstadoOperacionalPublicacao $estadoFinal
    ): DesfechoProcessamentoUnidade {
        $decisao = $contexto->getResultadoDecisao();

        if ($decisao->getDecisao() === DecisaoProcessamentoPlano::NENHUMA_ACAO) {
            return DesfechoProcessamentoUnidade::resultadoUnitario(
                ResultadoProcessamentoUnidade::semAlteracao(
                    $contexto->getUnidadeExterna(),
                    $estadoFinal->getSequencialPca()
                )
            );
        }

        $sequencial = $estadoFinal->getSequencialPca();

        if ($sequencial === null) {
            throw new InvalidArgumentException('Operação concluída exige sequencial conhecido.');
        }

        return DesfechoProcessamentoUnidade::resultadoUnitario(
            ResultadoProcessamentoUnidade::concluido(
                $contexto->getUnidadeExterna(),
                $decisao->getDecisao(),
                $sequencial,
                count($decisao->getDemandasAIncluir()),
                count($decisao->getDemandasAExcluir())
            )
        );
    }

    /**
     * @param array<ResultadoEscritaPncp|ResultadoPublicacaoPncp> $resultados
     */
    private function obterSequencialParaObservacao(
        ContextoFinalizacaoProcessamentoUnidade $contexto,
        array $resultados
    ): ?SequencialPca {
        for ($indice = count($resultados) - 1; $indice >= 0; $indice--) {
            $resultado = $resultados[$indice];

            if (
                $resultado instanceof ResultadoPublicacaoPncp
                && $resultado->getSituacao() === SituacaoEscritaPncp::ACEITA
                && $resultado->getSequencialPca() !== null
            ) {
                return $resultado->getSequencialPca();
            }
        }

        if (
            $contexto->getResultadoDecisao()->getDecisao()
            === DecisaoProcessamentoPlano::PUBLICAR
        ) {
            return null;
        }

        return $contexto->getEstadoOperacionalInicial()->getSequencialPca();
    }

    /**
     * @param array<ResultadoEscritaPncp|ResultadoPublicacaoPncp> $resultados
     */
    private function estadoCoerenteParaPolitica(
        ContextoFinalizacaoProcessamentoUnidade $contexto,
        array $resultados
    ): EstadoOperacionalPublicacao {
        if (
            $contexto->getResultadoDecisao()->getDecisao()
            === DecisaoProcessamentoPlano::PUBLICAR
        ) {
            $sequencial = $this->obterSequencialParaObservacao($contexto, $resultados);

            if ($sequencial !== null && $this->contemPublicacaoAceita($resultados)) {
                return EstadoOperacionalPublicacao::publicado($sequencial);
            }
        }

        return $contexto->getEstadoOperacionalInicial();
    }

    /**
     * @param array<ResultadoEscritaPncp|ResultadoPublicacaoPncp> $resultados
     */
    private function contemResultadoIncerto(array $resultados): bool
    {
        foreach ($resultados as $resultado) {
            if ($resultado->getSituacao() === SituacaoEscritaPncp::RESULTADO_INCERTO) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<ResultadoEscritaPncp|ResultadoPublicacaoPncp> $resultados
     */
    private function contemPublicacaoAceita(array $resultados): bool
    {
        foreach ($resultados as $resultado) {
            if (
                $resultado instanceof ResultadoPublicacaoPncp
                && $resultado->getSituacao() === SituacaoEscritaPncp::ACEITA
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<ResultadoEscritaPncp|ResultadoPublicacaoPncp> $resultados
     */
    private function deveAuditar(
        ContextoFinalizacaoProcessamentoUnidade $contexto,
        array $resultados
    ): bool {
        if (
            $contexto->getResultadoDecisao()->getDecisao()
            === DecisaoProcessamentoPlano::NENHUMA_ACAO
        ) {
            return false;
        }

        foreach ($resultados as $resultado) {
            if (
                $resultado->getSituacao() === SituacaoEscritaPncp::ACEITA
                || $resultado->getSituacao() === SituacaoEscritaPncp::RESULTADO_INCERTO
            ) {
                return true;
            }
        }

        return false;
    }

    private function criarIntencaoAuditoria(
        ContextoFinalizacaoProcessamentoUnidade $contexto,
        SequencialPca $sequencial
    ): IntencaoRegistroHistorico {
        $decisao = $contexto->getResultadoDecisao();
        $procedimento = match ($decisao->getDecisao()) {
            DecisaoProcessamentoPlano::PUBLICAR => ProcedimentoPublicacaoPca::PUBLICACAO_INICIAL,
            DecisaoProcessamentoPlano::RETIFICAR => ProcedimentoPublicacaoPca::RETIFICACAO,
            DecisaoProcessamentoPlano::EXCLUIR_PCA => ProcedimentoPublicacaoPca::EXCLUSAO_INTEGRAL,
            DecisaoProcessamentoPlano::NENHUMA_ACAO => throw new InvalidArgumentException(
                'NENHUMA_ACAO não gera auditoria.'
            ),
        };
        $unidade = $contexto->getUnidadeExterna();
        $identificacao = $unidade->getModo() === ModoPublicacao::CONSOLIDADO
            ? 'Consolidado'
            : 'UO: ' . $unidade->getIdentidadePlano()
            ?->getUnidadeOrcamentaria()
            ->getIdentidade()
            ->getValor();
        $mensagem = sprintf(
            'Exercício: %d; %s; UASG externa: %s; sequencialPca: %d; ação confirmada: %s.',
            $unidade->getAnoExecucao()->getValor(),
            $identificacao,
            $unidade->getUasgExterna()->getCodigo(),
            $sequencial->getValor(),
            $decisao->getDecisao()->value
        );

        if ($decisao->getDecisao() === DecisaoProcessamentoPlano::RETIFICAR) {
            $mensagem .= sprintf(
                ' Itens incluídos: %d; itens excluídos: %d.',
                count($decisao->getDemandasAIncluir()),
                count($decisao->getDemandasAExcluir())
            );
        }

        return new IntencaoRegistroHistorico(
            $procedimento,
            $mensagem,
            $contexto->getContextoUsuario()->getRepresentante(),
            $contexto->getContextoUsuario()->getLotacao()
        );
    }

    private function estadosIguais(
        EstadoOperacionalPublicacao $primeiro,
        EstadoOperacionalPublicacao $segundo
    ): bool {
        if ($primeiro->getEstado() !== $segundo->getEstado()) {
            return false;
        }

        $sequencialPrimeiro = $primeiro->getSequencialPca();
        $sequencialSegundo = $segundo->getSequencialPca();

        if ($sequencialPrimeiro === null || $sequencialSegundo === null) {
            return $sequencialPrimeiro === $sequencialSegundo;
        }

        return $sequencialPrimeiro->equals($sequencialSegundo);
    }

    private function falhaReconciliavelEDesfazer(
        ContextoFinalizacaoProcessamentoUnidade $contexto,
        FalhaSegura $falha
    ): DesfechoProcessamentoUnidade {
        $this->registrarLog(
            'warning',
            AcaoProcessamentoUnidade::VALIDAR_CONVERGENCIA,
            $falha->getMensagem(),
            $contexto,
            $falha
        );
        $decisao = $contexto->getResultadoDecisao();

        return $this->desfazedor->desfazerPreservando(
            DesfechoProcessamentoUnidade::resultadoUnitario(
                ResultadoProcessamentoUnidade::falhaReconciliavel(
                    $contexto->getUnidadeExterna(),
                    $decisao->getDecisao(),
                    $this->sequencialSeguroDoContexto($contexto),
                    count($decisao->getDemandasAIncluir()),
                    count($decisao->getDemandasAExcluir()),
                    $falha
                )
            )
        );
    }

    private function bloquearEDesfazer(
        ContextoFinalizacaoProcessamentoUnidade $contexto,
        FalhaSegura $falha
    ): DesfechoProcessamentoUnidade {
        $this->registrarLog(
            'warning',
            AcaoProcessamentoUnidade::VALIDAR_CONVERGENCIA,
            $falha->getMensagem(),
            $contexto,
            $falha
        );
        $decisao = $contexto->getResultadoDecisao();
        $decisaoResultado = $decisao->getDecisao() === DecisaoProcessamentoPlano::NENHUMA_ACAO
            ? null
            : $decisao->getDecisao();

        return $this->desfazedor->desfazerPreservando(
            DesfechoProcessamentoUnidade::resultadoUnitario(
                ResultadoProcessamentoUnidade::bloqueado(
                    $contexto->getUnidadeExterna(),
                    $decisaoResultado,
                    $decisaoResultado === null ? null : $this->sequencialSeguroDoContexto($contexto),
                    $decisaoResultado === null ? 0 : count($decisao->getDemandasAIncluir()),
                    $decisaoResultado === null ? 0 : count($decisao->getDemandasAExcluir()),
                    $falha
                )
            )
        );
    }

    private function sequencialSeguroDoContexto(
        ContextoFinalizacaoProcessamentoUnidade $contexto
    ): ?SequencialPca {
        $decisao = $contexto->getResultadoDecisao()->getDecisao();
        $sequencial = $this->obterSequencialParaObservacao(
            $contexto,
            $contexto->getResultadosEscrita()
        );

        if (
            $sequencial === null
            && in_array(
                $decisao,
                [DecisaoProcessamentoPlano::RETIFICAR, DecisaoProcessamentoPlano::EXCLUIR_PCA],
                true
            )
        ) {
            throw new InvalidArgumentException('Decisão exige sequencial conhecido.');
        }

        return $sequencial;
    }

    private function interromperEDesfazer(
        FalhaSegura $falha
    ): DesfechoProcessamentoUnidade {
        $this->registrarLogSemContexto(
            'error',
            AcaoProcessamentoUnidade::DESFAZER_UNIDADE,
            $falha->getMensagem(),
            $falha
        );

        return $this->desfazedor->desfazerPreservando(
            DesfechoProcessamentoUnidade::falhaSistemica($falha)
        );
    }

    private function registrarLog(
        string $nivel,
        AcaoProcessamentoUnidade $acao,
        string $mensagem,
        ContextoFinalizacaoProcessamentoUnidade $contexto,
        ?FalhaSegura $falha = null
    ): void {
        try {
            $this->logger->log($nivel, $mensagem, [
                'action' => $acao->value,
                'error' => $this->mapearFalhaParaLog($falha),
                'context' => [
                    'anoExecucao' => $contexto->getUnidadeExterna()->getAnoExecucao()->getValor(),
                    'uasg' => $contexto->getUnidadeExterna()->getUasgExterna()->getCodigo(),
                    'modo' => $contexto->getUnidadeExterna()->getModo()->value,
                ],
            ]);
        } catch (Throwable) {
            // Observabilidade nunca altera o desfecho da transação de negócio.
        }
    }

    private function registrarLogSemContexto(
        string $nivel,
        AcaoProcessamentoUnidade $acao,
        string $mensagem,
        ?FalhaSegura $falha = null
    ): void {
        try {
            $this->logger->log($nivel, $mensagem, [
                'action' => $acao->value,
                'error' => $this->mapearFalhaParaLog($falha),
            ]);
        } catch (Throwable) {
            // Observabilidade nunca altera o desfecho da transação de negócio.
        }
    }

    /** @return array<string, string> */
    private function mapearFalhaParaLog(?FalhaSegura $falha): array
    {
        if ($falha === null) {
            return [];
        }

        return [
            'code' => $falha->getCodigo(),
            'message' => $falha->getMensagem(),
        ];
    }
}
