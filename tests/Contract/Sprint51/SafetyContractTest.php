<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint51;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Contract\Sprint51\Support\ExactJsonEncoder;
use Tests\Contract\Sprint51\Support\ExactJsonNumber;
use Tests\Contract\Sprint51\Support\OracleScriptcaseProbe;
use Tests\Contract\Sprint51\Support\PncpContractRunner;
use Tests\Contract\Sprint51\Support\PncpHttpClient;
use Tests\Contract\Sprint51\Support\PncpHttpResponse;
use Tests\Contract\Sprint51\Support\Sprint51Config;

final class SafetyContractTest extends TestCase
{
    public function testEncoderDevePreservarDecimalExatoSemAspas(): void
    {
        $json = ExactJsonEncoder::encode([
            'valorUnitario' => new ExactJsonNumber('0.1667'),
            'valorTotal' => new ExactJsonNumber('1.0000'),
        ]);

        $this->assertSame('{"valorUnitario":0.1667,"valorTotal":1.0000}', $json);
    }

    public function testEncoderDeveRecusarFloat(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ExactJsonEncoder::encode(['valor' => 0.1]);
    }

    #[DataProvider('urlsProibidas')]
    public function testClienteDeveRecusarQualquerDestinoDiferenteDoTreinamento(string $url): void
    {
        $this->expectException(RuntimeException::class);

        new PncpHttpClient($url, 5, 30, 60, 1024);
    }

    /** @return array<string, array{string}> */
    public static function urlsProibidas(): array
    {
        return [
            'produção' => ['https://pncp.gov.br/api/pncp'],
            'subdomínio enganoso' => ['https://treina.pncp.gov.br.example/api/pncp'],
            'HTTP' => ['http://treina.pncp.gov.br/api/pncp'],
            'barra adicional' => ['https://treina.pncp.gov.br/api/pncp/'],
        ];
    }

    public function testPreflightPncpDeveAceitarCaBundleAusente(): void
    {
        $config = $this->validPncpConfig();
        unset($config['ca_bundle']);

        $validated = Sprint51Config::pncp(['pncp' => $config]);

        $this->assertNull($validated['ca_bundle']);
    }

    #[DataProvider('caBundlesInvalidos')]
    public function testPreflightPncpDeveRecusarCaBundleInvalido(mixed $caBundle): void
    {
        $config = $this->validPncpConfig();
        $config['ca_bundle'] = $caBundle;

        $this->expectException(InvalidArgumentException::class);
        Sprint51Config::pncp(['pncp' => $config]);
    }

    /** @return array<string, array{mixed}> */
    public static function caBundlesInvalidos(): array
    {
        return [
            'não textual' => [123],
            'vazio' => [''],
            'relativo' => ['certificados/ca-bundle.crt'],
            'inexistente' => [__FILE__ . '.inexistente'],
            'diretório' => [__DIR__],
        ];
    }

    public function testRunnerPncpDeveUsarCaBundleSemExpoLoNoRelatorio(): void
    {
        $config = $this->validPncpConfig();
        $config['ca_bundle'] = __FILE__;
        $validated = Sprint51Config::pncp(['pncp' => $config]);
        $expectedPath = realpath(__FILE__);
        $this->assertIsString($expectedPath);
        $this->assertSame($expectedPath, $validated['ca_bundle']);

        $runner = PncpContractRunner::fromConfig(['pncp' => $config]);
        $clientProperty = new \ReflectionProperty($runner, 'client');
        $clientProperty->setAccessible(true);
        $client = $clientProperty->getValue($runner);
        $optionsMethod = new \ReflectionMethod($client, 'securityOptions');
        $optionsMethod->setAccessible(true);
        $options = $optionsMethod->invoke($client);

        $this->assertSame($expectedPath, $options[CURLOPT_CAINFO]);
        $this->assertTrue($options[CURLOPT_SSL_VERIFYPEER]);
        $this->assertSame(2, $options[CURLOPT_SSL_VERIFYHOST]);
        $this->assertFalse($options[CURLOPT_FOLLOWLOCATION]);
        $this->assertSame(CURLPROTO_HTTPS, $options[CURLOPT_PROTOCOLS]);

        $report = (new PncpContractRunner(
            $validated,
            new SimulatedPncpHttpClient($validated)
        ))->run();
        $publicReport = json_encode($report, JSON_THROW_ON_ERROR);

        $this->assertTrue($report['ca_bundle_explicito']);
        $this->assertStringNotContainsString($expectedPath, $publicReport);
    }

