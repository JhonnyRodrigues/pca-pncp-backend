<?php

declare(strict_types=1);

namespace Tests\Support;

final class AdodbSemIo
{
    public int $chamadas = 0;

    public function BeginTrans(): bool
    {
        $this->chamadas++;

        return true;
    }

    public function CommitTrans(): bool
    {
        $this->chamadas++;

        return true;
    }

    public function RollbackTrans(): bool
    {
        $this->chamadas++;

        return true;
    }

    /** @param mixed[] $parametros */
    public function Execute(string $sql, array $parametros = []): bool
    {
        $this->chamadas++;

        return true;
    }

    /** @param mixed[] $parametros @return array<int, array<string, mixed>> */
    public function GetAll(string $sql, array $parametros = []): array
    {
        $this->chamadas++;

        return [];
    }

    public function Param(string|false $nome): string
    {
        $this->chamadas++;

        return ':' . ($nome === false ? 'param' : $nome);
    }

    public function ErrorNo(): int
    {
        $this->chamadas++;

        return 0;
    }

    public function ErrorMsg(): string
    {
        $this->chamadas++;

        return '';
    }
}
