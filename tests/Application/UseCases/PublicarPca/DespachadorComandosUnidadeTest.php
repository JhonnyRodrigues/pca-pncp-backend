<?php

declare(strict_types=1);

namespace Tests\Application\UseCases\PublicarPca;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Semae\Pca\Application\Commands\ComandoExcluirItensPca;
use Semae\Pca\Application\Commands\ComandoExcluirPca;
use Semae\Pca\Application\Commands\ComandoInserirItensPca;
use Semae\Pca\Application\Commands\ComandoPublicarPca;
use Semae\Pca\Application\Contracts\FinalizadorProcessamentoUnidade;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Enums\SituacaoDesfechoProcessamentoUnidade;
use Semae\Pca\Application\Enums\SituacaoEscritaPncp;
use Semae\Pca\Application\Enums\SituacaoProcessamentoUnidade;
use Semae\Pca\Application\Ports\EscritorPncpPort;
use Semae\Pca\Application\Ports\UnidadeDeTrabalhoPort;
use Semae\Pca\Application\Results\DesfechoProcessamentoUnidade;
use Semae\Pca\Application\Results\ResultadoEscritaPncp;
use Semae\Pca\Application\Results\ResultadoFinalizacaoUnidadeTrabalho;
use Semae\Pca\Application\Results\ResultadoInicioUnidadeTrabalho;
use Semae\Pca\Application\Results\ResultadoProcessamentoUnidade;
use Semae\Pca\Application\Results\ResultadoPublicacaoPncp;
use Semae\Pca\Application\UseCases\PublicarPca\DespachadorComandosUnidade;
use Semae\Pca\Application\UseCases\PublicarPca\ExecutorComandosUnidade;
use Semae\Pca\Application\ValueObjects\ChaveUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\ContextoFinalizacaoProcessamentoUnidade;
use Semae\Pca\Application\ValueObjects\ContextoUnidadeComDecisao;
use Semae\Pca\Application\ValueObjects\ContextoUnidadeSobExclusividade;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\Demanda;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\SituacaoDemanda;
use Semae\Pca\Domain\Policies\ResultadoDecisaoProcessamentoPlano;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\DecimalExato;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\PcaObservado;
use Semae\Pca\Domain\ValueObjects\Representante;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Stringable;

final class DespachadorComandosUnidadeTest extends TestCase
{
    public function testPublicarDeveEnviarTodasAsDemandasNoLimiteExatoDeMil(): void
    {
        $ids = range(1, 1000);
        $contexto = $this->criarContexto(
            $ids,
            ResultadoDecisaoProcessamentoPlano::publicar($ids)
        );
        $dependencias = $this->criarDependencias($contexto);

        $resultado = $dependencias->despachador->despachar(
            $this->criarContextoUsuario(),
            $contexto
        );

        $this->assertSame($dependencias->finalizador->desfechoRetornado, $resultado);
        $this->assertCount(1000, $dependencias->escritor->comandoPublicar?->getDemandas());
        $this->assertSame($ids, array_map(
            static fn (Demanda $demanda): int => $demanda->getIdDemanda(),
            $dependencias->escritor->comandoPublicar?->getDemandas() ?? []
        ));
        $this->assertSame(['publicar'], $dependencias->escritor->chamadas);
        $this->assertSame(1, $dependencias->finalizador->chamadas);
        $this->assertSame(0, $dependencias->unidadeTrabalho->desfazimentos);
        $this->assertSame(
            $ids,
            $dependencias->logger->contextoDeAcao('REGISTRAR_ITENS_PLANEJADOS')['metadata']['numerosItensAIncluir'] ?? null
        );
    }

    public function testPublicarComMilEUmaDemandasDeveBloquearAntesDaEscrita(): void
    {
        $ids = range(1, 1001);
        $contexto = $this->criarContexto(
            $ids,
            ResultadoDecisaoProcessamentoPlano::publicar($ids)
        );
        $dependencias = $this->criarDependencias($contexto);

        $resultado = $dependencias->despachador->despachar(
            $this->criarContextoUsuario(),
            $contexto
        );

        $this->assertSame(
            SituacaoProcessamentoUnidade::BLOQUEADO,
            $resultado->getResultadoUnitario()?->getSituacao()
        );
        $this->assertSame(
            'LIMITE_ITENS_PUBLICACAO_EXCEDIDO',
            $resultado->getResultadoUnitario()?->getFalha()?->getCodigo()
        );
        $this->assertSame([], $dependencias->escritor->chamadas);
        $this->assertSame(0, $dependencias->finalizador->chamadas);
        $this->assertSame(1, $dependencias->unidadeTrabalho->desfazimentos);
    }