    public function testPreflightPncpDeveAceitarConfiguracaoCompletaComLoteGrandeDesabilitado(): void
    {
        $validated = Sprint51Config::pncp(['pncp' => $this->validPncpConfig()]);

        $this->assertSame(Sprint51Config::trainingBaseUrl(), $validated['base_url']);
        $this->assertTrue($validated['allow_writes']);
        $this->assertFalse($validated['allow_large_batch']);
    }

    public function testPreflightPncpDeveDesabilitarLoteGrandeQuandoOpcaoForOmitida(): void
    {
        $config = $this->validPncpConfig();
        unset($config['allow_large_batch']);

        $validated = Sprint51Config::pncp(['pncp' => $config]);

        $this->assertFalse($validated['allow_large_batch']);
    }

    public function testPreflightPncpDeveRecusarOptInDeLoteGrandeNaoBooleano(): void
    {
        $config = $this->validPncpConfig();
        $config['allow_large_batch'] = 'true';

        $this->expectException(InvalidArgumentException::class);
        Sprint51Config::pncp(['pncp' => $config]);
    }

    public function testPreflightPncpDeveFalharFechadoSemConsentimentoDeEscrita(): void
    {
        $config = $this->validPncpConfig();
        $config['allow_writes'] = false;

        $this->expectException(InvalidArgumentException::class);
        Sprint51Config::pncp(['pncp' => $config]);
    }

    public function testPreflightOracleDeveRecusarConsultaAgregadaMutavel(): void
    {
        $config = $this->validOracleConfig();
        $config['aggregate_checks'][0]['sql'] = 'DELETE FROM PCA_S51_FIXTURE';

        $this->expectException(InvalidArgumentException::class);
        Sprint51Config::oracle(['oracle' => $config]);
    }

    public function testProbeOracleNaoDeveOferecerFechamentoDaConexaoEmprestada(): void
    {
        $this->assertFalse(method_exists(OracleScriptcaseProbe::class, 'Close'));
        $this->assertFalse(method_exists(OracleScriptcaseProbe::class, 'close'));
    }

    public function testProbeOracleDeveConverterPlaceholderParaBindNomeadoDoDriver(): void
    {
        $db = new RecordingOracleAdodb();
        $probe = new OracleScriptcaseProbe($db, $this->validOracleConfig());
        $method = new \ReflectionMethod($probe, 'getOne');
        $method->setAccessible(true);

        $value = $method->invoke(
            $probe,
            'SELECT ? FROM DUAL',
            ['SPRINT51_BIND'],
            'teste_bind'
        );

        $this->assertSame('SPRINT51_BIND', $value);
        $this->assertSame('SELECT :s51_p0 FROM DUAL', $db->lastSql);
        $this->assertSame(['s51_p0' => 'SPRINT51_BIND'], $db->lastParams);
    }

    public function testFluxoPncpMinimoDeveOmitirProbeDe1001ItensPorPadrao(): void
    {
        $config = $this->validPncpConfig();
        $client = new SimulatedPncpHttpClient($config);

        $report = (new PncpContractRunner($config, $client))->run();

        $this->assertTrue($report['confirmado']);
        $this->assertSame(1000, $report['limite_operacional_por_chamada']);
        $this->assertFalse($report['probe_1001_opt_in']);
        $this->assertFalse($report['limite_1001']['executado']);
        $this->assertSame('AINDA NÃO COMPROVADA', $report['limite_1001']['status']);
        $this->assertNotContains('publicacao_1001_itens', $client->requestedSteps());
        $this->assertSame([], $client->remainingSequences());
    }

