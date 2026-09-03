<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint83;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Contract\Sprint83\Support\PncpTrainingContractRunner;
use Tests\Contract\Sprint83\Support\Sprint83Config;
use Tests\Contract\Support\SafeContractReport;

final class PncpTrainingContractTest extends TestCase
{
    #[Group('pncp83-nominal-live')]
    public function testDeveExecutarCicloNominalNoTreinamento(): void
    {
        $this->executeAndReport('runNominal');
    }

    #[Group('pncp83-uncertain-live')]
    public function testDeveExecutarEscritaIncertaNoTreinamento(): void
    {
        $this->executeAndReport('runUncertainWrite');
    }

    private function executeAndReport(string $method): void
    {
        $config = Sprint83Config::loadFromEnvironment();
        $profile = Sprint83Config::loadHomologationProfileArray(
            $config,
            $config['server_name']
        );
        $report = PncpTrainingContractRunner::fromEnvironment()->{$method}();
        $json = SafeContractReport::encode($report, $this->sensitiveValues($config, $profile));

        fwrite(STDOUT, PHP_EOL . $json . PHP_EOL . 'SHA256: ' . hash('sha256', $json) . PHP_EOL);

        $this->assertTrue($report['confirmado']);
        $this->assertTrue($report['limpeza_confirmada']);
        $this->assertTrue($report['eventos_sanitizados']);
        if ($method === 'runNominal') {
            $this->assertIsInt($report['sequencial_pca_recebido_excluido']);
            $this->assertGreaterThan(0, $report['sequencial_pca_recebido_excluido']);
        }
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $profile
     * @return string[]
     */
    private function sensitiveValues(array $config, array $profile): array
    {
        return [
            $profile['cnpj'],
            $profile['pncp']['login'],
            $profile['pncp']['senha'],
            $config['server_name'],
            $config['massa']['codigo_uasg'],
            $config['massa']['descricao_uasg'],
        ];
    }
}
