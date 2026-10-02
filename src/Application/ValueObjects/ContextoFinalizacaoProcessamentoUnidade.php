<?php

declare(strict_types=1);

namespace Semae\Pca\Application\ValueObjects;

use InvalidArgumentException;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Enums\SituacaoEscritaPncp;
use Semae\Pca\Application\Results\ResultadoEscritaPncp;
use Semae\Pca\Application\Results\ResultadoPublicacaoPncp;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;
use Semae\Pca\Domain\Policies\ResultadoDecisaoProcessamentoPlano;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;
use Semae\Pca\Domain\ValueObjects\PcaObservado;

final class ContextoFinalizacaoProcessamentoUnidade
{
    /** @var Plano[] */
    private readonly array $planos;

    /** @var array<ResultadoEscritaPncp|ResultadoPublicacaoPncp> */
    private readonly array $resultadosEscrita;

    /**
     * @param Plano[] $planos
     * @param array<ResultadoEscritaPncp|ResultadoPublicacaoPncp> $resultadosEscrita
     */
    public function __construct(
        private readonly ContextoUsuario $contextoUsuario,
        private readonly SessaoPncp $sessaoPncp,
        private readonly Cnpj $cnpj,
        private readonly UnidadeExternaPublicacao $unidadeExterna,
        array $planos,
        private readonly EstadoOperacionalPublicacao $estadoOperacionalInicial,
        private readonly PcaObservado $pcaObservadoInicial,
        private readonly ResultadoDecisaoProcessamentoPlano $resultadoDecisao,
        array $resultadosEscrita
    ) {
        $this->planos = self::validarPlanos($planos);
        $this->resultadosEscrita = self::validarResultadosEscrita(
            $resultadoDecisao,
            $resultadosEscrita
        );
    }

    public function getContextoUsuario(): ContextoUsuario
    {
        return $this->contextoUsuario;
    }

    public function getSessaoPncp(): SessaoPncp
    {
        return $this->sessaoPncp;
    }

    public function getCnpj(): Cnpj
    {
        return $this->cnpj;
    }

    public function getUnidadeExterna(): UnidadeExternaPublicacao
    {
        return $this->unidadeExterna;
    }

    /** @return Plano[] */
    public function getPlanos(): array
    {
        return $this->planos;
    }

    public function getEstadoOperacionalInicial(): EstadoOperacionalPublicacao
    {
        return $this->estadoOperacionalInicial;
    }

    public function getPcaObservadoInicial(): PcaObservado
    {
        return $this->pcaObservadoInicial;
    }

    public function getResultadoDecisao(): ResultadoDecisaoProcessamentoPlano
    {
        return $this->resultadoDecisao;
    }

    /** @return array<ResultadoEscritaPncp|ResultadoPublicacaoPncp> */
    public function getResultadosEscrita(): array
    {
        return $this->resultadosEscrita;
    }

    /**
     * @param Plano[] $planos
     * @return Plano[]
     */
    private static function validarPlanos(array $planos): array
    {
        if ($planos === []) {
            throw new InvalidArgumentException('Finalização exige ao menos um Plano relido.');
        }

        foreach ($planos as $plano) {
            if (!$plano instanceof Plano) {
                throw new InvalidArgumentException('Finalização deve receber apenas Planos.');
            }
        }

        return array_values($planos);
    }

    /**
     * @param array<ResultadoEscritaPncp|ResultadoPublicacaoPncp> $resultados
     * @return array<ResultadoEscritaPncp|ResultadoPublicacaoPncp>
     */
    private static function validarResultadosEscrita(
        ResultadoDecisaoProcessamentoPlano $resultadoDecisao,
        array $resultados
    ): array {
        foreach ($resultados as $resultado) {
            if (
                !$resultado instanceof ResultadoEscritaPncp
                && !$resultado instanceof ResultadoPublicacaoPncp
            ) {
                throw new InvalidArgumentException('Resultado de escrita inválido.');
            }

            if ($resultado->getSituacao() === SituacaoEscritaPncp::FALHA_SISTEMICA) {
                throw new InvalidArgumentException(
                    'Falha sistêmica de escrita não pode ser encaminhada ao finalizador.'
                );
            }
        }

        $decisao = $resultadoDecisao->getDecisao();

        if ($decisao === DecisaoProcessamentoPlano::NENHUMA_ACAO) {
            if ($resultados !== []) {
                throw new InvalidArgumentException(
                    'NENHUMA_ACAO não admite resultado de escrita.'
                );
            }

            return [];
        }

        $compativel = match ($decisao) {
            DecisaoProcessamentoPlano::PUBLICAR => count($resultados) === 1
                && $resultados[0] instanceof ResultadoPublicacaoPncp,
            DecisaoProcessamentoPlano::RETIFICAR => count($resultados) >= 1
                && count($resultados) <= 2
                && self::contemSomenteResultadosSemSequencial($resultados),
            DecisaoProcessamentoPlano::EXCLUIR_PCA => count($resultados) === 1
                && $resultados[0] instanceof ResultadoEscritaPncp,
            DecisaoProcessamentoPlano::NENHUMA_ACAO => false,
        };

        if (!$compativel) {
            throw new InvalidArgumentException(
                'Resultado de escrita incompatível com a decisão.'
            );
        }

        return array_values($resultados);
    }

    /** @param array<ResultadoEscritaPncp|ResultadoPublicacaoPncp> $resultados */
    private static function contemSomenteResultadosSemSequencial(array $resultados): bool
    {
        foreach ($resultados as $resultado) {
            if (!$resultado instanceof ResultadoEscritaPncp) {
                return false;
            }
        }

        return true;
    }
}
