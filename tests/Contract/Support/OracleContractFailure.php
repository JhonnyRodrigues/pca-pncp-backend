<?php

declare(strict_types=1);

namespace Tests\Contract\Support;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class OracleContractFailure extends RuntimeException
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
        'provisionamento_tecnico',
        'lock_controlado',
        'commit_controlado',
        'verificacao_liberacao',
        'conexao_reutilizavel',
        'lock_aquisicao',
        'lock_retencao',
        'lock_finalizacao',
    ];

    public function __construct(private readonly string $stage, Throwable $cause)
    {
        if (!in_array($stage, self::STAGES, true)) {
            throw new InvalidArgumentException('Etapa Oracle contratual inválida.');
        }

        parent::__construct('O exercício contratual Oracle falhou.', 0, $cause);
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
