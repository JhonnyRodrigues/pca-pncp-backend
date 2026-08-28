<?php

declare(strict_types=1);

namespace Tests\Application\Ports;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Ports\ContextoUsuarioPort;
use Semae\Pca\Application\Ports\EstadoOperacionalPublicacaoPort;
use Semae\Pca\Application\Ports\RegistroAuditoriaPort;
use Semae\Pca\Application\Results\ResultadoConsultaEstadoOperacional;
use Semae\Pca\Application\Results\ResultadoPersistenciaLocal;
use Semae\Pca\Application\Results\ResultadoResolucaoContextoUsuario;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Application\ValueObjects\IntencaoRegistroHistorico;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Enums\ProcedimentoPublicacaoPca;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Representante;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class ContextoAuditoriaEstadoOperacionalPortsTest extends TestCase
{
    public function testConsumidorDeveResolverRepresentanteELotacaoConjuntamente(): void
    {
        $contexto = $this->criarContexto();
        $port = new ContextoUsuarioPortFake($contexto);

        $resultado = $port->resolver();

        $this->assertSame($contexto, $resultado->getContexto());
    }

    public function testConsumidorDeveConsultarERegistrarEstadoPorUnidadeExterna(): void
    {
        $unidade = $this->criarUnidade();
        $estado = EstadoOperacionalPublicacao::publicado(new SequencialPca(77));
        $port = new EstadoOperacionalPublicacaoPortFake($estado);

        $consulta = $port->consultar($unidade);
        $registro = $port->registrar($unidade, $estado);

        $this->assertSame($unidade, $port->unidadeConsultada);
        $this->assertSame($estado, $consulta->getEstadoOperacional());
        $this->assertSame($unidade, $port->unidadeRegistrada);
        $this->assertSame($estado, $port->estadoRegistrado);
        $this->assertNull($registro->getFalha());
    }

    public function testConsumidorDeveEntregarIntencaoSemInstanteParaRegistro(): void
    {
        $contexto = $this->criarContexto();
        $intencao = new IntencaoRegistroHistorico(
            ProcedimentoPublicacaoPca::PUBLICACAO_INICIAL,
            'Publicação inicial confirmada para o exercício 2027 na UASG 986700, sequencial 77.',
            $contexto->getRepresentante(),
            $contexto->getLotacao()
        );
        $port = new RegistroAuditoriaPortFake();

        $resultado = $port->registrar($intencao);

        $this->assertSame($intencao, $port->intencaoRegistrada);
        $this->assertFalse(method_exists($intencao, 'getInstante'));
        $this->assertNull($resultado->getFalha());
    }

    private function criarContexto(): ContextoUsuario
    {
        return new ContextoUsuario(
            new Representante(709, 'Maria da Silva'),
            new Lotacao(159, 'Coordenadoria de Tecnologia')
        );
    }

    private function criarUnidade(): UnidadeExternaPublicacao
    {
        return UnidadeExternaPublicacao::consolidada(
            new AnoExecucao((int) date('Y') + 1),
            new Uasg('986700', 'UASG Consolidadora')
        );
    }
}

final class ContextoUsuarioPortFake implements ContextoUsuarioPort
{
    public function __construct(private readonly ContextoUsuario $contexto) {}

    public function resolver(): ResultadoResolucaoContextoUsuario
    {
        return ResultadoResolucaoContextoUsuario::resolvido($this->contexto);
    }
}

final class EstadoOperacionalPublicacaoPortFake implements EstadoOperacionalPublicacaoPort
{
    public ?UnidadeExternaPublicacao $unidadeConsultada = null;
    public ?UnidadeExternaPublicacao $unidadeRegistrada = null;
    public ?EstadoOperacionalPublicacao $estadoRegistrado = null;

    public function __construct(private readonly EstadoOperacionalPublicacao $estado) {}

    public function consultar(
        UnidadeExternaPublicacao $unidade
    ): ResultadoConsultaEstadoOperacional {
        $this->unidadeConsultada = $unidade;

        return ResultadoConsultaEstadoOperacional::disponivel($this->estado);
    }

    public function registrar(
        UnidadeExternaPublicacao $unidade,
        EstadoOperacionalPublicacao $estado
    ): ResultadoPersistenciaLocal {
        $this->unidadeRegistrada = $unidade;
        $this->estadoRegistrado = $estado;

        return ResultadoPersistenciaLocal::concluida();
    }
}

final class RegistroAuditoriaPortFake implements RegistroAuditoriaPort
{
    public ?IntencaoRegistroHistorico $intencaoRegistrada = null;

    public function registrar(
        IntencaoRegistroHistorico $intencao
    ): ResultadoPersistenciaLocal
    {
        $this->intencaoRegistrada = $intencao;

        return ResultadoPersistenciaLocal::concluida();
    }
}
