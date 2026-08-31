<?php

declare(strict_types=1);

namespace Tests\Application\UseCases\PublicarPca;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Semae\Pca\Application\Commands\ComandoExcluirItensPca;
use Semae\Pca\Application\Commands\ComandoExcluirPca;
use Semae\Pca\Application\Commands\ComandoInserirItensPca;
use Semae\Pca\Application\Commands\ComandoPublicarPca;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Enums\SituacaoPublicacaoPca;
use Semae\Pca\Application\Ports\AutenticadorPncpPort;
use Semae\Pca\Application\Ports\ContextoUsuarioPort;
use Semae\Pca\Application\Ports\EscritorPncpPort;
use Semae\Pca\Application\Ports\EstadoOperacionalPublicacaoPort;
use Semae\Pca\Application\Ports\EstrategiaPublicacaoPort;
use Semae\Pca\Application\Ports\LeitorPncpPort;
use Semae\Pca\Application\Ports\RegistroAuditoriaPort;
use Semae\Pca\Application\Ports\UnidadeDeTrabalhoPort;
use Semae\Pca\Application\Results\PublicarPcaResponse;
use Semae\Pca\Application\Results\ResultadoAdocaoEstrategia;
use Semae\Pca\Application\Results\ResultadoAutenticacaoPncp;
use Semae\Pca\Application\Results\ResultadoBuscaCandidatosPca;
use Semae\Pca\Application\Results\ResultadoConsultaEstadoOperacional;
use Semae\Pca\Application\Results\ResultadoConsultaEstrategia;
use Semae\Pca\Application\Results\ResultadoEscritaPncp;
use Semae\Pca\Application\Results\ResultadoFinalizacaoUnidadeTrabalho;
use Semae\Pca\Application\Results\ResultadoInicioUnidadeTrabalho;
use Semae\Pca\Application\Results\ResultadoObservacaoPca;
use Semae\Pca\Application\Results\ResultadoPersistenciaLocal;
use Semae\Pca\Application\Results\ResultadoPublicacaoPncp;
use Semae\Pca\Application\Results\ResultadoResolucaoContextoUsuario;
use Semae\Pca\Application\UseCases\PublicarPca\CoordenadorVarreduraUnidades;
use Semae\Pca\Application\UseCases\PublicarPca\DecisorUnidadeSobExclusividade;
use Semae\Pca\Application\UseCases\PublicarPca\DespachadorComandosUnidade;
use Semae\Pca\Application\UseCases\PublicarPca\ExecutorComandosUnidade;
use Semae\Pca\Application\UseCases\PublicarPca\FinalizadorProcessamentoUnidadeInterno;
use Semae\Pca\Application\UseCases\PublicarPca\PreparadorExecucaoPublicacaoPca;
use Semae\Pca\Application\UseCases\PublicarPca\PreparadorUnidadeSobExclusividade;
use Semae\Pca\Application\UseCases\PublicarPca\ProcessadorUnidadeInterno;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaRequest;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaUseCase;
use Semae\Pca\Application\ValueObjects\ChaveUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Application\ValueObjects\EstrategiaPublicacao;
use Semae\Pca\Application\ValueObjects\IntencaoRegistroHistorico;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\Demanda;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\SituacaoDemanda;
use Semae\Pca\Domain\Policies\PoliticaProcessamentoPlano;
use Semae\Pca\Domain\Ports\PlanoRepository;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\DecimalExato;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\PcaObservado;
use Semae\Pca\Domain\ValueObjects\Representante;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;

/**
 * Objetivo: Demonstrar que o grafo real da Application funciona ponta a ponta.
 */
final class FluxoConcretoApplicationTest extends TestCase
{
    public function testDevePublicarPontaAPontaComGrafoConcreto(): void
    {
        $cenario = CenarioFluxoConcreto::individual([1001]);
        $cenario->estadoInicial = EstadoOperacionalPublicacao::naoPublicado();
        $cenario->observacoes = [ResultadoObservacaoPca::encontrado(
            PcaObservado::ativo(new SequencialPca(88), [1001])
        )];

        $response = $this->executar($cenario);

        $this->assertConcluidaComOperacoes($response, ['publicar'], $cenario);
        $this->assertSame(1, $cenario->auditoria->registros);
    }