    #[DataProvider('resultadosNaoSistemicosPublicacao')]
    public function testResultadoNaoSistemicoDaPublicacaoDeveSeguirAoFinalizador(
        string $situacao
    ): void {
        $contexto = $this->criarContexto(
            [1001],
            ResultadoDecisaoProcessamentoPlano::publicar([1001])
        );
        $dependencias = $this->criarDependencias($contexto);
        $dependencias->escritor->resultadoPublicacao = match ($situacao) {
            'aceita' => ResultadoPublicacaoPncp::aceita(new SequencialPca(88)),
            'rejeitada' => ResultadoPublicacaoPncp::rejeitada(
                new FalhaSegura('PUBLICACAO_REJEITADA', 'Publicação rejeitada.')
            ),
            'incerta' => ResultadoPublicacaoPncp::resultadoIncerto(
                new FalhaSegura('PUBLICACAO_INCERTA', 'Publicação incerta.')
            ),
        };

        $resultado = $dependencias->despachador->despachar(
            $this->criarContextoUsuario(),
            $contexto
        );

        $this->assertSame($dependencias->finalizador->desfechoRetornado, $resultado);
        $this->assertSame(1, $dependencias->finalizador->chamadas);
        $this->assertSame(
            $dependencias->escritor->resultadoPublicacao,
            $dependencias->finalizador->contextoRecebido?->getResultadosEscrita()[0]
        );
        $this->assertSame(0, $dependencias->unidadeTrabalho->desfazimentos);
    }

    public function testFalhaSistemicaDePublicacaoDeveDesfazerSemFinalizar(): void
    {
        $contexto = $this->criarContexto(
            [1001],
            ResultadoDecisaoProcessamentoPlano::publicar([1001])
        );
        $dependencias = $this->criarDependencias($contexto);
        $falha = new FalhaSegura('PNCP_INDISPONIVEL', 'PNCP indisponível.');
        $dependencias->escritor->resultadoPublicacao =
            ResultadoPublicacaoPncp::falhaSistemica($falha);

        $resultado = $dependencias->despachador->despachar(
            $this->criarContextoUsuario(),
            $contexto
        );

        $this->assertSame(
            SituacaoDesfechoProcessamentoUnidade::FALHA_SISTEMICA,
            $resultado->getSituacao()
        );
        $this->assertSame($falha, $resultado->getFalhaSistemica());
        $this->assertSame(0, $dependencias->finalizador->chamadas);
        $this->assertSame(1, $dependencias->unidadeTrabalho->desfazimentos);
    }

    public function testRetificarDeveExcluirAntesDeInserirComJustificativaPlural(): void
    {
        $contexto = $this->criarContexto(
            [2001],
            ResultadoDecisaoProcessamentoPlano::retificar([2001], [1001, 1002])
        );
        $dependencias = $this->criarDependencias($contexto);

        $resultado = $dependencias->despachador->despachar(
            $this->criarContextoUsuario(),
            $contexto
        );

        $this->assertSame($dependencias->finalizador->desfechoRetornado, $resultado);
        $this->assertSame(['excluir_itens', 'inserir_itens'], $dependencias->escritor->chamadas);
        $this->assertSame(
            'Exclusão de 2 itens por cancelamentos homologados no SGI.',
            $dependencias->escritor->comandoExcluirItens?->getJustificativa()
        );
        $this->assertSame([1001, 1002], $dependencias->escritor->comandoExcluirItens?->getIdsDemandas());
        $this->assertSame(
            [2001],
            array_map(
                static fn (Demanda $demanda): int => $demanda->getIdDemanda(),
                $dependencias->escritor->comandoInserirItens?->getDemandas() ?? []
            )
        );
        $this->assertCount(2, $dependencias->finalizador->contextoRecebido?->getResultadosEscrita());
    }

