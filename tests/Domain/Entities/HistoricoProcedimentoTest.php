<?php

declare(strict_types=1);

namespace Tests\Domain\Entities;

use DateTimeImmutable;
use Semae\Pca\Domain\Exceptions\HistoricoProcedimentoException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Semae\Pca\Domain\Entities\HistoricoProcedimento;
use Semae\Pca\Domain\Entities\Procedimento;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Representante;

class HistoricoProcedimentoTest extends TestCase
{
    public function testDeveCriarHistoricoProcedimentoValido(): void
    {
        $dataInsercao = new DateTimeImmutable('2026-07-15 14:25:00');
        $procedimento = new Procedimento(5, 'PUBLICAÇÃO DE PLANO');
        $representante = new Representante(709, 'Johnny B. Goodie');
        $lotacao = new Lotacao(159, 'Coordenadoria de Tecnologia');

        $historico = new HistoricoProcedimento(
            $dataInsercao,
            $procedimento,
            'O Plano de Compras e Contratações da unidade orçamentária Coordenadoria de Tecnologia para o ano 2027 foi publicado.',
            $representante,
            $lotacao
        );

        $this->assertSame($procedimento, $historico->getProcedimento());
        $this->assertSame($dataInsercao, $historico->getDataInsercao());
        $this->assertSame('O Plano de Compras e Contratações da unidade orçamentária Coordenadoria de Tecnologia para o ano 2027 foi publicado.', $historico->getMensagem());
        $this->assertSame($lotacao, $historico->getLotacao());
        $this->assertSame($representante, $historico->getRepresentante());
    }

    public function testDeveRepresentarHistoricoDePublicacao(): void
    {
        $dataInsercao = new DateTimeImmutable('2026-07-15 14:25:00');
        $procedimento = new Procedimento(5, 'PUBLICAÇÃO DE PLANO');
        $mensagem = 'O Plano de Compras e Contratações da unidade orçamentária Coordenadoria de Tecnologia para o ano 2027 foi publicado.';
        $representante = new Representante(709, 'Johnny B. Goodie');
        $lotacao = new Lotacao(159, 'Coordenadoria de Tecnologia');

        $historico = new HistoricoProcedimento(
            $dataInsercao,
            $procedimento,
            $mensagem,
            $representante,
            $lotacao
        );

        $this->assertSame('PUBLICAÇÃO DE PLANO', $historico->getProcedimento()->getDescricao());
        $this->assertSame($mensagem, $historico->getMensagem());
    }

    public function testDeveExigirMensagemQuandoProcedimentoForPublicacao(): void
    {
        $this->expectException(HistoricoProcedimentoException::class);

        new HistoricoProcedimento(
            new DateTimeImmutable('2026-07-15 14:25:00'),
            new Procedimento(5, 'PUBLICAÇÃO DE PLANO'),
            '', // <---
            new Representante(709, 'Johnny B. Goodie'),
            new Lotacao(159, 'Coordenadoria de Tecnologia')
        );
    }

    public function testDeveExigirRepresentanteQuandoProcedimentoForPublicacao(): void
    {
        $this->expectException(HistoricoProcedimentoException::class);

        new HistoricoProcedimento(
            new DateTimeImmutable('2026-07-15 14:25:00'),
            new Procedimento(5, 'PUBLICAÇÃO DE PLANO'),
            'O Plano de Compras e Contratações da unidade orçamentária Coordenadoria de Tecnologia para o ano 2027 foi publicado.',
            null, // <---
            new Lotacao(159, 'Coordenadoria de Tecnologia')
        );
    }

    public function testDeveExigirLotacaoQuandoProcedimentoForPublicacao(): void
    {
        $this->expectException(HistoricoProcedimentoException::class);

        new HistoricoProcedimento(
            new DateTimeImmutable('2026-07-15 14:25:00'),
            new Procedimento(5, 'PUBLICAÇÃO DE PLANO'),
            'O Plano de Compras e Contratações da unidade orçamentária Coordenadoria de Tecnologia para o ano 2027 foi publicado.',
            new Representante(709, 'Johnny B. Goodie'),
            null, // <---
        );
    }

    public function testDeveSerImutavel(): void
    {
        $reflection = new ReflectionClass(HistoricoProcedimento::class);

        foreach ($reflection->getMethods() as $method) {
            $this->assertStringStartsNotWith('set', $method->getName());
        }
    }
}
