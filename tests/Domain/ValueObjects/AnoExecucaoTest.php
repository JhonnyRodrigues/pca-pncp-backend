<?php

namespace Tests\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\Exceptions\AnoExecucaoInvalidoException;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;

class AnoExecucaoTest extends TestCase
{
    public function testDeveAceitarAnoAtual(): void
    {
        $anoAtual = (int) date('Y');

        $ano = new AnoExecucao($anoAtual);

        $this->assertSame($anoAtual, $ano->valor());
    }

    public function testDeveAceitarProximoAno(): void
    {
        $proximoAno = (int) date('Y') + 1;

        $ano = new AnoExecucao($proximoAno);

        $this->assertSame($proximoAno, $ano->valor());
    }

    public function testDeveRejeitarAnoAnteriorAoAtual(): void
    {
        $this->expectException(AnoExecucaoInvalidoException::class);

        new AnoExecucao((int) date('Y') - 1);
    }

    public function testDeveRejeitarAnoPosteriorAoProximoAno(): void
    {
        $this->expectException(AnoExecucaoInvalidoException::class);

        new AnoExecucao((int) date('Y') + 2);
    }
}