    public function testDeveRetificarExcluindoAntesDeInserirComGrafoConcreto(): void
    {
        $cenario = CenarioFluxoConcreto::individual([1002]);
        $sequencial = new SequencialPca(77);
        $cenario->estadoInicial = EstadoOperacionalPublicacao::publicado($sequencial);
        $cenario->observacoes = [
            ResultadoObservacaoPca::encontrado(PcaObservado::ativo($sequencial, [1001])),
            ResultadoObservacaoPca::encontrado(PcaObservado::ativo($sequencial, [1002])),
        ];

        $response = $this->executar($cenario);

        $this->assertConcluidaComOperacoes(
            $response,
            ['excluir_itens', 'inserir_itens'],
            $cenario
        );
        $this->assertSame(1, $cenario->auditoria->registros);
    }

    /** @regra RN-PUB-003 */
    public function testDeveExcluirPcaIndividualComJustificativaInstitucionalFixa(): void
    {
        $cenario = CenarioFluxoConcreto::individual([]);
        $sequencial = new SequencialPca(77);
        $cenario->estadoInicial = EstadoOperacionalPublicacao::publicado($sequencial);
        $cenario->observacoes = [
            ResultadoObservacaoPca::encontrado(PcaObservado::ativo($sequencial, [1001])),
            ResultadoObservacaoPca::ausenciaDefinitiva(),
        ];
        $response = $this->executar($cenario);

        $this->assertConcluidaComOperacoes($response, ['excluir_pca'], $cenario);
        $this->assertSame(
            'Exclusão integral do PCA por ausência de demandas vigentes no SGI.',
            $cenario->escritor->comandoExcluirPca?->getJustificativa()
        );
    }

    /** @regra RN-PUB-003 @regra RN-PUB-010 */
    public function testDeveExcluirPcaConsolidadoComJustificativaInstitucionalFixa(): void
    {
        $cenario = CenarioFluxoConcreto::consolidado([
            'uo-101' => [],
            'uo-102' => [],
        ]);
        $sequencial = new SequencialPca(77);
        $cenario->estadoInicial = EstadoOperacionalPublicacao::publicado($sequencial);
        $cenario->observacoes = [
            ResultadoObservacaoPca::encontrado(PcaObservado::ativo($sequencial, [1001, 1002])),
            ResultadoObservacaoPca::ausenciaDefinitiva(),
        ];
        $response = $this->executar($cenario);

        $this->assertConcluidaComOperacoes($response, ['excluir_pca'], $cenario);
        $this->assertSame(
            'Exclusão integral do PCA por ausência de demandas vigentes no SGI.',
            $cenario->escritor->comandoExcluirPca?->getJustificativa()
        );
        $this->assertCount(2, $cenario->repository->confirmados);
    }

    public function testDevePublicarModoConsolidadoComoUmaUnidade(): void
    {
        $cenario = CenarioFluxoConcreto::consolidado([
            'uo-101' => [1001],
            'uo-102' => [1002],
        ]);
        $cenario->estadoInicial = EstadoOperacionalPublicacao::naoPublicado();
        $cenario->observacoes = [ResultadoObservacaoPca::encontrado(
            PcaObservado::ativo(new SequencialPca(88), [1001, 1002])
        )];

        $response = $this->executar($cenario);

        $this->assertConcluidaComOperacoes($response, ['publicar'], $cenario);
        $this->assertCount(1, $response->getResultadosUnitarios());
        $this->assertCount(2, $cenario->repository->confirmados);
    }

