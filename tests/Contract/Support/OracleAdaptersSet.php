<?php

declare(strict_types=1);

namespace Tests\Contract\Support;

use Semae\Pca\Composition\ComposicaoOraclePublicacaoPca;
use Semae\Pca\Infrastructure\Oracle\Auditoria\OracleRegistroAuditoriaAdapter;
use Semae\Pca\Infrastructure\Oracle\Contexto\OracleContextoUsuarioAdapter;
use Semae\Pca\Infrastructure\Oracle\EstadoOperacional\OracleEstadoOperacionalPublicacaoAdapter;
use Semae\Pca\Infrastructure\Oracle\Estrategia\OracleEstrategiaPublicacaoAdapter;
use Semae\Pca\Infrastructure\Oracle\Plano\OraclePlanoRepository;
use Semae\Pca\Infrastructure\Oracle\Transacao\OracleUnidadeDeTrabalhoAdapter;

final class OracleAdaptersSet
{
    public function __construct(
        private readonly OracleContextoUsuarioAdapter $context,
        private readonly OraclePlanoRepository $plans,
        private readonly OracleEstrategiaPublicacaoAdapter $strategy,
        private readonly OracleUnidadeDeTrabalhoAdapter $unitOfWork,
        private readonly OracleEstadoOperacionalPublicacaoAdapter $state,
        private readonly OracleRegistroAuditoriaAdapter $audit
    ) {
    }

    public static function fromProductionComposition(
        ComposicaoOraclePublicacaoPca $composition
    ): self {
        return new self(
            $composition->getContextoUsuario(),
            $composition->getPlanoRepository(),
            $composition->getEstrategiaPublicacao(),
            $composition->getUnidadeDeTrabalho(),
            $composition->getEstadoOperacional(),
            $composition->getRegistroAuditoria()
        );
    }

    public function getContext(): OracleContextoUsuarioAdapter
    {
        return $this->context;
    }

    public function getPlans(): OraclePlanoRepository
    {
        return $this->plans;
    }

    public function getStrategy(): OracleEstrategiaPublicacaoAdapter
    {
        return $this->strategy;
    }

    public function getUnitOfWork(): OracleUnidadeDeTrabalhoAdapter
    {
        return $this->unitOfWork;
    }

    public function getState(): OracleEstadoOperacionalPublicacaoAdapter
    {
        return $this->state;
    }

    public function getAudit(): OracleRegistroAuditoriaAdapter
    {
        return $this->audit;
    }
}
