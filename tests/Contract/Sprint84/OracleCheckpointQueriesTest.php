<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Tests\Contract\Sprint84\Support\OracleCheckpointQueries;
use Tests\Support\PerfilPublicacaoPcaFixture;

final class OracleCheckpointQueriesTest extends TestCase
{
    public function testDeveMontarOnzeConsultasSomenteLeituraComColunasEBindsExplicitos(): void
    {
        $consultas = (new OracleCheckpointQueries(
            new PerfilPublicacaoPca(PerfilPublicacaoPcaFixture::valido()),
            $this->configuracao(),
            'usuario.teste'
        ))->all();

        $this->assertSame([
            'estrategia_corrente',
            'representante',
            'lotacao_usuario',
            'unidades_orcamentarias',
            'todas_versoes',
            'versoes_vigentes',
            'fotografia_vigente',
            'ciclo_demandas',
            'projecao_operacional',
            'auditoria',
            'controles_concorrencia',
        ], array_keys($consultas));

        foreach ($consultas as $nome => $consulta) {
            $sql = $consulta['sql'];
            $this->assertMatchesRegularExpression('/^SELECT\b/i', ltrim($sql), $nome);
            $this->assertDoesNotMatchRegularExpression(
                '/\b(?:INSERT|UPDATE|DELETE|MERGE|ALTER|DROP|CREATE|TRUNCATE|BEGIN|DECLARE)\b|FOR\s+UPDATE/i',
                $sql,
                $nome
            );
            $this->assertStringContainsString('ORDER BY', strtoupper($sql), $nome);
            $this->assertSame(substr_count($sql, '?'), count($consulta['binds']), $nome);
            $this->assertNotSame([], $consulta['columns'], $nome);
            $this->assertArrayHasKey('expected_cardinality', $consulta, $nome);
        }

        $this->assertSame(
            ['CHAVE_CONFIGURACAO', 'VALOR_CONFIGURACAO', 'DESCRICAO_CONFIGURACAO'],
            $consultas['estrategia_corrente']['columns']
        );
        $this->assertSame(1, $consultas['representante']['expected_cardinality']['exact']);
        $this->assertSame(1, $consultas['lotacao_usuario']['expected_cardinality']['exact']);
        $this->assertSame(2, $consultas['unidades_orcamentarias']['expected_cardinality']['exact']);
        $this->assertSame(2, $consultas['versoes_vigentes']['expected_cardinality']['exact']);
        $this->assertSame(4, $consultas['fotografia_vigente']['expected_cardinality']['exact']);
        $this->assertSame(4, $consultas['ciclo_demandas']['expected_cardinality']['exact']);
        $this->assertSame(2, $consultas['todas_versoes']['expected_cardinality']['exact']);
        $this->assertSame(0, $consultas['auditoria']['expected_cardinality']['exact']);
        $this->assertStringContainsString(
            'UPPER(H.MENSAGEM) LIKE UPPER(?)',
            strtoupper($consultas['auditoria']['sql'])
        );
        $this->assertContains(
            'CLASSIFICACAO_CATALOGO',
            $consultas['fotografia_vigente']['columns']
        );
        $this->assertStringContainsString(
            '2 AS CATALOGO',
            $consultas['fotografia_vigente']['sql']
        );
        $this->assertStringContainsString(
            'D.CLASSIFICACAO_CATALOGO AS CLASSIFICACAO_CATALOGO',
            strtoupper($consultas['fotografia_vigente']['sql'])
        );
    }

    public function testConsolidadoDeveExigirAsDuasChavesDeConfiguracao(): void
    {
        $configuracao = $this->configuracao();
        $configuracao['modo'] = 'CONSOLIDADO';

        $consultas = (new OracleCheckpointQueries(
            new PerfilPublicacaoPca(PerfilPublicacaoPcaFixture::valido()),
            $configuracao,
            'usuario.teste'
        ))->all();

        $this->assertSame(
            ['ESTRATEGIA_PUBLICACAO_PLANOS', 'UASG_CONSOLIDADORA'],
            $consultas['estrategia_corrente']['binds']
        );
        $this->assertSame(2, $consultas['estrategia_corrente']['expected_cardinality']['exact']);
    }