    /**
     * é praticamente um futuro Composition Root em miniatura para testes
     */
    private function executar(CenarioFluxoConcreto $cenario): PublicarPcaResponse
    {
        $politica = new PoliticaProcessamentoPlano();
        $executor = new ExecutorComandosUnidade(
            $cenario->escritor,
            $cenario->unidadeTrabalho
        );
        $finalizador = new FinalizadorProcessamentoUnidadeInterno(
            $cenario->leitor,
            $politica,
            $executor,
            $cenario->repository,
            $cenario->estado,
            $cenario->auditoria,
            $cenario->unidadeTrabalho,
            new NullLogger()
        );
        $processador = new ProcessadorUnidadeInterno(
            new PreparadorUnidadeSobExclusividade(
                $cenario->unidadeTrabalho,
                $cenario->repository,
                $cenario->estado
            ),
            new DecisorUnidadeSobExclusividade(
                $cenario->leitor,
                $politica,
                $cenario->unidadeTrabalho
            ),
            new DespachadorComandosUnidade(
                $executor,
                $finalizador,
                $cenario->unidadeTrabalho
            )
        );
        $useCase = new PublicarPcaUseCase(
            new PreparadorExecucaoPublicacaoPca(
                $cenario->contexto,
                $cenario->repository,
                $cenario->estrategia,
                $cenario->unidadeTrabalho,
                $cenario->autenticador
            ),
            new CoordenadorVarreduraUnidades($processador)
        );

        return $useCase->executar(
            new PublicarPcaRequest($cenario->cnpj, $cenario->ano),
            new CorrelacaoExecucao('fluxo-concreto')
        );
    }

    /** @param string[] $operacoes */
    private function assertConcluidaComOperacoes(
        PublicarPcaResponse $response,
        array $operacoes,
        CenarioFluxoConcreto $cenario
    ): void {
        $this->assertSame(SituacaoPublicacaoPca::CONCLUIDA, $response->getSituacao());
        $this->assertSame($operacoes, $cenario->escritor->operacoes);
        $this->assertSame(2, $cenario->unidadeTrabalho->inicios);
        $this->assertSame(2, $cenario->unidadeTrabalho->confirmacoes);
        $this->assertSame(0, $cenario->unidadeTrabalho->desfazimentos);
    }
}

/**
 * centraliza os dados e doubles. Monta um mundo externo controlado para cada teste, criando planos e demandas.
 */
final class CenarioFluxoConcreto
{
    public readonly AnoExecucao $ano;
    public readonly Cnpj $cnpj;
    /** @var IdentidadePlano[] */
    public readonly array $identidades;
    public EstadoOperacionalPublicacao $estadoInicial;
    /** @var ResultadoObservacaoPca[] */
    public array $observacoes = [];
    public readonly ContextoFluxoConcreto $contexto;
    public readonly RepositoryFluxoConcreto $repository;
    public readonly EstrategiaFluxoConcreto $estrategia;
    public readonly UnidadeTrabalhoFluxoConcreto $unidadeTrabalho;
    public readonly AutenticadorFluxoConcreto $autenticador;
    public readonly EstadoFluxoConcreto $estado;
    public readonly LeitorFluxoConcreto $leitor;
    public readonly EscritorFluxoConcreto $escritor;
    public readonly AuditoriaFluxoConcreto $auditoria;

