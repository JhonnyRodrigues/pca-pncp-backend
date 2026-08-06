<?php

declare(strict_types=1);

namespace Tests\Application\Ports;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Ports\EvidenciaCancelamentoHomologadoPort;
use Semae\Pca\Application\Results\ResultadoConsultaEvidenciaCancelamento;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\EvidenciaCancelamentoHomologado;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class EvidenciaCancelamentoHomologadoPortTest extends TestCase
{
    public function testConsumidorDeveConsultarUltimoCancelamentoDaUnidadeExterna(): void
    {
        $identidade = $this->criarIdentidade();
        $unidade = UnidadeExternaPublicacao::individual($identidade);
        $evidencia = new EvidenciaCancelamentoHomologado(
            $identidade,
            731,
            'Cancelamento homologado.'
        );
        $port = new EvidenciaCancelamentoHomologadoPortFake($evidencia);

        $resultado = $port->consultarUltimoCancelamentoQueTornouUnidadeVazia(
            $unidade,
            [$identidade]
        );

        $this->assertSame($unidade, $port->unidadeConsultada);
        $this->assertSame([$identidade], $port->identidadesConsultadas);
        $this->assertSame($evidencia, $resultado->getEvidencia());
    }

    private function criarIdentidade(): IdentidadePlano
    {
        return new IdentidadePlano(
            new UnidadeOrcamentaria(
                new IdentidadeUnidadeOrcamentaria('uo-101'),
                new Lotacao(101, 'Diretoria Administrativa'),
                new Uasg('928344', 'SEMAE Piracicaba'),
                'Diretoria Administrativa'
            ),
            new AnoExecucao((int) date('Y') + 1)
        );
    }
}

final class EvidenciaCancelamentoHomologadoPortFake implements EvidenciaCancelamentoHomologadoPort
{
    public ?UnidadeExternaPublicacao $unidadeConsultada = null;

    /** @var IdentidadePlano[] */
    public array $identidadesConsultadas = [];

    public function __construct(
        private readonly EvidenciaCancelamentoHomologado $evidencia
    ) {}

    public function consultarUltimoCancelamentoQueTornouUnidadeVazia(
        UnidadeExternaPublicacao $unidade,
        array $identidades
    ): ResultadoConsultaEvidenciaCancelamento {
        $this->unidadeConsultada = $unidade;
        $this->identidadesConsultadas = $identidades;

        return ResultadoConsultaEvidenciaCancelamento::disponivel($this->evidencia);
    }
}
