<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint59\Support;

use LogicException;

final class BorrowedAdodbGuard
{
    private bool $closeCalled = false;

    public function __construct(private readonly object $inner)
    {
    }

    public function BeginTrans(): mixed
    {
        return $this->inner->BeginTrans();
    }

    public function CommitTrans(): mixed
    {
        return $this->inner->CommitTrans();
    }

    public function RollbackTrans(): mixed
    {
        return $this->inner->RollbackTrans();
    }

    /** @param array<string, mixed> $parameters */
    public function Execute(string $sql, array $parameters = []): mixed
    {
        return $this->inner->Execute($sql, $parameters);
    }

    /** @param array<string, mixed> $parameters */
    public function GetAll(string $sql, array $parameters = []): mixed
    {
        return $this->inner->GetAll($sql, $parameters);
    }

    public function Param(string|false $name): mixed
    {
        return $this->inner->Param($name);
    }

    public function ErrorNo(): mixed
    {
        return $this->inner->ErrorNo();
    }

    public function ErrorMsg(): mixed
    {
        return $this->inner->ErrorMsg();
    }

    public function Close(): never
    {
        $this->closeCalled = true;
        throw new LogicException('A conexao ADOdb emprestada nao pode ser fechada pelo backend.');
    }

    public function wasCloseCalled(): bool
    {
        return $this->closeCalled;
    }
}
