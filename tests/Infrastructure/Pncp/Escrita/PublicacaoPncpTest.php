<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Pncp\Escrita;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Semae\Pca\Application\Commands\ComandoPublicarPca;
use Semae\Pca\Application\Enums\SituacaoEscritaPncp;
use Semae\Pca\Application\Results\ResultadoPublicacaoPncp;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\Demanda;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\SituacaoDemanda;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\DecimalExato;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Semae\Pca\Infrastructure\Pncp\Autenticacao\SessaoPncpAutenticada;
use Semae\Pca\Infrastructure\Pncp\Escrita\EscritorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Semae\Pca\Infrastructure\Pncp\Http\ConfiguracaoTransportePncp;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Serializacao\SerializadorPayloadPncp;

final class PublicacaoPncpTest extends TestCase
{
    #[DataProvider('statusAceitos')]
    public function testDevePublicarUmaVezEExtrairSequencialDoLocation(int $status): void
    {
        $transporte = new TransporteEscritaPncpFake(new RespostaHttpPncp(
            $status,
            ['Location' => ['/v1/orgaos/12345678000195/pca/2026/77']],
            ''
        ));
        $comando = $this->comando();

        $resultado = $this->adapter($transporte)->publicar(
            new SessaoPncpAutenticada('token-ultrassecreto'),
            $comando
        );

        $this->assertSame(SituacaoEscritaPncp::ACEITA, $resultado->getSituacao());
        $this->assertSame(77, $resultado->getSequencialPca()?->getValor());
        $this->assertNull($resultado->getFalha());
        $this->assertCount(1, $transporte->requisicoes);

        $requisicao = $transporte->requisicoes[0];
        $this->assertSame('POST', $requisicao->getMetodo());
        $this->assertSame('/v1/orgaos/12345678000195/pca', $requisicao->getCaminho());
        $this->assertSame([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'authorization' => 'Bearer token-ultrassecreto',
        ], $requisicao->getHeaders());
        $this->assertSame(60, $requisicao->getTimeoutSegundos());
        $this->assertSame(
            (new SerializadorPayloadPncp())->serializarPublicacao($comando),
            $requisicao->getCorpo()
        );
    }

    #[DataProvider('locationsAceitosPorAmbiente')]
    public function testDeveAceitarSomenteLocationCanonicoDoAmbiente(
        AmbientePncp $ambiente,
        string $location,
        int $sequencial
    ): void
    {
        $resultado = $this->publicarComLocation($location, $ambiente);

        $this->assertSame(SituacaoEscritaPncp::ACEITA, $resultado->getSituacao());
        $this->assertSame($sequencial, $resultado->getSequencialPca()?->getValor());
        $this->assertNull($resultado->getFalha());
    }

    #[DataProvider('locationsRejeitadosEmProducao')]
    public function testProducaoDeveRejeitarLocationForaDoPrefixoCanonico(string $location): void
    {
        $resultado = $this->publicarComLocation(
            $location,
            AmbientePncp::PRODUCAO
        );

        $this->assertSame(SituacaoEscritaPncp::RESULTADO_INCERTO, $resultado->getSituacao());
        $this->assertNull($resultado->getSequencialPca());
        $this->assertSame(
            'PNCP_PUBLICACAO_RESULTADO_INCERTO',
            $resultado->getFalha()?->getCodigo()
        );
    }

    #[DataProvider('locationsInvalidos')]
    public function testNaoDeveAceitarPublicacaoSemLocationCanonico(array $headers): void
    {
        $transporte = new TransporteEscritaPncpFake(new RespostaHttpPncp(
            201,
            $headers,
            '{"sequencialPca":77}'
        ));

        $resultado = $this->adapter($transporte)->publicar(
            new SessaoPncpAutenticada('token-opaco'),
            $this->comando()
        );

        $this->assertSame(SituacaoEscritaPncp::RESULTADO_INCERTO, $resultado->getSituacao());
        $this->assertNull($resultado->getSequencialPca());
        $this->assertSame('PNCP_PUBLICACAO_RESULTADO_INCERTO', $resultado->getFalha()?->getCodigo());
        $this->assertCount(1, $transporte->requisicoes);
    }

    /** @return array<string, array{int}> */
    public static function statusAceitos(): array
    {
        return [
            'manual 2.5' => [200],
            'gate contratual' => [201],
        ];
    }