    public function testProbeDe1001ItensDeveExecutarSomenteComOptInExplicito(): void
    {
        $config = $this->validPncpConfig();
        $config['allow_large_batch'] = true;
        $client = new SimulatedPncpHttpClient($config);

        $report = (new PncpContractRunner($config, $client))->run();

        $this->assertTrue($report['confirmado']);
        $this->assertTrue($report['probe_1001_opt_in']);
        $this->assertTrue($report['limite_1001']['executado']);
        $this->assertSame('ACEITA', $report['limite_1001']['resultado']);
        $this->assertSame(1001, $report['limite_1001']['quantidade_observada']);
        $this->assertContains('publicacao_1001_itens', $client->requestedSteps());
        $this->assertSame([], $client->remainingSequences());
    }

    public function testFluxoPncpDeveRecusarResiduoDesconhecidoSemExcluiLo(): void
    {
        $config = $this->validPncpConfig();
        $client = new SimulatedPncpHttpClient($config);
        $client->seedPlan(77, 'OUTRA-MASSA-');

        $report = (new PncpContractRunner($config, $client))->run();

        $this->assertFalse($report['confirmado']);
        $this->assertSame([77], $client->remainingSequences());
    }

    public function testFluxoPncpDeveLimparSomenteResiduoExplicitamenteReconhecido(): void
    {
        $config = $this->validPncpConfig();
        $config['owned_residue_sequence'] = 77;
        $client = new SimulatedPncpHttpClient($config);
        $client->seedPlan(77, $config['marcador_massa']);

        $report = (new PncpContractRunner($config, $client))->run();

        $this->assertTrue($report['confirmado']);
        $this->assertSame([], $client->remainingSequences());
    }