    public function testRetificacaoDeMilParaMilDeveExcluirAntesDeInserir(): void
    {
        $idsDesejados = range(2, 1001);
        $contexto = $this->criarContexto(
            $idsDesejados,
            ResultadoDecisaoProcessamentoPlano::retificar([1001], [1]),
            observado: PcaObservado::ativo(new SequencialPca(77), range(1, 1000))
        );
        $dependencias = $this->criarDependencias($contexto);

        $dependencias->despachador->despachar($this->criarContextoUsuario(), $contexto);

        $this->assertSame(['excluir_itens', 'inserir_itens'], $dependencias->escritor->chamadas);
        $this->assertSame([1], $dependencias->escritor->comandoExcluirItens?->getIdsDemandas());
        $this->assertSame(
            [1001],
            array_map(
                static fn (Demanda $demanda): int => $demanda->getIdDemanda(),
                $dependencias->escritor->comandoInserirItens?->getDemandas() ?? []
            )
        );
    }

    public function testRetificacaoComEstadoDesejadoAcimaDeMilDeveBloquearAntesDaEscrita(): void
    {
        $idsDesejados = range(1, 1001);
        $contexto = $this->criarContexto(
            $idsDesejados,
            ResultadoDecisaoProcessamentoPlano::retificar([1001], []),
            observado: PcaObservado::ativo(new SequencialPca(77), range(1, 1000))
        );
        $dependencias = $this->criarDependencias($contexto);

        $resultado = $dependencias->despachador->despachar(
            $this->criarContextoUsuario(),
            $contexto
        );

        $this->assertSame(
            SituacaoProcessamentoUnidade::BLOQUEADO,
            $resultado->getResultadoUnitario()?->getSituacao()
        );
        $this->assertSame(
            'LIMITE_ITENS_PUBLICACAO_EXCEDIDO',
            $resultado->getResultadoUnitario()?->getFalha()?->getCodigo()
        );
        $this->assertSame([], $dependencias->escritor->chamadas);
        $this->assertSame(0, $dependencias->finalizador->chamadas);
        $this->assertSame(1, $dependencias->unidadeTrabalho->desfazimentos);
    }

    public function testRetificacaoComUmaExclusaoDeveUsarJustificativaSingular(): void
    {
        $contexto = $this->criarContexto(
            [2001],
            ResultadoDecisaoProcessamentoPlano::retificar([], [1001])
        );
        $dependencias = $this->criarDependencias($contexto);

        $dependencias->despachador->despachar($this->criarContextoUsuario(), $contexto);

        $this->assertSame(['excluir_itens'], $dependencias->escritor->chamadas);
        $this->assertSame(
            'Exclusão de 1 item por cancelamento homologado no SGI.',
            $dependencias->escritor->comandoExcluirItens?->getJustificativa()
        );
        $this->assertSame(
            ['numerosItensAIncluir' => [], 'numerosItensAExcluir' => [1001]],
            $dependencias->logger->contextoDeAcao('REGISTRAR_ITENS_PLANEJADOS')['metadata'] ?? null
        );
    }

    #[DataProvider('resultadosQueInterrompemSegundaEscrita')]
    public function testRetificacaoNaoDeveInserirAposExclusaoNaoAceita(
        string $situacao
    ): void {
        $contexto = $this->criarContexto(
            [2001],
            ResultadoDecisaoProcessamentoPlano::retificar([2001], [1001])
        );
        $dependencias = $this->criarDependencias($contexto);
        $falha = new FalhaSegura('EXCLUSAO_NAO_ACEITA', 'Exclusão não aceita.');
        $dependencias->escritor->resultadoExcluirItens = $situacao === 'rejeitada'
            ? ResultadoEscritaPncp::rejeitada($falha)
            : ResultadoEscritaPncp::resultadoIncerto($falha);

        $resultado = $dependencias->despachador->despachar(
            $this->criarContextoUsuario(),
            $contexto
        );

        $this->assertSame($dependencias->finalizador->desfechoRetornado, $resultado);
        $this->assertSame(['excluir_itens'], $dependencias->escritor->chamadas);
        $this->assertNull($dependencias->escritor->comandoInserirItens);
        $this->assertCount(1, $dependencias->finalizador->contextoRecebido?->getResultadosEscrita());
    }

