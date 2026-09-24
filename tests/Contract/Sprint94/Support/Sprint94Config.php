<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint94\Support;

use Semae\Pca\Composition\CarregadorPerfilPublicacaoPca;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Throwable;

final class Sprint94Config
{
    public const ENVIRONMENT_VARIABLE = 'PCA_SPRINT94_CONFIG_FILE';

    private const KEYS = [
        'allow_oracle_writes',
        'allow_pncp_writes',
        'allow_log_writes',
        'server_name',
        'login_autorizado',
        'anos',
        'etapa_autorizada',
    ];

    /** @return array<string, mixed> */
    public static function loadFromEnvironment(): array
    {
        $locator = getenv(self::ENVIRONMENT_VARIABLE);
        if (!is_string($locator) || !self::isAbsolute($locator)) {
            throw self::error('CONFIG_LOCALIZADOR_INVALIDO', 'O localizador deve ser absoluto.');
        }

        $path = realpath($locator);
        if ($path === false || !is_file($path) || !is_readable($path)) {
            throw self::error('CONFIG_ARQUIVO_INDISPONIVEL', 'A configuracao externa nao esta legivel.');
        }

        try {
            $configuration = (static fn (string $file): mixed => require $file)($path);
        } catch (Throwable $failure) {
            throw self::error('CONFIG_CARREGAMENTO_FALHOU', 'A configuracao externa falhou.', $failure);
        }

        if (!is_array($configuration)) {
            throw self::error('CONFIG_RETORNO_INVALIDO', 'A configuracao deve retornar um array.');
        }

        return self::validate($configuration);
    }

    /** @param array<string, mixed> $configuration @return array<string, mixed> */
    public static function validate(array $configuration): array
    {
        $keys = array_keys($configuration);
        $expected = self::KEYS;
        sort($keys, SORT_STRING);
        sort($expected, SORT_STRING);
        if ($keys !== $expected) {
            throw self::error('CONFIG_CHAVES_INVALIDAS', 'A configuracao deve conter apenas as chaves previstas.');
        }

        foreach (['allow_oracle_writes', 'allow_pncp_writes', 'allow_log_writes'] as $flag) {
            if (($configuration[$flag] ?? null) !== false) {
                throw self::error('CONFIG_ESCRITA_PROIBIDA', "{$flag} deve permanecer false.");
            }
        }

        foreach (['server_name', 'login_autorizado'] as $field) {
            if (!is_string($configuration[$field] ?? null) || trim($configuration[$field]) === '') {
                throw self::error('CONFIG_IDENTIDADE_INVALIDA', "{$field} deve ser texto nao vazio.");
            }
        }

        if (($configuration['anos'] ?? null) !== [2026, 2027]) {
            throw self::error('CONFIG_ANOS_INVALIDOS', 'Somente os exercicios 2026 e 2027 sao permitidos.');
        }

        if (!in_array($configuration['etapa_autorizada'] ?? null, ['PRECHECK', 'INVENTARIO'], true)) {
            throw self::error('CONFIG_ETAPA_INVALIDA', 'Somente PRECHECK ou INVENTARIO sao permitidos.');
        }

        return $configuration;
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array{profile: PerfilPublicacaoPca, cnpj: string}
     */
    public static function loadProductionProfile(
        array $configuration,
        string $login,
        string $serverName
    ): array {
        $configuration = self::validate($configuration);
        if (!hash_equals((string) $configuration['server_name'], $serverName)) {
            throw self::error('SERVER_NAME_DIVERGENTE', 'O servidor nao foi autorizado para o gate.');
        }
        if (!hash_equals((string) $configuration['login_autorizado'], $login)) {
            throw self::error('LOGIN_DIVERGENTE', 'O usuario nao foi autorizado para o gate.');
        }

        try {
            $raw = (new CarregadorPerfilPublicacaoPca())->carregar($serverName);
            $profile = new PerfilPublicacaoPca($raw);
        } catch (Throwable $failure) {
            throw self::error('PERFIL_INVALIDO', 'O perfil externo nao pode ser carregado.', $failure);
        }

        if ($profile->getAmbiente() !== AmbientePncp::PRODUCAO) {
            throw self::error('PERFIL_NAO_PRODUCAO', 'O gate exige o perfil PRODUCAO.');
        }

        $cnpj = $raw['cnpj'] ?? null;
        if (!is_string($cnpj) || $cnpj === '') {
            throw self::error('PERFIL_CNPJ_INVALIDO', 'O CNPJ do perfil nao esta disponivel.');
        }

        return ['profile' => $profile, 'cnpj' => $cnpj];
    }

    private static function isAbsolute(string $path): bool
    {
        return preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\\/]{2}|\/)/D', $path) === 1;
    }

    private static function error(
        string $code,
        string $message,
        ?Throwable $previous = null
    ): Sprint94ConfigurationException {
        return new Sprint94ConfigurationException($code, $message, $previous);
    }
}
