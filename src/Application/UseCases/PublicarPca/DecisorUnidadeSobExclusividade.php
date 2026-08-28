<?php

declare(strict_types=1);

namespace Semae\Pca\Application\UseCases\PublicarPca;

use InvalidArgumentException;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Enums\SituacaoBuscaCandidatosPca;
use Semae\Pca\Application\Enums\SituacaoObservacaoPca;
use Semae\Pca\Application\Ports\LeitorPncp;
use Semae\Pca\Application\Ports\UnidadeDeTrabalhoPort;
use Semae\Pca\Application\Results\DesfechoProcessamentoUnidade;
use Semae\Pca\Application\Results\ResultadoBuscaCandidatosPca;
use Semae\Pca\Application\Results\ResultadoObservacaoPca;
use Semae\Pca\Application\Results\ResultadoProcessamentoUnidade;
use Semae\Pca\Application\ValueObjects\ContextoUnidadeComDecisao;
use Semae\Pca\Application\ValueObjects\ContextoUnidadeSobExclusividade;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Enums\EstadoPublicacao;
use Semae\Pca\Domain\Exceptions\ConflitoEstadoPublicacaoException;
use Semae\Pca\Domain\Policies\PoliticaProcessamentoPlano;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\PcaObservado;
use Throwable;

final class DecisorUnidadeSobExclusividade
{
    private readonly DesfazedorUnidadeProcessamento $desfazedor;

    public function __construct(
        private readonly LeitorPncp $leitorPncp,
        private readonly PoliticaProcessamentoPlano $politica,
        UnidadeDeTrabalhoPort $unidadeDeTrabalho
    ) {
        $this->desfazedor = new DesfazedorUnidadeProcessamento($unidadeDeTrabalho);
    }

    public function decidir(
        SessaoPncp $sessao,
        Cnpj $cnpj,
        ContextoUnidadeSobExclusividade $contexto
    ): ContextoUnidadeComDecisao|DesfechoProcessamentoUnidade {
        $observacao = $this->observar($sessao, $cnpj, $contexto);

        if ($observacao instanceof DesfechoProcessamentoUnidade) {
            return $observacao;
        }

        try {
            $decisao = $this->politica->decidir(
                $contexto->getPlanos(),
                $contexto->getEstadoOperacional(),
                $observacao
            );
        } catch (ConflitoEstadoPublicacaoException | InvalidArgumentException) {
            return $this->bloquearEDesfazer(
                $contexto,
                new FalhaSegura(
                    'DECISAO_DOMINIO_CONFLITANTE',
                    'Os fatos observados não permitem uma decisão segura.'
                )
            );
        } catch (Throwable) {
            return $this->interromperEDesfazer(new FalhaSegura(
                'DECISAO_DOMINIO_FALHOU',
                'Não foi possível decidir o processamento da unidade.'
            ));
        }

        return new ContextoUnidadeComDecisao(
            $sessao,
            $cnpj,
            $contexto,
            $observacao,
            $decisao
        );
    }

    private function observar(
        SessaoPncp $sessao,
        Cnpj $cnpj,
        ContextoUnidadeSobExclusividade $contexto
    ): PcaObservado|DesfechoProcessamentoUnidade {
        $estado = $contexto->getEstadoOperacional();

        if ($estado->getEstado() === EstadoPublicacao::PUBLICADO) {
            $sequencial = $estado->getSequencialPca();

            if ($sequencial === null) {
                return $this->interromperEDesfazer(new FalhaSegura(
                    'ESTADO_PUBLICADO_SEM_SEQUENCIAL',
                    'O estado publicado não forneceu sequencial conhecido.'
                ));
            }

            try {
                $resultado = $this->leitorPncp->observarPcaConhecido(
                    $sessao,
                    $cnpj,
                    $contexto->getUnidadeExterna(),
                    $sequencial
                );
            } catch (Throwable) {
                return $this->interromperEDesfazer(new FalhaSegura(
                    'OBSERVACAO_PNCP_FALHOU',
                    'Não foi possível observar o PCA conhecido.'
                ));
            }

            return $this->classificarObservacaoConhecida($contexto, $resultado);
        }

        try {
            $resultado = $this->leitorPncp->localizarCandidatos(
                $sessao,
                $cnpj,
                $contexto->getUnidadeExterna()
            );
        } catch (Throwable) {
            return $this->interromperEDesfazer(new FalhaSegura(
                'OBSERVACAO_PNCP_FALHOU',
                'Não foi possível localizar candidatos externos.'
            ));
        }

        return $this->classificarBuscaCandidatos($contexto, $resultado);
    }

