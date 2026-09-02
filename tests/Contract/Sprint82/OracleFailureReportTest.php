<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint82;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Contract\Sprint82\Support\OracleFailureReport;
use Tests\Contract\Support\OracleContractFailure;
use Tests\Contract\Support\SafeContractReport;

final class OracleFailureReportTest extends TestCase
{
    private string|false $debugAnterior;

    protected function setUp(): void
    {
        $this->debugAnterior = getenv('PCA_SPRINT82_DEBUG_ORACLE');
        putenv('PCA_SPRINT82_DEBUG_ORACLE');
    }

    protected function tearDown(): void
    {
        $this->debugAnterior === false
            ? putenv('PCA_SPRINT82_DEBUG_ORACLE')
            : putenv('PCA_SPRINT82_DEBUG_ORACLE=' . $this->debugAnterior);
    }

    public function testDeveInformarEtapaSeguraSemDetalheTecnicoPorPadrao(): void
    {
        $falha = new OracleContractFailure(
            'commit_controlado',
            new RuntimeException('SELECT segredo FROM tabela_interna')
        );

        $relatorio = OracleFailureReport::create('commit', $falha, []);

        $this->assertSame('8.2', $relatorio['sprint']);
        $this->assertSame('commit_controlado', $relatorio['falha']['etapa']);
        $this->assertArrayNotHasKey('tipo', $relatorio['falha']);
        $this->assertStringNotContainsString(
            'segredo',
            SafeContractReport::encode($relatorio, ['segredo'])
        );
    }

    public function testDebugDeveSanitizarCausaTecnicaSomenteNosModosDiagnosticos(): void
    {
        putenv('PCA_SPRINT82_DEBUG_ORACLE=true');
        $falha = new OracleContractFailure(
            'reconstrucao_plano',
            new RuntimeException(
                'Falha usuario-secreto senha=senha-real; '
                . 'Authorization: Bearer token-real; '
                . 'SELECT * FROM SGI.PAC_PLANOS'
            )
        );

        $relatorio = OracleFailureReport::create(
            'adapters',
            $falha,
            ['usuario-secreto', 'senha-real', 'token-real']
        );
        $json = SafeContractReport::encode(
            $relatorio,
            ['usuario-secreto', 'senha-real', 'token-real']
        );

        $this->assertSame(RuntimeException::class, $relatorio['falha']['tipo']);
        $this->assertStringNotContainsString('SELECT *', $json);
        $this->assertStringNotContainsString('Authorization', $json);
    }
}
