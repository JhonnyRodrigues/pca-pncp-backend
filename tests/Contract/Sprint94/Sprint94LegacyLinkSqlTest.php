<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint94;

use PHPUnit\Framework\TestCase;

final class Sprint94LegacyLinkSqlTest extends TestCase
{
    private string $sql;

    protected function setUp(): void
    {
        $contents = file_get_contents(__DIR__ . '/checkpoints/vincular_legados_individuais_2026.sql');
        $this->assertIsString($contents);
        $this->sql = $contents;
    }

    public function testDeveManterEscopoExatoDosTresLegadosComBaselineAprovado(): void
    {
        $this->assertStringContainsString(
            '7823d46dae06aff72119d2c1d2262afa2c4af82e24e28ae2ce69d1d296345975',
            $this->sql
        );
        $this->assertStringContainsString('p.ANO_EXERCICIO = 2026', $this->sql);
        $this->assertStringContainsString('V_UO_3 <> 3', $this->sql);
        $this->assertStringContainsString('V_UO_94 <> 38', $this->sql);
        $this->assertStringContainsString('V_UO_149 <> 71', $this->sql);
        $this->assertStringContainsString("WHEN 3 THEN '207'", $this->sql);
        $this->assertStringContainsString("WHEN 94 THEN '210'", $this->sql);
        $this->assertStringContainsString("WHEN 149 THEN '205'", $this->sql);
        $this->assertStringContainsString('WHEN 3 THEN 2', $this->sql);
        $this->assertStringContainsString('WHEN 94 THEN 3', $this->sql);
        $this->assertStringContainsString('WHEN 149 THEN 4', $this->sql);
    }

    public function testDeveAtualizarSomenteAProjecaoOperacionalSemCommitAutomatico(): void
    {
        $withoutComments = preg_replace('/^--.*$/m', '', $this->sql);
        $this->assertIsString($withoutComments);
        $this->assertSame(1, preg_match_all('/\bUPDATE\s+SGI\.PAC_PLANOS\b/i', $withoutComments));
        $this->assertDoesNotMatchRegularExpression('/\bCOMMIT\b/i', $withoutComments);
        $this->assertMatchesRegularExpression('/\bROLLBACK\b/i', $withoutComments);
        $this->assertDoesNotMatchRegularExpression(
            '/\b(?:PAC_DEMANDAS|PAC_HISTORICOS|PAC_CONTROLES_CONCORRENCIA)\b/i',
            $withoutComments
        );
        $this->assertDoesNotMatchRegularExpression('/\bDATA_PUBLICACAO\s*=/i', $withoutComments);

        $set = preg_match('/\bSET\b(?<set>.*?)\bWHERE\b/is', $withoutComments, $matches);
        $this->assertSame(1, $set);
        $columns = [];
        preg_match_all('/p\.([A-Z_]+)\s*=/i', $matches['set'], $assignments);
        foreach ($assignments[1] as $column) {
            $columns[] = strtoupper($column);
        }
        $this->assertSame([
            'ESTRATEGIA_ADOTADA_PUBLICACAO',
            'UASG_ADOTADA',
            'ESTADO_PUBLICACAO',
            'SEQUENCIAL_PUBLICACAO_PNCP',
        ], $columns);
    }

    public function testDeveExigirLockPrecondicoesCardinalidadeEPosCheck(): void
    {
        $this->assertStringContainsString('FOR UPDATE', strtoupper($this->sql));
        $this->assertStringContainsString('V_ATUALIZADAS <> 112', $this->sql);
        $this->assertStringContainsString('V_CONFIRMADAS <> 112', $this->sql);
        $this->assertGreaterThanOrEqual(8, substr_count($this->sql, 'RAISE_APPLICATION_ERROR'));
        foreach ([
            'ESTRATEGIA_ADOTADA_PUBLICACAO IS NULL',
            'UASG_ADOTADA IS NULL',
            'ESTADO_PUBLICACAO IS NULL',
            'SEQUENCIAL_PUBLICACAO_PNCP IS NULL',
        ] as $precondition) {
            $this->assertStringContainsString($precondition, $this->sql);
        }
    }
}
