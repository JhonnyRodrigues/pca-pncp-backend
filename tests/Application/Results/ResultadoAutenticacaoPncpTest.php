<?php

declare(strict_types=1);

namespace Tests\Application\Results;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Enums\SituacaoAutenticacaoPncp;
use Semae\Pca\Application\Results\ResultadoAutenticacaoPncp;
use Semae\Pca\Application\ValueObjects\FalhaSegura;

final class ResultadoAutenticacaoPncpTest extends TestCase
{
    public function testAutenticacaoConcluidaDeveExporSomenteSessaoOpaca(): void
    {
        $sessao = new class implements SessaoPncp {};

        $resultado = ResultadoAutenticacaoPncp::autenticada($sessao);

        $this->assertSame(SituacaoAutenticacaoPncp::AUTENTICADA, $resultado->getSituacao());
        $this->assertSame($sessao, $resultado->getSessao());
        $this->assertNull($resultado->getFalha());
    }

    public function testFalhaSistemicaDeveExporSomenteDiagnosticoSeguro(): void
    {
        $falha = new FalhaSegura('AUTENTICACAO_INDISPONIVEL', 'Autenticação indisponível.');

        $resultado = ResultadoAutenticacaoPncp::falhaSistemica($falha);

        $this->assertSame(
            SituacaoAutenticacaoPncp::FALHA_SISTEMICA,
            $resultado->getSituacao()
        );
        $this->assertNull($resultado->getSessao());
        $this->assertSame($falha, $resultado->getFalha());
    }
}
