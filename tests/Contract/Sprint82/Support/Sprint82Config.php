<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint82\Support;

use InvalidArgumentException;
use RuntimeException;
use Semae\Pca\Composition\CarregadorPerfilPublicacaoPca;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;

final class Sprint82Config
{
    private const VARIAVEL_CONFIGURACAO = 'PCA_SPRINT82_CONFIG_FILE';

    /** @return array<string, mixed> */
    public static function loadFromEnvironment(): array
    {
        $localizador = getenv(self::VARIAVEL_CONFIGURACAO);

        if (!is_string($localizador) || trim($localizador) === '') {
            throw new RuntimeException('PCA_SPRINT82_CONFIG_FILE não foi informado.');
        }

        $caminho = self::resolverArquivoExterno($localizador);
        $configuracao = require $caminho;

        if (!is_array($configuracao)) {
            throw new RuntimeException('A configuração externa da Sprint 8.2 deve retornar um array.');
        }

        return self::validar($configuracao);
    }

    /** @param array<string, mixed> $configuracao */
    public static function loadHomologationProfile(
        array $configuracao,
        string $serverName
    ): PerfilPublicacaoPca
    {
        return new PerfilPublicacaoPca(
            self::loadHomologationProfileArray($configuracao, $serverName)
        );
    }

    /**
     * @param array<string, mixed> $configuracao
     * @return array<string, mixed>
     */
    public static function loadHomologationProfileArray(
        array $configuracao,
        string $serverName
    ): array {
        $configuracao = self::validar($configuracao);

        if ($serverName !== $configuracao['server_name']) {
            throw new InvalidArgumentException(
                'O servidor recebido diverge do servidor autorizado para a Sprint 8.2.'
            );
        }

        $perfilBruto = (new CarregadorPerfilPublicacaoPca())->carregar($serverName);
        $perfil = new PerfilPublicacaoPca($perfilBruto);

        if ($perfil->getAmbiente() !== AmbientePncp::HOMOLOGACAO) {
            throw new InvalidArgumentException(
                'A integração Oracle da Sprint 8.2 aceita somente perfil de homologação.'
            );
        }

        return $perfilBruto;
    }

    /** @param array<string, mixed> $configuracao @return array<string, mixed> */
    private static function validar(array $configuracao): array
    {
        self::exigirChavesExatas($configuracao, ['allow_writes', 'server_name', 'massa']);

        if ($configuracao['allow_writes'] !== true) {
            throw new InvalidArgumentException(
                'allow_writes=true é obrigatório para a integração Oracle da Sprint 8.2.'
            );
        }

        self::exigirTextoNaoVazio($configuracao, 'server_name');
        $massa = $configuracao['massa'] ?? null;

        if (!is_array($massa)) {
            throw new InvalidArgumentException('A massa dedicada da Sprint 8.2 é obrigatória.');
        }

        self::exigirChavesExatas($massa, [
            'ano',
            'id_unidade_orcamentaria',
            'sequencial_teste',
            'mensagem_auditoria',
            'lock_hold_seconds',
        ]);
        $ano = self::exigirInteiroPositivo($massa, 'ano');
        self::exigirInteiroPositivo($massa, 'id_unidade_orcamentaria');
        $sequencial = self::exigirInteiroPositivo($massa, 'sequencial_teste');
        self::exigirTextoNaoVazio($massa, 'mensagem_auditoria');
        $duracaoLock = self::exigirInteiroPositivo($massa, 'lock_hold_seconds');

        if ($duracaoLock > 60) {
            throw new InvalidArgumentException('lock_hold_seconds não pode exceder 60 segundos.');
        }

        new AnoExecucao($ano);
        new SequencialPca($sequencial);

        return $configuracao;
    }

    /** @param array<string, mixed> $valores @param string[] $esperadas */
    private static function exigirChavesExatas(array $valores, array $esperadas): void
    {
        $recebidas = array_keys($valores);

        foreach ($recebidas as $recebida) {
            if (!is_string($recebida)) {
                throw new InvalidArgumentException('A configuração possui chave inválida.');
            }
        }

        sort($recebidas, SORT_STRING);
        sort($esperadas, SORT_STRING);

        if ($recebidas !== $esperadas) {
            throw new InvalidArgumentException('A estrutura da configuração da Sprint 8.2 é inválida.');
        }
    }

    /** @param array<string, mixed> $valores */
    private static function exigirTextoNaoVazio(array $valores, string $chave): string
    {
        $valor = $valores[$chave] ?? null;

        if (!is_string($valor) || trim($valor) === '') {
            throw new InvalidArgumentException("{$chave} deve ser texto não vazio.");
        }

        return $valor;
    }

    /** @param array<string, mixed> $valores */
    private static function exigirInteiroPositivo(array $valores, string $chave): int
    {
        $valor = $valores[$chave] ?? null;

        if (!is_int($valor) || $valor < 1) {
            throw new InvalidArgumentException("{$chave} deve ser inteiro positivo.");
        }

        return $valor;
    }

    private static function resolverArquivoExterno(string $caminho): string
    {
        if (preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\\/]{2}|\/)/D', $caminho) !== 1) {
            throw new RuntimeException('A configuração da Sprint 8.2 deve usar caminho absoluto.');
        }

        $resolvido = realpath($caminho);

        if ($resolvido === false || !is_file($resolvido) || !is_readable($resolvido)) {
            throw new RuntimeException('A configuração externa da Sprint 8.2 não está legível.');
        }

        $raizRepositorio = realpath(dirname(__DIR__, 4));

        if ($raizRepositorio === false || self::estaDentro($resolvido, $raizRepositorio)) {
            throw new RuntimeException('A configuração da Sprint 8.2 deve ficar fora do repositório.');
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
