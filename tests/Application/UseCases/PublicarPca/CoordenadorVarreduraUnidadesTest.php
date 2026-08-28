<?php

declare(strict_types=1);

namespace Tests\Application\UseCases\PublicarPca;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Contracts\ProcessadorUnidade;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Results\DesfechoProcessamentoUnidade;
use Semae\Pca\Application\Results\ResultadoProcessamentoUnidade;
use Semae\Pca\Application\UseCases\PublicarPca\CoordenadorVarreduraUnidades;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Representante;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class CoordenadorVarreduraUnidadesTest extends TestCase
{
    public function testIndividualDeveContinuarDepoisDeBloqueioEFalhaReconciliavel(): void
    {
        $primeira = $this->criarUnidadeIndividual('uo-101', '928344');
        $segunda = $this->criarUnidadeIndividual('uo-102', '928345');
        $terceira = $this->criarUnidadeIndividual('uo-103', '928346');
        $bloqueado = ResultadoProcessamentoUnidade::bloqueado(
            $primeira,
            null,
            null,
            0,
            0,
            new FalhaSegura('UNIDADE_BLOQUEADA', 'Unidade bloqueada.')
        );
        $reconciliavel = ResultadoProcessamentoUnidade::falhaReconciliavel(
            $segunda,
            null,
            null,
            0,
            0,
            new FalhaSegura('OBSERVACAO_INCONCLUSIVA', 'Observação inconclusiva.')
        );
        $semAlteracao = ResultadoProcessamentoUnidade::semAlteracao($terceira);
        $processador = new ProcessadorUnidadeFakeVarredura([
            DesfechoProcessamentoUnidade::resultadoUnitario($bloqueado),
            DesfechoProcessamentoUnidade::resultadoUnitario($reconciliavel),
            DesfechoProcessamentoUnidade::resultadoUnitario($semAlteracao),
        ]);

        $resultado = $this->coordenador($processador)->processar(
            $this->criarContextoUsuario(),
            new SessaoPncpFakeVarredura(),
            new Cnpj('12.345.678/0001-95'),
            [$primeira, $segunda, $terceira]
        );

        $this->assertSame(
            [$bloqueado, $reconciliavel, $semAlteracao],
            $resultado->getResultadosUnitarios()
        );
        $this->assertFalse($resultado->foiInterrompida());
        $this->assertSame([$primeira, $segunda, $terceira], $processador->unidadesVisitadas);
        $this->assertSame(3, $resultado->getQuantidadeVisitadas());
    }

    public function testFalhaSistemicaDeveInterromperSemFabricarUnidadesNaoVisitadas(): void
    {
        $primeira = $this->criarUnidadeIndividual('uo-101', '928344');
        $segunda = $this->criarUnidadeIndividual('uo-102', '928345');
        $terceira = $this->criarUnidadeIndividual('uo-103', '928346');
        $resultadoPrimeira = ResultadoProcessamentoUnidade::semAlteracao($primeira);
        $falha = new FalhaSegura('PNCP_INDISPONIVEL', 'PNCP indisponível.');
        $processador = new ProcessadorUnidadeFakeVarredura([
            DesfechoProcessamentoUnidade::resultadoUnitario($resultadoPrimeira),
            DesfechoProcessamentoUnidade::falhaSistemica($falha),
            DesfechoProcessamentoUnidade::resultadoUnitario(
                ResultadoProcessamentoUnidade::semAlteracao($terceira)
            ),
        ]);

        $resultado = $this->coordenador($processador)->processar(
            $this->criarContextoUsuario(),
            new SessaoPncpFakeVarredura(),
            new Cnpj('12.345.678/0001-95'),
            [$primeira, $segunda, $terceira]
        );

        $this->assertTrue($resultado->foiInterrompida());
        $this->assertSame($falha, $resultado->getFalhaSistemica());
        $this->assertSame([$resultadoPrimeira], $resultado->getResultadosUnitarios());
        $this->assertSame([$primeira, $segunda], $processador->unidadesVisitadas);
        $this->assertSame(2, $resultado->getQuantidadeVisitadas());
    }

    public function testConsolidadoDeveProcessarSomenteAUnidadeConjunta(): void
    {
        $unidade = UnidadeExternaPublicacao::consolidada(
            new AnoExecucao((int) date('Y') + 1),
            new Uasg('986700', 'UASG Consolidadora')
        );
        $resultadoUnitario = ResultadoProcessamentoUnidade::semAlteracao($unidade);
        $processador = new ProcessadorUnidadeFakeVarredura([
            DesfechoProcessamentoUnidade::resultadoUnitario($resultadoUnitario),
        ]);

        $resultado = $this->coordenador($processador)->processar(
            $this->criarContextoUsuario(),
            new SessaoPncpFakeVarredura(),
            new Cnpj('12.345.678/0001-95'),
            [$unidade]
        );

        $this->assertSame([$resultadoUnitario], $resultado->getResultadosUnitarios());
        $this->assertSame([$unidade], $processador->unidadesVisitadas);
        $this->assertSame(1, $resultado->getQuantidadeVisitadas());
    }

    public function testConsolidadoComMaisDeUmaUnidadeDeveFalharSemVisitar(): void
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $primeira = UnidadeExternaPublicacao::consolidada(
            $ano,
            new Uasg('986700', 'UASG Consolidadora')
        );
        $segunda = UnidadeExternaPublicacao::consolidada(
            $ano,
            new Uasg('986701', 'Outra UASG')
        );
        $processador = new ProcessadorUnidadeFakeVarredura([]);

        $resultado = $this->coordenador($processador)->processar(
            $this->criarContextoUsuario(),
            new SessaoPncpFakeVarredura(),
            new Cnpj('12.345.678/0001-95'),
            [$primeira, $segunda]
        );

        $this->assertTrue($resultado->foiInterrompida());
        $this->assertSame(
            'VARREDURA_CONSOLIDADA_INCONSISTENTE',
            $resultado->getFalhaSistemica()?->getCodigo()
        );
        $this->assertSame([], $resultado->getResultadosUnitarios());
        $this->assertSame([], $processador->unidadesVisitadas);
        $this->assertSame(0, $resultado->getQuantidadeVisitadas());
    }

    public function testResultadoDeOutraUnidadeNaoDeveSerAceitoNemFabricado(): void
    {
        $esperada = $this->criarUnidadeIndividual('uo-101', '928344');
        $outra = $this->criarUnidadeIndividual('uo-102', '928345');
        $processador = new ProcessadorUnidadeFakeVarredura([
            DesfechoProcessamentoUnidade::resultadoUnitario(
                ResultadoProcessamentoUnidade::semAlteracao($outra)
            ),
        ]);

        $resultado = $this->coordenador($processador)->processar(
            $this->criarContextoUsuario(),
            new SessaoPncpFakeVarredura(),
            new Cnpj('12.345.678/0001-95'),
            [$esperada]
        );

        $this->assertTrue($resultado->foiInterrompida());
        $this->assertSame([], $resultado->getResultadosUnitarios());
        $this->assertSame(
            'RESULTADO_UNIDADE_INCOERENTE',
            $resultado->getFalhaSistemica()?->getCodigo()
        );
    }

    private function coordenador(
        ProcessadorUnidade $processador
    ): CoordenadorVarreduraUnidades {
        return new CoordenadorVarreduraUnidades($processador);
    }

    private function criarContextoUsuario(): ContextoUsuario
    {
        return new ContextoUsuario(
            new Representante(709, 'Maria da Silva'),
            new Lotacao(159, 'Coordenadoria de Tecnologia')
        );
    }

    private function criarUnidadeIndividual(
        string $idUo,
        string $uasg
    ): UnidadeExternaPublicacao {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidade = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria($idUo),
            new Lotacao(101, 'Diretoria Administrativa'),
            new Uasg($uasg, 'UASG da UO'),
            'Diretoria Administrativa'
        );

        return UnidadeExternaPublicacao::individual(new IdentidadePlano($unidade, $ano));
    }
}

final class SessaoPncpFakeVarredura implements SessaoPncp
{
}

final class ProcessadorUnidadeFakeVarredura implements ProcessadorUnidade
{
    /** @var UnidadeExternaPublicacao[] */
    public array $unidadesVisitadas = [];
    private int $indice = 0;

    /** @param DesfechoProcessamentoUnidade[] $desfechos */
    public function __construct(private readonly array $desfechos) {}

    public function processar(
        ContextoUsuario $contextoUsuario,
        SessaoPncp $sessao,
        Cnpj $cnpj,
        UnidadeExternaPublicacao $unidade
    ): DesfechoProcessamentoUnidade {
        $this->unidadesVisitadas[] = $unidade;

        return $this->desfechos[$this->indice++];
    }
}
