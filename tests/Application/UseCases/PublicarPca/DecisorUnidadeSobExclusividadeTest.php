<?php

declare(strict_types=1);

namespace Tests\Application\UseCases\PublicarPca;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Enums\SituacaoDesfechoProcessamentoUnidade;
use Semae\Pca\Application\Enums\SituacaoProcessamentoUnidade;
use Semae\Pca\Application\Ports\LeitorPncp;
use Semae\Pca\Application\Ports\UnidadeDeTrabalhoPort;
use Semae\Pca\Application\Results\DesfechoProcessamentoUnidade;
use Semae\Pca\Application\Results\ResultadoBuscaCandidatosPca;
use Semae\Pca\Application\Results\ResultadoFinalizacaoUnidadeTrabalho;
use Semae\Pca\Application\Results\ResultadoInicioUnidadeTrabalho;
use Semae\Pca\Application\Results\ResultadoObservacaoPca;
use Semae\Pca\Application\UseCases\PublicarPca\DecisorUnidadeSobExclusividade;
use Semae\Pca\Application\ValueObjects\ChaveUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\ContextoUnidadeComDecisao;
use Semae\Pca\Application\ValueObjects\ContextoUnidadeSobExclusividade;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\Demanda;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;
use Semae\Pca\Domain\Enums\SituacaoDemanda;
use Semae\Pca\Domain\Policies\PoliticaProcessamentoPlano;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\DecimalExato;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\PcaObservado;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class DecisorUnidadeSobExclusividadeTest extends TestCase
{
    public function testPublicadoDeveObservarSequencialConhecidoEDecidirPeloDomain(): void
    {
        $sequencial = new SequencialPca(77);
        $contexto = $this->criarContexto([1001, 1002], EstadoOperacionalPublicacao::publicado($sequencial));
        $observado = PcaObservado::ativo($sequencial, [1001]);
        $leitor = new LeitorPncpFakeDecisao(
            ResultadoObservacaoPca::encontrado($observado),
            ResultadoBuscaCandidatosPca::concluida([])
        );
        $unidadeTrabalho = new UnidadeTrabalhoFakeDecisao();
        $sessao = new SessaoPncpFakeDecisao();
        $cnpj = new Cnpj('12.345.678/0001-95');

        $resultado = $this->decisor($leitor, $unidadeTrabalho)->decidir(
            $sessao,
            $cnpj,
            $contexto
        );

        $this->assertInstanceOf(ContextoUnidadeComDecisao::class, $resultado);
        $this->assertSame($sessao, $resultado->getSessaoPncp());
        $this->assertSame($cnpj, $resultado->getCnpj());
        $this->assertSame($contexto, $resultado->getContextoSobExclusividade());
        $this->assertSame($observado, $resultado->getPcaObservado());
        $this->assertSame(
            DecisaoProcessamentoPlano::RETIFICAR,
            $resultado->getResultadoDecisao()->getDecisao()
        );
        $this->assertSame([1002], $resultado->getResultadoDecisao()->getDemandasAIncluir());
        $this->assertSame([], $resultado->getResultadoDecisao()->getDemandasAExcluir());
        $this->assertSame($sequencial, $leitor->sequencialConsultado);
        $this->assertSame(1, $leitor->observacoesConhecidas);
        $this->assertSame(0, $leitor->buscasCandidatos);
        $this->assertSame(0, $unidadeTrabalho->desfazimentos);
    }

    public function testAusenciaDefinitivaDoPcaConhecidoDevePermitirDecisaoPublicar(): void
    {
        $contexto = $this->criarContexto(
            [1001],
            EstadoOperacionalPublicacao::publicado(new SequencialPca(77))
        );
        $leitor = new LeitorPncpFakeDecisao(
            ResultadoObservacaoPca::ausenciaDefinitiva(),
            ResultadoBuscaCandidatosPca::concluida([])
        );

        $resultado = $this->decisor($leitor)->decidir(
            new SessaoPncpFakeDecisao(),
            new Cnpj('12.345.678/0001-95'),
            $contexto
        );

        $this->assertInstanceOf(ContextoUnidadeComDecisao::class, $resultado);
        $this->assertSame(
            DecisaoProcessamentoPlano::PUBLICAR,
            $resultado->getResultadoDecisao()->getDecisao()
        );
    }

    #[DataProvider('estadosSemVinculoAtivo')]
    public function testSemVinculoAtivoENenhumCandidatoDeveObservarAusencia(
        string $factoryEstado
    ): void {
        $estado = $factoryEstado === 'naoPublicado'
            ? EstadoOperacionalPublicacao::naoPublicado()
            : EstadoOperacionalPublicacao::excluido(new SequencialPca(70));
        $contexto = $this->criarContexto([1001], $estado);
        $leitor = new LeitorPncpFakeDecisao(
            ResultadoObservacaoPca::ausenciaDefinitiva(),
            ResultadoBuscaCandidatosPca::concluida([])
        );

        $resultado = $this->decisor($leitor)->decidir(
            new SessaoPncpFakeDecisao(),
            new Cnpj('12.345.678/0001-95'),
            $contexto
        );

        $this->assertInstanceOf(ContextoUnidadeComDecisao::class, $resultado);
        $this->assertFalse($resultado->getPcaObservado()->estaAtivo());
        $this->assertSame(
            DecisaoProcessamentoPlano::PUBLICAR,
            $resultado->getResultadoDecisao()->getDecisao()
        );
        $this->assertSame(0, $leitor->observacoesConhecidas);
        $this->assertSame(1, $leitor->buscasCandidatos);
    }

    public function testQualquerCandidatoSemVinculoDeveBloquearSemAdocaoAutomatica(): void
    {
        $candidato = PcaObservado::ativo(new SequencialPca(91), [1001]);
        $leitor = new LeitorPncpFakeDecisao(
            ResultadoObservacaoPca::ausenciaDefinitiva(),
            ResultadoBuscaCandidatosPca::concluida([$candidato])
        );
        $unidadeTrabalho = new UnidadeTrabalhoFakeDecisao();

        $resultado = $this->decisor($leitor, $unidadeTrabalho)->decidir(
            new SessaoPncpFakeDecisao(),
            new Cnpj('12.345.678/0001-95'),
            $this->criarContexto([1001], EstadoOperacionalPublicacao::naoPublicado())
        );

        $this->assertBloqueadoComCodigo($resultado, 'CANDIDATO_EXTERNO_SEM_VINCULO');
        $this->assertNull($resultado->getResultadoUnitario()?->getSequencialPca());
        $this->assertSame(1, $unidadeTrabalho->desfazimentos);
    }

    #[DataProvider('consultasInconclusivas')]
    public function testConsultaInconclusivaDeveProduzirFalhaReconciliavel(
        string $origem
    ): void {
        $falha = new FalhaSegura('OBSERVACAO_INCONCLUSIVA', 'Observação inconclusiva.');
        $leitor = new LeitorPncpFakeDecisao(
            ResultadoObservacaoPca::inconclusiva($falha),
            ResultadoBuscaCandidatosPca::inconclusiva($falha)
        );
        $unidadeTrabalho = new UnidadeTrabalhoFakeDecisao();
        $estado = $origem === 'conhecido'
            ? EstadoOperacionalPublicacao::publicado(new SequencialPca(77))
            : EstadoOperacionalPublicacao::naoPublicado();

        $resultado = $this->decisor($leitor, $unidadeTrabalho)->decidir(
            new SessaoPncpFakeDecisao(),
            new Cnpj('12.345.678/0001-95'),
            $this->criarContexto([1001], $estado)
        );

        $this->assertInstanceOf(DesfechoProcessamentoUnidade::class, $resultado);
        $this->assertSame(
            SituacaoProcessamentoUnidade::FALHA_RECONCILIAVEL,
            $resultado->getResultadoUnitario()?->getSituacao()
        );
        $this->assertSame($falha, $resultado->getResultadoUnitario()?->getFalha());
        $this->assertSame(1, $unidadeTrabalho->desfazimentos);
    }

    #[DataProvider('consultasComConflito')]
    public function testConflitoDeConsultaDeveBloquear(string $origem): void
    {
        $falha = new FalhaSegura('OBSERVACAO_CONFLITANTE', 'Observação conflitante.');
        $leitor = new LeitorPncpFakeDecisao(
            ResultadoObservacaoPca::conflito($falha),
            ResultadoBuscaCandidatosPca::conflito($falha)
        );
        $unidadeTrabalho = new UnidadeTrabalhoFakeDecisao();
        $estado = $origem === 'conhecido'
            ? EstadoOperacionalPublicacao::publicado(new SequencialPca(77))
            : EstadoOperacionalPublicacao::naoPublicado();

        $resultado = $this->decisor($leitor, $unidadeTrabalho)->decidir(
            new SessaoPncpFakeDecisao(),
            new Cnpj('12.345.678/0001-95'),
            $this->criarContexto([1001], $estado)
        );

        $this->assertBloqueadoComCodigo($resultado, 'OBSERVACAO_CONFLITANTE');
        $this->assertSame(1, $unidadeTrabalho->desfazimentos);
    }

    #[DataProvider('consultasComFalhaSistemica')]
    public function testFalhaSistemicaDeConsultaDeveDesfazerEInterromper(
        string $origem
    ): void {
        $falha = new FalhaSegura('PNCP_INDISPONIVEL', 'PNCP indisponível.');
        $leitor = new LeitorPncpFakeDecisao(
            ResultadoObservacaoPca::falhaSistemica($falha),
            ResultadoBuscaCandidatosPca::falhaSistemica($falha)
        );
        $unidadeTrabalho = new UnidadeTrabalhoFakeDecisao();
        $estado = $origem === 'conhecido'
            ? EstadoOperacionalPublicacao::publicado(new SequencialPca(77))
            : EstadoOperacionalPublicacao::naoPublicado();

        $resultado = $this->decisor($leitor, $unidadeTrabalho)->decidir(
            new SessaoPncpFakeDecisao(),
            new Cnpj('12.345.678/0001-95'),
            $this->criarContexto([1001], $estado)
        );

        $this->assertSame(
            SituacaoDesfechoProcessamentoUnidade::FALHA_SISTEMICA,
            $resultado->getSituacao()
        );
        $this->assertSame($falha, $resultado->getFalhaSistemica());
        $this->assertSame(1, $unidadeTrabalho->desfazimentos);
    }

    public function testConflitoDetectadoPelaPoliticaDeveBloquearSemDuplicarRegra(): void
    {
        $leitor = new LeitorPncpFakeDecisao(
            ResultadoObservacaoPca::encontrado(
                PcaObservado::ativo(new SequencialPca(78), [1001])
            ),
            ResultadoBuscaCandidatosPca::concluida([])
        );
        $unidadeTrabalho = new UnidadeTrabalhoFakeDecisao();

        $resultado = $this->decisor($leitor, $unidadeTrabalho)->decidir(
            new SessaoPncpFakeDecisao(),
            new Cnpj('12.345.678/0001-95'),
            $this->criarContexto(
                [1001],
                EstadoOperacionalPublicacao::publicado(new SequencialPca(77))
            )
        );

        $this->assertBloqueadoComCodigo($resultado, 'DECISAO_DOMINIO_CONFLITANTE');
        $this->assertSame(1, $unidadeTrabalho->desfazimentos);
    }

    public function testPoliticaDevePoderDecidirExcluirPca(): void
    {
        $sequencial = new SequencialPca(77);
        $leitor = new LeitorPncpFakeDecisao(
            ResultadoObservacaoPca::encontrado(PcaObservado::ativo($sequencial, [1001])),
            ResultadoBuscaCandidatosPca::concluida([])
        );

        $resultado = $this->decisor($leitor)->decidir(
            new SessaoPncpFakeDecisao(),
            new Cnpj('12.345.678/0001-95'),
            $this->criarContexto([], EstadoOperacionalPublicacao::publicado($sequencial))
        );

        $this->assertInstanceOf(ContextoUnidadeComDecisao::class, $resultado);
        $this->assertSame(
            DecisaoProcessamentoPlano::EXCLUIR_PCA,
            $resultado->getResultadoDecisao()->getDecisao()
        );
    }

    public function testPoliticaDevePoderDecidirNenhumaAcao(): void
    {
        $sequencial = new SequencialPca(77);
        $leitor = new LeitorPncpFakeDecisao(
            ResultadoObservacaoPca::encontrado(PcaObservado::ativo($sequencial, [1001])),
            ResultadoBuscaCandidatosPca::concluida([])
        );

        $resultado = $this->decisor($leitor)->decidir(
            new SessaoPncpFakeDecisao(),
            new Cnpj('12.345.678/0001-95'),
            $this->criarContexto([1001], EstadoOperacionalPublicacao::publicado($sequencial))
        );

        $this->assertInstanceOf(ContextoUnidadeComDecisao::class, $resultado);
        $this->assertSame(
            DecisaoProcessamentoPlano::NENHUMA_ACAO,
            $resultado->getResultadoDecisao()->getDecisao()
        );
    }

    public function testExcecaoDoLeitorDeveVirarFalhaSistemicaSegura(): void
    {
        $leitor = new LeitorPncpFakeDecisao(
            ResultadoObservacaoPca::ausenciaDefinitiva(),
            ResultadoBuscaCandidatosPca::concluida([])
        );
        $leitor->falhaObservacao = new RuntimeException('detalhe interno');
        $unidadeTrabalho = new UnidadeTrabalhoFakeDecisao();

        $resultado = $this->decisor($leitor, $unidadeTrabalho)->decidir(
            new SessaoPncpFakeDecisao(),
            new Cnpj('12.345.678/0001-95'),
            $this->criarContexto(
                [1001],
                EstadoOperacionalPublicacao::publicado(new SequencialPca(77))
            )
        );

        $this->assertSame(
            SituacaoDesfechoProcessamentoUnidade::FALHA_SISTEMICA,
            $resultado->getSituacao()
        );
        $this->assertSame('OBSERVACAO_PNCP_FALHOU', $resultado->getFalhaSistemica()?->getCodigo());
        $this->assertSame(1, $unidadeTrabalho->desfazimentos);
    }

    public function testFalhaNoRollbackDevePrevalecerSobreInconclusao(): void
    {
        $falhaRollback = new FalhaSegura('ROLLBACK_FALHOU', 'Rollback falhou.');
        $leitor = new LeitorPncpFakeDecisao(
            ResultadoObservacaoPca::inconclusiva(
                new FalhaSegura('OBSERVACAO_INCONCLUSIVA', 'Observação inconclusiva.')
            ),
            ResultadoBuscaCandidatosPca::concluida([])
        );
        $unidadeTrabalho = new UnidadeTrabalhoFakeDecisao(
            ResultadoFinalizacaoUnidadeTrabalho::falhaSistemica($falhaRollback)
        );

        $resultado = $this->decisor($leitor, $unidadeTrabalho)->decidir(
            new SessaoPncpFakeDecisao(),
            new Cnpj('12.345.678/0001-95'),
            $this->criarContexto(
                [1001],
                EstadoOperacionalPublicacao::publicado(new SequencialPca(77))
            )
        );

        $this->assertSame(
            SituacaoDesfechoProcessamentoUnidade::FALHA_SISTEMICA,
            $resultado->getSituacao()
        );
        $this->assertSame($falhaRollback, $resultado->getFalhaSistemica());
    }

    /** @return array<string, array{string}> */
    public static function estadosSemVinculoAtivo(): array
    {
        return [
            'não publicado' => ['naoPublicado'],
            'excluído' => ['excluido'],
        ];
    }

    /** @return array<string, array{string}> */
    public static function consultasInconclusivas(): array
    {
        return ['PCA conhecido' => ['conhecido'], 'candidatos' => ['candidatos']];
    }

    /** @return array<string, array{string}> */
    public static function consultasComConflito(): array
    {
        return self::consultasInconclusivas();
    }

    /** @return array<string, array{string}> */
    public static function consultasComFalhaSistemica(): array
    {
        return self::consultasInconclusivas();
    }

    private function decisor(
        LeitorPncp $leitor,
        ?UnidadeDeTrabalhoPort $unidadeTrabalho = null
    ): DecisorUnidadeSobExclusividade {
        return new DecisorUnidadeSobExclusividade(
            $leitor,
            new PoliticaProcessamentoPlano(),
            $unidadeTrabalho ?? new UnidadeTrabalhoFakeDecisao()
        );
    }

    private function assertBloqueadoComCodigo(
        DesfechoProcessamentoUnidade $resultado,
        string $codigo
    ): void {
        $this->assertSame(
            SituacaoDesfechoProcessamentoUnidade::RESULTADO_UNITARIO,
            $resultado->getSituacao()
        );
        $this->assertSame(
            SituacaoProcessamentoUnidade::BLOQUEADO,
            $resultado->getResultadoUnitario()?->getSituacao()
        );
        $this->assertSame($codigo, $resultado->getResultadoUnitario()?->getFalha()?->getCodigo());
    }

    /** @param int[] $idsDemandas */
    private function criarContexto(
        array $idsDemandas,
        EstadoOperacionalPublicacao $estado
    ): ContextoUnidadeSobExclusividade {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidadeOrcamentaria = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria('uo-101'),
            new Lotacao(101, 'Diretoria Administrativa'),
            new Uasg('928344', 'SEMAE Piracicaba'),
            'Diretoria Administrativa'
        );
        $identidade = new IdentidadePlano($unidadeOrcamentaria, $ano);
        $demandas = array_map(
            fn (int $id): Demanda => $this->criarDemanda($id, $unidadeOrcamentaria, $ano),
            $idsDemandas
        );
        $plano = new Plano(
            $identidade,
            2,
            new DateTimeImmutable('2026-07-16'),
            $demandas,
            null
        );

        return new ContextoUnidadeSobExclusividade(
            UnidadeExternaPublicacao::individual($identidade),
            [$plano],
            $estado
        );
    }

    private function criarDemanda(
        int $id,
        UnidadeOrcamentaria $unidade,
        AnoExecucao $ano
    ): Demanda {
        return new Demanda(
            $id,
            SituacaoDemanda::HOMOLOGADA,
            159,
            $unidade,
            $ano,
            new DateTimeImmutable($ano->getValor() . '-03-15'),
            1,
            'ITEM ' . $id,
            'UNIDADE',
            1,
            new DecimalExato('100.00'),
            new DecimalExato('100.00'),
            2,
            (string) $id,
            '98',
            'Grupo 98',
            'N'
        );
    }
}

