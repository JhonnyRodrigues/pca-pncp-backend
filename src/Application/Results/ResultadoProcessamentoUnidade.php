<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Results;

use InvalidArgumentException;
use Semae\Pca\Application\Enums\SituacaoProcessamentoUnidade;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;
use Semae\Pca\Domain\ValueObjects\SequencialPca;

final class ResultadoProcessamentoUnidade
{
    private function __construct(
        private readonly UnidadeExternaPublicacao $unidadeExterna,
        private readonly SituacaoProcessamentoUnidade $situacao,
        private readonly ?DecisaoProcessamentoPlano $decisao,
        private readonly ?SequencialPca $sequencialPca,
        private readonly int $quantidadeInclusoes,
        private readonly int $quantidadeExclusoes,
        private readonly ?FalhaSegura $falha
    ) {}

    public static function concluido(
        UnidadeExternaPublicacao $unidadeExterna,
        DecisaoProcessamentoPlano $decisao,
        SequencialPca $sequencialPca,
        int $quantidadeInclusoes,
        int $quantidadeExclusoes
    ): self {
        if ($decisao === DecisaoProcessamentoPlano::NENHUMA_ACAO) {
            throw new InvalidArgumentException(
                'CONCLUIDO exige uma decisão com escrita externa.'
            );
        }

        self::validarContagens($decisao, $quantidadeInclusoes, $quantidadeExclusoes);

        return new self(
            $unidadeExterna,
            SituacaoProcessamentoUnidade::CONCLUIDO,
            $decisao,
            $sequencialPca,
            $quantidadeInclusoes,
            $quantidadeExclusoes,
            null
        );
    }

    public static function semAlteracao(
        UnidadeExternaPublicacao $unidadeExterna,
        ?SequencialPca $sequencialPca = null
    ): self {
        return new self(
            $unidadeExterna,
            SituacaoProcessamentoUnidade::SEM_ALTERACAO,
            DecisaoProcessamentoPlano::NENHUMA_ACAO,
            $sequencialPca,
            0,
            0,
            null
        );
    }

    public static function falhaReconciliavel(
        UnidadeExternaPublicacao $unidadeExterna,
        ?DecisaoProcessamentoPlano $decisao,
        ?SequencialPca $sequencialPca,
        int $quantidadeInclusoes,
        int $quantidadeExclusoes,
        FalhaSegura $falha
    ): self {
        return self::comPendencia(
            $unidadeExterna,
            SituacaoProcessamentoUnidade::FALHA_RECONCILIAVEL,
            $decisao,
            $sequencialPca,
            $quantidadeInclusoes,
            $quantidadeExclusoes,
            $falha
        );
    }

    public static function bloqueado(
        UnidadeExternaPublicacao $unidadeExterna,
        ?DecisaoProcessamentoPlano $decisao,
        ?SequencialPca $sequencialPca,
        int $quantidadeInclusoes,
        int $quantidadeExclusoes,
        FalhaSegura $falha
    ): self {
        return self::comPendencia(
            $unidadeExterna,
            SituacaoProcessamentoUnidade::BLOQUEADO,
            $decisao,
            $sequencialPca,
            $quantidadeInclusoes,
            $quantidadeExclusoes,
            $falha
        );
    }

    public function getUnidadeExterna(): UnidadeExternaPublicacao
    {
        return $this->unidadeExterna;
    }

    public function getSituacao(): SituacaoProcessamentoUnidade
    {
        return $this->situacao;
    }

    public function getDecisao(): ?DecisaoProcessamentoPlano
    {
        return $this->decisao;
    }

    public function getSequencialPca(): ?SequencialPca
    {
        return $this->sequencialPca;
    }

    public function getQuantidadeInclusoes(): int
    {
        return $this->quantidadeInclusoes;
    }

    public function getQuantidadeExclusoes(): int
    {
        return $this->quantidadeExclusoes;
    }

    public function getFalha(): ?FalhaSegura
    {
        return $this->falha;
    }

    private static function comPendencia(
        UnidadeExternaPublicacao $unidadeExterna,
        SituacaoProcessamentoUnidade $situacao,
        ?DecisaoProcessamentoPlano $decisao,
        ?SequencialPca $sequencialPca,
        int $quantidadeInclusoes,
        int $quantidadeExclusoes,
        FalhaSegura $falha
    ): self {
        if ($decisao === null) {
            if ($quantidadeInclusoes < 0 || $quantidadeExclusoes < 0) {
                throw new InvalidArgumentException(
                    'Contagens do processamento não podem ser negativas.'
                );
            }

            if ($quantidadeInclusoes !== 0 || $quantidadeExclusoes !== 0) {
                throw new InvalidArgumentException(
                    'Resultado sem decisão não pode possuir contagens.'
                );
            }
        } else {
            if ($decisao === DecisaoProcessamentoPlano::NENHUMA_ACAO) {
                throw new InvalidArgumentException(
                    'Pendência unitária não admite a decisão NENHUMA_ACAO.'
                );
            }

            self::validarContagens($decisao, $quantidadeInclusoes, $quantidadeExclusoes);

            if (
                $sequencialPca === null
                && in_array(
                    $decisao,
                    [
                        DecisaoProcessamentoPlano::RETIFICAR,
                        DecisaoProcessamentoPlano::EXCLUIR_PCA,
                    ],
                    true
                )
            ) {
                throw new InvalidArgumentException(
                    'Decisão sobre PCA existente exige sequencial conhecido.'
                );
            }
        }

        return new self(
            $unidadeExterna,
            $situacao,
            $decisao,
            $sequencialPca,
            $quantidadeInclusoes,
            $quantidadeExclusoes,
            $falha
        );
    }

    private static function validarContagens(
        DecisaoProcessamentoPlano $decisao,
        int $quantidadeInclusoes,
        int $quantidadeExclusoes
    ): void {
        if ($quantidadeInclusoes < 0 || $quantidadeExclusoes < 0) {
            throw new InvalidArgumentException(
                'Contagens do processamento não podem ser negativas.'
            );
        }

        $contagensValidas = match ($decisao) {
            DecisaoProcessamentoPlano::PUBLICAR => $quantidadeInclusoes > 0
                && $quantidadeExclusoes === 0,
            DecisaoProcessamentoPlano::RETIFICAR => $quantidadeInclusoes > 0
                || $quantidadeExclusoes > 0,
            DecisaoProcessamentoPlano::EXCLUIR_PCA,
            DecisaoProcessamentoPlano::NENHUMA_ACAO => $quantidadeInclusoes === 0
                && $quantidadeExclusoes === 0,
        };

        if (!$contagensValidas) {
            throw new InvalidArgumentException(sprintf(
                'Contagens incompatíveis com a decisão %s.',
                $decisao->value
            ));
        }
    }
}