    public function testRetificacaoSomenteComInclusoesNaoDeveExcluirItens(): void
    {
        $contexto = $this->criarContexto(
            [2001],
            ResultadoDecisaoProcessamentoPlano::retificar([2001], [])
        );
        $dependencias = $this->criarDependencias($contexto);

        $dependencias->despachador->despachar($this->criarContextoUsuario(), $contexto);

        $this->assertSame(['inserir_itens'], $dependencias->escritor->chamadas);
        $this->assertNull($dependencias->escritor->comandoExcluirItens);
        $this->assertNotNull($dependencias->escritor->comandoInserirItens);
        $this->assertSame(
            ['numerosItensAIncluir' => [2001], 'numerosItensAExcluir' => []],
            $dependencias->logger->contextoDeAcao('REGISTRAR_ITENS_PLANEJADOS')['metadata'] ?? null
        );
    }

    public function test403NaExclusaoDeItensNaoDeveRepetirNemInserir(): void
    {
        $contexto = $this->criarContexto(
            [2001],
            ResultadoDecisaoProcessamentoPlano::retificar([2001], [1001])
        );
        $dependencias = $this->criarDependencias($contexto);
        $falha = new FalhaSegura(
            'PNCP_EXCLUSAO_ITENS_NAO_AUTORIZADA',
            'A exclusão de itens no PNCP não foi autorizada.'
        );
        $dependencias->escritor->resultadoExcluirItens = ResultadoEscritaPncp::falhaSistemica($falha);
        $dependencias->escritor->antesDaEscrita = function () use ($dependencias): void {
            $this->assertSame(
                [1001],
                $dependencias->logger->contextoDeAcao('REGISTRAR_ITENS_PLANEJADOS')['metadata']['numerosItensAExcluir'] ?? null
            );
        };

        $resultado = $dependencias->despachador->despachar(
            $this->criarContextoUsuario(),
            $contexto
        );

        $this->assertSame($falha, $resultado->getFalhaSistemica());
        $this->assertSame(['excluir_itens'], $dependencias->escritor->chamadas);
        $this->assertSame(0, $dependencias->finalizador->chamadas);
        $this->assertSame(1, $dependencias->unidadeTrabalho->desfazimentos);
        $this->assertNull($dependencias->logger->contextoDeAcao('REGISTRAR_ITENS_CONFIRMADOS'));
    }

    /** @regra RN-PUB-003 */
    public function testExcluirPcaDeveUsarJustificativaInstitucionalFixa(): void
    {
        $contexto = $this->criarContexto(
            [],
            ResultadoDecisaoProcessamentoPlano::excluirPca()
        );
        $dependencias = $this->criarDependencias($contexto);

        $resultado = $dependencias->despachador->despachar(
            $this->criarContextoUsuario(),
            $contexto
        );

        $this->assertSame($dependencias->finalizador->desfechoRetornado, $resultado);
        $this->assertSame(['excluir_pca'], $dependencias->escritor->chamadas);
        $this->assertSame(
            'Exclusão integral do PCA por ausência de demandas vigentes no SGI.',
            $dependencias->escritor->comandoExcluirPca?->getJustificativa()
        );
        $this->assertSame(1, $dependencias->finalizador->chamadas);
    }

    public function testExcluirPcaSemSequencialDeveBloquearAntesDaEscritaEDesfazer(): void
    {
        $contexto = $this->criarContexto(
            [],
            ResultadoDecisaoProcessamentoPlano::excluirPca(),
            EstadoOperacionalPublicacao::naoPublicado()
        );
        $dependencias = $this->criarDependencias($contexto);

        $resultado = $dependencias->despachador->despachar(
            $this->criarContextoUsuario(),
            $contexto
        );

        $this->assertSame(
            'SEQUENCIAL_PCA_AUSENTE',
            $resultado->getResultadoUnitario()?->getFalha()?->getCodigo()
        );
        $this->assertSame([], $dependencias->escritor->chamadas);
        $this->assertSame(0, $dependencias->finalizador->chamadas);
        $this->assertSame(1, $dependencias->unidadeTrabalho->desfazimentos);
    }

