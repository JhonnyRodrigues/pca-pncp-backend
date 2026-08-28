<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint59\Support;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use RuntimeException;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Semae\Pca\Infrastructure\Oracle\Auditoria\MapeamentoHistoricosOracle;
use Semae\Pca\Infrastructure\Oracle\Contexto\MapeamentoLotacoesUsuarioOracle;
use Semae\Pca\Infrastructure\Oracle\Contexto\MapeamentoRepresentantesOracle;
use Semae\Pca\Infrastructure\Oracle\EstadoOperacional\MapeamentoEstadoOperacionalOracle;
use Semae\Pca\Infrastructure\Oracle\Estrategia\MapeamentoConfiguracaoEstrategiaOracle;
use Semae\Pca\Infrastructure\Oracle\Estrategia\MapeamentoPlanosEstrategiaOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoDemandasOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoLotacoesOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoPlanosOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoUnidadesOrcamentariasOracle;
use Semae\Pca\Infrastructure\Oracle\Transacao\MapeamentoControlesConcorrenciaOracle;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Semae\Pca\Infrastructure\Pncp\Http\ConfiguracaoTransportePncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\ConfiguracaoLeituraPncp;

final class Sprint59Config
{
    /** @return array<string, mixed> */
    public static function loadFromEnvironment(): array
    {
        $configuredPath = getenv('PCA_SPRINT59_CONFIG_FILE');

        if (!is_string($configuredPath) || trim($configuredPath) === '') {
            throw new RuntimeException('PCA_SPRINT59_CONFIG_FILE nao foi informado.');
        }

        $path = realpath($configuredPath);
        if ($path === false || !is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Arquivo externo da Sprint 5.9 nao esta legivel.');
        }

        $repositoryRoot = realpath(dirname(__DIR__, 4));
        if ($repositoryRoot === false || self::isWithin($path, $repositoryRoot)) {
            throw new RuntimeException('A configuracao da Sprint 5.9 deve ficar fora do repositorio.');
        }

        $config = require $path;
        if (!is_array($config)) {
            throw new RuntimeException('O arquivo externo da Sprint 5.9 deve retornar um array.');
        }

        return $config;
    }

    /** @param array<string, mixed> $config @return array<string, mixed> */
    public static function pncp(array $config): array
    {
        $pncp = self::section($config, 'pncp');

        if (($pncp['allow_writes'] ?? null) !== true) {
            throw new InvalidArgumentException('allow_writes=true e obrigatorio para o gate PNCP.');
        }
        if (array_key_exists('base_url', $pncp) || ($pncp['ambiente'] ?? null) !== 'HOMOLOGACAO') {
            throw new InvalidArgumentException('O gate aceita apenas o ambiente PNCP HOMOLOGACAO canonico.');
        }
        if (array_key_exists('allow_large_batch', $pncp)) {
            throw new InvalidArgumentException('A Sprint 5.9 nao permite opt-in para lote de 1.001 itens.');
        }

        foreach (['login', 'senha', 'cnpj', 'codigo_uasg', 'descricao_uasg', 'data_desejada'] as $field) {
            self::nonEmptyString($pncp, $field);
        }
        if (preg_match('/^[0-9]{14}$/D', (string) $pncp['cnpj']) !== 1) {
            throw new InvalidArgumentException('O CNPJ do gate deve possuir 14 digitos.');
        }

        $year = self::positiveInt($pncp, 'ano');
        $currentYear = (int) date('Y');
        if (!in_array($year, [$currentYear, $currentYear + 1], true)) {
            throw new InvalidArgumentException('O ano do gate deve ser o corrente ou o seguinte.');
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $pncp['data_desejada']);
        if (
            $date === false
            || $date->format('Y-m-d') !== $pncp['data_desejada']
            || (int) $date->format('Y') !== $year
        ) {
            throw new InvalidArgumentException('data_desejada deve ser valida e pertencer ao ano do gate.');
        }

        $ids = [];
        foreach (['id_demanda_publicacao_1', 'id_demanda_publicacao_2', 'id_demanda_inclusao'] as $field) {
            $ids[] = self::positiveInt($pncp, $field);
        }
        if (
            count(array_unique($ids)) !== 3
            || ($ids[1] - $ids[0]) === 1
            || ($ids[2] - $ids[1]) === 1
        ) {
            throw new InvalidArgumentException('Os IDs de Demanda devem ser distintos e lacunados.');
        }

        $positiveFields = [
            'limite_payload_bytes',
            'limite_resposta_bytes',
            'timeout_conexao',
            'timeout_login',
            'timeout_leitura',
            'timeout_escrita',
            'page_size',
            'max_pages',
        ];
        foreach ($positiveFields as $field) {
            self::positiveInt($pncp, $field);
        }
        if ($pncp['page_size'] > 1000) {
            throw new InvalidArgumentException('page_size nao pode exceder 1000.');
        }

        $pncp['ca_bundle'] = self::optionalReadableAbsoluteFile($pncp, 'ca_bundle');
        $residue = $pncp['owned_residue_sequence'] ?? null;
        if ($residue !== null && (!is_int($residue) || $residue < 1)) {
            throw new InvalidArgumentException('owned_residue_sequence deve ser inteiro positivo ou null.');
        }
        $template = $pncp['item_template'] ?? null;
        if (!is_array($template)) {
            throw new InvalidArgumentException('item_template e obrigatorio.');
        }
        $category = self::positiveInt($template, 'categoria');
        $catalog = self::positiveInt($template, 'catalogo');
        if (!in_array($category, [1, 2], true) || !in_array($catalog, [1, 2], true)) {
            throw new InvalidArgumentException('Categoria e catalogo devem usar literais suportados.');
        }
        foreach (['unidade_medida', 'codigo_item', 'grupo', 'descricao_grupo', 'origem'] as $field) {
            self::nonEmptyString($template, $field);
        }

        try {
            new Cnpj($pncp['cnpj']);
            new AnoExecucao($pncp['ano']);
            new Uasg($pncp['codigo_uasg'], $pncp['descricao_uasg']);
            new ConfiguracaoTransportePncp(
                AmbientePncp::HOMOLOGACAO,
                $pncp['limite_payload_bytes'],
                $pncp['limite_resposta_bytes'],
                $pncp['timeout_conexao'],
                $pncp['timeout_login'],
                $pncp['timeout_leitura'],
                $pncp['timeout_escrita'],
                $pncp['ca_bundle']
            );
            new ConfiguracaoLeituraPncp($pncp['page_size'], 64, $pncp['max_pages'], 250, 5000);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException(
                'A configuracao PNCP da Sprint 5.9 e invalida.',
                0,
                $exception
            );
        }

        return $pncp;
    }

