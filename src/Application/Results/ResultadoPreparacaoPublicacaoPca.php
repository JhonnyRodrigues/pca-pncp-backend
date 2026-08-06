<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Results;

use InvalidArgumentException;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Enums\SituacaoPreparacaoPublicacaoPca;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Application\ValueObjects\EstrategiaPublicacao;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;

final class ResultadoPreparacaoPublicacaoPca
{
    /**
     * @param UnidadeExternaPublicacao[] $unidadesExternas
     */
    private function __construct(
        private readonly SituacaoPreparacaoPublicacaoPca $situacao,
        private readonly ?ContextoUsuario $contextoUsuario,
        private readonly ?EstrategiaPublicacao $estrategia,
        private readonly array $unidadesExternas,
        private readonly ?SessaoPncp $sessaoPncp,
        private readonly ?FalhaSegura $falha
    ) {}

    /**
     * @param UnidadeExternaPublicacao[] $unidadesExternas
     */
    public static function preparada(
        ContextoUsuario $contextoUsuario,
        EstrategiaPublicacao $estrategia,
        array $unidadesExternas,
        SessaoPncp $sessaoPncp
    ): self {
        if ($unidadesExternas === []) {
            throw new InvalidArgumentException('A preparação concluída exige unidades externas.');
        }

        foreach ($unidadesExternas as $unidadeExterna) {
            if (!$unidadeExterna instanceof UnidadeExternaPublicacao) {
                throw new InvalidArgumentException('Unidade externa de publicação inválida.');
            }
        }

        return new self(
            SituacaoPreparacaoPublicacaoPca::PREPARADA,
            $contextoUsuario,
            $estrategia,
            array_values($unidadesExternas),
            $sessaoPncp,
            null
        );
    }

    /** @param UnidadeExternaPublicacao[] $unidadesExternas */
    public static function interrompida(
        FalhaSegura $falha,
        array $unidadesExternas = []
    ): self
    {
        self::validarUnidades($unidadesExternas);

        return new self(
            SituacaoPreparacaoPublicacaoPca::INTERROMPIDA,
            null,
            null,
            array_values($unidadesExternas),
            null,
            $falha
        );
    }

    public static function semUnidades(): self
    {
        return new self(
            SituacaoPreparacaoPublicacaoPca::SEM_UNIDADES,
            null,
            null,
            [],
            null,
            null
        );
    }

    public function getSituacao(): SituacaoPreparacaoPublicacaoPca
    {
        return $this->situacao;
    }

    public function getContextoUsuario(): ?ContextoUsuario
    {
        return $this->contextoUsuario;
    }

    public function getEstrategia(): ?EstrategiaPublicacao
    {
        return $this->estrategia;
    }

    /**
     * @return UnidadeExternaPublicacao[]
     */
    public function getUnidadesExternas(): array
    {
        return $this->unidadesExternas;
    }

    public function getSessaoPncp(): ?SessaoPncp
    {
        return $this->sessaoPncp;
    }

    public function getFalha(): ?FalhaSegura
    {
        return $this->falha;
    }

    /** @param mixed[] $unidadesExternas */
    private static function validarUnidades(array $unidadesExternas): void
    {
        foreach ($unidadesExternas as $unidadeExterna) {
            if (!$unidadeExterna instanceof UnidadeExternaPublicacao) {
                throw new InvalidArgumentException('Unidade externa de publicação inválida.');
            }
        }
    }
}
