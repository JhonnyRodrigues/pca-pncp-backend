<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint94\Support;

use LogicException;

final class Sprint94ReadOnlyAdodbGuard
{
    public function __construct(private readonly object $inner) {}

    /** @param array<int|string, mixed> $binds @return array<int, array<string, mixed>> */
    public function GetAll(string $sql, array $binds = []): array
    {
        $normalized = preg_replace('/\s+/', ' ', trim($sql));
        if (
            !is_string($normalized)
            || preg_match('/^SELECT\b/i', $normalized) !== 1
            || preg_match('/\b(INSERT|UPDATE|DELETE|MERGE|ALTER|DROP|CREATE|TRUNCATE|BEGIN|DECLARE|COMMIT|ROLLBACK)\b|;|FOR\s+UPDATE/i', $normalized) === 1
        ) {
            throw new LogicException('O diagnostico da Sprint 9.4 aceita apenas SELECT sem comando mutante.');
        }

        /** @var mixed $rows */
        $rows = $this->inner->GetAll($sql, $binds);
        if (!is_array($rows)) {
            throw new LogicException('A leitura Oracle nao retornou uma colecao valida.');
        }

        return $rows;
    }

    public function Param(string|false $name): string|bool
    {
        return $this->inner->Param($name);
    }

    public function ErrorNo(): int|string
    {
        return $this->inner->ErrorNo();
    }

    public function ErrorMsg(): string
    {
        return (string) $this->inner->ErrorMsg();
    }

    public function Execute(string $sql, array $binds = []): never
    {
        throw $this->mutationError();
    }

    public function BeginTrans(): never
    {
        throw $this->mutationError();
    }

    public function CommitTrans(): never
    {
        throw $this->mutationError();
    }

    public function RollbackTrans(): never
    {
        throw $this->mutationError();
    }

    public function Close(): never
    {
        throw $this->mutationError();
    }

    private function mutationError(): LogicException
    {
        return new LogicException('Operacao Oracle mutante proibida no diagnostico da Sprint 9.4.');
    }
}
