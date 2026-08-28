<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Results;

use Semae\Pca\Application\Enums\SituacaoResolucaoContextoUsuario;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Application\ValueObjects\FalhaSegura;

final class ResultadoResolucaoContextoUsuario
{
    private function __construct(
        private readonly SituacaoResolucaoContextoUsuario $situacao,
        private readonly ?ContextoUsuario $contexto,
        private readonly ?FalhaSegura $falha
    ) {}

    public static function resolvido(ContextoUsuario $contexto): self
    {
        return new self(SituacaoResolucaoContextoUsuario::RESOLVIDO, $contexto, null);
    }

    public static function naoResolvido(FalhaSegura $falha): self
    {
        return new self(SituacaoResolucaoContextoUsuario::NAO_RESOLVIDO, null, $falha);
    }

    public static function falhaSistemica(FalhaSegura $falha): self
    {
        return new self(SituacaoResolucaoContextoUsuario::FALHA_SISTEMICA, null, $falha);
    }

    public function getSituacao(): SituacaoResolucaoContextoUsuario
    {
        return $this->situacao;
    }

    public function getContexto(): ?ContextoUsuario
    {
        return $this->contexto;
    }

    public function getFalha(): ?FalhaSegura
    {
        return $this->falha;
    }
}
