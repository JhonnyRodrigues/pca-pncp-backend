<?php

declare(strict_types=1);

namespace Semae\Pca\Application\UseCases\PublicarPca;

use Semae\Pca\Application\Commands\ComandoExcluirItensPca;
use Semae\Pca\Application\Commands\ComandoExcluirPca;
use Semae\Pca\Application\Commands\ComandoInserirItensPca;
use Semae\Pca\Application\Commands\ComandoPublicarPca;
use Semae\Pca\Application\Enums\SituacaoEscritaPncp;
use Semae\Pca\Application\Ports\EscritorPncpPort;
use Semae\Pca\Application\Ports\UnidadeDeTrabalhoPort;
use Semae\Pca\Application\Results\DesfechoProcessamentoUnidade;
use Semae\Pca\Application\Results\ResultadoEscritaPncp;
use Semae\Pca\Application\Results\ResultadoProcessamentoUnidade;
use Semae\Pca\Application\Results\ResultadoPublicacaoPncp;
use Semae\Pca\Application\ValueObjects\ContextoUnidadeComDecisao;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Domain\Entities\Demanda;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;
use Semae\Pca\Domain\ValueObjects\SequencialPca;

final class ExecutorComandosUnidade
{
    private const LIMITE_ITENS_PUBLICACAO = 1000;
    private const JUSTIFICATIVA_EXCLUSAO_PCA =
    'Exclusão integral do PCA por ausência de demandas vigentes no SGI.';

    private readonly DesfazedorUnidadeProcessamento $desfazedor;

    public function __construct(
        private readonly EscritorPncpPort $escritorPncp,
        UnidadeDeTrabalhoPort $unidadeDeTrabalho
    ) {
        $this->desfazedor = new DesfazedorUnidadeProcessamento($unidadeDeTrabalho);
    }

    /**
     * @return array<ResultadoEscritaPncp|ResultadoPublicacaoPncp>|DesfechoProcessamentoUnidade
     */
    public function executar(
        ContextoUnidadeComDecisao $contexto
    ): array|DesfechoProcessamentoUnidade {
        return match ($contexto->getResultadoDecisao()->getDecisao()) {
            DecisaoProcessamentoPlano::PUBLICAR => $this->publicar($contexto),
            DecisaoProcessamentoPlano::RETIFICAR => $this->retificar($contexto),
            DecisaoProcessamentoPlano::EXCLUIR_PCA => $this->excluirPca($contexto),
            DecisaoProcessamentoPlano::NENHUMA_ACAO => [],
        };
    }

    /** @return array{ResultadoPublicacaoPncp}|DesfechoProcessamentoUnidade */
    private function publicar(
        ContextoUnidadeComDecisao $contexto
    ): array|DesfechoProcessamentoUnidade {
        $demandas = $this->obterDemandasVigentes($contexto);

        if (count($demandas) > self::LIMITE_ITENS_PUBLICACAO) {
            return $this->bloquearEDesfazer(
                $contexto,
                new FalhaSegura(
                    'LIMITE_ITENS_PUBLICACAO_EXCEDIDO',
                    'A unidade excede o limite de 1000 itens para publicação.'
                )
            );
        }

        $resultado = $this->escritorPncp->publicar(
            $contexto->getSessaoPncp(),
            new ComandoPublicarPca(
                $contexto->getCnpj(),
                $contexto->getContextoSobExclusividade()->getUnidadeExterna(),
                $demandas
            )
        );

        $falhaSistemica = $this->classificarFalhaSistemicaEscrita($resultado);

        return $falhaSistemica ?? [$resultado];
    }

    /** @return ResultadoEscritaPncp[]|DesfechoProcessamentoUnidade */
    private function retificar(
        ContextoUnidadeComDecisao $contexto
    ): array|DesfechoProcessamentoUnidade {
        # verifica se a fotografia desejada não ultrapassa mil itens
        if (count($this->obterDemandasVigentes($contexto)) > self::LIMITE_ITENS_PUBLICACAO) {
            return $this->bloquearEDesfazer(
                $contexto,
                new FalhaSegura(
                    'LIMITE_ITENS_PUBLICACAO_EXCEDIDO',
                    'A unidade excede o limite de 1000 itens para publicação.'
                )
            );
        }

        # localiza os objetos Demanda correspondentes às inclusões
        $decisao = $contexto->getResultadoDecisao();
        $demandasAInserir = $this->obterDemandasPorIds($contexto, $decisao->getDemandasAIncluir());

        if ($demandasAInserir === null) {
            return $this->bloquearEDesfazer(
                $contexto,
                new FalhaSegura(
                    'DEMANDA_DE_INCLUSAO_NAO_LOCALIZADA',
                    'Uma Demanda indicada para inclusão não foi localizada na fotografia vigente.'
                )
            );
        }

        $sequencial = $this->obterSequencialExistente($contexto);
        
        # exige o sequencial do PCA existente
        if ($sequencial === null) {
            return $this->bloquearSemDecisaoEDesfazer(
                $contexto,
                new FalhaSegura(
                    'SEQUENCIAL_PCA_AUSENTE',
                    'A retificação exige sequencial de PCA conhecido.'
                )
            );
        }

        # executa exclusões antes das inclusões
        $resultados = [];
        $idsAExcluir = $decisao->getDemandasAExcluir();

        if ($idsAExcluir !== []) {
            $resultadoExclusao = $this->escritorPncp->excluirItens(
                $contexto->getSessaoPncp(),
                new ComandoExcluirItensPca(
                    $contexto->getCnpj(),
                    $contexto->getContextoSobExclusividade()->getUnidadeExterna(),
                    $sequencial,
                    $idsAExcluir,
                    self::justificativaExclusaoItens(count($idsAExcluir))
                )
            );
            $falhaSistemica = $this->classificarFalhaSistemicaEscrita($resultadoExclusao);

            if ($falhaSistemica !== null) {
                return $falhaSistemica;
            }

            $resultados[] = $resultadoExclusao;

            if ($resultadoExclusao->getSituacao() !== SituacaoEscritaPncp::ACEITA) {
                # Uma exclusão não aceita interrompe a sequência: a inclusão não é tentada, e o resultado segue ao finalizador para observação.
                return $resultados;
            }
        }

        # somente insere se a exclusão anterior foi aceita
        if ($demandasAInserir !== []) {
            $resultadoInclusao = $this->escritorPncp->inserirItens(
                $contexto->getSessaoPncp(),
                new ComandoInserirItensPca(
                    $contexto->getCnpj(),
                    $contexto->getContextoSobExclusividade()->getUnidadeExterna(),
                    $sequencial,
                    $demandasAInserir
                )
            );
            $falhaSistemica = $this->classificarFalhaSistemicaEscrita($resultadoInclusao);

            if ($falhaSistemica !== null) {
                return $falhaSistemica;
            }

            $resultados[] = $resultadoInclusao;
        }

        # Entrega ao finalizador os resultados das escritas realizadas para nova observação do PCA.
        return $resultados;
    }

