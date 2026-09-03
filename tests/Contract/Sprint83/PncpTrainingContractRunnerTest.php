<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint83;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Tests\Contract\Sprint83\Support\PncpTrainingContractRunner;
use Tests\Contract\Support\SafeContractReport;
use Tests\Support\PerfilPublicacaoPcaFixture;

final class PncpTrainingContractRunnerTest extends TestCase
{
    public function testDeveExecutarCicloNominalPaginadoELimpar(): void
    {
        $remote = new PncpRemotoEmMemoria();
        $report = PncpTrainingContractRunner::fromComponents(
            $this->configuracao(),
            PerfilPublicacaoPcaFixture::valido(),
            $remote
        )->runNominal();

        $this->assertTrue($report['confirmado']);
        $this->assertSame('NOMINAL', $report['modo']);
        $this->assertTrue($report['paginacao_multipla']);
        $this->assertTrue($report['decimais_exatos']);
        $this->assertSame(77, $report['sequencial_pca_recebido_excluido']);
        $this->assertStringContainsString(
            '"sequencial_pca_recebido_excluido": 77',
            SafeContractReport::encode($report, [])
        );
        $this->assertTrue($report['limpeza_confirmada']);
        $this->assertFalse($remote->existe);
        $this->assertSame(1, $remote->logins);
        $this->assertSame(1, $remote->publicacoes);
    }

    public function testStatusHttpNumericoIgualAUasgNaoDeveSerFalsoVazamento(): void
    {
        $config = $this->configuracao();
        $config['massa']['codigo_uasg'] = '204';
        $remote = new PncpRemotoEmMemoria(uasg: '204');

        $report = PncpTrainingContractRunner::fromComponents(
            $config,
            PerfilPublicacaoPcaFixture::valido(),
            $remote
        )->runNominal();

        $this->assertTrue($report['eventos_sanitizados']);
        $this->assertFalse($remote->existe);
    }

    public function testDeveObservarEExcluirAposPerderRespostaSemRepetirPublicacao(): void
    {
        $remote = new PncpRemotoEmMemoria();
        $report = PncpTrainingContractRunner::fromComponents(
            $this->configuracao(),
            PerfilPublicacaoPcaFixture::valido(),
            $remote
        )->runUncertainWrite();

        $this->assertTrue($report['confirmado']);
        $this->assertSame('ESCRITA_INCERTA', $report['modo']);
        $this->assertTrue($report['resposta_descartada']);
        $this->assertTrue($report['sem_repeticao']);
        $this->assertTrue($report['efeito_localizado_por_leitura']);
        $this->assertTrue($report['limpeza_confirmada']);
        $this->assertFalse($remote->existe);
        $this->assertSame(1, $remote->logins);
        $this->assertSame(1, $remote->publicacoes);
    }

    public function testDeveLimparSomenteResiduoExplicitamenteAutorizado(): void
    {
        $config = $this->configuracao();
        $config['massa']['residuo_sequencial_autorizado'] = 77;
        $remote = new PncpRemotoEmMemoria(true);

        $report = PncpTrainingContractRunner::fromComponents(
            $config,
            PerfilPublicacaoPcaFixture::valido(),
            $remote
        )->cleanupOwnedResidue();

        $this->assertTrue($report['confirmado']);
        $this->assertSame('LIMPEZA_ISOLADA', $report['modo']);
        $this->assertTrue($report['exclusao_executada']);
        $this->assertFalse($remote->existe);
        $this->assertSame(0, $remote->publicacoes);
    }

    public function testDeveRecusarExecucaoNormalEnquantoResiduoEstiverConfigurado(): void
    {
        $config = $this->configuracao();
        $config['massa']['residuo_sequencial_autorizado'] = 77;
        $runner = PncpTrainingContractRunner::fromComponents(
            $config,
            PerfilPublicacaoPcaFixture::valido(),
            new PncpRemotoEmMemoria(true)
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('resíduo explicitamente pendente');

        $runner->runNominal();
    }

    public function testDeveExecutarLimpezaBestEffortQuandoFluxoFalharAposPublicacao(): void
    {
        $remote = new PncpRemotoEmMemoria();
        $remote->falharInclusao = true;
        $runner = PncpTrainingContractRunner::fromComponents(
            $this->configuracao(),
            PerfilPublicacaoPcaFixture::valido(),
            $remote
        );

        try {
            $runner->runNominal();
            $this->fail('Era esperada falha controlada depois da publicação.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'O gate nominal falhou depois de confirmar a limpeza.',
                $exception->getMessage()
            );
        }

        $this->assertFalse($remote->existe);
        $this->assertSame(1, $remote->publicacoes);
    }

    /** @return array<string, mixed> */
    private function configuracao(): array
    {
        return [
            'allow_writes' => true,
            'server_name' => 'pca.homologacao.test',
            'massa' => [
                'ano' => 2027,
                'data_desejada' => '2027-06-30',
                'codigo_uasg' => '123456',
                'descricao_uasg' => 'Massa dedicada Sprint 8.3',
                'id_demanda_publicacao_1' => 1001,
                'id_demanda_publicacao_2' => 1003,
                'id_demanda_inclusao' => 1005,
                'residuo_sequencial_autorizado' => null,
                'item' => [
                    'categoria' => 1,
                    'catalogo' => 1,
                    'unidade_medida' => 'UNIDADE',
                    'codigo_item' => '123',
                    'grupo' => '45',
                    'descricao_grupo' => 'Grupo da massa dedicada',
                    'origem' => 'N',
                ],
            ],
        ];
    }
}

final class PncpRemotoEmMemoria implements TransporteHttpPncp
{
    public int $logins = 0;
    public int $publicacoes = 0;
    public bool $falharInclusao = false;

