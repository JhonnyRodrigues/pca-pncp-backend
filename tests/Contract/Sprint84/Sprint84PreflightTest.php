<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Contract\Sprint84\Support\Sprint84ConfigurationException;
use Tests\Contract\Sprint84\Support\Sprint84DiagnosticReport;
use Tests\Contract\Sprint84\Support\Sprint84ErrorReport;

final class Sprint84PreflightTest extends TestCase
{
    public function testRelatorioDeErroDevePreservarDiagnosticoSemVazarSegredo(): void
    {
        $causa = new RuntimeException('causa interna controlada');
        $erro = new Sprint84ConfigurationException(
            'CONFIG_CAMPO_INVALIDO',
            'perfil.pncp.senha',
            'texto não vazio',
            'senha-secreta',
            $causa
        );

        $relatorio = Sprint84ErrorReport::fromThrowable('CONFIGURACAO', $erro);
        $json = json_encode($relatorio, JSON_THROW_ON_ERROR);

        $this->assertSame('8.4', $relatorio['sprint']);
        $this->assertSame('CONFIGURACAO', $relatorio['fase']);
        $this->assertSame('CONFIG_CAMPO_INVALIDO', $relatorio['erro']['codigo']);
        $this->assertSame('perfil.pncp.senha', $relatorio['erro']['campo']);
        $this->assertSame('[PROTEGIDO]', $relatorio['erro']['valor_recebido']);
        $this->assertSame([
            'presente' => true,
            'tipo' => 'string',
            'tamanho' => 13,
        ], $relatorio['erro']['metadados_valor_protegido']);
        $this->assertSame(RuntimeException::class, $relatorio['erro']['causa']['classe']);
        $this->assertArrayHasKey('stack', $relatorio['erro']);
        $this->assertStringNotContainsString('senha-secreta', $json);
    }

    public function testRelatorioFinalDeveRemoverSegredoTambemDeMensagensEStack(): void
    {
        $relatorio = Sprint84DiagnosticReport::finish([
            'perfil' => ['pncp' => ['senha' => 'segredo-84']],
            'erro' => [
                'mensagem' => 'Falha causada por segredo-84',
                'stack' => 'stack com segredo-84',
            ],
        ]);
        $json = json_encode($relatorio, JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('segredo-84', $json);
        $this->assertSame('[PROTEGIDO]', $relatorio['perfil']['pncp']['senha']);
        $this->assertSame('Falha causada por [PROTEGIDO]', $relatorio['erro']['mensagem']);
        $this->assertArrayHasKey('sha256_relatorio', $relatorio);
    }

    public function testRelatorioDeErroNaoDeveOcultarCausaComByteInvalido(): void
    {
        $relatorio = Sprint84DiagnosticReport::finish([
            'erro' => ['mensagem' => "Descri\xE7\xE3o Windows-1252 inválida em UTF-8"],
        ]);

        $this->assertTrue(mb_check_encoding($relatorio['erro']['mensagem'], 'UTF-8'));
        $this->assertStringContainsString('Descri��o', $relatorio['erro']['mensagem']);
        $this->assertSame(64, strlen($relatorio['sha256_relatorio']));
    }
}