    #[DataProvider('resultadosNaoSistemicosExclusaoIntegral')]
    public function testResultadoNaoSistemicoDaExclusaoIntegralDeveSeguirParaReconciliacao(
        string $tipo
    ): void {
        $contexto = $this->criarContexto(
            [],
            ResultadoDecisaoProcessamentoPlano::excluirPca()
        );
        $dependencias = $this->criarDependencias($contexto);
        $falha = new FalhaSegura('EXCLUSAO_NAO_CONFIRMADA', 'Exclusão não confirmada.');
        $resultadoEscrita = $tipo === 'rejeitada'
            ? ResultadoEscritaPncp::rejeitada($falha)
            : ResultadoEscritaPncp::resultadoIncerto($falha);
        $dependencias->escritor->resultadoExcluirPca = $resultadoEscrita;

        $resultado = $dependencias->despachador->despachar(
            $this->criarContextoUsuario(),
            $contexto
        );

        $this->assertSame($dependencias->finalizador->desfechoRetornado, $resultado);
        $this->assertSame(['excluir_pca'], $dependencias->escritor->chamadas);
        $this->assertSame(
            [$resultadoEscrita],
            $dependencias->finalizador->contextoRecebido?->getResultadosEscrita()
        );
        $this->assertSame(0, $dependencias->unidadeTrabalho->desfazimentos);
    }

    public function testFalhaSistemicaNaExclusaoIntegralDeveDesfazerSemFinalizar(): void
    {
        $contexto = $this->criarContexto(
            [],
            ResultadoDecisaoProcessamentoPlano::excluirPca()
        );
        $dependencias = $this->criarDependencias($contexto);
        $falha = new FalhaSegura('PNCP_INDISPONIVEL', 'PNCP indisponível.');
        $dependencias->escritor->resultadoExcluirPca =
            ResultadoEscritaPncp::falhaSistemica($falha);

        $resultado = $dependencias->despachador->despachar(
            $this->criarContextoUsuario(),
            $contexto
        );

        $this->assertSame($falha, $resultado->getFalhaSistemica());
        $this->assertSame(['excluir_pca'], $dependencias->escritor->chamadas);
        $this->assertSame(0, $dependencias->finalizador->chamadas);
        $this->assertSame(1, $dependencias->unidadeTrabalho->desfazimentos);
    }

    public function testNenhumaAcaoNaoDeveChamarEscritorMasDeveFinalizarSincronamente(): void
    {
        $contexto = $this->criarContexto(
            [],
            ResultadoDecisaoProcessamentoPlano::nenhumaAcao(),
            EstadoOperacionalPublicacao::naoPublicado(),
            PcaObservado::ausente()
        );
        $dependencias = $this->criarDependencias($contexto);

        $resultado = $dependencias->despachador->despachar(
            $this->criarContextoUsuario(),
            $contexto
        );

        $this->assertSame($dependencias->finalizador->desfechoRetornado, $resultado);
        $this->assertSame([], $dependencias->escritor->chamadas);
        $this->assertSame([], $dependencias->finalizador->contextoRecebido?->getResultadosEscrita());
        $this->assertSame(1, $dependencias->finalizador->chamadas);
        $this->assertNull($dependencias->logger->contextoDeAcao('REGISTRAR_ITENS_PLANEJADOS'));
        $this->assertSame($contexto->getSessaoPncp(), $dependencias->finalizador->contextoRecebido?->getSessaoPncp());
    }

    public function testEscritaDeveUsarSessaoCompartilhadaTambemNaFinalizacao(): void
    {
        $contexto = $this->criarContexto(
            [2001],
            ResultadoDecisaoProcessamentoPlano::retificar([2001], [1001])
        );
        $dependencias = $this->criarDependencias($contexto);

        $dependencias->despachador->despachar($this->criarContextoUsuario(), $contexto);

        $this->assertSame(
            [$contexto->getSessaoPncp(), $contexto->getSessaoPncp()],
            $dependencias->escritor->sessoes
        );
        $this->assertSame(
            $contexto->getSessaoPncp(),
            $dependencias->finalizador->contextoRecebido?->getSessaoPncp()
        );
    }

