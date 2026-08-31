<?php

declare(strict_types=1);

namespace Tests\Composition;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Semae\Pca\Application\Ports\AutenticadorPncpPort;
use Semae\Pca\Application\Ports\ContextoUsuarioPort;
use Semae\Pca\Application\Ports\EscritorPncpPort;
use Semae\Pca\Application\Ports\EstadoOperacionalPublicacaoPort;
use Semae\Pca\Application\Ports\EstrategiaPublicacaoPort;
use Semae\Pca\Application\Ports\LeitorPncpPort;
use Semae\Pca\Application\Ports\RegistroAuditoriaPort;
use Semae\Pca\Application\Ports\UnidadeDeTrabalhoPort;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaUseCase;
use Semae\Pca\Composition\MontadorNucleoPublicacaoPca;
use Semae\Pca\Domain\Ports\PlanoRepository;

final class MontadorNucleoPublicacaoPcaTest extends TestCase
{
    public function testDeveMontarUseCaseComAsNovePortsELogger(): void
    {
        $montador = new MontadorNucleoPublicacaoPca(
            $this->createStub(ContextoUsuarioPort::class),
            $this->createStub(PlanoRepository::class),
            $this->createStub(EstrategiaPublicacaoPort::class),
            $this->createStub(UnidadeDeTrabalhoPort::class),
            $this->createStub(AutenticadorPncpPort::class),
            $this->createStub(LeitorPncpPort::class),
            $this->createStub(EscritorPncpPort::class),
            $this->createStub(EstadoOperacionalPublicacaoPort::class),
            $this->createStub(RegistroAuditoriaPort::class),
            new NullLogger()
        );

        $this->assertInstanceOf(PublicarPcaUseCase::class, $montador->montar());
    }
}
