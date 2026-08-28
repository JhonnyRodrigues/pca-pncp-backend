<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint510\Support;

use RuntimeException;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Oracle\Adodb\FalhaOracleException;
use Throwable;

final class OracleCancellationEvidenceInvestigationRunner
{
    private readonly BorrowedAdodbReadOnlyGuard $guard;
    private readonly ConexaoAdodbEmprestada $connection;
    private string $stage = 'preparacao';

    /** @param array<string, mixed> $oracle */
    public function __construct(object $db, private readonly array $oracle)
    {
        Sprint510Config::oracle(['oracle' => $oracle]);
        $this->guard = new BorrowedAdodbReadOnlyGuard($db);
        $this->connection = new ConexaoAdodbEmprestada($this->guard);
    }

    /** @param array<string, mixed> $config */
    public static function fromConfig(object $db, array $config): self
    {
        return new self($db, Sprint510Config::oracle($config));
    }

    /** @return array<string, mixed> */
    public function run(string $mode): array
    {
        $base = [
            'sprint' => '5.10',
            'gate' => 'ORACLE_INVESTIGACAO_EVIDENCIA_CANCELAMENTO',
            'modo' => in_array($mode, Sprint510Config::modes(), true) ? $mode : 'INVALIDO',
            'executado_em_utc' => gmdate('c'),
            'runtime_php' => PHP_VERSION,
            'conexao_emprestada' => true,
            'massa_dedicada' => true,
            'fluxo_sgi_oficial' => true,
        ];

        try {
            if (!in_array($mode, Sprint510Config::modes(), true)) {
                throw new RuntimeException('Modo investigativo desconhecido.');
            }

            $this->stage = 'sessao_inicial';
            $sessionBefore = $this->sessionFingerprint();
            $results = [];
            foreach ($this->oracle['modes'][$mode] as $index => $check) {
                $safeLabel = 'check_' . ((int) $index + 1);
                $this->stage = $safeLabel;
                $results[] = $this->executeCheck($check, $safeLabel);
            }
            $this->stage = 'sessao_final';
            $sameSession = $sessionBefore === $this->sessionFingerprint();
            $readOnly = !$this->guard->wasWriteAttempted()
                && !$this->guard->wasTransactionAttempted();
            $confirmedChecks = count(array_filter(
                $results,
                static fn (array $result): bool => $result['confirmado'] === true
            ));

            return $base + [
                'confirmado' => $sameSession && $readOnly && $confirmedChecks === count($results),
                'somente_leitura' => $readOnly,
                'mesma_sessao' => $sameSession,
                'close_invocado' => $this->guard->wasCloseCalled(),
                'resultado' => [
                    'quantidade_checks' => count($results),
                    'checks' => $results,
                ],
            ];
        } catch (Throwable $failure) {
            $oracleCode = $failure instanceof FalhaOracleException
                ? $failure->getCodigoOracle()
                : null;

            return $base + [
                'confirmado' => false,
                'somente_leitura' => !$this->guard->wasWriteAttempted()
                    && !$this->guard->wasTransactionAttempted(),
                'close_invocado' => $this->guard->wasCloseCalled(),
                'falha' => [
                    'codigo' => 'INVESTIGACAO_ORACLE_SPRINT_510_FALHOU',
                    'etapa' => $this->stage,
                    'codigo_oracle' => $oracleCode,
                    'mensagem' => 'A investigacao Oracle foi recusada sem exposicao de detalhes internos.',
                ],
            ];
        }
    }

    /**
     * @param array<string, mixed> $check
     * @return array<string, mixed>
     */
    private function executeCheck(array $check, string $safeLabel): array
    {
        $rows = $this->connection->buscarTodos($check['sql'], $check['params']);
        $normalizedRows = array_map(
            fn (array $row): array => $this->normalizeRow($row, $check['aliases']),
            $rows
        );
        $rowCountConfirmed = count($normalizedRows) === $check['expected_rows'];
        $verifications = [];
        foreach ($check['assertions'] as $assertion) {
            $values = array_column($normalizedRows, $assertion['alias']);
            $verifications[] = [
                'operador' => $assertion['operator'],
                'confirmada' => $this->assertionConfirmed($values, $assertion),
            ];
        }
        $assertionsConfirmed = count(array_filter(
            $verifications,
            static fn (array $verification): bool => $verification['confirmada'] === true
        )) === count($verifications);

        return [
            'rotulo' => $safeLabel,
            'confirmado' => $rowCountConfirmed && $assertionsConfirmed,
            'quantidade_linhas' => count($normalizedRows),
            'cardinalidade_confirmada' => $rowCountConfirmed,
            'quantidade_aliases_esperados' => count($check['aliases']),
            'aliases_confirmados' => true,
            'verificacoes' => $verifications,
        ];
    }

    /**
     * @param array<int|string, mixed> $row
     * @param string[] $expectedAliases
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row, array $expectedAliases): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            $alias = strtoupper($key);
            if (array_key_exists($alias, $normalized)) {
                throw new RuntimeException('Consulta retornou alias associativo duplicado.');
            }
            $normalized[$alias] = $value;
        }

        $actualAliases = array_keys($normalized);
        sort($actualAliases);
        $expected = $expectedAliases;
        sort($expected);
        if ($actualAliases !== $expected) {
            throw new RuntimeException('Consulta nao retornou exatamente os aliases esperados.');
        }

        return $normalized;
    }

    /**
     * @param mixed[] $values
     * @param array<string, mixed> $assertion
     */
    private function assertionConfirmed(array $values, array $assertion): bool
    {
        if ($values === []) {
            return false;
        }

        return match ($assertion['operator']) {
            'eq' => count(array_filter(
                $values,
                fn (mixed $value): bool => $this->sameScalar($value, $assertion['value'])
            )) === count($values),
            'not_null' => count(array_filter(
                $values,
                static fn (mixed $value): bool => $value !== null
            )) === count($values),
            'non_empty' => count(array_filter(
                $values,
                static fn (mixed $value): bool => is_string($value) && trim($value) !== ''
            )) === count($values),
            'unique' => !in_array(null, $values, true)
                && count(array_unique(array_map($this->scalarKey(...), $values))) === count($values),
            default => false,
        };
    }

    private function sessionFingerprint(): string
    {
        $rows = $this->connection->buscarTodos(
            "SELECT SYS_CONTEXT('USERENV','INSTANCE') || ':' || "
                . "SYS_CONTEXT('USERENV','SID') AS SESSAO FROM DUAL"
        );
        if (count($rows) !== 1 || !is_array($rows[0])) {
            throw new RuntimeException('Consulta de sessao nao retornou linha unica.');
        }

        $normalized = array_change_key_case($rows[0], CASE_UPPER);
        $value = $normalized['SESSAO'] ?? null;
        if (!is_scalar($value) || (string) $value === '') {
            throw new RuntimeException('Consulta de sessao nao retornou escalar valido.');
        }

        return (string) $value;
    }

    private function sameScalar(mixed $left, mixed $right): bool
    {
        if ($left === null || $right === null) {
            return $left === $right;
        }

        return is_scalar($left) && is_scalar($right) && (string) $left === (string) $right;
    }

    private function scalarKey(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }
        if (!is_scalar($value)) {
            return 'nao_escalar';
        }

        return get_debug_type($value) . ':' . (string) $value;
    }
}
