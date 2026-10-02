<?php

declare(strict_types=1);

namespace Tests\Application\Results;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Enums\SituacaoPreparacaoPublicacaoPca;
use Semae\Pca\Application\Results\ResultadoPreparacaoPublicacaoPca;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Application\ValueObjects\EstrategiaPublicacao;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Representante;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class ResultadoPreparacaoPublicacaoPcaTest extends TestCase
{
    public function testPreparacaoConcluidaDevePreservarSomenteOsDadosPreparados(): void
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidadeOrcamentaria = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria('UO-01'),
            new Lotacao(10, 'Diretoria'),
            new Uasg('986789', 'UASG da UO'),
            'Unidade Orçamentária'
        );
        $unidade = UnidadeExternaPublicacao::individual(
            new IdentidadePlano($unidadeOrcamentaria, $ano)
        );
        $contexto = new ContextoUsuario(
            new Representante(709, 'Maria da Silva'),
            new Lotacao(159, 'Coordenadoria de Tecnologia')
        );
        $estrategia = EstrategiaPublicacao::individual();
        $sessao = new class implements SessaoPncp {};

        $resultado = ResultadoPreparacaoPublicacaoPca::preparada(
            $contexto,
            $estrategia,
            [$unidade],
            $sessao
        );

        $this->assertSame(SituacaoPreparacaoPublicacaoPca::PREPARADA, $resultado->getSituacao());
        $this->assertSame($contexto, $resultado->getContextoUsuario());
        $this->assertSame($estrategia, $resultado->getEstrategia());
        $this->assertSame([$unidade], $resultado->getUnidadesExternas());
        $this->assertSame($sessao, $resultado->getSessaoPncp());
        $this->assertNull($resultado->getFalha());
    }

    public function testPreparacaoConcluidaDeveExigirAoMenosUmaUnidadeExterna(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ResultadoPreparacaoPublicacaoPca::preparada(
            new ContextoUsuario(
                new Representante(709, 'Maria da Silva'),
                new Lotacao(159, 'Coordenadoria de Tecnologia')
            ),
            EstrategiaPublicacao::individual(),
            [],
            new class implements SessaoPncp {}
        );
    }

    public function testPreparacaoInterrompidaDeveConterSomenteFalhaSegura(): void
    {
        $falha = new FalhaSegura('ESTRATEGIA_INVALIDA', 'Estratégia inválida.');

        $resultado = ResultadoPreparacaoPublicacaoPca::interrompida($falha);

        $this->assertSame(
            SituacaoPreparacaoPublicacaoPca::INTERROMPIDA,
            $resultado->getSituacao()
        );
        $this->assertNull($resultado->getContextoUsuario());
        $this->assertNull($resultado->getEstrategia());
        $this->assertSame([], $resultado->getUnidadesExternas());
        $this->assertNull($resultado->getSessaoPncp());
        $this->assertSame($falha, $resultado->getFalha());
    }

    public function testPreparacaoInterrompidaDevePreservarUnidadesJaFormadas(): void
    {
        $unidade = $this->criarUnidade();
        $falha = new FalhaSegura('AUTENTICACAO_FALHOU', 'Autenticação falhou.');

        $resultado = ResultadoPreparacaoPublicacaoPca::interrompida(
            $falha,
            [$unidade]
        );

        $this->assertSame([$unidade], $resultado->getUnidadesExternas());
        $this->assertSame($falha, $resultado->getFalha());
    }

    public function testAusenciaDeUnidadesDeveSerRepresentadaSemDadosDeExecucao(): void
    {
        $resultado = ResultadoPreparacaoPublicacaoPca::semUnidades();

        $this->assertSame(
            SituacaoPreparacaoPublicacaoPca::SEM_UNIDADES,
            $resultado->getSituacao()
        );
        $this->assertNull($resultado->getContextoUsuario());
        $this->assertNull($resultado->getEstrategia());
        $this->assertSame([], $resultado->getUnidadesExternas());
        $this->assertNull($resultado->getSessaoPncp());
        $this->assertNull($resultado->getFalha());
    }

    private function criarUnidade(): UnidadeExternaPublicacao
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $uo = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria('UO-01'),
            new Lotacao(10, 'Diretoria'),
            new Uasg('986789', 'UASG da UO'),
            'Unidade Orçamentária'
        );

        return UnidadeExternaPublicacao::individual(new IdentidadePlano($uo, $ano));
    }
}