    public function testArquivoManualDoPrimeiroCheckpointDeveConterSomenteOnzeSelects(): void
    {
        $path = __DIR__ . '/checkpoints/publicar_individual_precheck.sql';
        $contents = file_get_contents($path);

        $this->assertIsString($contents);
        $withoutComments = preg_replace('/^--.*$/m', '', $contents);
        $this->assertIsString($withoutComments);
        $statements = array_values(array_filter(
            array_map('trim', preg_split('/;\s*(?:\R|$)/', $withoutComments) ?: []),
            static fn (string $statement): bool => $statement !== ''
        ));

        $this->assertCount(11, $statements);
        foreach ($statements as $index => $statement) {
            $this->assertMatchesRegularExpression('/^SELECT\b/i', $statement, (string) $index);
            $this->assertDoesNotMatchRegularExpression(
                '/\b(?:INSERT|UPDATE|DELETE|MERGE|ALTER|DROP|CREATE|TRUNCATE|BEGIN|DECLARE)\b|FOR\s+UPDATE/i',
                $statement,
                (string) $index
            );
        }
    }

    public function testArquivoManualNenhumaAcaoDeveReusarOnzeSelectsSomenteLeitura(): void
    {
        $path = __DIR__ . '/checkpoints/nenhuma_acao_individual_precheck.sql';
        $contents = file_get_contents($path);

        $this->assertIsString($contents);
        $this->assertStringContainsString('@@publicar_individual_precheck.sql', $contents);
        $this->assertStringContainsString(
            'f9cfb8abb04aed362b9624637cbe0e9eab7cfb4bd8e68c1cec0126184b06ec5f',
            $contents
        );
        $this->assertStringContainsString('exatamente 3 âncoras permanentes', $contents);

        $canonical = file_get_contents(
            __DIR__ . '/checkpoints/publicar_individual_precheck.sql'
        );
        $this->assertIsString($canonical);
        $withoutComments = preg_replace('/^--.*$/m', '', $canonical);
        $this->assertIsString($withoutComments);
        $statements = array_values(array_filter(
            array_map('trim', preg_split('/;\s*(?:\R|$)/', $withoutComments) ?: []),
            static fn (string $statement): bool => $statement !== ''
        ));

        $this->assertCount(11, $statements);
        foreach ($statements as $statement) {
            $this->assertMatchesRegularExpression('/^SELECT\b/i', $statement);
            $this->assertDoesNotMatchRegularExpression(
                '/\b(?:INSERT|UPDATE|DELETE|MERGE|ALTER|DROP|CREATE|TRUNCATE|BEGIN|DECLARE)\b|FOR\s+UPDATE/i',
                $statement
            );
        }
    }

    public function testArquivoManualDaExclusaoDeveConterOnzeSelectsSomenteLeitura(): void
    {
        $path = __DIR__ . '/checkpoints/excluir_pca_individual_precheck.sql';

        $this->assertFileExists($path);
        $contents = file_get_contents($path);
        $this->assertIsString($contents);
        $this->assertStringContainsString('Cardinalidade: exatamente 0', $contents);
        $this->assertStringContainsString('Cardinalidade: exatamente 11', $contents);

        $withoutComments = preg_replace('/^--.*$/m', '', $contents);
        $this->assertIsString($withoutComments);
        $statements = array_values(array_filter(
            array_map('trim', preg_split('/;\s*(?:\R|$)/', $withoutComments) ?: []),
            static fn (string $statement): bool => $statement !== ''
        ));

        $this->assertCount(11, $statements);
        foreach ($statements as $statement) {
            $this->assertMatchesRegularExpression('/^SELECT\b/i', $statement);
            $this->assertDoesNotMatchRegularExpression(
                '/\b(?:INSERT|UPDATE|DELETE|MERGE|ALTER|DROP|CREATE|TRUNCATE|BEGIN|DECLARE)\b|FOR\s+UPDATE/i',
                $statement
            );
        }
    }

