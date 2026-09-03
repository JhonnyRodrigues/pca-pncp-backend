<?php

declare(strict_types=1);

namespace Tests\Composition;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionProperty;
use Semae\Pca\Composition\ComposicaoPncpPublicacaoPca;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Semae\Pca\Infrastructure\Pncp\Autenticacao\AutenticadorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Escrita\EscritorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\LeitorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Leitura\TemporizadorPncp;
use Tests\Support\PerfilPublicacaoPcaFixture;

final class ComposicaoPncpPublicacaoPcaTest extends TestCase
{
    public function testDeveCriarOsTresAdaptersComTransporteELoggerCompartilhadosSemIo(): void
    {
        $perfil = new PerfilPublicacaoPca(PerfilPublicacaoPcaFixture::valido());
        $transporte = new TransportePncpSemIo();
        $logger = new NullLogger();
        $temporizador = new TemporizadorPncpSemEspera();
        $configuracaoLeitura = $perfil->getConfiguracaoLeitura();
        $composicao = new ComposicaoPncpPublicacaoPca(
            $perfil,
            $transporte,
            $configuracaoLeitura,
            $logger,
            $temporizador
        );
        $adapters = [
            $composicao->getAutenticador(),
            $composicao->getLeitor(),
            $composicao->getEscritor(),
        ];

        $this->assertInstanceOf(AutenticadorPncpAdapter::class, $adapters[0]);
        $this->assertInstanceOf(LeitorPncpAdapter::class, $adapters[1]);
        $this->assertInstanceOf(EscritorPncpAdapter::class, $adapters[2]);

        foreach ($adapters as $adapter) {
            $this->assertSame($transporte, $this->propriedade($adapter, 'transporte'));
            $this->assertSame($logger, $this->propriedade($adapter, 'logger'));
        }

        $this->assertSame(
            $perfil->getConfiguracaoTransporte(),
            $this->propriedade($adapters[0], 'configuracao')
        );
        $this->assertSame(
            $perfil->getConfiguracaoTransporte(),
            $this->propriedade($adapters[1], 'configuracaoTransporte')
        );
        $this->assertSame(
            $perfil->getConfiguracaoTransporte(),
            $this->propriedade($adapters[2], 'configuracao')
        );
        $this->assertSame($configuracaoLeitura, $this->propriedade($adapters[1], 'configuracaoLeitura'));
        $this->assertSame($temporizador, $this->propriedade($adapters[1], 'temporizador'));
        $this->assertSame(0, $transporte->chamadas);
        $this->assertSame(0, $temporizador->chamadas);
    }

    private function propriedade(object $objeto, string $nome): mixed
    {
        $propriedade = new ReflectionProperty($objeto, $nome);
        $propriedade->setAccessible(true);

        return $propriedade->getValue($objeto);
    }
}

final class TransportePncpSemIo implements TransporteHttpPncp
{
    public int $chamadas = 0;

    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        $this->chamadas++;

        return new RespostaHttpPncp(500, [], '');
    }
}

final class TemporizadorPncpSemEspera implements TemporizadorPncp
{
    public int $chamadas = 0;

    public function agoraUtc(): DateTimeImmutable
    {
        $this->chamadas++;

        return new DateTimeImmutable('2026-09-02T00:00:00+00:00');
    }

    public function aguardarMilissegundos(int $milissegundos): void
    {
        $this->chamadas++;
    }
}