final class SessaoPncpFakeDecisao implements SessaoPncp
{
}

final class LeitorPncpFakeDecisao implements LeitorPncp
{
    public int $observacoesConhecidas = 0;
    public int $buscasCandidatos = 0;
    public ?SequencialPca $sequencialConsultado = null;
    public ?RuntimeException $falhaObservacao = null;

    public function __construct(
        private readonly ResultadoObservacaoPca $observacao,
        private readonly ResultadoBuscaCandidatosPca $candidatos
    ) {}

    public function observarPcaConhecido(
        SessaoPncp $sessao,
        Cnpj $cnpj,
        UnidadeExternaPublicacao $unidade,
        SequencialPca $sequencialConhecido
    ): ResultadoObservacaoPca {
        $this->observacoesConhecidas++;
        $this->sequencialConsultado = $sequencialConhecido;

        if ($this->falhaObservacao !== null) {
            throw $this->falhaObservacao;
        }

        return $this->observacao;
    }

    public function localizarCandidatos(
        SessaoPncp $sessao,
        Cnpj $cnpj,
        UnidadeExternaPublicacao $unidade
    ): ResultadoBuscaCandidatosPca {
        $this->buscasCandidatos++;

        if ($this->falhaObservacao !== null) {
            throw $this->falhaObservacao;
        }

        return $this->candidatos;
    }
}

final class UnidadeTrabalhoFakeDecisao implements UnidadeDeTrabalhoPort
{
    public int $desfazimentos = 0;

    public function __construct(
        private readonly ?ResultadoFinalizacaoUnidadeTrabalho $resultadoDesfazimento = null
    ) {}

    public function iniciar(ChaveUnidadeTrabalho $chave): ResultadoInicioUnidadeTrabalho
    {
        throw new RuntimeException('Este estágio não deve iniciar outra unidade de trabalho.');
    }

    public function confirmar(): ResultadoFinalizacaoUnidadeTrabalho
    {
        throw new RuntimeException('Este estágio não deve confirmar a unidade de trabalho.');
    }

    public function desfazer(): ResultadoFinalizacaoUnidadeTrabalho
    {
        $this->desfazimentos++;

        return $this->resultadoDesfazimento ?? ResultadoFinalizacaoUnidadeTrabalho::concluida();
    }
}
