<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint51\Support;

use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

final class Sprint51Config
{
    private const PNCP_TRAINING_BASE_URL = 'https://treina.pncp.gov.br/api/pncp';

    /** @return array<string, mixed> */
    public static function loadFromEnvironment(): array
    {
        $configuredPath = getenv('PCA_SPRINT51_CONFIG_FILE');

        if (!is_string($configuredPath) || trim($configuredPath) === '') {
            throw new RuntimeException('PCA_SPRINT51_CONFIG_FILE não foi informado.');
        }

        $path = realpath($configuredPath);
        if ($path === false || !is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Arquivo externo da Sprint 5.1 não está legível.');
        }

        $repositoryRoot = realpath(dirname(__DIR__, 4));
        if ($repositoryRoot !== false && str_starts_with($path, $repositoryRoot . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('O arquivo com segredos deve ficar fora do repositório.');
        }

        $config = require $path;
        if (!is_array($config)) {
            throw new RuntimeException('Configuração externa da Sprint 5.1 deve retornar array.');
        }

        return $config;
    }

    /** @param array<string, mixed> $config @return array<string, mixed> */
    public static function pncp(array $config): array
    {
        $pncp = self::section($config, 'pncp');

        if (($pncp['base_url'] ?? null) !== self::PNCP_TRAINING_BASE_URL) {
            throw new InvalidArgumentException('O gate PNCP só aceita a URL canônica de treinamento.');
        }

        $pncp['ca_bundle'] = self::optionalReadableAbsoluteFile($pncp, 'ca_bundle');

        foreach (['login', 'senha', 'codigo_unidade', 'marcador_massa'] as $field) {
            self::nonEmptyString($pncp, $field);
        }

        $cnpj = self::nonEmptyString($pncp, 'cnpj');
        if (!preg_match('/^[0-9]{14}$/D', $cnpj)) {
            throw new InvalidArgumentException('O CNPJ operacional disponível deve conter 14 dígitos.');
        }

        $year = self::positiveInt($pncp, 'ano');
        if ($year < 2000 || $year > 9999) {
            throw new InvalidArgumentException('Ano do gate PNCP fora da faixa aceita.');
        }

        $date = self::nonEmptyString($pncp, 'data_desejada');
        $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if ($parsedDate === false || $parsedDate->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('data_desejada deve usar uma data válida YYYY-MM-DD.');
        }

        $baseItem = self::positiveInt($pncp, 'numero_item_base');
        if ($baseItem > 2147482547) {
            throw new InvalidArgumentException('numero_item_base não deixa margem segura para o int32 do PNCP.');
        }

        foreach (['connect_timeout_seconds', 'read_timeout_seconds', 'write_timeout_seconds', 'max_response_bytes', 'page_size'] as $field) {
            self::positiveInt($pncp, $field);
        }

        if (($pncp['page_size'] ?? 0) > 1000) {
            throw new InvalidArgumentException('page_size do probe não pode exceder 1000.');
        }

        if (($pncp['allow_writes'] ?? null) !== true) {
            throw new InvalidArgumentException('allow_writes=true é obrigatório para a suíte PNCP completa.');
        }

        $allowLargeBatch = $pncp['allow_large_batch'] ?? false;
        if (!is_bool($allowLargeBatch)) {
            throw new InvalidArgumentException('allow_large_batch deve ser booleano quando informado.');
        }
        $pncp['allow_large_batch'] = $allowLargeBatch;

        $marker = (string) $pncp['marcador_massa'];
        if (!str_starts_with($marker, 'PCA-S51-') || strlen($marker) > 40) {
            throw new InvalidArgumentException('marcador_massa deve iniciar com PCA-S51- e ter até 40 bytes.');
        }

        $ownedResidue = $pncp['owned_residue_sequence'] ?? null;
        if ($ownedResidue !== null && (!is_int($ownedResidue) || $ownedResidue < 1)) {
            throw new InvalidArgumentException('owned_residue_sequence deve ser inteiro positivo ou null.');
        }

        $template = $pncp['item_template'] ?? null;
        if (!is_array($template)) {
            throw new InvalidArgumentException('item_template é obrigatório.');
        }

        foreach (['categoriaItemPca', 'catalogo'] as $field) {
            self::positiveInt($template, $field);
        }

        foreach (['unidadeFornecimento', 'classificacaoCatalogo', 'classificacaoSuperiorCodigo', 'classificacaoSuperiorNome'] as $field) {
            self::nonEmptyString($template, $field);
        }
        if (!in_array($template['classificacaoCatalogo'], ['1', '2'], true)) {
            throw new InvalidArgumentException('classificacaoCatalogo deve usar a enumeração textual 1 ou 2 do OpenAPI.');
        }

        return $pncp;
    }

    /** @param array<string, mixed> $config @return array<string, mixed> */
    public static function oracle(array $config): array
    {
        $oracle = self::section($config, 'oracle');
        self::identifier(self::nonEmptyString($oracle, 'schema_owner'));

        $tables = $oracle['tables'] ?? null;
        if (!is_array($tables) || $tables === []) {
            throw new InvalidArgumentException('A allowlist Oracle deve possuir ao menos uma tabela.');
        }
        foreach ($tables as $table) {
            self::identifierValue($table);
        }

        foreach (['fixture', 'lock'] as $section) {
            $value = $oracle[$section] ?? null;
            if (!is_array($value)) {
                throw new InvalidArgumentException("Seção Oracle {$section} é obrigatória.");
            }
            foreach (['table', 'key_column'] as $field) {
                self::identifier(self::nonEmptyString($value, $field));
            }
            if (!array_key_exists('key_value', $value) || !is_scalar($value['key_value'])) {
                throw new InvalidArgumentException("{$section}.key_value deve ser escalar.");
            }
        }

        $fixture = $oracle['fixture'];
        self::identifier(self::nonEmptyString($fixture, 'value_column'));
        self::nonEmptyString($fixture, 'probe_value');

        $lock = $oracle['lock'];
        $holdSeconds = self::positiveInt($lock, 'hold_seconds');
        if ($holdSeconds > 60) {
            throw new InvalidArgumentException('lock.hold_seconds não pode exceder 60 segundos.');
        }

        $checks = $oracle['aggregate_checks'] ?? [];
        if (!is_array($checks) || $checks === []) {
            throw new InvalidArgumentException('aggregate_checks deve possuir as validações Oracle obrigatórias.');
        }

        foreach ($checks as $check) {
            self::validateAggregateCheck($check);
        }

        return $oracle;
    }

    public static function trainingBaseUrl(): string
    {
        return self::PNCP_TRAINING_BASE_URL;
    }

    /** @param array<string, mixed> $config @return array<string, mixed> */
    private static function section(array $config, string $name): array
    {
        $section = $config[$name] ?? null;
        if (!is_array($section)) {
            throw new InvalidArgumentException("Seção {$name} não foi configurada.");
        }

        return $section;
    }

    /** @param array<string, mixed> $values */
    private static function nonEmptyString(array $values, string $field): string
    {
        $value = $values[$field] ?? null;
        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("{$field} deve ser texto não vazio.");
        }

        return trim($value);
    }

