<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Pncp\Autenticacao;

use DateTimeImmutable;
use DateTimeZone;
use JsonSerializable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\SituacaoAutenticacaoPncp;
use Semae\Pca\Infrastructure\Observabilidade\AcumuladorEventosTecnicos;
use Semae\Pca\Infrastructure\Observabilidade\RelogioEventosTecnicos;
use Semae\Pca\Infrastructure\Pncp\Autenticacao\AutenticadorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Autenticacao\SessaoPncpAutenticada;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Semae\Pca\Infrastructure\Pncp\Http\ConfiguracaoTransportePncp;
use Semae\Pca\Infrastructure\Pncp\Http\FalhaTransportePncpException;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Stringable;

final class AutenticadorPncpAdapterTest extends TestCase
{
    public function testDeveAutenticarUmaVezEManterTokenOpacoNaSessao(): void
    {
        $login = 'usuario.integracao';
        $senha = 'senha-super-secreta';
        $token = 'conteudo-totalmente-opaco';
        $transporte = new TransporteHttpPncpFake(new RespostaHttpPncp(
            200,
            ['Authorization' => ["Bearer {$token}"]],
            ''
        ));
        $logger = $this->criarLogger();
        $adapter = new AutenticadorPncpAdapter(
            $transporte,
            $this->configuracao(),
            $logger,
            $login,
            $senha
        );

        $resultado = $adapter->autenticar();

        $this->assertSame(SituacaoAutenticacaoPncp::AUTENTICADA, $resultado->getSituacao());
        $this->assertInstanceOf(SessaoPncpAutenticada::class, $resultado->getSessao());
        $this->assertSame($token, $resultado->getSessao()?->getTokenOpaco());
        $this->assertNotInstanceOf(Stringable::class, $resultado->getSessao());
        $this->assertNotInstanceOf(JsonSerializable::class, $resultado->getSessao());
        $this->assertSame(1, $transporte->quantidadeRequisicoes());

        $requisicao = $transporte->ultimaRequisicao();
        $this->assertSame('POST', $requisicao->getMetodo());
        $this->assertSame('/v1/usuarios/login', $requisicao->getCaminho());
        $this->assertSame(30, $requisicao->getTimeoutSegundos());
        $this->assertSame('application/json', $requisicao->getHeaders()['accept']);
        $this->assertSame('application/json', $requisicao->getHeaders()['content-type']);
        $this->assertSame(
            ['login' => $login, 'senha' => $senha],
            json_decode((string) $requisicao->getCorpo(), true, flags: JSON_THROW_ON_ERROR)
        );

        $envelopes = $logger->criarLoteParaExportacao()->getEnvelopes();
        $this->assertCount(1, $envelopes);
        $this->assertSame('AUTENTICAR_PNCP', $envelopes[0]['action']);
        $serializado = json_encode($envelopes, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($login, $serializado);
        $this->assertStringNotContainsString($senha, $serializado);
        $this->assertStringNotContainsString($token, $serializado);
        $this->assertStringNotContainsString('authorization', strtolower($serializado));
    }

    /** @return iterable<string, array{int, array<string, string[]>}> */
    public static function respostasInvalidasOuNaoAutorizadas(): iterable
    {
        yield 'não autenticado' => [401, []];
        yield 'não autorizado' => [403, []];
        yield 'status inesperado' => [500, []];
        yield 'header ausente' => [200, []];
        yield 'esquema inválido' => [200, ['Authorization' => ['Basic credencial']]];
        yield 'bearer vazio' => [200, ['Authorization' => ['Bearer   ']]];
    }

    /** @param array<string, string[]> $headers */
    #[DataProvider('respostasInvalidasOuNaoAutorizadas')]
    public function testDeveTratarRespostaInvalidaOuNaoAutorizadaComoFalhaSistemica(
        int $status,
        array $headers
    ): void {
        $transporte = new TransporteHttpPncpFake(new RespostaHttpPncp($status, $headers, 'sensivel'));
        $logger = $this->criarLogger();
        $adapter = new AutenticadorPncpAdapter(
            $transporte,
            $this->configuracao(),
            $logger,
            'login-secreto',
            'senha-secreta'
        );

        $resultado = $adapter->autenticar();

        $this->assertSame(
            SituacaoAutenticacaoPncp::FALHA_SISTEMICA,
            $resultado->getSituacao()
        );
        $this->assertNull($resultado->getSessao());
        $this->assertNotNull($resultado->getFalha());
        $this->assertSame(1, $transporte->quantidadeRequisicoes());
        $serializado = json_encode(
            $logger->criarLoteParaExportacao()->getEnvelopes(),
            JSON_THROW_ON_ERROR
        );
        $this->assertStringNotContainsString('login-secreto', $serializado);
        $this->assertStringNotContainsString('senha-secreta', $serializado);
        $this->assertStringNotContainsString('sensivel', $serializado);
        $this->assertStringNotContainsString('Basic credencial', $serializado);
    }

    public function testFalhaDeTransporteNaoDeveGerarRetryNemExporDetalhes(): void
    {
        $transporte = new TransporteHttpPncpFake(
            FalhaTransportePncpException::execucaoCurlFalhou(28)
        );
        $logger = $this->criarLogger();
        $adapter = new AutenticadorPncpAdapter(
            $transporte,
            $this->configuracao(),
            $logger,
            'login-secreto',
            'senha-secreta'
        );

        $resultado = $adapter->autenticar();

        $this->assertSame(SituacaoAutenticacaoPncp::FALHA_SISTEMICA, $resultado->getSituacao());
        $this->assertSame(1, $transporte->quantidadeRequisicoes());
        $this->assertSame(
            'PNCP_AUTENTICACAO_TRANSPORTE_FALHOU',
            $resultado->getFalha()?->getCodigo()
        );
        $this->assertSame('Não foi possível autenticar no PNCP.', $resultado->getFalha()?->getMensagem());
        $serializado = json_encode(
            $logger->criarLoteParaExportacao()->getEnvelopes(),
            JSON_THROW_ON_ERROR
        );
        $this->assertStringNotContainsString('cURL 28', $serializado);
        $this->assertStringNotContainsString('login-secreto', $serializado);
        $this->assertStringNotContainsString('senha-secreta', $serializado);
    }

    private function configuracao(): ConfiguracaoTransportePncp
    {
        return new ConfiguracaoTransportePncp(AmbientePncp::HOMOLOGACAO, 1_024, 1_024);
    }

    private function criarLogger(): AcumuladorEventosTecnicos
    {
        return new AcumuladorEventosTecnicos(
            '550e8400-e29b-41d4-a716-446655440000',
            'homologacao',
            'autenticacao-pncp',
            null,
            new RelogioAutenticacaoPncpFake()
        );
    }
}

final class TransporteHttpPncpFake implements TransporteHttpPncp
{
    /** @var RequisicaoHttpPncp[] */
    private array $requisicoes = [];

    public function __construct(
        private readonly RespostaHttpPncp|FalhaTransportePncpException $resultado
    ) {}

    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        $this->requisicoes[] = $requisicao;

        if ($this->resultado instanceof FalhaTransportePncpException) {
            throw $this->resultado;
        }

        return $this->resultado;
    }

    public function quantidadeRequisicoes(): int
    {
        return count($this->requisicoes);
    }

    public function ultimaRequisicao(): RequisicaoHttpPncp
    {
        return $this->requisicoes[array_key_last($this->requisicoes)];
    }
}

final class RelogioAutenticacaoPncpFake implements RelogioEventosTecnicos
{
    public function agora(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-24 15:30:45', new DateTimeZone('UTC'));
    }

    public function tempoMonotonicoMs(): int
    {
        return 1_000;
    }
}
