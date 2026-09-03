<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint83;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Contract\Sprint83\Support\Sprint83Config;
use Tests\Contract\Support\SafeContractReport;

final class SafetyContractTest extends TestCase
{
    public function testRelatorioDeveRegistrarContratoSemAceitarDadosSensiveis(): void
    {
        $report = [
            'sprint' => '8.3',
            'manual_pncp_versao' => Sprint83Config::MANUAL_PNCP_VERSAO,
            'manual_pncp_data' => Sprint83Config::MANUAL_PNCP_DATA,
            'openapi_versao' => Sprint83Config::OPENAPI_VERSAO,
            'contrato_revalidado_em' => Sprint83Config::CONTRATO_REVALIDADO_EM,
            'confirmado' => true,
        ];

        $json = SafeContractReport::encode($report, ['segredo-externo']);

        $this->assertStringContainsString('"manual_pncp_versao": "2.6"', $json);
        $this->assertSame(64, strlen(SafeContractReport::hash($report)));

        $this->expectException(RuntimeException::class);
        SafeContractReport::encode($report + ['detalhe' => 'segredo-externo'], ['segredo-externo']);
    }

    public function testEntrypointsReaisDevemPermanecerEmGruposOptInSeparados(): void
    {
        $reflection = new \ReflectionClass(PncpTrainingContractTest::class);
        $methods = [
            'testDeveExecutarCicloNominalNoTreinamento' => 'pncp83-nominal-live',
            'testDeveExecutarEscritaIncertaNoTreinamento' => 'pncp83-uncertain-live',
        ];

        foreach ($methods as $method => $expectedGroup) {
            $attributes = $reflection->getMethod($method)->getAttributes(Group::class);
            $this->assertCount(1, $attributes);
            $this->assertSame($expectedGroup, $attributes[0]->newInstance()->name());
        }
    }
}