    /** @param array<string, int[]> $idsPorUo */
    private function __construct(array $idsPorUo, EstrategiaPublicacao $estrategia)
    {
        $this->ano = new AnoExecucao((int) date('Y') + 1);
        $this->cnpj = new Cnpj('12.345.678/0001-95');
        $planos = [];
        $identidades = [];
        $indice = 0;

        foreach ($idsPorUo as $idUo => $idsDemandas) {
            $indice++;
            $uo = new UnidadeOrcamentaria(
                new IdentidadeUnidadeOrcamentaria($idUo),
                new Lotacao(100 + $indice, 'Lotação ' . $indice),
                new Uasg((string) (928343 + $indice), 'UASG ' . $indice),
                'Unidade Orçamentária ' . $indice
            );
            $identidade = new IdentidadePlano($uo, $this->ano);
            $identidades[] = $identidade;
            $planos[$idUo] = new Plano(
                $identidade,
                2,
                new DateTimeImmutable('2026-07-16'),
                array_map(
                    fn (int $id): Demanda => self::demanda($id, $uo, $this->ano),
                    $idsDemandas
                ),
                null
            );
        }

        $this->identidades = $identidades;
        $this->estadoInicial = EstadoOperacionalPublicacao::naoPublicado();
        $this->contexto = new ContextoFluxoConcreto();
        $this->repository = new RepositoryFluxoConcreto($identidades, $planos);
        $this->estrategia = new EstrategiaFluxoConcreto($estrategia);
        $this->unidadeTrabalho = new UnidadeTrabalhoFluxoConcreto();
        $this->autenticador = new AutenticadorFluxoConcreto();
        $this->estado = new EstadoFluxoConcreto($this);
        $this->leitor = new LeitorFluxoConcreto($this);
        $this->escritor = new EscritorFluxoConcreto();
        $this->auditoria = new AuditoriaFluxoConcreto();
    }

    /** @param int[] $idsDemandas */
    public static function individual(array $idsDemandas): self
    {
        return new self(['uo-101' => $idsDemandas], EstrategiaPublicacao::individual());
    }

    /** @param array<string, int[]> $idsPorUo */
    public static function consolidado(array $idsPorUo): self
    {
        return new self(
            $idsPorUo,
            EstrategiaPublicacao::consolidada(new Uasg('928300', 'UASG Consolidadora'))
        );
    }