    /** @return array<string, array{string}> */
    public static function resultadosNaoSistemicosPublicacao(): array
    {
        return [
            'aceita' => ['aceita'],
            'rejeitada' => ['rejeitada'],
            'resultado incerto' => ['incerta'],
        ];
    }

    /** @return array<string, array{string}> */
    public static function resultadosQueInterrompemSegundaEscrita(): array
    {
        return ['rejeitada' => ['rejeitada'], 'resultado incerto' => ['incerta']];
    }

    /** @return array<string, array{string}> */
    public static function resultadosNaoSistemicosExclusaoIntegral(): array
    {
        return ['rejeitada' => ['rejeitada'], 'resultado incerto' => ['incerta']];
    }

    private function criarContextoUsuario(): ContextoUsuario
    {
        return new ContextoUsuario(
            new Representante(709, 'Maria da Silva'),
            new Lotacao(159, 'Coordenadoria de Tecnologia')
        );
    }

    private function criarDependencias(
        ContextoUnidadeComDecisao $contexto
    ): DependenciasDespachadorFake {
        $escritor = new EscritorPncpFakeDespacho();
        $finalizador = new FinalizadorFakeDespacho();
        $unidadeTrabalho = new UnidadeTrabalhoFakeDespacho();
        $logger = new LoggerMemoriaDespacho();

        return new DependenciasDespachadorFake(
            new DespachadorComandosUnidade(
                new ExecutorComandosUnidade($escritor, $unidadeTrabalho),
                $finalizador,
                $unidadeTrabalho,
                $logger
            ),
            $escritor,
            $finalizador,
            $unidadeTrabalho,
            $logger
        );
    }

    /** @param int[] $idsDemandas */
    private function criarContexto(
        array $idsDemandas,
        ResultadoDecisaoProcessamentoPlano $decisao,
        ?EstadoOperacionalPublicacao $estado = null,
        ?PcaObservado $observado = null
    ): ContextoUnidadeComDecisao {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidadeOrcamentaria = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria('uo-101'),
            new Lotacao(101, 'Diretoria Administrativa'),
            new Uasg('928344', 'SEMAE Piracicaba'),
            'Diretoria Administrativa'
        );
        $identidade = new IdentidadePlano($unidadeOrcamentaria, $ano);
        $plano = new Plano(
            $identidade,
            2,
            new DateTimeImmutable('2026-07-16'),
            array_map(
                fn (int $id): Demanda => $this->criarDemanda($id, $unidadeOrcamentaria, $ano),
                $idsDemandas
            ),
            null
        );
        $sequencial = new SequencialPca(77);
        $estado ??= EstadoOperacionalPublicacao::publicado($sequencial);
        $observado ??= PcaObservado::ativo($sequencial, [9001]);
        $sobExclusividade = new ContextoUnidadeSobExclusividade(
            UnidadeExternaPublicacao::individual($identidade),
            [$plano],
            $estado
        );

        return new ContextoUnidadeComDecisao(
            new SessaoPncpFakeDespacho(),
            new Cnpj('12.345.678/0001-95'),
            $sobExclusividade,
            $observado,
            $decisao
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
            1,
            (string) $id,
            '98',
            'Grupo 98',
            'N'
        );
    }
}

final class DependenciasDespachadorFake
{
    public function __construct(
        public readonly DespachadorComandosUnidade $despachador,
        public readonly EscritorPncpFakeDespacho $escritor,
        public readonly FinalizadorFakeDespacho $finalizador,
        public readonly UnidadeTrabalhoFakeDespacho $unidadeTrabalho,
        public readonly LoggerMemoriaDespacho $logger
    ) {}
}

final class LoggerMemoriaDespacho extends AbstractLogger
{
    /** @var array<int, array<string, mixed>> */
    private array $contextos = [];

    /** @param array<string, mixed> $context */
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->contextos[] = $context;
    }

    /** @return array<string, mixed>|null */
    public function contextoDeAcao(string $acao): ?array
    {
        foreach ($this->contextos as $contexto) {
            if (($contexto['action'] ?? null) === $acao) {
                return $contexto;
            }
        }

        return null;
    }
}

