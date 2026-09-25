<?php

declare(strict_types=1);

namespace Semae\Pca\Application\UseCases\PublicarPca;

use Psr\Log\LoggerInterface;
use Semae\Pca\Application\Contracts\FinalizadorProcessamentoUnidade;
use Semae\Pca\Application\Enums\AcaoProcessamentoUnidade;
use Semae\Pca\Application\Enums\SituacaoAutenticacaoPncp;
use Semae\Pca\Application\Ports\AutenticadorPncpPort;
use Semae\Pca\Application\Ports\UnidadeDeTrabalhoPort;
use Semae\Pca\Application\Results\DesfechoProcessamentoUnidade;
use Semae\Pca\Application\ValueObjects\ContextoFinalizacaoProcessamentoUnidade;
use Semae\Pca\Application\ValueObjects\ContextoUnidadeComDecisao;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;
use Throwable;

final class DespachadorComandosUnidade
{
    private readonly DesfazedorUnidadeProcessamento $desfazedor;

    public function __construct(
        private readonly ExecutorComandosUnidade $executor,
        private readonly FinalizadorProcessamentoUnidade $finalizador,
        UnidadeDeTrabalhoPort $unidadeDeTrabalho,
        private readonly AutenticadorPncpPort $autenticadorPncp,
        private readonly LoggerInterface $logger
    ) {
        $this->desfazedor = new DesfazedorUnidadeProcessamento($unidadeDeTrabalho);
    }

    public function despachar(
        ContextoUsuario $contextoUsuario,
        ContextoUnidadeComDecisao $contexto
    ): DesfechoProcessamentoUnidade {
        if ($contexto->getResultadoDecisao()->getDecisao() !== DecisaoProcessamentoPlano::NENHUMA_ACAO) {
            try {
                $autenticacao = $this->autenticadorPncp->autenticar();
            } catch (Throwable) {
                return $this->interromperEDesfazer(new FalhaSegura(
                    'AUTENTICACAO_PNCP_ANTES_ESCRITA_FALHOU',
                    'Não foi possível autenticar no PNCP antes da escrita.'
                ));
            }

            if ($autenticacao->getSituacao() !== SituacaoAutenticacaoPncp::AUTENTICADA) {
                return $this->interromperEDesfazer(
                    $autenticacao->getFalha() ?? new FalhaSegura(
                        'AUTENTICACAO_PNCP_ANTES_ESCRITA_FALHOU',
                        'Não foi possível autenticar no PNCP antes da escrita.'
                    )
                );
            }

            $sessao = $autenticacao->getSessao();

            if ($sessao === null) {
                return $this->interromperEDesfazer(new FalhaSegura(
                    'SESSAO_PNCP_ANTES_ESCRITA_INCONSISTENTE',
                    'A autenticação PNCP não forneceu sessão para a escrita.'
                ));
            }

            $contexto = new ContextoUnidadeComDecisao(
                $sessao,
                $contexto->getCnpj(),
                $contexto->getContextoSobExclusividade(),
                $contexto->getPcaObservado(),
                $contexto->getResultadoDecisao()
            );
        }

        $this->registrarItensPlanejados($contexto);

        try {
            $resultados = $this->executor->executar($contexto);
        } catch (Throwable) {
            return $this->interromperEDesfazer(new FalhaSegura(
                'DESPACHO_COMANDO_FALHOU',
                'Não foi possível despachar o comando da unidade.'
            ));
        }

        if ($resultados instanceof DesfechoProcessamentoUnidade) {
            return $resultados;
        }

        $sobExclusividade = $contexto->getContextoSobExclusividade();

        try {
            return $this->finalizador->finalizar(
                new ContextoFinalizacaoProcessamentoUnidade(
                    $contextoUsuario,
                    $contexto->getSessaoPncp(),
                    $contexto->getCnpj(),
                    $sobExclusividade->getUnidadeExterna(),
                    $sobExclusividade->getPlanos(),
                    $sobExclusividade->getEstadoOperacional(),
                    $contexto->getPcaObservado(),
                    $contexto->getResultadoDecisao(),
                    $resultados
                )
            );
        } catch (Throwable) {
            return $this->interromperEDesfazer(new FalhaSegura(
                'FINALIZACAO_UNIDADE_FALHOU',
                'Não foi possível finalizar o processamento da unidade.'
            ));
        }
    }

    private function registrarItensPlanejados(ContextoUnidadeComDecisao $contexto): void
    {
        $decisao = $contexto->getResultadoDecisao();

        if (!in_array(
            $decisao->getDecisao(),
            [DecisaoProcessamentoPlano::PUBLICAR, DecisaoProcessamentoPlano::RETIFICAR],
            true
        )) {
            return;
        }

        $unidade = $contexto->getContextoSobExclusividade()->getUnidadeExterna();

        try {
            $this->logger->info('Itens planejados para escrita no PNCP.', [
                'action' => AcaoProcessamentoUnidade::REGISTRAR_ITENS_PLANEJADOS->value,
                'context' => [
                    'anoExecucao' => $unidade->getAnoExecucao()->getValor(),
                    'uasg' => $unidade->getUasgExterna()->getCodigo(),
                    'modo' => $unidade->getModo()->value,
                    'decisao' => $decisao->getDecisao()->value,
                ],
                'metadata' => [
                    'numerosItensAIncluir' => $decisao->getDemandasAIncluir(),
                    'numerosItensAExcluir' => $decisao->getDemandasAExcluir(),
                ],
            ]);
        } catch (Throwable) {
            // Falha de observabilidade não altera o processamento da unidade.
        }
    }

    private function interromperEDesfazer(
        FalhaSegura $falha
    ): DesfechoProcessamentoUnidade {
        return $this->desfazedor->desfazerPreservando(
            DesfechoProcessamentoUnidade::falhaSistemica($falha)
        );
    }
}
