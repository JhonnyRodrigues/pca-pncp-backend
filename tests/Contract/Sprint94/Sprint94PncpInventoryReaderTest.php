<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint94;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Infrastructure\Pncp\Autenticacao\SessaoPncpAutenticada;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Semae\Pca\Infrastructure\Pncp\Http\ConfiguracaoTransportePncp;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\ConfiguracaoLeituraPncp;
use Tests\Contract\Sprint94\Support\Sprint94PncpInventoryReader;

final class Sprint94PncpInventoryReaderTest extends TestCase
{
    public function testDevePaginarCandidatosEItensSemOmitirCandidatoDaMesmaUasg(): void
    {
        $fake = new Sprint94InventoryTransportFake([
            $this->json([$this->record('999001', 41), $this->record('999001', 42)]),
            $this->json([$this->record('999999', 43)]),
            $this->json([$this->record('999001', 41, 7), $this->record('999001', 41, 3)]),
            $this->json([]),
            $this->json([$this->record('999001', 42, 9)]),
            $this->json([$this->record('999999', 43, 11)]),
        ]);
        $reader = new Sprint94PncpInventoryReader(
            $fake,
            new ConfiguracaoTransportePncp(AmbientePncp::PRODUCAO, 10_000, 10_000),
            new ConfiguracaoLeituraPncp(2, 32, 10, 1, 10)
        );

        $result = $reader->inventory(
            new SessaoPncpAutenticada('token-opaco'),
            new Cnpj('12345678000195'),
            2026,
            ['999001', '999999']
        );

        $this->assertSame([
            ['uasg' => '999001', 'sequencial' => 41, 'numeros_item' => [3, 7]],
            ['uasg' => '999001', 'sequencial' => 42, 'numeros_item' => [9]],
            ['uasg' => '999999', 'sequencial' => 43, 'numeros_item' => [11]],
        ], $result);
        $this->assertCount(6, $fake->requests);
        foreach ($fake->requests as $request) {
            $this->assertSame('GET', $request->getMetodo());
        }
    }

    public function testDeveAceitarAnoSemCandidatosSomenteDepoisDeComprovacaoEstrutural(): void
    {
        $fake = new Sprint94InventoryTransportFake([
            new RespostaHttpPncp(404, [], 'nao interpretar'),
            $this->json([[
                'orgao' => ['cnpj' => '12345678000195'],
                'codigoUnidade' => '930281',
            ]]),
            new RespostaHttpPncp(404, [], 'nao interpretar'),
        ]);
        $reader = new Sprint94PncpInventoryReader(
            $fake,
            new ConfiguracaoTransportePncp(AmbientePncp::PRODUCAO, 10_000, 10_000),
            new ConfiguracaoLeituraPncp(2, 32, 10, 1, 10)
        );

        $result = $reader->inventory(
            new SessaoPncpAutenticada('token-opaco'),
            new Cnpj('12345678000195'),
            2027,
            ['930281']
        );

        $this->assertSame([], $result);
        $this->assertSame([
            '/v1/orgaos/12345678000195/pca/2027/consolidado/unidades?pagina=1&tamanhoPagina=2',
            '/v1/orgaos/12345678000195/unidades',
            '/v1/orgaos/12345678000195/pca/2027/consolidado',
        ], array_map(
            static fn (RequisicaoHttpPncp $request): string => $request->getCaminho(),
            $fake->requests
        ));
    }

    public function testNaoDeveAceitar404AnualQuandoUasgEsperadaNaoFoiComprovada(): void
    {
        $fake = new Sprint94InventoryTransportFake([
            new RespostaHttpPncp(404, [], ''),
            $this->json([[
                'orgao' => ['cnpj' => '12345678000195'],
                'codigoUnidade' => '999999',
            ]]),
        ]);
        $reader = new Sprint94PncpInventoryReader(
            $fake,
            new ConfiguracaoTransportePncp(AmbientePncp::PRODUCAO, 10_000, 10_000),
            new ConfiguracaoLeituraPncp(2, 32, 10, 1, 10)
        );

        $this->expectException(\DomainException::class);

        $reader->inventory(
            new SessaoPncpAutenticada('token-opaco'),
            new Cnpj('12345678000195'),
            2027,
            ['930281']
        );
    }

    public function testNaoDeveAceitarAusenciaQuandoConsultaAnualAindaEncontraPca(): void
    {
        $fake = new Sprint94InventoryTransportFake([
            new RespostaHttpPncp(404, [], ''),
            $this->json([[
                'orgao' => ['cnpj' => '12345678000195'],
                'codigoUnidade' => '930281',
            ]]),
            $this->json([]),
        ]);
        $reader = new Sprint94PncpInventoryReader(
            $fake,
            new ConfiguracaoTransportePncp(AmbientePncp::PRODUCAO, 10_000, 10_000),
            new ConfiguracaoLeituraPncp(2, 32, 10, 1, 10)
        );

        $this->expectException(\UnexpectedValueException::class);

        $reader->inventory(
            new SessaoPncpAutenticada('token-opaco'),
            new Cnpj('12345678000195'),
            2027,
            ['930281']
        );
    }

    /** @return array<string, mixed> */
    private function record(string $uasg, int $sequence, ?int $item = null): array
    {
        $record = [
            'cnpj' => '12345678000195',
            'anoPca' => 2026,
            'codigoUnidade' => $uasg,
            'sequencialPca' => $sequence,
        ];
        if ($item !== null) {
            $record['numeroItem'] = $item;
        }
        return $record;
    }

    /** @param array<int, array<string, mixed>> $records */
    private function json(array $records): RespostaHttpPncp
    {
        return new RespostaHttpPncp(200, [], json_encode($records, JSON_THROW_ON_ERROR));
    }
}

final class Sprint94InventoryTransportFake implements TransporteHttpPncp
{
    /** @var RequisicaoHttpPncp[] */
    public array $requests = [];

    /** @param RespostaHttpPncp[] $responses */
    public function __construct(private array $responses) {}

    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        $this->requests[] = $requisicao;
        $response = array_shift($this->responses);
        self::assertResponse($response);
        return $response;
    }

    private static function assertResponse(mixed $response): void
    {
        if (!$response instanceof RespostaHttpPncp) {
            throw new \RuntimeException('Resposta fake ausente.');
        }
    }
}
