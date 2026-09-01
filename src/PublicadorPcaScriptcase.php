<?php

declare(strict_types=1);

namespace Semae\Pca;

use InvalidArgumentException;
use RuntimeException;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Composition\CompositionRootPublicacaoPca;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Presentation\Facades\FachadaPublicarPca;
use Semae\Pca\Presentation\Inputs\EntradaPublicarPca;
use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;
use Throwable;
use UnexpectedValueException;

final class PublicadorPcaScriptcase
{
    private const VARIAVEL_REGISTRO = 'PCA_PNCP_PROFILE_REGISTRY_FILE';
    private const UUID_V4 = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/Di';

    public function executar(
        object $conexaoAdodb,
        string $loginAutenticado,
        string $serverName,
        int $anoExecucao,
        ?string $requestId
    ): PublicarPcaViewModel {
        if ($requestId === null || preg_match(self::UUID_V4, $requestId) !== 1) {
            return FachadaPublicarPca::interrompidaPorRequestIdInvalido();
        }

        $correlacao = new CorrelacaoExecucao($requestId);

        try {
            $registro = self::carregarRegistroExterno();

            if (!array_key_exists($serverName, $registro)) {
                throw new UnexpectedValueException('Servidor não autorizado pelo registro.');
            }

            $perfil = self::carregarArrayPhp($registro[$serverName]);
            $cnpjBruto = $perfil['cnpj'] ?? null;

            if (!is_string($cnpjBruto)) {
                throw new InvalidArgumentException('CNPJ do perfil é inválido.');
            }

            $cnpj = new Cnpj($cnpjBruto);
            $entrada = new EntradaPublicarPca($cnpj->getValor(), $anoExecucao);
            $fachada = (new CompositionRootPublicacaoPca())->montar(
                $conexaoAdodb,
                $loginAutenticado,
                $perfil,
                $correlacao
            );

            return $fachada->executar($entrada, $correlacao);
        } catch (Throwable) {
            return FachadaPublicarPca::interrompidaPorFalhaInesperada($correlacao);
        }
    }

    /** @return array<string, string> */
    private static function carregarRegistroExterno(): array
    {
        $localizador = getenv(self::VARIAVEL_REGISTRO);

        if (!is_string($localizador)) {
            throw new RuntimeException('Localizador do registro externo ausente.');
        }

        $caminhoRegistro = self::resolverArquivoExterno($localizador);
        $registroBruto = self::carregarArrayPhp($caminhoRegistro);
        $registro = [];

        foreach ($registroBruto as $serverName => $caminhoPerfil) {
            if (!is_string($serverName) || !is_string($caminhoPerfil)) {
                throw new UnexpectedValueException('Estrutura do registro externo inválida.');
            }

            $registro[$serverName] = self::resolverArquivoExterno($caminhoPerfil);
        }

        return $registro;
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

        $raizRelease = realpath(dirname(__DIR__));

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
