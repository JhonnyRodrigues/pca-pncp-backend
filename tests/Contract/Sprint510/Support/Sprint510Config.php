<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint510\Support;

use InvalidArgumentException;
use RuntimeException;

final class Sprint510Config
{
    private const MODES = ['metadata', 'individual', 'consolidado', 'ausente'];

    private const OPERATORS = ['eq', 'not_null', 'non_empty', 'unique'];

    /** @return array<string, mixed> */
    public static function loadFromEnvironment(): array
    {
        $configuredPath = getenv('PCA_SPRINT510_CONFIG_FILE');
        if (!is_string($configuredPath) || trim($configuredPath) === '') {
            throw new RuntimeException('PCA_SPRINT510_CONFIG_FILE nao foi informado.');
        }

        $path = realpath($configuredPath);
        if ($path === false || !is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Arquivo externo da Sprint 5.10 nao esta legivel.');
        }

        $repositoryRoot = realpath(dirname(__DIR__, 4));
        if ($repositoryRoot === false || self::isWithin($path, $repositoryRoot)) {
            throw new RuntimeException('A configuracao da Sprint 5.10 deve ficar fora do repositorio.');
        }

        $config = require $path;
        if (!is_array($config)) {
            throw new RuntimeException('O arquivo externo da Sprint 5.10 deve retornar um array.');
        }

        return $config;
    }

    /** @param array<string, mixed> $config @return array<string, mixed> */
    public static function oracle(array $config): array
    {
        $oracle = $config['oracle'] ?? null;
        if (!is_array($oracle)) {
            throw new InvalidArgumentException('Secao oracle nao foi configurada.');
        }
        if (($oracle['allow_reads'] ?? null) !== true) {
            throw new InvalidArgumentException('allow_reads=true e obrigatorio para a investigacao Oracle.');
        }
        if (array_key_exists('allow_writes', $oracle)) {
            throw new InvalidArgumentException('A Sprint 5.10 nao aceita autorizacao de escrita.');
        }
        foreach (['dedicated_mass', 'official_sgi_workflow', 'physical_source_confirmed'] as $field) {
            if (($oracle[$field] ?? null) !== true) {
                throw new InvalidArgumentException("{$field}=true e obrigatorio para a investigacao Oracle.");
            }
        }

        $modes = $oracle['modes'] ?? null;
        if (!is_array($modes) || array_keys($modes) !== self::MODES) {
            throw new InvalidArgumentException('Os quatro modos da Sprint 5.10 devem ser configurados na ordem aprovada.');
        }

        foreach (self::MODES as $mode) {
            $checks = $modes[$mode];
            if (!is_array($checks) || !array_is_list($checks) || $checks === []) {
                throw new InvalidArgumentException("O modo {$mode} deve possuir checks de leitura.");
            }
            foreach ($checks as $check) {
                self::validateCheck($check, $mode);
            }
        }

        return $oracle;
    }

    /** @return string[] */
    public static function modes(): array
    {
        return self::MODES;
    }

    /** @param array<string, mixed> $oracle @return string[] */
    public static function sensitiveValues(array $oracle): array
    {
        $values = [];
        foreach ($oracle['modes'] ?? [] as $checks) {
            if (!is_array($checks)) {
                continue;
            }
            foreach ($checks as $check) {
                if (!is_array($check)) {
                    continue;
                }
                foreach ($check['params'] ?? [] as $value) {
                    self::collectSensitiveString($values, $value);
                }
                foreach ($check['assertions'] ?? [] as $assertion) {
                    if (is_array($assertion) && array_key_exists('value', $assertion)) {
                        self::collectSensitiveString($values, $assertion['value']);
                    }
                }
            }
        }

        return array_values(array_unique($values));
    }

