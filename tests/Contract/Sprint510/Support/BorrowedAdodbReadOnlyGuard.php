<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint510\Support;

use LogicException;

final class BorrowedAdodbReadOnlyGuard
{
    private bool $writeAttempted = false;
    private bool $transactionAttempted = false;
    private bool $closeCalled = false;

    public function __construct(private readonly object $inner)
    {
    }

    public function BeginTrans(): never
    {
        $this->transactionAttempted = true;
        throw new LogicException('A investigacao read-only nao pode iniciar transacao.');
    }

    public function CommitTrans(): never
    {
        $this->transactionAttempted = true;
        throw new LogicException('A investigacao read-only nao pode confirmar transacao.');
    }

    public function RollbackTrans(): never
    {
        $this->transactionAttempted = true;
        throw new LogicException('A investigacao read-only nao pode desfazer transacao inexistente.');
    }

    /** @param array<int|string, mixed> $parameters */
    public function Execute(string $sql, array $parameters = []): never
    {
        $this->writeAttempted = true;
        throw new LogicException('A investigacao read-only nao pode executar comandos.');
    }

    /** @param array<int|string, mixed> $parameters */
    public function GetAll(string $sql, array $parameters = []): mixed
    {
        if (!Sprint510Config::isReadOnlySelect($sql)) {
            $this->writeAttempted = true;
            throw new LogicException('A investigacao recusou consulta que nao e SELECT read-only.');
        }

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
        throw new LogicException('A conexao ADOdb emprestada nao pode ser fechada pela investigacao.');
    }

    public function wasWriteAttempted(): bool
    {
        return $this->writeAttempted;
    }

    public function wasTransactionAttempted(): bool
    {
        return $this->transactionAttempted;
    }

    public function wasCloseCalled(): bool
    {
        return $this->closeCalled;
    }
}
