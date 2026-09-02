<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint82;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Composition\ComposicaoOraclePublicacaoPca;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Tests\Support\AdodbSemIo;
use Tests\Support\PerfilPublicacaoPcaFixture;

final class OracleCompositionContractTest extends TestCase
{
    public function testGateDeveConsumirASubcomposicaoOracleDeProducaoSemIo(): void
    {
        $adodb = new AdodbSemIo();
        $composicao = new ComposicaoOraclePublicacaoPca(
            new ConexaoAdodbEmprestada($adodb),
            new PerfilPublicacaoPca(PerfilPublicacaoPcaFixture::valido()),
            'usuario.teste'
        );

        $this->assertNotNull($composicao->getContextoUsuario());
        $this->assertNotNull($composicao->getPlanoRepository());
        $this->assertNotNull($composicao->getEstrategiaPublicacao());
        $this->assertNotNull($composicao->getUnidadeDeTrabalho());
        $this->assertNotNull($composicao->getEstadoOperacional());
        $this->assertNotNull($composicao->getRegistroAuditoria());
        $this->assertSame(0, $adodb->chamadas);
    }
}