    private static function validateCheck(mixed $check, string $mode): void
    {
        if (!is_array($check)) {
            throw new InvalidArgumentException("Cada check do modo {$mode} deve ser um array.");
        }

        $label = $check['label'] ?? null;
        if (!is_string($label) || preg_match('/^[a-z][a-z0-9_]{2,63}$/D', $label) !== 1) {
            throw new InvalidArgumentException('Rotulo de check deve ser um identificador sem dados fisicos.');
        }

        $sql = $check['sql'] ?? null;
        if (!is_string($sql) || str_contains($sql, '...') || !self::isReadOnlySelect($sql)) {
            throw new InvalidArgumentException("O check {$label} aceita somente um SELECT read-only.");
        }

        $params = $check['params'] ?? null;
        if (!is_array($params) || !array_is_list($params) || substr_count($sql, '?') !== count($params)) {
            throw new InvalidArgumentException("Os binds do check {$label} sao invalidos.");
        }
        foreach ($params as $parameter) {
            if (!is_scalar($parameter) && $parameter !== null) {
                throw new InvalidArgumentException("Os binds do check {$label} devem ser escalares ou null.");
            }
        }

        $expectedRows = $check['expected_rows'] ?? null;
        if (!is_int($expectedRows) || $expectedRows < 0) {
            throw new InvalidArgumentException("expected_rows do check {$label} deve ser inteiro nao negativo.");
        }
        if (($mode === 'ausente' && $expectedRows !== 0)
            || ($mode !== 'ausente' && $expectedRows === 0)
        ) {
            throw new InvalidArgumentException("Cardinalidade esperada incompativel com o modo {$mode}.");
        }

        $aliases = $check['aliases'] ?? null;
        if (!is_array($aliases) || !array_is_list($aliases) || $aliases === []) {
            throw new InvalidArgumentException("Aliases do check {$label} nao foram configurados.");
        }
        foreach ($aliases as $alias) {
            if (!is_string($alias) || preg_match('/^[A-Z][A-Z0-9_]{0,63}$/D', $alias) !== 1) {
                throw new InvalidArgumentException("Alias invalido no check {$label}.");
            }
        }
        if (count(array_unique($aliases)) !== count($aliases)) {
            throw new InvalidArgumentException("Aliases duplicados no check {$label}.");
        }

        $assertions = $check['assertions'] ?? null;
        if (!is_array($assertions) || !array_is_list($assertions)) {
            throw new InvalidArgumentException("Assertions do check {$label} devem ser uma lista.");
        }
        if ($expectedRows > 0 && $assertions === []) {
            throw new InvalidArgumentException("O check {$label} deve verificar os dados encontrados.");
        }
        foreach ($assertions as $assertion) {
            self::validateAssertion($assertion, $aliases, $label);
        }
    }

    /** @param string[] $aliases */
    private static function validateAssertion(mixed $assertion, array $aliases, string $label): void
    {
        if (!is_array($assertion)) {
            throw new InvalidArgumentException("Assertion invalida no check {$label}.");
        }
        $alias = $assertion['alias'] ?? null;
        $operator = $assertion['operator'] ?? null;
        if (!is_string($alias) || !in_array($alias, $aliases, true)) {
            throw new InvalidArgumentException("Assertion referencia alias nao declarado no check {$label}.");
        }
        if (!is_string($operator) || !in_array($operator, self::OPERATORS, true)) {
            throw new InvalidArgumentException("Operador invalido no check {$label}.");
        }
        if ($operator === 'eq') {
            if (!array_key_exists('value', $assertion)
                || (!is_scalar($assertion['value']) && $assertion['value'] !== null)
            ) {
                throw new InvalidArgumentException("Operador eq exige valor escalar ou null no check {$label}.");
            }
        } elseif (array_key_exists('value', $assertion)) {
            throw new InvalidArgumentException("O operador {$operator} nao aceita value no check {$label}.");
        }
    }

    public static function isReadOnlySelect(string $sql): bool
    {
        $trimmed = trim($sql);
        if (preg_match('/\ASELECT\b/is', $trimmed) !== 1 || str_contains($trimmed, ';')) {
            return false;
        }
        if (preg_match('~(?:--|/\*|\*/)|\b(?:INSERT|UPDATE|DELETE|MERGE|ALTER|CREATE|DROP|TRUNCATE|'
            . 'BEGIN|DECLARE|EXECUTE|COMMIT|ROLLBACK|GRANT|REVOKE)\b|\bFOR\s+UPDATE\b~i', $trimmed) === 1
        ) {
            return false;
        }

        return true;
    }

    /** @param string[] $values */
    private static function collectSensitiveString(array &$values, mixed $value): void
    {
        if (is_string($value) && $value !== '') {
            $values[] = $value;
        }
    }

    private static function isWithin(string $path, string $directory): bool
    {
        $path = str_replace('\\', '/', strtolower($path));
        $directory = rtrim(str_replace('\\', '/', strtolower($directory)), '/');

        return $path === $directory || str_starts_with($path, $directory . '/');
    }
}