    private static function demanda(
        int $id,
        UnidadeOrcamentaria $uo,
        AnoExecucao $ano
    ): Demanda {
        return new Demanda(
            $id,
            SituacaoDemanda::HOMOLOGADA,
            159,
            $uo,
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

final class SessaoFluxoConcreto implements SessaoPncp {}

final class ContextoFluxoConcreto implements ContextoUsuarioPort
{
    public function resolver(): ResultadoResolucaoContextoUsuario
    {
        return ResultadoResolucaoContextoUsuario::resolvido(new ContextoUsuario(
            new Representante(709, 'Maria da Silva'),
            new Lotacao(159, 'Coordenadoria de Tecnologia')
        ));
    }
}

final class RepositoryFluxoConcreto implements PlanoRepository
{
    /** @var Plano[] */
    public array $confirmados = [];

    /**
     * @param IdentidadePlano[] $identidades
     * @param array<string, Plano> $planos
     */
    public function __construct(
        private readonly array $identidades,
        private readonly array $planos
    ) {}

    public function listarIdentidades(AnoExecucao $anoExecucao): array
    {
        return $this->identidades;
    }

    public function reconstruirFotografiaVigente(IdentidadePlano $identidade): Plano
    {
        $id = $identidade->getUnidadeOrcamentaria()->getIdentidade()->getValor();

        return $this->planos[$id];
    }

    public function confirmarSincronizacao(Plano $plano): void
    {
        $this->confirmados[] = $plano;
    }
}

final class EstrategiaFluxoConcreto implements EstrategiaPublicacaoPort
{
    public function __construct(private readonly EstrategiaPublicacao $estrategia) {}

    public function consultarCorrente(AnoExecucao $anoExecucao): ResultadoConsultaEstrategia
    {
        return ResultadoConsultaEstrategia::disponivel($this->estrategia);
    }

    public function consultarAdotada(AnoExecucao $anoExecucao): ResultadoConsultaEstrategia
    {
        return ResultadoConsultaEstrategia::disponivel($this->estrategia);
    }

    public function adotar(
        AnoExecucao $anoExecucao,
        EstrategiaPublicacao $estrategia
    ): ResultadoAdocaoEstrategia {
        return ResultadoAdocaoEstrategia::adotada();
    }
}

final class UnidadeTrabalhoFluxoConcreto implements UnidadeDeTrabalhoPort
{
    public int $inicios = 0;
    public int $confirmacoes = 0;
    public int $desfazimentos = 0;

    public function iniciar(ChaveUnidadeTrabalho $chave): ResultadoInicioUnidadeTrabalho
    {
        $this->inicios++;

        return ResultadoInicioUnidadeTrabalho::iniciada();
    }

    public function confirmar(): ResultadoFinalizacaoUnidadeTrabalho
    {
        $this->confirmacoes++;

        return ResultadoFinalizacaoUnidadeTrabalho::concluida();
    }

    public function desfazer(): ResultadoFinalizacaoUnidadeTrabalho
    {
        $this->desfazimentos++;

        return ResultadoFinalizacaoUnidadeTrabalho::concluida();
    }
}

final class AutenticadorFluxoConcreto implements AutenticadorPncpPort
{
    public function autenticar(): ResultadoAutenticacaoPncp
    {
        return ResultadoAutenticacaoPncp::autenticada(new SessaoFluxoConcreto());
    }
}

final class EstadoFluxoConcreto implements EstadoOperacionalPublicacaoPort
{
    /** @var EstadoOperacionalPublicacao[] */
    public array $registrados = [];

    public function __construct(private readonly CenarioFluxoConcreto $cenario) {}

    public function consultar(
        UnidadeExternaPublicacao $unidade
    ): ResultadoConsultaEstadoOperacional {
        return ResultadoConsultaEstadoOperacional::disponivel($this->cenario->estadoInicial);
    }

    public function registrar(
        UnidadeExternaPublicacao $unidade,
        EstadoOperacionalPublicacao $estado
    ): ResultadoPersistenciaLocal {
        $this->registrados[] = $estado;

        return ResultadoPersistenciaLocal::concluida();
    }
}

final class LeitorFluxoConcreto implements LeitorPncpPort
{
    public function __construct(private readonly CenarioFluxoConcreto $cenario) {}

    public function observarPcaConhecido(
        SessaoPncp $sessao,
        Cnpj $cnpj,
        UnidadeExternaPublicacao $unidade,
        SequencialPca $sequencialConhecido
    ): ResultadoObservacaoPca {
        return array_shift($this->cenario->observacoes)
            ?? ResultadoObservacaoPca::ausenciaDefinitiva();
    }

    public function localizarCandidatos(
        SessaoPncp $sessao,
        Cnpj $cnpj,
        UnidadeExternaPublicacao $unidade
    ): ResultadoBuscaCandidatosPca {
        return ResultadoBuscaCandidatosPca::concluida([]);
    }
}

final class EscritorFluxoConcreto implements EscritorPncpPort
{
    /** @var string[] */
    public array $operacoes = [];
    public ?ComandoExcluirPca $comandoExcluirPca = null;

    public function publicar(
        SessaoPncp $sessao,
        ComandoPublicarPca $comando
    ): ResultadoPublicacaoPncp {
        $this->operacoes[] = 'publicar';

        return ResultadoPublicacaoPncp::aceita(new SequencialPca(88));
    }

    public function inserirItens(
        SessaoPncp $sessao,
        ComandoInserirItensPca $comando
    ): ResultadoEscritaPncp {
        $this->operacoes[] = 'inserir_itens';

        return ResultadoEscritaPncp::aceita();
    }

    public function excluirItens(
        SessaoPncp $sessao,
        ComandoExcluirItensPca $comando
    ): ResultadoEscritaPncp {
        $this->operacoes[] = 'excluir_itens';

        return ResultadoEscritaPncp::aceita();
    }

    public function excluirPca(
        SessaoPncp $sessao,
        ComandoExcluirPca $comando
    ): ResultadoEscritaPncp {
        $this->operacoes[] = 'excluir_pca';
        $this->comandoExcluirPca = $comando;

        return ResultadoEscritaPncp::aceita();
    }
}

final class AuditoriaFluxoConcreto implements RegistroAuditoriaPort
{
    public int $registros = 0;

    public function registrar(
        IntencaoRegistroHistorico $intencao
    ): ResultadoPersistenciaLocal {
        $this->registros++;

        return ResultadoPersistenciaLocal::concluida();
    }
}
