<?php

declare(strict_types=1);

namespace Tests\Application\Ports;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Ports\UnidadeDeTrabalhoPort;
use Semae\Pca\Application\Results\ResultadoFinalizacaoUnidadeTrabalho;
use Semae\Pca\Application\Results\ResultadoInicioUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\ChaveUnidadeTrabalho;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;

final class UnidadeDeTrabalhoPortTest extends TestCase
{
    public function testConsumidorDevePoderExpressarOCicloConceitualCompleto(): void
    {
        $port = new UnidadeDeTrabalhoPortFake();
        $chave = ChaveUnidadeTrabalho::estrategiaAnual(
            new AnoExecucao((int) date('Y') + 1)
        );

        $inicio = $port->iniciar($chave);
        $confirmacao = $port->confirmar();
        $desfazimento = $port->desfazer();

        $this->assertSame($chave, $port->chaveRecebida);
        $this->assertSame(
            ResultadoInicioUnidadeTrabalho::iniciada()->getSituacao(),
            $inicio->getSituacao()
        );
        $this->assertSame(
            ResultadoFinalizacaoUnidadeTrabalho::concluida()->getSituacao(),
            $confirmacao->getSituacao()
        );
        $this->assertSame(
            ResultadoFinalizacaoUnidadeTrabalho::concluida()->getSituacao(),
            $desfazimento->getSituacao()
        );
    }
}

final class UnidadeDeTrabalhoPortFake implements UnidadeDeTrabalhoPort
{
    public ?ChaveUnidadeTrabalho $chaveRecebida = null;

    public function iniciar(ChaveUnidadeTrabalho $chave): ResultadoInicioUnidadeTrabalho
    {
        $this->chaveRecebida = $chave;

        return ResultadoInicioUnidadeTrabalho::iniciada();
    }

    public function confirmar(): ResultadoFinalizacaoUnidadeTrabalho
    {
        return ResultadoFinalizacaoUnidadeTrabalho::concluida();
    }

    public function desfazer(): ResultadoFinalizacaoUnidadeTrabalho
    {
        return ResultadoFinalizacaoUnidadeTrabalho::concluida();
    }
}