    /** @return array<string, array{AmbientePncp, string, int}> */
    public static function locationsAceitosPorAmbiente(): array
    {
        return [
            'homologacao relativo' => [
                AmbientePncp::HOMOLOGACAO,
                '/v1/orgaos/12345678000195/pca/2026/21',
                21,
            ],
            'homologacao absoluto canonico' => [
                AmbientePncp::HOMOLOGACAO,
                'https://treina.pncp.gov.br/api/pncp/v1/orgaos/12345678000195/pca/2026/22',
                22,
            ],
            'homologacao absoluto emitido sob pncp-api' => [
                AmbientePncp::HOMOLOGACAO,
                'https://treina.pncp.gov.br/pncp-api/v1/orgaos/12345678000195/pca/2026/24',
                24,
            ],
            'producao relativo' => [
                AmbientePncp::PRODUCAO,
                '/v1/orgaos/12345678000195/pca/2026/31',
                31,
            ],
            'producao absoluto canonico' => [
                AmbientePncp::PRODUCAO,
                'https://pncp.gov.br/api/pncp/v1/orgaos/12345678000195/pca/2026/32',
                32,
            ],
        ];
    }

    /** @return array<string, array{string}> */
    public static function locationsRejeitadosEmProducao(): array
    {
        return [
            'alias exclusivo da homologacao' => [
                'https://pncp.gov.br/pncp-api/v1/orgaos/12345678000195/pca/2026/41',
            ],
            'host divergente' => [
                'https://treina.pncp.gov.br/api/pncp/v1/orgaos/12345678000195/pca/2026/42',
            ],
            'esquema diferente de HTTPS' => [
                'http://pncp.gov.br/api/pncp/v1/orgaos/12345678000195/pca/2026/43',
            ],
        ];
    }

    /** @return array<string, array{array<string, string[]>}> */
    public static function locationsInvalidos(): array
    {
        return [
            'ausente sem fallback pelo corpo' => [[]],
            'duplicado' => [['location' => [
                '/v1/orgaos/12345678000195/pca/2026/77',
                '/v1/orgaos/12345678000195/pca/2026/78',
            ]]],
            'CNPJ divergente' => [[
                'location' => ['/v1/orgaos/98765432000198/pca/2026/77'],
            ]],
            'ano divergente' => [[
                'location' => ['/v1/orgaos/12345678000195/pca/2025/77'],
            ]],
            'sequencial zero' => [[
                'location' => ['/v1/orgaos/12345678000195/pca/2026/0'],
            ]],
            'sufixo inesperado' => [[
                'location' => ['/v1/orgaos/12345678000195/pca/2026/77/itens'],
            ]],
            'query inesperada' => [[
                'location' => ['/v1/orgaos/12345678000195/pca/2026/77?origem=externa'],
            ]],
        ];
    }

    private function publicarComLocation(
        string $location,
        AmbientePncp $ambiente = AmbientePncp::HOMOLOGACAO
    ): ResultadoPublicacaoPncp
    {
        return $this->adapter(new TransporteEscritaPncpFake(
            new RespostaHttpPncp(201, ['location' => [$location]], '')
        ), $ambiente)->publicar(new SessaoPncpAutenticada('token-opaco'), $this->comando());
    }

    private function adapter(
        TransporteHttpPncp $transporte,
        AmbientePncp $ambiente = AmbientePncp::HOMOLOGACAO
    ): EscritorPncpAdapter
    {
        return new EscritorPncpAdapter(
            $transporte,
            new ConfiguracaoTransportePncp(
                $ambiente,
                1_000_000,
                1_000_000
            ),
            new SerializadorPayloadPncp(),
            new NullLogger()
        );
    }

    private function comando(): ComandoPublicarPca
    {
        $ano = new AnoExecucao(2026);
        $unidade = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria('UO-10'),
            new Lotacao(20, 'Lotação de teste'),
            new Uasg('110001', 'UASG de teste'),
            'Unidade de teste'
        );
        $demanda = new Demanda(
            1001,
            SituacaoDemanda::HOMOLOGADA,
            159,
            $unidade,
            $ano,
            new DateTimeImmutable('2026-03-15'),
            1,
            'Item de teste',
            'UNIDADE',
            2,
            new DecimalExato('100'),
            new DecimalExato('100'),
            2,
            1,
            'ITEM-1',
            'GRUPO-1',
            'Grupo de teste',
            'N'
        );

        return new ComandoPublicarPca(
            new Cnpj('12.345.678/0001-95'),
            UnidadeExternaPublicacao::consolidada(
                $ano,
                new Uasg('110001', 'UASG de teste')
            ),
            [$demanda]
        );
    }
}

final class TransporteEscritaPncpFake implements TransporteHttpPncp
{
    /** @var RequisicaoHttpPncp[] */
    public array $requisicoes = [];

    public function __construct(private readonly RespostaHttpPncp $resposta) {}

    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        $this->requisicoes[] = $requisicao;

        return $this->resposta;
    }
}
