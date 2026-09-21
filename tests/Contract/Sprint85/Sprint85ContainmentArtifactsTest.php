<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint85;

use PHPUnit\Framework\TestCase;

final class Sprint85ContainmentArtifactsTest extends TestCase
{
    public function testCheckpointManualDeveMaterializarDozeSelectsSomenteLeitura(): void
    {
        $path = __DIR__ . '/checkpoints/contencao_precheck.sql';

        $this->assertFileExists($path);
        $contents = file_get_contents($path);
        $this->assertIsString($contents);
        $this->assertStringNotContainsString('?', $contents);
        $this->assertStringContainsString(
            'SPRINT85-CONTENCAO-HML-20260921-JSILVA-01',
            $contents
        );

        $withoutComments = preg_replace('/^--.*$/m', '', $contents);
        $this->assertIsString($withoutComments);
        $statements = array_values(array_filter(
            array_map('trim', preg_split('/;\s*(?:\R|$)/', $withoutComments) ?: []),
            static fn (string $statement): bool => $statement !== ''
        ));

        $this->assertCount(12, $statements);
        foreach ($statements as $statement) {
            $this->assertMatchesRegularExpression('/^SELECT\b/i', $statement);
            $this->assertDoesNotMatchRegularExpression(
                '/\b(?:INSERT|UPDATE|DELETE|MERGE|ALTER|DROP|CREATE|TRUNCATE|BEGIN|DECLARE)\b|FOR\s+UPDATE/i',
                $statement
            );
        }
    }

    public function testBlankDeveExporSomenteAsTresEtapasDoGateDeContencao(): void
    {
        $path = __DIR__ . '/blank_scriptcase/teste_contencao_sprint85.php';

        $this->assertFileExists($path);
        $contents = file_get_contents($path);
        $this->assertIsString($contents);
        $this->assertStringContainsString(
            'PCA_SPRINT85_CONFIG_FILE=/var/www/configuracoes/pca-pncp/sprint85.php',
            $contents
        );
        $this->assertStringContainsString(
            'PCA_PNCP_PROFILE_REGISTRY_FILE=/var/www/configuracoes/pca-pncp/registro.php',
            $contents
        );
        $this->assertStringNotContainsString('/var/www/html/', $contents);
        $this->assertStringContainsString('/pca-pncp-containment-gate', $contents);
        $this->assertStringContainsString(
            "\$etapasDisponiveis = ['PRECHECK', 'CONTENCAO', 'DISPONIBILIDADE'];",
            $contents
        );
        $this->assertStringContainsString('executar_precheck_contencao_sprint_85(', $contents);
        $this->assertStringContainsString('executar_contencao_sprint_85(', $contents);
        $this->assertStringContainsString('verificar_disponibilidade_contencao_sprint_85(', $contents);
        $this->assertStringContainsString("'HTTP_X_REQUEST_ID'", $contents);
        $this->assertStringNotContainsString('random_bytes(', $contents);
        $this->assertStringNotContainsString('sc_log_add(', $contents);
        $this->assertStringNotContainsString('PUBLICAR', $contents);
    }

    public function testEmpacotadorDeveSepararGateDeContencaoDoGateDeLogs(): void
    {
        $builder = file_get_contents(dirname(__DIR__, 3) . '/tools/package/build-containment-gate.php');
        $verifier = file_get_contents(dirname(__DIR__, 3) . '/tools/package/verify-containment-gate.php');

        $this->assertIsString($builder);
        $this->assertIsString($verifier);
        $this->assertStringContainsString('PCA_PNCP_CONTAINMENT_GATE_V1', $builder);
        $this->assertStringContainsString('pca-pncp-containment-gate', $builder);
        $this->assertStringContainsString('scriptcase_containment_probe.php', $builder);
        $this->assertStringNotContainsString('Sprint85LogGate.php', $builder);
        $this->assertStringContainsString('PCA_PNCP_CONTAINMENT_GATE_V1', $verifier);
        $this->assertStringContainsString('executar_contencao_sprint_85', $verifier);
    }

    public function testConfiguracaoModeloDeveComecarDesarmadaParaOHml(): void
    {
        $configuration = require __DIR__ . '/containment-config.example.php';

        $this->assertSame('validasgi.semaepiracicaba.sp.gov.br', $configuration['server_name']);
        $this->assertSame('PRECHECK', $configuration['etapa_autorizada']);
        $this->assertNull($configuration['oracle_checkpoint_sha256']);
        $this->assertFalse($configuration['allow_oracle_writes']);
        $this->assertFalse($configuration['allow_pncp_writes']);
        $this->assertFalse($configuration['allow_log_writes']);
        $this->assertSame([3, 94, 149], array_column(
            $configuration['unidades'],
            'id_unidade_orcamentaria'
        ));
        $this->assertSame(0, $configuration['baseline_auditoria']);
    }

}
