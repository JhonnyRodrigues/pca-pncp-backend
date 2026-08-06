<?php

declare(strict_types=1);

namespace Tests\Application\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Results\ResultadoEscritaPncp;
use Semae\Pca\Application\Results\ResultadoPublicacaoPncp;
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
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class ContextoFinalizacaoProcessamentoUnidadeTest extends TestCase
{
    /** @decisao DI-004 */
    public function testDevePreservarTodosOsFatosEResultadosDeEscrita(): void
    {
        $plano = $this->criarPlano();
        $unidade = UnidadeExternaPublicacao::individual($plano->getIdentidade());
        $sessao = new SessaoPncpFakeContextoFinalizacao();
        $contextoUsuario = new ContextoUsuario(
            new Representante(709, 'Maria da Silva'),
            new Lotacao(159, 'Coordenadoria de Tecnologia')
        );
        $cnpj = new Cnpj('12.345.678/0001-95');
        $sequencial = new SequencialPca(77);
        $estado = EstadoOperacionalPublicacao::publicado($sequencial);
        $observado = PcaObservado::ativo($sequencial, [1001]);
        $decisao = ResultadoDecisaoProcessamentoPlano::retificar([1002], [1001]);
        $resultadosEscrita = [
            ResultadoEscritaPncp::aceita(),
            ResultadoEscritaPncp::resultadoIncerto(
                new FalhaSegura('INCLUSAO_INCERTA', 'Resultado da inclusão é incerto.')
            ),
        ];

        $contexto = new ContextoFinalizacaoProcessamentoUnidade(
            $contextoUsuario,
            $sessao,
            $cnpj,
            $unidade,
            [$plano],
            $estado,
            $observado,
            $decisao,
            $resultadosEscrita
        );

        $this->assertSame($contextoUsuario, $contexto->getContextoUsuario());
        $this->assertSame($sessao, $contexto->getSessaoPncp());
        $this->assertSame($cnpj, $contexto->getCnpj());
        $this->assertSame($unidade, $contexto->getUnidadeExterna());
        $this->assertSame([$plano], $contexto->getPlanos());
        $this->assertSame($estado, $contexto->getEstadoOperacionalInicial());
        $this->assertSame($observado, $contexto->getPcaObservadoInicial());
        $this->assertSame($decisao, $contexto->getResultadoDecisao());
        $this->assertSame($resultadosEscrita, $contexto->getResultadosEscrita());
    }

    public function testDeveAceitarNenhumaAcaoSemResultadoDeEscrita(): void
    {
        $contexto = $this->criarContexto(
            ResultadoDecisaoProcessamentoPlano::nenhumaAcao(),
            []
        );

        $this->assertSame([], $contexto->getResultadosEscrita());
    }

    public function testDeveRejeitarAusenciaDePlanosRelidos(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Finalização exige ao menos um Plano relido.');

        $this->criarContexto(
            ResultadoDecisaoProcessamentoPlano::nenhumaAcao(),
            [],
            []
        );
    }

    public function testDeveRejeitarResultadoDeEscritaComFalhaSistemica(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Falha sistêmica de escrita não pode ser encaminhada ao finalizador.'
        );

        $this->criarContexto(
            ResultadoDecisaoProcessamentoPlano::excluirPca(),
            [ResultadoEscritaPncp::falhaSistemica(
                new FalhaSegura('PNCP_INDISPONIVEL', 'PNCP indisponível.')
            )]
        );
    }

    public function testDeveRejeitarResultadosDeEscritaParaNenhumaAcao(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('NENHUMA_ACAO não admite resultado de escrita.');

        $this->criarContexto(
            ResultadoDecisaoProcessamentoPlano::nenhumaAcao(),
            [ResultadoEscritaPncp::aceita()]
        );
    }

    public function testDeveRejeitarResultadoDePublicacaoParaExclusaoIntegral(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resultado de escrita incompatível com a decisão.');

        $this->criarContexto(
            ResultadoDecisaoProcessamentoPlano::excluirPca(),
            [ResultadoPublicacaoPncp::aceita(new SequencialPca(77))]
        );
    }

    /**
     * @param array<ResultadoEscritaPncp|ResultadoPublicacaoPncp> $resultadosEscrita
     * @param Plano[]|null $planos
     */
    private function criarContexto(
        ResultadoDecisaoProcessamentoPlano $decisao,
        array $resultadosEscrita,
        ?array $planos = null
    ): ContextoFinalizacaoProcessamentoUnidade {
        $plano = $this->criarPlano();
        $sequencial = new SequencialPca(77);

        return new ContextoFinalizacaoProcessamentoUnidade(
            new ContextoUsuario(
                new Representante(709, 'Maria da Silva'),
                new Lotacao(159, 'Coordenadoria de Tecnologia')
            ),
            new SessaoPncpFakeContextoFinalizacao(),
            new Cnpj('12.345.678/0001-95'),
            UnidadeExternaPublicacao::individual($plano->getIdentidade()),
            $planos ?? [$plano],
            EstadoOperacionalPublicacao::publicado($sequencial),
            PcaObservado::ativo($sequencial, [1001]),
            $decisao,
            $resultadosEscrita
        );
    }

    private function criarPlano(): Plano
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidade = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria('uo-101'),
            new Lotacao(101, 'Diretoria Administrativa'),
            new Uasg('928344', 'SEMAE Piracicaba'),
            'Diretoria Administrativa'
        );

        return new Plano(
            new IdentidadePlano($unidade, $ano),
            2,
            new DateTimeImmutable('2026-07-16'),
            [],
            null
        );
    }
}

final class SessaoPncpFakeContextoFinalizacao implements SessaoPncp
{
}
