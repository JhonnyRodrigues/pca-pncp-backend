<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84\Support;

use LogicException;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConversorCharsetAdodbOracle;
use Throwable;

final class Sprint84AdodbGuard
{
    /** @var array<int, array<string, mixed>> */
    private array $calls = [];

    private readonly ConversorCharsetAdodbOracle $conversorCharset;

    public function __construct(
        private readonly object $inner,
        string $charsetAdodb = 'UTF-8'
    ) {
        $this->conversorCharset = new ConversorCharsetAdodbOracle($charsetAdodb);
    }

    public function BeginTrans(): mixed
    {
        return $this->simpleCall('BeginTrans');
    }

    public function CommitTrans(): mixed
    {
        return $this->simpleCall('CommitTrans');
    }

    public function RollbackTrans(): mixed
    {
        return $this->simpleCall('RollbackTrans');
    }

    /** @param array<int|string, mixed> $binds */
    public function Execute(string $sql, array $binds = []): mixed
    {
        try {
            $result = $this->inner->Execute($sql, $binds);
            $this->calls[] = [
                'metodo' => 'Execute',
                'sql' => $sql,
                'binds' => $this->normalizarDiagnostico($binds),
                'retorno' => $result,
            ];

            return $result;
        } catch (Throwable $failure) {
            $this->recordFailure('Execute', $failure, ['sql' => $sql, 'binds' => $binds]);
            throw $failure;
        }
    }

    /** @param array<int|string, mixed> $binds */
    public function GetAll(string $sql, array $binds = []): mixed
    {
        try {
            $rows = $this->inner->GetAll($sql, $binds);
            $this->calls[] = [
                'metodo' => 'GetAll',
                'sql' => $sql,
                'binds' => $this->normalizarDiagnostico($binds),
                'linhas' => $this->normalizarDiagnostico($rows),
                'quantidade_linhas' => is_array($rows) ? count($rows) : null,
            ];

            return $rows;
        } catch (Throwable $failure) {
            $this->recordFailure('GetAll', $failure, ['sql' => $sql, 'binds' => $binds]);
            throw $failure;
        }
    }

    public function Param(string|false $name): mixed
    {
        try {
            $result = $this->inner->Param($name);
            $this->calls[] = ['metodo' => 'Param', 'nome' => $name, 'retorno' => $result];

            return $result;
        } catch (Throwable $failure) {
            $this->recordFailure('Param', $failure, ['nome' => $name]);
            throw $failure;
        }
    }

    public function ErrorNo(): mixed
    {
        return $this->simpleCall('ErrorNo');
    }

    public function ErrorMsg(): mixed
    {
        return $this->simpleCall('ErrorMsg');
    }

    public function Close(): never
    {
        $this->calls[] = ['metodo' => 'Close', 'bloqueado' => true];

        throw new LogicException(
            'A conexão ADOdb emprestada não pode ser fechada pelo gate da Sprint 8.4.'
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function diagnostico(): array
    {
        return $this->calls;
    }

    private function simpleCall(string $method): mixed
    {
        try {
            $result = $this->inner->{$method}();
            $this->calls[] = ['metodo' => $method, 'retorno' => $result];

            return $result;
        } catch (Throwable $failure) {
            $this->recordFailure($method, $failure);
            throw $failure;
        }
    }

    private function normalizarDiagnostico(mixed $value): mixed
    {
        try {
            return $this->conversorCharset->converterLeitura($value);
        } catch (Throwable) {
            return $value;
        }
    }

    /** @param array<string, mixed> $context */
    private function recordFailure(string $method, Throwable $failure, array $context = []): void
    {
        $this->calls[] = array_merge(['metodo' => $method], $context, [
            'erro' => [
                'classe' => $failure::class,
                'mensagem' => $failure->getMessage(),
                'arquivo' => $failure->getFile(),
                'linha' => $failure->getLine(),
                'stack' => $failure->getTraceAsString(),
            ],
        ]);
    }
}
