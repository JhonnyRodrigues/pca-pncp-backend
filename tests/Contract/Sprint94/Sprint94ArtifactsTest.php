<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint94;

use PHPUnit\Framework\TestCase;

final class Sprint94ArtifactsTest extends TestCase
{
    public function testBlankDeveExporSomentePrecheckEInventario(): void
    {
        $conteudo = file_get_contents(__DIR__ . '/blank_scriptcase/teste_inventario_sprint94.php');

        $this->assertIsString($conteudo);
        $this->assertStringContainsString("['PRECHECK', 'INVENTARIO']", $conteudo);
        $this->assertStringContainsString('executar_precheck_sprint_94(', $conteudo);
        $this->assertStringContainsString('executar_inventario_sprint_94(', $conteudo);
        $this->assertStringNotContainsString('sc_exec_sql', $conteudo);
        $this->assertStringNotContainsString('PUBLICAR', $conteudo);
    }

    public function testEmpacotadorDeveManterGateSeparadoDaRelease(): void
    {
        $construtor = file_get_contents(dirname(__DIR__, 3) . '/tools/package/build-sprint94-inventory-gate.php');
        $verificador = file_get_contents(dirname(__DIR__, 3) . '/tools/package/verify-sprint94-inventory-gate.php');

        $this->assertIsString($construtor);
        $this->assertIsString($verificador);
        $this->assertStringContainsString('PCA_PNCP_SPRINT94_INVENTORY_GATE_V1', $construtor);
        $this->assertStringContainsString('pca-pncp-sprint94-inventory-gate', $construtor);
        $this->assertStringContainsString('PCA_PNCP_SPRINT94_INVENTORY_GATE_V1', $verificador);
    }

    public function testCorrecao3ADeveSerEmpacotadaSeparadamenteDaReleaseOficial(): void
    {
        $raiz = dirname(__DIR__, 3);
        $construtor = file_get_contents(
            $raiz . '/tools/package/build-sprint94-correction-3a.php'
        );
        $verificador = file_get_contents(
            $raiz . '/tools/package/verify-sprint94-correction-3a.php'
        );
        $arquivador = file_get_contents(
            $raiz . '/tools/package/archive-sprint94-correction-3a.php'
        );
        $composer = json_decode(
            (string) file_get_contents($raiz . '/composer.json'),
            true,
            32,
            JSON_THROW_ON_ERROR
        );

        $this->assertIsString($construtor);
        $this->assertIsString($verificador);
        $this->assertIsString($arquivador);
        $this->assertStringContainsString('PCA_PNCP_SPRINT94_CORRECAO_3A_V1', $construtor);
        $this->assertStringContainsString("'release_oficial' => false", $construtor);
        $this->assertStringContainsString('pca-pncp-backend', $construtor);
        $this->assertStringContainsString('scriptcase/PCA_PUBLICAR_B/onExecute.php', $construtor);
        $this->assertStringContainsString('PCA_PNCP_SPRINT94_CORRECAO_3A_V1', $verificador);
        $this->assertStringContainsString('/tools/package/verify.php', $verificador);
        $this->assertStringContainsString('topologia_segura', $arquivador);
        $this->assertSame(
            'php tools/package/build-sprint94-correction-3a.php',
            $composer['scripts']['package:sprint94:correction-3a'] ?? null
        );
        $this->assertSame(
            'php tools/package/verify-sprint94-correction-3a.php',
            $composer['scripts']['package:sprint94:correction-3a:verify'] ?? null
        );
        $this->assertSame(
            'php tools/package/archive-sprint94-correction-3a.php',
            $composer['scripts']['package:sprint94:correction-3a:archive'] ?? null
        );
    }

    public function testInventarioDeveValidarEscopoOracleAntesDeAutenticarNoPncp(): void
    {
        $conteudo = file_get_contents(__DIR__ . '/Support/Sprint94InventoryGate.php');

        $this->assertIsString($conteudo);
        $posicaoEscopo = strpos($conteudo, 'new Sprint94OracleScope()');
        $posicaoAutenticacao = strpos($conteudo, 'new AutenticadorPncpAdapter(');
        $this->assertIsInt($posicaoEscopo);
        $this->assertIsInt($posicaoAutenticacao);
        $this->assertLessThan($posicaoAutenticacao, $posicaoEscopo);
    }
}
