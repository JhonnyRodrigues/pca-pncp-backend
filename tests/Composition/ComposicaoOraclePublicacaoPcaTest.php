<?php

declare(strict_types=1);

namespace Tests\Composition;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Semae\Pca\Composition\ComposicaoOraclePublicacaoPca;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Oracle\Auditoria\OracleRegistroAuditoriaAdapter;
use Semae\Pca\Infrastructure\Oracle\Contexto\OracleContextoUsuarioAdapter;
use Semae\Pca\Infrastructure\Oracle\EstadoOperacional\OracleEstadoOperacionalPublicacaoAdapter;
use Semae\Pca\Infrastructure\Oracle\Estrategia\OracleEstrategiaPublicacaoAdapter;
use Semae\Pca\Infrastructure\Oracle\Plano\OraclePlanoRepository;
use Semae\Pca\Infrastructure\Oracle\Transacao\OracleUnidadeDeTrabalhoAdapter;
use Tests\Support\AdodbSemIo;
use Tests\Support\PerfilPublicacaoPcaFixture;

final class ComposicaoOraclePublicacaoPcaTest extends TestCase
{
    public function testDeveCriarOsSeisAdaptersComAMesmaConexaoSemExecutarIo(): void
    {
        $adodb = new AdodbSemIo();
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $composicao = new ComposicaoOraclePublicacaoPca(
            $conexao,
            new PerfilPublicacaoPca(PerfilPublicacaoPcaFixture::valido()),
            'usuario.teste'
        );
        $adapters = [
            $composicao->getContextoUsuario(),
            $composicao->getPlanoRepository(),
            $composicao->getEstrategiaPublicacao(),
            $composicao->getUnidadeDeTrabalho(),
            $composicao->getEstadoOperacional(),
            $composicao->getRegistroAuditoria(),
        ];

        $this->assertInstanceOf(OracleContextoUsuarioAdapter::class, $adapters[0]);
        $this->assertInstanceOf(OraclePlanoRepository::class, $adapters[1]);
        $this->assertInstanceOf(OracleEstrategiaPublicacaoAdapter::class, $adapters[2]);
        $this->assertInstanceOf(OracleUnidadeDeTrabalhoAdapter::class, $adapters[3]);
        $this->assertInstanceOf(OracleEstadoOperacionalPublicacaoAdapter::class, $adapters[4]);
        $this->assertInstanceOf(OracleRegistroAuditoriaAdapter::class, $adapters[5]);

        foreach ($adapters as $adapter) {
            // A identidade da conexão é uma invariante estrutural sem saída pública.
            $propriedade = new ReflectionProperty($adapter, 'conexao');
            $propriedade->setAccessible(true);
            $this->assertSame($conexao, $propriedade->getValue($adapter));
        }

        $this->assertSame(0, $adodb->chamadas);
    }
}
