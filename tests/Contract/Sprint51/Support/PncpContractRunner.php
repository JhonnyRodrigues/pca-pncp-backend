<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint51\Support;

use RuntimeException;
use Throwable;

final class PncpContractRunner
{
    private const OPERATIONAL_ITEM_LIMIT = 1000;

    /** @var array<int, array<string, mixed>> */
    private array $events = [];
    /** @var int[] */
    private array $createdSequences = [];
    private ?string $token = null;
    private int $possibleUnknownResidues = 0;

    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly array $config,
        private readonly PncpHttpClient $client
    ) {
    }

    /** @param array<string, mixed> $config */
    public static function fromConfig(array $config): self
    {
        $pncp = Sprint51Config::pncp($config);

        return new self(
            $pncp,
            new PncpHttpClient(
                (string) $pncp['base_url'],
                (int) $pncp['connect_timeout_seconds'],
                (int) $pncp['read_timeout_seconds'],
                (int) $pncp['write_timeout_seconds'],
                (int) $pncp['max_response_bytes'],
                is_string($pncp['ca_bundle']) ? $pncp['ca_bundle'] : null
            )
        );
    }

    /** @return array<string, mixed> */
    public function run(): array
    {
        $largeBatchEnabled = ($this->config['allow_large_batch'] ?? false) === true;
        $report = [
            'sprint' => '5.1',
            'gate' => 'PNCP_TREINAMENTO',
            'executado_em_utc' => gmdate('c'),
            'runtime' => [
                'php' => PHP_VERSION,
                'sapi' => PHP_SAPI,
                'curl' => curl_version()['version'] ?? 'indisponivel',
                'openssl' => OPENSSL_VERSION_TEXT,
            ],
            'destino_validado' => true,
            'tls_obrigatorio' => true,
            'redirects_desabilitados' => true,
            'ca_bundle_explicito' => ($this->config['ca_bundle'] ?? null) !== null,
            'limite_operacional_por_chamada' => self::OPERATIONAL_ITEM_LIMIT,
            'probe_1001_opt_in' => $largeBatchEnabled,
            'cnpj_alfanumerico' => [
                'status' => 'AINDA NÃO COMPROVADA',
                'bloqueia_sprint_5_1' => false,
                'motivo' => 'Organização disponível possui CNPJ numérico tradicional.',
            ],
        ];

        try {
            $this->authenticate();
            $this->assertNoUnknownTarget();

            $report['fluxo_pequeno'] = $this->capture(fn (): array => $this->runSmallFlow());
            $report['limite_1001'] = $largeBatchEnabled
                ? ['executado' => true] + $this->capture(fn (): array => $this->runLargeBatchProbe())
                : [
                    'executado' => false,
                    'status' => 'AINDA NÃO COMPROVADA',
                    'bloqueia_sprint_5_1' => false,
                    'motivo' => 'Probe opt-in desabilitado; limite operacional conservador mantido em 1.000 itens por chamada.',
                ];
        } catch (Throwable $exception) {
            $report['preflight'] = [
                'confirmado' => false,
                'falha' => $this->safeFailure($exception),
            ];
        } finally {
            $report['cleanup_final'] = $this->cleanupKnownSequences();
        }

        $report['eventos_http'] = $this->events;
        $smallSucceeded = ($report['fluxo_pequeno']['confirmado'] ?? false) === true;
        $limitSucceeded = !$largeBatchEnabled
            || ($report['limite_1001']['confirmado'] ?? false) === true;
        $preflightSucceeded = ($report['preflight']['confirmado'] ?? true) === true;
        $cleanupSucceeded = ($report['cleanup_final']['confirmado'] ?? false) === true;
        $report['confirmado'] = $smallSucceeded && $limitSucceeded && $preflightSucceeded && $cleanupSucceeded;

        return $report;
    }

    private function authenticate(): void
    {
        $response = $this->http(
            'autenticacao',
            'POST',
            '/v1/usuarios/login',
            ExactJsonEncoder::encode([
                'login' => $this->config['login'],
                'senha' => $this->config['senha'],
            ])
        );

        if ($response->status !== 200) {
            throw new RuntimeException("Autenticação não confirmada (HTTP {$response->status}).");
        }

        $authorization = $response->header('authorization');
        if (!is_string($authorization) || !preg_match('/^Bearer\s+(.+)$/iD', $authorization, $matches)) {
            throw new RuntimeException('Autenticação não retornou Authorization no esquema Bearer.');
        }

        $this->token = $matches[1];
        $this->events[] = [
            'etapa' => 'token_opaco',
            'bearer_confirmado' => true,
            'token_registrado' => false,
        ];
    }

    private function assertNoUnknownTarget(): void
    {
        $candidates = $this->listCandidates('preflight_candidatos');
        $matching = [];
        foreach ($candidates as $candidate) {
            if (($candidate['codigoUnidade'] ?? null) === $this->config['codigo_unidade']) {
                $matching[] = $candidate;
            }
        }

        if ($matching === []) {
            return;
        }

        $ownedSequence = $this->config['owned_residue_sequence'] ?? null;
        if (count($matching) !== 1
            || !is_int($ownedSequence)
            || (int) ($matching[0]['sequencialPca'] ?? 0) !== $ownedSequence
            || !$this->isOwnedResidue($ownedSequence)
        ) {
            throw new RuntimeException('Já existe PCA desconhecido para a unidade e o ano; limpeza automática recusada.');
        }

        $this->createdSequences[] = $ownedSequence;
        if (!$this->cleanupSequence($ownedSequence, 'cleanup_residuo_conhecido')) {
            throw new RuntimeException('Resíduo próprio conhecido não pôde ser removido e comprovado ausente.');
        }
    }

    private function isOwnedResidue(int $sequence): bool
    {
        $pageSize = (int) $this->config['page_size'];
        $observed = 0;

        for ($page = 1; $page <= 10000; $page++) {
            $response = $this->http(
                'inspecao_residuo_pagina_' . $page,
                'GET',
                $this->itemsPath($sequence) . '?pagina=' . $page . '&tamanhoPagina=' . $pageSize,
                null,
                $this->requiredToken()
            );
            if ($response->status === 204) {
                break;
            }
            if ($response->status !== 200) {
                return false;
            }

            $items = $response->json();
            if (!is_array($items) || !array_is_list($items) || $items === []) {
                return false;
            }
            foreach ($items as $item) {
                if (!is_array($item)
                    || !is_string($item['descricao'] ?? null)
                    || !str_starts_with($item['descricao'], (string) $this->config['marcador_massa'])
                ) {
                    return false;
                }
                $this->assertObservedContext($item, $sequence);
                $observed++;
            }
            if (count($items) < $pageSize) {
                break;
            }
        }

        return $observed > 0;
    }

    /** @return array<string, mixed> */
    private function runSmallFlow(): array
    {
        $base = (int) $this->config['numero_item_base'];
        $expectedInitial = [$base, $base + 2];
        $inserted = $base + 5;
        $sequence = null;

        try {
            $publish = $this->publish('publicacao_ids_com_lacuna', $expectedInitial, 'GAP');
            $sequence = $this->extractSequence($publish);
            $this->createdSequences[] = $sequence;

            $observedInitial = $this->observeItems($sequence, 1, 'observacao_inicial');
            $this->assertSameIds($expectedInitial, $observedInitial);

            $insert = $this->http(
                'insercao_item_com_lacuna',
                'POST',
                $this->itemsPath($sequence),
                ExactJsonEncoder::encode([$this->item($inserted, 'INSERT')]),
                $this->requiredToken()
            );
            $this->assertAcceptedWrite($insert, 'Inserção');

            $observedAfterInsert = $this->observeItems($sequence, 1, 'observacao_pos_insercao');
            $this->assertSameIds([$base, $base + 2, $inserted], $observedAfterInsert);

            $invalid = $this->http(
                'rejeicao_controlada',
                'POST',
                $this->itemsPath($sequence),
                ExactJsonEncoder::encode([['numeroItem' => $base + 9]]),
                $this->requiredToken()
            );
            if (!in_array($invalid->status, [400, 422], true)) {
                throw new RuntimeException("Requisição inválida não foi rejeitada como 400/422 (HTTP {$invalid->status}).");
            }

            $deleteItems = $this->http(
                'exclusao_em_lote',
                'DELETE',
                $this->itemsPath($sequence),
                ExactJsonEncoder::encode([
                    'justificativa' => 'Limpeza controlada do gate contratual Sprint 5.1.',
                    'listaNumerosItens' => $expectedInitial,
                ]),
                $this->requiredToken()
            );
            $this->assertAcceptedWrite($deleteItems, 'Exclusão em lote');
            $this->assertSameIds([$inserted], $this->observeItems($sequence, 1, 'observacao_pos_exclusao_lote'));

            $this->deletePlan($sequence, 'exclusao_integral');
            $this->assertAbsent($sequence, 'ausencia_final');
            $this->forgetSequence($sequence);

            return [
                'ids_com_lacunas' => true,
                'decimal_exato_enviado' => true,
                'paginacao_completa' => true,
                'rejeicao_400_422' => true,
                'exclusao_em_lote' => true,
                'exclusao_integral' => true,
                'ausencia_definitiva' => true,
            ];
        } finally {
            if ($sequence !== null && in_array($sequence, $this->createdSequences, true)) {
                $this->cleanupSequence($sequence, 'cleanup_fluxo_pequeno');
            }
        }
    }

    /** @return array<string, mixed> */
    private function runLargeBatchProbe(): array
    {
        $start = (int) $this->config['numero_item_base'] + 100;
        $items = [];
        for ($offset = 0; $offset < 1001; $offset++) {
            $items[] = $this->item($start + $offset, 'LARGE');
        }

        $sequence = null;
        try {
            $response = $this->http(
                'publicacao_1001_itens',
                'POST',
                '/v1/orgaos/' . rawurlencode((string) $this->config['cnpj']) . '/pca',
                ExactJsonEncoder::encode([
                    'codigoUnidade' => $this->config['codigo_unidade'],
                    'anoPca' => $this->config['ano'],
                    'itensPlano' => $items,
                ]),
                $this->requiredToken()
            );

            if (in_array($response->status, [400, 422], true)) {
                $this->assertNoUnknownTarget();
                return [
                    'confirmado' => true,
                    'resultado' => 'REJEITADA',
                    'quantidade_enviada' => 1001,
                    'estado_criado' => false,
                ];
            }

            if ($response->status < 200 || $response->status >= 300) {
                $this->possibleUnknownResidues++;
            }
            $this->assertAcceptedWrite($response, 'Publicação de 1.001 itens');
            $sequence = $this->extractSequence($response);
            $this->createdSequences[] = $sequence;
            $observed = $this->observeItems(
                $sequence,
                (int) $this->config['page_size'],
                'observacao_1001_itens'
            );
            if (count($observed) !== 1001 || count(array_unique($observed)) !== 1001) {
                throw new RuntimeException('Leitura após aceitação não recuperou exatamente 1.001 itens únicos.');
            }

            $this->deletePlan($sequence, 'cleanup_publicacao_1001');
            $this->assertAbsent($sequence, 'ausencia_pos_1001');
            $this->forgetSequence($sequence);

            return [
                'confirmado' => true,
                'resultado' => 'ACEITA',
                'quantidade_enviada' => 1001,
                'quantidade_observada' => 1001,
                'cleanup_confirmado' => true,
            ];
        } finally {
            if ($sequence !== null && in_array($sequence, $this->createdSequences, true)) {
                $this->cleanupSequence($sequence, 'cleanup_emergencial_1001');
            }
        }
    }

    /** @param int[] $itemNumbers */
    private function publish(string $label, array $itemNumbers, string $suffix): PncpHttpResponse
    {
        $items = array_map(fn (int $number): array => $this->item($number, $suffix), $itemNumbers);
        $response = $this->http(
            $label,
            'POST',
            '/v1/orgaos/' . rawurlencode((string) $this->config['cnpj']) . '/pca',
            ExactJsonEncoder::encode([
                'codigoUnidade' => $this->config['codigo_unidade'],
                'anoPca' => $this->config['ano'],
                'itensPlano' => $items,
            ]),
            $this->requiredToken()
        );
        if ($response->status < 200 || $response->status >= 300) {
            if (!in_array($response->status, [400, 422], true)) {
                $this->possibleUnknownResidues++;
            }
        }
        $this->assertAcceptedWrite($response, 'Publicação');

        return $response;
    }

    /** @return array<string, mixed> */
    private function item(int $number, string $suffix): array
    {
        /** @var array<string, mixed> $template */
        $template = $this->config['item_template'];

        return [
            'numeroItem' => $number,
            'categoriaItemPca' => $template['categoriaItemPca'],
            'descricao' => $this->config['marcador_massa'] . $suffix . '-' . $number,
            'unidadeFornecimento' => $template['unidadeFornecimento'],
            'quantidade' => new ExactJsonNumber('6.0000'),
            'valorUnitario' => new ExactJsonNumber('0.1667'),
            'valorTotal' => new ExactJsonNumber('1.0000'),
            'valorOrcamentoExercicio' => new ExactJsonNumber('1.0000'),
            'unidadeRequisitante' => $template['unidadeRequisitante'] ?? null,
            'dataDesejada' => $this->config['data_desejada'],
            'grupoContratacaoCodigo' => $template['grupoContratacaoCodigo'] ?? null,
            'grupoContratacaoNome' => $template['grupoContratacaoNome'] ?? null,
            'catalogo' => $template['catalogo'],
            'classificacaoCatalogo' => $template['classificacaoCatalogo'],
            'codigoItem' => $template['codigoItem'] ?? null,
            'classificacaoSuperiorCodigo' => $template['classificacaoSuperiorCodigo'],
            'classificacaoSuperiorNome' => $template['classificacaoSuperiorNome'],
            'pdmCodigo' => $template['pdmCodigo'] ?? null,
            'pdmDescricao' => $template['pdmDescricao'] ?? null,
        ];
    }

    /** @return int[] */
    private function observeItems(int $sequence, int $pageSize, string $label): array
    {
        $all = [];
        $pageFingerprints = [];

        for ($page = 1; $page <= 10000; $page++) {
            $response = $this->http(
                $label . '_pagina_' . $page,
                'GET',
                $this->itemsPath($sequence) . '?pagina=' . $page . '&tamanhoPagina=' . $pageSize,
                null,
                $this->requiredToken()
            );

            if ($response->status === 204) {
                break;
            }
            if ($response->status !== 200) {
                throw new RuntimeException("Leitura paginada falhou (HTTP {$response->status}).");
            }

            $items = $response->json();
            if (!is_array($items) || !array_is_list($items)) {
                throw new RuntimeException('Página de itens não retornou uma lista JSON.');
            }

            $pageIds = [];
            foreach ($items as $item) {
                if (!is_array($item) || !is_int($item['numeroItem'] ?? null)) {
                    throw new RuntimeException('Item observado não possui numeroItem inteiro.');
                }
                $this->assertObservedContext($item, $sequence);
                $id = $item['numeroItem'];
                if (isset($all[$id])) {
                    throw new RuntimeException('Leitura paginada retornou numeroItem duplicado.');
                }
                $all[$id] = true;
                $pageIds[] = $id;
            }

            $fingerprint = hash('sha256', implode(',', $pageIds));
            if (isset($pageFingerprints[$fingerprint]) && $pageIds !== []) {
                throw new RuntimeException('Leitura paginada repetiu uma página.');
            }
            $pageFingerprints[$fingerprint] = true;

            if (count($items) < $pageSize) {
                break;
            }
        }

        return array_map('intval', array_keys($all));
    }

    /** @param array<string, mixed> $item */
    private function assertObservedContext(array $item, int $sequence): void
    {
        $expected = [
            'cnpj' => (string) $this->config['cnpj'],
            'codigoUnidade' => (string) $this->config['codigo_unidade'],
            'anoPca' => (int) $this->config['ano'],
            'sequencialPca' => $sequence,
        ];
        foreach ($expected as $field => $value) {
            if (array_key_exists($field, $item) && (string) $item[$field] !== (string) $value) {
                throw new RuntimeException("Contexto divergente no campo {$field}.");
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function listCandidates(string $label): array
    {
        $all = [];
        $pageSize = (int) $this->config['page_size'];
        for ($page = 1; $page <= 10000; $page++) {
            $path = '/v1/orgaos/' . rawurlencode((string) $this->config['cnpj'])
                . '/pca/' . (int) $this->config['ano'] . '/consolidado/unidades'
                . '?pagina=' . $page . '&tamanhoPagina=' . $pageSize;
            $response = $this->http($label . '_pagina_' . $page, 'GET', $path, null, $this->requiredToken());
            if ($response->status === 204) {
                break;
            }
            if ($response->status !== 200) {
                throw new RuntimeException("Consulta de candidatos falhou (HTTP {$response->status}).");
            }

            $candidates = $response->json();
            if (!is_array($candidates) || !array_is_list($candidates)) {
                throw new RuntimeException('Consulta de candidatos não retornou lista JSON.');
            }
            foreach ($candidates as $candidate) {
                if (!is_array($candidate)) {
                    throw new RuntimeException('Candidato PNCP possui forma inválida.');
                }
                $all[] = $candidate;
            }
            if (count($candidates) < $pageSize) {
                break;
            }
        }

        return $all;
    }

    private function assertAbsent(int $sequence, string $label): void
    {
        $known = $this->http(
            $label . '_sequencial',
            'GET',
            $this->itemsPath($sequence) . '?pagina=1&tamanhoPagina=1',
            null,
            $this->requiredToken()
        );
        $knownAbsent = $known->status === 204
            || ($known->status === 200 && $known->json() === []);
        if (!$knownAbsent) {
            throw new RuntimeException("Sequencial excluído ainda foi observado (HTTP {$known->status}).");
        }

        foreach ($this->listCandidates($label . '_candidatos') as $candidate) {
            if (($candidate['codigoUnidade'] ?? null) === $this->config['codigo_unidade']) {
                throw new RuntimeException('Unidade excluída continua presente na listagem de candidatos.');
            }
        }
    }

    private function deletePlan(int $sequence, string $label): void
    {
        $response = $this->http(
            $label,
            'DELETE',
            '/v1/orgaos/' . rawurlencode((string) $this->config['cnpj'])
                . '/pca/' . (int) $this->config['ano'] . '/' . $sequence,
            ExactJsonEncoder::encode([
                'justificativa' => 'Limpeza controlada do gate contratual Sprint 5.1.',
            ]),
            $this->requiredToken()
        );
        $this->assertAcceptedWrite($response, 'Exclusão integral');
    }

    private function extractSequence(PncpHttpResponse $response): int
    {
        $location = $response->header('location');
        $year = preg_quote((string) $this->config['ano'], '~');
        if (is_string($location) && preg_match("~/pca/{$year}/([1-9][0-9]*)(?:/|$|\\?)~", $location, $matches)) {
            return (int) $matches[1];
        }

        $body = $response->json();
        if (is_array($body)) {
            foreach (['sequencialPlano', 'sequencialPca'] as $field) {
                if (is_int($body[$field] ?? null) && $body[$field] > 0) {
                    return $body[$field];
                }
            }
        }

        $this->possibleUnknownResidues++;
        throw new RuntimeException('Escrita aceita sem sequencial recuperável; resultado incerto e sem cleanup automático.');
    }

    private function assertAcceptedWrite(PncpHttpResponse $response, string $operation): void
    {
        if ($response->status < 200 || $response->status >= 300) {
            throw new RuntimeException("{$operation} não foi aceita (HTTP {$response->status}).");
        }
    }

    /** @param int[] $expected @param int[] $actual */
    private function assertSameIds(array $expected, array $actual): void
    {
        sort($expected);
        sort($actual);
        if ($expected !== $actual) {
            throw new RuntimeException('Conjunto observado de numeroItem diverge do conjunto esperado.');
        }
    }

    private function itemsPath(int $sequence): string
    {
        return '/v1/orgaos/' . rawurlencode((string) $this->config['cnpj'])
            . '/pca/' . (int) $this->config['ano'] . '/' . $sequence . '/itens';
    }

    private function requiredToken(): string
    {
        if ($this->token === null || $this->token === '') {
            throw new RuntimeException('Sessão PNCP não autenticada.');
        }
        return $this->token;
    }

    private function http(
        string $label,
        string $method,
        string $path,
        ?string $body = null,
        ?string $token = null
    ): PncpHttpResponse {
        try {
            $this->assertOperationalItemLimit($label, $method, $body);
            $response = $this->client->request($method, $path, $body, $token);
            $this->events[] = ['etapa' => $label, 'metodo' => $method] + $response->sanitizedSummary();
            if ($response->sslVerifyResult !== 0) {
                throw new RuntimeException('Verificação TLS não foi confirmada pelo cURL.');
            }
            return $response;
        } catch (Throwable $exception) {
            $this->events[] = [
                'etapa' => $label,
                'metodo' => $method,
                'falha_transporte' => $this->safeFailure($exception),
            ];
            throw $exception;
        }
    }

    private function assertOperationalItemLimit(string $label, string $method, ?string $body): void
    {
        if ($body === null || !in_array($method, ['POST', 'DELETE'], true)) {
            return;
        }

        $payload = json_decode($body, true);
        if (!is_array($payload)) {
            return;
        }

        $items = null;
        if (isset($payload['itensPlano']) && is_array($payload['itensPlano'])) {
            $items = $payload['itensPlano'];
        } elseif (isset($payload['listaNumerosItens']) && is_array($payload['listaNumerosItens'])) {
            $items = $payload['listaNumerosItens'];
        } elseif (array_is_list($payload)) {
            $items = $payload;
        }

        if ($items === null || count($items) <= self::OPERATIONAL_ITEM_LIMIT) {
            return;
        }

        $largeBatchAllowed = ($this->config['allow_large_batch'] ?? false) === true
            && $label === 'publicacao_1001_itens';
        if (!$largeBatchAllowed) {
            throw new RuntimeException('Fluxo PNCP recusou chamada acima do limite operacional de 1.000 itens.');
        }
    }

    /** @return array<string, mixed> */
    private function capture(callable $operation): array
    {
        try {
            return ['confirmado' => true] + $operation();
        } catch (Throwable $exception) {
            return [
                'confirmado' => false,
                'falha' => $this->safeFailure($exception),
            ];
        }
    }

    /** @return array<string, mixed> */
    private function cleanupKnownSequences(): array
    {
        $failed = [];
        foreach (array_values(array_unique($this->createdSequences)) as $sequence) {
            if (!$this->cleanupSequence($sequence, 'cleanup_final')) {
                $failed[] = 'sequencial_criado_na_execucao';
            }
        }

        $possibleResidues = count($failed) + $this->possibleUnknownResidues;
        return [
            'confirmado' => $possibleResidues === 0,
            'residuos_possiveis' => $possibleResidues,
        ];
    }

    private function cleanupSequence(int $sequence, string $label): bool
    {
        try {
            $this->deletePlan($sequence, $label);
            $this->assertAbsent($sequence, $label . '_ausencia');
            $this->forgetSequence($sequence);
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function forgetSequence(int $sequence): void
    {
        $this->createdSequences = array_values(array_filter(
            $this->createdSequences,
            static fn (int $known): bool => $known !== $sequence
        ));
    }

    /** @return array{tipo: string, mensagem: string} */
    private function safeFailure(Throwable $exception): array
    {
        return [
            'tipo' => $exception::class,
            'mensagem' => $exception->getMessage(),
        ];
    }
}