final class SessaoPncpFakeDespacho implements SessaoPncp
{
}

final class EscritorPncpFakeDespacho implements EscritorPncpPort
{
    /** @var string[] */
    public array $chamadas = [];
    /** @var SessaoPncp[] */
    public array $sessoes = [];
    public ?ComandoPublicarPca $comandoPublicar = null;
    public ?ComandoInserirItensPca $comandoInserirItens = null;
    public ?ComandoExcluirItensPca $comandoExcluirItens = null;
    public ?ComandoExcluirPca $comandoExcluirPca = null;
    public ResultadoPublicacaoPncp $resultadoPublicacao;
    public ResultadoEscritaPncp $resultadoInserirItens;
    public ResultadoEscritaPncp $resultadoExcluirItens;
    public ResultadoEscritaPncp $resultadoExcluirPca;
    public ?\Closure $antesDaEscrita = null;

    public function __construct()
    {
        $this->resultadoPublicacao = ResultadoPublicacaoPncp::aceita(new SequencialPca(88));
        $this->resultadoInserirItens = ResultadoEscritaPncp::aceita();
        $this->resultadoExcluirItens = ResultadoEscritaPncp::aceita();
        $this->resultadoExcluirPca = ResultadoEscritaPncp::aceita();
    }

    public function publicar(
        SessaoPncp $sessao,
        ComandoPublicarPca $comando
    ): ResultadoPublicacaoPncp {
        $this->chamadas[] = 'publicar';
        $this->sessoes[] = $sessao;
        $this->comandoPublicar = $comando;

        return $this->resultadoPublicacao;
    }

    public function inserirItens(
        SessaoPncp $sessao,
        ComandoInserirItensPca $comando
    ): ResultadoEscritaPncp {
        $this->chamadas[] = 'inserir_itens';
        $this->sessoes[] = $sessao;
        $this->comandoInserirItens = $comando;

        return $this->resultadoInserirItens;
    }

    public function excluirItens(
        SessaoPncp $sessao,
        ComandoExcluirItensPca $comando
    ): ResultadoEscritaPncp {
        if ($this->antesDaEscrita !== null) {
            ($this->antesDaEscrita)();
        }
        $this->chamadas[] = 'excluir_itens';
        $this->sessoes[] = $sessao;
        $this->comandoExcluirItens = $comando;

        return $this->resultadoExcluirItens;
    }

    public function excluirPca(
        SessaoPncp $sessao,
        ComandoExcluirPca $comando
    ): ResultadoEscritaPncp {
        $this->chamadas[] = 'excluir_pca';
        $this->sessoes[] = $sessao;
        $this->comandoExcluirPca = $comando;

        return $this->resultadoExcluirPca;
    }
}

final class FinalizadorFakeDespacho implements FinalizadorProcessamentoUnidade
{
    public int $chamadas = 0;
    public ?ContextoFinalizacaoProcessamentoUnidade $contextoRecebido = null;
    public ?DesfechoProcessamentoUnidade $desfechoRetornado = null;

    public function finalizar(
        ContextoFinalizacaoProcessamentoUnidade $contexto
    ): DesfechoProcessamentoUnidade {
        $this->chamadas++;
        $this->contextoRecebido = $contexto;
        $this->desfechoRetornado = DesfechoProcessamentoUnidade::resultadoUnitario(
            ResultadoProcessamentoUnidade::semAlteracao($contexto->getUnidadeExterna())
        );

        return $this->desfechoRetornado;
    }
}

final class UnidadeTrabalhoFakeDespacho implements UnidadeDeTrabalhoPort
{
    public int $desfazimentos = 0;

    public function iniciar(ChaveUnidadeTrabalho $chave): ResultadoInicioUnidadeTrabalho
    {
        throw new \RuntimeException('O dispatcher não inicia unidade de trabalho.');
    }

    public function confirmar(): ResultadoFinalizacaoUnidadeTrabalho
    {
        throw new \RuntimeException('A confirmação pertence ao finalizador da Sprint 4.11.');
    }

    public function desfazer(): ResultadoFinalizacaoUnidadeTrabalho
    {
        $this->desfazimentos++;

        return ResultadoFinalizacaoUnidadeTrabalho::concluida();
    }
}