    /** @return array{ResultadoEscritaPncp}|DesfechoProcessamentoUnidade */
    private function excluirPca(
        ContextoUnidadeComDecisao $contexto
    ): array|DesfechoProcessamentoUnidade {
        $sobExclusividade = $contexto->getContextoSobExclusividade();
        $sequencial = $this->obterSequencialExistente($contexto);

        if ($sequencial === null) {
            return $this->bloquearSemDecisaoEDesfazer(
                $contexto,
                new FalhaSegura(
                    'SEQUENCIAL_PCA_AUSENTE',
                    'A exclusão integral exige sequencial de PCA conhecido.'
                )
            );
        }

        $resultado = $this->escritorPncp->excluirPca(
            $contexto->getSessaoPncp(),
            new ComandoExcluirPca(
                $contexto->getCnpj(),
                $sobExclusividade->getUnidadeExterna(),
                $sequencial,
                self::JUSTIFICATIVA_EXCLUSAO_PCA
            )
        );
        $falhaSistemica = $this->classificarFalhaSistemicaEscrita($resultado);

        return $falhaSistemica ?? [$resultado];
    }

    /** @return Demanda[] */
    private function obterDemandasVigentes(ContextoUnidadeComDecisao $contexto): array
    {
        $demandas = [];

        foreach ($contexto->getContextoSobExclusividade()->getPlanos() as $plano) {
            foreach ($plano->getDemandas() as $demanda) {
                $demandas[] = $demanda;
            }
        }

        return $demandas;
    }

    /**
     * @param int[] $ids
     * @return Demanda[]|null
     */
    private function obterDemandasPorIds(
        ContextoUnidadeComDecisao $contexto,
        array $ids
    ): ?array {
        $porId = [];

        foreach ($this->obterDemandasVigentes($contexto) as $demanda) {
            $porId[$demanda->getIdDemanda()] = $demanda;
        }

        $demandas = [];

        foreach ($ids as $id) {
            if (!isset($porId[$id])) {
                return null;
            }

            $demandas[] = $porId[$id];
        }

        return $demandas;
    }

    private function obterSequencialExistente(
        ContextoUnidadeComDecisao $contexto
    ): ?SequencialPca {
        return $contexto->getContextoSobExclusividade()
            ->getEstadoOperacional()
            ->getSequencialPca();
    }

    private function classificarFalhaSistemicaEscrita(
        ResultadoEscritaPncp|ResultadoPublicacaoPncp $resultado
    ): ?DesfechoProcessamentoUnidade {
        if ($resultado->getSituacao() !== SituacaoEscritaPncp::FALHA_SISTEMICA) {
            return null;
        }

        # A falha sistêmica encerra o processamento da unidade e solicita imediatamente o rollback das alterações realizadas no banco local.
        return $this->interromperEDesfazer($this->falhaOuDiagnostico(
            $resultado->getFalha(),
            'ESCRITA_PNCP_FALHOU',
            'A escrita externa falhou de forma sistêmica.'
        ));
    }

    private static function justificativaExclusaoItens(int $quantidade): string
    {
        if ($quantidade === 1) {
            return 'Exclusão de 1 item por cancelamento homologado no SGI.';
        }

        return sprintf(
            'Exclusão de %d itens por cancelamentos homologados no SGI.',
            $quantidade
        );
    }

    private function bloquearEDesfazer(
        ContextoUnidadeComDecisao $contexto,
        FalhaSegura $falha
    ): DesfechoProcessamentoUnidade {
        $decisao = $contexto->getResultadoDecisao();

        return $this->desfazedor->desfazerPreservando(
            DesfechoProcessamentoUnidade::resultadoUnitario(
                ResultadoProcessamentoUnidade::bloqueado(
                    $contexto->getContextoSobExclusividade()->getUnidadeExterna(),
                    $decisao->getDecisao(),
                    $this->obterSequencialExistente($contexto),
                    count($decisao->getDemandasAIncluir()),
                    count($decisao->getDemandasAExcluir()),
                    $falha
                )
            )
        );
    }

    private function bloquearSemDecisaoEDesfazer(
        ContextoUnidadeComDecisao $contexto,
        FalhaSegura $falha
    ): DesfechoProcessamentoUnidade {
        return $this->desfazedor->desfazerPreservando(
            DesfechoProcessamentoUnidade::resultadoUnitario(
                ResultadoProcessamentoUnidade::bloqueado(
                    $contexto->getContextoSobExclusividade()->getUnidadeExterna(),
                    null,
                    null,
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
