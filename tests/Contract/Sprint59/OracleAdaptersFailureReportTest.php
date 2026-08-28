<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint59;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\Pca\Infrastructure\Oracle\Adodb\FalhaOracleException;
use Tests\Contract\Sprint59\Support\OracleAdaptersContractFailure;
use Tests\Contract\Sprint59\Support\OracleAdaptersFailureReport;
use Tests\Contract\Sprint59\Support\SafeContractReport;

final class OracleAdaptersFailureReportTest extends TestCase
{
    private string|false $debugAnterior;

    protected function setUp(): void
    {
        $this->debugAnterior = getenv('PCA_SPRINT59_DEBUG_ORACLE');
        putenv('PCA_SPRINT59_DEBUG_ORACLE');
    }

    protected function tearDown(): void
    {
        if ($this->debugAnterior === false) {
            putenv('PCA_SPRINT59_DEBUG_ORACLE');

            return;
        }

        putenv('PCA_SPRINT59_DEBUG_ORACLE=' . $this->debugAnterior);
    }

    public function testDeveIdentificarEtapaEPreservarMensagemGenericaSemDebug(): void
    {
        $failure = new OracleAdaptersContractFailure(
            'consulta_estado',
            new RuntimeException('ORA-00942: tabela de desenvolvimento ausente')
        );

        $report = OracleAdaptersFailureReport::create('adapters', $failure, []);

        $this->assertFalse($report['confirmado']);
        $this->assertSame(
            'CONTRATO_ORACLE_SPRINT_59_FALHOU',
            $report['falha']['codigo']
        );
        $this->assertSame('consulta_estado', $report['falha']['etapa']);
        $this->assertSame(
            'O contrato Oracle foi recusado sem exposicao de detalhes internos.',
            $report['falha']['mensagem']
        );
        $this->assertArrayNotHasKey('tipo', $report['falha']);
        $this->assertFalse($report['close_invocado']);
    }

    public function testDeveExporCausaTecnicaSanitizadaSomenteComOptIn(): void
    {
        putenv('PCA_SPRINT59_DEBUG_ORACLE=true');
        $failure = new OracleAdaptersContractFailure(
            'reconstrucao_plano',
            new RuntimeException(
                'Falha PHP para usuario-secreto; senha=senha-real; '
                . 'Authorization: Bearer token-real; CNPJ 12345678000195; '
                . 'URL https://interno.exemplo/segredo; '
                . 'SQL SELECT * FROM SGI.PAC_PLANOS WHERE LOGIN = usuario-secreto'
            )
        );

        $report = OracleAdaptersFailureReport::create(
            'adapters',
            $failure,
            ['usuario-secreto', 'senha-real', 'token-real']
        );
        $json = SafeContractReport::encode(
            $report,
            ['usuario-secreto', 'senha-real', 'token-real', '12345678000195']
        );

        $this->assertSame('reconstrucao_plano', $report['falha']['etapa']);
        $this->assertSame(RuntimeException::class, $report['falha']['tipo']);
        $this->assertStringContainsString('Falha PHP', $report['falha']['mensagem']);
        $this->assertStringNotContainsString('SELECT *', $json);
        $this->assertStringNotContainsString('Authorization', $json);
        $this->assertStringNotContainsString('https://', $json);
        $this->assertStringNotContainsString('12345678000195', $json);
    }

    public function testDevePreservarRelatorioGenericoNosModosDeLock(): void
    {
        putenv('PCA_SPRINT59_DEBUG_ORACLE=true');

        foreach (['lock_holder', 'lock_expect_blocked', 'lock_expect_available'] as $mode) {
            $report = OracleAdaptersFailureReport::create(
                $mode,
                new RuntimeException('detalhe tecnico'),
                []
            );

            $this->assertSame($mode, $report['modo']);
            $this->assertSame(
                [
                    'codigo' => 'CONTRATO_ORACLE_SPRINT_59_FALHOU',
                    'mensagem' => 'O contrato Oracle foi recusado sem exposicao de detalhes internos.',
                ],
                $report['falha']
            );
            $this->assertFalse($report['close_invocado']);
        }
    }

    public function testDeveIncluirCodigoDaExcecaoOracleSemConsultarMensagemAdodb(): void
    {
        putenv('PCA_SPRINT59_DEBUG_ORACLE=true');
        $failure = new OracleAdaptersContractFailure(
            'bind',
            new FalhaOracleException('Falha ao consultar dados no Oracle.', 'ORA-00942')
        );

        $report = OracleAdaptersFailureReport::create('adapters', $failure, []);

        $this->assertSame(FalhaOracleException::class, $report['falha']['tipo']);
        $this->assertSame(
            'Falha ao consultar dados no Oracle. (ORA-00942)',
            $report['falha']['mensagem']
        );
    }
}