    public function testArquivoManualDaPublicacaoConsolidadaDeveConterOnzeSelectsSomenteLeitura(): void
    {
        $path = __DIR__ . '/checkpoints/publicar_consolidado_precheck.sql';

        $this->assertFileExists($path);
        $contents = file_get_contents($path);
        $this->assertIsString($contents);
        $this->assertStringContainsString('ESTRATEGIA_PUBLICACAO_PLANOS', $contents);
        $this->assertStringContainsString('UASG_CONSOLIDADORA', $contents);
        $this->assertStringContainsString('ANO_EXECUCAO = 2026', $contents);

        $withoutComments = preg_replace('/^--.*$/m', '', $contents);
        $this->assertIsString($withoutComments);
        $statements = array_values(array_filter(
            array_map('trim', preg_split('/;\s*(?:\R|$)/', $withoutComments) ?: []),
            static fn (string $statement): bool => $statement !== ''
        ));

        $this->assertCount(11, $statements);
        foreach ($statements as $statement) {
            $this->assertMatchesRegularExpression('/^SELECT\b/i', $statement);
            $this->assertDoesNotMatchRegularExpression(
                '/\b(?:INSERT|UPDATE|DELETE|MERGE|ALTER|DROP|CREATE|TRUNCATE|BEGIN|DECLARE)\b|FOR\s+UPDATE/i',
                $statement
            );
        }
    }

    public function testArquivoManualDaRetificacaoConsolidadaDeveConterOnzeSelectsSomenteLeitura(): void
    {
        $path = __DIR__ . '/checkpoints/retificar_consolidado_precheck.sql';

        $this->assertFileExists($path);
        $contents = file_get_contents($path);
        $this->assertIsString($contents);
        $this->assertStringContainsString('Microciclo 8.4.12', $contents);
        $this->assertStringContainsString('ANO_EXECUCAO = 2026', $contents);
        $this->assertStringContainsString('CONSOLIDADO/930281/PUBLICADO/36', $contents);
        $this->assertStringContainsString('Cardinalidade: exatamente 4', $contents);

        $withoutComments = preg_replace('/^--.*$/m', '', $contents);
        $this->assertIsString($withoutComments);
        $statements = array_values(array_filter(
            array_map('trim', preg_split('/;\s*(?:\R|$)/', $withoutComments) ?: []),
            static fn (string $statement): bool => $statement !== ''
        ));

        $this->assertCount(11, $statements);
        foreach ($statements as $statement) {
            $this->assertMatchesRegularExpression('/^SELECT\b/i', $statement);
            $this->assertDoesNotMatchRegularExpression(
                '/\b(?:INSERT|UPDATE|DELETE|MERGE|ALTER|DROP|CREATE|TRUNCATE|BEGIN|DECLARE)\b|FOR\s+UPDATE/i',
                $statement
            );
        }
    }

    public function testArquivoManualDaExclusaoConsolidadaDeveConterOnzeSelectsSomenteLeitura(): void
    {
        $path = __DIR__ . '/checkpoints/excluir_pca_consolidado_precheck.sql';

        $this->assertFileExists($path);
        $contents = file_get_contents($path);
        $this->assertIsString($contents);
        $this->assertStringContainsString('Microciclo 8.4.13', $contents);
        $this->assertStringContainsString('ANO_EXECUCAO = 2026', $contents);
        $this->assertStringContainsString('CONSOLIDADO/930281/PUBLICADO/36', $contents);
        $this->assertStringContainsString('Cardinalidade: exatamente 6', $contents);
        $this->assertStringContainsString('Cardinalidade: exatamente 0', $contents);

        $withoutComments = preg_replace('/^--.*$/m', '', $contents);
        $this->assertIsString($withoutComments);
        $statements = array_values(array_filter(
            array_map('trim', preg_split('/;\s*(?:\R|$)/', $withoutComments) ?: []),
            static fn (string $statement): bool => $statement !== ''
        ));

        $this->assertCount(11, $statements);
        foreach ($statements as $statement) {
            $this->assertMatchesRegularExpression('/^SELECT\b/i', $statement);
            $this->assertDoesNotMatchRegularExpression(
                '/\b(?:INSERT|UPDATE|DELETE|MERGE|ALTER|DROP|CREATE|TRUNCATE|BEGIN|DECLARE)\b|FOR\s+UPDATE/i',
                $statement
            );
        }
    }

    /** @return array<string, mixed> */
    private function configuracao(): array
    {
        return [
            'modo' => 'INDIVIDUAL',
            'ano' => 2027,
            'unidades' => [
                [
                    'id_unidade_orcamentaria' => 101,
                    'quantidade_versoes' => 1,
                    'quantidade_fotografia' => 2,
                    'quantidade_demandas' => 2,
                ],
                [
                    'id_unidade_orcamentaria' => 202,
                    'quantidade_versoes' => 1,
                    'quantidade_fotografia' => 2,
                    'quantidade_demandas' => 2,
                ],
            ],
            'baseline_auditoria' => 0,
        ];
    }
}