    /** @return array<string, mixed> */
    private function validPncpConfig(): array
    {
        return [
            'base_url' => Sprint51Config::trainingBaseUrl(),
            'ca_bundle' => null,
            'login' => 'usuario-secreto',
            'senha' => 'senha-secreta',
            'cnpj' => '12345678000195',
            'codigo_unidade' => '986789',
            'ano' => 2027,
            'data_desejada' => '2027-12-31',
            'numero_item_base' => 10000,
            'marcador_massa' => 'PCA-S51-TESTE-',
            'owned_residue_sequence' => null,
            'allow_writes' => true,
            'allow_large_batch' => false,
            'connect_timeout_seconds' => 5,
            'read_timeout_seconds' => 30,
            'write_timeout_seconds' => 60,
            'max_response_bytes' => 8388608,
            'page_size' => 100,
            'item_template' => [
                'categoriaItemPca' => 1,
                'unidadeFornecimento' => 'UNIDADE',
                'unidadeRequisitante' => 'UNIDADE',
                'grupoContratacaoCodigo' => 'G1',
                'grupoContratacaoNome' => 'Grupo de teste',
                'catalogo' => 1,
                'classificacaoCatalogo' => '1',
                'codigoItem' => '1',
                'classificacaoSuperiorCodigo' => '1',
                'classificacaoSuperiorNome' => 'Classe de teste',
                'pdmCodigo' => null,
                'pdmDescricao' => null,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function validOracleConfig(): array
    {
        return [
            'schema_owner' => 'PCA_TESTE',
            'tables' => ['PCA_S51_FIXTURE'],
            'fixture' => [
                'table' => 'PCA_S51_FIXTURE',
                'key_column' => 'ID',
                'key_value' => 1,
                'value_column' => 'MARCADOR',
                'probe_value' => 'SPRINT51_PROBE',
            ],
            'lock' => [
                'table' => 'PCA_S51_FIXTURE',
                'key_column' => 'ID',
                'key_value' => 1,
                'hold_seconds' => 30,
            ],
            'aggregate_checks' => [
                [
                    'label' => 'fixture_existe',
                    'sql' => 'SELECT COUNT(*) FROM PCA_S51_FIXTURE',
                    'params' => [],
                    'expect' => ['operator' => 'eq', 'value' => 1],
                ],
            ],
        ];
    }
}

final class SimulatedPncpHttpClient extends PncpHttpClient
{
    /** @var array<int, array<int, array<string, mixed>>> */
    private array $plans = [];
    /** @var string[] */
    private array $requestedSteps = [];

    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config)
    {
        parent::__construct(Sprint51Config::trainingBaseUrl(), 5, 30, 60, 8388608);
    }

    public function request(
        string $method,
        string $path,
        ?string $body = null,
        ?string $bearerToken = null
    ): PncpHttpResponse {
        if ($body !== null) {
            $payload = json_decode($body, true);
            if (is_array($payload)
                && isset($payload['itensPlano'])
                && is_array($payload['itensPlano'])
                && count($payload['itensPlano']) === 1001
            ) {
                $this->requestedSteps[] = 'publicacao_1001_itens';
            }
        }

        if ($path === '/v1/usuarios/login' && $method === 'POST') {
            return $this->response(200, '', ['authorization' => ['Bearer TOKEN_NAO_REAL']]);
        }

        if (str_contains($path, '/consolidado/unidades')) {
            $candidates = [];
            foreach (array_keys($this->plans) as $sequence) {
                $candidates[] = [
                    'codigoUnidade' => $this->config['codigo_unidade'],
                    'sequencialPca' => $sequence,
                ];
            }
            return $this->response(200, json_encode($candidates, JSON_THROW_ON_ERROR));
        }

        if ($method === 'POST' && preg_match('~/pca$~', $path)) {
            $payload = json_decode((string) $body, true, 64, JSON_THROW_ON_ERROR);
            $items = $payload['itensPlano'];
            $sequence = count($items) === 1001 ? 42 : 41;
            $this->plans[$sequence] = $items;
            return $this->response(
                201,
                '',
                ['location' => ["/v1/orgaos/00000000000000/pca/{$this->config['ano']}/{$sequence}"]]
            );
        }

        if (preg_match('~/pca/[0-9]+/([0-9]+)/itens(?:\?.*)?$~', $path, $matches)) {
            $sequence = (int) $matches[1];

            if ($method === 'GET') {
                if (!isset($this->plans[$sequence])) {
                    return $this->response(204);
                }
                parse_str((string) parse_url($path, PHP_URL_QUERY), $query);
                $page = (int) ($query['pagina'] ?? 1);
                $size = (int) ($query['tamanhoPagina'] ?? 100);
                $slice = array_slice($this->plans[$sequence], ($page - 1) * $size, $size);
                if ($slice === []) {
                    return $this->response(204);
                }
                return $this->response(200, json_encode($slice, JSON_THROW_ON_ERROR));
            }

            $payload = json_decode((string) $body, true, 64, JSON_THROW_ON_ERROR);
            if ($method === 'POST') {
                if (!isset($payload[0]['categoriaItemPca'])) {
                    return $this->response(422, '{}');
                }
                $this->plans[$sequence] = [
                    ...($this->plans[$sequence] ?? []),
                    ...$payload,
                ];
                return $this->response(204);
            }

            if ($method === 'DELETE') {
                $toDelete = $payload['listaNumerosItens'];
                $this->plans[$sequence] = array_values(array_filter(
                    $this->plans[$sequence],
                    static fn (array $item): bool => !in_array($item['numeroItem'], $toDelete, true)
                ));
                return $this->response(204);
            }
        }

        if ($method === 'DELETE' && preg_match('~/pca/[0-9]+/([0-9]+)$~', $path, $matches)) {
            unset($this->plans[(int) $matches[1]]);
            return $this->response(204);
        }

        throw new RuntimeException('Requisição inesperada no simulador estrutural.');
    }

    /** @return int[] */
    public function remainingSequences(): array
    {
        return array_keys($this->plans);
    }

    /** @return string[] */
    public function requestedSteps(): array
    {
        return $this->requestedSteps;
    }

    public function seedPlan(int $sequence, string $marker): void
    {
        $this->plans[$sequence] = [[
            'numeroItem' => 1,
            'descricao' => $marker . 'RESIDUO',
        ]];
    }

    /** @param array<string, string[]> $headers */
    private function response(int $status, string $body = '', array $headers = []): PncpHttpResponse
    {
        return new PncpHttpResponse($status, $headers, $body, 0);
    }
}

final class RecordingOracleAdodb
{
    public ?string $lastSql = null;
    /** @var array<int|string, mixed> */
    public array $lastParams = [];

    public function Param(string|false $name): string|false
    {
        return $name === false ? false : ':' . $name;
    }

    /** @param array<int|string, mixed> $params */
    public function GetOne(string $sql, array $params = []): mixed
    {
        $this->lastSql = $sql;
        $this->lastParams = $params;

        return $params['s51_p0'] ?? false;
    }
}
