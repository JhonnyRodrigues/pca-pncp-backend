<?php

declare(strict_types=1);

namespace Tests\Contract\Support;

use LogicException;

class BorrowedAdodbGuard
{
    private bool $closeCalled = false;

    /** @var string[] */
    private array $calls = [];

    public function __construct(private readonly object $inner)
    {
    }

    public function BeginTrans(): mixed
    {
        $this->calls[] = 'BeginTrans';

        return $this->inner->BeginTrans();
    }

    public function CommitTrans(): mixed
    {
        $this->calls[] = 'CommitTrans';

        return $this->inner->CommitTrans();
    }

    public function RollbackTrans(): mixed
    {
        $this->calls[] = 'RollbackTrans';

        return $this->inner->RollbackTrans();
    }

    /** @param array<string, mixed> $parameters */
    public function Execute(string $sql, array $parameters = []): mixed
    {
        $this->calls[] = 'Execute';

        return $this->inner->Execute($sql, $parameters);
    }

    /** @param array<string, mixed> $parameters */
    public function GetAll(string $sql, array $parameters = []): mixed
    {
        $this->calls[] = 'GetAll';

        return $this->inner->GetAll($sql, $parameters);
    }

    public function Param(string|false $name): mixed
    {
        $this->calls[] = 'Param';

        return $this->inner->Param($name);
    }

    public function ErrorNo(): mixed
    {
        $this->calls[] = 'ErrorNo';

        return $this->inner->ErrorNo();
    }

    public function ErrorMsg(): mixed
    {
        $this->calls[] = 'ErrorMsg';

        return $this->inner->ErrorMsg();
    }

    public function Close(): never
    {
        $this->calls[] = 'Close';
        $this->closeCalled = true;

        throw new LogicException('A conexao ADOdb emprestada nao pode ser fechada pelo backend.');
    }

    public function wasCloseCalled(): bool
    {
        return $this->closeCalled;
    }

    public function countCalls(string $method): int
    {
        return count(array_filter(
            $this->calls,
            static fn (string $call): bool => $call === $method
        ));
    }

    public function countRecordedCalls(): int
    {
        return count($this->calls);
    }

    /** @param string[] $expected */
    public function hasCallsInOrderAfter(array $expected, int $offset): bool
    {
        $position = 0;

        foreach (array_slice($this->calls, $offset) as $call) {
            if (($expected[$position] ?? null) !== $call) {
                continue;
            }

            $position++;
            if ($position === count($expected)) {
                return true;
            }
        }

        return $expected === [];
    }
}
