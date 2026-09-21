<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint85;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Contract\Sprint84\Support\Sprint84ConfigurationException;
use Tests\Contract\Sprint85\Support\Sprint85Config;

final class Sprint85ConfigTest extends TestCase
{
    public function testPrecheckDeveAceitarSomenteLeituraESemConsentimentosDeEscrita(): void
    {
        $configuration = Sprint85Config::validate($this->validConfiguration());

        $this->assertFalse($configuration['allow_oracle_writes']);
        $this->assertFalse($configuration['allow_pncp_writes']);
        $this->assertFalse($configuration['allow_log_writes']);
        $this->assertSame('CONSOLIDADO', $configuration['modo']);
        $this->assertSame([16, 17, 18], $configuration['sequenciais_legados']);
        $this->assertSame(36, $configuration['sequencial_pca_excluido']);
        $this->assertSame(103, $configuration['id_plano_sem_confirmacao']);
    }

    #[DataProvider('realStages')]
    public function testEtapasReaisDevemExigirHashEConsentimentosExatos(
        string $stage,
        bool $oracleWrites
    ): void {
        $configuration = $this->validConfiguration();
        $configuration['etapa_autorizada'] = $stage;
        $configuration['allow_oracle_writes'] = $oracleWrites;
        $configuration['allow_log_writes'] = true;
        $configuration['oracle_checkpoint_sha256'] = str_repeat('a', 64);

        $validated = Sprint85Config::validate($configuration);

        $this->assertSame($stage, $validated['etapa_autorizada']);
        $this->assertFalse($validated['allow_pncp_writes']);
    }

    /** @return array<string, array{string, bool}> */
    public static function realStages(): array
    {
        return [
            'logs' => ['LOGS', false],
            'contenção' => ['CONTENCAO', false],
            'smoke' => ['SMOKE', true],
        ];
    }

    public function testDeveRecusarMutacaoPncpEmQualquerEtapa(): void
    {
        $configuration = $this->validConfiguration();
        $configuration['allow_pncp_writes'] = true;

        $this->expectException(Sprint84ConfigurationException::class);
        $this->expectExceptionMessage('CONFIG_CONSENTIMENTO_INVALIDO');

        Sprint85Config::validate($configuration);
    }

    public function testDeveAceitarTresUosNaFotografiaConsolidadaAtual(): void
    {
        $configuration = $this->validConfiguration();
        $configuration['unidades'][] = [
            'id_unidade_orcamentaria' => 149,
            'quantidade_versoes' => 71,
            'quantidade_fotografia' => 71,
            'quantidade_demandas' => 71,
        ];

        $validated = Sprint85Config::validate($configuration);

        $this->assertCount(3, $validated['unidades']);
    }

    public function testDeveRecusarMudancaNaMassaProtegida(): void
    {
        $configuration = $this->validConfiguration();
        $configuration['sequencial_pca_excluido'] = 37;

        $this->expectException(Sprint84ConfigurationException::class);
        $this->expectExceptionMessage('CONFIG_MASSA_INVALIDA');

        Sprint85Config::validate($configuration);
    }

    /** @return array<string, mixed> */
    private function validConfiguration(): array
    {
        return [
            'allow_oracle_writes' => false,
            'allow_pncp_writes' => false,
            'allow_log_writes' => false,
            'server_name' => 'pca.homologacao.test',
            'modo' => 'CONSOLIDADO',
            'ano' => 2026,
            'etapa_autorizada' => 'PRECHECK',
            'unidades' => [
                [
                    'id_unidade_orcamentaria' => 1,
                    'quantidade_versoes' => 3,
                    'quantidade_fotografia' => 0,
                    'quantidade_demandas' => 2,
                ],
                [
                    'id_unidade_orcamentaria' => 3,
                    'quantidade_versoes' => 3,
                    'quantidade_fotografia' => 0,
                    'quantidade_demandas' => 1,
                ],
            ],
            'oracle_checkpoint_sha256' => null,
            'identificador_log' => 'SPRINT85-TESTE-LOCAL',
            'quantidade_logs_esperada' => 0,
            'sequenciais_legados' => [16, 17, 18],
            'sequencial_pca_excluido' => 36,
            'id_plano_sem_confirmacao' => 103,
            'baseline_auditoria' => 3,
        ];
    }
}
