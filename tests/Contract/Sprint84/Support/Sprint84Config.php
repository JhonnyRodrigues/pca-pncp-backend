<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84\Support;

use Semae\Pca\Composition\CarregadorPerfilPublicacaoPca;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Throwable;

final class Sprint84Config
{
    public const ENVIRONMENT_VARIABLE = 'PCA_SPRINT84_CONFIG_FILE';

    private const MODES = ['INDIVIDUAL', 'CONSOLIDADO'];
    private const STAGES = ['PRECHECK', 'PUBLICAR', 'NENHUMA_ACAO', 'RETIFICAR', 'EXCLUIR_PCA'];
    private const PNCP_STATES = ['AUSENTE', 'ENCONTRADO'];

    /** @return array<string, mixed> */
    public static function loadFromEnvironment(): array
    {
        $locator = getenv(self::ENVIRONMENT_VARIABLE);

        if (!is_string($locator) || trim($locator) === '') {
            throw self::error(
                'CONFIG_LOCALIZADOR_AUSENTE',
                self::ENVIRONMENT_VARIABLE,
                'caminho absoluto não vazio',
                $locator
            );
        }

        $path = self::resolveExternalFile($locator);

        try {
            $configuration = require $path;
        } catch (Throwable $failure) {
            throw self::error(
                'CONFIG_CARREGAMENTO_FALHOU',
                self::ENVIRONMENT_VARIABLE,
                'arquivo PHP legível que retorne array',
                $path,
                $failure
            );
        }

        if (!is_array($configuration)) {
            throw self::error(
                'CONFIG_RETORNO_INVALIDO',
                '$',
                'array',
                $configuration
            );
        }

        return self::validate($configuration);
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, mixed>
     */
    public static function validate(array $configuration): array
    {
        self::requireExactKeys($configuration, [
            'allow_oracle_writes',
            'allow_pncp_writes',
            'server_name',
            'modo',
            'ano',
            'etapa_autorizada',
            'unidades',
            'oracle_checkpoint_sha256',
            'estado_pncp_esperado',
            'sequenciais_limpeza_autorizados',
            'baseline_auditoria',
        ], '$');

        $stage = self::requireEnum($configuration, 'etapa_autorizada', self::STAGES);
        self::requireConsent($configuration, 'allow_oracle_writes', $stage !== 'PRECHECK');
        self::requireConsent($configuration, 'allow_pncp_writes', !in_array($stage, ['PRECHECK', 'NENHUMA_ACAO'], true));
        self::requireNonEmptyString($configuration, 'server_name');
        $mode = self::requireEnum($configuration, 'modo', self::MODES);
        $year = self::requirePositiveInteger($configuration, 'ano');

        try {
            new AnoExecucao($year);
        } catch (Throwable $failure) {
            throw self::error(
                'CONFIG_ANO_INVALIDO',
                'ano',
                'ano de execução aceito pelo domínio',
                $year,
                $failure
            );
        }

        self::validateHash($configuration, $stage);
        self::validateExpectedPncpState($configuration, $stage);
        self::validateUnits($configuration, $stage, $mode);
        self::validateCleanupSequences($configuration);
        self::requireNonNegativeInteger($configuration, 'baseline_auditoria');

        return $configuration;
    }

    /** @param array<string, mixed> $configuration */
    public static function loadHomologationProfile(
        array $configuration,
        string $serverName
    ): PerfilPublicacaoPca {
        return new PerfilPublicacaoPca(
            self::loadHomologationProfileArray($configuration, $serverName)
        );
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, mixed>
     */
    public static function loadHomologationProfileArray(
        array $configuration,
        string $serverName
    ): array {
        $configuration = self::validate($configuration);

        if ($serverName !== $configuration['server_name']) {
            throw self::error(
                'SERVER_NAME_DIVERGENTE',
                'server_name',
                (string) $configuration['server_name'],
                $serverName
            );
        }

        try {
            $rawProfile = (new CarregadorPerfilPublicacaoPca())->carregar($serverName);
            $profile = new PerfilPublicacaoPca($rawProfile);
        } catch (Throwable $failure) {
            throw self::error(
                'PERFIL_INVALIDO',
                'perfil',
                'perfil íntegro selecionado pelo registro autorizado',
                get_debug_type($failure),
                $failure
            );
        }

        if ($profile->getAmbiente() !== AmbientePncp::HOMOLOGACAO) {
            throw self::error(
                'PERFIL_PRODUCAO_PROIBIDO',
                'perfil.ambiente',
                AmbientePncp::HOMOLOGACAO->value,
                $profile->getAmbiente()->value
            );
        }

        $cnpj = $rawProfile['cnpj'] ?? null;
        try {
            if (!is_string($cnpj)) {
                throw self::error('PERFIL_CNPJ_INVALIDO', 'perfil.cnpj', 'texto válido', $cnpj);
            }
            new Cnpj($cnpj);
        } catch (Sprint84ConfigurationException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw self::error(
                'PERFIL_CNPJ_INVALIDO',
                'perfil.cnpj',
                'CNPJ válido',
                $cnpj,
                $failure
            );
        }

        return $rawProfile;
    }

    /** @param array<string, mixed> $configuration */
    private static function validateHash(array $configuration, string $stage): void
    {
        $hash = $configuration['oracle_checkpoint_sha256'] ?? null;

        if ($stage === 'PRECHECK') {
            if ($hash !== null && (!is_string($hash) || preg_match('/^[a-f0-9]{64}$/D', $hash) !== 1)) {
                throw self::error(
                    'CONFIG_HASH_INVALIDO',
                    'oracle_checkpoint_sha256',
                    'null ou SHA-256 hexadecimal minúsculo',
                    $hash
                );
            }
            return;
        }

        if (!is_string($hash) || preg_match('/^[a-f0-9]{64}$/D', $hash) !== 1) {
            throw self::error(
                'CONFIG_HASH_OBRIGATORIO',
                'oracle_checkpoint_sha256',
                'SHA-256 hexadecimal minúsculo para etapa real',
                $hash
            );
        }
    }

    /** @param array<string, mixed> $configuration */
    private static function validateExpectedPncpState(array $configuration, string $stage): void
    {
        $state = self::requireEnum(
            $configuration,
            'estado_pncp_esperado',
            self::PNCP_STATES
        );
        if ($stage === 'PRECHECK') {
            return;
        }

        $expected = $stage === 'PUBLICAR'
            ? 'AUSENTE'
            : 'ENCONTRADO';

        if ($state !== $expected) {
            throw self::error(
                'CONFIG_ESTADO_PNCP_INCOMPATIVEL',
                'estado_pncp_esperado',
                $expected . ' para a etapa ' . $stage,
                $state
            );
        }
    }

    /** @param array<string, mixed> $configuration */
    private static function validateUnits(array $configuration, string $stage, string $mode): void
    {
        $units = $configuration['unidades'] ?? null;
        if (!is_array($units) || !array_is_list($units) || count($units) !== 2) {
            throw self::error(
                'CONFIG_UNIDADES_INVALIDAS',
                'unidades',
                'lista com exatamente duas UOs',
                $units
            );
        }

        $identifiers = [];
        $totalInclusions = 0;
        $totalExclusions = 0;

        foreach ($units as $index => $unit) {
            $path = 'unidades.' . $index;
            if (!is_array($unit)) {
                throw self::error('CONFIG_UNIDADE_INVALIDA', $path, 'array', $unit);
            }
            self::requireExactKeys($unit, [
                'id_unidade_orcamentaria',
                'quantidade_versoes',
                'quantidade_fotografia',
                'quantidade_demandas',
                'inclusoes_retificacao',
                'exclusoes_retificacao',
            ], $path);

            $identifier = self::requirePositiveInteger(
                $unit,
                'id_unidade_orcamentaria',
                $path
            );
            $versions = self::requirePositiveInteger($unit, 'quantidade_versoes', $path);
            $photograph = self::requireNonNegativeInteger(
                $unit,
                'quantidade_fotografia',
                $path
            );
            $demands = self::requireNonNegativeInteger($unit, 'quantidade_demandas', $path);
            $inclusions = self::requireNonNegativeInteger(
                $unit,
                'inclusoes_retificacao',
                $path
            );
            $exclusions = self::requireNonNegativeInteger(
                $unit,
                'exclusoes_retificacao',
                $path
            );

            if ($versions < 1 || $demands < $photograph) {
                throw self::error(
                    'CONFIG_CARDINALIDADE_INVALIDA',
                    $path . '.quantidade_demandas',
                    'quantidade maior ou igual à fotografia',
                    $demands
                );
            }
            if ($stage === 'EXCLUIR_PCA' && $photograph !== 0) {
                throw self::error(
                    'CONFIG_FOTOGRAFIA_NAO_VAZIA',
                    $path . '.quantidade_fotografia',
                    'zero para EXCLUIR_PCA',
                    $photograph
                );
            }
            if (in_array($stage, ['PUBLICAR', 'NENHUMA_ACAO'], true) && $photograph < 1) {
                throw self::error(
                    'CONFIG_FOTOGRAFIA_VAZIA',
                    $path . '.quantidade_fotografia',
                    'inteiro positivo para ' . $stage,
                    $photograph
                );
            }
            if ($stage !== 'RETIFICAR' && ($inclusions !== 0 || $exclusions !== 0)) {
                throw self::error(
                    'CONFIG_DIFERENCAS_INESPERADAS',
                    $path,
                    'inclusões e exclusões iguais a zero fora da retificação',
                    ['inclusoes' => $inclusions, 'exclusoes' => $exclusions]
                );
            }

            $identifiers[] = $identifier;
            $totalInclusions += $inclusions;
            $totalExclusions += $exclusions;
        }

        if (count(array_unique($identifiers, SORT_NUMERIC)) !== 2) {
            throw self::error(
                'CONFIG_UNIDADES_DUPLICADAS',
                'unidades',
                'duas UOs distintas',
                $identifiers
            );
        }

        if ($stage === 'RETIFICAR') {
            $valid = $mode === 'INDIVIDUAL'
                ? self::eachUnitHasDifferences($units)
                : $totalInclusions > 0 && $totalExclusions > 0;

            if (!$valid) {
                throw self::error(
                    'CONFIG_RETIFICACAO_SEM_DIFERENCAS',
                    'unidades',
                    $mode === 'INDIVIDUAL'
                        ? 'ao menos uma inclusão e uma exclusão por UO'
                        : 'ao menos uma inclusão e uma exclusão na união consolidada',
                    ['inclusoes' => $totalInclusions, 'exclusoes' => $totalExclusions]
                );
            }
        }
    }

    /** @param array<int, array<string, mixed>> $units */
    private static function eachUnitHasDifferences(array $units): bool
    {
        foreach ($units as $unit) {
            if ($unit['inclusoes_retificacao'] < 1 || $unit['exclusoes_retificacao'] < 1) {
                return false;
            }
        }
        return true;
    }

    /** @param array<string, mixed> $configuration */
    private static function validateCleanupSequences(array $configuration): void
    {
        $sequences = $configuration['sequenciais_limpeza_autorizados'] ?? null;
        if (!is_array($sequences) || !array_is_list($sequences)) {
            throw self::error(
                'CONFIG_SEQUENCIAIS_LIMPEZA_INVALIDOS',
                'sequenciais_limpeza_autorizados',
                'lista de inteiros positivos distintos',
                $sequences
            );
        }

        foreach ($sequences as $index => $sequence) {
            if (!is_int($sequence)) {
                throw self::error(
                    'CONFIG_SEQUENCIAL_LIMPEZA_INVALIDO',
                    'sequenciais_limpeza_autorizados.' . $index,
                    'inteiro positivo',
                    $sequence
                );
            }
            try {
                new SequencialPca($sequence);
            } catch (Throwable $failure) {
                throw self::error(
                    'CONFIG_SEQUENCIAL_LIMPEZA_INVALIDO',
                    'sequenciais_limpeza_autorizados.' . $index,
                    'inteiro positivo',
                    $sequence,
                    $failure
                );
            }
        }

        if (count(array_unique($sequences, SORT_NUMERIC)) !== count($sequences)) {
            throw self::error(
                'CONFIG_SEQUENCIAIS_LIMPEZA_DUPLICADOS',
                'sequenciais_limpeza_autorizados',
                'sequenciais distintos',
                $sequences
            );
        }
    }

    /** @param array<string, mixed> $values @param string[] $expectedKeys */
    private static function requireExactKeys(array $values, array $expectedKeys, string $path): void
    {
        $actualKeys = array_keys($values);
        $invalidKey = array_filter($actualKeys, static fn (mixed $key): bool => !is_string($key));
        sort($actualKeys);
        sort($expectedKeys);

        if ($invalidKey !== [] || $actualKeys !== $expectedKeys) {
            throw self::error(
                'CONFIG_ESTRUTURA_INVALIDA',
                $path,
                'chaves exatas: ' . implode(', ', $expectedKeys),
                array_keys($values)
            );
        }
    }

    /** @param array<string, mixed> $values */
    private static function requireConsent(array $values, string $key, bool $required = true): void
    {
        if (!is_bool($values[$key] ?? null) || ($required && $values[$key] !== true)) {
            throw self::error(
                'CONFIG_CONSENTIMENTO_AUSENTE',
                $key,
                $required ? 'true explícito' : 'booleano explícito',
                $values[$key] ?? null
            );
        }
    }

    /** @param array<string, mixed> $values */
    private static function requireNonEmptyString(array $values, string $key, string $prefix = ''): string
    {
        $value = $values[$key] ?? null;
        $path = $prefix === '' ? $key : $prefix . '.' . $key;
        if (!is_string($value) || trim($value) === '') {
            throw self::error('CONFIG_TIPO_INVALIDO', $path, 'texto não vazio', $value);
        }
        return $value;
    }

    /** @param array<string, mixed> $values @param string[] $allowed */
    private static function requireEnum(array $values, string $key, array $allowed): string
    {
        $value = self::requireNonEmptyString($values, $key);
        if (!in_array($value, $allowed, true)) {
            throw self::error(
                'CONFIG_VALOR_INVALIDO',
                $key,
                implode(' | ', $allowed),
                $value
            );
        }
        return $value;
    }

    /** @param array<string, mixed> $values */
    private static function requirePositiveInteger(array $values, string $key, string $prefix = ''): int
    {
        $value = $values[$key] ?? null;
        $path = $prefix === '' ? $key : $prefix . '.' . $key;
        if (!is_int($value) || $value < 1) {
            throw self::error('CONFIG_TIPO_INVALIDO', $path, 'inteiro positivo', $value);
        }
        return $value;
    }

    /** @param array<string, mixed> $values */
    private static function requireNonNegativeInteger(
        array $values,
        string $key,
        string $prefix = ''
    ): int {
        $value = $values[$key] ?? null;
        $path = $prefix === '' ? $key : $prefix . '.' . $key;
        if (!is_int($value) || $value < 0) {
            throw self::error('CONFIG_TIPO_INVALIDO', $path, 'inteiro não negativo', $value);
        }
        return $value;
    }

    private static function resolveExternalFile(string $path): string
    {
        if (preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\\/]{2}|\/)/D', $path) !== 1) {
            throw self::error(
                'CONFIG_CAMINHO_NAO_ABSOLUTO',
                self::ENVIRONMENT_VARIABLE,
                'caminho absoluto',
                $path
            );
        }

        $resolved = realpath($path);
        if ($resolved === false || !is_file($resolved) || !is_readable($resolved)) {
            throw self::error(
                'CONFIG_ARQUIVO_ILEGIVEL',
                self::ENVIRONMENT_VARIABLE,
                'arquivo existente e legível',
                $path
            );
        }

        $repositoryRoot = realpath(dirname(__DIR__, 4));
        if ($repositoryRoot === false || self::isInside($resolved, $repositoryRoot)) {
            throw self::error(
                'CONFIG_ARQUIVO_INTERNO',
                self::ENVIRONMENT_VARIABLE,
                'arquivo externo ao repositório',
                $resolved
            );
        }

        return $resolved;
    }

    private static function isInside(string $path, string $directory): bool
    {
        $path = str_replace('\\', '/', $path);
        $directory = rtrim(str_replace('\\', '/', $directory), '/');

        if (PHP_OS_FAMILY === 'Windows') {
            $path = strtolower($path);
            $directory = strtolower($directory);
        }

        return $path === $directory || str_starts_with($path, $directory . '/');
    }

    private static function error(
        string $code,
        string $path,
        string $expected,
        mixed $actual,
        ?Throwable $previous = null
    ): Sprint84ConfigurationException {
        return new Sprint84ConfigurationException($code, $path, $expected, $actual, $previous);
    }
}
