<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint85\Support;

use Semae\Pca\Infrastructure\Oracle\Adodb\ConversorCharsetAdodbOracle;
use Tests\Contract\Sprint84\Support\Sprint84ConfigurationException;
use Throwable;

final class Sprint85LogGateConfig
{
    public const ENVIRONMENT_VARIABLE = 'PCA_SPRINT85_LOG_GATE_CONFIG_FILE';

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
            'charset_adodb',
            'etapa_autorizada',
            'oracle_checkpoint_sha256',
            'identificador_log',
        ]);

        $stage = $configuration['etapa_autorizada'] ?? null;
        if (!is_string($stage) || !in_array($stage, ['PRECHECK', 'LOGS'], true)) {
            throw self::error('CONFIG_ETAPA_INVALIDA', 'etapa_autorizada', 'PRECHECK|LOGS', $stage);
        }

        self::requireBoolean($configuration, 'allow_oracle_writes', false);
        self::requireBoolean($configuration, 'allow_pncp_writes', false);
        self::requireBoolean($configuration, 'allow_log_writes', $stage === 'LOGS');
        self::requireNonEmptyString($configuration, 'server_name');
        $charset = self::requireNonEmptyString($configuration, 'charset_adodb');
        try {
            new ConversorCharsetAdodbOracle($charset);
        } catch (Throwable $failure) {
            throw self::error(
                'CONFIG_CHARSET_INVALIDO',
                'charset_adodb',
                'charset aceito pelo conversor ADOdb',
                $charset,
                $failure
            );
        }

        $identifier = self::requireNonEmptyString($configuration, 'identificador_log');
        if (preg_match('/^[A-Z0-9-]{12,80}$/D', $identifier) !== 1) {
            throw self::error(
                'CONFIG_IDENTIFICADOR_INVALIDO',
                'identificador_log',
                '12 a 80 caracteres ASCII: A-Z, 0-9 e hífen',
                $identifier
            );
        }

        $hash = $configuration['oracle_checkpoint_sha256'] ?? null;
        if ($stage === 'PRECHECK' && $hash === null) {
            return $configuration;
        }
        if (!is_string($hash) || preg_match('/^[a-f0-9]{64}$/D', $hash) !== 1) {
            throw self::error(
                'CONFIG_HASH_INVALIDO',
                'oracle_checkpoint_sha256',
                'SHA-256 minúsculo; null somente no PRECHECK inicial',
                $hash
            );
        }

        return $configuration;
    }

    /** @param array<string, mixed> $configuration */
    public static function assertServer(array $configuration, string $serverName): void
    {
        $configuration = self::validate($configuration);
        if ($serverName !== $configuration['server_name']) {
            throw self::error(
                'SERVER_NAME_DIVERGENTE',
                'server_name',
                (string) $configuration['server_name'],
                $serverName
            );
        }

    }

    /** @param array<string, mixed> $values @param string[] $expected */
    private static function requireExactKeys(array $values, array $expected): void
    {
        $found = array_keys($values);
        sort($found, SORT_STRING);
        sort($expected, SORT_STRING);
        if ($found !== $expected) {
            throw self::error('CONFIG_CHAVES_INVALIDAS', '$', 'chaves exatas do gate de logs', $found);
        }
    }

    /** @param array<string, mixed> $values */
    private static function requireBoolean(array $values, string $key, bool $expected): void
    {
        if (($values[$key] ?? null) !== $expected) {
            throw self::error(
                'CONFIG_CONSENTIMENTO_INVALIDO',
                $key,
                $expected ? 'true' : 'false',
                $values[$key] ?? null
            );
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