    private function classificarObservacaoConhecida(
        ContextoUnidadeSobExclusividade $contexto,
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
                'OBSERVACAO_PNCP_INCONSISTENTE',
                'A observação concluída não forneceu um fato externo.'
            ));
        }

        $falha = $this->falhaOuDiagnostico(
            $resultado->getFalha(),
            'OBSERVACAO_PNCP_INDEFINIDA',
            'A observação do PCA conhecido não foi conclusiva.'
        );

        return match ($resultado->getSituacao()) {
            SituacaoObservacaoPca::INCONCLUSIVA => $this->falhaReconciliavelEDesfazer(
                $contexto,
                $falha
            ),
            SituacaoObservacaoPca::CONFLITO => $this->bloquearEDesfazer(
                $contexto,
                $falha
            ),
            SituacaoObservacaoPca::FALHA_SISTEMICA => $this->interromperEDesfazer($falha),
            SituacaoObservacaoPca::ENCONTRADO,
            SituacaoObservacaoPca::AUSENCIA_DEFINITIVA => $this->interromperEDesfazer($falha),
        };
    }

    private function classificarBuscaCandidatos(
        ContextoUnidadeSobExclusividade $contexto,
        ResultadoBuscaCandidatosPca $resultado
    ): PcaObservado|DesfechoProcessamentoUnidade {
        if ($resultado->getSituacao() === SituacaoBuscaCandidatosPca::CONCLUIDA) {
            if ($resultado->getCandidatos() === []) {
                return PcaObservado::ausente();
            }

            return $this->bloquearEDesfazer(
                $contexto,
                new FalhaSegura(
                    'CANDIDATO_EXTERNO_SEM_VINCULO',
                    'Foi localizado PCA externo sem vínculo operacional conhecido.'
                )
            );
        }

        $falha = $this->falhaOuDiagnostico(
            $resultado->getFalha(),
            'BUSCA_CANDIDATOS_INDEFINIDA',
            'A busca de candidatos externos não foi conclusiva.'
        );

        return match ($resultado->getSituacao()) {
            SituacaoBuscaCandidatosPca::INCONCLUSIVA => $this->falhaReconciliavelEDesfazer(
                $contexto,
                $falha
            ),
            SituacaoBuscaCandidatosPca::CONFLITO => $this->bloquearEDesfazer(
                $contexto,
                $falha
            ),
            SituacaoBuscaCandidatosPca::FALHA_SISTEMICA => $this->interromperEDesfazer($falha),
            SituacaoBuscaCandidatosPca::CONCLUIDA => $this->interromperEDesfazer($falha),
        };
    }

    private function falhaReconciliavelEDesfazer(
        ContextoUnidadeSobExclusividade $contexto,
        FalhaSegura $falha
    ): DesfechoProcessamentoUnidade {
        return $this->desfazedor->desfazerPreservando(
            DesfechoProcessamentoUnidade::resultadoUnitario(
                ResultadoProcessamentoUnidade::falhaReconciliavel(
                    $contexto->getUnidadeExterna(),
                    null,
                    $contexto->getEstadoOperacional()->getSequencialPca(),
                    0,
                    0,
                    $falha
                )
            )
        );
    }

    private function bloquearEDesfazer(
        ContextoUnidadeSobExclusividade $contexto,
        FalhaSegura $falha
    ): DesfechoProcessamentoUnidade {
        return $this->desfazedor->desfazerPreservando(
            DesfechoProcessamentoUnidade::resultadoUnitario(
                ResultadoProcessamentoUnidade::bloqueado(
                    $contexto->getUnidadeExterna(),
                    null,
                    $contexto->getEstadoOperacional()->getSequencialPca(),
                    0,
                    0,
                    $falha
                )
            )
        );
    }

    private function interromperEDesfazer(
        FalhaSegura $falha
    ): DesfechoProcessamentoUnidade {
        return $this->desfazedor->desfazerPreservando(
            DesfechoProcessamentoUnidade::falhaSistemica($falha)
        );
    }

    private function falhaOuDiagnostico(
        ?FalhaSegura $falha,
        string $codigo,
        string $mensagem
    ): FalhaSegura {
        return $falha ?? new FalhaSegura($codigo, $mensagem);
    }
}
