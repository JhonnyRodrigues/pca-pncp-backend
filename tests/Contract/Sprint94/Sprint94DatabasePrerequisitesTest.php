<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint94;

use PHPUnit\Framework\TestCase;

final class Sprint94DatabasePrerequisitesTest extends TestCase
{
    private const PROCEDURES_VERSION = 'V202609241700__procedimentos_publicacao_pca';
    private const STRATEGY_VERSION = 'V202609241701__estrategia_individual_publicacao_pca';

    public function testMigrationDeveAssegurarProcedimentosDeRetificacaoEExclusao(): void
    {
        $sql = $this->read('database/migrations/' . self::PROCEDURES_VERSION . '.sql');

        $this->assertStringContainsString('ASSEGURAR_PROCEDIMENTO', $sql);
        $this->assertStringContainsString("8, 'RETIFICAÇÃO DE PLANO'", $sql);
        $this->assertStringContainsString("9, 'EXCLUSÃO DE PLANO NO PNCP'", $sql);
        $this->assertStringContainsString('INSERT INTO SGI.PAC_PROCEDIMENTOS', $sql);
        $this->assertStringContainsString('COMMIT;', $sql);
        $this->assertStringContainsString('ROLLBACK;', $sql);
        $this->assertStringNotContainsString('PAC_HISTORICOS', $sql);
        $this->assertStringNotContainsString('PAC_DEMANDA', $sql);
    }

    public function testMigrationDeveFormalizarEstrategiaIndividualSemReescreverBaseline(): void
    {
        $sql = $this->read('database/migrations/' . self::STRATEGY_VERSION . '.sql');
        $baseline = $this->read(
            'database/migrations/V202609181815__configuracao_publicacao_pca.sql'
        );

        $this->assertStringContainsString("'ESTRATEGIA_PUBLICACAO_PLANOS'", $sql);
        $this->assertStringContainsString("'INDIVIDUAL'", $sql);
        $this->assertStringContainsString("'CONSOLIDADO'", $sql);
        $this->assertStringContainsString('UPDATE SGI.PAC_CONFIGURACOES', $sql);
        $this->assertStringContainsString('FOR UPDATE', $sql);
        $this->assertStringContainsString('COMMIT;', $sql);
        $this->assertStringContainsString('ROLLBACK;', $sql);
        $this->assertStringContainsString("'CONSOLIDADO'", $baseline);
    }

    public function testChecksDevemSerSomenteLeituraEUsarMesmasVersoes(): void
    {
        foreach ([self::PROCEDURES_VERSION, self::STRATEGY_VERSION] as $version) {
            foreach (['precheck', 'postcheck'] as $stage) {
                $sql = $this->read('database/checks/' . $version . '_' . $stage . '.sql');
                $withoutComments = preg_replace('/--.*$/m', '', $sql);

                $this->assertIsString($withoutComments);
                $this->assertStringContainsString('SELECT ', strtoupper($withoutComments));
                $this->assertDoesNotMatchRegularExpression(
                    '/\b(?:INSERT|UPDATE|DELETE|MERGE|COMMIT|ROLLBACK)\b/i',
                    $withoutComments
                );
            }
        }
    }

    public function testConsultaOperacionalDeveLocalizarLogsPorBindSemCorrelationIdFixo(): void
    {
        $sql = $this->read('database/queries/seguranca_log_por_correlacao.sql');
        $withoutComments = preg_replace('/--.*$/m', '', $sql);

        $this->assertIsString($withoutComments);
        $this->assertStringContainsString(':correlacao', $sql);
        $this->assertStringContainsString('DBMS_LOB.INSTR', $sql);
        $this->assertStringContainsString("'$.metadata.status_http'", $sql);
        $this->assertStringContainsString("'$.metadata.statusHttp'", $sql);
        $this->assertStringContainsString("'$.context.decisao'", $sql);
        $this->assertDoesNotMatchRegularExpression(
            '/[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}/i',
            $sql
        );
        $this->assertDoesNotMatchRegularExpression(
            '/\b(?:INSERT|UPDATE|DELETE|MERGE|COMMIT|ROLLBACK)\b/i',
            $withoutComments
        );
    }

    public function testDocumentacaoDevePreservarPreRequisitosESituacaoOperacional(): void
    {
        $guide = $this->read('docs/vigentes/operacional/guia_de_configuracao_de_producao.md');
        $roadmap = $this->read('docs/vigentes/autoritativos/roadmap.md');
        $situation = $this->read('docs/vigentes/operacional/situacao_operacional.md');

        $this->assertStringContainsString(self::PROCEDURES_VERSION . '.sql', $guide);
        $this->assertStringContainsString(self::STRATEGY_VERSION . '.sql', $guide);
        $this->assertStringContainsString('guia operacional de diagnóstico da publicação PCA', $roadmap);
        $this->assertStringContainsString('`curl_errno`', $roadmap);
        $this->assertStringContainsString('procedimentos de auditoria 8 e 9', $situation);
        $this->assertStringContainsString('migrations verificadas em produção', $situation);

        $deployment = $this->read('database/deployments/producao.md');
        foreach (['V202609241700', 'V202609241701', 'V202609251823'] as $version) {
            $this->assertMatchesRegularExpression(
                '/^\| [^\r\n]*`' . $version . '`[^\r\n]*\| `APLICADA` \|/m',
                $deployment
            );
        }
    }

    private function read(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3) . '/' . $relativePath);

        $this->assertIsString($contents, 'Arquivo ausente: ' . $relativePath);

        return $contents;
    }
}
