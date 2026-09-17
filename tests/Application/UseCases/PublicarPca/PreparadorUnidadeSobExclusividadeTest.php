<?php

declare(strict_types=1);

namespace Tests\Application\UseCases\PublicarPca;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\Pca\Application\Enums\SituacaoDesfechoProcessamentoUnidade;
use Semae\Pca\Application\Enums\SituacaoProcessamentoUnidade;
use Semae\Pca\Application\Ports\EstadoOperacionalPublicacaoPort;
use Semae\Pca\Application\Ports\UnidadeDeTrabalhoPort;
use Semae\Pca\Application\Results\DesfechoProcessamentoUnidade;
use Semae\Pca\Application\Results\ResultadoConsultaEstadoOperacional;
use Semae\Pca\Application\Results\ResultadoFinalizacaoUnidadeTrabalho;
use Semae\Pca\Application\Results\ResultadoInicioUnidadeTrabalho;
use Semae\Pca\Application\Results\ResultadoPersistenciaLocal;
use Semae\Pca\Application\UseCases\PublicarPca\PreparadorUnidadeSobExclusividade;
use Semae\Pca\Application\ValueObjects\ChaveUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\ContextoUnidadeSobExclusividade;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\Demanda;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\SituacaoDemanda;
use Semae\Pca\Domain\Ports\PlanoRepository;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\DecimalExato;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class PreparadorUnidadeSobExclusividadeTest extends TestCase
{
    public function testIndividualDeveIniciarComChaveExternaERelerSomenteSuaIdentidade(): void
    {
        $registro = new RegistroChamadasPreparacaoUnidade();
        $alvo = $this->criarIdentidade('uo-101', '928344');
        $outra = $this->criarIdentidade('uo-102', '928345');
        $plano = $this->criarPlano($alvo, [1001]);
        $repository = new PlanoRepositoryFakePreparacao(
            $registro,
            [$outra, $alvo],
            ['uo-101' => $plano]
        );
        $unidadeTrabalho = new UnidadeTrabalhoFakePreparacao($registro);
        $estadoPort = new EstadoOperacionalFakePreparacao(
            $registro,
            ResultadoConsultaEstadoOperacional::disponivel(
                EstadoOperacionalPublicacao::naoPublicado()
            )
        );
        $unidade = UnidadeExternaPublicacao::individual($alvo);

        $resultado = $this->preparador(
            $unidadeTrabalho,
            $repository,
            $estadoPort
        )->preparar($unidade);

        $this->assertInstanceOf(ContextoUnidadeSobExclusividade::class, $resultado);
        $this->assertTrue(
            ChaveUnidadeTrabalho::unidadeExterna($unidade)->equals(
                $unidadeTrabalho->chaveRecebida
            )
        );
        $this->assertSame([$plano], $resultado->getPlanos());
        $this->assertFalse($resultado->getEstadoOperacional()->estaAtivo());
        $this->assertSame(
            ['iniciar', 'listar', 'reconstruir:uo-101', 'consultar_estado'],
            $registro->chamadas
        );
        $this->assertSame(0, $unidadeTrabalho->desfazimentos);
        $this->assertSame(0, $unidadeTrabalho->confirmacoes);
    }

    public function testConsolidadoDeveRelerTodosOsPlanosInclusivePlanoVazio(): void
    {
        $registro = new RegistroChamadasPreparacaoUnidade();
        $primeira = $this->criarIdentidade('uo-101', '928344');
        $segunda = $this->criarIdentidade('uo-102', '928345');
        $planoComDemanda = $this->criarPlano($primeira, [1001]);
        $planoVazio = $this->criarPlano($segunda, []);
        $repository = new PlanoRepositoryFakePreparacao(
            $registro,
            [$primeira, $segunda],
            [
                'uo-101' => $planoComDemanda,
                'uo-102' => $planoVazio,
            ]
        );
        $unidadeTrabalho = new UnidadeTrabalhoFakePreparacao($registro);
        $estado = EstadoOperacionalPublicacao::naoPublicado();
        $estadoPort = new EstadoOperacionalFakePreparacao(
            $registro,
            ResultadoConsultaEstadoOperacional::disponivel($estado)
        );
        $unidade = UnidadeExternaPublicacao::consolidada(
            $primeira->getAnoExecucao(),
            new Uasg('986700', 'UASG Consolidadora')
        );

        $resultado = $this->preparador(
            $unidadeTrabalho,
            $repository,
            $estadoPort
        )->preparar($unidade);

        $this->assertInstanceOf(ContextoUnidadeSobExclusividade::class, $resultado);
        $this->assertSame([$planoComDemanda, $planoVazio], $resultado->getPlanos());
        $this->assertTrue($resultado->getPlanos()[0]->possuiDemandas());
        $this->assertFalse($resultado->getPlanos()[1]->possuiDemandas());
        $this->assertSame($estado, $resultado->getEstadoOperacional());
        $this->assertSame(
            [
                'iniciar',
                'listar',
                'reconstruir:uo-101',
                'reconstruir:uo-102',
                'consultar_estado',
            ],
            $registro->chamadas
        );
    }

    public function testContencaoDeveBloquearERetornarDepoisDoRollback(): void
    {
        $falha = new FalhaSegura('UNIDADE_OCUPADA', 'Unidade em processamento.');
        [$preparador, $unidadeTrabalho, $repository, $estadoPort] =
            $this->criarDependenciasComInicio(ResultadoInicioUnidadeTrabalho::bloqueada($falha));

        $resultado = $preparador->preparar(
            UnidadeExternaPublicacao::individual($this->criarIdentidade('uo-101', '928344'))
        );

        $this->assertInstanceOf(DesfechoProcessamentoUnidade::class, $resultado);
        $this->assertSame(
            SituacaoDesfechoProcessamentoUnidade::RESULTADO_UNITARIO,
            $resultado->getSituacao()
        );
        $this->assertSame(
            SituacaoProcessamentoUnidade::BLOQUEADO,
            $resultado->getResultadoUnitario()?->getSituacao()
        );
        $this->assertSame($falha, $resultado->getResultadoUnitario()?->getFalha());
        $this->assertSame(1, $unidadeTrabalho->desfazimentos);
        $this->assertSame(0, $repository->listagens);
        $this->assertSame(0, $estadoPort->consultas);
    }

    public function testFalhaAoIniciarDeveInterromperSemTentarRollback(): void
    {
        $falha = new FalhaSegura('TRANSACAO_INDISPONIVEL', 'Transação indisponível.');
        [$preparador, $unidadeTrabalho] = $this->criarDependenciasComInicio(
            ResultadoInicioUnidadeTrabalho::falhaSistemica($falha)
        );

        $resultado = $preparador->preparar(
            UnidadeExternaPublicacao::individual($this->criarIdentidade('uo-101', '928344'))
        );

        $this->assertSame(
            SituacaoDesfechoProcessamentoUnidade::FALHA_SISTEMICA,
            $resultado->getSituacao()
        );
        $this->assertSame($falha, $resultado->getFalhaSistemica());
        $this->assertSame(0, $unidadeTrabalho->desfazimentos);
    }

    public function testFalhaNoRollbackDevePrevalecerSobreContencao(): void
    {
        $falhaRollback = new FalhaSegura('ROLLBACK_FALHOU', 'Rollback falhou.');
        $registro = new RegistroChamadasPreparacaoUnidade();
        $unidadeTrabalho = new UnidadeTrabalhoFakePreparacao(
            $registro,
            ResultadoInicioUnidadeTrabalho::bloqueada(
                new FalhaSegura('UNIDADE_OCUPADA', 'Unidade em processamento.')
            ),
            ResultadoFinalizacaoUnidadeTrabalho::falhaSistemica($falhaRollback)
        );
        $repository = new PlanoRepositoryFakePreparacao($registro, [], []);
        $estadoPort = new EstadoOperacionalFakePreparacao(
            $registro,
            ResultadoConsultaEstadoOperacional::disponivel(
                EstadoOperacionalPublicacao::naoPublicado()
            )
        );

        $resultado = $this->preparador(
            $unidadeTrabalho,
            $repository,
            $estadoPort
        )->preparar(
            UnidadeExternaPublicacao::individual($this->criarIdentidade('uo-101', '928344'))
        );

        $this->assertSame(
            SituacaoDesfechoProcessamentoUnidade::FALHA_SISTEMICA,
            $resultado->getSituacao()
        );
        $this->assertSame($falhaRollback, $resultado->getFalhaSistemica());
    }

    public function testDesaparecimentoDaIdentidadeIndividualDeveBloquear(): void
    {
        $resultado = $this->executarComIdentidadesSobLock([
            $this->criarIdentidade('uo-102', '928345'),
        ]);

        $this->assertBloqueadoComCodigo($resultado, 'IDENTIDADE_INDIVIDUAL_DESAPARECEU');
    }

    public function testIdentidadeDuplicadaSobLockDeveBloquear(): void
    {
        $resultado = $this->executarComIdentidadesSobLock([
            $this->criarIdentidade('uo-101', '928344'),
            $this->criarIdentidade('uo-101', '928344'),
        ]);

        $this->assertBloqueadoComCodigo($resultado, 'IDENTIDADE_PLANO_DUPLICADA');
    }

    public function testIdentidadeDeOutroExercicioSobLockDeveBloquear(): void
    {
        $resultado = $this->executarComIdentidadesSobLock([
            $this->criarIdentidade(
                'uo-101',
                '928344',
                new AnoExecucao((int) date('Y'))
            ),
        ]);

        $this->assertBloqueadoComCodigo($resultado, 'EXERCICIO_IDENTIDADE_INCOERENTE');
    }

    public function testMudancaDeUasgIndividualSobLockDeveBloquear(): void
    {
        $resultado = $this->executarComIdentidadesSobLock([
            $this->criarIdentidade('uo-101', '928399'),
        ]);

        $this->assertBloqueadoComCodigo($resultado, 'UASG_INDIVIDUAL_INCOERENTE');
    }

    public function testPlanoReconstruidoComIdentidadeDivergenteDeveBloquear(): void
    {
        $registro = new RegistroChamadasPreparacaoUnidade();
        $identidade = $this->criarIdentidade('uo-101', '928344');
        $repository = new PlanoRepositoryFakePreparacao(
            $registro,
            [$identidade],
            ['uo-101' => $this->criarPlano(
                $this->criarIdentidade('uo-102', '928345'),
                []
            )]
        );
        $unidadeTrabalho = new UnidadeTrabalhoFakePreparacao($registro);
        $estadoPort = new EstadoOperacionalFakePreparacao(
            $registro,
            ResultadoConsultaEstadoOperacional::disponivel(
                EstadoOperacionalPublicacao::naoPublicado()
            )
        );

        $resultado = $this->preparador(
            $unidadeTrabalho,
            $repository,
            $estadoPort
        )->preparar(UnidadeExternaPublicacao::individual($identidade));

        $this->assertBloqueadoComCodigo($resultado, 'PLANO_RECONSTRUIDO_INCOERENTE');
        $this->assertSame(1, $unidadeTrabalho->desfazimentos);
        $this->assertSame(0, $estadoPort->consultas);
    }

    public function testConflitoOperacionalDeveBloquearEDesfazer(): void
    {
        [$preparador, $unidadeTrabalho] = $this->criarDependenciasComEstado(
            ResultadoConsultaEstadoOperacional::conflito(
                new FalhaSegura('ESTADO_CONFLITANTE', 'Estado operacional conflitante.')
            )
        );

        $resultado = $preparador->preparar(
            UnidadeExternaPublicacao::individual($this->criarIdentidade('uo-101', '928344'))
        );

        $this->assertBloqueadoComCodigo($resultado, 'ESTADO_CONFLITANTE');
        $this->assertSame(1, $unidadeTrabalho->desfazimentos);
    }

    public function testFalhaSistemicaOperacionalDeveDesfazerEInterromper(): void
    {
        $falha = new FalhaSegura('ESTADO_INDISPONIVEL', 'Estado operacional indisponível.');
        [$preparador, $unidadeTrabalho] = $this->criarDependenciasComEstado(
            ResultadoConsultaEstadoOperacional::falhaSistemica($falha)
        );

        $resultado = $preparador->preparar(
            UnidadeExternaPublicacao::individual($this->criarIdentidade('uo-101', '928344'))
        );

        $this->assertSame(
            SituacaoDesfechoProcessamentoUnidade::FALHA_SISTEMICA,
            $resultado->getSituacao()
        );
        $this->assertSame($falha, $resultado->getFalhaSistemica());
        $this->assertSame(1, $unidadeTrabalho->desfazimentos);
    }

    public function testExcecaoNaReleituraDeveDesfazerEInterromperComFalhaSegura(): void
    {
        $registro = new RegistroChamadasPreparacaoUnidade();
        $repository = new PlanoRepositoryFakePreparacao($registro, [], []);
        $repository->falhaListagem = new RuntimeException('detalhe interno');
        $unidadeTrabalho = new UnidadeTrabalhoFakePreparacao($registro);
        $estadoPort = new EstadoOperacionalFakePreparacao(
            $registro,
            ResultadoConsultaEstadoOperacional::disponivel(
                EstadoOperacionalPublicacao::naoPublicado()
            )
        );

        $resultado = $this->preparador(
            $unidadeTrabalho,
            $repository,
            $estadoPort
        )->preparar(
            UnidadeExternaPublicacao::individual($this->criarIdentidade('uo-101', '928344'))
        );

        $this->assertSame(
            SituacaoDesfechoProcessamentoUnidade::FALHA_SISTEMICA,
            $resultado->getSituacao()
        );
        $this->assertSame('RELEITURA_UNIDADE_FALHOU', $resultado->getFalhaSistemica()?->getCodigo());
        $this->assertSame(1, $unidadeTrabalho->desfazimentos);
        $this->assertSame(0, $estadoPort->consultas);
    }

    /**
     * @return array{
     *   PreparadorUnidadeSobExclusividade,
     *   UnidadeTrabalhoFakePreparacao,
     *   PlanoRepositoryFakePreparacao,
     *   EstadoOperacionalFakePreparacao
     * }
     */
    private function criarDependenciasComInicio(
        ResultadoInicioUnidadeTrabalho $inicio
    ): array {
        $registro = new RegistroChamadasPreparacaoUnidade();
        $unidadeTrabalho = new UnidadeTrabalhoFakePreparacao($registro, $inicio);
        $repository = new PlanoRepositoryFakePreparacao($registro, [], []);
        $estadoPort = new EstadoOperacionalFakePreparacao(
            $registro,
            ResultadoConsultaEstadoOperacional::disponivel(
                EstadoOperacionalPublicacao::naoPublicado()
            )
        );

        return [
            $this->preparador($unidadeTrabalho, $repository, $estadoPort),
            $unidadeTrabalho,
            $repository,
            $estadoPort,
        ];
    }

    /** @return array{PreparadorUnidadeSobExclusividade, UnidadeTrabalhoFakePreparacao} */
    private function criarDependenciasComEstado(
        ResultadoConsultaEstadoOperacional $resultadoEstado
    ): array {
        $registro = new RegistroChamadasPreparacaoUnidade();
        $identidade = $this->criarIdentidade('uo-101', '928344');
        $repository = new PlanoRepositoryFakePreparacao(
            $registro,
            [$identidade],
            ['uo-101' => $this->criarPlano($identidade, [])]
        );
        $unidadeTrabalho = new UnidadeTrabalhoFakePreparacao($registro);
        $estadoPort = new EstadoOperacionalFakePreparacao($registro, $resultadoEstado);

        return [
            $this->preparador($unidadeTrabalho, $repository, $estadoPort),
            $unidadeTrabalho,
        ];
    }

    /** @param IdentidadePlano[] $identidades */
    private function executarComIdentidadesSobLock(array $identidades): DesfechoProcessamentoUnidade
    {
        $registro = new RegistroChamadasPreparacaoUnidade();
        $repository = new PlanoRepositoryFakePreparacao($registro, $identidades, []);
        $unidadeTrabalho = new UnidadeTrabalhoFakePreparacao($registro);
        $estadoPort = new EstadoOperacionalFakePreparacao(
            $registro,
            ResultadoConsultaEstadoOperacional::disponivel(
                EstadoOperacionalPublicacao::naoPublicado()
            )
        );

        $resultado = $this->preparador(
            $unidadeTrabalho,
            $repository,
            $estadoPort
        )->preparar(
            UnidadeExternaPublicacao::individual($this->criarIdentidade('uo-101', '928344'))
        );

        $this->assertInstanceOf(DesfechoProcessamentoUnidade::class, $resultado);
        $this->assertSame(1, $unidadeTrabalho->desfazimentos);
        $this->assertSame(0, $estadoPort->consultas);

        return $resultado;
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

    private function preparador(
        UnidadeDeTrabalhoPort $unidadeTrabalho,
        PlanoRepository $repository,
        EstadoOperacionalPublicacaoPort $estadoPort
    ): PreparadorUnidadeSobExclusividade {
        return new PreparadorUnidadeSobExclusividade(
            $unidadeTrabalho,
            $repository,
            $estadoPort
        );
    }

    private function criarIdentidade(
        string $idUo,
        string $uasg,
        ?AnoExecucao $ano = null
    ): IdentidadePlano {
        return new IdentidadePlano(
            new UnidadeOrcamentaria(
                new IdentidadeUnidadeOrcamentaria($idUo),
                new Lotacao(101, 'Diretoria Administrativa'),
                new Uasg($uasg, 'UASG da UO'),
                'Diretoria Administrativa'
            ),
            $ano ?? new AnoExecucao((int) date('Y') + 1)
        );
    }

    /** @param int[] $idsDemandas */
    private function criarPlano(IdentidadePlano $identidade, array $idsDemandas): Plano
    {
        $demandas = array_map(
            fn (int $id): Demanda => $this->criarDemanda($id, $identidade),
            $idsDemandas
        );

        return new Plano(
            $identidade,
            2,
            new DateTimeImmutable('2026-07-16'),
            $demandas,
            null
        );
    }

    private function criarDemanda(int $id, IdentidadePlano $identidade): Demanda
    {
        $ano = $identidade->getAnoExecucao();

        return new Demanda(
            $id,
            SituacaoDemanda::HOMOLOGADA,
            159,
            $identidade->getUnidadeOrcamentaria(),
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

final class RegistroChamadasPreparacaoUnidade
{
    /** @var string[] */
    public array $chamadas = [];
}

final class UnidadeTrabalhoFakePreparacao implements UnidadeDeTrabalhoPort
{
    public ?ChaveUnidadeTrabalho $chaveRecebida = null;
    public int $confirmacoes = 0;
    public int $desfazimentos = 0;
    private readonly ResultadoInicioUnidadeTrabalho $inicio;
    private readonly ResultadoFinalizacaoUnidadeTrabalho $desfazimento;

    public function __construct(
        private readonly RegistroChamadasPreparacaoUnidade $registro,
        ?ResultadoInicioUnidadeTrabalho $inicio = null,
        ?ResultadoFinalizacaoUnidadeTrabalho $desfazimento = null
    ) {
        $this->inicio = $inicio ?? ResultadoInicioUnidadeTrabalho::iniciada();
        $this->desfazimento = $desfazimento
            ?? ResultadoFinalizacaoUnidadeTrabalho::concluida();
    }

    public function iniciar(ChaveUnidadeTrabalho $chave): ResultadoInicioUnidadeTrabalho
    {
        $this->registro->chamadas[] = 'iniciar';
        $this->chaveRecebida = $chave;

        return $this->inicio;
    }

    public function confirmar(): ResultadoFinalizacaoUnidadeTrabalho
    {
        $this->confirmacoes++;

        return ResultadoFinalizacaoUnidadeTrabalho::concluida();
    }

    public function desfazer(): ResultadoFinalizacaoUnidadeTrabalho
    {
        $this->registro->chamadas[] = 'desfazer';
        $this->desfazimentos++;

        return $this->desfazimento;
    }
}

final class PlanoRepositoryFakePreparacao implements PlanoRepository
{
    public int $listagens = 0;
    public ?RuntimeException $falhaListagem = null;

    /**
     * @param IdentidadePlano[] $identidades
     * @param array<string, Plano> $planosPorUo
     */
    public function __construct(
        private readonly RegistroChamadasPreparacaoUnidade $registro,
        private readonly array $identidades,
        private readonly array $planosPorUo
    ) {}

    public function listarIdentidades(AnoExecucao $anoExecucao): array
    {
        $this->registro->chamadas[] = 'listar';
        $this->listagens++;

        if ($this->falhaListagem !== null) {
            throw $this->falhaListagem;
        }

        return $this->identidades;
    }

    public function reconstruirFotografiaVigente(IdentidadePlano $identidade): Plano
    {
        $idUo = $identidade->getUnidadeOrcamentaria()->getIdentidade()->getValor();
        $this->registro->chamadas[] = 'reconstruir:' . $idUo;

        return $this->planosPorUo[$idUo];
    }

    public function confirmarSincronizacao(Plano $plano): void
    {
    }
}

final class EstadoOperacionalFakePreparacao implements EstadoOperacionalPublicacaoPort
{
    public int $consultas = 0;

    public function __construct(
        private readonly RegistroChamadasPreparacaoUnidade $registro,
        private readonly ResultadoConsultaEstadoOperacional $resultado
    ) {}

    public function consultar(
        UnidadeExternaPublicacao $unidade
    ): ResultadoConsultaEstadoOperacional {
        $this->registro->chamadas[] = 'consultar_estado';
        $this->consultas++;

        return $this->resultado;
    }

    public function registrar(
        UnidadeExternaPublicacao $unidade,
        EstadoOperacionalPublicacao $estado
    ): ResultadoPersistenciaLocal {
        return ResultadoPersistenciaLocal::concluida();
    }
}
