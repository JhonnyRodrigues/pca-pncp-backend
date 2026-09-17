<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84;

use PHPUnit\Framework\TestCase;

final class Sprint84EntrypointTest extends TestCase
{
    private string|false $previousConfig;

    protected function setUp(): void
    {
        $this->previousConfig = getenv('PCA_SPRINT84_CONFIG_FILE');
        putenv('PCA_SPRINT84_CONFIG_FILE');
        require_once __DIR__ . '/scriptcase_probe.php';
    }

    protected function tearDown(): void
    {
        $this->previousConfig === false
            ? putenv('PCA_SPRINT84_CONFIG_FILE')
            : putenv('PCA_SPRINT84_CONFIG_FILE=' . $this->previousConfig);
    }

    public function testEntrypointDeveDetalharErroDeConfiguracaoAntesDeIo(): void
    {
        $report = \executar_fluxos_completos_sprint_84(
            new \stdClass(),
            'usuario.teste',
            'pca.homologacao.test',
            '00000000-0000-4000-8000-000000000084',
            'PRECHECK'
        );

        $this->assertSame('RECUSADO', $report['preflight']);
        $this->assertSame('CONFIGURACAO', $report['fase']);
        $this->assertSame('CONFIG_LOCALIZADOR_AUSENTE', $report['erro']['codigo']);
        $this->assertSame('PCA_SPRINT84_CONFIG_FILE', $report['erro']['campo']);
        $this->assertSame('caminho absoluto não vazio', $report['erro']['esperado']);
        $this->assertSame('bool', $report['erro']['tipo_recebido']);
        $this->assertFalse($report['erro']['valor_recebido']);
        $this->assertArrayHasKey('arquivo', $report['erro']);
        $this->assertArrayHasKey('linha', $report['erro']);
        $this->assertArrayHasKey('stack', $report['erro']);
        $this->assertArrayHasKey('sha256_relatorio', $report);
    }

    public function testLimpezaPadraoDeveSerRecusadaAntesDeIoComMesmoDiagnostico(): void
    {
        $report = \executar_limpeza_emergencial_sprint_84(
            new \stdClass(),
            'usuario.teste',
            'pca.homologacao.test',
            '00000000-0000-4000-8000-000000000084'
        );

        $this->assertSame('RECUSADO', $report['preflight']);
        $this->assertSame('CONFIG_LOCALIZADOR_AUSENTE', $report['erro']['codigo']);
        $this->assertArrayHasKey('sha256_relatorio', $report);
    }

    public function testSuiteSeparadaDeveExcluirGrupoRealPorPadrao(): void
    {
        $xml = file_get_contents(dirname(__DIR__, 3) . '/phpunit.sprint84.contract.xml');
        $composer = file_get_contents(dirname(__DIR__, 3) . '/composer.json');

        $this->assertIsString($xml);
        $this->assertStringContainsString('<group>sprint84-live</group>', $xml);
        $this->assertIsString($composer);
        $this->assertStringContainsString('contract:sprint84:tests', $composer);
        $this->assertStringContainsString('contract:sprint84:preflight', $composer);
    }

    public function testBlankScriptcaseDevePreservarDiagnosticoIntegralERecusarEtapaInvalida(): void
    {
        $blank = file_get_contents(
            __DIR__ . '/blank_scriptcase/teste_adapters_contracts_sprint84.php'
        );

        $this->assertIsString($blank);
        $this->assertStringContainsString("'RETIFICAR',", $blank);
        $this->assertStringNotContainsString("'RETIFICAR ',", $blank);
        $this->assertStringContainsString('Sprint84ErrorReport::fromThrowable', $blank);
        $this->assertStringContainsString('JSON_INVALID_UTF8_SUBSTITUTE', $blank);
        $this->assertStringNotContainsString("'codigo' => 'EXECUCAO_FALHOU'", $blank);
        $this->assertMatchesRegularExpression(
            '/if \(!in_array\(\$etapa, \$etapas, true\)\).*?return;/s',
            $blank
        );
    }
}