    /** @var int[] */
    private array $itens = [];

    public function __construct(
        public bool $existe = false,
        private readonly string $uasg = '123456'
    )
    {
    }

    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        $metodo = $requisicao->getMetodo();
        $caminho = $requisicao->getCaminho();

        if ($metodo === 'POST' && $caminho === '/v1/usuarios/login') {
            $this->logins++;

            return new RespostaHttpPncp(200, ['Authorization' => ['Bearer token-opaco']], '');
        }

        if ($metodo === 'POST' && preg_match('#^/v1/orgaos/[^/]+/pca$#D', $caminho) === 1) {
            $this->publicacoes++;
            $this->existe = true;
            $body = $this->json($requisicao);
            $this->itens = array_column($body['itensPlano'], 'numeroItem');

            return new RespostaHttpPncp(201, [
                'Location' => ['/v1/orgaos/12345678000195/pca/2027/77'],
            ], '');
        }

        if ($metodo === 'GET' && str_contains($caminho, '/consolidado/unidades?')) {
            if (!$this->existe) {
                return new RespostaHttpPncp(204, [], '');
            }
            $pagina = $this->pagina($caminho);
            $items = $pagina === 1 ? [[
                'cnpj' => '12345678000195',
                'anoPca' => 2027,
                'codigoUnidade' => $this->uasg,
                'sequencialPca' => 77,
            ]] : [];

            return $this->lista($items);
        }

        if ($metodo === 'GET' && str_contains($caminho, '/77/consolidado')) {
            if (!$this->existe) {
                return new RespostaHttpPncp(204, [], '');
            }

            return $this->lista([
                'cnpj' => '12345678000195',
                'anoPca' => 2027,
                'codigoUnidade' => $this->uasg,
                'sequencialPca' => 77,
            ]);
        }

        if ($metodo === 'GET' && str_contains($caminho, '/77/itens?')) {
            $indice = $this->pagina($caminho) - 1;
            $items = isset($this->itens[$indice]) ? [[
                'cnpj' => '12345678000195',
                'anoPca' => 2027,
                'codigoUnidade' => $this->uasg,
                'sequencialPca' => 77,
                'numeroItem' => $this->itens[$indice],
            ]] : [];

            return $this->lista($items);
        }

        if ($metodo === 'POST' && str_ends_with($caminho, '/77/itens')) {
            if ($this->falharInclusao) {
                return new RespostaHttpPncp(500, [], '');
            }
            $body = $this->json($requisicao);
            foreach ($body as $item) {
                $this->itens[] = $item['numeroItem'];
            }

            return new RespostaHttpPncp(200, [], '');
        }

        if ($metodo === 'DELETE' && str_ends_with($caminho, '/77/itens')) {
            $body = $this->json($requisicao);
            $this->itens = array_values(array_diff($this->itens, $body['listaNumerosItens']));

            return new RespostaHttpPncp(200, [], '');
        }

        if ($metodo === 'DELETE' && str_ends_with($caminho, '/77')) {
            $this->existe = false;
            $this->itens = [];

            return new RespostaHttpPncp(200, [], '');
        }

        throw new RuntimeException('Requisição inesperada no PNCP em memória.');
    }

    /** @return array<mixed> */
    private function json(RequisicaoHttpPncp $request): array
    {
        return json_decode((string) $request->getCorpo(), true, 512, JSON_THROW_ON_ERROR);
    }

    /** @param array<mixed> $body */
    private function lista(array $body): RespostaHttpPncp
    {
        return new RespostaHttpPncp(200, [], json_encode($body, JSON_THROW_ON_ERROR));
    }

    private function pagina(string $path): int
    {
        parse_str((string) parse_url($path, PHP_URL_QUERY), $query);

        return (int) ($query['pagina'] ?? 0);
    }
}
