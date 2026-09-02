<?php

declare(strict_types=1);

namespace Semae\Pca\Composition;

use RuntimeException;
use UnexpectedValueException;

final class CarregadorPerfilPublicacaoPca
{
    private const VARIAVEL_REGISTRO = 'PCA_PNCP_PROFILE_REGISTRY_FILE';

    /** @return array<string, mixed> */
    public function carregar(string $serverName): array
    {
        $localizador = getenv(self::VARIAVEL_REGISTRO);

        if (!is_string($localizador)) {
            throw new RuntimeException('Localizador do registro externo ausente.');
        }

        $caminhoRegistro = self::resolverArquivoExterno($localizador);
        $registroBruto = self::carregarArrayPhp($caminhoRegistro);
        $registro = [];

        foreach ($registroBruto as $servidorRegistrado => $caminhoPerfil) {
            if (!is_string($servidorRegistrado) || !is_string($caminhoPerfil)) {
                throw new UnexpectedValueException('Estrutura do registro externo inválida.');
            }

            $registro[$servidorRegistrado] = self::resolverArquivoExterno($caminhoPerfil);
        }

        if (!array_key_exists($serverName, $registro)) {
            throw new UnexpectedValueException('Servidor não autorizado pelo registro.');
        }

        return self::carregarArrayPhp($registro[$serverName]);
    }

    /** @return array<string, mixed> */
    private static function carregarArrayPhp(string $caminho): array
    {
        $retorno = (static fn (string $arquivo): mixed => require $arquivo)($caminho);

        if (!is_array($retorno)) {
            throw new UnexpectedValueException('Arquivo externo deve retornar um array.');
        }

        return $retorno;
    }

    private static function resolverArquivoExterno(string $caminho): string
    {
        if (preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\\/]{2}|\/)/D', $caminho) !== 1) {
            throw new RuntimeException('Caminho externo deve ser absoluto.');
        }

        $resolvido = realpath($caminho);

        if ($resolvido === false || !is_file($resolvido) || !is_readable($resolvido)) {
            throw new RuntimeException('Arquivo externo não está legível.');
        }

        $raizRelease = realpath(dirname(__DIR__, 2));

        if ($raizRelease === false || self::estaDentro($resolvido, $raizRelease)) {
            throw new RuntimeException('Arquivo externo deve permanecer fora da release.');
        }

        return $resolvido;
    }

    private static function estaDentro(string $caminho, string $diretorio): bool
    {
        $caminho = str_replace('\\', '/', $caminho);
        $diretorio = rtrim(str_replace('\\', '/', $diretorio), '/');

        if (PHP_OS_FAMILY === 'Windows') {
            $caminho = strtolower($caminho);
            $diretorio = strtolower($diretorio);
        }

        return $caminho === $diretorio || str_starts_with($caminho, $diretorio . '/');
    }
}