    /** @param array<string, mixed> $config @return array<string, mixed> */
    public static function oracle(array $config): array
    {
        $oracle = self::section($config, 'oracle');
        if (($oracle['allow_writes'] ?? null) !== true) {
            throw new InvalidArgumentException('allow_writes=true e obrigatorio para o gate Oracle.');
        }

        foreach (['timezone', 'mensagem_auditoria'] as $field) {
            self::nonEmptyString($oracle, $field);
        }
        self::positiveInt($oracle, 'ano');
        self::positiveInt($oracle, 'id_unidade_orcamentaria');
        self::positiveInt($oracle, 'sequencial_teste');
        $hold = self::positiveInt($oracle, 'lock_hold_seconds');
        if ($hold > 60) {
            throw new InvalidArgumentException('lock_hold_seconds nao pode exceder 60.');
        }

        $mappings = $oracle['mappings'] ?? null;
        if (is_array($mappings) && array_key_exists('ancoras', $mappings)) {
            throw new InvalidArgumentException(
                'O mapeamento legado ancoras nao e aceito; use controles_concorrencia.'
            );
        }
        $required = [
            'controles_concorrencia',
            'configuracao_estrategia',
            'planos_estrategia',
            'planos',
            'demandas',
            'unidades_orcamentarias',
            'lotacoes',
            'estado_operacional',
            'representantes',
            'lotacoes_usuario',
            'historicos',
        ];
        if (!is_array($mappings)) {
            throw new InvalidArgumentException('oracle.mappings e obrigatorio.');
        }
        foreach ($required as $name) {
            if (!is_array($mappings[$name] ?? null) || $mappings[$name] === []) {
                throw new InvalidArgumentException("Mapeamento Oracle {$name} nao foi configurado.");
            }
        }

        try {
            new DateTimeZone($oracle['timezone']);
            new MapeamentoControlesConcorrenciaOracle(
                ...$mappings['controles_concorrencia']
            );
            new MapeamentoConfiguracaoEstrategiaOracle(...$mappings['configuracao_estrategia']);
            new MapeamentoPlanosEstrategiaOracle(...$mappings['planos_estrategia']);
            new MapeamentoPlanosOracle(...$mappings['planos']);
            new MapeamentoDemandasOracle(...$mappings['demandas']);
            new MapeamentoUnidadesOrcamentariasOracle(...$mappings['unidades_orcamentarias']);
            new MapeamentoLotacoesOracle(...$mappings['lotacoes']);
            new MapeamentoEstadoOperacionalOracle(...$mappings['estado_operacional']);
            new MapeamentoRepresentantesOracle(...$mappings['representantes']);
            new MapeamentoLotacoesUsuarioOracle(...$mappings['lotacoes_usuario']);
            new MapeamentoHistoricosOracle(...$mappings['historicos']);
        } catch (InvalidArgumentException | \Error $exception) {
            throw new InvalidArgumentException(
                'Um mapeamento Oracle da Sprint 5.9 e invalido.',
                0,
                $exception
            );
        }

        return $oracle;
    }

    /** @param array<string, mixed> $config @return array<string, mixed> */
    private static function section(array $config, string $name): array
    {
        $section = $config[$name] ?? null;
        if (!is_array($section)) {
            throw new InvalidArgumentException("Secao {$name} nao foi configurada.");
        }
        return $section;
    }

    /** @param array<string, mixed> $values */
    private static function nonEmptyString(array $values, string $field): string
    {
        $value = $values[$field] ?? null;
        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("{$field} deve ser texto nao vazio.");
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
        $path = $values[$field];
        if (!is_string($path) || preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\\/]{2}|\/)/D', $path) !== 1) {
            throw new InvalidArgumentException("{$field} deve usar caminho absoluto.");
        }
        $resolved = realpath($path);
        if ($resolved === false || !is_file($resolved) || !is_readable($resolved)) {
            throw new InvalidArgumentException("{$field} deve apontar para arquivo legivel.");
        }
        return $resolved;
    }

    private static function isWithin(string $path, string $directory): bool
    {
        $path = str_replace('\\', '/', strtolower($path));
        $directory = rtrim(str_replace('\\', '/', strtolower($directory)), '/');
        return $path === $directory || str_starts_with($path, $directory . '/');
    }
}
