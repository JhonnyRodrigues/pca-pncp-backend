<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint85\Support;

use Semae\Pca\Composition\CarregadorPerfilPublicacaoPca;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Tests\Contract\Sprint84\Support\Sprint84ConfigurationException;
use Throwable;

final class Sprint85Config
{
    public const ENVIRONMENT_VARIABLE = 'PCA_SPRINT85_CONFIG_FILE';

    private const STAGES = ['PRECHECK', 'LOGS', 'CONTENCAO', 'SMOKE'];

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

        $path = realpath($locator);
        if ($path === false || !is_file($path) || !is_readable($path) || !self::isAbsolute($locator)) {
            throw self::error(
                'CONFIG_CAMINHO_INVALIDO',
                self::ENVIRONMENT_VARIABLE,
                'arquivo absoluto, existente e legível',
                $locator
            );
        }

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
            throw self::error('CONFIG_RETORNO_INVALIDO', '$', 'array', $configuration);
        }

        return self::validate($configuration);
    }

    /** @param array<string, mixed> $configuration @return array<string, mixed> */
    public static function validate(array $configuration): array
    {
        self::requireExactKeys($configuration, [
            'allow_oracle_writes',
            'allow_pncp_writes',
            'allow_log_writes',
            'server_name',
            'modo',
            'ano',
            'etapa_autorizada',
            'unidades',
            'oracle_checkpoint_sha256',
            'identificador_log',
            'quantidade_logs_esperada',
            'sequenciais_legados',
            'sequencial_pca_excluido',
            'id_plano_sem_confirmacao',
            'baseline_auditoria',
        ]);

        $stage = self::requireEnum($configuration, 'etapa_autorizada', self::STAGES);
        self::requireBoolean($configuration, 'allow_pncp_writes', false);
        self::requireBoolean($configuration, 'allow_oracle_writes', $stage === 'SMOKE');
        self::requireBoolean($configuration, 'allow_log_writes', $stage !== 'PRECHECK');

        self::requireNonEmptyString($configuration, 'server_name');
        if (($configuration['modo'] ?? null) !== 'CONSOLIDADO') {
            throw self::error('CONFIG_MODO_INVALIDO', 'modo', 'CONSOLIDADO', $configuration['modo'] ?? null);
        }

        $year = self::requirePositiveInteger($configuration, 'ano');
        try {
            new AnoExecucao($year);
        } catch (Throwable $failure) {
            throw self::error(
                'CONFIG_ANO_INVALIDO',
                'ano',
                'ano aceito pelo domínio',
                $year,
                $failure
            );
        }

        self::validateUnits($configuration['unidades'] ?? null);
        self::validateHash($configuration['oracle_checkpoint_sha256'] ?? null, $stage);
        self::requireNonEmptyString($configuration, 'identificador_log');
        self::requireNonNegativeInteger($configuration, 'quantidade_logs_esperada');
        self::requireExactValue($configuration, 'sequenciais_legados', [16, 17, 18]);
        self::requireExactValue($configuration, 'sequencial_pca_excluido', 36);
        self::requireExactValue($configuration, 'id_plano_sem_confirmacao', 103);
        self::requireNonNegativeInteger($configuration, 'baseline_auditoria');

        return $configuration;
    }

    /** @param array<string, mixed> $configuration */
    public static function loadHomologationProfile(
        array $configuration,
        string $serverName
    ): PerfilPublicacaoPca {
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
            $profile = new PerfilPublicacaoPca(
                (new CarregadorPerfilPublicacaoPca())->carregar($serverName)
            );
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

        return $profile;
    }

    private static function validateUnits(mixed $units): void
    {
        if (
            !is_array($units)
            || !array_is_list($units)
            || !in_array(count($units), [2, 3], true)
        ) {
            throw self::error(
                'CONFIG_UNIDADES_INVALIDAS',
                'unidades',
                'lista com duas ou três UOs',
                $units
            );
        }

        $identifiers = [];
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
            ], $path);
            $identifiers[] = self::requirePositiveInteger($unit, 'id_unidade_orcamentaria', $path);
            self::requirePositiveInteger($unit, 'quantidade_versoes', $path);
            self::requireNonNegativeInteger($unit, 'quantidade_fotografia', $path);
            self::requireNonNegativeInteger($unit, 'quantidade_demandas', $path);
        }

        if (count(array_unique($identifiers)) !== count($identifiers)) {
            throw self::error(
                'CONFIG_UNIDADES_REPETIDAS',
                'unidades',
                'UOs distintas',
                $identifiers
            );
        }
    }

    private static function validateHash(mixed $hash, string $stage): void
    {
        if ($stage === 'PRECHECK' && $hash === null) {
            return;
        }
        if (!is_string($hash) || preg_match('/^[a-f0-9]{64}$/D', $hash) !== 1) {
            throw self::error(
                'CONFIG_HASH_INVALIDO',
                'oracle_checkpoint_sha256',
                'SHA-256 minúsculo; null somente no PRECHECK inicial',
                $hash
            );
        }
    }

    /** @param array<string, mixed> $values @param string[] $expected */
    private static function requireExactKeys(array $values, array $expected, string $path = '$'): void
    {
        $found = array_keys($values);
        sort($found, SORT_STRING);
        sort($expected, SORT_STRING);
        if ($found !== $expected) {
            throw self::error('CONFIG_CHAVES_INVALIDAS', $path, 'chaves exatas do contrato', $found);
        }
    }

    /** @param array<string, mixed> $values @param string[] $allowed */
    private static function requireEnum(array $values, string $key, array $allowed): string
    {
        $value = $values[$key] ?? null;
        if (!is_string($value) || !in_array($value, $allowed, true)) {
            throw self::error('CONFIG_ENUM_INVALIDO', $key, implode('|', $allowed), $value);
        }
        return $value;
    }

    /** @param array<string, mixed> $values */
    private static function requireBoolean(array $values, string $key, bool $expected): void
    {
        if (($values[$key] ?? null) !== $expected) {
            throw self::error('CONFIG_CONSENTIMENTO_INVALIDO', $key, $expected ? 'true' : 'false', $values[$key] ?? null);
        }
    }

    /** @param array<string, mixed> $values */
    private static function requireNonEmptyString(array $values, string $key): string
    {
        $value = $values[$key] ?? null;
        if (!is_string($value) || trim($value) === '') {
            throw self::error('CONFIG_TEXTO_INVALIDO', $key, 'texto não vazio', $value);
        }
        return $value;
    }

    /** @param array<string, mixed> $values */
    private static function requirePositiveInteger(array $values, string $key, string $path = '$'): int
    {
        $value = $values[$key] ?? null;
        if (!is_int($value) || $value <= 0) {
            throw self::error('CONFIG_INTEIRO_INVALIDO', $path . '.' . $key, 'inteiro positivo', $value);
        }
        return $value;
    }

    /** @param array<string, mixed> $values */
    private static function requireNonNegativeInteger(array $values, string $key): int
    {
        $value = $values[$key] ?? null;
        if (!is_int($value) || $value < 0) {
            throw self::error('CONFIG_INTEIRO_INVALIDO', $key, 'inteiro não negativo', $value);
        }
        return $value;
    }

    /** @param array<string, mixed> $values */
    private static function requireExactValue(array $values, string $key, mixed $expected): void
    {
        if (($values[$key] ?? null) !== $expected) {
            throw self::error('CONFIG_MASSA_INVALIDA', $key, json_encode($expected, JSON_THROW_ON_ERROR), $values[$key] ?? null);
        }
    }

    private static function isAbsolute(string $path): bool
    {
        return preg_match('/^(?:[A-Za-z]:[\\\\\/]|\/)/', $path) === 1;
    }

    private static function error(
        string $code,
        string $field,
        string $expected,
        mixed $actual,
        ?Throwable $previous = null
    ): Sprint84ConfigurationException {
        return new Sprint84ConfigurationException($code, $field, $expected, $actual, $previous);
    }
}
