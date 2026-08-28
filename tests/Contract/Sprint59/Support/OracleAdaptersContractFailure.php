<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint59\Support;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class OracleAdaptersContractFailure extends RuntimeException
{
    private const STAGES = [
        'bind',
        'estrategia_corrente',
        'estrategia_adotada',
        'contexto_usuario',
        'identidade_plano',
        'inicio_transacao',
        'reconstrucao_plano',
        'consulta_estado',
        'confirmacao_plano',
        'registro_estado',
        'registro_auditoria',
        'rollback',
        'verificacao_rollback',
    ];

    public function __construct(private readonly string $stage, Throwable $cause)
    {
        if (!in_array($stage, self::STAGES, true)) {
            throw new InvalidArgumentException('Etapa Oracle contratual invalida.');
        }

        parent::__construct('O fluxo de adapters Oracle falhou.', 0, $cause);
    }

    public function getStage(): string
    {
        return $this->stage;
    }

    public function getCause(): Throwable
    {
        return $this->getPrevious() ?? $this;
    }
}
