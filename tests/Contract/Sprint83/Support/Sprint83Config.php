<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint83\Support;

use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;
use Semae\Pca\Composition\CarregadorPerfilPublicacaoPca;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;

final class Sprint83Config
{
    public const MANUAL_PNCP_VERSAO = '2.6';
    public const MANUAL_PNCP_DATA = '2026-08-31';
    public const OPENAPI_VERSAO = '1.0';
    public const CONTRATO_REVALIDADO_EM = '2026-09-02';

    private const VARIAVEL_CONFIGURACAO = 'PCA_SPRINT83_CONFIG_FILE';

    /** @return array<string, mixed> */
    public static function loadFromEnvironment(): array
    {
        $localizador = getenv(self::VARIAVEL_CONFIGURACAO);

        if (!is_string($localizador) || trim($localizador) === '') {
            throw new RuntimeException('PCA_SPRINT83_CONFIG_FILE não foi informado.');
        }

        $caminho = self::resolverArquivoExterno($localizador);
        $configuracao = require $caminho;

        if (!is_array($configuracao)) {
            throw new RuntimeException('A configuração externa da Sprint 8.3 deve retornar um array.');
        }

        return self::validar($configuracao);
    }

    /** @param array<string, mixed> $configuracao */
    public static function loadHomologationProfile(
        array $configuracao,
        string $serverName
    ): PerfilPublicacaoPca {
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
                'O servidor recebido diverge do servidor autorizado para a Sprint 8.3.'
            );
        }

        $perfilBruto = (new CarregadorPerfilPublicacaoPca())->carregar($serverName);
        $perfil = new PerfilPublicacaoPca($perfilBruto);

        if ($perfil->getAmbiente() !== AmbientePncp::HOMOLOGACAO) {
            throw new InvalidArgumentException(
                'A suíte PNCP da Sprint 8.3 aceita somente perfil de homologação.'
            );
        }

        $cnpj = $perfilBruto['cnpj'] ?? null;
        if (!is_string($cnpj)) {
            throw new InvalidArgumentException('O CNPJ do perfil da Sprint 8.3 é inválido.');
        }
        new Cnpj($cnpj);

        return $perfilBruto;
    }

    /** @param array<string, mixed> $configuracao @return array<string, mixed> */
    private static function validar(array $configuracao): array
    {
        self::exigirChavesExatas($configuracao, ['allow_writes', 'server_name', 'massa']);

        if ($configuracao['allow_writes'] !== true) {
            throw new InvalidArgumentException(
                'allow_writes=true é obrigatório para a suíte PNCP da Sprint 8.3.'
            );
        }

        self::exigirTextoNaoVazio($configuracao, 'server_name');
        $massa = $configuracao['massa'] ?? null;

        if (!is_array($massa)) {
            throw new InvalidArgumentException('A massa dedicada da Sprint 8.3 é obrigatória.');
        }

        self::exigirChavesExatas($massa, [
            'ano',
            'data_desejada',
            'codigo_uasg',
            'descricao_uasg',
            'id_demanda_publicacao_1',
            'id_demanda_publicacao_2',
            'id_demanda_inclusao',
            'residuo_sequencial_autorizado',
            'item',
        ]);

        $ano = self::exigirInteiroPositivo($massa, 'ano');
        new AnoExecucao($ano);
        self::validarData($massa, $ano);
        new Uasg(
            self::exigirTextoNaoVazio($massa, 'codigo_uasg'),
            self::exigirTextoNaoVazio($massa, 'descricao_uasg')
        );
        self::validarIdsLacunados($massa);
        self::validarResiduo($massa);
        self::validarItem($massa);

        return $configuracao;
    }

    /** @param array<string, mixed> $massa */
    private static function validarData(array $massa, int $ano): void
    {
        $valor = self::exigirTextoNaoVazio($massa, 'data_desejada');
        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);

        if (
            $data === false
            || $data->format('Y-m-d') !== $valor
            || (int) $data->format('Y') !== $ano
        ) {
            throw new InvalidArgumentException(
                'data_desejada deve ser válida e pertencer ao ano da massa.'
            );
        }
    }

    /** @param array<string, mixed> $massa */
    private static function validarIdsLacunados(array $massa): void
    {
        $ids = [
            self::exigirInteiroPositivo($massa, 'id_demanda_publicacao_1'),
            self::exigirInteiroPositivo($massa, 'id_demanda_publicacao_2'),
            self::exigirInteiroPositivo($massa, 'id_demanda_inclusao'),
        ];
        sort($ids, SORT_NUMERIC);

        if (count(array_unique($ids)) !== 3 || $ids[1] - $ids[0] < 2 || $ids[2] - $ids[1] < 2) {
            throw new InvalidArgumentException(
                'Os IDs de Demanda da Sprint 8.3 devem ser distintos e lacunados.'
            );
        }
    }

    /** @param array<string, mixed> $massa */
    private static function validarResiduo(array $massa): void
    {
        $residuo = $massa['residuo_sequencial_autorizado'];

        if ($residuo === null) {
            return;
        }
        if (!is_int($residuo)) {
            throw new InvalidArgumentException(
                'residuo_sequencial_autorizado deve ser inteiro positivo ou null.'
            );
        }

        new SequencialPca($residuo);
    }

    /** @param array<string, mixed> $massa */
    private static function validarItem(array $massa): void
    {
        $item = $massa['item'] ?? null;
        if (!is_array($item)) {
            throw new InvalidArgumentException('O item da massa da Sprint 8.3 é obrigatório.');
        }

        self::exigirChavesExatas($item, [
            'categoria',
            'catalogo',
            'unidade_medida',
            'codigo_item',
            'grupo',
            'descricao_grupo',
            'origem',
        ]);
        $categoria = self::exigirInteiroPositivo($item, 'categoria');
        $catalogo = self::exigirInteiroPositivo($item, 'catalogo');

        if (!in_array($categoria, [1, 2], true) || !in_array($catalogo, [1, 2], true)) {
            throw new InvalidArgumentException('Categoria e catálogo devem usar literais suportados.');
        }

        foreach (['unidade_medida', 'codigo_item', 'grupo', 'descricao_grupo', 'origem'] as $campo) {
            self::exigirTextoNaoVazio($item, $campo);
        }
        if (!in_array($item['origem'], ['N', 'E'], true)) {
            throw new InvalidArgumentException('Origem deve usar um literal suportado.');
        }
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
            throw new InvalidArgumentException('A estrutura da configuração da Sprint 8.3 é inválida.');
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
            throw new RuntimeException('A configuração da Sprint 8.3 deve usar caminho absoluto.');
        }

        $resolvido = realpath($caminho);

        if ($resolvido === false || !is_file($resolvido) || !is_readable($resolvido)) {
            throw new RuntimeException('A configuração externa da Sprint 8.3 não está legível.');
        }

        $raizRepositorio = realpath(dirname(__DIR__, 4));

        if ($raizRepositorio === false || self::estaDentro($resolvido, $raizRepositorio)) {
            throw new RuntimeException('A configuração da Sprint 8.3 deve ficar fora do repositório.');
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