    /** @param array<string, mixed> $values */
    private static function positiveInt(array $values, string $field): int
    {
        $value = $values[$field] ?? null;
        if (!is_int($value) || $value < 1) {
            throw new InvalidArgumentException("{$field} deve ser inteiro positivo.");
        }

        return $value;
    }

    /** @param array<string, mixed> $values */
    private static function optionalReadableAbsoluteFile(array $values, string $field): ?string
    {
        if (!array_key_exists($field, $values) || $values[$field] === null) {
            return null;
        }

        $value = $values[$field];
        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("{$field} deve ser string não vazia ou null.");
        }

        $path = trim($value);
        $isAbsolute = str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:[\\\\\/]/D', $path) === 1
            || preg_match('/^[\\\\\/]{2}[^\\\\\/]/D', $path) === 1;
        if (!$isAbsolute) {
            throw new InvalidArgumentException("{$field} deve usar caminho absoluto.");
        }

        $resolved = realpath($path);
        if ($resolved === false || !is_file($resolved) || !is_readable($resolved)) {
            throw new InvalidArgumentException("{$field} deve apontar para arquivo existente e legível.");
        }

        return $resolved;
    }

    private static function identifierValue(mixed $value): void
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Identificador Oracle deve ser textual.');
        }
        self::identifier($value);
    }

    private static function identifier(string $value): void
    {
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_$#]{0,127}$/D', $value)) {
            throw new InvalidArgumentException('Identificador Oracle fora da forma permitida.');
        }
    }

    private static function validateAggregateCheck(mixed $check): void
    {
        if (!is_array($check)) {
            throw new InvalidArgumentException('Cada aggregate_check deve ser um array.');
        }

        $label = self::nonEmptyString($check, 'label');
        if (str_starts_with($label, 'SUBSTITUIR_')) {
            throw new InvalidArgumentException('aggregate_check de exemplo deve ser substituído por prova real.');
        }
        $sql = self::nonEmptyString($check, 'sql');
        if (!preg_match('/^\s*SELECT\b/i', $sql) || str_contains($sql, ';')) {
            throw new InvalidArgumentException('aggregate_check aceita somente um SELECT sem separador.');
        }
        if (preg_match('/\b(?:INSERT|UPDATE|DELETE|MERGE|ALTER|DROP|TRUNCATE|EXECUTE)\b/i', $sql)) {
            throw new InvalidArgumentException('aggregate_check contém operação não permitida.');
        }

        if (!is_array($check['params'] ?? null)) {
            throw new InvalidArgumentException('aggregate_check.params deve ser array.');
        }

        $expect = $check['expect'] ?? null;
        $allowedOperators = ['eq', 'neq', 'lt', 'lte', 'gt', 'gte', 'not_null'];
        if (!is_array($expect) || !in_array($expect['operator'] ?? null, $allowedOperators, true)) {
            throw new InvalidArgumentException('Operador de expectativa agregado inválido.');
        }
        if (($expect['operator'] ?? null) !== 'not_null' && !array_key_exists('value', $expect)) {
            throw new InvalidArgumentException('Expectativa agregada deve fornecer value.');
        }
    }
}
