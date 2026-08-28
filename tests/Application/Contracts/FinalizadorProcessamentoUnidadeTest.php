<?php

declare(strict_types=1);

namespace Tests\Application\Contracts;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Contracts\FinalizadorProcessamentoUnidade;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Enums\SituacaoDesfechoProcessamentoUnidade;
use Semae\Pca\Application\Results\DesfechoProcessamentoUnidade;
use Semae\Pca\Application\Results\ResultadoProcessamentoUnidade;
use Semae\Pca\Application\ValueObjects\ContextoFinalizacaoProcessamentoUnidade;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Policies\ResultadoDecisaoProcessamentoPlano;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\PcaObservado;
use Semae\Pca\Domain\ValueObjects\Representante;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class FinalizadorProcessamentoUnidadeTest extends TestCase
{
    public function testDesfechoUnitarioDeveConterResultadoSemFalhaGlobal(): void
    {
        $resultado = ResultadoProcessamentoUnidade::semAlteracao($this->criarUnidade());

        $desfecho = DesfechoProcessamentoUnidade::resultadoUnitario($resultado);

        $this->assertSame(
            SituacaoDesfechoProcessamentoUnidade::RESULTADO_UNITARIO,
            $desfecho->getSituacao()
        );
        $this->assertSame($resultado, $desfecho->getResultadoUnitario());
        $this->assertNull($desfecho->getFalhaSistemica());
    }

    public function testFalhaSistemicaDevePermanecerGlobalSemFabricarResultadoUnitario(): void
    {
        $falha = new FalhaSegura('FINALIZACAO_INTERROMPIDA', 'Finalização interrompida.');

        $desfecho = DesfechoProcessamentoUnidade::falhaSistemica($falha);

        $this->assertSame(
            SituacaoDesfechoProcessamentoUnidade::FALHA_SISTEMICA,
            $desfecho->getSituacao()
        );
        $this->assertNull($desfecho->getResultadoUnitario());
        $this->assertSame($falha, $desfecho->getFalhaSistemica());
    }

    public function testContratoInternoDeveReceberContextoCompletoEDevolverDesfecho(): void
    {
        $contexto = $this->criarContextoFinalizacao();
        $resultado = ResultadoProcessamentoUnidade::semAlteracao($this->criarUnidade());
        $desfecho = DesfechoProcessamentoUnidade::resultadoUnitario($resultado);
        $finalizador = new FinalizadorProcessamentoUnidadeFake($desfecho);

        $retorno = $finalizador->finalizar($contexto);

        $this->assertSame($contexto, $finalizador->contextoRecebido);
        $this->assertSame($desfecho, $retorno);
    }

    private function criarContextoFinalizacao(): ContextoFinalizacaoProcessamentoUnidade
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidadeOrcamentaria = $this->criarUnidadeOrcamentaria();
        $identidade = new IdentidadePlano($unidadeOrcamentaria, $ano);
        $plano = new Plano(
            $identidade,
            2,
            new DateTimeImmutable('2026-07-16'),
            [],
            null
        );

        return new ContextoFinalizacaoProcessamentoUnidade(
            new ContextoUsuario(
                new Representante(709, 'Maria da Silva'),
                new Lotacao(159, 'Coordenadoria de Tecnologia')
            ),
            new SessaoPncpFakeFinalizador(),
            new Cnpj('12.345.678/0001-95'),
            UnidadeExternaPublicacao::individual($identidade),
            [$plano],
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente(),
            ResultadoDecisaoProcessamentoPlano::nenhumaAcao(),
            []
        );
    }

    private function criarUnidade(): UnidadeExternaPublicacao
    {
        return UnidadeExternaPublicacao::individual(new IdentidadePlano(
            $this->criarUnidadeOrcamentaria(),
            new AnoExecucao((int) date('Y') + 1)
        ));
    }

    private function criarUnidadeOrcamentaria(): UnidadeOrcamentaria
    {
        return new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria('uo-101'),
            new Lotacao(101, 'Diretoria Administrativa'),
            new Uasg('928344', 'SEMAE Piracicaba'),
            'Diretoria Administrativa'
        );
    }
}

final class SessaoPncpFakeFinalizador implements SessaoPncp
{
}

final class FinalizadorProcessamentoUnidadeFake implements FinalizadorProcessamentoUnidade
{
    public ?ContextoFinalizacaoProcessamentoUnidade $contextoRecebido = null;

    public function __construct(
        private readonly DesfechoProcessamentoUnidade $desfecho
    ) {}

    public function finalizar(
        ContextoFinalizacaoProcessamentoUnidade $contexto
    ): DesfechoProcessamentoUnidade {
        $this->contextoRecebido = $contexto;

        return $this->desfecho;
    }
}
