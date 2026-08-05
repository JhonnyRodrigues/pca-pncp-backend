<?php

declare(strict_types=1);

namespace Tests\Application\Results;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Enums\SituacaoConsultaEstadoOperacional;
use Semae\Pca\Application\Enums\SituacaoPersistenciaLocal;
use Semae\Pca\Application\Enums\SituacaoResolucaoContextoUsuario;
use Semae\Pca\Application\Results\ResultadoConsultaEstadoOperacional;
use Semae\Pca\Application\Results\ResultadoPersistenciaLocal;
use Semae\Pca\Application\Results\ResultadoResolucaoContextoUsuario;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Domain\ValueObjects\EstadoOperacionalPublicacao;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Representante;

final class ResultadosContextoEEstadoOperacionalTest extends TestCase
{
    public function testContextoResolvidoDeveConterContextoSemFalha(): void
    {
        $contexto = $this->criarContexto();

        $resultado = ResultadoResolucaoContextoUsuario::resolvido($contexto);

        $this->assertSame(SituacaoResolucaoContextoUsuario::RESOLVIDO, $resultado->getSituacao());
        $this->assertSame($contexto, $resultado->getContexto());
        $this->assertNull($resultado->getFalha());
    }

    #[DataProvider('contextosNaoResolvidos')]
    public function testContextoNaoResolvidoDeveConterFalhaSemFabricarContexto(
        SituacaoResolucaoContextoUsuario $situacao,
        string $factory
    ): void {
        $falha = new FalhaSegura('CONTEXTO_INDISPONIVEL', 'Contexto indisponível.');

        $resultado = ResultadoResolucaoContextoUsuario::{$factory}($falha);

        $this->assertSame($situacao, $resultado->getSituacao());
        $this->assertNull($resultado->getContexto());
        $this->assertSame($falha, $resultado->getFalha());
    }

    public function testEstadoDisponivelDeveConterEstadoLogicoSemFalha(): void
    {
        $estado = EstadoOperacionalPublicacao::naoPublicado();

        $resultado = ResultadoConsultaEstadoOperacional::disponivel($estado);

        $this->assertSame(SituacaoConsultaEstadoOperacional::DISPONIVEL, $resultado->getSituacao());
        $this->assertSame($estado, $resultado->getEstadoOperacional());
        $this->assertNull($resultado->getFalha());
    }

    #[DataProvider('consultasDeEstadoSemResultado')]
    public function testConsultaDeEstadoSemResultadoDeveConterFalhaSegura(
        SituacaoConsultaEstadoOperacional $situacao,
        string $factory
    ): void {
        $falha = new FalhaSegura('ESTADO_INDISPONIVEL', 'Estado indisponível.');

        $resultado = ResultadoConsultaEstadoOperacional::{$factory}($falha);

        $this->assertSame($situacao, $resultado->getSituacao());
        $this->assertNull($resultado->getEstadoOperacional());
        $this->assertSame($falha, $resultado->getFalha());
    }

    public function testPersistenciaConcluidaNaoDeveFabricarFalha(): void
    {
        $resultado = ResultadoPersistenciaLocal::concluida();

        $this->assertSame(SituacaoPersistenciaLocal::CONCLUIDA, $resultado->getSituacao());
        $this->assertNull($resultado->getFalha());
    }

    public function testFalhaDePersistenciaDevePermanecerTipada(): void
    {
        $falha = new FalhaSegura('PERSISTENCIA_FALHOU', 'Persistência local falhou.');

        $resultado = ResultadoPersistenciaLocal::falhaSistemica($falha);

        $this->assertSame(SituacaoPersistenciaLocal::FALHA_SISTEMICA, $resultado->getSituacao());
        $this->assertSame($falha, $resultado->getFalha());
    }

    /** @return array<string, array{SituacaoResolucaoContextoUsuario, string}> */
    public static function contextosNaoResolvidos(): array
    {
        return [
            'não resolvido' => [
                SituacaoResolucaoContextoUsuario::NAO_RESOLVIDO,
                'naoResolvido',
            ],
            'falha sistêmica' => [
                SituacaoResolucaoContextoUsuario::FALHA_SISTEMICA,
                'falhaSistemica',
            ],
        ];
    }

    /** @return array<string, array{SituacaoConsultaEstadoOperacional, string}> */
    public static function consultasDeEstadoSemResultado(): array
    {
        return [
            'conflito' => [SituacaoConsultaEstadoOperacional::CONFLITO, 'conflito'],
            'falha sistêmica' => [
                SituacaoConsultaEstadoOperacional::FALHA_SISTEMICA,
                'falhaSistemica',
            ],
        ];
    }

    private function criarContexto(): ContextoUsuario
    {
        return new ContextoUsuario(
            new Representante(709, 'Maria da Silva'),
            new Lotacao(159, 'Coordenadoria de Tecnologia')
        );
    }
}
